<?php
/**
 * Neuromax – Contact Controller
 * 
 * Handles contact form submission.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../models/Contact.php';

class ContactController
{
    private Contact $contactModel;

    public function __construct()
    {
        $this->contactModel = new Contact();
    }

    /**
     * Store a contact form submission.
     */
    public function store(): void
    {
        requireCsrf();

        $errors = [];
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || strlen($name) < 2) {
            $errors[] = 'Name must be at least 2 characters.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (empty($subject)) {
            $errors[] = 'Subject is required.';
        }
        if (empty($message) || strlen($message) < 10) {
            $errors[] = 'Message must be at least 10 characters.';
        }

        if (!empty($errors)) {
            setFlash('error', implode('<br>', $errors));
            redirect(publicUrl('contact.php'));
        }

        $this->contactModel->create([
            'name'    => $name,
            'email'   => $email,
            'subject' => $subject,
            'message' => $message,
        ]);

        setFlash('success', 'Your message has been sent! We\'ll get back to you soon.');
        redirect(publicUrl('contact.php'));
    }
}
