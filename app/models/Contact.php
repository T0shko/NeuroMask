<?php
/**
 * Neuromax – Contact Model
 * 
 * Handles contact form submissions.
 */

require_once __DIR__ . '/../../includes/db.php';

class Contact
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Store a new contact message.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO contacts (name, email, subject, message)
             VALUES (:name, :email, :subject, :message)'
        );
        $stmt->execute([
            ':name'    => $data['name'],
            ':email'   => $data['email'],
            ':subject' => $data['subject'],
            ':message' => $data['message'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Get all contact messages.
     */
    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM contacts ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    /**
     * Mark a message as read.
     */
    public function markRead(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE contacts SET is_read = 1 WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Count unread messages.
     */
    public function countUnread(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) as total FROM contacts WHERE is_read = 0');
        return (int)$stmt->fetch()['total'];
    }
}
