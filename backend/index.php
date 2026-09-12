<?php

header('Content-Type: application/json');

$db = new PDO('sqlite:' . __DIR__ . '/data/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL
    )
");

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$input = json_decode(file_get_contents('php://input'), true);


if ($path === '/api/health' && $method === 'GET') {

    echo json_encode([
        'status' => 'ok'
    ]);

    exit;
}


if ($path === '/api/register' && $method === 'POST') {

    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    if ($username === '' || $password === '') {

        http_response_code(400);

        echo json_encode([
            'error' => 'Username and password are required'
        ]);

        exit;
    }

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    try {

        $stmt = $db->prepare(
            'INSERT INTO users (username, password)
             VALUES (?, ?)'
        );

        $stmt->execute([
            $username,
            $passwordHash
        ]);

        echo json_encode([
            'message' => 'User registered'
        ]);

    } catch (PDOException $e) {

        http_response_code(409);

        echo json_encode([
            'error' => 'Username already exists'
        ]);
    }

    exit;
}


if ($path === '/api/login' && $method === 'POST') {

    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    $stmt = $db->prepare(
        'SELECT * FROM users WHERE username = ?'
    );

    $stmt->execute([$username]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {

        http_response_code(401);

        echo json_encode([
            'error' => 'Invalid username or password'
        ]);

        exit;
    }

    echo json_encode([
        'message' => 'Login successful',
        'username' => $user['username']
    ]);

    exit;
}


if ($path === '/api/me' && $method === 'GET') {

    echo json_encode([
        'message' => 'Backend is working'
    ]);

    exit;
}


http_response_code(404);

echo json_encode([
    'error' => 'Not found'
]);