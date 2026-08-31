<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('rss7_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function request_json(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    if (!is_array($data)) json_response(['success' => false, 'message' => 'JSONが不正です'], 400);
    return $data;
}

function require_method(string ...$allowed): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    if (!in_array($method, $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        json_response(['success' => false, 'message' => '許可されていないメソッドです'], 405);
    }
}

function is_admin(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function require_admin(): void
{
    if (!is_admin()) json_response(['success' => false, 'message' => 'ログインが必要です'], 401);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return (string)$_SESSION['csrf'];
}

function require_csrf(): void
{
    $received = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($received) || !hash_equals(csrf_token(), $received)) {
        json_response(['success' => false, 'message' => 'セキュリティ確認に失敗しました'], 403);
    }
}

function local_config(): array
{
    $path = __DIR__ . '/config.local.php';
    if (!is_file($path)) json_response(['success' => false, 'message' => 'サーバー設定が未完了です'], 503);
    $config = require $path;
    if (!is_array($config)) json_response(['success' => false, 'message' => 'サーバー設定が不正です'], 500);
    return $config;
}
