<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// POST /api/images/upload
if ($method === 'POST' && $uri === '/api/images/upload') {
    if (!isset($_FILES['image']))
        json_response(['error' => 'No image file provided'], 400);

    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK)
        json_response(['error' => 'Error al subir archivo'], 400);
    if ($file['size'] > MAX_FILE_SIZE)
        json_response(['error' => 'Archivo excede 25 MB'], 400);
    if (!allowed_file($file['name']))
        json_response(['error' => 'Formato no permitido'], 400);

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
    $savePath = UPLOAD_DIR . '/' . $filename;
    $pathinfo = pathinfo($savePath);
    $counter = 1;
    while (file_exists($savePath)) {
        $savePath = UPLOAD_DIR . '/' . $pathinfo['filename'] . '_' . $counter . '.' . $pathinfo['extension'];
        $counter++;
    }

    move_uploaded_file($file['tmp_name'], $savePath);
    $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST'];
    $imageUrl = $host . '/uploads/' . basename($savePath);

    json_response(['image_url' => $imageUrl]);
}

// POST /api/images/upscale
if ($method === 'POST' && $uri === '/api/images/upscale') {
    $data = get_json_input();
    $imageUrl = $data['image_url'] ?? '';
    $scale = $data['scale'] ?? 2;

    if (!$imageUrl) json_response(['error' => 'image_url is required'], 400);
    if (!in_array((int)$scale, [2, 4])) json_response(['error' => 'scale must be 2 or 4'], 400);
    if (!PIX_API_KEY) json_response(['error' => 'Missing PIXA_API_KEY'], 500);

    // Check user limits
    $uid = current_user_id();
    if ($uid) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$uid]);
        $user = $stmt->fetch();
        if ($user) {
            reset_daily_if_needed($user);
            if ($user['daily_count'] >= DAILY_LIMIT)
                json_response(['error' => 'Límite diario de 5 imágenes alcanzado.'], 403);
            if ($user['credits'] <= 0 && $user['role'] !== 'admin')
                json_response(['error' => 'No tienes créditos suficientes.'], 403);
        }
    }

    $result = curl_post(
        'https://api.developer.pixelcut.ai/v1/upscale',
        ['image_url' => $imageUrl, 'scale' => (int)$scale],
        [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-API-Key: ' . PIX_API_KEY,
        ]
    );

    if (isset($result['error']))
        json_response(['error' => $result['error']], $result['code']);

    $respData = $result['data'];
    if ($result['code'] !== 200)
        json_response(['error' => 'API error', 'details' => $respData], $result['code']);

    $resultUrl = $respData['result_url'] ?? null;
    if (!$resultUrl)
        json_response(['error' => 'La respuesta no contiene result_url.', 'details' => $respData], 500);

    if ($uid) {
        $stmt = db()->prepare('INSERT INTO history (user_id, result_url) VALUES (?, ?)');
        $stmt->execute([$uid, $resultUrl]);
        $stmt = db()->prepare('UPDATE users SET daily_count = daily_count + 1, credits = credits - 1 WHERE id = ? AND role != "admin"');
        $stmt->execute([$uid]);
    }

    json_response(['result_url' => $resultUrl]);
}

// POST /api/images/remove_bg
if ($method === 'POST' && $uri === '/api/images/remove_bg') {
    if (!PIX_API_KEY)
        json_response(['error' => 'PIXA_API_KEY no está configurada en el servidor.'], 501);

    // Check user limits
    $uid = current_user_id();
    if ($uid) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$uid]);
        $user = $stmt->fetch();
        if ($user) {
            reset_daily_if_needed($user);
            if ($user['daily_count'] >= DAILY_LIMIT)
                json_response(['error' => 'Límite diario de 5 imágenes alcanzado.'], 403);
            if ($user['credits'] <= 0 && $user['role'] !== 'admin')
                json_response(['error' => 'No tienes créditos suficientes.'], 403);
        }
    }

    $imageUrl = null;

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        if (!allowed_file($file['name']))
            json_response(['error' => 'Archivo no válido.'], 400);

        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
        $savePath = UPLOAD_DIR . '/' . $filename;
        $pathinfo = pathinfo($savePath);
        $counter = 1;
        while (file_exists($savePath)) {
            $savePath = UPLOAD_DIR . '/' . $pathinfo['filename'] . '_' . $counter . '.' . $pathinfo['extension'];
            $counter++;
        }
        move_uploaded_file($file['tmp_name'], $savePath);
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                . '://' . $_SERVER['HTTP_HOST'];
        $imageUrl = $host . '/uploads/' . basename($savePath);
    }

    // Handle JSON or form data
    $data = get_json_input();
    if (!$data) {
        $data = $_POST;
    }

    if (!$imageUrl) {
        $imageUrl = $data['image_url'] ?? null;
    }

    if (!$imageUrl)
        json_response(['error' => 'Se requiere un archivo de imagen o image_url.'], 400);

    // Build payload
    $payload = ['image_url' => $imageUrl, 'format' => 'png'];
    if (isset($data['format'])) $payload['format'] = $data['format'];
    if (isset($data['shadow'])) $payload['shadow'] = $data['shadow'];
    if (isset($data['crop'])) $payload['crop'] = filter_var($data['crop'], FILTER_VALIDATE_BOOLEAN);
    if (isset($data['margin'])) $payload['margin'] = $data['margin'];

    $result = curl_post(
        'https://api.developer.pixelcut.ai/v1/remove-background',
        $payload,
        [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-API-Key: ' . PIX_API_KEY,
        ],
        120
    );

    if (isset($result['error']))
        json_response(['error' => $result['error']], $result['code']);

    $respData = $result['data'];
    if ($result['code'] !== 200)
        json_response(['error' => 'API error', 'details' => $respData], $result['code']);

    $resultUrl = $respData['result_url'] ?? null;
    if (!$resultUrl)
        json_response(['error' => 'La respuesta no contiene result_url.', 'details' => $respData], 500);

    if ($uid) {
        $stmt = db()->prepare('INSERT INTO history (user_id, result_url) VALUES (?, ?)');
        $stmt->execute([$uid, $resultUrl]);
        $stmt = db()->prepare('UPDATE users SET daily_count = daily_count + 1, credits = credits - 1 WHERE id = ? AND role != "admin"');
        $stmt->execute([$uid]);
    }

    json_response(['result_url' => $resultUrl]);
}

// GET /api/images/history
if ($method === 'GET' && $uri === '/api/images/history') {
    $uid = current_user_id();
    if (!$uid)
        json_response(['error' => 'Necesitas iniciar sesión para ver tu historial.'], 401);

    $stmt = db()->prepare('SELECT result_url, created_at FROM history WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$uid]);
    $rows = $stmt->fetchAll();

    $history = array_map(fn($r) => [
        'result_url' => $r['result_url'],
        'timestamp' => $r['created_at'],
    ], $rows);

    json_response(['history' => $history]);
}

json_response(['error' => 'Ruta no encontrada.'], 404);
