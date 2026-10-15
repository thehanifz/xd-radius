#!/usr/bin/env bash
set -u
set -o pipefail

# xd-radius Unified Setup Script
#
# ONE entrypoint:
#   ./setup.sh                 -> full setup (default)
#   ./setup.sh setup           -> full setup
#   ./setup.sh check           -> prerequisite check only
#   ./setup.sh install         -> install project OS packages, then re-check
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

check_host_timezone() {
  say ""
  say "[TIMEZONE]"

  if ! command_exists timedatectl; then
    warn "timedatectl unavailable; setup mode will attempt to set the host timezone directly."
    return
  fi

  local timezone
  timezone="$(timedatectl show -p Timezone --value 2>/dev/null || true)"
  if [ "$timezone" = "Asia/Jakarta" ]; then
    ok "Host timezone is Asia/Jakarta (UTC+7)"
  else
    warn "Host timezone is ${timezone:-unknown}; setup mode will set Asia/Jakarta (UTC+7)."
  fi

  local key value
  for key in APP_TIMEZONE DB_TIMEZONE RADIUS_DB_TIMEZONE; do
    value="$(sed -n "s/^${key}=//p" "$ROOT/.env" 2>/dev/null | head -n1 | tr -d '\"' || true)"
    if [ "$value" = "Asia/Jakarta" ]; then
      ok "$key is Asia/Jakarta"
    else
      warn "$key is ${value:-unset}; setup mode will set Asia/Jakarta (UTC+7)."
    fi
  done
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
    local nodever nodemaj
    nodever="$(node -v 2>/dev/null | sed 's/^v//')"
    nodemaj="$(printf '%s' "$nodever" | cut -d. -f1)"
    if [ "${nodemaj:-0}" -ge 18 ] 2>/dev/null; then
      ok "Node.js $nodever (>= 18)"
    else
      fail "Node.js ${nodever:-unknown} (< 18 required)"
    fi
  else
    fail "Node.js not installed."
  fi

  if command_exists npm; then
    ok "npm $(npm -v 2>/dev/null)"
  else
    fail "npm not installed."
  fi
}

check_pm2() {
  say ""
  say "[PM2]"
  if command_exists pm2; then
    ok "PM2 $(pm2 --version 2>/dev/null | tail -n1)"
    if pm2 describe xd-radius-app >/dev/null 2>&1; then
      ok "PM2 xd-radius-app process registered"
    else
      warn "PM2 installed but xd-radius-app not running yet (run setup to start)."
    fi
  else
    warn "PM2 not installed. Install via: npm install -g pm2"
  fi
}

check_docker() {
  say ""
  say "[DOCKER]"
  if ! command_exists docker; then
    warn "Docker not installed."
    return
  fi
  local dver
  dver="$(docker --version 2>/dev/null | sed -E 's/Docker version ([^,]+).*/\1/')"
  ok "Docker $dver"
  if systemctl is-active --quiet docker 2>/dev/null; then
    ok "Docker daemon is active"
  else
    warn "Docker daemon is not active (run: systemctl start docker)"
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
  check_host_timezone
  check_php
  check_composer
  check_node
  check_pm2
  check_docker
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

set_timezone_env() {
  local env_file="$ROOT/.env"
  [ -f "$env_file" ] || die ".env not found. Copy .env.example to .env and configure database credentials first."

  local key
  for key in APP_TIMEZONE DB_TIMEZONE RADIUS_DB_TIMEZONE; do
    if grep -q "^${key}=" "$env_file"; then
      sed -i "s#^${key}=.*#${key}=Asia/Jakarta#" "$env_file"
    else
      printf '\n%s=Asia/Jakarta\n' "$key" >> "$env_file"
    fi
  done
}

ensure_jakarta_timezone() {
  require_root

  say ""
  say "[TIMEZONE] Configure Asia/Jakarta (UTC+7)"
  set_timezone_env
  chown root:www-data "$ROOT/.env"
  chmod 0640 "$ROOT/.env"

  if command_exists timedatectl; then
    if timedatectl set-timezone Asia/Jakarta; then
      local timezone
      timezone="$(timedatectl show -p Timezone --value 2>/dev/null || true)"
      [ "$timezone" = "Asia/Jakarta" ] || die "Host timezone verification failed: ${timezone:-unknown}."
    else
      warn "timedatectl could not update this container; applying the zoneinfo link directly."
      [ -e /usr/share/zoneinfo/Asia/Jakarta ] || die "Asia/Jakarta timezone data is missing."
      ln -sf /usr/share/zoneinfo/Asia/Jakarta /etc/localtime \
        || die "Unable to set /etc/localtime to Asia/Jakarta."
      printf 'Asia/Jakarta\n' > /etc/timezone \
        || die "Unable to write /etc/timezone."
    fi
  else
    [ -e /usr/share/zoneinfo/Asia/Jakarta ] || die "Asia/Jakarta timezone data is missing."
    ln -sf /usr/share/zoneinfo/Asia/Jakarta /etc/localtime \
      || die "Unable to set /etc/localtime to Asia/Jakarta."
    printf 'Asia/Jakarta\n' > /etc/timezone \
      || die "Unable to write /etc/timezone."
  fi

  [ "$(date +%z)" = "+0700" ] || die "Host timezone verification failed: expected UTC+7, got $(date +%z)."

  run php "$ARTISAN" optimize:clear || die "Unable to clear Laravel cached configuration after setting timezone."
  ok "Host, Laravel, and PostgreSQL connection timezone set to Asia/Jakarta"
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
  command_exists freeradius || packages+=(freeradius freeradius-postgresql)
  command_exists docker || packages+=(docker.io)

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

  run apt-get update || die "apt-get update failed."
  # shellcheck disable=SC2086
  run apt-get install -y $unique_packages
}


ensure_doku_keypair() {
  require_root

  command_exists openssl || die "OpenSSL tidak tersedia; tidak dapat membuat RSA key DOKU."

  local key_dir="/etc/xd-radius/doku"
  local private_key="$key_dir/private.key"
  local public_key="$key_dir/public.key"

  install -d -o root -g www-data -m 0750 "$key_dir"

  if [ -f "$private_key" ] && [ -f "$public_key" ]; then
    chmod 0640 "$private_key"
    chmod 0644 "$public_key"
    ok "DOKU RSA key pair already exists; keeping existing keys"
  elif [ ! -e "$private_key" ] && [ ! -e "$public_key" ]; then
    run openssl genrsa -out "$private_key" 2048
    run openssl rsa -in "$private_key" -pubout -out "$public_key"
    chmod 0640 "$private_key"
    chmod 0644 "$public_key"
    ok "DOKU RSA 2048 key pair generated"
  else
    die "DOKU RSA key pair tidak lengkap. private.key dan public.key harus ada berpasangan; tidak akan menimpa key existing."
  fi

  openssl rsa -in "$private_key" -check -noout >/dev/null 2>&1 \
    || die "DOKU private key gagal diverifikasi."

  local fingerprint
  fingerprint="$(openssl pkey -pubin -in "$public_key" -outform DER 2>/dev/null | sha256sum | awk '{print $1}')"
  [ -n "$fingerprint" ] || die "Tidak dapat menghitung fingerprint public key DOKU."
  ok "DOKU public key fingerprint: SHA256:$fingerprint"

  local env_file="$ROOT/.env"
  if [ -f "$env_file" ]; then
    if grep -q '^DOKU_PRIVATE_KEY_PATH=' "$env_file"; then
      sed -i 's#^DOKU_PRIVATE_KEY_PATH=.*#DOKU_PRIVATE_KEY_PATH=/etc/xd-radius/doku/private.key#' "$env_file"
    else
      printf '\nDOKU_PRIVATE_KEY_PATH=/etc/xd-radius/doku/private.key\n' >> "$env_file"
    fi
    chown root:www-data "$env_file"
    chmod 0640 "$env_file"
  fi
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
    chown root:www-data "$env_file"
    chmod 0640 "$env_file"
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
  cd "$ROOT" || die "Cannot enter project root: $ROOT"

  bootstrap_fresh_server
  ensure_jakarta_timezone
  install_privileged_helper
  ensure_doku_keypair

  say "[Laravel] Database migrations"
  local radius_schema=""
  for candidate in /etc/freeradius/3.0/mods-config/sql/main/postgresql/schema.sql /etc/freeradius/mods-config/sql/main/postgresql/schema.sql; do
    if [ -f "$candidate" ]; then radius_schema="$candidate"; break; fi
  done
  [ -n "$radius_schema" ] || die "FreeRADIUS PostgreSQL schema not found."
  local radius_db_password radius_db_host radius_db_port radius_db_user radius_db_name
  radius_db_password="$(sed -n 's/^RADIUS_DB_PASSWORD=//p' "$ROOT/.env" | head -n1)"
  radius_db_host="$(sed -n 's/^RADIUS_DB_HOST=//p' "$ROOT/.env" | head -n1)"
  radius_db_port="$(sed -n 's/^RADIUS_DB_PORT=//p' "$ROOT/.env" | head -n1)"
  radius_db_user="$(sed -n 's/^RADIUS_DB_USERNAME=//p' "$ROOT/.env" | head -n1)"
  radius_db_name="$(sed -n 's/^RADIUS_DB_DATABASE=//p' "$ROOT/.env" | head -n1)"
  if ! env PGPASSWORD="$radius_db_password" psql -h "$radius_db_host" -p "$radius_db_port" -U "$radius_db_user" -d "$radius_db_name" -tAc "SELECT to_regclass('public.radacct')" | grep -q radacct; then
    env PGPASSWORD="$radius_db_password" psql -h "$radius_db_host" -p "$radius_db_port" -U "$radius_db_user" -d "$radius_db_name" -v ON_ERROR_STOP=1 -f "$radius_schema" >/dev/null \
      || die "Unable to create FreeRADIUS tables before Laravel migrations."
  fi
  run php "$ARTISAN" migrate --force || die "Laravel migration failed."
  run php "$ARTISAN" app:configure-jakarta-timezone || die "Unable to configure PostgreSQL timezone."

  say "[FreeRADIUS] Configure SQL module and schema"
  run php "$ARTISAN" freeradius:setup || die "FreeRADIUS setup service failed."
  run systemctl restart freeradius || die "Unable to restart FreeRADIUS."
  run freeradius -XC || die "FreeRADIUS configuration validation failed."
  systemctl is-active --quiet freeradius || die "FreeRADIUS service is not active."
  run php "$ARTISAN" freeradius:health-check || die "FreeRADIUS health check failed."

  say "[Superadmin] Create the first login"
  run php "$ARTISAN" app:create-superuser || die "Superadmin account creation failed."
  install_application_services

  local server_ip
  server_ip="$(hostname -I 2>/dev/null | awk '{print $1}')"
  say ""
  say "[OK] xd-radius is ready at http://${server_ip:-SERVER_IP}:8000"
  say "RADIUS: UDP 1812/1813"
  say "Superadmin password is stored as a hash in PostgreSQL, never as plaintext in .env."
}

PG_INSTANCE_KIND=()
PG_INSTANCE_LABEL=()
PG_INSTANCE_PORT=()
PG_INSTANCE_HOST=()
PG_INSTANCE_STATE=()
PG_INSTANCE_CONTAINER=()

detect_postgres_instances() {
  PG_INSTANCE_KIND=(); PG_INSTANCE_LABEL=(); PG_INSTANCE_PORT=(); PG_INSTANCE_HOST=(); PG_INSTANCE_STATE=(); PG_INSTANCE_CONTAINER=()
  local seen_ports=" " version cluster port status owner datadir logfile name image mapped_port mapped_address probe_port container_ip network_mode

  if command_exists pg_lsclusters; then
    while read -r version cluster port status owner datadir logfile; do
      [ -n "${version:-}" ] || continue
      PG_INSTANCE_KIND+=(native)
      PG_INSTANCE_LABEL+=("PostgreSQL cluster ${version}/${cluster} (${status})")
      PG_INSTANCE_PORT+=("$port")
      PG_INSTANCE_HOST+=(127.0.0.1)
      PG_INSTANCE_STATE+=("$status")
      PG_INSTANCE_CONTAINER+=("")
      seen_ports+="${port} "
    done < <(pg_lsclusters --no-header 2>/dev/null || true)
  fi

  if command_exists docker && docker info >/dev/null 2>&1; then
    while IFS='|' read -r name image; do
      [[ "${image,,}" == *postgres* ]] || continue
      mapped_address="$(docker port "$name" 5432/tcp 2>/dev/null | head -n1 || true)"
      mapped_port="$(printf '%s' "$mapped_address" | awk -F: '{print $NF}')"
      mapped_address="${mapped_address%:*}"
      case "$mapped_address" in 0.0.0.0|::|'[::]'|127.*) mapped_address=127.0.0.1 ;; esac
      if [ -z "$mapped_port" ]; then
        mapped_port=5432
        network_mode="$(docker inspect -f '{{.HostConfig.NetworkMode}}' "$name" 2>/dev/null || true)"
        if [ "$network_mode" = host ]; then
          mapped_address=127.0.0.1
        else
          container_ip="$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "$name" 2>/dev/null || true)"
        fi
        if [ -n "$container_ip" ]; then
          mapped_address="$container_ip"
        fi
      fi
      PG_INSTANCE_KIND+=(docker)
      PG_INSTANCE_LABEL+=("Docker ${name} (${image}${mapped_port:+, host port ${mapped_port}}${mapped_port:-, port not published})")
      PG_INSTANCE_PORT+=("$mapped_port")
      PG_INSTANCE_HOST+=("${mapped_address:-127.0.0.1}")
      PG_INSTANCE_STATE+=("$(docker inspect -f '{{.State.Status}}' "$name" 2>/dev/null || printf unknown)")
      PG_INSTANCE_CONTAINER+=("$name")
      [ -n "$mapped_port" ] && seen_ports+="${mapped_port} "
    done < <(docker ps -a --format '{{.Names}}|{{.Image}}' 2>/dev/null || true)
  fi

  if command_exists pg_isready; then
    for probe_port in {5432..5499}; do
      [[ "$seen_ports" == *" ${probe_port} "* ]] && continue
      if pg_isready -h 127.0.0.1 -p "$probe_port" -q >/dev/null 2>&1; then
        PG_INSTANCE_KIND+=(tcp)
        PG_INSTANCE_LABEL+=("PostgreSQL terdeteksi di 127.0.0.1:${probe_port} (TCP)")
        PG_INSTANCE_PORT+=("$probe_port")
        PG_INSTANCE_HOST+=(127.0.0.1)
        PG_INSTANCE_STATE+=(online)
        PG_INSTANCE_CONTAINER+=("")
      fi
    done
  fi
}

start_native_postgres_port() {
  local requested_port="$1" version cluster port status owner datadir logfile
  command_exists pg_lsclusters || die "Tidak menemukan utilitas pg_lsclusters untuk cluster native."
  while read -r version cluster port status owner datadir logfile; do
    [ "$port" = "$requested_port" ] || continue
    if [ "$status" != online ]; then
      run pg_ctlcluster "$version" "$cluster" start || die "Tidak bisa menyalakan cluster PostgreSQL ${version}/${cluster}."
    fi
    return 0
  done < <(pg_lsclusters --no-header 2>/dev/null || true)
  die "Cluster PostgreSQL native pada port ${requested_port} tidak ditemukan lagi."
}

choose_postgres_instance() {
  DB_BACKEND="docker-new"; DB_PORT=""; DB_CONTAINER=""; DB_HOST="127.0.0.1"
  if command_exists docker && command_exists systemctl; then
    systemctl enable --now docker >/dev/null 2>&1 || true
  fi
  detect_postgres_instances
  local count="${#PG_INSTANCE_KIND[@]}" choice index

  if [ "$count" -eq 0 ]; then
    say "Tidak ada PostgreSQL lokal yang terdeteksi. Menyiapkan PostgreSQL baru dalam Docker."
    return
  fi

  say ""
  say "PostgreSQL yang terdeteksi:"
  for ((index=0; index<count; index++)); do
    printf '  %d) %s\n' "$((index + 1))" "${PG_INSTANCE_LABEL[$index]}"
  done
  printf '  N) Buat PostgreSQL baru dalam Docker (terisolasi)\n'

  if [ "$ASSUME_YES" = true ]; then
    choice="N"
  else
    read -r -p "Pilih nomor instance yang dipakai, atau N untuk membuat baru [N]: " choice
    choice="${choice:-N}"
  fi

  case "${choice,,}" in
    n|new|baru)
      DB_BACKEND="docker-new"
      ;;
    *)
      [[ "$choice" =~ ^[0-9]+$ ]] || die "Pilihan PostgreSQL tidak valid."
      index=$((choice - 1))
      [ "$index" -ge 0 ] && [ "$index" -lt "$count" ] || die "Nomor instance PostgreSQL tidak tersedia."
      DB_BACKEND="${PG_INSTANCE_KIND[$index]}"
      DB_HOST="${PG_INSTANCE_HOST[$index]}"
      DB_PORT="${PG_INSTANCE_PORT[$index]}"
      DB_CONTAINER="${PG_INSTANCE_CONTAINER[$index]}"
      if [ "${PG_INSTANCE_STATE[$index]}" != online ] && [ "${PG_INSTANCE_STATE[$index]}" != running ]; then
        if [ "$DB_BACKEND" = native ]; then
          start_native_postgres_port "$DB_PORT"
        elif [ "$DB_BACKEND" = docker ]; then
          run docker start "$DB_CONTAINER" || die "Tidak bisa menyalakan container PostgreSQL yang dipilih."
        else
          die "Instance PostgreSQL yang dipilih tidak aktif."
        fi
      fi
      if [ "$DB_BACKEND" = docker ] && [ -z "$DB_HOST" ]; then
        DB_HOST="$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' "$DB_CONTAINER" 2>/dev/null || true)"
        [ -n "$DB_HOST" ] || die "Container ${DB_CONTAINER} tidak mempublikasikan port PostgreSQL dan tidak memiliki IP jaringan Docker yang dapat dijangkau host."
      fi
      ;;
  esac

  if [ "$DB_BACKEND" = docker-new ]; then
    install_new_postgres_container
  fi
}

wait_for_postgres_instance() {
  local attempt
  for attempt in {1..60}; do
    pg_isready -h "$DB_HOST" -p "$DB_PORT" -q >/dev/null 2>&1 && return 0
    sleep 2
  done
  die "PostgreSQL yang dipilih tidak menerima koneksi di ${DB_HOST}:${DB_PORT}. Periksa port/listen_addresses dan firewall lokal."
}

install_new_postgres_container() {
  require_root
  if ! command_exists docker; then
    run env DEBIAN_FRONTEND=noninteractive apt-get install -y docker.io || die "Instalasi Docker gagal."
  fi
  run systemctl enable --now docker || die "Docker daemon tidak dapat dijalankan."

  local candidate name_in_use root_password db_volume volume_exists
  DB_PORT=""
  for candidate in {5432..5499}; do
    if ! python3 -c 'import socket,sys; s=socket.socket(); s.bind(("127.0.0.1", int(sys.argv[1]))); s.close()' "$candidate" >/dev/null 2>&1; then
      continue
    fi
    DB_PORT="$candidate"
    break
  done
  [ -n "$DB_PORT" ] || die "Tidak ada port PostgreSQL kosong pada rentang 5432-5499."

  DB_CONTAINER="xd-radius-postgres"
  name_in_use="$(docker ps -a --filter "name=^/${DB_CONTAINER}$" --format '{{.Names}}' | head -n1)"
  if [ -n "$name_in_use" ]; then DB_CONTAINER="xd-radius-postgres-$(date +%s)"; fi
  DB_VOLUME="xd-radius-postgres-data"
  volume_exists="$(docker volume ls -q --filter "name=^${DB_VOLUME}$" | head -n1)"
  if [ -n "$volume_exists" ]; then DB_VOLUME="xd-radius-postgres-data-$(date +%s)"; fi
  root_password="$(openssl rand -hex 32)"
  say "Membuat container PostgreSQL 16 pada 127.0.0.1:${DB_PORT} dengan volume persisten."
  docker volume create "$DB_VOLUME" >/dev/null || die "Tidak bisa membuat volume PostgreSQL."
  docker run -d --name "$DB_CONTAINER" --restart unless-stopped \
    -e POSTGRES_PASSWORD="$root_password" \
    -p "127.0.0.1:${DB_PORT}:5432" \
    -v "${DB_VOLUME}:/var/lib/postgresql/data" \
    postgres:16 >/dev/null || die "Container PostgreSQL tidak dapat dibuat."
  local attempt
  for attempt in {1..60}; do
    if docker exec "$DB_CONTAINER" pg_isready -U postgres >/dev/null 2>&1; then break; fi
    sleep 2
  done
  docker exec "$DB_CONTAINER" pg_isready -U postgres >/dev/null 2>&1 || die "PostgreSQL Docker tidak menjadi siap."
  DB_BACKEND="docker-new"
  DB_HOST="127.0.0.1"
}

pg_admin_psql() {
  local database="$1"; shift
  case "$DB_BACKEND" in
    native)
      sudo -u postgres psql -h /var/run/postgresql -p "$DB_PORT" -d "$database" -v ON_ERROR_STOP=1 "$@"
      ;;
    docker|docker-new)
      docker exec -e "PGPASSWORD=${PG_ADMIN_PASSWORD:-}" "$DB_CONTAINER" psql -U "${PG_ADMIN_USER:-postgres}" -d "$database" -v ON_ERROR_STOP=1 "$@"
      ;;
    tcp)
      PGPASSWORD="$PG_ADMIN_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$PG_ADMIN_USER" -d "$database" -v ON_ERROR_STOP=1 "$@"
      ;;
  esac
}

provision_postgres_database() {
  local db_name="$1" db_user="$2" db_password="$3" recovery="${4:-false}" exists
  if [ "$DB_BACKEND" = docker ] && [ -z "${PG_ADMIN_USER:-}" ]; then
    local container_env
    container_env="$(docker inspect -f '{{range .Config.Env}}{{println .}}{{end}}' "$DB_CONTAINER" 2>/dev/null || true)"
    PG_ADMIN_USER="$(printf '%s\n' "$container_env" | sed -n -e 's/^POSTGRES_USER=//p' -e 's/^POSTGRESQL_USERNAME=//p' | head -n1)"
    PG_ADMIN_PASSWORD="$(printf '%s\n' "$container_env" | sed -n -e 's/^POSTGRES_PASSWORD=//p' -e 's/^POSTGRESQL_PASSWORD=//p' | head -n1)"
    [ -n "$PG_ADMIN_USER" ] || PG_ADMIN_USER=postgres
    if [ -z "$PG_ADMIN_PASSWORD" ]; then
      read -r -s -p "Password administrator PostgreSQL: " PG_ADMIN_PASSWORD; printf '\n'
    fi
  fi
  if [ "$DB_BACKEND" = tcp ] && [ -z "${PG_ADMIN_USER:-}" ]; then
    read -r -p "User administrator PostgreSQL: " PG_ADMIN_USER
    read -r -s -p "Password administrator PostgreSQL: " PG_ADMIN_PASSWORD; printf '\n'
  fi
  if ! exists="$(pg_admin_psql postgres -tAc "SELECT 1 FROM pg_roles WHERE rolname='${db_user}'" 2>/dev/null | tr -d '[:space:]')"; then
    if [ "$DB_BACKEND" = docker ]; then
      read -r -p "User administrator PostgreSQL dalam container [${PG_ADMIN_USER:-postgres}]: " input_admin_user
      PG_ADMIN_USER="${input_admin_user:-${PG_ADMIN_USER:-postgres}}"
      read -r -s -p "Password administrator PostgreSQL: " PG_ADMIN_PASSWORD; printf '\n'
      exists="$(pg_admin_psql postgres -tAc "SELECT 1 FROM pg_roles WHERE rolname='${db_user}'" 2>/dev/null | tr -d '[:space:]')" \
        || die "Akses admin ke container PostgreSQL gagal."
    else
      die "Tidak bisa mengakses instance PostgreSQL terpilih dengan akun admin."
    fi
  fi
  if [ "$recovery" != creating ] && [ -n "$exists" ]; then
    die "Role '${db_user}' sudah ada di instance terpilih; setup tidak akan mengganti password atau mengambil alih role tersebut."
  fi
  exists="$(pg_admin_psql postgres -tAc "SELECT 1 FROM pg_database WHERE datname='${db_name}'" 2>/dev/null | tr -d '[:space:]')" \
    || die "Tidak bisa memeriksa database pada instance PostgreSQL terpilih."
  if [ "$recovery" != creating ] && [ -n "$exists" ]; then
    die "Database '${db_name}' sudah ada di instance terpilih; setup tidak akan mengadopsi atau mengubahnya."
  fi
  if [ -z "$(pg_admin_psql postgres -tAc "SELECT 1 FROM pg_roles WHERE rolname='${db_user}'" 2>/dev/null | tr -d '[:space:]')" ]; then
    pg_admin_psql postgres -c "CREATE ROLE ${db_user} LOGIN CREATEDB PASSWORD '${db_password}'" >/dev/null \
      || die "Tidak bisa membuat role aplikasi; pastikan akun admin memiliki hak CREATEROLE."
  elif [ "$recovery" = creating ]; then
    pg_admin_psql postgres -c "ALTER ROLE ${db_user} LOGIN CREATEDB PASSWORD '${db_password}'" >/dev/null \
      || die "Tidak bisa memulihkan role aplikasi setup yang belum selesai."
  fi
  if [ -z "$(pg_admin_psql postgres -tAc "SELECT 1 FROM pg_database WHERE datname='${db_name}'" 2>/dev/null | tr -d '[:space:]')" ]; then
    pg_admin_psql postgres -c "CREATE DATABASE ${db_name} OWNER ${db_user}" >/dev/null \
      || die "Tidak bisa membuat database aplikasi."
  elif [ "$recovery" = creating ]; then
    pg_admin_psql postgres -c "ALTER DATABASE ${db_name} OWNER TO ${db_user}" >/dev/null \
      || die "Tidak bisa memulihkan database setup yang belum selesai."
  fi
}

bootstrap_fresh_server() {
  . /etc/os-release 2>/dev/null || true
  case "${ID:-} ${ID_LIKE:-}" in *debian*|*ubuntu*) ;; *) die "One-command bootstrap supports Debian/Ubuntu and their Armbian derivatives." ;; esac
  command_exists systemctl || die "systemd is required."
  command_exists apt-get || die "apt-get is required."

  say "[1/5] Install OS packages"
  run apt-get update || die "apt-get update failed."
  if [[ "${ID:-}" = ubuntu && "${VERSION_ID:-0}" < 24.04 ]] || \
     [[ "${ID:-}" = armbian && " ${ID_LIKE:-} " == *" ubuntu "* && " ${VERSION_CODENAME:-} " == *" jammy "* ]]; then
    run env DEBIAN_FRONTEND=noninteractive apt-get install -y software-properties-common || die "Unable to install Ubuntu repository tools."
    run env LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php || die "Unable to add the PHP package repository."
    run apt-get update || die "apt-get update after adding PHP repository failed."
  fi
  local nodesource_setup="/tmp/xd-radius-nodesource-setup.sh"
  run curl -fsSL https://deb.nodesource.com/setup_20.x -o "$nodesource_setup" || die "Unable to download Node.js package setup."
  run bash "$nodesource_setup" || die "Unable to configure Node.js package repository."
  rm -f "$nodesource_setup"
  run apt-get update || die "apt-get update after adding Node.js repository failed."
  run env DEBIAN_FRONTEND=noninteractive apt-get install -y ca-certificates curl git unzip sudo openssl python3 \
    php-cli php-fpm php-pgsql php-mbstring php-xml php-bcmath php-curl php-zip php-intl php-gd gnupg \
    composer nodejs npm postgresql-client postgresql-common freeradius freeradius-postgresql \
    || die "Installing system packages failed."
  php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1)' || die "PHP 8.2+ is required; install a supported OS repository."
  node -e 'process.exit(Number(process.versions.node.split(".")[0]) >= 18 ? 0 : 1)' || die "Node.js 18+ is required."
  run systemctl enable --now freeradius || die "Unable to start FreeRADIUS."

  local app_ip db_password db_name db_user app_url existing_env managed_bootstrap db_host db_port pg_mode pg_container
  app_ip="$(hostname -I 2>/dev/null | awk '{print $1}')"
  [ -n "$app_ip" ] || app_ip="127.0.0.1"
  existing_env=false
  if [ -f "$ROOT/.env" ]; then existing_env=true; else
    [ -f "$ROOT/.env.example" ] || die ".env.example is missing."
    cp "$ROOT/.env.example" "$ROOT/.env"
  fi
  managed_bootstrap="$(sed -n 's/^XD_RADIUS_BOOTSTRAPPED=//p' "$ROOT/.env" | head -n1)"
  if [ "$managed_bootstrap" = true ] || [ "$managed_bootstrap" = creating ]; then
    db_name="$(sed -n 's/^DB_DATABASE=//p' "$ROOT/.env" | head -n1)"
    db_user="$(sed -n 's/^DB_USERNAME=//p' "$ROOT/.env" | head -n1)"
    db_password="$(sed -n 's/^DB_PASSWORD=//p' "$ROOT/.env" | head -n1)"
    app_url="$(sed -n 's/^APP_URL=//p' "$ROOT/.env" | head -n1)"
    db_host="$(sed -n 's/^DB_HOST=//p' "$ROOT/.env" | head -n1)"
    db_port="$(sed -n 's/^DB_PORT=//p' "$ROOT/.env" | head -n1)"
    pg_mode="$(sed -n 's/^XD_RADIUS_PG_MODE=//p' "$ROOT/.env" | head -n1)"
    pg_container="$(sed -n 's/^XD_RADIUS_PG_CONTAINER=//p' "$ROOT/.env" | head -n1)"
    [[ "$db_name" =~ ^[a-zA-Z_][a-zA-Z0-9_]*$ && "$db_user" =~ ^[a-zA-Z_][a-zA-Z0-9_]*$ && "$db_password" =~ ^[a-zA-Z0-9]+$ && "$db_port" =~ ^[0-9]+$ ]] \
      || die "Generated database credentials in .env are invalid; refusing to modify the database."
    [ -n "$app_url" ] || app_url="http://${app_ip}:8000"
    [ -n "$db_host" ] && [ -n "$db_port" ] && [ -n "$pg_mode" ] || die "PostgreSQL backend settings in .env are incomplete."
    DB_BACKEND="$pg_mode"; DB_HOST="$db_host"; DB_PORT="$db_port"; DB_CONTAINER="$pg_container"
    case "$DB_BACKEND" in
      docker|docker-new)
        if ! command_exists docker; then
          run env DEBIAN_FRONTEND=noninteractive apt-get install -y docker.io || die "Instalasi Docker gagal."
        fi
        run systemctl enable --now docker || die "Docker daemon tidak dapat dijalankan."
        [ -n "$DB_CONTAINER" ] || die "Nama container PostgreSQL tidak tercatat di .env."
        [ "$(docker inspect -f '{{.State.Status}}' "$DB_CONTAINER" 2>/dev/null || true)" = running ] || run docker start "$DB_CONTAINER" || die "Container PostgreSQL bootstrap tidak dapat dijalankan."
        local pg_attempt
        for pg_attempt in {1..60}; do
          docker exec "$DB_CONTAINER" pg_isready -U postgres >/dev/null 2>&1 && break
          sleep 2
        done
        docker exec "$DB_CONTAINER" pg_isready -U postgres >/dev/null 2>&1 || die "PostgreSQL container belum siap menerima koneksi."
        ;;
      native)
        start_native_postgres_port "$DB_PORT"
        ;;
      tcp)
        PG_ADMIN_USER=""
        ;;
      *) die "Mode PostgreSQL tersimpan tidak dikenal: $DB_BACKEND" ;;
    esac
    wait_for_postgres_instance
  else
    managed_bootstrap=false
    if [ "$existing_env" = true ] && grep -q '^APP_KEY=base64:' "$ROOT/.env"; then
      die "A configured .env already exists. Refusing to replace its database credentials; use bootstrap only on a fresh clone/server."
    fi
    if [ "$existing_env" = true ] && { ! grep -q '^DB_CONNECTION=sqlite$' "$ROOT/.env" || ! grep -q '^DB_DATABASE=laravel$' "$ROOT/.env" || ! grep -q '^DB_USERNAME=root$' "$ROOT/.env"; }; then
      die "Existing .env has custom database settings. Refusing to replace them during fresh bootstrap."
    fi
    app_url="http://${app_ip}:8000"
    choose_postgres_instance
    db_name="xd_radius"; db_user="xd_radius"; db_password="$(openssl rand -hex 24)"
    db_host="${DB_HOST:-127.0.0.1}"; db_port="$DB_PORT"; pg_mode="$DB_BACKEND"; pg_container="$DB_CONTAINER"
    wait_for_postgres_instance
  fi
  if [ "$DB_BACKEND" = tcp ] && [ -z "${PG_ADMIN_USER:-}" ]; then
    read -r -p "User administrator PostgreSQL: " PG_ADMIN_USER
    read -r -s -p "Password administrator PostgreSQL: " PG_ADMIN_PASSWORD; printf '\n'
  fi
  export SETUP_APP_URL="$app_url" SETUP_DB_NAME="$db_name" SETUP_DB_USER="$db_user" SETUP_DB_PASSWORD="$db_password" SETUP_DB_HOST="$db_host" SETUP_DB_PORT="$db_port" SETUP_PG_MODE="$pg_mode" SETUP_PG_CONTAINER="$pg_container" SETUP_BOOTSTRAP_STATE=creating
  python3 - "$ROOT/.env" <<'PY' || die "Unable to create/update the Laravel environment file."
import os, re, sys
path = sys.argv[1]
updates = {
 'APP_NAME':'RadiusManager','APP_ENV':'production','APP_DEBUG':'false','APP_URL':os.environ['SETUP_APP_URL'],'APP_TIMEZONE':'Asia/Jakarta',
 'DB_CONNECTION':'pgsql','DB_HOST':os.environ['SETUP_DB_HOST'],'DB_PORT':os.environ['SETUP_DB_PORT'],'DB_DATABASE':os.environ['SETUP_DB_NAME'],'DB_USERNAME':os.environ['SETUP_DB_USER'],'DB_PASSWORD':os.environ['SETUP_DB_PASSWORD'],'DB_TIMEZONE':'Asia/Jakarta',
 'SESSION_DRIVER':'database','QUEUE_CONNECTION':'database','CACHE_STORE':'database',
 'RADIUS_DB_CONNECTION':'pgsql','RADIUS_DB_HOST':os.environ['SETUP_DB_HOST'],'RADIUS_DB_PORT':os.environ['SETUP_DB_PORT'],'RADIUS_DB_DATABASE':os.environ['SETUP_DB_NAME'],'RADIUS_DB_USERNAME':os.environ['SETUP_DB_USER'],'RADIUS_DB_PASSWORD':os.environ['SETUP_DB_PASSWORD'],'RADIUS_DB_SSLMODE':'disable','RADIUS_DB_TIMEZONE':'Asia/Jakarta','FREERADIUS_USE_SUDO':'true','XD_RADIUS_BOOTSTRAPPED':os.environ['SETUP_BOOTSTRAP_STATE'],'XD_RADIUS_PG_MODE':os.environ['SETUP_PG_MODE'],'XD_RADIUS_PG_CONTAINER':os.environ['SETUP_PG_CONTAINER']}
with open(path, encoding='utf-8') as f: lines=f.read().splitlines()
seen=set(); out=[]
for line in lines:
 m=re.match(r'^([A-Za-z_][A-Za-z0-9_]*)=',line); key=m.group(1) if m else None
 if key in updates:
  if key not in seen: out.append(f'{key}={updates[key]}'); seen.add(key)
 else: out.append(line)
for key,value in updates.items():
 if key not in seen: out.append(f'{key}={value}')
with open(path,'w',encoding='utf-8') as f: f.write('\n'.join(out)+'\n')
os.chmod(path,0o640)
PY
  unset SETUP_APP_URL SETUP_DB_NAME SETUP_DB_USER SETUP_DB_PASSWORD SETUP_DB_HOST SETUP_DB_PORT SETUP_PG_MODE SETUP_PG_CONTAINER SETUP_BOOTSTRAP_STATE || true
  chown root:www-data "$ROOT/.env"
  chmod 0640 "$ROOT/.env"
  DB_BACKEND="$pg_mode"; DB_HOST="$db_host"; DB_PORT="$db_port"; DB_CONTAINER="$pg_container"
  if [ "$managed_bootstrap" != true ]; then
    say "Provision application database and role on selected PostgreSQL instance"
    provision_postgres_database "$db_name" "$db_user" "$db_password" "$managed_bootstrap"
    env PGPASSWORD="$db_password" psql -h "$db_host" -p "$db_port" -U "$db_user" -d "$db_name" -tAc 'SELECT 1' >/dev/null \
      || die "Tidak bisa tersambung ke database baru melalui host/port yang dipilih."
    sed -i 's/^XD_RADIUS_BOOTSTRAPPED=creating$/XD_RADIUS_BOOTSTRAPPED=true/' "$ROOT/.env"
    chown root:www-data "$ROOT/.env"; chmod 0640 "$ROOT/.env"
  fi

  say "[3/5] Install PHP and Node dependencies; build frontend"
  run env COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction || die "Composer dependency installation failed."
  if ! grep -q '^APP_KEY=base64:' "$ROOT/.env"; then run php "$ARTISAN" key:generate --force || die "Unable to generate APP_KEY."; fi
  run npm ci --no-audit --no-fund || die "npm dependency installation failed."
  run npm run build || die "Frontend asset build failed."
  run php "$ARTISAN" storage:link --force || die "Unable to create public storage symlink."
  install -d -o www-data -g www-data -m 0775 "$ROOT/storage" "$ROOT/bootstrap/cache"
  run chown -R www-data:www-data "$ROOT/storage" "$ROOT/bootstrap/cache"
  run php "$ARTISAN" optimize:clear || die "Unable to clear Laravel caches."
  run php "$ARTISAN" config:cache || die "Unable to cache Laravel configuration."
  ok "Packages, .env, PostgreSQL, Composer, and frontend build are ready"
}

install_application_services() {
  run chown -R www-data:www-data "$ROOT/storage" "$ROOT/bootstrap/cache" || die "Unable to set Laravel runtime directory permissions."
  local unit
  for unit in xd-radius-app xd-radius-queue xd-radius-scheduler; do
    if [ -f "/etc/systemd/system/${unit}.service" ]; then
      systemctl disable --now "${unit}.service" >/dev/null 2>&1 || true
      rm -f "/etc/systemd/system/${unit}.service"
    fi
  done
  run systemctl daemon-reload
  command_exists pm2 || run npm install --global pm2 || die "Unable to install PM2."
  run pm2 startOrReload "$ROOT/ecosystem.config.cjs" --update-env || die "Unable to start the application, queue, and scheduler with PM2."
  run pm2 save || die "Unable to save the PM2 process list."
  run pm2 startup systemd -u root --hp /root || die "Unable to configure PM2 startup at boot."
  run systemctl enable --now pm2-root.service || die "PM2 startup service did not start."
  pm2 describe xd-radius-app >/dev/null 2>&1 || die "PM2 application process did not start."
  run curl --fail --silent --show-error --retry 10 --retry-connrefused --retry-delay 2 http://127.0.0.1:8000/up >/dev/null || die "Laravel health endpoint did not become ready."
  ok "PM2 application, queue, and scheduler processes enabled at boot"
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
  doku-key)
    ensure_doku_keypair
    ;;
  -h|--help)
    cat <<'EOF'
xd-radius Unified Setup

Usage:
  ./setup.sh              Full setup (default)
  ./setup.sh setup        Full setup
  ./setup.sh check        Check prerequisites only
  ./setup.sh install      Install missing OS packages, then validate
  ./setup.sh doku-key     Generate/verify DOKU RSA key pair without overwriting existing keys
  ./setup.sh setup --yes  Full setup without confirmations

The script is self-contained. It does not call installation-requirements.sh
or another setup script.
EOF
    ;;
  *)
    die "Unknown mode: ${1:-}. Use setup, check, or install."
    ;;
esac
