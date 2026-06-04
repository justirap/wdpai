<?php

require_once 'Repository.php';

class ContactMessageRepository extends Repository {
    private static $instance;

    private function __construct() {
        parent::__construct();
    }

    public static function getInstance(): ContactMessageRepository {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function create(?int $userId, string $name, string $email, string $message): void {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO contact_messages (user_id, name, email, message)
            VALUES (:user_id, :name, :email, :message)
        ');
        $stmt->bindValue(':user_id', $userId, $userId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':message', $message, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function getAll(): array {
        $stmt = $this->database->connect()->query('
            SELECT cm.*, u.username AS account_username
            FROM contact_messages cm
            LEFT JOIN users u ON u.id = cm.user_id
            ORDER BY cm.created_at DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getUnreadCount(): int {
        $stmt = $this->database->connect()->query('
            SELECT COUNT(*) FROM contact_messages WHERE is_read = FALSE
        ');
        return (int) $stmt->fetchColumn();
    }

    public function markAsRead(int $id): void {
        $stmt = $this->database->connect()->prepare('
            UPDATE contact_messages SET is_read = TRUE WHERE id = :id
        ');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }
}
