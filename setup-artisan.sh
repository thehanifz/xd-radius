#!/usr/bin/env bash
set -u
set -o pipefail
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$SCRIPT_DIR"
[ -f "$ROOT/artisan" ] || { [ -f "$ROOT/../artisan" ] && ROOT="$(cd "$ROOT/.." && pwd)"; }
ARTISAN="$ROOT/artisan"
REQUIREMENTS="$ROOT/installation-requirements.sh"
[ -f "$REQUIREMENTS" ] || REQUIREMENTS="$ROOT/scripts/installation-requirements.sh"
MODE="setup"; ASSUME_YES=false
say(){ printf '%s\n' "$*"; }; die(){ printf '\n[ERROR] %s\n' "$*" >&2; exit 1; }
run(){ printf '+'; printf ' %q' "$@"; printf '\n'; "$@"; }
for arg in "$@"; do case "$arg" in check) MODE=check;; setup) MODE=setup;; --yes|-y) ASSUME_YES=true;; --help|-h) cat <<'HELP'
xd-radius One-Command Setup

  ./setup-artisan.sh             Full setup (default)
  ./setup-artisan.sh setup       Full setup
  ./setup-artisan.sh --yes       Full setup without confirmation
  ./setup-artisan.sh check       Check only; no changes

Full setup: preflight -> migrate -> RADIUS DB check -> FreeRADIUS setup -> validate -> health check.
OS packages are NOT installed by this script.
HELP
exit 0;; *) die "Unknown argument: $arg. Use --help.";; esac; done
[ -f "$ARTISAN" ] || die "Laravel artisan not found. Expected: $ROOT/artisan"
[ -f "$ROOT/composer.json" ] || die "composer.json not found: $ROOT"
[ -d "$ROOT/vendor" ] || die "vendor/ not found. Run composer install first."
[ -f "$REQUIREMENTS" ] || die "installation-requirements.sh not found."
cd "$ROOT" || die "Cannot enter project root: $ROOT"
say ""; say "xd-radius Setup"; say "==============="; say "Project: $ROOT"; say "Mode:    $MODE"; say ""
say "[1/6] Server prerequisite preflight"
if [ -x "$REQUIREMENTS" ]; then "$REQUIREMENTS" check; else bash "$REQUIREMENTS" check; fi
[ $? -eq 0 ] || die "Prerequisite validation failed."
if [ "$MODE" = check ]; then
  say ""; say "[2/6] Laravel application check"; run php "$ARTISAN" about || die "php artisan about failed."
  say ""; say "[3/6] Migration status"; run php "$ARTISAN" migrate:status || die "migration status failed."
  say ""; say "[4/6] RADIUS DB connectivity"; php "$ARTISAN" tinker --execute='try { DB::connection("radius")->getPdo(); echo "RADIUS_DB_OK\n"; } catch (\Throwable $e) { fwrite(STDERR, "RADIUS_DB_FAILED: ".$e->getMessage()."\n"); exit(1); }' || die "RADIUS DB connection failed."
  say ""; say "[5/6] FreeRADIUS service"; systemctl is-active --quiet freeradius && say "  [OK] freeradius.service active" || say "  [WARN] freeradius.service not active"
  say ""; say "[6/6] FreeRADIUS configuration"; command -v freeradius >/dev/null 2>&1 && freeradius -XC || { command -v radiusd >/dev/null 2>&1 && radiusd -XC || die "FreeRADIUS binary not found."; }
  say ""; say "[OK] Check selesai. Tidak ada perubahan dilakukan."; exit 0
fi
say ""
say "[2/7] FreeRADIUS privileged helper"
[ "$(id -u)" -eq 0 ] || die "Full setup must run as root so the scoped FreeRADIUS helper can be installed."
run bash "$ROOT/scripts/install-freeradius-helper.sh" || die "Gagal memasang privileged FreeRADIUS helper."

say ""; say "[3/7] Laravel database migration"; run php "$ARTISAN" migrate --force || die "Laravel migration failed."
say ""; say "Verifying migration state..."; migration_output="$(php "$ARTISAN" migrate:status 2>&1)"; printf '%s\n' "$migration_output"; printf '%s\n' "$migration_output" | grep -Eq '\bPending\b' && die "There are still pending Laravel migrations after migrate --force."
say ""; say "[4/7] RADIUS database connectivity"; php "$ARTISAN" tinker --execute='try { DB::connection("radius")->getPdo(); echo "RADIUS_DB_OK\n"; } catch (\Throwable $e) { fwrite(STDERR, "RADIUS_DB_FAILED: ".$e->getMessage()."\n"); exit(1); }' || die "RADIUS database connection failed. Check RADIUS_DB_* / DB_* in .env."
say ""; say "[5/7] FreeRADIUS setup service"; php "$ARTISAN" tinker --execute='try { $service = app(\App\Services\Radius\FreeRadiusSetupService::class); $result = $service->run(); echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL; exit(($result["ok"] ?? false) ? 0 : 1); } catch (\Throwable $e) { fwrite(STDERR, "FREERADIUS_SETUP_FAILED: ".$e->getMessage()."\n"); exit(1); }' || die "FreeRADIUS setup service failed."
say ""; say "[6/7] FreeRADIUS configuration validation"; if command -v freeradius >/dev/null 2>&1; then run freeradius -XC || die "FreeRADIUS configuration validation failed."; elif command -v radiusd >/dev/null 2>&1; then run radiusd -XC || die "FreeRADIUS configuration validation failed."; else die "FreeRADIUS binary not found."; fi
say ""; say "[7/7] FreeRADIUS service / health verification"; if ! systemctl is-active --quiet freeradius; then systemctl status freeradius --no-pager || true; journalctl -u freeradius -n 50 --no-pager || true; die "FreeRADIUS service is not healthy."; fi
say "  [OK] freeradius.service active"
php "$ARTISAN" tinker --execute='try { $checker = app(\App\Services\Radius\FreeRadiusHealthChecker::class); $result = $checker->check(); echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL; exit(($result["healthy"] ?? $result["ok"] ?? false) ? 0 : 1); } catch (\Throwable $e) { fwrite(STDERR, "HEALTH_CHECK_FAILED: ".$e->getMessage()."\n"); exit(1); }' || die "Application FreeRADIUS health check failed."
say ""; say "=================================="; say "[OK] xd-radius setup completed successfully."; say "=================================="
