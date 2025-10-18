<?php
require __DIR__ . '/../src/helpers/auth.php';
requireLogin();
$pdo = require __DIR__ . '/../src/helpers/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: posts.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$token = $_POST['csrf_token'] ?? '';

if (!$id) { header('Location: posts.php'); exit; }

if (empty($token) || !hash_equals(getCsrfToken() ?? '', $token)) {
    http_response_code(403);
    echo "Invalid CSRF token.";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM blogPost WHERE id = :id LIMIT 1");
$stmt->execute(['id'=>$id]);
$post = $stmt->fetch();
if (!$post) { header('Location: posts.php'); exit; }

if ($post['user_id'] != currentUser()['id']) {
    http_response_code(403);
    echo "Not allowed";
    exit;
}

if ($post['featured_image'] && file_exists(__DIR__ . '/../' . $post['featured_image'])) {
    @unlink(__DIR__ . '/../' . $post['featured_image']);
}

$del = $pdo->prepare("DELETE FROM blogPost WHERE id = :id");
$del->execute(['id'=>$id]);

header('Location: posts.php');
exit;
