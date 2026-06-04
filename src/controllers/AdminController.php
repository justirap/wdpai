<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/AdminRepository.php';
require_once __DIR__.'/../repositories/ContactMessageRepository.php';
require_once __DIR__.'/../repositories/UsersRepository.php';
require_once __DIR__.'/../repositories/MovieRepository.php';
require_once __DIR__.'/../repositories/ScreeningRepository.php';

class AdminController extends AppController {
    private const MAX_POSTER_BYTES = 5 * 1024 * 1024;
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private $adminRepository;
    private $messageRepository;
    private $usersRepository;
    private $movieRepository;
    private $screeningRepository;

    public function __construct() {
        $this->adminRepository = AdminRepository::getInstance();
        $this->messageRepository = ContactMessageRepository::getInstance();
        $this->usersRepository = UsersRepository::getInstance();
        $this->movieRepository = MovieRepository::getInstance();
        $this->screeningRepository = ScreeningRepository::getInstance();
    }

    public function index() {
        $this->requireAdmin();

        return $this->render('admin/dashboard', [
            'activeSection' => 'dashboard',
            'stats' => $this->adminRepository->getDashboardStats(),
            'recentReservations' => $this->adminRepository->getRecentReservations(8),
            'users' => $this->adminRepository->getAllUsers(),
            'unreadMessages' => $this->messageRepository->getUnreadCount(),
        ]);
    }

    public function messages() {
        $this->requireAdmin();

        if (isset($_GET['read'])) {
            $id = (int) $_GET['read'];
            if ($id > 0) {
                $this->messageRepository->markAsRead($id);
            }
            $url = "http://$_SERVER[HTTP_HOST]/admin/messages";
            header("Location: {$url}");
            exit();
        }

        return $this->render('admin/messages', [
            'activeSection' => 'messages',
            'messages' => $this->messageRepository->getAll(),
            'unreadMessages' => $this->messageRepository->getUnreadCount(),
        ]);
    }

    public function reservations() {
        $this->requireAdmin();

        return $this->render('admin/reservations', [
            'activeSection' => 'reservations',
            'reservations' => $this->adminRepository->getRecentReservations(50),
            'unreadMessages' => $this->messageRepository->getUnreadCount(),
        ]);
    }

    public function users() {
        $this->requireAdmin();

        if ($this->isPost() && isset($_POST['delete_user_id'])) {
            $deleteId = (int) $_POST['delete_user_id'];
            if ($deleteId !== (int) $_SESSION['user_id']) {
                $this->usersRepository->deleteUser($deleteId);
            }
            $url = "http://$_SERVER[HTTP_HOST]/admin/users";
            header("Location: {$url}");
            exit();
        }

        return $this->render('admin/users', [
            'activeSection' => 'users',
            'users' => $this->adminRepository->getAllUsers(),
            'unreadMessages' => $this->messageRepository->getUnreadCount(),
        ]);
    }

    public function movies() {
        $this->requireAdmin();

        $messages = [];
        $errors = [];
        $old = [];

        if ($this->isPost()) {
            $result = $this->handleAddMovie();
            if ($result['success']) {
                $messages[] = $result['message'];
            } else {
                $errors = $result['errors'];
                $old = $_POST;
            }
        }

        return $this->render('admin/movies', [
            'activeSection' => 'movies',
            'movies' => $this->movieRepository->getAllMoviesAdmin(),
            'categories' => $this->movieRepository->getAllCategories(),
            'messages' => $messages,
            'errors' => $errors,
            'old' => $old,
            'unreadMessages' => $this->messageRepository->getUnreadCount(),
        ]);
    }

    public function reports() {
        $this->requireAdmin();

        return $this->render('admin/reports', [
            'activeSection' => 'reports',
            'stats' => $this->adminRepository->getDashboardStats(),
            'unreadMessages' => $this->messageRepository->getUnreadCount(),
        ]);
    }

    private function handleAddMovie(): array {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $duration = (int) ($_POST['duration'] ?? 0);
        $categoryIds = $_POST['categories'] ?? [];

        if (!is_array($categoryIds)) {
            $categoryIds = [];
        }
        $categoryIds = array_map('intval', $categoryIds);

        $errors = [];
        if ($title === '') {
            $errors[] = 'Title is required.';
        }
        if ($description === '') {
            $errors[] = 'Description is required.';
        }
        if ($duration <= 0) {
            $errors[] = 'Duration must be greater than 0 minutes.';
        }
        if (empty($categoryIds)) {
            $errors[] = 'Select at least one category.';
        }

        $upload = $_FILES['poster'] ?? null;
        if (!$upload || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Poster image is required.';
        } elseif (($upload['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors[] = 'Failed to upload image. Please try again.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $filename = $this->savePosterUpload($upload);
        } catch (RuntimeException $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }

        try {
            $movieId = $this->movieRepository->createMovie($title, $description, $filename, $duration);
            $this->movieRepository->attachCategories($movieId, $categoryIds);
            $this->screeningRepository->createDefaultScreenings($movieId);
        } catch (Exception $e) {
            $this->deletePosterFile($filename);
            return ['success' => false, 'errors' => ['Could not save movie. Please try again.']];
        }

        return [
            'success' => true,
            'message' => "Movie \"{$title}\" added successfully with 4 default showtimes.",
        ];
    }

    private function savePosterUpload(array $file): string {
        if ($file['size'] > self::MAX_POSTER_BYTES) {
            throw new RuntimeException('Image must be smaller than 5 MB.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset(self::ALLOWED_MIME[$mime])) {
            throw new RuntimeException('Only JPG, PNG and WebP images are allowed.');
        }

        if (!getimagesize($file['tmp_name'])) {
            throw new RuntimeException('Uploaded file is not a valid image.');
        }

        $uploadDir = realpath(__DIR__ . '/../../public/img');
        if ($uploadDir === false || !is_dir($uploadDir) || !is_writable($uploadDir)) {
            throw new RuntimeException('Upload directory is not writable.');
        }

        $extension = self::ALLOWED_MIME[$mime];
        $filename = 'movie_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new RuntimeException('Could not save uploaded image.');
        }

        return $filename;
    }

    private function deletePosterFile(string $filename): void {
        $basename = basename($filename);
        $path = realpath(__DIR__ . '/../../public/img');
        if ($path === false) {
            return;
        }
        $file = $path . DIRECTORY_SEPARATOR . $basename;
        if (is_file($file)) {
            unlink($file);
        }
    }
}
