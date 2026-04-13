<?php
/**
 * Neuromax – Face Login Controller
 * 
 * Handles face enrollment and face-based authentication.
 * Responds with JSON for AJAX requests from face-login.js.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/FaceData.php';
require_once __DIR__ . '/../services/FaceMatchService.php';

class FaceLoginController
{
    private FaceData $faceDataModel;
    private FaceMatchService $matchService;
    private User $userModel;

    public function __construct()
    {
        $this->faceDataModel = new FaceData();
        $this->matchService = new FaceMatchService();
        $this->userModel = new User();
    }

    /**
     * Handle incoming AJAX requests.
     */
    public function handleRequest(): void
    {
        header('Content-Type: application/json');

        $action = $_GET['action'] ?? $_POST['action'] ?? '';

        switch ($action) {
            case 'enroll':
                $this->enroll();
                break;
            case 'authenticate':
                $this->authenticate();
                break;
            case 'remove':
                $this->remove();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action.']);
        }
    }

    /**
     * Enroll a face descriptor for the currently logged-in user.
     * Requires the user to be logged in.
     */
    private function enroll(): void
    {
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $descriptor = $input['descriptor'] ?? null;

        if (!$descriptor || !is_array($descriptor) || count($descriptor) !== 128) {
            echo json_encode([
                'success' => false,
                'error'   => 'Invalid face descriptor. Expected 128-float array.',
            ]);
            return;
        }

        // Validate descriptor values are floats
        foreach ($descriptor as $val) {
            if (!is_numeric($val)) {
                echo json_encode(['success' => false, 'error' => 'Descriptor contains non-numeric values.']);
                return;
            }
        }

        $this->faceDataModel->create(currentUserId(), $descriptor);

        echo json_encode([
            'success' => true,
            'message' => 'Face enrolled successfully!',
        ]);
    }

    /**
     * Authenticate using a face descriptor.
     * Compares against all stored descriptors.
     */
    private function authenticate(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $descriptor = $input['descriptor'] ?? null;

        if (!$descriptor || !is_array($descriptor) || count($descriptor) !== 128) {
            echo json_encode([
                'success' => false,
                'error'   => 'Invalid face descriptor.',
            ]);
            return;
        }

        // Find matching user
        $match = $this->matchService->findMatch($descriptor);

        if ($match) {
            // Log in the matched user
            $user = $this->userModel->findById($match['user_id']);
            if ($user) {
                loginUser($user);
                echo json_encode([
                    'success'  => true,
                    'message'  => 'Welcome, ' . $user['name'] . '!',
                    'redirect' => publicUrl('dashboard.php'),
                    'distance' => round($match['distance'], 4),
                ]);
                return;
            }
        }

        echo json_encode([
            'success' => false,
            'error'   => 'Face not recognized. Please try again or use email login.',
        ]);
    }

    /**
     * Remove face data for logged-in user.
     */
    private function remove(): void
    {
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
            return;
        }

        $this->faceDataModel->delete(currentUserId());

        echo json_encode([
            'success' => true,
            'message' => 'Face data removed.',
        ]);
    }
}

// ── Direct request handler ──
// This file is called directly via AJAX
if (basename($_SERVER['PHP_SELF']) === 'FaceLoginController.php') {
    $controller = new FaceLoginController();
    $controller->handleRequest();
}
