<?php

require_once 'Repository.php';
require_once __DIR__.'/../models/Movie.php';

class MovieRepository extends Repository {
    private static $instance;

    private function __construct() {
        parent::__construct();
    }

    public static function getInstance(): MovieRepository {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getMovies(): array {
        $result = [];
        $stmt = $this->database->connect()->prepare('SELECT * FROM movies');
        $stmt->execute();
        $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($movies as $movie) {
            $result[] = new Movie(
                $movie['title'],
                $movie['description'],
                $movie['image'],
                $movie['duration'],
                $movie['id']
            );
        }
        return $result;
    }
}