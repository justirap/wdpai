<?php
/**
 * Ustawia hasło konta admin@cinema.com
 * Uruchomienie: docker compose exec php php scripts/set-admin-password.php [hasło]
 * Domyślne hasło: password123
 */

require_once __DIR__ . '/../Database.php';

$password = $argv[1] ?? 'password123';
$email = 'admin@cinema.com';

$hash = password_hash($password, PASSWORD_BCRYPT);

$db = new Database();
$stmt = $db->connect()->prepare('
    UPDATE users SET password = :password WHERE email = :email
');
$stmt->execute([':password' => $hash, ':email' => $email]);

if ($stmt->rowCount() === 0) {
    $insert = $db->connect()->prepare('
        INSERT INTO users (username, email, password, role)
        VALUES (\'admin\', :email, :password, \'admin\')
    ');
    $insert->execute([':email' => $email, ':password' => $hash]);
    echo "Admin account created.\n";
} else {
    echo "Admin password updated.\n";
}

echo "Email: {$email}\n";
echo "Password: {$password}\n";
