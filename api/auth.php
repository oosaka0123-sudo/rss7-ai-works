<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

require_method('GET', 'POST');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    json_response(is_admin()
        ? ['success' => true, 'authenticated' => true, 'user' => $_SESSION['user'], 'csrf' => csrf_token()]
        : ['success' => true, 'authenticated' => false]);
}

$input = request_json();
$action = (string)($input['action'] ?? '');

if ($action === 'logout') {
    require_admin();
    require_csrf();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
    json_response(['success' => true, 'message' => 'ログアウトしました']);
}

if ($action !== 'login') json_response(['success' => false, 'message' => '操作が不正です'], 400);

$now = time();
$attempts = array_values(array_filter($_SESSION['login_attempts'] ?? [], static fn($time) => is_int($time) && $time > $now - 900));
if (count($attempts) >= 5) json_response(['success' => false, 'message' => '試行回数が多すぎます。15分後にお試しください'], 429);

$username = trim((string)($input['username'] ?? ''));
$password = (string)($input['password'] ?? '');
$users = local_config()['users'] ?? [];
$account = is_array($users) && isset($users[$username]) && is_array($users[$username]) ? $users[$username] : null;
$valid = $account && isset($account['password_hash']) && password_verify($password, (string)$account['password_hash']);

if (!$valid) {
    $attempts[] = $now;
    $_SESSION['login_attempts'] = $attempts;
    usleep(500000);
    json_response(['success' => false, 'message' => 'ユーザー名またはパスワードが違います'], 401);
}

session_regenerate_id(true);
unset($_SESSION['login_attempts']);
$_SESSION['user'] = ['username' => $username, 'name' => (string)($account['name'] ?? $username), 'role' => 'admin'];
$_SESSION['csrf'] = bin2hex(random_bytes(32));
json_response(['success' => true, 'user' => $_SESSION['user'], 'csrf' => $_SESSION['csrf']]);
