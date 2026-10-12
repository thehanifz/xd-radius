#!/usr/bin/env bash
set -euo pipefail

URL="${1:-}"
MODE="${2:-probe}"

if [[ -z "$URL" ]]; then
  echo "Usage: $0 https://domain.example/payments/notifications [probe|signed]"
  exit 2
fi

BODY='{"test":true,"source":"manual-webhook-test"}'

if [[ "$MODE" == "probe" ]]; then
  echo "=== DOKU WEBHOOK PROBE ==="
  echo "URL: $URL"
  echo
  curl -sS -i -X POST "$URL" \
    -H 'Content-Type: application/json' \
    -H 'Accept: application/json' \
    --data "$BODY"
  exit 0
fi

if [[ "$MODE" != "signed" ]]; then
  echo "Unknown mode: $MODE" >&2
  exit 2
fi

: "${DOKU_CLIENT_ID:?Set DOKU_CLIENT_ID}"
: "${DOKU_ACCESS_TOKEN:?Set DOKU_ACCESS_TOKEN}"
: "${DOKU_SECRET_KEY:?Set DOKU_SECRET_KEY}"

TIMESTAMP="${DOKU_TIMESTAMP:-$(date -u '+%Y-%m-%dT%H:%M:%SZ')}"
EXTERNAL_ID="${DOKU_EXTERNAL_ID:-$(date -u '+%Y%m%d%H%M%S')$$}"
PATH_ONLY="$(printf '%s' "$URL" | sed -E 's#^https?://[^/]+##')"
PATH_ONLY="/${PATH_ONLY#/}"

if command -v php >/dev/null 2>&1; then
  SIGNATURE="$(php -r '$body=$argv[1];$path=$argv[2];$token=$argv[3];$timestamp=$argv[4];$secret=$argv[5];$decoded=json_decode($body,true);$body=json_encode($decoded,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);$hash=hash("sha256",$body);$string="POST:".$path.":".$token.":".strtolower($hash).":".$timestamp;echo base64_encode(hash_hmac("sha512",$string,$secret,true));' "$BODY" "$PATH_ONLY" "$DOKU_ACCESS_TOKEN" "$TIMESTAMP" "$DOKU_SECRET_KEY")"
else
  echo "PHP is required for signed mode." >&2
  exit 2
fi

echo "=== DOKU WEBHOOK SIGNED TEST ==="
echo "URL: $URL"
echo "Timestamp: $TIMESTAMP"
echo "External ID: $EXTERNAL_ID"
echo

curl -sS -i -X POST "$URL" \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -H "X-PARTNER-ID: $DOKU_CLIENT_ID" \
  -H "X-EXTERNAL-ID: $EXTERNAL_ID" \
  -H "X-TIMESTAMP: $TIMESTAMP" \
  -H "X-SIGNATURE: $SIGNATURE" \
  -H "Authorization: Bearer $DOKU_ACCESS_TOKEN" \
  --data "$BODY"
