<?php
require __DIR__ . '/../src/helpers/auth.php';
$pdo = require __DIR__ . '/../src/helpers/db.php';
require __DIR__ . '/../src/helpers/markdown.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    http_response_code(404);
    echo "Post not found";
    exit;
}

$stmt = $pdo->prepare("SELECT p.*, u.username FROM blogPost p JOIN user u ON p.user_id = u.id WHERE p.slug = :s LIMIT 1");
$stmt->execute(['s' => $slug]);
$post = $stmt->fetch();

if (!$post || $post['status'] !== 'published') {
    // allow owner to view drafts if desired (optional)
    if (!isLoggedIn() || (currentUser()['id'] ?? 0) != $post['user_id']) {
        http_response_code(404);
        echo "Post not found or not published.";
        exit;
    }
}

// increment view count
$update = $pdo->prepare("UPDATE blogPost SET views = views + 1 WHERE id = :id");
$update->execute(['id' => $post['id']]);

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?=htmlspecialchars($post['title'])?> — Mini Blog</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <a class="btn btn-link" href="posts.php">&larr; Back to posts</a>

  <div class="card">
    <?php if ($post['featured_image']): ?>
      <img src="/mini-blog/<?=htmlspecialchars($post['featured_image'])?>" class="card-img-top" alt="">
    <?php endif; ?>
    <div class="card-body">
      <h1 class="card-title"><?=htmlspecialchars($post['title'])?></h1>
      <p class="text-muted">By <?=htmlspecialchars($post['username'])?> — <?=htmlspecialchars($post['created_at'])?> — Views: <?=htmlspecialchars($post['views'])?></p>
      <hr>
      <div class="post-content">
        <?= render_markdown($post['content']) ?>
      </div>

      <?php if (isLoggedIn() && (currentUser()['id'] ?? 0) == $post['user_id']): ?>
        <hr>
        <a class="btn btn-sm btn-outline-primary" href="edit_post.php?id=<?=urlencode($post['id'])?>">Edit</a>
        <a class="btn btn-sm btn-danger" href="delete_post.php?id=<?=urlencode($post['id'])?>" onclick="return confirm('Delete this post?');">Delete</a>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
