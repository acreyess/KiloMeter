<?php
ini_set('display_errors', 0);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once 'db.php';

$userId = requireLogin();
$method = $_SERVER['REQUEST_METHOD'];

try { $db = getDB(); }
catch (Exception $e) { jsonOut(['success'=>false,'message'=>'Database connection failed.'], 500); }

if ($method === 'GET') {
    $stmt = $db->prepare('SELECT * FROM goals WHERE user_id=? ORDER BY created_at DESC');
    $stmt->execute([$userId]);
    jsonOut(['success'=>true,'goals'=>$stmt->fetchAll()]);
}

if ($method === 'POST') {
    $d = getInput();
    $title   = trim($d['title'] ?? '');
    $target  = $d['target_value'] ?? null;
    $unit    = trim($d['unit']    ?? '');
    $deadline = $d['deadline']    ?? null;
    if (!$title) jsonOut(['success'=>false,'message'=>'Goal title is required.'], 400);
    $db->prepare('INSERT INTO goals (user_id,title,target_value,unit,deadline) VALUES (?,?,?,?,?)')
       ->execute([$userId, $title, $target ?: null, $unit ?: null, $deadline ?: null]);
    $id = $db->lastInsertId();
    $stmt = $db->prepare('SELECT * FROM goals WHERE id=?');
    $stmt->execute([$id]);
    jsonOut(['success'=>true,'goal'=>$stmt->fetch()], 201);
}

if ($method === 'PATCH') {
    $d  = getInput();
    $id = (int)($d['id'] ?? 0);
    if (!$id) jsonOut(['success'=>false,'message'=>'Goal id required.'], 400);
    $completed = isset($d['completed']) ? (int)(bool)$d['completed'] : null;
    if ($completed !== null) {
        $db->prepare('UPDATE goals SET completed=? WHERE id=? AND user_id=?')
           ->execute([$completed, $id, $userId]);
    }
    $stmt = $db->prepare('SELECT * FROM goals WHERE id=? AND user_id=?');
    $stmt->execute([$id, $userId]);
    jsonOut(['success'=>true,'goal'=>$stmt->fetch()]);
}

if ($method === 'DELETE') {
    $d  = getInput();
    $id = (int)($d['id'] ?? 0);
    if (!$id) jsonOut(['success'=>false,'message'=>'Goal id required.'], 400);
    $db->prepare('DELETE FROM goals WHERE id=? AND user_id=?')->execute([$id, $userId]);
    jsonOut(['success'=>true,'message'=>'Goal deleted.']);
}
