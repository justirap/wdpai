<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/UsersRepository.php';
require_once __DIR__.'/../repositories/MovieRepository.php';
require_once __DIR__.'/../repositories/ReservationRepository.php';

class DashboardController extends AppController {

    private $movieRepository;
    private $reservationRepository;

    public function __construct() {
        $this->movieRepository = MovieRepository::getInstance();
        $this->reservationRepository = ReservationRepository::getInstance();
    }

    public function index() {
        $this->requireLogin();
        $movies = $this->movieRepository->getMovies();
        
        return $this->render('dashboard', ['movies' => $movies]);
    }

}