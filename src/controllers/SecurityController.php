<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/UsersRepository.php';
require_once __DIR__.'/../services/LoginRateLimiter.php';
require_once __DIR__.'/../services/InputLimits.php';
require_once __DIR__.'/../services/PasswordValidator.php';

class SecurityController extends AppController {
    private const INVALID_LOGIN_MESSAGE = 'Invalid email or password.';
    private const DUMMY_PASSWORD_HASH = '$2y$10$Rd1LPARvIxew4g1fgEVYcODtJAgZ7We10k4Pka5x2cn9msjALrDN.';

    private $userRepository;
    private LoginRateLimiter $loginRateLimiter;

    public function __construct() {
        $this->userRepository = UsersRepository::getInstance();
        $this->loginRateLimiter = new LoginRateLimiter();
    }

    public function login() {
        if (isset($_SESSION['user_id'])) {
            $url = "http://$_SERVER[HTTP_HOST]";
            $destination = ($_SESSION['user_role'] ?? '') === 'admin' ? '/admin' : '/dashboard';
            header("Location: {$url}{$destination}");
            exit();
        }

        if (!$this->isPost()) {
            if ($this->loginRateLimiter->isLocked()) {
                return $this->render('login', $this->authViewData($this->lockedLoginViewData()));
            }

            return $this->render('login', $this->authViewData());
        }

        if (!$this->validateCsrfToken()) {
            http_response_code(403);
            return $this->render('login', $this->authViewData([
                'messages' => ['Invalid request. Please try again.'],
            ]));
        }

        if ($this->loginRateLimiter->isLocked()) {
            http_response_code(429);
            return $this->render('login', $this->authViewData($this->lockedLoginViewData()));
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $lengthError = $this->validateLoginInputLengths($email, $password);
        if ($lengthError !== null) {
            return $this->render('login', $this->authViewData(['messages' => [$lengthError]]));
        }

        $user = $this->userRepository->getUserByEmail($email);
        $storedHash = $user !== null ? $user->getPassword() : self::DUMMY_PASSWORD_HASH;
        $passwordValid = password_verify($password, $storedHash);
        $loginFailed = $user === null || !$passwordValid;

        if ($loginFailed) {
            $this->loginRateLimiter->recordFailure();

            if ($this->loginRateLimiter->isLocked()) {
                http_response_code(429);
                return $this->render('login', $this->authViewData($this->lockedLoginViewData()));
            }

            $message = self::INVALID_LOGIN_MESSAGE;
            $remaining = $this->loginRateLimiter->getRemainingAttempts();
            if ($remaining <= 2) {
                $message .= " {$remaining} attempt(s) remaining.";
            }

            return $this->render('login', $this->authViewData(['messages' => [$message]]));
        }

        $this->loginRateLimiter->clear();

        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['user_username'] = $user->getUsername();
        $_SESSION['user_role'] = $user->getRole();

        $url = "http://$_SERVER[HTTP_HOST]";
        $destination = $user->isAdmin() ? '/admin' : '/dashboard';
        header("Location: {$url}{$destination}");
        exit();
    }

    public function logout() {
        session_destroy();
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
        exit();
    }

    public function register() {
        if (!$this->isPost()) {
            return $this->render('register', $this->authViewData());
        }

        if (!$this->validateCsrfToken()) {
            http_response_code(403);
            return $this->render('register', $this->authViewData([
                'messages' => ['Invalid request. Please try again.'],
            ]));
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $username = trim($_POST['username'] ?? '');

        $lengthError = $this->validateRegisterInputLengths($email, $password, $password2, $username);
        if ($lengthError !== null) {
            return $this->render('register', $this->authViewData(['messages' => [$lengthError]]));
        }

        if ($email === '' || $password === '' || $username === '') {
            return $this->render('register', $this->authViewData(['messages' => ['Fill all fields']]));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('register', $this->authViewData(['messages' => ['Please enter a valid email address.']]));
        }

        $passwordError = PasswordValidator::validate($password);
        if ($passwordError !== null) {
            return $this->render('register', $this->authViewData(['messages' => [$passwordError]]));
        }

        if ($password !== $password2) {
            return $this->render('register', $this->authViewData(['messages' => ['Passwords do not match!']]));
        }

        $user = $this->userRepository->getUserByEmail($email);

        if ($user) {
            return $this->render('register', $this->authViewData(['messages' => ['User already exists!']]));
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $this->userRepository->createUser($email, $hashedPassword, $username);

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
        exit();
    }

    private function validateLoginInputLengths(string $email, string $password): ?string {
        if (InputLimits::emailTooLong($email)) {
            return 'Email is too long.';
        }

        if (InputLimits::passwordTooLong($password)) {
            return 'Password is too long.';
        }

        return null;
    }

    private function validateRegisterInputLengths(
        string $email,
        string $password,
        string $password2,
        string $username
    ): ?string {
        if (InputLimits::usernameTooLong($username)) {
            return 'Username is too long.';
        }

        if (InputLimits::emailTooLong($email)) {
            return 'Email is too long.';
        }

        if (InputLimits::passwordTooLong($password) || InputLimits::passwordTooLong($password2)) {
            return 'Password is too long.';
        }

        return null;
    }

    private function lockedLoginViewData(): array {
        return [
            'messages' => [$this->loginRateLimiter->formatLockMessage()],
            'loginLocked' => true,
        ];
    }
}

