DROP TABLE IF EXISTS reservations CASCADE;
DROP TABLE IF EXISTS movie_categories CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS movies CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- 1. TABELA UŻYTKOWNIKÓW
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user'
);

-- 2. TABELA FILMÓW
CREATE TABLE movies (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255), 
    duration INT
);

-- 3. TABELA KATEGORII (GATUNKÓW)
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL
);

-- 4. TABELA POŚREDNICZĄCA (RELACJA FILM <-> KATEGORIA)
CREATE TABLE movie_categories (
    movie_id INT REFERENCES movies(id) ON DELETE CASCADE,
    category_id INT REFERENCES categories(id) ON DELETE CASCADE,
    PRIMARY KEY (movie_id, category_id)
);

-- 5. TABELA REZERWACJI
CREATE TABLE reservations (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    movie_id INT REFERENCES movies(id) ON DELETE CASCADE,
    seat_number VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================================
-- INSERTY DANYCH
-- =========================================================================

-- Użytkownicy (Hasło dla obu kont: "password123")
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@cinema.com', '$2y$10$8uN6Nf8lYhX.GgGv.P8QOuC2mH2zZ5H0xM5L7U7v0L5P1i0e6L2mO', 'admin'),
('jan_kowalski', 'user@gmail.com', '$2y$10$8uN6Nf8lYhX.GgGv.P8QOuC2mH2zZ5H0xM5L7U7v0L5P1i0e6L2mO', 'user');

-- Kategorie filmowe
INSERT INTO categories (name) VALUES 
('Sci-Fi'), ('Action'), ('Comedy'), ('Drama'), ('Horror'), ('Thriller'), ('Documentary');

INSERT INTO movies (id, title, description, image, duration) VALUES 
-- STRONA 1
(1, 'Inception', 'Złodziej kradnie tajemnice firmowe poprzez wykorzystanie technologii dzielenia się snami.', '1.png', 148),
(2, 'The Dark Knight', 'Batman podejmuje walkę z Jokerem, który chce pogrążyć Gotham City w anarchii.', '2.png', 152),
(3, 'Interstellar', 'Grupa odkrywców podróżuje przez tunel czasoprzestrzenny w celu ratowania ludzkości.', '3.png', 169),
(4, 'Parasite', 'Głód i bezrobocie popychają członków biednej rodziny do infiltracji zamożnego domu.', '4.png', 132),
(5, 'Dune: Part Two', 'Paul Atryda jednoczy się z Chani i Fremenami, aby zemścić się na spiskowcach.', '5.png', 166),
(6, 'Oppenheimer', 'Historia amerykańskiego naukowca J. Roberta Oppenheimera i jego roli w stworzeniu bomby atomowej.', '6.png', 180),
(7, 'Everything Everywhere All at Once', 'Egzystencjalny kryzys gospodyni domowej przeradza się w niesamowitą walkę w multiwersum.', '7.png', 139),
(8, 'The Batman', 'Mroczny Rycerz tropi seryjnego mordercę Człowieka-Zagadkę w skorumpowanym Gotham.', '8.png', 176),

-- STRONA 2
(9, 'Biedne Istoty', 'Niesamowita opowieść o ewolucji Belli Baxter, młodej kobiety przywróconej do życia.', '9.png', 141),
(10, 'Blade Runner 2049', 'Nowy oficer policji Los Angeles odkrywa skrywaną przez lata tajemnicę.', '10.png', 164),
(11, 'Joker', 'Strudzony życiem komik Arthur Fleck popada w obłęd i staje się psychopatycznym mordercą.', '11.png', 122),
(12, 'Mad Max: Fury Road', 'W postapokaliptycznym świecie Max łączy siły z tajemniczą Furiosą.', '12.png', 120),
(13, 'Zodiac', 'Seryjny morderca terroryzuje San Francisco, wysyłając listy z szyframi do gazet.', '13.png', 157),
(14, 'Shutter Island', 'Szeryf federalny bada sprawę zniknięcia pacjentki ze szpitala dla psychicznie chorych.', '14.png', 138),
(15, 'Whiplash', 'Młody perkusista dostaje się do elitarnej orkiestry prowadzonej przez bezwzględnego nauczyciela.', '15.png', 106),
(16, 'Get Out', 'Młody Afroamerykanin odwiedza posiadłość rodziców swojej białej dziewczyny.', '16.png', 104),

-- STRONA 3
(17, 'The Matrix', 'Haker komputerowy dowiaduje się od tajemniczych buntowników o prawdziwej naturze jego rzeczywistości.', '17.png', 136),
(18, 'Gladiator', 'Generał rzymski staje się gladiatorem, by pomścić śmierć swojej rodziny i cesarza.', '18.png', 155),
(19, 'Se7en', 'Dwóch detektywów poluje na seryjnego mordercę, który wybiera ofiary według siedmiu grzechów głównych.', '19.png', 127),
(20, 'Spider-Man: Into the Spider-Verse', 'Nastoletni Miles Morales staje się nowym Spider-Manem i odkrywa inne wymiary.', '20.png', 117),
(21, 'The Conjuring', 'Badacze zjawisk paranormalnych pomagają rodzinie terroryzowanej przez mroczną siłę.', '21.png', 112),
(22, 'Knives Out', 'Detektyw bada sprawę śmierci ekscentrycznego pisarza, podejrzewając jego rodzinę.', '22.png', 130),
(23, 'Tenet', 'Uzbrojony tylko w jedno słowo, agent walczy o przetrwanie całego świata.', '23.png', 150),
(24, 'Fight Club', 'Cierpiący na bezsenność mężczyzna zakłada podziemny klub walki z tajemniczym sprzedawcą mydła.', '24.png', 139);

SELECT setval('movies_id_seq', 24);


INSERT INTO movie_categories (movie_id, category_id) VALUES 
(1, 1), (1, 6), -- Inception: Sci-Fi, Thriller
(2, 2), (2, 6), -- The Dark Knight: Action, Thriller
(3, 1), (3, 4), -- Interstellar: Sci-Fi, Drama
(4, 4), (4, 6), -- Parasite: Drama, Thriller
(5, 1), (5, 2), -- Dune 2: Sci-Fi, Action
(6, 4),          -- Oppenheimer: Drama
(7, 1), (7, 3), -- Everything Everywhere: Sci-Fi, Comedy
(8, 2), (8, 6), -- The Batman: Action, Thriller
(9, 3), (9, 4), -- Biedne Istoty: Comedy, Drama
(10, 1),         -- Blade Runner 2049: Sci-Fi
(11, 4), (11, 6),-- Joker: Drama, Thriller
(12, 2),         -- Mad Max: Action
(13, 4), (13, 6),-- Zodiac: Drama, Thriller
(14, 6),         -- Shutter Island: Thriller
(15, 4),         -- Whiplash: Drama
(16, 5), (16, 6),-- Get Out: Horror, Thriller
(17, 1), (17, 2),-- The Matrix: Sci-Fi, Action
(18, 2), (18, 4),-- Gladiator: Action, Drama
(19, 6),         -- Se7en: Thriller
(20, 1), (20, 2),-- Spider-Verse: Sci-Fi, Action
(21, 5),         -- The Conjuring: Horror
(22, 3), (22, 6),-- Knives Out: Comedy, Thriller
(23, 1), (23, 2),-- Tenet: Sci-Fi, Action
(24, 4);         -- Fight Club: Drama