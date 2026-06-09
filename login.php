<?php
ini_set('display_errors', 0);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once 'db.php';
$d = getInput();

$identifier = trim($d['identifier'] ?? $d['username'] ?? $d['email'] ?? '');
$password   = $d['password'] ?? '';

if (!$identifier || !$password)
    jsonOut(['success'=>false,'message'=>'Username/email and password are required.'], 400);

try { $db = getDB(); }
catch (Exception $e) { jsonOut(['success'=>false,'message'=>'Database connection failed. Is XAMPP running?'], 500); }

$stmt = $db->prepare('SELECT id,username,email,password_hash FROM users WHERE username=? OR email=?');
$stmt->execute([$identifier, $identifier]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash']))
    jsonOut(['success'=>false,'message'=>'Invalid username/email or password.'], 401);

// Generate a token and save it to the DB so it survives XAMPP restarts
$token = bin2hex(random_bytes(32));
$db->prepare('UPDATE users SET session_token=? WHERE id=?')->execute([$token, $user['id']]);

jsonOut(['success'=>true,'message'=>'Login successful.','token'=>$token,'user'=>[
    'id'       => (int)$user['id'],
    'username' => $user['username'],
    'email'    => $user['email'],
]]);
