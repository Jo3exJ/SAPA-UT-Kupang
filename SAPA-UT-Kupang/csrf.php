<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token(): string
{
    return $_SESSION['csrf_token'] ?? '';
}

function csrf_valid(string $token): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    return $sessionToken !== '' && $token !== '' && hash_equals($sessionToken, $token);
}
