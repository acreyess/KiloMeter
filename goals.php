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

/* ── GET: list all goals for this user ── */
if ($method === 'GET') {
    $stmt = $db->prepare('SELECT * FROM goals WHERE user_id=? ORDER BY created_at DESC');
    $stmt->execute([$userId]);
    $goals = $stmt->fetchAll();
    /* ensure numeric fields are typed correctly */
    foreach ($goals as &$g) {
        $g['target_value']  = (float)$g['target_value'];
        $g['current_value'] = (float)($g['current_value'] ?? 0);
        $g['progress']      = $g['target_value'] > 0
            ? min(100, round(($g['current_value'] / $g['target_value']) * 100))
            : 0;
        if (!isset($g['status'])) {
            $g['status'] = $g['current_value'] >= $g['target_value'] ? 'completed' : 'active';
        }
    }
    unset($g);
    jsonOut(['success'=>true,'goals'=>$goals]);
}

/* ── POST: create new goal ── */
if ($method === 'POST') {
    $d        = getInput();
    $title    = trim($d['title']   ?? '');
    $cat      = trim($d['category'] ?? 'custom');
    $unit     = trim($d['unit']    ?? '');
    $target   = (float)($d['target_value']  ?? 0);
    $current  = (float)($d['current_value'] ?? 0);
    $deadline = $d['deadline'] ?: null;
    $notes    = trim($d['notes']   ?? '');

    if (!$title) jsonOut(['success'=>false,'message'=>'Goal title is required.'], 400);
    if ($target <= 0) jsonOut(['success'=>false,'message'=>'Target must be greater than 0.'], 400);

    $status = $current >= $target ? 'completed' : 'active';

    /* Add columns if they don't exist yet (graceful for older DB schemas) */
    try {
        $db->exec("ALTER TABLE goals ADD COLUMN category VARCHAR(50) DEFAULT 'custom'");
    } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE goals ADD COLUMN unit VARCHAR(30) DEFAULT ''"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE goals ADD COLUMN current_value DECIMAL(10,2) DEFAULT 0"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE goals ADD COLUMN notes TEXT DEFAULT NULL"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE goals ADD COLUMN status VARCHAR(20) DEFAULT 'active'"); } catch (Exception $e) {}

    $db->prepare('INSERT INTO goals (user_id, title, category, unit, target_value, current_value, deadline, notes, status)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
       ->execute([$userId, $title, $cat, $unit, $target, $current, $deadline, $notes ?: null, $status]);

    $id   = $db->lastInsertId();
    $stmt = $db->prepare('SELECT * FROM goals WHERE id=?');
    $stmt->execute([$id]);
    $g = $stmt->fetch();
    $g['target_value']  = (float)$g['target_value'];
    $g['current_value'] = (float)($g['current_value'] ?? 0);
    $g['progress']      = $target > 0 ? min(100, round(($g['current_value'] / $target) * 100)) : 0;
    $g['status']        = $status;
    jsonOut(['success'=>true,'goal'=>$g], 201);
}

/* ── PATCH: update an existing goal (title/target/current/status/etc.) ── */
if ($method === 'PATCH') {
    $d  = getInput();
    $id = (int)($d['id'] ?? 0);
    if (!$id) jsonOut(['success'=>false,'message'=>'Goal id required.'], 400);

    /* Build dynamic SET clause from provided fields */
    $allowed = ['title','category','unit','target_value','current_value','deadline','notes','status','completed'];
    $sets    = [];
    $params  = [];

    foreach ($allowed as $field) {
        if (!array_key_exists($field, $d)) continue;
        if ($field === 'completed') {
            /* legacy field — map to status */
            $sets[]   = 'status = ?';
            $params[] = $d['completed'] ? 'completed' : 'active';
        } else {
            $sets[]   = "$field = ?";
            $params[] = ($field === 'deadline' && $d[$field] === '') ? null : $d[$field];
        }
    }

    if (!$sets) jsonOut(['success'=>false,'message'=>'Nothing to update.'], 400);

    /* Auto-compute status when current_value or target_value changes */
    $newCurrent = isset($d['current_value']) ? (float)$d['current_value'] : null;
    $newTarget  = isset($d['target_value'])  ? (float)$d['target_value']  : null;

    if ($newCurrent !== null || $newTarget !== null) {
        /* Fetch current row to get existing values */
        $row = $db->prepare('SELECT target_value, current_value FROM goals WHERE id=? AND user_id=?');
        $row->execute([$id, $userId]);
        $existing = $row->fetch();
        if ($existing) {
            $cur = $newCurrent !== null ? $newCurrent : (float)$existing['current_value'];
            $tgt = $newTarget  !== null ? $newTarget  : (float)$existing['target_value'];
            if (!in_array('status = ?', $sets)) {
                $sets[]   = 'status = ?';
                $params[] = $cur >= $tgt ? 'completed' : 'active';
            }
        }
    }

    $params[] = $id;
    $params[] = $userId;
    $db->prepare('UPDATE goals SET ' . implode(', ', $sets) . ' WHERE id=? AND user_id=?')
       ->execute($params);

    $stmt = $db->prepare('SELECT * FROM goals WHERE id=? AND user_id=?');
    $stmt->execute([$id, $userId]);
    $g = $stmt->fetch();
    if ($g) {
        $g['target_value']  = (float)$g['target_value'];
        $g['current_value'] = (float)($g['current_value'] ?? 0);
        $g['progress']      = $g['target_value'] > 0
            ? min(100, round(($g['current_value'] / $g['target_value']) * 100)) : 0;
    }
    jsonOut(['success'=>true,'goal'=>$g]);
}

/* ── DELETE: remove a goal ── */
if ($method === 'DELETE') {
    $d  = getInput();
    $id = (int)($d['id'] ?? 0);
    if (!$id) jsonOut(['success'=>false,'message'=>'Goal id required.'], 400);
    $db->prepare('DELETE FROM goals WHERE id=? AND user_id=?')->execute([$id, $userId]);
    jsonOut(['success'=>true,'message'=>'Goal deleted.']);
}

jsonOut(['success'=>false,'message'=>'Method not allowed.'], 405);
