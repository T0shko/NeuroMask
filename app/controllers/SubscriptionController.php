<?php
/**
 * Neuromax – Subscription Controller
 * 
 * Handles plan selection and subscription management.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../models/Subscription.php';

class SubscriptionController
{
    private Subscription $subscriptionModel;

    public function __construct()
    {
        $this->subscriptionModel = new Subscription();
    }

    /**
     * Handle plan selection / change.
     */
    public function selectPlan(): void
    {
        requireLogin();
        requireCsrf();

        $planId = (int)($_POST['plan_id'] ?? 0);

        if ($planId < 1 || $planId > 3) {
            setFlash('error', 'Invalid plan selected.');
            redirect(publicUrl('plans.php'));
        }

        $plan = $this->subscriptionModel->findById($planId);
        if (!$plan) {
            setFlash('error', 'Plan not found.');
            redirect(publicUrl('plans.php'));
        }

        $this->subscriptionModel->assignToUser(currentUserId(), $planId);

        setFlash('success', 'You are now subscribed to the ' . e($plan['name']) . ' plan!');
        redirect(publicUrl('plans.php'));
    }
}
