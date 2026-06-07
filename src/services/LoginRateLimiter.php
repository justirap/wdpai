<?php

class LoginRateLimiter {
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 300;

    private string $storageDir;
    private string $clientKey;

    public function __construct() {
        $this->storageDir = __DIR__ . '/../../storage/login_attempts';
        $this->clientKey = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    public function isLocked(): bool {
        $state = $this->readState();

        if ($state['locked_until'] > time()) {
            return true;
        }

        if ($state['locked_until'] > 0) {
            $this->clear();
        }

        return false;
    }

    public function getRemainingSeconds(): int {
        $state = $this->readState();
        return max(0, $state['locked_until'] - time());
    }

    public function getRemainingAttempts(): int {
        $state = $this->readState();
        return max(0, self::MAX_ATTEMPTS - $state['attempts']);
    }

    public function recordFailure(): void {
        $state = $this->readState();

        if ($state['locked_until'] > time()) {
            return;
        }

        if ($state['locked_until'] > 0) {
            $state = ['attempts' => 0, 'locked_until' => 0];
        }

        $state['attempts']++;

        if ($state['attempts'] >= self::MAX_ATTEMPTS) {
            $state['locked_until'] = time() + self::LOCKOUT_SECONDS;
            $state['attempts'] = 0;
        }

        $this->writeState($state);
    }

    public function clear(): void {
        $path = $this->getFilePath();
        if (is_file($path)) {
            unlink($path);
        }
    }

    public function formatLockMessage(): string {
        $seconds = $this->getRemainingSeconds();
        if ($seconds <= 60) {
            return 'Too many failed login attempts. Please try again in about 1 minute.';
        }

        $minutes = (int) ceil($seconds / 60);
        return "Too many failed login attempts. Please try again in {$minutes} minutes.";
    }

    private function getFilePath(): string {
        return $this->storageDir . '/' . $this->clientKey . '.json';
    }

    private function readState(): array {
        $this->ensureStorageDir();
        $path = $this->getFilePath();

        if (!is_file($path)) {
            return ['attempts' => 0, 'locked_until' => 0];
        }

        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data)) {
            return ['attempts' => 0, 'locked_until' => 0];
        }

        return [
            'attempts' => (int) ($data['attempts'] ?? 0),
            'locked_until' => (int) ($data['locked_until'] ?? 0),
        ];
    }

    private function writeState(array $state): void {
        $this->ensureStorageDir();
        file_put_contents(
            $this->getFilePath(),
            json_encode($state),
            LOCK_EX
        );
    }

    private function ensureStorageDir(): void {
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }
}
