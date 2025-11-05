<?php
require __DIR__ . '/../src/helpers/auth.php';
requireLogin();
$pdo = require __DIR__ . '/../src/helpers/db.php';

$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM blogPost WHERE id = :id AND user_id = :uid LIMIT 1");
$stmt->execute(['id' => $id, 'uid' => currentUser()['id']]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(403);
    echo "Not allowed or post not found.";
    exit;
}

// Permanently delete post and image if exists
if ($post['featured_image'] && file_exists(__DIR__ . '/../' . $post['featured_image'])) {
    @unlink(__DIR__ . '/../' . $post['featured_image']);
}

$del = $pdo->prepare("DELETE FROM blogPost WHERE id = :id");
$del->execute(['id' => $id]);

header('Location: profile.php');
exit;
