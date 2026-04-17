<?php
/**
 * Neuromax – Stripe Service
 *
 * Thin PHP/cURL wrapper around the Stripe REST API.
 * No Composer dependency — works out of the box with any PHP 7.4+ + cURL.
 */

class StripeService
{
    private const API_BASE = 'https://api.stripe.com/v1';
    private string $secretKey;

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    // ── Checkout Sessions ────────────────────────────────────────────────────

    /**
     * Create a Stripe Checkout Session.
     *
     * @param array $params  Stripe checkout.session.create parameters
     * @return array  Decoded response (contains 'url' on success, 'error' on failure)
     */
    public function createCheckoutSession(array $params): array
    {
        return $this->post('/checkout/sessions', $params);
    }

    /**
     * Retrieve a Checkout Session by ID.
     */
    public function retrieveCheckoutSession(string $sessionId): array
    {
        return $this->get('/checkout/sessions/' . urlencode($sessionId));
    }

    // ── Customers ────────────────────────────────────────────────────────────

    public function createCustomer(array $params): array
    {
        return $this->post('/customers', $params);
    }

    // ── Webhook Signature Verification ───────────────────────────────────────

    /**
     * Verify a Stripe webhook signature and return the decoded event array.
     * Returns null if the signature is invalid or timestamp is too old (> 5 min).
     */
    public function constructEvent(string $payload, string $sigHeader, string $secret): ?array
    {
        $parts     = [];
        $timestamp = null;
        $sigs      = [];

        foreach (explode(',', $sigHeader) as $part) {
            $kv = explode('=', $part, 2);
            if (count($kv) !== 2) continue;
            if ($kv[0] === 't')  $timestamp = (int)$kv[1];
            if ($kv[0] === 'v1') $sigs[]    = $kv[1];
        }

        if (!$timestamp || empty($sigs)) return null;

        // Reject events older than 5 minutes
        if (abs(time() - $timestamp) > 300) return null;

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        $valid    = false;
        foreach ($sigs as $sig) {
            if (hash_equals($expected, $sig)) { $valid = true; break; }
        }

        return $valid ? json_decode($payload, true) : null;
    }

    // ── Internal HTTP helpers ─────────────────────────────────────────────────

    private function post(string $endpoint, array $params): array
    {
        return $this->request('POST', $endpoint, $params);
    }

    private function get(string $endpoint): array
    {
        return $this->request('GET', $endpoint);
    }

    private function request(string $method, string $endpoint, array $params = []): array
    {
        $ch = curl_init(self::API_BASE . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_USERPWD        => $this->secretKey . ':',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_USERAGENT      => 'Neuromax/1.0 PHP/' . PHP_VERSION,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->buildQuery($params));
        }

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($body, true) ?? [];
        $data['_http_status'] = $code;
        return $data;
    }

    /**
     * Recursively build a URL-encoded query string for nested Stripe params.
     * e.g. ['line_items' => [['price' => 'price_xxx', 'quantity' => 1]]]
     * becomes line_items[0][price]=price_xxx&line_items[0][quantity]=1
     */
    private function buildQuery(array $params, string $prefix = ''): string
    {
        $parts = [];
        foreach ($params as $key => $value) {
            $fullKey = $prefix !== '' ? $prefix . '[' . $key . ']' : $key;
            if (is_array($value)) {
                $parts[] = $this->buildQuery($value, $fullKey);
            } else {
                $parts[] = urlencode($fullKey) . '=' . urlencode((string)$value);
            }
        }
        return implode('&', $parts);
    }
}
