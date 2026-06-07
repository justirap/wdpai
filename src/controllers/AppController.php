<?php

class AppController {
    protected function isGet(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    protected function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function getCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    protected function validateCsrfToken(): bool {
        $submitted = $_POST['csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        return is_string($submitted)
            && $sessionToken !== ''
            && hash_equals($sessionToken, $submitted);
    }

    protected function authViewData(array $extra = []): array {
        return array_merge(['csrfToken' => $this->getCsrfToken()], $extra);
    }
 
    protected function render(string $template = null, array $variables = [])
    {
        $templatePath = 'public/views/'. $template.'.html';
        $templatePath404 = 'public/views/404.html';
        $output = "";
                 
        if(file_exists($templatePath)){
            extract($variables);
            
            ob_start();
            include $templatePath;
            $output = ob_get_clean();
        } else {
            ob_start();
            include $templatePath404;
            $output = ob_get_clean();
        }
        echo $output;
    }

    protected function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
        exit();
    }
}

protected function requireAdmin() {
    $this->requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        $url = "http://$_SERVER[HTTP_HOST]/dashboard";
        header("Location: {$url}");
        exit();
    }
}

protected function getSessionUsername(): string {
    return $_SESSION['user_username'] ?? $_SESSION['user_email'] ?? 'User';
}

}