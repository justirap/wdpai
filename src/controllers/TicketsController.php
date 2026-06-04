<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/ReservationRepository.php';

class TicketsController extends AppController {
    private const TICKET_PRICE = 12.00;
    private const BOOKING_FEE = 2.50;

    private $reservationRepository;

    public function __construct() {
        $this->reservationRepository = ReservationRepository::getInstance();
    }

    public function index() {
        $this->requireLogin();

        $bookings = $this->reservationRepository->getUserBookings((int) $_SESSION['user_id']);

        return $this->render('tickets', [
            'bookings' => $bookings,
            'ticketPrice' => self::TICKET_PRICE,
            'bookingFee' => self::BOOKING_FEE,
        ]);
    }
}
