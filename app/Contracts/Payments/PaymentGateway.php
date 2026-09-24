<?php

namespace App\Contracts\Payments;

use App\Models\BillingInvoice;
use App\Models\Member;

interface PaymentGateway
{
    public function createPaymentAttempt(BillingInvoice $invoice, string $method): array;

    public function createReusableVirtualAccount(Member $member, string $bank = 'BNI'): array;

    public function verifyWebhook(array $headers, string $rawBody, string $path): bool;
}
