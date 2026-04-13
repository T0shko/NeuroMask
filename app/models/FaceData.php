<?php
/**
 * Neuromax – FaceData Model
 * 
 * Stores and retrieves face descriptors for biometric login.
 * Each descriptor is a 128-float vector from face-api.js.
 */

require_once __DIR__ . '/../../includes/db.php';

class FaceData
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Store a face descriptor for a user.
     * Replaces existing descriptor if one exists.
     */
    public function create(int $userId, array $descriptor): int
    {
        // Remove existing face data for this user (one face per user)
        $this->delete($userId);

        $stmt = $this->db->prepare(
            'INSERT INTO face_data (user_id, descriptor) VALUES (:uid, :desc)'
        );
        $stmt->execute([
            ':uid'  => $userId,
            ':desc' => json_encode($descriptor),
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Get face data for a user.
     */
    public function findByUser(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM face_data WHERE user_id = :uid LIMIT 1'
        );
        $stmt->execute([':uid' => $userId]);
        $data = $stmt->fetch();
        return $data ?: null;
    }

    /**
     * Get all stored face descriptors (for matching during login).
     */
    public function getAll(): array
    {
        $stmt = $this->db->query(
            'SELECT fd.*, u.name, u.email, u.role FROM face_data fd JOIN users u ON fd.user_id = u.id'
        );
        return $stmt->fetchAll();
    }

    /**
     * Delete face data for a user.
     */
    public function delete(int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM face_data WHERE user_id = :uid');
        return $stmt->execute([':uid' => $userId]);
    }
}
