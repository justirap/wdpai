<?php

class Reservation {
    private $id;
    private $user_id;
    private $movie_id;
    private $seat_number;
    private $created_at;

    public function __construct($user_id, $movie_id, $seat_number, $id = null, $created_at = null) {
        $this->user_id = $user_id;
        $this->movie_id = $movie_id;
        $this->seat_number = $seat_number;
        $this->id = $id;
        $this->created_at = $created_at;
    }

    public function getId() { return $this->id; }
    public function getUserId() { return $this->user_id; }
    public function getMovieId() { return $this->movie_id; }
    public function getSeatNumber() { return $this->seat_number; }
    public function getCreatedAt() { return $this->created_at; }
}