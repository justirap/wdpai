<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/Reservation.php';

class ReservationRepository extends Repository {
    private static $instance;

    private function __construct() {
        parent::__construct();
    }

    public static function getInstance(): ReservationRepository {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addReservation(int $userId, int $movieId, string $seat): void {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO reservations (user_id, movie_id, seat_number)
            VALUES (?, ?, ?)
        ');
        $stmt->execute([$userId, $movieId, $seat]);
    }
}