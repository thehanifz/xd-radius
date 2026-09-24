# XD-radius — DOKU Payment Integration

## Current implementation

The payment integration is split into:

- `PaymentService`: billing/payment orchestration and webhook idempotency.
- `PaymentGateway`: provider boundary.
- `DokuPaymentGateway`: DOKU-specific VA/QRIS operations.
- `PaymentAttempt`: one payment attempt for an invoice.
- `PaymentAccount`: reusable customer payment account (e.g. BNI VA).
- `PaymentWebhookEvent`: durable provider event/idempotency record.

Members do not authenticate to XD-radius. They receive a public payment URL containing a cryptographically random token.

## DOKU SNAP prerequisites

DOKU SNAP requires a merchant RSA key pair in addition to Client ID and Secret Key. The merchant public key is registered in DOKU; the private key stays only on the XD-radius server.

Required environment variables:

```dotenv
DOKU_ENABLED=true
DOKU_ENVIRONMENT=sandbox
DOKU_BASE_URL=https://api-sandbox.doku.com
DOKU_CLIENT_ID=...
DOKU_SECRET_KEY=...
DOKU_PRIVATE_KEY_PATH=/secure/path/doku/private.pem
DOKU_PRIVATE_KEY_PASSPHRASE=...
DOKU_MERCHANT_ID=...
DOKU_TERMINAL_ID=...
DOKU_CHANNEL_ID=H2H
DOKU_VA_PARTNER_SERVICE_ID=98829172
DOKU_VA_CUSTOMER_PREFIX=3
DOKU_NOTIFICATION_PATH=/webhooks/doku
```

Never commit any private key or secret value to Git.

## Credential check

After environment configuration:

```bash
php artisan doku:check
```

The command requests a short-lived B2B token but never prints the token.

## Webhook

DOKU notification URL:

```text
https://<xd-radius-host>/webhooks/doku
```

The endpoint is outside admin authentication and CSRF validation. It verifies DOKU signature, partner ID, stores the event with a unique provider event ID, and applies payment state changes exactly once.

## Payment state separation

A successful payment does not automatically imply RADIUS provisioning success.

```text
DOKU SUCCESS
  -> Payment PAID
  -> Invoice PAID
  -> Provisioning SUCCESS / FAILED
```

If RADIUS provisioning fails after payment, the customer must not be charged again.

## VA model

The target is one reusable VA per member. The current BNI SNAP integration uses the merchant's `partnerServiceId`, a deterministic member customer number, and `virtualAccountConfig.reusableStatus=true`.

The account is created once; each invoice updates the amount on the same VA.

## QRIS

Dynamic QRIS is generated per invoice using the DOKU SNAP QR MPM endpoint. The current code stores the returned QR content in the payment attempt. A QR renderer should be added to the public payment UI before customer-facing QRIS is enabled in production.

## Production gate

Do not switch `DOKU_BASE_URL` to production until:

1. DOKU merchant verification is complete.
2. QRIS service is activated.
3. BNI reusable VA behavior is verified in the actual merchant account.
4. Merchant public key is registered at DOKU.
5. Sandbox payment and webhook tests pass.
6. Payment-success/RADIUS-failure retry behavior is verified.
