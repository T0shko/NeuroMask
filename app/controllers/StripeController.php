<?php
/**
 * Neuromax – Stripe Controller
 *
 * Handles creation of Stripe Checkout Sessions and webhook events.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../models/Subscription.php';
require_once __DIR__ . '/../services/StripeService.php';

class StripeController
{
    private StripeService $stripe;
    private Subscription  $subModel;
    private PDO           $db;

    public function __construct()
    {
        $this->stripe   = new StripeService(STRIPE_SECRET_KEY);
        $this->subModel = new Subscription();
        $this->db       = Database::getInstance();
    }

    // ── Create Checkout Session ───────────────────────────────────────────────

    /**
     * POST /public/payment/checkout.php
     * Expects: plan_id (int), csrf token
     * Creates a Stripe Checkout Session and redirects the user to Stripe.
     */
    public function createCheckout(): void
    {
        requireLogin();
        requireCsrf();

        $planId = (int)($_POST['plan_id'] ?? 0);
        $plan   = $this->subModel->findById($planId);

        if (!$plan || (float)$plan['price'] <= 0) {
            setFlash('error', 'Invalid plan selected for payment.');
            redirect(publicUrl('plans.php'));
        }

        if (empty($plan['stripe_price_id'])) {
            setFlash('error', 'Stripe price not configured for this plan. Please contact support.');
            redirect(publicUrl('plans.php'));
        }

        $userId = currentUserId();
        $user   = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $user->execute([':id' => $userId]);
        $userData = $user->fetch();

        $session = $this->stripe->createCheckoutSession([
            'mode'                 => 'subscription',
            'customer_email'       => $userData['email'],
            'line_items'           => [
                [
                    'price'    => $plan['stripe_price_id'],
                    'quantity' => 1,
                ],
            ],
            'success_url'          => STRIPE_SUCCESS_URL,
            'cancel_url'           => STRIPE_CANCEL_URL,
            'metadata'             => [
                'user_id' => $userId,
                'plan_id' => $planId,
            ],
            'subscription_data'    => [
                'metadata' => [
                    'user_id' => $userId,
                    'plan_id' => $planId,
                ],
            ],
            'allow_promotion_codes' => 'true',
        ]);

        if (isset($session['error'])) {
            setFlash('error', 'Payment error: ' . ($session['error']['message'] ?? 'Unknown error'));
            redirect(publicUrl('plans.php'));
        }

        // Save pending session ID for verification on return
        $this->savePendingSession($userId, $planId, $session['id']);

        header('Location: ' . $session['url']);
        exit;
    }

    // ── Handle Success Redirect ───────────────────────────────────────────────

    /**
     * GET /public/payment/success.php?session_id=cs_xxx
     * Verifies payment and activates subscription.
     */
    public function handleSuccess(): void
    {
        requireLogin();

        $sessionId = $_GET['session_id'] ?? '';
        if (!$sessionId) {
            setFlash('error', 'Missing session ID.');
            redirect(publicUrl('plans.php'));
        }

        $session = $this->stripe->retrieveCheckoutSession($sessionId);

        if (($session['payment_status'] ?? '') !== 'paid' &&
            ($session['status'] ?? '') !== 'complete') {
            setFlash('error', 'Payment not completed.');
            redirect(publicUrl('plans.php'));
        }

        $userId = (int)($session['metadata']['user_id'] ?? 0);
        $planId = (int)($session['metadata']['plan_id'] ?? 0);

        // Only activate for the currently logged-in user
        if ($userId !== currentUserId()) {
            setFlash('error', 'Session mismatch.');
            redirect(publicUrl('plans.php'));
        }

        $plan = $this->subModel->findById($planId);
        if ($plan) {
            $this->activateSubscription(
                $userId,
                $planId,
                $sessionId,
                $session['customer'] ?? null,
                $session['subscription'] ?? null
            );
            setFlash('success', 'Payment successful! You are now on the ' . e($plan['name']) . ' plan.');
        }

        redirect(publicUrl('plans.php'));
    }

    // ── Stripe Webhook ────────────────────────────────────────────────────────

    /**
     * POST /public/payment/webhook.php
     * Handles Stripe events for subscription lifecycle.
     */
    public function handleWebhook(): void
    {
        $payload   = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        $event = $this->stripe->constructEvent($payload, $sigHeader, STRIPE_WEBHOOK_SECRET);

        if (!$event) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid signature or stale event.']);
            exit;
        }

        $object = $event['data']['object'] ?? [];

        switch ($event['type']) {
            case 'checkout.session.completed':
                $userId = (int)($object['metadata']['user_id'] ?? 0);
                $planId = (int)($object['metadata']['plan_id'] ?? 0);
                if ($userId && $planId) {
                    $this->activateSubscription(
                        $userId, $planId,
                        $object['id']           ?? null,
                        $object['customer']     ?? null,
                        $object['subscription'] ?? null
                    );
                }
                break;

            case 'customer.subscription.deleted':
                $customerId = $object['customer'] ?? null;
                if ($customerId) {
                    $this->cancelByCustomer($customerId);
                }
                break;
        }

        http_response_code(200);
        echo json_encode(['received' => true]);
    }

    // ── Internal Helpers ──────────────────────────────────────────────────────

    private function activateSubscription(
        int    $userId,
        int    $planId,
        ?string $sessionId,
        ?string $customerId,
        ?string $subscriptionId
    ): void {
        // Expire existing active subscriptions
        $stmt = $this->db->prepare(
            'UPDATE user_subscriptions SET status = "expired"
             WHERE user_id = :uid AND status = "active"'
        );
        $stmt->execute([':uid' => $userId]);

        // Insert new active subscription
        $stmt = $this->db->prepare(
            'INSERT INTO user_subscriptions
                (user_id, subscription_id, start_date, end_date, status,
                 stripe_session_id, stripe_customer_id, stripe_subscription_id)
             VALUES
                (:uid, :sid, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), "active",
                 :sess, :cust, :sub)'
        );
        $stmt->execute([
            ':uid'  => $userId,
            ':sid'  => $planId,
            ':sess' => $sessionId,
            ':cust' => $customerId,
            ':sub'  => $subscriptionId,
        ]);
    }

    private function cancelByCustomer(string $customerId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE user_subscriptions SET status = "cancelled"
             WHERE stripe_customer_id = :cid AND status = "active"'
        );
        $stmt->execute([':cid' => $customerId]);
    }

    private function savePendingSession(int $userId, int $planId, string $sessionId): void
    {
        // Store session ID so we can verify it on the success redirect
        // (lightweight: just update the most recent expired/active row or do nothing)
        // The full activation happens in handleSuccess() after verification.
    }
}
