<?php

require_once 'Repository.php';

class AdminRepository extends Repository {
    private static $instance;
    private const TICKET_PRICE = 12.00;
    private const BOOKING_FEE = 2.50;
    private const SEATS_PER_SCREENING = 32;

    private function __construct() {
        parent::__construct();
    }

    public static function getInstance(): AdminRepository {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getDashboardStats(): array {
        $pdo = $this->database->connect();

        $ticketsSold = (int) $pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn();

        $bookingGroups = (int) $pdo->query('
            SELECT COUNT(*) FROM (
                SELECT user_id, screening_id FROM reservations GROUP BY user_id, screening_id
            ) g
        ')->fetchColumn();

        $totalRevenue = ($ticketsSold * self::TICKET_PRICE) + ($bookingGroups * self::BOOKING_FEE);

        $activeUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();

        $screeningCount = (int) $pdo->query('SELECT COUNT(*) FROM screenings')->fetchColumn();
        $maxSeats = max(1, $screeningCount * self::SEATS_PER_SCREENING);
        $avgOccupancy = round(($ticketsSold / $maxSeats) * 100, 1);

        return [
            'totalRevenue' => $totalRevenue,
            'ticketsSold' => $ticketsSold,
            'activeUsers' => $activeUsers,
            'avgOccupancy' => min(100, $avgOccupancy),
        ];
    }

    public function getRecentReservations(int $limit = 8): array {
        $stmt = $this->database->connect()->prepare('
            SELECT
                u.username,
                m.title AS movie_title,
                s.show_date,
                s.show_time,
                STRING_AGG(r.seat_number, \', \' ORDER BY r.seat_number) AS seats,
                MAX(r.created_at) AS booked_at
            FROM reservations r
            INNER JOIN users u ON u.id = r.user_id
            INNER JOIN screenings s ON s.id = r.screening_id
            INNER JOIN movies m ON m.id = s.movie_id
            GROUP BY u.username, m.title, s.show_date, s.show_time, r.user_id, r.screening_id
            ORDER BY booked_at DESC
            LIMIT :limit
        ');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getAllUsers(): array {
        $stmt = $this->database->connect()->query('
            SELECT id, username, email, role FROM users ORDER BY id ASC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
