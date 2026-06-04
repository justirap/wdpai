<?php

require_once 'AppController.php';
require_once __DIR__.'/../repositories/MovieRepository.php';

class DashboardController extends AppController {

    private $movieRepository;

    public function __construct() {
        $this->movieRepository = MovieRepository::getInstance();
    }

  public function index() {
        $this->requireLogin();

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $category = isset($_GET['category']) ? trim($_GET['category']) : 'All Movies';
        
        $limit = 8;

        $movies = $this->movieRepository->getMovies($page, $limit, $search, $category);
        
        $totalMovies = $this->movieRepository->getTotalMoviesCount($search, $category);
        $totalPages = ceil($totalMovies / $limit);

        return $this->render('dashboard', [
            'movies' => $movies,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
            'currentCategory' => $category
        ]);
    }

}