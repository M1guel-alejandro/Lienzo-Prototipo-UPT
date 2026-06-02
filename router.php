<?php
/**
 * Router para el servidor PHP built-in.
 * Uso: php -S localhost:8000 router.php
 * Esto permite que el proyecto funcione en Windows sin Apache ni .htaccess.
 */
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-eval' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Servir archivos estáticos directamente
$staticFile = __DIR__ . $uri;
if ($uri !== '/' && is_file($staticFile)) {
    $ext = strtolower(pathinfo($staticFile, PATHINFO_EXTENSION));
    $types = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
    ];
    if (isset($types[$ext])) {
        header('Content-Type: ' . $types[$ext]);
    }
    readfile($staticFile);
    return true;
}

// Todo lo demás pasa por index.php (front controller)
require __DIR__ . '/index.php';
