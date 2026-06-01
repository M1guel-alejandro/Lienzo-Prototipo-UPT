<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$admin = require_admin();

// GET /api/admin/sessions
if ($method === 'GET' && $uri === '/api/admin/sessions') {
    $stmt = db()->prepare(
        'SELECT s.id, s.login_time, u.email, u.name
         FROM sessions s JOIN users u ON s.user_id = u.id
         ORDER BY s.login_time DESC'
    );
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $sessions = [];
    foreach ($rows as $r) {
        $sessions[$r['id']] = [
            'email' => $r['email'],
            'name' => $r['name'],
            'login_time' => $r['login_time'],
        ];
    }
    json_response(['sessions' => $sessions]);
}

// GET /api/admin/users
if ($method === 'GET' && $uri === '/api/admin/users') {
    $stmt = db()->prepare('SELECT email, name, role, credits, daily_count, daily_count_date FROM users');
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $users = [];
    foreach ($rows as $r) {
        $users[$r['email']] = [
            'name' => $r['name'],
            'role' => $r['role'],
            'credits' => (int)$r['credits'],
            'daily_count' => (int)$r['daily_count'],
            'daily_count_date' => $r['daily_count_date'],
        ];
    }
    json_response(['users' => $users]);
}

// GET /api/admin/users/{email}
if ($method === 'GET' && preg_match('#^/api/admin/users/(.+)$#', $uri, $m)) {
    $email = urldecode($m[1]);
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user)
        json_response(['error' => 'Usuario no encontrado.'], 404);

    $stmt = db()->prepare('SELECT result_url, created_at FROM history WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user['id']]);
    $history = array_map(fn($r) => [
        'result_url' => $r['result_url'],
        'timestamp' => $r['created_at'],
    ], $stmt->fetchAll());

    json_response([
        'email' => $user['email'],
        'name' => $user['name'],
        'role' => $user['role'],
        'credits' => (int)$user['credits'],
        'daily_count' => (int)$user['daily_count'],
        'daily_count_date' => $user['daily_count_date'],
        'history' => $history,
    ]);
}

// PATCH /api/admin/users/update
if ($method === 'PATCH' && $uri === '/api/admin/users/update') {
    $data = get_json_input();
    $email = $data['email'] ?? '';
    $credits = $data['credits'] ?? null;

    if (!$email)
        json_response(['error' => 'Email requerido.'], 400);
    if ($credits === null || !is_numeric($credits) || $credits < 0)
        json_response(['error' => 'Créditos inválidos.'], 400);

    $stmt = db()->prepare('UPDATE users SET credits = ? WHERE email = ?');
    $stmt->execute([(int)$credits, $email]);
    if ($stmt->rowCount() === 0)
        json_response(['error' => 'Usuario no encontrado.'], 404);

    json_response(['success' => true, 'user' => ['email' => $email, 'credits' => (int)$credits]]);
}

json_response(['error' => 'Ruta no encontrada.'], 404);
