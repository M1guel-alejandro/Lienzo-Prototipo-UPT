<?php
// Script de creación rápida de la base de datos SQLite
// Ejecutar: php create_sqlite.php

$dbFile = __DIR__ . '/database.sqlite';
$dsn = 'sqlite:' . $dbFile;

try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec('PRAGMA foreign_keys = ON;');

    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'user',
    credits INTEGER DEFAULT 20,
    daily_count INTEGER DEFAULT 0,
    daily_count_date DATE DEFAULT NULL,
    created_at DATETIME DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS sessions (
    id TEXT PRIMARY KEY,
    user_id INTEGER NOT NULL,
    login_time DATETIME DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    result_url TEXT NOT NULL,
    created_at DATETIME DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
SQL;

    $pdo->exec($sql);

    // Inserta admin por defecto si no existe (password bcrypt ya generada en database.sql)
    $adminEmail = 'admin@lienzo.com';
    $adminName = 'Administrador';
    $adminHash = '$2y$10$ZJy.gZk4CxcYpg4gETuEVORtLcafIxXIEXXI37xCC4TWfsbDbgQRO'; // Admin123 (ejemplo)

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$adminEmail]);
    $exists = $stmt->fetchColumn();
    if (!$exists) {
        $ins = $pdo->prepare('INSERT INTO users (name, email, password, role, credits) VALUES (?, ?, ?, ?, ?)');
        $ins->execute([$adminName, $adminEmail, $adminHash, 'admin', 9999]);
        echo "Admin creado: $adminEmail\n";
    } else {
        echo "Admin ya existe: $adminEmail\n";
    }

    echo "Base de datos SQLite inicializada en: $dbFile\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
