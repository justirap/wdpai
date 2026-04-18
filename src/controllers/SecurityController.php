<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/UsersRepository.php';

class SecurityController extends AppController {

    private $userRepository;

    public function __construct() {
        $this->userRepository = UsersRepository::getInstance();
    }

    public function login() {
        if (!$this->isPost()) {
            return $this->render("login");
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = $this->userRepository->getUserByEmail($email);

        if (!$user) {
            return $this->render("login", ["messages" => ["User not found!"]]);
        }


        if (!password_verify($password, $user->getPassword())) {
            return $this->render("login", ["messages" => ["Wrong password!"]]);
        }

        session_regenerate_id(true);
    
     
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['user_role'] = $user->getRole(); 

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/dashboard");
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
            return $this->render("register");
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $username = $_POST['username'] ?? '';

        if (empty($email) || empty($password) || empty($username)) {
            return $this->render('register', ['messages' => ['Fill all fields']]);
        }

        if ($password !== $password2) {
            return $this->render('register', ['messages' => ['Passwords do not match!']]);
        }

        $user = $this->userRepository->getUserByEmail($email);
        
   
        if($user) {
            return $this->render("register", ["messages" => ["User already exists!"]]);
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $this->userRepository->createUser($email, $hashedPassword, $username);

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
        exit();
    }
}