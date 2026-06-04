<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/ContactMessageRepository.php';

class ContactController extends AppController {
    private $messageRepository;

    public function __construct() {
        $this->messageRepository = ContactMessageRepository::getInstance();
    }

    public function submit() {
        $this->requireLogin();

        if (!$this->isPost()) {
            http_response_code(405);
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || $email === '' || $message === '') {
            $this->jsonResponse(['success' => false, 'error' => 'Please fill in all fields.'], 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['success' => false, 'error' => 'Please enter a valid email address.'], 400);
            return;
        }

        $this->messageRepository->create(
            (int) $_SESSION['user_id'],
            $name,
            $email,
            $message
        );

        $this->jsonResponse(['success' => true, 'message' => 'Your message has been sent. We will get back to you soon.']);
    }

    private function jsonResponse(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
