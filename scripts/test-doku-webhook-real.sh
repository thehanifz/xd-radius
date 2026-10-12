#!/usr/bin/env bash
set -euo pipefail

# Build a realistic DOKU SNAP VA payment notification from a real pending
# PaymentAttempt + PaymentAccount, then send it to the webhook endpoint.
#
# This intentionally uses the application's Laravel models/config only to
# discover the transaction data. No credentials are written to disk.
#
# Usage:
#   ./scripts/test-doku-webhook-real.sh https://example.test/payments/notifications
#
# Required environment variables:
#   DOKU_CLIENT_ID
#   DOKU_ACCESS_TOKEN
#   DOKU_CLIENT_SECRET
#
# Add SEND=1 to actually POST the notification. Without SEND=1 the script
# only builds and validates the signed request locally.

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
URL="${1:-}"

if [[ -z "$URL" ]]; then
    echo "Usage: $0 https://host/payments/notifications"
    exit 2
fi

for name in DOKU_CLIENT_ID DOKU_ACCESS_TOKEN DOKU_CLIENT_SECRET; do
    if [[ -z "${!name:-}" ]]; then
        echo "ERROR: $name belum di-set."
        exit 2
    fi
done

if [[ ! -f "$ROOT_DIR/artisan" ]]; then
    echo "ERROR: artisan tidak ditemukan di $ROOT_DIR"
    exit 2
fi

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

PAYLOAD_FILE="$TMP_DIR/payload.json"
META_FILE="$TMP_DIR/meta.json"

export TEST_DOKU_PAYLOAD_FILE="$PAYLOAD_FILE"
export TEST_DOKU_META_FILE="$META_FILE"
export TEST_DOKU_CLIENT_ID="$DOKU_CLIENT_ID"
export TEST_DOKU_ACCESS_TOKEN="$DOKU_ACCESS_TOKEN"
export TEST_DOKU_CLIENT_SECRET="$DOKU_CLIENT_SECRET"
export TEST_DOKU_PATH="$(php -r 'echo parse_url($argv[1], PHP_URL_PATH) ?: "/";' "$URL")"

cd "$ROOT_DIR"

php artisan tinker --execute='
$attempt = App\Models\PaymentAttempt::query()
    ->with("invoice.member")
    ->where("gateway", "doku")
    ->where("status", "pending")
    ->whereHas("invoice", fn ($q) => $q->whereIn("status", ["pending", "overdue"]))
    ->latest("id")
    ->first();

if (! $attempt) {
    throw new RuntimeException("Tidak ditemukan PaymentAttempt DOKU pending dengan invoice pending/overdue.");
}

$account = App\Models\PaymentAccount::query()
    ->where("gateway", "doku")
    ->where("status", "active")
    ->where("member_id", $attempt->invoice->member_id)
    ->whereNotNull("account_number")
    ->latest("id")
    ->first();

if (! $account) {
    throw new RuntimeException("Tidak ditemukan PaymentAccount DOKU aktif untuk member PaymentAttempt terpilih.");
}

if (! trim((string) $account->provider_account_id)) {
    throw new RuntimeException("provider_account_id pada PaymentAccount DOKU kosong; webhook application memang akan menolak trxId.");
}

$timestamp = gmdate("Y-m-d\\TH:i:s\\Z");
$externalId = gmdate("YmdHis") . str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);
$paymentRequestId = "TEST" . gmdate("YmdHis") . random_int(1000, 9999);
$amount = number_format((float) $attempt->amount, 2, ".", "");

$payload = [
    "partnerServiceId" => trim((string) $account->partner_service_id),
    "customerNo" => trim((string) $account->provider_customer_id),
    "virtualAccountNo" => trim((string) $account->account_number),
    "virtualAccountName" => trim((string) ($attempt->invoice->member->name ?? $attempt->invoice->member->username ?? "XD-RADIUS TEST")),
    "trxId" => trim((string) $account->provider_account_id),
    "paymentRequestId" => $paymentRequestId,
    "paidAmount" => [
        "value" => $amount,
        "currency" => "IDR",
    ],
    "trxDateTime" => $timestamp,
];

if (trim((string) ($attempt->invoice->member->email ?? "")) !== "") {
    $payload["virtualAccountEmail"] = trim((string) $attempt->invoice->member->email);
}

if (trim((string) ($attempt->invoice->member->phone ?? "")) !== "") {
    $payload["virtualAccountPhone"] = trim((string) $attempt->invoice->member->phone);
}

$body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($body === false) {
    throw new RuntimeException("Gagal encode payload JSON.");
}

$path = getenv("TEST_DOKU_PATH") ?: "/payments/notifications";
$accessToken = getenv("TEST_DOKU_ACCESS_TOKEN") ?: "";
$secret = getenv("TEST_DOKU_CLIENT_SECRET") ?: "";
$partnerId = getenv("TEST_DOKU_CLIENT_ID") ?: "";

$bodyHash = hash("sha256", $body);
$stringToSign = "POST:" . $path . ":" . $accessToken . ":" . strtolower($bodyHash) . ":" . $timestamp;
$signature = base64_encode(hash_hmac("sha512", $stringToSign, $secret, true));

file_put_contents(getenv("TEST_DOKU_PAYLOAD_FILE"), $body);
file_put_contents(getenv("TEST_DOKU_META_FILE"), json_encode([
    "timestamp" => $timestamp,
    "external_id" => $externalId,
    "payment_request_id" => $paymentRequestId,
    "partner_id" => $partnerId,
    "channel_id" => trim((string) ($account->doku_va_channel_id ?: "VA002")),
    "signature" => $signature,
    "string_to_sign_sha256" => hash("sha256", $stringToSign),
    "attempt_id" => $attempt->id,
    "invoice_id" => $attempt->invoice_id,
    "amount" => $attempt->amount,
    "account_id" => $account->id,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
'

TIMESTAMP="$(php -r '$x=json_decode(file_get_contents($argv[1]),true); echo $x["timestamp"];' "$META_FILE")"
EXTERNAL_ID="$(php -r '$x=json_decode(file_get_contents($argv[1]),true); echo $x["external_id"];' "$META_FILE")"
PAYMENT_REQUEST_ID="$(php -r '$x=json_decode(file_get_contents($argv[1]),true); echo $x["payment_request_id"];' "$META_FILE")"
CHANNEL_ID="$(php -r '$x=json_decode(file_get_contents($argv[1]),true); echo $x["channel_id"];' "$META_FILE")"
SIGNATURE="$(php -r '$x=json_decode(file_get_contents($argv[1]),true); echo $x["signature"];' "$META_FILE")"
ATTEMPT_ID="$(php -r '$x=json_decode(file_get_contents($argv[1]),true); echo $x["attempt_id"];' "$META_FILE")"
INVOICE_ID="$(php -r '$x=json_decode(file_get_contents($argv[1]),true); echo $x["invoice_id"];' "$META_FILE")"
AMOUNT="$(php -r '$x=json_decode(file_get_contents($argv[1]),true); echo $x["amount"];' "$META_FILE")"

echo "=== DOKU REALISTIC SNAP WEBHOOK TEST ==="
echo "URL: $URL"
echo "Path: ${TEST_DOKU_PATH}"
echo "Attempt ID: $ATTEMPT_ID"
echo "Invoice ID: $INVOICE_ID"
echo "Amount: $AMOUNT"
echo "Payment Request ID: $PAYMENT_REQUEST_ID"
echo "Timestamp: $TIMESTAMP"
echo "External ID: $EXTERNAL_ID"
echo "Channel ID: $CHANNEL_ID"
echo "Signature: [generated]"
echo
echo "Payload:"
cat "$PAYLOAD_FILE"
echo

if [[ "${SEND:-0}" != "1" ]]; then
    echo "DRY RUN: payload + signature berhasil dibuat."
    echo "Untuk benar-benar POST: SEND=1 $0 '$URL'"
    exit 0
fi

echo "POST webhook..."
curl --http2 -sS -D "$TMP_DIR/headers.txt" \
    -o "$TMP_DIR/response.txt" \
    -X POST "$URL" \
    -H 'Content-Type: application/json' \
    -H 'Accept: application/json' \
    -H "X-TIMESTAMP: $TIMESTAMP" \
    -H "X-SIGNATURE: $SIGNATURE" \
    -H "X-PARTNER-ID: $DOKU_CLIENT_ID" \
    -H "X-EXTERNAL-ID: $EXTERNAL_ID" \
    -H "CHANNEL-ID: $CHANNEL_ID" \
    -H "Authorization: Bearer $DOKU_ACCESS_TOKEN" \
    --data-binary "@$PAYLOAD_FILE"

cat "$TMP_DIR/headers.txt"
echo
cat "$TMP_DIR/response.txt"
echo
