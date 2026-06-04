<?php

class UserBooking {
    private $screeningId;
    private $movieId;
    private $title;
    private $image;
    private $duration;
    private $showDate;
    private $showTime;
    private $hallNumber;
    private $format;
    private $seats;
    private $bookedAt;

    public function __construct(
        int $screeningId,
        int $movieId,
        string $title,
        string $image,
        int $duration,
        string $showDate,
        string $showTime,
        int $hallNumber,
        string $format,
        array $seats,
        string $bookedAt
    ) {
        $this->screeningId = $screeningId;
        $this->movieId = $movieId;
        $this->title = $title;
        $this->image = $image;
        $this->duration = $duration;
        $this->showDate = $showDate;
        $this->showTime = $showTime;
        $this->hallNumber = $hallNumber;
        $this->format = $format;
        $this->seats = $seats;
        $this->bookedAt = $bookedAt;
    }

    public function getScreeningId(): int { return $this->screeningId; }
    public function getMovieId(): int { return $this->movieId; }
    public function getTitle(): string { return $this->title; }
    public function getImage(): string { return $this->image; }
    public function getDuration(): int { return $this->duration; }
    public function getShowDate(): string { return $this->showDate; }
    public function getShowTime(): string { return $this->showTime; }
    public function getHallNumber(): int { return $this->hallNumber; }
    public function getFormat(): string { return $this->format; }
    public function getSeats(): array { return $this->seats; }
    public function getBookedAt(): string { return $this->bookedAt; }

    public function getSessionLabel(): string {
        $time = date('g:i A', strtotime($this->showTime));
        return "Hall {$this->hallNumber} • {$this->format} • {$time}";
    }

    public function getDateTimeLabel(): string {
        $date = date('M j, Y', strtotime($this->showDate));
        $time = date('g:i A', strtotime($this->showTime));
        return "{$date} • {$time}";
    }

    public function getSeatsLabel(): string {
        return implode(', ', $this->seats);
    }

    public function getTicketCount(): int {
        return count($this->seats);
    }
}
