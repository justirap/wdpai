<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/Screening.php';

class ScreeningRepository extends Repository {
    private static $instance;

    private function __construct() {
        parent::__construct();
    }

    public static function getInstance(): ScreeningRepository {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getById(int $id): ?Screening {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM screenings WHERE id = :id
        ');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapRow($row);
    }

    public function getUpcomingByMovieId(int $movieId): array {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM screenings
            WHERE movie_id = :movie_id
              AND (show_date + show_time) >= CURRENT_TIMESTAMP
            ORDER BY show_date ASC, show_time ASC
        ');
        $stmt->bindValue(':movie_id', $movieId, PDO::PARAM_INT);
        $stmt->execute();

        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[] = $this->mapRow($row);
        }
        return $result;
    }

    public function belongsToMovie(int $screeningId, int $movieId): bool {
        $stmt = $this->database->connect()->prepare('
            SELECT 1 FROM screenings WHERE id = :id AND movie_id = :movie_id LIMIT 1
        ');
        $stmt->bindValue(':id', $screeningId, PDO::PARAM_INT);
        $stmt->bindValue(':movie_id', $movieId, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    public function createScreenings(int $movieId, array $slots): void {
        if (empty($slots)) {
            return;
        }

        $hall = ($movieId % 4) + 1;
        $format = $movieId % 4 === 0 ? 'IMAX 3D' : ($movieId % 4 === 2 ? '4DX' : 'Digital');

        $stmt = $this->database->connect()->prepare('
            INSERT INTO screenings (movie_id, show_date, show_time, hall_number, format)
            VALUES (:movie_id, :show_date, :show_time, :hall, :format)
        ');

        foreach ($slots as $slot) {
            $stmt->execute([
                ':movie_id' => $movieId,
                ':show_date' => $slot['date'],
                ':show_time' => $slot['time'],
                ':hall' => $hall,
                ':format' => $format,
            ]);
        }
    }

    private function mapRow(array $row): Screening {
        return new Screening(
            (int) $row['movie_id'],
            $row['show_date'],
            $row['show_time'],
            (int) $row['hall_number'],
            $row['format'],
            (int) $row['id']
        );
    }
}
