<?php

ini_set('display_errors', 0);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
    jsonOut(['success' => false, 'message' => 'You must be logged in.'], 401);
}
$user_id = $_SESSION['user_id'];

try { $db = getDB(); }
catch (Exception $e) { jsonOut(['success'=>false,'message'=>'Database connection failed.'], 500); }


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("
        SELECT id, distance_km, duration_minutes, calories_burned,
               burn_rate, notes, logged_at
        FROM running_logs
        WHERE user_id = ?
        ORDER BY logged_at DESC
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $logs = $stmt->fetchAll();

    $stats_stmt = $db->prepare("
        SELECT
            COUNT(*) as total_runs,
            COALESCE(SUM(distance_km), 0) as total_km,
            COALESCE(SUM(calories_burned), 0) as total_calories,
            COALESCE(AVG(distance_km), 0) as avg_km
        FROM running_logs WHERE user_id = ?
    ");
    $stats_stmt->execute([$user_id]);
    $stats = $stats_stmt->fetch();

    jsonOut(['success' => true, 'logs' => $logs, 'stats' => $stats]);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $distance  = floatval($data['distance_km'] ?? 0);
    $duration  = intval($data['duration_minutes'] ?? 0);
    $calories  = intval($data['calories_burned'] ?? 0);
    $burn_rate = $distance > 0 ? round($calories / $distance, 2) : 0;
    $notes     = trim($data['notes'] ?? '');

    if (!$distance || !$duration || !$calories) {
        jsonOut(['success' => false, 'message' => 'Distance, duration, and calories are required.'], 400);
    }

    $stmt = $db->prepare("
        INSERT INTO running_logs (user_id, distance_km, duration_minutes, calories_burned, burn_rate, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $distance, $duration, $calories, $burn_rate, $notes]);

    jsonOut([
        'success'   => true,
        'message'   => 'Run logged successfully!',
        'id'        => $db->lastInsertId(),
        'burn_rate' => $burn_rate,
    ]);
}

jsonOut(['success' => false, 'message' => 'Method not allowed.'], 405);
