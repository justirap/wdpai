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

    public function getMovieById(int $id): ?Movie {
        $stmt = $this->database->connect()->prepare('
            SELECT m.*, STRING_AGG(c.name, \', \') AS categories
            FROM movies m
            LEFT JOIN movie_categories mc ON m.id = mc.movie_id
            LEFT JOIN categories c ON mc.category_id = c.id
            WHERE m.id = :id
            GROUP BY m.id
        ');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$movie) {
            return null;
        }

        return new Movie(
            $movie['title'],
            $movie['description'],
            $movie['image'],
            $movie['duration'],
            $movie['id'],
            $movie['categories'] ?? 'Cinema'
        );
    }

public function getMovies(int $page = 1, int $limit = 8, string $search = '', string $category = ''): array {
        $result = [];
        
        $offset = ($page - 1) * $limit;

        $query = '
            SELECT m.*, STRING_AGG(c.name, \', \') AS categories 
            FROM movies m
            LEFT JOIN movie_categories mc ON m.id = mc.movie_id
            LEFT JOIN categories c ON mc.category_id = c.id
            WHERE 1=1
        ';

        $params = [];

        if (!empty($search)) {
            $query .= ' AND LOWER(m.title) LIKE LOWER(:search)';
            $params[':search'] = '%' . $search . '%';
        }

        if (!empty($category) && $category !== 'All Movies') {
            $query .= ' AND m.id IN (
                SELECT movie_id FROM movie_categories mc2 
                JOIN categories c2 ON mc2.category_id = c2.id 
                WHERE c2.name = :category
            )';
            $params[':category'] = $category;
        }

        $query .= ' GROUP BY m.id ORDER BY m.id ASC LIMIT :limit OFFSET :offset';

        $stmt = $this->database->connect()->prepare($query);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($movies as $movie) {
            $result[] = new Movie(
                $movie['title'],
                $movie['description'],
                $movie['image'],
                $movie['duration'],
                $movie['id'],
                $movie['categories'] ?? 'Cinema'
            );
        }
        return $result;
    }

    public function getTotalMoviesCount(string $search = '', string $category = ''): int {
        $query = 'SELECT COUNT(DISTINCT m.id) FROM movies m
                  LEFT JOIN movie_categories mc ON m.id = mc.movie_id
                  LEFT JOIN categories c ON mc.category_id = c.id
                  WHERE 1=1';
        
        $params = [];
        if (!empty($search)) {
            $query .= ' AND LOWER(m.title) LIKE LOWER(:search)';
            $params[':search'] = '%' . $search . '%';
        }
        if (!empty($category) && $category !== 'All Movies') {
            $query .= ' AND c.name = :category';
            $params[':category'] = $category;
        }

        $stmt = $this->database->connect()->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getAllMoviesAdmin(): array {
        $stmt = $this->database->connect()->query('
            SELECT m.*, STRING_AGG(c.name, \', \' ORDER BY c.name) AS categories
            FROM movies m
            LEFT JOIN movie_categories mc ON m.id = mc.movie_id
            LEFT JOIN categories c ON mc.category_id = c.id
            GROUP BY m.id
            ORDER BY m.id DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getAllCategories(): array {
        $stmt = $this->database->connect()->query('
            SELECT id, name FROM categories ORDER BY name ASC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createMovie(string $title, string $description, string $image, int $duration): int {
        $stmt = $this->database->connect()->prepare('
            INSERT INTO movies (title, description, image, duration)
            VALUES (:title, :description, :image, :duration)
            RETURNING id
        ');
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':image' => $image,
            ':duration' => $duration,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function attachCategories(int $movieId, array $categoryIds): void {
        if (empty($categoryIds)) {
            return;
        }
        $pdo = $this->database->connect();
        $stmt = $pdo->prepare('
            INSERT INTO movie_categories (movie_id, category_id) VALUES (:movie_id, :category_id)
        ');
        foreach ($categoryIds as $categoryId) {
            $stmt->execute([
                ':movie_id' => $movieId,
                ':category_id' => (int) $categoryId,
            ]);
        }
    }
}