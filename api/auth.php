<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// POST /api/auth/signup
if ($method === 'POST' && $uri === '/api/auth/signup') {
    $data = get_json_input();
    $name = trim($data['name'] ?? '');
    $email = strtolower(trim($data['email'] ?? ''));
    $password = $data['password'] ?? '';
    $confirm = $data['confirm'] ?? '';

    if (!$name || !$email || !$password || !$confirm)
        json_response(['error' => 'Todos los campos son obligatorios.'], 400);
    if (strlen($name) > 50 || strlen($email) > 50 || strlen($password) > 20 || strlen($confirm) > 20)
        json_response(['error' => 'Los campos exceden el máximo de caracteres permitidos.'], 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        json_response(['error' => 'Ingresa un correo electrónico válido.'], 400);

    $pwError = validate_password($password);
    if ($pwError) json_response(['error' => $pwError], 400);
    if ($password !== $confirm)
        json_response(['error' => 'Las contraseñas no coinciden.'], 400);

    $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch())
        json_response(['error' => 'Ya existe una cuenta con ese correo.'], 400);

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = db()->prepare('INSERT INTO users (name, email, password, role, credits) VALUES (?, ?, ?, "user", ?)');
    $stmt->execute([$name, $email, $hash, DEFAULT_CREDITS]);

    json_response(['success' => true, 'message' => 'Cuenta creada correctamente. Ya puedes iniciar sesión.']);
}

// POST /api/auth/login
if ($method === 'POST' && $uri === '/api/auth/login') {
    $data = get_json_input();
    if (empty($data)) {
        $data = $_POST;
    }
    $email = strtolower(trim($data['email'] ?? ''));
    $password = $data['password'] ?? '';

    if (!$email || !$password)
        json_response(['error' => 'Todos los campos son obligatorios.'], 400);
    if (strlen($email) > 50 || strlen($password) > 20)
        json_response(['error' => 'Los campos exceden el máximo de caracteres permitidos.'], 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        json_response(['error' => 'Ingresa un correo electrónico válido.'], 400);

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password']))
        json_response(['error' => 'Correo o contraseña incorrectos.'], 401);

    $sessionId = bin2hex(random_bytes(16));
    $stmt = db()->prepare('INSERT INTO sessions (id, user_id) VALUES (?, ?)');
    $stmt->execute([$sessionId, $user['id']]);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['session_id'] = $sessionId;

    json_response(['success' => true, 'message' => 'Has iniciado sesión correctamente.']);
}

// POST /api/auth/logout
if ($method === 'POST' && $uri === '/api/auth/logout') {
    $sid = $_SESSION['session_id'] ?? null;
    if ($sid) {
        $stmt = db()->prepare('DELETE FROM sessions WHERE id = ?');
        $stmt->execute([$sid]);
    }
    session_destroy();
    json_response(['success' => true, 'message' => 'Sesión cerrada correctamente.']);
}

// GET /api/auth/current_user
if ($method === 'GET' && $uri === '/api/auth/current_user') {
    $uid = current_user_id();
    if (!$uid) {
        json_response(['logged_in' => false]);
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$uid]);
    $user = $stmt->fetch();
    if (!$user) {
        json_response(['logged_in' => false]);
    }

    reset_daily_if_needed($user);

    json_response([
        'logged_in' => true,
        'name' => $user['name'],
        'role' => $user['role'],
        'daily_count' => (int)$user['daily_count'],
        'credits' => (int)$user['credits'],
    ]);
}

json_response(['error' => 'Ruta no encontrada.'], 404);
