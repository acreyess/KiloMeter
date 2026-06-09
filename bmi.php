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
        SELECT id, weight, height, age, gender, activity_level,
               bmi, bmi_status, daily_calories, recorded_at
        FROM bmi_results
        WHERE user_id = ?
        ORDER BY recorded_at DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    jsonOut(['success' => true, 'results' => $stmt->fetchAll()]);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $weight         = floatval($data['weight'] ?? 0);
    $height         = floatval($data['height'] ?? 0);
    $age            = intval($data['age'] ?? 0);
    $gender         = $data['gender'] ?? 'male';
    $activity_level = $data['activity_level'] ?? 'Moderate';
    $bmi            = floatval($data['bmi'] ?? 0);
    $bmi_status     = $data['bmi_status'] ?? 'Normal';
    $daily_calories = intval($data['daily_calories'] ?? 0);

    if (!$weight || !$height || !$age || !$bmi) {
        jsonOut(['success' => false, 'message' => 'Missing required fields.'], 400);
    }

    $stmt = $db->prepare("
        INSERT INTO bmi_results
            (user_id, weight, height, age, gender, activity_level, bmi, bmi_status, daily_calories)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $weight, $height, $age, $gender, $activity_level, $bmi, $bmi_status, $daily_calories]);

    jsonOut(['success' => true, 'message' => 'BMI result saved!', 'id' => $db->lastInsertId()]);
}

jsonOut(['success' => false, 'message' => 'Method not allowed.'], 405);
