<?php
/**
 * Neuromax – User Model
 * 
 * Handles all database operations for the users table.
 * All queries use PDO prepared statements.
 */

require_once __DIR__ . '/../../includes/db.php';

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find a user by their email address.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Find a user by their ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Create a new user.
     * Password should already be hashed before calling this.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password, role)
             VALUES (:name, :email, :password, :role)'
        );
        $stmt->execute([
            ':name'     => $data['name'],
            ':email'    => $data['email'],
            ':password' => $data['password'],
            ':role'     => $data['role'] ?? 'user',
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update user profile data.
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['name', 'email', 'password', 'role', 'avatar'])) {
                $fields[] = "`{$key}` = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($fields)) return false;

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete a user by ID.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Get all users (admin listing).
     */
    public function getAll(string $search = ''): array
    {
        if ($search) {
            $stmt = $this->db->prepare(
                'SELECT id, name, email, role, created_at FROM users
                 WHERE name LIKE :search OR email LIKE :search2
                 ORDER BY created_at DESC'
            );
            $stmt->execute([
                ':search'  => '%' . $search . '%',
                ':search2' => '%' . $search . '%',
            ]);
        } else {
            $stmt = $this->db->query(
                'SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC'
            );
        }
        return $stmt->fetchAll();
    }

    /**
     * Count total users.
     */
    public function count(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) as total FROM users');
        return (int)$stmt->fetch()['total'];
    }

    /**
     * Get user with their active subscription info.
     */
    public function getUserWithSubscription(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, s.name as plan_name, s.price as plan_price, s.max_jobs,
                    us.start_date, us.end_date, us.status as sub_status
             FROM users u
             LEFT JOIN user_subscriptions us ON u.id = us.user_id AND us.status = "active"
             LEFT JOIN subscriptions s ON us.subscription_id = s.id
             WHERE u.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}
