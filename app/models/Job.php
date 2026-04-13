<?php
/**
 * Neuromax – Job Model
 * 
 * Handles all database operations for AI face-swap jobs.
 * Each job has a source face image and a target photo.
 */

require_once __DIR__ . '/../../includes/db.php';

class Job
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new face-swap job.
     * source_path = the face to swap in (source face)
     * file_path   = the target photo where the face gets replaced
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO jobs (user_id, source_path, file_path, effect, status)
             VALUES (:user_id, :source_path, :file_path, :effect, "pending")'
        );
        $stmt->execute([
            ':user_id'     => $data['user_id'],
            ':source_path' => $data['source_path'],
            ':file_path'   => $data['file_path'],
            ':effect'      => $data['effect'] ?? 'faceswap',
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Find a job by ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM jobs WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $job = $stmt->fetch();
        return $job ?: null;
    }

    /**
     * Get jobs for a specific user, optionally limited.
     */
    public function findByUser(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM jobs WHERE user_id = :uid ORDER BY created_at DESC LIMIT :lim'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Update job status and optionally set result path.
     */
    public function updateStatus(int $id, string $status, ?string $resultPath = null, ?string $errorMsg = null): bool
    {
        $sql = 'UPDATE jobs SET status = :status';
        $params = [':id' => $id, ':status' => $status];

        if ($resultPath !== null) {
            $sql .= ', result_path = :result_path';
            $params[':result_path'] = $resultPath;
        }

        if ($errorMsg !== null) {
            $sql .= ', error_msg = :error_msg';
            $params[':error_msg'] = $errorMsg;
        }

        $sql .= ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete a job by ID.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM jobs WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Get job statistics for a user (counts by status).
     */
    public function getStats(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "processing" THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
             FROM jobs WHERE user_id = :uid'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetch() ?: ['total' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0, 'failed' => 0];
    }

    /**
     * Get all jobs (admin view) with user info.
     */
    public function getAll(string $statusFilter = '', int $limit = 100): array
    {
        $sql = 'SELECT j.*, u.name as user_name, u.email as user_email
                FROM jobs j
                JOIN users u ON j.user_id = u.id';
        $params = [];

        if ($statusFilter) {
            $sql .= ' WHERE j.status = :status';
            $params[':status'] = $statusFilter;
        }

        $sql .= ' ORDER BY j.created_at DESC LIMIT :lim';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Count total jobs (admin stats).
     */
    public function countAll(): array
    {
        $stmt = $this->db->query(
            'SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "processing" THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
             FROM jobs'
        );
        return $stmt->fetch() ?: [];
    }

    /**
     * Count jobs created this month by a user (for plan limit checking).
     */
    public function countMonthlyJobs(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as total FROM jobs
             WHERE user_id = :uid
             AND MONTH(created_at) = MONTH(CURRENT_DATE())
             AND YEAR(created_at) = YEAR(CURRENT_DATE())'
        );
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetch()['total'];
    }
}
