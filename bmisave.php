<?php
ini_set('display_errors', 0);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once 'db.php';

$userId = requireLogin();
$d = getInput();

$weight = (float)($d['weight_kg'] ?? 0);
$height = (float)($d['height_cm'] ?? 0);

if ($weight <= 0 || $height <= 0)
    jsonOut(['success'=>false,'message'=>'Valid weight and height are required.'], 400);

$heightM = $height / 100;
$bmi     = round($weight / ($heightM * $heightM), 1);

if      ($bmi < 18.5) $category = 'Underweight';
else if ($bmi < 25.0) $category = 'Normal weight';
else if ($bmi < 30.0) $category = 'Overweight';
else                  $category = 'Obese';

try { $db = getDB(); }
catch (Exception $e) { jsonOut(['success'=>false,'message'=>'Database connection failed.'], 500); }

$db->prepare('INSERT INTO bmi_records (user_id,weight_kg,height_cm,bmi,category) VALUES (?,?,?,?,?)')
   ->execute([$userId, $weight, $height, $bmi, $category]);

jsonOut(['success'=>true,'bmi'=>$bmi,'category'=>$category,'message'=>"BMI: $bmi — $category"]);
