#!/usr/bin/env bash
set -u
set -o pipefail

# xd-radius Unified Setup Script.
# Dedicated Artisan commands are used for FreeRADIUS setup and health checks;
# `artisan tinker --execute` routes exit() through Psy Shell and can report a
# false shell-level failure after a successful setup.

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$SCRIPT_DIR"
ARTISAN="$ROOT/artisan"

say() { printf '%s\n' "$*"; }
die() { printf '\n[ERROR] %s\n' "$*" >&2; exit 1; }
run() { printf '+'; printf ' %q' "$@"; printf '\n'; "$@"; }
command_exists() { command -v "$1" >/dev/null 2>&1; }

run_checks() {
  ./setup.sh check
}

install_privileged_helper() {
  local helper_src="$ROOT/scripts/xd-radius-freeradius-privileged"
  local helper_dst="/usr/local/sbin/xd-radius-freeradius"
  local sudoers_dst="/etc/sudoers.d/xd-radius-freeradius"
  local staging="/var/lib/xd-radius-freeradius/staging"

  [ "$(id -u)" -eq 0 ] || die "Full setup must run as root so the scoped FreeRADIUS helper can be installed."
  [ -f "$helper_src" ] || die "Privileged helper source not found: $helper_src"

  install -o root -g root -m 0755 "$helper_src" "$helper_dst"
  install -d -o www-data -g www-data -m 0750 "$staging"

  cat > "$sudoers_dst" <<EOF
# xd-radius: tightly scoped FreeRADIUS management helper
www-data ALL=(root) NOPASSWD: $helper_dst
EOF
  chmod 0440 "$sudoers_dst"
  visudo -cf "$sudoers_dst" || die "Generated sudoers file failed validation: $sudoers_dst"

  local env_file="$ROOT/.env"
  if [ -f "$env_file" ]; then
    grep -q '^FREERADIUS_USE_SUDO=' "$env_file" \
      && sed -i 's/^FREERADIUS_USE_SUDO=.*/FREERADIUS_USE_SUDO=true/' "$env_file" \
      || printf '\nFREERADIUS_USE_SUDO=true\n' >> "$env_file"
    grep -q '^FREERADIUS_PRIVILEGED_HELPER=' "$env_file" \
      && sed -i 's#^FREERADIUS_PRIVILEGED_HELPER=.*#FREERADIUS_PRIVILEGED_HELPER=/usr/local/sbin/xd-radius-freeradius#' "$env_file" \
      || printf 'FREERADIUS_PRIVILEGED_HELPER=/usr/local/sbin/xd-radius-freeradius\n' >> "$env_file"
  fi

  sudo -u www-data sudo -n "$helper_dst" is-active freeradius >/dev/null \
    || die "Privileged helper verification failed (www-data cannot invoke $helper_dst via sudo)."

  say "  [OK] Installed $helper_dst"
  say "  [OK] Installed $sudoers_dst"
  say "  [OK] Staging: $staging"
}

run_setup() {
  [ -f "$ARTISAN" ] || die "Laravel artisan not found. Expected: $ROOT/artisan"
  [ -f "$ROOT/composer.json" ] || die "composer.json not found: $ROOT"
  [ -d "$ROOT/vendor" ] || die "vendor/ not found. Run composer install first."
  cd "$ROOT" || die "Cannot enter project root: $ROOT"

  say ""; say "[1/7] Server prerequisite preflight"
  ./setup.sh check || die "Prerequisite validation failed."

  say ""; say "[2/7] FreeRADIUS privileged helper"
  install_privileged_helper

  say ""; say "[3/7] Laravel database migration"
  run php "$ARTISAN" migrate --force || die "Laravel migration failed."
  say ""; say "Verifying migration state..."
  migration_output="$(php "$ARTISAN" migrate:status 2>&1)"
  printf '%s\n' "$migration_output"
  printf '%s\n' "$migration_output" | grep -Eq '\bPending\b' && die "There are still pending Laravel migrations after migrate --force."

  say ""; say "[4/7] RADIUS database connectivity"
  php "$ARTISAN" tinker --execute='try { DB::connection("radius")->getPdo(); echo "RADIUS_DB_OK\n"; } catch (\Throwable $e) { fwrite(STDERR, "RADIUS_DB_FAILED: ".$e->getMessage()."\n"); exit(1); }' \
    || die "RADIUS database connection failed. Check RADIUS_DB_* / DB_* in .env."

  say ""; say "[5/7] FreeRADIUS setup service"
  run php "$ARTISAN" freeradius:setup || die "FreeRADIUS setup service failed."

  say ""; say "[6/7] FreeRADIUS configuration validation"
  if command_exists freeradius; then
    run freeradius -XC || die "FreeRADIUS configuration validation failed."
  elif command_exists radiusd; then
    run radiusd -XC || die "FreeRADIUS configuration validation failed."
  else
    die "FreeRADIUS binary not found."
  fi

  say ""; say "[7/7] FreeRADIUS service / health verification"
  if ! systemctl is-active --quiet freeradius; then
    systemctl status freeradius --no-pager || true
    journalctl -u freeradius -n 50 --no-pager || true
    die "FreeRADIUS service is not healthy."
  fi
  say "  [OK] freeradius.service active"
  run php "$ARTISAN" freeradius:health-check || die "Application FreeRADIUS health check failed."

  say ""; say "=================================="
  say "[OK] xd-radius setup completed successfully."
  say "=================================="
}

case "${1:-check}" in
  setup) shift; run_setup "$@" ;;
  check|install) exec "${BASH_SOURCE[0]}" "${1:-check}" ;;
  *) die "Unknown mode: ${1:-}" ;;
esac
