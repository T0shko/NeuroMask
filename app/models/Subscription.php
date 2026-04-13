<?php
/**
 * Neuromax – Subscription Model
 * 
 * Handles subscription plans and user-plan relationships.
 */

require_once __DIR__ . '/../../includes/db.php';

class Subscription
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all available subscription plans.
     */
    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM subscriptions ORDER BY price ASC');
        return $stmt->fetchAll();
    }

    /**
     * Find a plan by ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM subscriptions WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $plan = $stmt->fetch();
        return $plan ?: null;
    }

    /**
     * Update a subscription plan.
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE subscriptions SET name = :name, price = :price, features = :features, max_jobs = :max_jobs WHERE id = :id'
        );
        return $stmt->execute([
            ':id'       => $id,
            ':name'     => $data['name'],
            ':price'    => $data['price'],
            ':features' => $data['features'],
            ':max_jobs' => $data['max_jobs'],
        ]);
    }

    /**
     * Assign a subscription plan to a user.
     * Deactivates any existing active subscription first.
     */
    public function assignToUser(int $userId, int $planId): bool
    {
        // Deactivate current active subscription
        $stmt = $this->db->prepare(
            'UPDATE user_subscriptions SET status = "expired"
             WHERE user_id = :uid AND status = "active"'
        );
        $stmt->execute([':uid' => $userId]);

        // Create new subscription
        $stmt = $this->db->prepare(
            'INSERT INTO user_subscriptions (user_id, subscription_id, start_date, end_date, status)
             VALUES (:uid, :sid, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), "active")'
        );
        return $stmt->execute([':uid' => $userId, ':sid' => $planId]);
    }

    /**
     * Get a user's active subscription.
     */
    public function getUserSubscription(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT s.*, us.start_date, us.end_date, us.status as sub_status
             FROM user_subscriptions us
             JOIN subscriptions s ON us.subscription_id = s.id
             WHERE us.user_id = :uid AND us.status = "active"
             LIMIT 1'
        );
        $stmt->execute([':uid' => $userId]);
        $sub = $stmt->fetch();
        return $sub ?: null;
    }

    /**
     * Get subscriber count per plan.
     */
    public function getSubscriberCounts(): array
    {
        $stmt = $this->db->query(
            'SELECT s.id, s.name, s.price, COUNT(us.id) as subscriber_count
             FROM subscriptions s
             LEFT JOIN user_subscriptions us ON s.id = us.subscription_id AND us.status = "active"
             GROUP BY s.id
             ORDER BY s.price ASC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Get max jobs allowed for a user based on their plan.
     */
    public function getUserMaxJobs(int $userId): int
    {
        $sub = $this->getUserSubscription($userId);
        return $sub ? (int)$sub['max_jobs'] : 5; // Default to Basic (5)
    }
}
