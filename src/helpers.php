<?php

declare(strict_types=1);

function view(string $name, array $data = []): void {
    // $name is used by layout.php to include the right template
    extract($data, EXTR_SKIP);
    require __DIR__ . '/../views/layout.php';
}

function redirect(string $path): never {
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string {
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void {
    $ok = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
    if (!$ok) {
        http_response_code(403);
        echo 'CSRF invalid.';
        exit;
    }
}
