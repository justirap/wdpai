<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/UserBooking.php';

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

    public function getUserBookings(int $userId): array {
        $stmt = $this->database->connect()->prepare('
            SELECT
                screening_id,
                movie_id,
                title,
                image,
                duration,
                show_date,
                show_time,
                hall_number,
                format,
                seats,
                booked_at
            FROM v_user_tickets
            WHERE user_id = :user_id
            ORDER BY show_date ASC, show_time ASC
        ');
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $bookings = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $seats = array_map('trim', explode(',', $row['seats']));
            $bookings[] = new UserBooking(
                (int) $row['screening_id'],
                (int) $row['movie_id'],
                $row['title'],
                $row['image'],
                (int) $row['duration'],
                $row['show_date'],
                $row['show_time'],
                (int) $row['hall_number'],
                $row['format'],
                $seats,
                $row['booked_at']
            );
        }
        return $bookings;
    }

    public function getOccupiedSeats(int $screeningId): array {
        $stmt = $this->database->connect()->prepare('
            SELECT seat_number FROM reservations WHERE screening_id = :screening_id
        ');
        $stmt->bindValue(':screening_id', $screeningId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * @param string[] $seats
     * @return string[] seats that could not be booked (already taken)
     */
    public function createReservations(int $userId, int $screeningId, array $seats): array {
        $failed = [];
        $pdo = $this->database->connect();

        try {
            $pdo->beginTransaction();

            $check = $pdo->prepare('
                SELECT fn_is_seat_available(:screening_id, :seat_number) AS available
            ');
            $insert = $pdo->prepare('
                INSERT INTO reservations (user_id, screening_id, seat_number)
                VALUES (:user_id, :screening_id, :seat_number)
            ');

            foreach ($seats as $seat) {
                $check->execute([
                    ':screening_id' => $screeningId,
                    ':seat_number' => $seat,
                ]);
                $available = filter_var(
                    $check->fetchColumn(),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                );
                if ($available !== true) {
                    $failed[] = $seat;
                }
            }

            if (!empty($failed)) {
                $pdo->rollBack();
                return $failed;
            }

            foreach ($seats as $seat) {
                try {
                    $insert->execute([
                        ':user_id' => $userId,
                        ':screening_id' => $screeningId,
                        ':seat_number' => $seat,
                    ]);
                } catch (PDOException $e) {
                    $sqlState = $e->errorInfo[0] ?? '';
                    if ($sqlState === '23505') {
                        $failed[] = $seat;
                    } else {
                        throw $e;
                    }
                }
            }

            if (!empty($failed)) {
                $pdo->rollBack();
                return $failed;
            }

            $pdo->commit();
            return [];
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
