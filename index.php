<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-eval' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Handle PATCH via X-HTTP-Method-Override header
if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
    $method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
}

// Serve uploaded files
if (preg_match('#^/uploads/(.+)$#', $uri, $m)) {
    $file = __DIR__ . '/uploads/' . $m[1];
    if (is_file($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $types = [
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp', 'gif' => 'image/gif',
        ];
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
    http_response_code(404);
    exit;
}

// Serve static files
if (preg_match('#^/(css|js)/(.+)$#', $uri, $m)) {
    $file = __DIR__ . '/static/' . $m[1] . '/' . $m[2];
    if (is_file($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $types = ['css' => 'text/css', 'js' => 'application/javascript'];
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        readfile($file);
        exit;
    }
    http_response_code(404);
    exit;
}

// API routes
$apiMap = [
    'POST /api/auth/signup'         => __DIR__ . '/api/auth.php',
    'POST /api/auth/login'          => __DIR__ . '/api/auth.php',
    'POST /api/auth/logout'         => __DIR__ . '/api/auth.php',    
    'POST /api/images/upload'       => __DIR__ . '/api/images.php',
    'POST /api/images/upscale'      => __DIR__ . '/api/images.php',
    'POST /api/images/remove_bg'    => __DIR__ . '/api/images.php',
    'GET /api/auth/current_user'   => __DIR__ . '/api/auth.php',
    'GET /api/images/history'      => __DIR__ . '/api/images.php',
    'GET /api/admin/sessions'      => __DIR__ . '/api/admin.php',
    'GET /api/admin/users'         => __DIR__ . '/api/admin.php',
    'PATCH /api/admin/users/update' => __DIR__ . '/api/admin.php',
];

$key = "$method $uri";
if (isset($apiMap[$key])) {
    require $apiMap[$key];
    exit;
}

// Handle GET /api/admin/users/{email}
if (preg_match('#^/api/admin/users/(.+)$#', $uri, $m) && $method === 'GET') {
    $_GET['email'] = urldecode($m[1]);
    require __DIR__ . '/api/admin.php';
    exit;
}

// Page routes
$pageMap = [
    '/'        => __DIR__ . '/views/index.php',
    '/login'   => __DIR__ . '/views/login.php',
    '/signup'  => __DIR__ . '/views/signup.php',
    '/admin'   => __DIR__ . '/views/admin.php',
    '/history' => __DIR__ . '/views/history.php',
];

if (isset($pageMap[$uri])) {
    require $pageMap[$uri];
    exit;
}

http_response_code(404);
echo '404 Not Found';
