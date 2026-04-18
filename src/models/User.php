<?php
class User {
    private $id;
    private $email;
    private $password;
    private $username;
    private $role;

    public function __construct($email, $password, $username, $role = 'user', $id = null) {
        $this->email = $email;
        $this->password = $password;
        $this->username = $username;
        $this->role = $role;
        $this->id = $id;
    }

    public function getId() { return $this->id; }
    public function getEmail() { return $this->email; }
    public function getPassword() { return $this->password; }
    public function getUsername() { return $this->username; }
    public function getRole() { return $this->role; }
    
    public function isAdmin(): bool {
        return $this->role === 'admin';
    }
}