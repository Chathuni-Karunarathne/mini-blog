<?php
require __DIR__ . '/../src/helpers/auth.php';
$pdo = require __DIR__ . '/../src/helpers/db.php';
require __DIR__ . '/../src/helpers/slugify.php';
require __DIR__ . '/../src/helpers/markdown.php';

$stmt = $pdo->prepare("SELECT p.*, u.username FROM blogPost p JOIN user u ON p.user_id = u.id WHERE p.status = 'published' ORDER BY p.created_at DESC");
$stmt->execute();
$posts = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>All Posts — Mini Blog</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../src/partials/navbar.php'; ?>
<div class="container py-4">
  <div class="d-flex justify-content-between mb-3">
    <h1>Posts</h1>
    <?php if (isLoggedIn()): ?>
      <a class="btn btn-primary" href="new_post.php">New Post</a>
    <?php else: ?>
      <a class="btn btn-outline-primary" href="login.php">Login to write</a>
    <?php endif; ?>
  </div>

  <?php foreach ($posts as $p): ?>
    <div class="card mb-3">
      <div class="row g-0">
        <?php if ($p['featured_image']): ?>
        <div class="col-md-3">
          <img src="/mini-blog/<?=htmlspecialchars($p['featured_image'])?>" class="img-fluid rounded-start" alt="">
        </div>
        <?php endif; ?>
        <div class="col">
          <div class="card-body">
            <h5 class="card-title"><a href="view_post.php?slug=<?=urlencode($p['slug'])?>"><?=htmlspecialchars($p['title'])?></a></h5>
            <p class="card-text"><small class="text-muted">By <?=htmlspecialchars($p['username'])?> — <?=htmlspecialchars($p['created_at'])?></small></p>
            <?php if ($p['excerpt']): ?>
              <p class="card-text"><?=htmlspecialchars($p['excerpt'])?></p>
            <?php else: ?>
              <p class="card-text"><?=nl2br(htmlspecialchars(substr(strip_tags($p['content']), 0, 200)))?>...</p>
            <?php endif; ?>
            <a href="view_post.php?slug=<?=urlencode($p['slug'])?>" class="stretched-link"></a>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if (empty($posts)): ?>
    <div class="alert alert-info">No posts published yet.</div>
  <?php endif; ?>
</div>
</body>
</html>
