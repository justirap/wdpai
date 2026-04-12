<?php

require_once 'AppController.php';

class SecurityController extends AppController {

     public function login() {
        // TODO sprawdzeie czy user istnieje

        if ($this->isPost()) {
            // return $this->render("dashboard");

            var_dump($_POST);
            //

            $url = "http://$_SERVER[HTTP_HOST]";
            header("Location: {$url}/dashboard");
        }

        return $this->render("login");
    }
    public function register() {


       $userRepository = new UsersRepository();


       if ($this->isPost()) {
               $email = trim($_POST['email'] ?? '');
               $password = $_POST['password'] ?? '';
               $password2 = $_POST['password2'] ?? '';
               $username = $_POST['username'] ?? '';


               if (empty($email) || empty($password) || empty($username)) {
                   return $this->render('register', ['messages' => 'Fill all fields']);
               }


               // TODO porównać czy hasło1 równe z hasło2


               $user = $userRepository->getUserByEmail($email);
               if($user) {
                   return $this->render("register", ["messages" => "User exists"]);
               }


               $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
               $userRepository->createUser($email, $hashedPassword, $username);


               $url = "http://$_SERVER[HTTP_HOST]";
               header("Location: {$url}/login");
               return;
       }


       return $this->render("register");
   }

}
