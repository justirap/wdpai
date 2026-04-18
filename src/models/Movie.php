<?php
class Movie {
    private $id;
    private $title;
    private $description;
    private $image;
    private $duration;

    public function __construct($title, $description, $image, $duration, $id = null) {
        $this->title = $title;
        $this->description = $description;
        $this->image = $image;
        $this->duration = $duration;
        $this->id = $id;
    }

    public function getId() { return $this->id; }
    public function getTitle() { return $this->title; }
    public function getDescription() { return $this->description; }
    public function getImage() { return $this->image; }
    public function getDuration() { return $this->duration; }
}