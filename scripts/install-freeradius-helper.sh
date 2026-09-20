#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
HELPER_SRC="$ROOT/scripts/xd-radius-freeradius-privileged"
HELPER_DST="/usr/local/sbin/xd-radius-freeradius"
SUDOERS_DST="/etc/sudoers.d/xd-radius-freeradius"
STAGING="/var/lib/xd-radius-freeradius/staging"

[ "$(id -u)" -eq 0 ] || { echo "Run as root." >&2; exit 1; }
[ -f "$HELPER_SRC" ] || { echo "Helper not found: $HELPER_SRC" >&2; exit 1; }

install -o root -g root -m 0755 "$HELPER_SRC" "$HELPER_DST"
install -d -o www-data -g www-data -m 0750 "$STAGING"

cat > "$SUDOERS_DST" <<EOF2
# xd-radius: tightly scoped FreeRADIUS management helper
www-data ALL=(root) NOPASSWD: $HELPER_DST
EOF2
chmod 0440 "$SUDOERS_DST"

visudo -cf "$SUDOERS_DST"

# Enable the narrowly-scoped privileged path for the Laravel worker/UI.
ENV_FILE="$ROOT/.env"
if [ -f "$ENV_FILE" ]; then
    if grep -q '^FREERADIUS_USE_SUDO=' "$ENV_FILE"; then
        sed -i 's/^FREERADIUS_USE_SUDO=.*/FREERADIUS_USE_SUDO=true/' "$ENV_FILE"
    else
        printf '\nFREERADIUS_USE_SUDO=true\n' >> "$ENV_FILE"
    fi
    if grep -q '^FREERADIUS_PRIVILEGED_HELPER=' "$ENV_FILE"; then
        sed -i 's#^FREERADIUS_PRIVILEGED_HELPER=.*#FREERADIUS_PRIVILEGED_HELPER=/usr/local/sbin/xd-radius-freeradius#' "$ENV_FILE"
    else
        printf 'FREERADIUS_PRIVILEGED_HELPER=/usr/local/sbin/xd-radius-freeradius\n' >> "$ENV_FILE"
    fi
fi

# Verify the exact non-interactive privilege path.
sudo -u www-data sudo -n "$HELPER_DST" is-active freeradius >/dev/null

echo "[OK] Installed $HELPER_DST"
echo "[OK] Installed $SUDOERS_DST"
echo "[OK] Staging: $STAGING"
