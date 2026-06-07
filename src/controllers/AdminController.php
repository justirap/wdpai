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

        $screenings = $old['screenings'] ?? $this->getDefaultScreenings();

        return $this->render('admin/movies', [
            'activeSection' => 'movies',
            'movies' => $this->movieRepository->getAllMoviesAdmin(),
            'categories' => $this->movieRepository->getAllCategories(),
            'screenings' => $screenings,
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
        if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            return [
                'success' => false,
                'errors' => ['Upload too large. Poster must be smaller than 5 MB.'],
            ];
        }

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

        $screeningResult = $this->parseScreenings($_POST['screenings'] ?? []);
        if (!empty($screeningResult['errors'])) {
            $errors = array_merge($errors, $screeningResult['errors']);
        }

        $upload = $_FILES['poster'] ?? null;
        if (!$upload || !isset($upload['error'])) {
            $errors[] = 'Poster image is required.';
        } elseif ($upload['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Poster image is required.';
        } elseif ($upload['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->uploadErrorMessage($upload['error']);
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
            $this->screeningRepository->createScreenings($movieId, $screeningResult['slots']);
        } catch (Exception $e) {
            $this->deletePosterFile($filename);
            return ['success' => false, 'errors' => ['Could not save movie. Please try again.']];
        }

        $showtimeCount = count($screeningResult['slots']);

        return [
            'success' => true,
            'message' => "Movie \"{$title}\" added successfully with {$showtimeCount} showtime(s).",
        ];
    }

    private function getDefaultScreenings(): array {
        return [
            ['date' => date('Y-m-d', strtotime('+1 day')), 'time' => '14:00'],
            ['date' => date('Y-m-d', strtotime('+1 day')), 'time' => '20:00'],
            ['date' => date('Y-m-d', strtotime('+2 days')), 'time' => '17:30'],
            ['date' => date('Y-m-d', strtotime('+3 days')), 'time' => '22:30'],
        ];
    }

    private function parseScreenings($raw): array {
        if (!is_array($raw)) {
            return ['slots' => [], 'errors' => ['Add at least one showtime.']];
        }

        $slots = [];
        $errors = [];
        $seen = [];

        foreach ($raw as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $date = trim($row['date'] ?? '');
            $time = trim($row['time'] ?? '');

            if ($date === '' && $time === '') {
                continue;
            }

            $rowNum = (int) $index + 1;

            if ($date === '') {
                $errors[] = "Showtime #{$rowNum}: date is required.";
                continue;
            }
            if ($time === '') {
                $errors[] = "Showtime #{$rowNum}: time is required.";
                continue;
            }

            $parsedDate = DateTime::createFromFormat('Y-m-d', $date);
            if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
                $errors[] = "Showtime #{$rowNum}: invalid date.";
                continue;
            }

            $parsedTime = DateTime::createFromFormat('H:i', $time);
            if (!$parsedTime || $parsedTime->format('H:i') !== $time) {
                $errors[] = "Showtime #{$rowNum}: invalid time (use HH:MM).";
                continue;
            }

            $key = $date . '|' . $time;
            if (isset($seen[$key])) {
                $errors[] = "Showtime #{$rowNum}: duplicate date and time.";
                continue;
            }
            $seen[$key] = true;

            $slots[] = ['date' => $date, 'time' => $time];
        }

        if (empty($slots) && empty($errors)) {
            $errors[] = 'Add at least one showtime.';
        }

        return ['slots' => $slots, 'errors' => $errors];
    }

    private function uploadErrorMessage(int $code): string {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Image is too large. Maximum size is 5 MB.',
            UPLOAD_ERR_PARTIAL => 'Image upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'Server could not save the image. Contact administrator.',
            default => 'Failed to upload image. Please try again.',
        };
    }

    private function savePosterUpload(array $file): string {
        if (($file['size'] ?? 0) <= 0) {
            throw new RuntimeException('Uploaded file is empty.');
        }

        if ($file['size'] > self::MAX_POSTER_BYTES) {
            throw new RuntimeException('Image must be smaller than 5 MB.');
        }

        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new RuntimeException('Uploaded file is not a valid image.');
        }

        $mime = $imageInfo['mime'] ?? '';
        if ($mime === '' && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']) ?: '';
            finfo_close($finfo);
        }

        if (!isset(self::ALLOWED_MIME[$mime])) {
            throw new RuntimeException('Only JPG, PNG and WebP images are allowed.');
        }

        $uploadDir = $this->getPosterUploadDir();

        $extension = self::ALLOWED_MIME[$mime];
        $filename = 'movie_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new RuntimeException('Could not save uploaded image.');
        }

        return $filename;
    }

    private function getPosterUploadDir(): string {
        $dir = realpath(__DIR__ . '/../../public/img');
        if ($dir === false) {
            $dir = __DIR__ . '/../../public/img';
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException('Upload directory does not exist.');
            }
            $dir = realpath($dir) ?: $dir;
        }

        if (!is_writable($dir)) {
            throw new RuntimeException('Upload directory is not writable.');
        }

        return $dir;
    }

    private function deletePosterFile(string $filename): void {
        $basename = basename($filename);
        try {
            $path = $this->getPosterUploadDir();
        } catch (RuntimeException $e) {
            return;
        }
        $file = $path . DIRECTORY_SEPARATOR . $basename;
        if (is_file($file)) {
            unlink($file);
        }
    }
}
