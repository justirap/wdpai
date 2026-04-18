DROP TABLE IF EXISTS reservations CASCADE;
DROP TABLE IF EXISTS movies CASCADE;
DROP TABLE IF EXISTS users CASCADE;

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user'
);

CREATE TABLE movies (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255), 
    duration INT
);

CREATE TABLE reservations (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    movie_id INT REFERENCES movies(id) ON DELETE CASCADE,
    seat_number VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
--Hasło dla obu kont: "password123"
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@cinema.com', '$2y$10$8uN6Nf8lYhX.GgGv.P8QOuC2mH2zZ5H0xM5L7U7v0L5P1i0e6L2mO', 'admin'),
('jan_kowalski', 'user@gmail.com', '$2y$10$8uN6Nf8lYhX.GgGv.P8QOuC2mH2zZ5H0xM5L7U7v0L5P1i0e6L2mO', 'user');

INSERT INTO movies (title, description, image, duration) VALUES 
('Diuna: Część Druga', 'Paul Atryda jednoczy się z Chani i Fremenami, aby zemścić się na spiskowcach.', 'dune2.jpg', 166),
('Oppenheimer', 'Historia amerykańskiego naukowca J. Roberta Oppenheimera i jego roli w stworzeniu bomby atomowej.', 'oppenheimer.jpg', 180),
('Biedne Istoty', 'Niesamowita opowieść o ewolucji Belli Baxter, młodej kobiety przywróconej do życia.', 'poor_things.jpg', 141);