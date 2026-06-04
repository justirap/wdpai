<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/User.php';

class UsersRepository extends Repository {
    private static $instance;

    private function __construct() {
        parent::__construct();
    }

    public static function getInstance(): UsersRepository {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getUserByEmail(string $email): ?User {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM users WHERE email = :email
        ');
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return null;

        return new User(
            $user['email'],
            $user['password'],
            $user['username'],
            $user['role'], 
            $user['id']
        );
    }

    public function deleteUser(int $id): bool {
        $stmt = $this->database->connect()->prepare('
            DELETE FROM users WHERE id = :id AND role != \'admin\'
        ');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function createUser(string $email, string $password, string $username) {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO users (username, email, password, role)
            VALUES (?, ?, ?, ?)
        ');

        $stmt->execute([
            $username,
            $email,
            $password,
            'user' 
        ]);
    }
}