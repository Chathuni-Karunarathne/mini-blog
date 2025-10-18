<?php
require __DIR__ . '/../src/helpers/auth.php';
requireLogin();
$pdo = require __DIR__ . '/../src/helpers/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: posts.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM blogPost WHERE id = :id LIMIT 1");
$stmt->execute(['id'=>$id]);
$post = $stmt->fetch();
if (!$post) { header('Location: posts.php'); exit; }

if ($post['user_id'] != currentUser()['id']) {
    http_response_code(403); echo "Not allowed"; exit;
}

// delete featured image file if exists
if ($post['featured_image'] && file_exists(__DIR__ . '/../' . $post['featured_image'])) {
    @unlink(__DIR__ . '/../' . $post['featured_image']);
}

// delete row
$del = $pdo->prepare("DELETE FROM blogPost WHERE id = :id");
$del->execute(['id'=>$id]);

header('Location: posts.php');
exit;
