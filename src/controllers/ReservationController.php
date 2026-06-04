<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/MovieRepository.php';
require_once __DIR__.'/../repositories/ScreeningRepository.php';
require_once __DIR__.'/../repositories/ReservationRepository.php';

class ReservationController extends AppController {
    private const ROWS = ['A', 'B', 'C', 'D'];
    private const COLS = 8;
    private const AISLE_AFTER = 2;
    private const TICKET_PRICE = 12.00;
    private const BOOKING_FEE = 2.50;
    private const VALID_SEAT_PATTERN = '/^[A-D]([1-8])$/';

    private $movieRepository;
    private $screeningRepository;
    private $reservationRepository;

    public function __construct() {
        $this->movieRepository = MovieRepository::getInstance();
        $this->screeningRepository = ScreeningRepository::getInstance();
        $this->reservationRepository = ReservationRepository::getInstance();
    }

    public function index() {
        $this->requireLogin();

        if (isset($_GET['format']) && $_GET['format'] === 'json') {
            return $this->occupiedJson();
        }

        if ($this->isPost()) {
            return $this->confirm();
        }

        $screeningId = isset($_GET['screening']) ? (int) $_GET['screening'] : 0;
        if ($screeningId > 0) {
            return $this->showSeats($screeningId);
        }

        $movieId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($movieId <= 0) {
            $this->redirectDashboard();
        }

        return $this->showShowtimes($movieId);
    }

    private function showShowtimes(int $movieId) {
        $movie = $this->movieRepository->getMovieById($movieId);
        if (!$movie) {
            return $this->render('404');
        }

        $screenings = $this->screeningRepository->getUpcomingByMovieId($movieId);

        return $this->render('reservation-showtimes', [
            'movie' => $movie,
            'screenings' => $screenings,
        ]);
    }

    private function showSeats(int $screeningId, array $messages = []) {
        $screening = $this->screeningRepository->getById($screeningId);
        if (!$screening) {
            return $this->render('404');
        }

        $movie = $this->movieRepository->getMovieById($screening->getMovieId());
        if (!$movie) {
            return $this->render('404');
        }

        $occupied = $this->reservationRepository->getOccupiedSeats($screeningId);

        return $this->render('reservation', [
            'movie' => $movie,
            'screening' => $screening,
            'occupiedSeats' => $occupied,
            'rows' => self::ROWS,
            'cols' => self::COLS,
            'aisleAfter' => self::AISLE_AFTER,
            'ticketPrice' => self::TICKET_PRICE,
            'bookingFee' => self::BOOKING_FEE,
            'sessionLabel' => $screening->getSessionLabel(),
            'messages' => $messages,
        ]);
    }

    private function occupiedJson(): void {
        $screeningId = isset($_GET['screening']) ? (int) $_GET['screening'] : 0;
        if ($screeningId <= 0) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid screening id']);
            return;
        }

        $occupied = $this->reservationRepository->getOccupiedSeats($screeningId);
        header('Content-Type: application/json');
        echo json_encode(['occupied' => $occupied]);
    }

    private function confirm() {
        $screeningId = isset($_POST['screening_id']) ? (int) $_POST['screening_id'] : 0;
        $seats = $_POST['seats'] ?? [];

        if (!is_array($seats)) {
            $seats = [];
        }

        $seats = array_values(array_unique(array_map('strtoupper', array_map('trim', $seats))));
        $seats = array_filter($seats, fn($seat) => preg_match(self::VALID_SEAT_PATTERN, $seat));

        if ($screeningId <= 0 || empty($seats)) {
            return $this->showSeats(
                $screeningId,
                ['Please select at least one seat.']
            );
        }

        $screening = $this->screeningRepository->getById($screeningId);
        if (!$screening) {
            return $this->render('404');
        }

        $failed = $this->reservationRepository->createReservations(
            (int) $_SESSION['user_id'],
            $screeningId,
            $seats
        );

        if (!empty($failed)) {
            $labels = implode(', ', $failed);
            return $this->showSeats(
                $screeningId,
                ["These seats are no longer available: {$labels}. Please choose different seats."]
            );
        }

        $url = "http://$_SERVER[HTTP_HOST]/tickets";
        header("Location: {$url}");
        exit();
    }

    private function redirectDashboard(): void {
        $url = "http://$_SERVER[HTTP_HOST]/dashboard";
        header("Location: {$url}");
        exit();
    }
}
