<?php

class InputLimits {
    public const MAX_EMAIL = 255;
    public const MAX_USERNAME = 100;
    public const MAX_PASSWORD = 128;

    public static function emailTooLong(string $email): bool {
        return strlen($email) > self::MAX_EMAIL;
    }

    public static function usernameTooLong(string $username): bool {
        return strlen($username) > self::MAX_USERNAME;
    }

    public static function passwordTooLong(string $password): bool {
        return strlen($password) > self::MAX_PASSWORD;
    }
}
