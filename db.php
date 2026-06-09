<?php

 
function getDB(): PDO {
    $host   = 'localhost';
    $dbname = 'kilometer_db';
    $user   = 'root';
    $pass   = '';             
 
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    return $pdo;
}
 
function getInput(): array {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) return $json;
    return $_POST;          
}
 
function jsonOut(array $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data);
    exit;
}
 
function requireLogin(): int {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        jsonOut(['success' => false, 'message' => 'Not authenticated.'], 401);
    }
    return (int) $_SESSION['user_id'];
}
