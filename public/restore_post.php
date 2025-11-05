<?php
require __DIR__ . '/../src/helpers/auth.php';
requireLogin();
$pdo = require __DIR__ . '/../src/helpers/db.php';

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? 'draft';

$stmt = $pdo->prepare("SELECT * FROM blogPost WHERE id = :id AND user_id = :uid LIMIT 1");
$stmt->execute(['id' => $id, 'uid' => currentUser()['id']]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(403);
    echo "Not allowed or post not found.";
    exit;
}

$newStatus = ($action === 'publish') ? 'published' : 'draft';
$update = $pdo->prepare("UPDATE blogPost SET is_deleted = 0, status = :status, updated_at = NOW() WHERE id = :id");
$update->execute(['status' => $newStatus, 'id' => $id]);

header('Location: profile.php');
exit;
