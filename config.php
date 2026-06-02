<?php
// Configuración estricta de cookies de sesión para producción en Render
if (getenv('DB_PORT')) { // Si estamos en Render
    ini_set('session.cookie_secure', '1');     // Obliga a viajar solo por HTTPS
    ini_set('session.cookie_httponly', '1');   // Protege la cookie de ataques JS
    ini_set('session.cookie_samesite', 'Lax'); // Permite navegación segura entre páginas
    ini_set('session.use_only_cookies', '1');
}
// Configuración dinámica de la Base de Datos (Render / Local)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'lienzo');
define('DB_USER', getenv('DB_USER') ?: 'lienzo');
define('DB_PASS', getenv('DB_PASS') ?: 'Lienzo123!');

// Verificar si se configuró un puerto específico (como el 23949 de Aiven)
$dbPort = getenv('DB_PORT');

$pixKey = getenv('PIXA_API_KEY') ?: '';
if (!$pixKey && is_file(__DIR__ . '/key.txt')) {
    $pixKey = trim(file_get_contents(__DIR__ . '/key.txt'));
}
define('PIX_API_KEY', $pixKey);
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('MAX_FILE_SIZE', 25 * 1024 * 1024);
define('ALLOWED_EXT', ['png', 'jpg', 'jpeg', 'webp', 'gif']);
define('DEFAULT_CREDITS', 20);
define('DAILY_LIMIT', 5);

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        global $dbPort;
        $hostStr = DB_HOST;
        
        if (!empty($dbPort)) {
            $hostStr .= ';port=' . $dbPort;
        }
        
        $dsn = 'mysql:host=' . $hostStr . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        
        // Opciones de conexión PDO
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        // SI ESTAMOS EN PRODUCCIÓN (Render), le exigimos SSL a PDO para que Aiven lo acepte
        if (!empty($dbPort)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = true; 
            // Al ponerlo en true, PHP usará los certificados válidos nativos del sistema en Render
        }
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}
function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function get_json_input(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?: [];
}

function current_user_id(): ?int {
    if (session_status() === PHP_SESSION_NONE) {
        if (!empty(getenv('DB_PORT'))) {
            session_start([
                'cookie_secure' => true,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax'
            ]);
        } else {
            session_start();
        }
    }
    return $_SESSION['user_id'] ?? null;
}

function require_login(): array {
    $uid = current_user_id();
    if (!$uid) json_response(['error' => 'Necesitas iniciar sesión.'], 401);
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$uid]);
    $user = $stmt->fetch();
    if (!$user) json_response(['error' => 'Usuario no encontrado.'], 401);
    return $user;
}

function require_admin(): array {
    $user = require_login();
    if ($user['role'] !== 'admin') json_response(['error' => 'Acceso denegado.'], 403);
    return $user;
}

function allowed_file(string $filename): bool {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ALLOWED_EXT);
}

function validate_password(string $password): string {
    if (strlen($password) < 8) return 'La contraseña debe tener al menos 8 caracteres.';
    if (!preg_match('/[A-Z]/', $password)) return 'La contraseña debe contener al menos una letra mayúscula.';
    if (!preg_match('/[a-z]/', $password)) return 'La contraseña debe contener al menos una letra minúscula.';
    if (!preg_match('/\d/', $password)) return 'La contraseña debe contener al menos un número.';
    return '';
}

function reset_daily_if_needed(array &$user): void {
    $today = date('Y-m-d');
    if ($user['daily_count_date'] !== $today) {
        $stmt = db()->prepare('UPDATE users SET daily_count = 0, daily_count_date = ? WHERE id = ?');
        $stmt->execute([$today, $user['id']]);
        $user['daily_count'] = 0;
        $user['daily_count_date'] = $today;
    }
}

function curl_post(string $url, array $data, array $headers, int $timeout = 60): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        return ['error' => "cURL error ($errno): $error", 'code' => 502];
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) return ['error' => "Request failed: $error", 'code' => 502];
    $json = json_decode($response, true);
    if ($json === null && $response !== '') {
        return ['error' => 'Invalid JSON from API', 'code' => 502];
    }
    return ['data' => $json, 'code' => $httpCode];
}
