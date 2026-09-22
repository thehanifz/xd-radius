#!/usr/bin/env bash
set -u
set -o pipefail

# xd-radius Unified Setup Script
#
# ONE entrypoint:
#   ./setup.sh                 -> full setup (default)
#   ./setup.sh setup           -> full setup
#   ./setup.sh check           -> prerequisite check only
#   ./setup.sh install         -> install missing OS packages, then re-check
#   ./setup.sh setup --yes     -> full setup without confirmation
#
# This script intentionally does not call another project setup script.

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$SCRIPT_DIR"
ARTISAN="$ROOT/artisan"

MODE="setup"
ASSUME_YES=false
FAIL_COUNT=0
WARN_COUNT=0
OK_COUNT=0

say() { printf '%s\n' "$*"; }
die() { printf '\n[ERROR] %s\n' "$*" >&2; exit 1; }
run() { printf '+'; printf ' %q' "$@"; printf '\n'; "$@"; }
command_exists() { command -v "$1" >/dev/null 2>&1; }

ok() { OK_COUNT=$((OK_COUNT + 1)); printf '  [OK] %s\n' "$*"; }
warn() { WARN_COUNT=$((WARN_COUNT + 1)); printf '  [WARN] %s\n' "$*"; }
fail() { FAIL_COUNT=$((FAIL_COUNT + 1)); printf '  [FAIL] %s\n' "$*"; }

version_ge() {
  # version_ge INSTALLED REQUIRED
  dpkg --compare-versions "$1" ge "$2" 2>/dev/null
}

confirm() {
  local prompt="$1"
  [ "$ASSUME_YES" = true ] && return 0
  while true; do
    read -r -p "$prompt [y/N]: " answer
    case "${answer,,}" in
      y|yes) return 0 ;;
      n|no|"") return 1 ;;
      *) say "Please answer yes or no." ;;
    esac
  done
}

require_root() {
  [ "$(id -u)" -eq 0 ] || die "This operation must run as root."
}

php_ext_missing=()

check_os() {
  say ""
  say "[OS / SYSTEM]"
  if [ -r /etc/os-release ]; then
    . /etc/os-release
    ok "OS: ${PRETTY_NAME:-unknown}"
  else
    warn "Cannot identify OS."
  fi

  command_exists systemctl && ok "systemd available" || fail "systemd not available"
  command_exists sudo && ok "sudo available" || fail "sudo not available"
}

check_php() {
  say ""
  say "[PHP]"
  if ! command_exists php; then
    fail "PHP not installed."
    return
  fi

  local phpver
  phpver="$(php -r 'echo PHP_VERSION;' 2>/dev/null || true)"
  if [ -n "$phpver" ] && dpkg --compare-versions "$phpver" ge "8.2"; then
    ok "PHP $phpver (>= 8.2)"
  else
    fail "PHP ${phpver:-unknown} (< 8.2 or unreadable)"
  fi

  local required_ext
  required_ext=(pdo pdo_pgsql mbstring openssl tokenizer xml ctype json bcmath fileinfo curl zip intl)
  php_ext_missing=()
  for required_ext in "${required_ext[@]}"; do
    if php -m 2>/dev/null | grep -Fxqi "$required_ext"; then
      :
    else
      php_ext_missing+=("$required_ext")
    fi
  done

  if [ "${#php_ext_missing[@]}" -eq 0 ]; then
    ok "Required PHP extensions present"
  else
    fail "Missing PHP extensions: ${php_ext_missing[*]}"
  fi
}

check_composer() {
  say ""
  say "[COMPOSER]"
  if command_exists composer; then
    ok "Composer $(composer --version --no-ansi 2>/dev/null | sed -E 's/^Composer version ([^ ]+).*/\1/' | head -n1)"
  else
    fail "Composer not installed."
  fi
}

check_node() {
  say ""
  say "[NODE / NPM]"
  if command_exists node; then
    ok "Node.js $(node -v 2>/dev/null | sed 's/^v//')"
  else
    fail "Node.js not installed."
  fi

  if command_exists npm; then
    ok "npm $(npm -v 2>/dev/null)"
  else
    fail "npm not installed."
  fi
}

check_postgres() {
  say ""
  say "[POSTGRESQL]"

  if command_exists psql; then
    local psqlver
    psqlver="$(psql --version | sed -E 's/.* ([0-9]+(\.[0-9]+)?).*/\1/')"
    if dpkg --compare-versions "$psqlver" ge "14"; then
      ok "PostgreSQL client $psqlver (>= 14)"
    else
      fail "PostgreSQL client $psqlver (< 14)"
    fi
  else
    fail "PostgreSQL client (psql) not installed."
  fi

  local found=false
  if command_exists systemctl && systemctl list-unit-files 2>/dev/null | grep -q '^postgresql\.service'; then
    if systemctl is-active --quiet postgresql; then
      ok "PostgreSQL native service active"
      found=true
    elif systemctl is-enabled --quiet postgresql 2>/dev/null; then
      warn "PostgreSQL native service exists but is not active."
      found=true
    fi
  fi

  if command_exists docker; then
    local containers
    containers="$(docker ps --format '{{.Names}}|{{.Image}}|{{.Ports}}' 2>/dev/null | grep -Ei '(^|[|/ ])postgres([|/: ]|$)' || true)"
    if [ -n "$containers" ]; then
      ok "PostgreSQL container detected"
      while IFS= read -r line; do
        [ -n "$line" ] && printf '       container=%s\n' "$line"
      done <<< "$containers"
      found=true
    fi
  fi

  if [ "$found" = false ]; then
    warn "No active native PostgreSQL service or running PostgreSQL Docker container detected."
  fi
}

check_freeradius() {
  say ""
  say "[FREERADIUS]"

  local binary=""
  if command_exists freeradius; then
    binary="$(command -v freeradius)"
  elif command_exists radiusd; then
    binary="$(command -v radiusd)"
  fi

  if [ -z "$binary" ]; then
    fail "FreeRADIUS binary not installed."
    return
  fi

  local version
  version="$("$binary" -v 2>&1 | grep -Eo 'FreeRADIUS Version [0-9.]+' | head -n1 | sed 's/.*Version //' || true)"
  [ -n "$version" ] && ok "FreeRADIUS $version ($binary)" || warn "FreeRADIUS binary found but version could not be parsed."

  if systemctl list-unit-files 2>/dev/null | grep -q '^freeradius\.service'; then
    if systemctl is-active --quiet freeradius; then
      ok "freeradius.service is active"
    else
      warn "freeradius.service exists but is not active."
    fi
  else
    warn "freeradius.service not found."
  fi

  if "$binary" -XC >/tmp/xd-radius-freeradius-check.$$ 2>&1; then
    ok "FreeRADIUS configuration validation passed"
  else
    fail "FreeRADIUS configuration validation failed"
    sed -n '1,40p' /tmp/xd-radius-freeradius-check.$$ 2>/dev/null || true
  fi
  rm -f /tmp/xd-radius-freeradius-check.$$

  local schema=""
  for candidate in \
    /etc/freeradius/3.0/mods-config/sql/main/postgresql/schema.sql \
    /etc/freeradius/mods-config/sql/main/postgresql/schema.sql \
    /etc/raddb/mods-config/sql/main/postgresql/schema.sql; do
    if [ -f "$candidate" ]; then
      schema="$candidate"
      break
    fi
  done
  if [ -n "$schema" ]; then
    ok "PostgreSQL vendor schema found: $schema"
  else
    fail "FreeRADIUS PostgreSQL vendor schema not found."
  fi
}

check_application() {
  say ""
  say "[APPLICATION]"

  if [ -f "$ARTISAN" ] && [ -f "$ROOT/composer.json" ]; then
    ok "xd-radius Laravel application detected: $ROOT"
  else
    fail "Laravel application files not found."
  fi

  [ -f "$ROOT/composer.lock" ] && ok "composer.lock present" || warn "composer.lock missing"
  [ -f "$ROOT/package-lock.json" ] && ok "package-lock.json present" || warn "package-lock.json missing"
  [ -d "$ROOT/vendor" ] && ok "vendor/ present" || fail "vendor/ missing; run composer install"
}

check_privileges() {
  say ""
  say "[PRIVILEGES]"

  local web_user="www-data"
  if id "$web_user" >/dev/null 2>&1; then
    printf '       Likely web/PHP-FPM user: %s\n' "$web_user"
    if sudo -u "$web_user" sudo -n true >/dev/null 2>&1; then
      ok "sudo can execute as $web_user"
    else
      warn "sudo can execute as root, but www-data sudo capability is not configured yet."
    fi
  else
    warn "www-data user not found."
  fi
}

run_preflight() {
  OK_COUNT=0
  WARN_COUNT=0
  FAIL_COUNT=0

  say ""
  say "xd-radius Server Preflight"
  say "========================="
  say "Mode: CHECK / VALIDATE (no host changes)"

  check_os
  check_php
  check_composer
  check_node
  check_postgres
  check_freeradius
  check_application
  check_privileges

  say ""
  say "Summary"
  say "-------"
  say "  OK   : $OK_COUNT"
  say "  WARN : $WARN_COUNT"
  say "  FAIL : $FAIL_COUNT"

  if [ "$FAIL_COUNT" -gt 0 ]; then
    say ""
    say "  [FAIL] Environment is NOT ready."
    return 1
  fi

  say ""
  say "  [OK] Environment passed prerequisite validation."
  return 0
}

apt_install_missing() {
  require_root
  . /etc/os-release 2>/dev/null || true

  case "${ID:-}" in
    debian|ubuntu)
      ;;
    *)
      die "Automatic package installation is supported only on Debian/Ubuntu."
      ;;
  esac

  local packages=()

  command_exists php || packages+=(php-cli php-fpm)
  command_exists composer || packages+=(composer)
  command_exists node || packages+=(nodejs)
  command_exists npm || packages+=(npm)
  command_exists psql || packages+=(postgresql-client)
  command_exists sudo || packages+=(sudo)

  for ext in "${php_ext_missing[@]}"; do
    case "$ext" in
      pdo|openssl|tokenizer|ctype|json|fileinfo) ;;
      pdo_pgsql) packages+=(php-pgsql) ;;
      mbstring) packages+=(php-mbstring) ;;
      xml) packages+=(php-xml) ;;
      bcmath) packages+=(php-bcmath) ;;
      curl) packages+=(php-curl) ;;
      zip) packages+=(php-zip) ;;
      intl) packages+=(php-intl) ;;
    esac
  done

  if [ "${#packages[@]}" -eq 0 ]; then
    say "[OK] No missing apt packages detected."
    return 0
  fi

  # De-duplicate package list.
  local unique_packages
  unique_packages="$(printf '%s\n' "${packages[@]}" | awk '!seen[$0]++' | tr '\n' ' ')"

  say ""
  say "Packages that may be installed:"
  say "  $unique_packages"
  say ""

  confirm "Install missing OS packages?" || {
    say "Installation cancelled."
    return 1
  }

  run apt-get update
  # shellcheck disable=SC2086
  run apt-get install -y $unique_packages
}

install_privileged_helper() {
  require_root

  local helper_src="$ROOT/scripts/xd-radius-freeradius-privileged"
  local helper_dst="/usr/local/sbin/xd-radius-freeradius"
  local sudoers_dst="/etc/sudoers.d/xd-radius-freeradius"
  local staging="/var/lib/xd-radius-freeradius/staging"

  [ -f "$helper_src" ] || die "Privileged helper source not found: $helper_src"

  install -o root -g root -m 0755 "$helper_src" "$helper_dst"
  install -d -o www-data -g www-data -m 0750 "$staging"

  cat > "$sudoers_dst" <<EOF
# xd-radius: tightly scoped FreeRADIUS management helper
www-data ALL=(root) NOPASSWD: $helper_dst
EOF
  chmod 0440 "$sudoers_dst"
  visudo -cf "$sudoers_dst" >/dev/null || die "Generated sudoers file failed validation."

  local env_file="$ROOT/.env"
  if [ -f "$env_file" ]; then
    if grep -q '^FREERADIUS_USE_SUDO=' "$env_file"; then
      sed -i 's/^FREERADIUS_USE_SUDO=.*/FREERADIUS_USE_SUDO=true/' "$env_file"
    else
      printf '\nFREERADIUS_USE_SUDO=true\n' >> "$env_file"
    fi

    if grep -q '^FREERADIUS_PRIVILEGED_HELPER=' "$env_file"; then
      sed -i 's#^FREERADIUS_PRIVILEGED_HELPER=.*#FREERADIUS_PRIVILEGED_HELPER=/usr/local/sbin/xd-radius-freeradius#' "$env_file"
    else
      printf 'FREERADIUS_PRIVILEGED_HELPER=/usr/local/sbin/xd-radius-freeradius\n' >> "$env_file"
    fi
  fi

  sudo -u www-data sudo -n "$helper_dst" is-active freeradius >/dev/null \
    || die "Privileged helper verification failed: www-data cannot invoke helper."

  ok "Privileged FreeRADIUS helper installed"
  ok "Sudoers policy validated"
  ok "Staging directory ready: $staging"
}

run_setup() {
  require_root
  [ -f "$ARTISAN" ] || die "Laravel artisan not found: $ARTISAN"
  [ -f "$ROOT/composer.json" ] || die "composer.json not found: $ROOT"
  [ -d "$ROOT/vendor" ] || die "vendor/ not found. Run composer install first."
  cd "$ROOT" || die "Cannot enter project root: $ROOT"

  say ""
  say "[1/7] Server prerequisite preflight"
  run_preflight || die "Prerequisite validation failed."

  say ""
  say "[2/7] FreeRADIUS privileged helper"
  install_privileged_helper

  say ""
  say "[3/7] Laravel database migration"
  run php "$ARTISAN" migrate --force || die "Laravel migration failed."

  say ""
  say "Verifying migration state..."
  local migration_output
  migration_output="$(php "$ARTISAN" migrate:status 2>&1)"
  printf '%s\n' "$migration_output"
  if printf '%s\n' "$migration_output" | grep -Eq '\bPending\b'; then
    die "There are still pending Laravel migrations after migrate --force."
  fi

  say ""
  say "[4/7] RADIUS database connectivity"
  php "$ARTISAN" tinker --execute='
try {
    DB::connection("radius")->getPdo();
    echo "RADIUS_DB_OK\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "RADIUS_DB_FAILED: ".$e->getMessage()."\n");
    exit(1);
}
' || die "RADIUS database connection failed."

  say ""
  say "[5/7] FreeRADIUS setup service"
  run php "$ARTISAN" freeradius:setup || die "FreeRADIUS setup service failed."

  say ""
  say "[6/7] FreeRADIUS configuration validation"
  if command_exists freeradius; then
    run freeradius -XC || die "FreeRADIUS configuration validation failed."
  elif command_exists radiusd; then
    run radiusd -XC || die "FreeRADIUS configuration validation failed."
  else
    die "FreeRADIUS binary not found."
  fi

  say ""
  say "[7/7] FreeRADIUS service / health verification"
  if ! systemctl is-active --quiet freeradius; then
    systemctl status freeradius --no-pager || true
    journalctl -u freeradius -n 50 --no-pager || true
    die "FreeRADIUS service is not healthy."
  fi
  ok "freeradius.service active"

  run php "$ARTISAN" freeradius:health-check || die "Application FreeRADIUS health check failed."

  say ""
  say "=================================="
  say "[OK] xd-radius setup completed successfully."
  say "=================================="
}

run_install() {
  require_root
  say ""
  say "[1/2] Current prerequisite check"
  run_preflight || true
  say ""
  say "[2/2] Install missing OS packages"
  apt_install_missing
  say ""
  say "Re-running prerequisite validation..."
  run_preflight || die "Environment is still not ready after installation."
  say ""
  say "[OK] Prerequisites are ready."
}

for arg in "$@"; do
  case "$arg" in
    --yes|-y) ASSUME_YES=true ;;
  esac
done

case "${1:-setup}" in
  setup)
    run_setup
    ;;
  check)
    run_preflight
    ;;
  install)
    run_install
    ;;
  -h|--help)
    cat <<'EOF'
xd-radius Unified Setup

Usage:
  ./setup.sh              Full setup (default)
  ./setup.sh setup        Full setup
  ./setup.sh check        Check prerequisites only
  ./setup.sh install      Install missing OS packages, then validate
  ./setup.sh setup --yes  Full setup without confirmations

The script is self-contained. It does not call installation-requirements.sh
or another setup script.
EOF
    ;;
  *)
    die "Unknown mode: ${1:-}. Use setup, check, or install."
    ;;
esac
