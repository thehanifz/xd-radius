#!/usr/bin/env bash
set -u

# xd-radius server prerequisite checker.
# Default: check/validate only. Never changes the host unless --install is used.

set +e

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

say() { printf '%s\n' "$*"; }
ok() { PASS=$((PASS+1)); printf '  [OK] %s\n' "$*"; }
warn() { WARN=$((WARN+1)); printf '  [WARN] %s\n' "$*"; }
fail() { FAIL=$((FAIL+1)); printf '  [FAIL] %s\n' "$*"; }
info() { printf '       %s\n' "$*"; }

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
  while true; do
    read -r -p "$prompt [y/N]: " answer
    case "${answer,,}" in
      y|yes) return 0 ;;
      n|no|"") return 1 ;;
      *) echo "Please answer yes or no." ;;
    esac
  done
}

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

postgres_server_native() {
  command_exists pg_isready && pg_isready -h 127.0.0.1 -p "${1:-5432}" >/dev/null 2>&1
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
  local script_dir root candidate
  script_dir="$(cd "$(dirname "$0")" && pwd)"

  # Support both repository layouts:
  #   1) <repo>/installation-requirements.sh
  #   2) <repo>/scripts/installation-requirements.sh
  if [ -f "$script_dir/artisan" ]; then
    root="$script_dir"
  elif [ -f "$script_dir/../artisan" ]; then
    root="$(cd "$script_dir/.." && pwd)"
  else
    root="$script_dir"
  fi

  if [ -f "$root/artisan" ]; then
    ok "xd-radius Laravel application detected: ${root}"
  else
    fail "artisan not found; run this script from the xd-radius repository."
  fi

  if [ -f "$root/composer.lock" ]; then ok "composer.lock present"; else warn "composer.lock missing"; fi
  if [ -f "$root/package-lock.json" ]; then ok "package-lock.json present"; else warn "package-lock.json missing"; fi
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
  say ""
  say "xd-radius Installation Requirements"
  say "=================================="
  say "Mode: CHECK / VALIDATE (no host changes)"
  say ""

  say "[OS / SYSTEM]"
  detect_os
  say ""
  say "[PHP]"
  check_php
  say ""
  say "[COMPOSER]"
  check_composer
  say ""
  say "[NODE / NPM]"
  check_node
  say ""
  say "[POSTGRESQL]"
  check_postgres
  say ""
  say "[FREERADIUS]"
  check_freeradius
  say ""
  say "[APPLICATION]"
  check_app_files
  say ""
  say "[PRIVILEGES]"
  check_privileges
  say ""

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
    fail "--install must be run as root (or through sudo)."
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
    # Only apt package names are installed automatically. PHP extension names are expanded below.
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

  # Replace non-package PHP pseudo extensions and de-duplicate.
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

usage() {
  cat <<USAGE
Usage: ${SCRIPT_NAME} [check|install] [--yes]

Default:
  check     Detect and validate server prerequisites without changing the host.

Install:
  install   Run checks, show missing installable packages, then ask for yes/no
            confirmation before modifying the OS.
  --yes     Skip the confirmation prompt (use only when explicitly intended).

Examples:
  ./${SCRIPT_NAME}
  ./${SCRIPT_NAME} check
  sudo ./${SCRIPT_NAME} install
USAGE
}

MODE="check"
AUTO_YES=false
for arg in "$@"; do
  case "$arg" in
    check|install) MODE="$arg" ;;
    --yes) AUTO_YES=true ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown argument: $arg"; usage; exit 2 ;;
  esac
done

if [ "$MODE" = "check" ]; then
  run_checks
  exit $?
fi

run_checks
CHECK_STATUS=$?

if [ "$AUTO_YES" = true ]; then
  if [ "$EUID" -ne 0 ]; then
    fail "--yes install requires root."
    exit 1
  fi
  # Re-run install without interactive confirmation.
  if [ "${#INSTALL_ITEMS[@]}" -eq 0 ]; then exit "$CHECK_STATUS"; fi
  PKG_MANAGER="$PKG_MANAGER" true
  # Temporarily override confirm for this invocation.
  confirm() { return 0; }
fi

install_missing
INSTALL_STATUS=$?

if [ "$INSTALL_STATUS" -ne 0 ]; then
  exit "$INSTALL_STATUS"
fi

# Re-validate after installation so the final exit status reflects the host state.
run_checks
exit $?
