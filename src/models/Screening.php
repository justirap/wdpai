<?php

class Screening {
    private $id;
    private $movieId;
    private $showDate;
    private $showTime;
    private $hallNumber;
    private $format;

    public function __construct(
        int $movieId,
        string $showDate,
        string $showTime,
        int $hallNumber,
        string $format = 'Digital',
        ?int $id = null
    ) {
        $this->movieId = $movieId;
        $this->showDate = $showDate;
        $this->showTime = $showTime;
        $this->hallNumber = $hallNumber;
        $this->format = $format;
        $this->id = $id;
    }

    public function getId(): ?int { return $this->id; }
    public function getMovieId(): int { return $this->movieId; }
    public function getShowDate(): string { return $this->showDate; }
    public function getShowTime(): string { return $this->showTime; }
    public function getHallNumber(): int { return $this->hallNumber; }
    public function getFormat(): string { return $this->format; }

    public function getSessionLabel(): string {
        $time = date('g:i A', strtotime($this->showTime));
        return "Hall {$this->hallNumber} • {$this->format} • {$time}";
    }

    public function getDateTimeLabel(): string {
        $date = date('M j, Y', strtotime($this->showDate));
        $time = date('g:i A', strtotime($this->showTime));
        return "{$date} • {$time}";
    }
}
