<?php
/**
 * Neuromax – Admin Controller
 * 
 * Handles admin operations: user management, job management,
 * subscription management, and dashboard stats.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Job.php';
require_once __DIR__ . '/../models/Subscription.php';
require_once __DIR__ . '/../models/Contact.php';

class AdminController
{
    private User $userModel;
    private Job $jobModel;
    private Subscription $subModel;
    private Contact $contactModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->jobModel = new Job();
        $this->subModel = new Subscription();
        $this->contactModel = new Contact();
    }

    /**
     * Get dashboard aggregate stats.
     */
    public function getDashboardStats(): array
    {
        return [
            'total_users'    => $this->userModel->count(),
            'job_stats'      => $this->jobModel->countAll(),
            'plan_stats'     => $this->subModel->getSubscriberCounts(),
            'unread_msgs'    => $this->contactModel->countUnread(),
        ];
    }

    /**
     * Delete a user.
     */
    public function deleteUser(): void
    {
        requireAdmin();
        requireCsrf();

        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId < 1) {
            setFlash('error', 'Invalid user ID.');
            redirect(url('admin/users.php'));
            return;
        }

        // Prevent deleting self
        if ($userId === currentUserId()) {
            setFlash('error', 'You cannot delete your own account.');
            redirect(url('admin/users.php'));
            return;
        }

        $this->userModel->delete($userId);
        setFlash('success', 'User deleted successfully.');
        redirect(url('admin/users.php'));
    }

    /**
     * Toggle user role between 'user' and 'admin'.
     */
    public function toggleRole(): void
    {
        requireAdmin();
        requireCsrf();

        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId < 1) {
            setFlash('error', 'Invalid user ID.');
            redirect(url('admin/users.php'));
            return;
        }

        $user = $this->userModel->findById($userId);
        if (!$user) {
            setFlash('error', 'User not found.');
            redirect(url('admin/users.php'));
            return;
        }

        $newRole = $user['role'] === 'admin' ? 'user' : 'admin';
        $this->userModel->update($userId, ['role' => $newRole]);
        setFlash('success', 'User role changed to ' . $newRole . '.');
        redirect(url('admin/users.php'));
    }

    /**
     * Delete a job.
     */
    public function deleteJob(): void
    {
        requireAdmin();
        requireCsrf();

        $jobId = (int)($_POST['job_id'] ?? 0);
        $this->jobModel->delete($jobId);
        setFlash('success', 'Job deleted.');
        redirect(url('admin/jobs.php'));
    }

    /**
     * Update a subscription plan.
     */
    public function updatePlan(): void
    {
        requireAdmin();
        requireCsrf();

        $planId = (int)($_POST['plan_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $maxJobs = (int)($_POST['max_jobs'] ?? 10);
        $features = trim($_POST['features'] ?? '[]');

        // Validate features is valid JSON
        $decoded = json_decode($features);
        if ($decoded === null) {
            setFlash('error', 'Features must be valid JSON array.');
            redirect(url('admin/subscriptions.php'));
            return;
        }

        $this->subModel->update($planId, [
            'name'     => $name,
            'price'    => $price,
            'features' => $features,
            'max_jobs' => $maxJobs,
        ]);

        setFlash('success', 'Plan updated successfully.');
        redirect(url('admin/subscriptions.php'));
    }
}
