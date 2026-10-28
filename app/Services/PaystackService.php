<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Seam for Paystack card payments.
 *
 * Card payments are not switched on yet: the parent form shows the card option
 * as "coming soon" and every payment is still confirmed by the CEO. When the
 * school has a Paystack account:
 *
 *   1. Add PAYSTACK_PUBLIC_KEY and PAYSTACK_SECRET_KEY to .env.
 *   2. Add a `parent.payments.card` route that calls initializeTransaction() and
 *      redirects the parent to `data.authorization_url`.
 *   3. Add the callback route, call verifyTransaction($reference) and, when
 *      `data.status === 'success'`, create the Payment with
 *      payment_method = Payment::METHOD_CARD, status = completed, gateway =
 *      Payment::GATEWAY_PAYSTACK and gateway_reference = $reference (the gateway
 *      already took the money, so there is nothing for the CEO to verify).
 *
 * The admin bank-accounts screen reads configured()/missingKeys() so the CEO can
 * see whether card payments can be enabled.
 */
class PaystackService
{
    /**
     * Initialise a transaction and return Paystack's payload (contains
     * `authorization_url` for the redirect).
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initializeTransaction(array $payload): array
    {
        return $this->request('post', '/transaction/initialize', $payload);
    }

    /**
     * Confirm a transaction with Paystack before marking a payment complete.
     *
     * @return array<string, mixed>
     */
    public function verifyTransaction(string $reference): array
    {
        return $this->request('get', '/transaction/verify/' . rawurlencode($reference));
    }

    /** Both keys have to be present before the card option can be offered. */
    public static function configured(): bool
    {
        return static::missingKeys() === [];
    }

    /**
     * The env keys that still need to be set, e.g. ['PAYSTACK_SECRET_KEY'].
     *
     * @return array<int, string>
     */
    public static function missingKeys(): array
    {
        return array_values(array_filter([
            filled(config('services.paystack.public_key')) ? null : 'PAYSTACK_PUBLIC_KEY',
            filled(config('services.paystack.secret_key')) ? null : 'PAYSTACK_SECRET_KEY',
        ]));
    }

    public static function publicKey(): ?string
    {
        return config('services.paystack.public_key');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload = []): array
    {
        if (! static::configured()) {
            throw new RuntimeException('Paystack is not configured. Add ' . implode(' and ', static::missingKeys()) . ' to the environment first.');
        }

        $client = Http::withToken((string) config('services.paystack.secret_key'))
            ->acceptJson()
            ->baseUrl((string) config('services.paystack.base_url'));

        $response = $method === 'post'
            ? $client->post($path, $payload)
            : $client->get($path);

        return (array) $response->throw()->json();
    }
}
