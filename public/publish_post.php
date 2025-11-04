<?php
require __DIR__ . '/../src/helpers/auth.php';
requireLogin();
$pdo = require __DIR__ . '/../src/helpers/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$token = $_POST['csrf_token'] ?? '';

if (empty($id) || empty($token) || !hash_equals(getCsrfToken() ?? '', $token)) {
    http_response_code(403);
    echo "Invalid request.";
    exit;
}

$stmt = $pdo->prepare("UPDATE blogPost SET status='published' WHERE id=:id AND user_id=:uid");
$stmt->execute(['id' => $id, 'uid' => currentUser()['id']]);

header('Location: profile.php');
exit;
