<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Charges invoices through Accept Blue (issue #27).
 *
 * Card data is never handled here — the public pay page tokenizes the card via
 * Accept Blue's hosted fields and only the resulting token/nonce reaches the
 * server, which we charge against. The `mode` config decides sandbox vs live so
 * test charges never touch the production gateway.
 */
class AcceptBluePaymentService
{
    /**
     * Charge a tokenized card / ACH source for an invoice.
     *
     * @param  string  $sourceToken  Nonce/token produced by the hosted fields.
     * @param  string  $type         'card' or 'ach'.
     * @return array{success: bool, transaction_id: ?string, status: ?string, error: ?string}
     */
    public function charge(Invoice $invoice, string $sourceToken, float $amount, string $type = 'card'): array
    {
        try {
            $endpoint = $this->endpoint();
        } catch (RuntimeException $e) {
            return $this->failure($e->getMessage());
        }

        if ($sourceToken === '' || $amount <= 0) {
            return $this->failure('Missing payment token or amount.');
        }

        $payload = [
            'amount' => round($amount, 2),
            'source' => $sourceToken,
            'capture' => true,
            'reference' => $invoice->invoice_number,
            'transaction_details' => [
                'description' => 'Invoice ' . $invoice->invoice_number,
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->authorizationHeader(),
                'Accept' => 'application/json',
            ])
                ->timeout(20)
                ->post(rtrim($endpoint, '/') . '/transactions/charge', $payload);
        } catch (\Throwable $e) {
            Log::error('Accept Blue charge request failed: ' . $e->getMessage());

            return $this->failure('Could not reach the payment processor. Please try again.');
        }

        $body = $response->json() ?? [];

        if (! $response->successful()) {
            $message = $body['error_message'] ?? $body['message'] ?? 'Payment was declined.';
            Log::warning('Accept Blue charge non-2xx', ['status' => $response->status(), 'body' => $body]);

            return $this->failure((string) $message, $this->extractStatus($body));
        }

        $status = $this->extractStatus($body);
        $approved = in_array(strtolower((string) $status), ['approved', 'partially_approved', 'success'], true);

        if (! $approved) {
            $message = $body['error_message'] ?? $body['decline_reason'] ?? 'Payment was not approved.';

            return $this->failure((string) $message, $status);
        }

        return [
            'success' => true,
            'transaction_id' => $this->extractTransactionId($body),
            'status' => $status,
            'error' => null,
        ];
    }

    public function isLive(): bool
    {
        return config('services.accept_blue.mode') === 'live';
    }

    /** Resolve the API base URL for the active mode; refuse to fall back to live in sandbox mode. */
    private function endpoint(): string
    {
        if ($this->isLive()) {
            $endpoint = config('services.accept_blue.endpoint');
            if (! $endpoint) {
                throw new RuntimeException('Accept Blue live endpoint is not configured.');
            }

            return $endpoint;
        }

        $endpoint = config('services.accept_blue.sandbox_endpoint');
        if (! $endpoint) {
            throw new RuntimeException('Accept Blue sandbox endpoint is not configured (ACCEPT_BLUE_SANDBOX_ENDPOINT).');
        }

        return $endpoint;
    }

    private function authorizationHeader(): string
    {
        $token = config('services.accept_blue.auth_token');
        if ($token) {
            return 'Basic ' . $token;
        }

        $apiKey = (string) config('services.accept_blue.api_key');
        $sourceKey = (string) config('services.accept_blue.source_key');

        return 'Basic ' . base64_encode($apiKey . ':' . $sourceKey);
    }

    /** @param array<string, mixed> $body */
    private function extractStatus(array $body): ?string
    {
        return $body['status']
            ?? $body['transaction']['status']
            ?? $body['result']
            ?? null;
    }

    /** @param array<string, mixed> $body */
    private function extractTransactionId(array $body): ?string
    {
        $id = $body['transaction']['id']
            ?? $body['transaction_id']
            ?? $body['id']
            ?? null;

        return $id !== null ? (string) $id : null;
    }

    /** @return array{success: bool, transaction_id: null, status: ?string, error: string} */
    private function failure(string $error, ?string $status = null): array
    {
        return [
            'success' => false,
            'transaction_id' => null,
            'status' => $status,
            'error' => $error,
        ];
    }
}
