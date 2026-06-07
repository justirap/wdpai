DROP VIEW IF EXISTS v_user_tickets CASCADE;
DROP FUNCTION IF EXISTS fn_is_seat_available(INT, VARCHAR) CASCADE;
DROP FUNCTION IF EXISTS trg_validate_seat_number() CASCADE;

DROP TABLE IF EXISTS contact_messages CASCADE;
DROP TABLE IF EXISTS reservations CASCADE;
DROP TABLE IF EXISTS screenings CASCADE;
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

-- 3. TABELA KATEGORII
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL
);

-- 4. TABELA POŚREDNICZĄCA (FILM <-> KATEGORIA)
CREATE TABLE movie_categories (
    movie_id INT REFERENCES movies(id) ON DELETE CASCADE,
    category_id INT REFERENCES categories(id) ON DELETE CASCADE,
    PRIMARY KEY (movie_id, category_id)
);

-- 5. SEANSE (data + godzina + sala)
CREATE TABLE screenings (
    id SERIAL PRIMARY KEY,
    movie_id INT NOT NULL REFERENCES movies(id) ON DELETE CASCADE,
    show_date DATE NOT NULL,
    show_time TIME NOT NULL,
    hall_number INT NOT NULL DEFAULT 1,
    format VARCHAR(50) NOT NULL DEFAULT 'Digital',
    UNIQUE (movie_id, show_date, show_time, hall_number)
);

-- 6. WIADOMOŚCI KONTAKTOWE
CREATE TABLE contact_messages (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE SET NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. REZERWACJE (per seans)
CREATE TABLE reservations (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    screening_id INT NOT NULL REFERENCES screenings(id) ON DELETE CASCADE,
    seat_number VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (screening_id, seat_number)
);

CREATE VIEW v_user_tickets AS
SELECT
    r.user_id,
    s.id AS screening_id,
    m.id AS movie_id,
    m.title,
    m.image,
    m.duration,
    s.show_date,
    s.show_time,
    s.hall_number,
    s.format,
    STRING_AGG(r.seat_number, ', ' ORDER BY r.seat_number) AS seats,
    MIN(r.created_at) AS booked_at
FROM reservations r
INNER JOIN screenings s ON s.id = r.screening_id
INNER JOIN movies m ON m.id = s.movie_id
GROUP BY r.user_id, s.id, m.id, m.title, m.image, m.duration,
         s.show_date, s.show_time, s.hall_number, s.format;

CREATE OR REPLACE FUNCTION fn_is_seat_available(p_screening_id INT, p_seat VARCHAR)
RETURNS BOOLEAN AS $$
    SELECT NOT EXISTS (
        SELECT 1
        FROM reservations
        WHERE screening_id = p_screening_id
          AND seat_number = p_seat
    );
$$ LANGUAGE sql STABLE;

CREATE OR REPLACE FUNCTION trg_validate_seat_number()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.seat_number !~ '^[A-D]([1-8])$' THEN
        RAISE EXCEPTION 'Invalid seat number: %', NEW.seat_number;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER reservations_validate_seat
BEFORE INSERT OR UPDATE ON reservations
FOR EACH ROW EXECUTE FUNCTION trg_validate_seat_number();

-- =========================================================================
-- DANE
-- =========================================================================

-- Hasło dla obu kont: password123 (hash wygenerowany przez password_hash)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@cinema.com', '$2y$10$Rd1LPARvIxew4g1fgEVYcODtJAgZ7We10k4Pka5x2cn9msjALrDN.', 'admin'),
('jan_kowalski', 'user@gmail.com', '$2y$10$Rd1LPARvIxew4g1fgEVYcODtJAgZ7We10k4Pka5x2cn9msjALrDN.', 'user');

INSERT INTO categories (name) VALUES
('Sci-Fi'), ('Action'), ('Comedy'), ('Drama'), ('Horror'), ('Thriller'), ('Documentary');

INSERT INTO movies (id, title, description, image, duration) VALUES
(1, 'Inception', 'Złodziej kradnie tajemnice firmowe poprzez wykorzystanie technologii dzielenia się snami.', '1.png', 148),
(2, 'The Dark Knight', 'Batman podejmuje walkę z Jokerem, który chce pogrążyć Gotham City w anarchii.', '2.png', 152),
(3, 'Interstellar', 'Grupa odkrywców podróżuje przez tunel czasoprzestrzenny w celu ratowania ludzkości.', '3.png', 169),
(4, 'Parasite', 'Głód i bezrobocie popychają członków biednej rodziny do infiltracji zamożnego domu.', '4.png', 132),
(5, 'Dune: Part Two', 'Paul Atryda jednoczy się z Chani i Fremenami, aby zemścić się na spiskowcach.', '5.png', 166),
(6, 'Oppenheimer', 'Historia amerykańskiego naukowca J. Roberta Oppenheimera i jego roli w stworzeniu bomby atomowej.', '6.png', 180),
(7, 'Everything Everywhere All at Once', 'Egzystencjalny kryzys gospodyni domowej przeradza się w niesamowitą walkę w multiwersum.', '7.png', 139),
(8, 'The Batman', 'Mroczny Rycerz tropi seryjnego mordercę Człowieka-Zagadkę w skorumpowanym Gotham.', '8.png', 176),
(9, 'Biedne Istoty', 'Niesamowita opowieść o ewolucji Belli Baxter, młodej kobiety przywróconej do życia.', '9.png', 141),
(10, 'Blade Runner 2049', 'Nowy oficer policji Los Angeles odkrywa skrywaną przez lata tajemnicę.', '10.png', 164),
(11, 'Joker', 'Strudzony życiem komik Arthur Fleck popada w obłęd i staje się psychopatycznym mordercą.', '11.png', 122),
(12, 'Mad Max: Fury Road', 'W postapokaliptycznym świecie Max łączy siły z tajemniczą Furiosą.', '12.png', 120),
(13, 'Zodiac', 'Seryjny morderca terroryzuje San Francisco, wysyłając listy z szyframi do gazet.', '13.png', 157),
(14, 'Shutter Island', 'Szeryf federalny bada sprawę zniknięcia pacjentki ze szpitala dla psychicznie chorych.', '14.png', 138),
(15, 'Whiplash', 'Młody perkusista dostaje się do elitarnej orkiestry prowadzonej przez bezwzględnego nauczyciela.', '15.png', 106),
(16, 'Get Out', 'Młody Afroamerykanin odwiedza posiadłość rodziców swojej białej dziewczyny.', '16.png', 104),
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
(1, 1), (1, 6),
(2, 2), (2, 6),
(3, 1), (3, 4),
(4, 4), (4, 6),
(5, 1), (5, 2),
(6, 4),
(7, 1), (7, 3),
(8, 2), (8, 6),
(9, 3), (9, 4),
(10, 1),
(11, 4), (11, 6),
(12, 2),
(13, 4), (13, 6),
(14, 6),
(15, 4),
(16, 5), (16, 6),
(17, 1), (17, 2),
(18, 2), (18, 4),
(19, 6),
(20, 1), (20, 2),
(21, 5),
(22, 3), (22, 6),
(23, 1), (23, 2),
(24, 4);

-- 4 seanse na film (czerwiec 2026)
INSERT INTO screenings (movie_id, show_date, show_time, hall_number, format)
SELECT
    m.id,
    ('2026-06-01'::date + ((m.id + slot.day_offset) % 5))::date,
    slot.show_time,
    (m.id % 4) + 1,
    CASE WHEN m.id % 4 = 0 THEN 'IMAX 3D' WHEN m.id % 4 = 2 THEN '4DX' ELSE 'Digital' END
FROM movies m
CROSS JOIN (
    VALUES
        (0, '14:00'::time),
        (0, '20:00'::time),
        (1, '17:30'::time),
        (2, '22:30'::time)
) AS slot(day_offset, show_time);

-- Przykładowe zajęte miejsca (Inception 01.06 14:00) + demo bilet użytkownika
INSERT INTO reservations (user_id, screening_id, seat_number)
SELECT 2, s.id, t.seat
FROM screenings s
CROSS JOIN (VALUES ('A4'), ('A5'), ('C1')) AS t(seat)
WHERE s.movie_id = 1
  AND s.show_date = '2026-06-01'
  AND s.show_time = '14:00'::time;

INSERT INTO reservations (user_id, screening_id, seat_number)
SELECT 2, s.id, t.seat
FROM screenings s
CROSS JOIN (VALUES ('B3'), ('B4')) AS t(seat)
WHERE s.movie_id = 2
  AND s.show_date = '2026-06-03'
  AND s.show_time = '20:00'::time;

-- Przykładowa wiadomość kontaktowa
INSERT INTO contact_messages (user_id, name, email, message, is_read) VALUES
(2, 'Jan Kowalski', 'user@gmail.com', 'Is there a student discount for weekend screenings?', FALSE);
