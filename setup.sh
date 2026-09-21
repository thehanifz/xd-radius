#!/usr/bin/env bash
set -u
set -o pipefail

# =============================================================================
# xd-radius — Unified Setup Script
# =============================================================================
# Consolidates:
#   - installation-requirements.sh  (server prerequisite check/install)
#   - setup-artisan.sh              (Laravel migrate + FreeRADIUS orchestration)
#   - scripts/install-freeradius-helper.sh (privileged helper installer)
#
# Modes:
#   ./setup.sh check              Validate only. No host changes. (default)
#   ./setup.sh install [--yes]    Validate, then apt-get install missing packages.
#   ./setup.sh setup   [--yes]    Full setup: preflight -> privileged helper ->
#                                  migrate -> RADIUS DB check -> FreeRADIUS setup
#                                  -> validate -> health check.
#
# The privileged FreeRADIUS helper binary (scripts/xd-radius-freeradius-privileged)
# is a separate, narrowly-scoped root-owned script and is NOT merged here — it must
# remain standalone because it is installed to /usr/local/sbin and invoked via a
# dedicated sudoers entry.
# =============================================================================

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$SCRIPT_DIR"
ARTISAN="$ROOT/artisan"

SCRIPT_NAME="$(basename "$0")"
REQUIRED_PHP_MAJOR_MINOR="8.2"
MIN_POSTGRES_MAJOR=14
MIN_FREERADIUS_MAJOR=3
REQUIRED_PHP_EXTENSIONS=(pdo pdo_pgsql mbstring openssl tokenizer xml ctype json bcmath fileinfo curl zip intl)

PASS=0
WARN=0
FAIL=0
INSTALL_ITEMS=()
OS_FAMILY="unknown"
PKG_MANAGER=""

MODE="check"
ASSUME_YES=false

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

say() { printf '%s\n' "$*"; }
ok() { PASS=$((PASS+1)); printf '  [OK] %s\n' "$*"; }
warn() { WARN=$((WARN+1)); printf '  [WARN] %s\n' "$*"; }
fail() { FAIL=$((FAIL+1)); printf '  [FAIL] %s\n' "$*"; }
info() { printf '       %s\n' "$*"; }
die() { printf '\n[ERROR] %s\n' "$*" >&2; exit 1; }
run() { printf '+'; printf ' %q' "$@"; printf '\n'; "$@"; }

command_exists() { command -v "$1" >/dev/null 2>&1; }

version_ge() {
  # version_ge actual required; uses sort -V available on Debian/Ubuntu.
  [ "$(printf '%s\n%s\n' "$2" "$1" | sort -V | head -n1)" = "$2" ]
}

add_install() {
  INSTALL_ITEMS+=("$1|$2")
}

confirm() {
  local prompt="$1"
  if [ "$ASSUME_YES" = true ]; then
    return 0
  fi
  while true; do
    read -r -p "$prompt [y/N]: " answer
    case "${answer,,}" in
      y|yes) return 0 ;;
      n|no|"") return 1 ;;
      *) echo "Please answer yes or no." ;;
    esac
  done
}

usage() {
  cat <<USAGE
xd-radius Unified Setup

Usage: ${SCRIPT_NAME} [check|install|setup] [--yes]

Modes:
  check     Detect and validate server prerequisites. No host changes. (default)
  install   Run checks, show missing installable packages, then ask for yes/no
            confirmation before modifying the OS (apt-get install).
  setup     Full setup: preflight -> privileged FreeRADIUS helper -> Laravel
            migration -> RADIUS DB connectivity -> FreeRADIUS setup service ->
            configuration validation -> health check.

Flags:
  --yes, -y   Skip interactive confirmation prompts.
  --help, -h  Show this help.

Examples:
  ./${SCRIPT_NAME}
  ./${SCRIPT_NAME} check
  sudo ./${SCRIPT_NAME} install --yes
  sudo ./${SCRIPT_NAME} setup
USAGE
}

# ---------------------------------------------------------------------------
# [1] Server prerequisite checks (from installation-requirements.sh)
# ---------------------------------------------------------------------------

detect_os() {
  if [ -r /etc/os-release ]; then
    . /etc/os-release
    case "${ID:-}" in
      debian|ubuntu)
        OS_FAMILY="${ID}"
        PKG_MANAGER="apt-get"
        ok "OS: ${PRETTY_NAME:-$ID}"
        ;;
      *)
        warn "OS detected: ${PRETTY_NAME:-unknown}. Debian/Ubuntu package installation is not automated."
        ;;
    esac
  else
    fail "Cannot detect OS (/etc/os-release missing)."
  fi

  if command_exists systemctl; then
    ok "systemd available"
  else
    fail "systemd/systemctl is required for FreeRADIUS service management."
  fi

  if command_exists sudo; then
    ok "sudo available"
  else
    warn "sudo is not installed; privileged FreeRADIUS management will need another approved mechanism."
    add_install "sudo" "sudo"
  fi
}

check_php() {
  if ! command_exists php; then
    fail "PHP is not installed."
    add_install "PHP + required extensions" "php-cli php-fpm php-pgsql php-mbstring php-xml php-curl php-zip php-bcmath php-intl"
    return
  fi

  local version
  version="$(php -r 'echo PHP_VERSION;' 2>/dev/null)"
  if version_ge "$version" "$REQUIRED_PHP_MAJOR_MINOR"; then
    ok "PHP ${version} (>= ${REQUIRED_PHP_MAJOR_MINOR})"
  else
    fail "PHP ${version} is below required ${REQUIRED_PHP_MAJOR_MINOR}."
  fi

  local missing=()
  local ext
  for ext in "${REQUIRED_PHP_EXTENSIONS[@]}"; do
    if php -m 2>/dev/null | grep -qi "^${ext}$"; then
      :
    else
      missing+=("$ext")
    fi
  done
  if [ "${#missing[@]}" -eq 0 ]; then
    ok "Required PHP extensions present"
  else
    fail "Missing PHP extensions: ${missing[*]}"
    add_install "Missing PHP extensions" "${missing[*]}"
  fi
}

check_composer() {
  if command_exists composer; then
    local v
    v="$(composer --version 2>/dev/null | sed -n 's/.*Composer version \([^ ]*\).*/\1/p')"
    ok "Composer ${v:-installed}"
  else
    fail "Composer is not installed."
    add_install "Composer" "composer"
  fi
}

check_node() {
  if command_exists node; then
    ok "Node.js $(node -v 2>/dev/null | sed 's/^v//')"
  else
    fail "Node.js is not installed."
    add_install "Node.js/npm" "nodejs npm"
  fi

  if command_exists npm; then
    ok "npm $(npm -v 2>/dev/null)"
  else
    fail "npm is not installed."
    add_install "npm" "npm"
  fi
}

check_postgres() {
  if command_exists psql; then
    local v major
    v="$(psql --version | sed -n 's/.* \([0-9][0-9.]*\).*/\1/p')"
    major="${v%%.*}"
    if [ -n "$major" ] && [ "$major" -ge "$MIN_POSTGRES_MAJOR" ]; then
      ok "PostgreSQL client ${v} (>= ${MIN_POSTGRES_MAJOR})"
    else
      fail "PostgreSQL client ${v:-unknown} is below required ${MIN_POSTGRES_MAJOR}."
    fi
  else
    fail "PostgreSQL client (psql) is not installed."
    add_install "PostgreSQL client" "postgresql-client"
  fi

  local native=false
  if command_exists systemctl && systemctl list-unit-files 'postgresql*.service' 2>/dev/null | grep -q postgresql; then
    native=true
    if systemctl is-active --quiet postgresql 2>/dev/null || systemctl list-units --type=service --state=active 'postgresql@*.service' 2>/dev/null | grep -q 'postgresql@'; then
      ok "PostgreSQL native service is active"
    else
      warn "PostgreSQL native service is installed but not active."
    fi
  fi

  local docker_found=false
  if command_exists docker && docker info >/dev/null 2>&1; then
    local ids
    ids="$(docker ps --format '{{.ID}}\t{{.Names}}\t{{.Image}}\t{{.Ports}}' | grep -Ei 'postgres|postgre' || true)"
    if [ -n "$ids" ]; then
      docker_found=true
      ok "PostgreSQL container detected"
      while IFS=$'\t' read -r cid cname cimage cports; do
        [ -z "$cid" ] && continue
        info "container=${cname} image=${cimage} ports=${cports}"
      done <<< "$ids"
    fi
  fi

  if [ "$native" = false ] && [ "$docker_found" = false ]; then
    warn "No active PostgreSQL server detected locally (native or Docker)."
    add_install "PostgreSQL server" "postgresql"
  fi
}

check_freeradius() {
  local bin=""
  if command_exists freeradius; then bin="$(command -v freeradius)"; fi
  if [ -z "$bin" ] && command_exists radiusd; then bin="$(command -v radiusd)"; fi

  if [ -z "$bin" ]; then
    fail "FreeRADIUS is not installed."
    add_install "FreeRADIUS + PostgreSQL module" "freeradius freeradius-postgresql"
    return
  fi

  local output version major
  output="$($bin -v 2>&1)"
  version="$(printf '%s\n' "$output" | sed -n 's/.*FreeRADIUS Version \([0-9][0-9.]*\).*/\1/p' | head -n1)"
  major="${version%%.*}"
  if [ -n "$version" ] && [ "$major" -ge "$MIN_FREERADIUS_MAJOR" ]; then
    ok "FreeRADIUS ${version} (${bin})"
  else
    fail "Unable to determine a supported FreeRADIUS version."
  fi

  if command_exists systemctl; then
    if systemctl list-unit-files freeradius.service >/dev/null 2>&1; then
      if systemctl is-active --quiet freeradius; then
        ok "freeradius.service is active"
      else
        warn "freeradius.service exists but is not active."
      fi
    else
      fail "freeradius.service is not registered with systemd."
    fi
  fi

  local config_test
  config_test="$($bin -XC 2>&1)"
  if printf '%s\n' "$config_test" | grep -q 'Configuration appears to be OK'; then
    ok "FreeRADIUS configuration validation passed"
  else
    fail "FreeRADIUS configuration validation failed."
    info "Run: sudo ${bin} -XC"
  fi

  local schema
  schema="$(find /etc/freeradius /etc/raddb -type f -path '*/mods-config/sql/main/postgresql/schema.sql' 2>/dev/null | head -n1)"
  if [ -n "$schema" ]; then
    ok "PostgreSQL vendor schema found: ${schema}"
  else
    warn "FreeRADIUS PostgreSQL vendor schema.sql was not found."
    add_install "FreeRADIUS PostgreSQL SQL module/schema" "freeradius-postgresql"
  fi
}

check_app_files() {
  if [ -f "$ROOT/artisan" ]; then
    ok "xd-radius Laravel application detected: ${ROOT}"
  else
    fail "artisan not found; run this script from the xd-radius repository root."
  fi

  if [ -f "$ROOT/composer.lock" ]; then ok "composer.lock present"; else warn "composer.lock missing"; fi
  if [ -f "$ROOT/package-lock.json" ]; then ok "package-lock.json present"; else warn "package-lock.json missing"; fi
}

check_privileges() {
  local user
  user="$(ps -eo user=,comm= 2>/dev/null | awk '$2 ~ /php-fpm/ {print $1; exit}')"
  if [ -z "$user" ]; then
    user="www-data"
  fi
  if id "$user" >/dev/null 2>&1; then
    info "Likely web/PHP-FPM user: ${user}"
  else
    warn "Could not determine PHP-FPM user."
  fi

  if command_exists sudo; then
    if sudo -n -u "$user" true >/dev/null 2>&1; then
      ok "sudo can execute as ${user}"
    else
      info "No passwordless sudo policy detected for ${user}; this is not automatically treated as a failure."
    fi
  fi
}

run_checks() {
  PASS=0; WARN=0; FAIL=0; INSTALL_ITEMS=()

  say ""
  say "xd-radius Installation Requirements"
  say "=================================="
  say "Mode: CHECK / VALIDATE (no host changes)"
  say ""

  say "[OS / SYSTEM]"; detect_os; say ""
  say "[PHP]"; check_php; say ""
  say "[COMPOSER]"; check_composer; say ""
  say "[NODE / NPM]"; check_node; say ""
  say "[POSTGRESQL]"; check_postgres; say ""
  say "[FREERADIUS]"; check_freeradius; say ""
  say "[APPLICATION]"; check_app_files; say ""
  say "[PRIVILEGES]"; check_privileges; say ""

  say "Summary"
  say "-------"
  printf '  OK   : %d\n' "$PASS"
  printf '  WARN : %d\n' "$WARN"
  printf '  FAIL : %d\n' "$FAIL"

  if [ "$FAIL" -eq 0 ]; then
    say ""
    ok "Environment passed prerequisite validation."
    return 0
  fi

  say ""
  fail "Environment is NOT ready. Review failures before running xd-radius setup."
  return 1
}

install_missing() {
  if [ "${#INSTALL_ITEMS[@]}" -eq 0 ]; then
    say "No installable missing packages were detected."
    return 0
  fi

  if [ "$EUID" -ne 0 ]; then
    fail "install mode must be run as root (or through sudo)."
    return 1
  fi

  say ""
  say "Installable items detected"
  say "-------------------------"
  local item name packages
  for item in "${INSTALL_ITEMS[@]}"; do
    name="${item%%|*}"
    packages="${item#*|}"
    printf '  - %s: %s\n' "$name" "$packages"
  done
  say ""
  warn "The install mode can modify the operating system."
  if ! confirm "Proceed with installation of the listed apt packages?"; then
    say "Installation cancelled. No package changes were made."
    return 0
  fi

  if [ "$PKG_MANAGER" != "apt-get" ]; then
    fail "Automatic installation is currently supported only on Debian/Ubuntu."
    return 1
  fi

  local packages_to_install=()
  for item in "${INSTALL_ITEMS[@]}"; do
    packages="${item#*|}"
    case "$packages" in
      *" "*)
        read -r -a parts <<< "$packages"
        for p in "${parts[@]}"; do
          case "$p" in
            pdo|pdo_pgsql|mbstring|openssl|tokenizer|xml|ctype|json|bcmath|fileinfo|curl|zip|intl)
              packages_to_install+=("php-${p//pdo_pgsql/pgsql}") ;;
            *) packages_to_install+=("$p") ;;
          esac
        done
        ;;
      *) packages_to_install+=("$packages") ;;
    esac
  done

  local filtered=() p
  for p in "${packages_to_install[@]}"; do
    case "$p" in
      php-pdo|php-openssl|php-tokenizer|php-ctype|php-json|php-fileinfo) continue ;;
    esac
    filtered+=("$p")
  done
  mapfile -t filtered < <(printf '%s\n' "${filtered[@]}" | awk 'NF && !seen[$0]++')

  apt-get update || return 1
  apt-get install -y "${filtered[@]}"
}

# ---------------------------------------------------------------------------
# [2] Full setup orchestration (from setup-artisan.sh + install-freeradius-helper.sh)
# ---------------------------------------------------------------------------

install_privileged_helper() {
  local helper_src="$ROOT/scripts/xd-radius-freeradius-privileged"
  local helper_dst="/usr/local/sbin/xd-radius-freeradius"
  local sudoers_dst="/etc/sudoers.d/xd-radius-freeradius"
  local staging="/var/lib/xd-radius-freeradius/staging"

  [ "$(id -u)" -eq 0 ] || die "Full setup must run as root so the scoped FreeRADIUS helper can be installed."
  [ -f "$helper_src" ] || die "Privileged helper source not found: $helper_src"

  install -o root -g root -m 0755 "$helper_src" "$helper_dst"
  install -d -o www-data -g www-data -m 0750 "$staging"

  cat > "$sudoers_dst" <<EOF2
# xd-radius: tightly scoped FreeRADIUS management helper
www-data ALL=(root) NOPASSWD: $helper_dst
EOF2
  chmod 0440 "$sudoers_dst"
  visudo -cf "$sudoers_dst" || die "Generated sudoers file failed validation: $sudoers_dst"

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
  run_checks
  [ $? -eq 0 ] || die "Prerequisite validation failed."

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
  php "$ARTISAN" tinker --execute='try { $service = app(\App\Services\Radius\FreeRadiusSetupService::class); $result = $service->run(); echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL; exit(($result["ok"] ?? false) ? 0 : 1); } catch (\Throwable $e) { fwrite(STDERR, "FREERADIUS_SETUP_FAILED: ".$e->getMessage()."\n"); exit(1); }' \
    || die "FreeRADIUS setup service failed."

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

  php "$ARTISAN" tinker --execute='try { $checker = app(\App\Services\Radius\FreeRadiusHealthChecker::class); $result = $checker->check(); echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL; exit(($result["healthy"] ?? $result["ok"] ?? false) ? 0 : 1); } catch (\Throwable $e) { fwrite(STDERR, "HEALTH_CHECK_FAILED: ".$e->getMessage()."\n"); exit(1); }' \
    || die "Application FreeRADIUS health check failed."

  say ""; say "=================================="
  say "[OK] xd-radius setup completed successfully."
  say "=================================="
}

# ---------------------------------------------------------------------------
# Entry point
# ---------------------------------------------------------------------------

for arg in "$@"; do
  case "$arg" in
    check) MODE=check ;;
    install) MODE=install ;;
    setup) MODE=setup ;;
    --yes|-y) ASSUME_YES=true ;;
    --help|-h) usage; exit 0 ;;
    *) echo "Unknown argument: $arg"; usage; exit 2 ;;
  esac
done

case "$MODE" in
  check)
    run_checks
    exit $?
    ;;
  install)
    run_checks
    CHECK_STATUS=$?
    install_missing
    INSTALL_STATUS=$?
    [ "$INSTALL_STATUS" -ne 0 ] && exit "$INSTALL_STATUS"
    say ""; say "Re-validating after installation..."
    run_checks
    exit $?
    ;;
  setup)
    run_setup
    ;;
esac
