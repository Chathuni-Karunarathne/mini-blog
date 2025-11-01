<?php
require __DIR__ . '/../src/helpers/auth.php';
requireLogin();
$pdo = require __DIR__ . '/../src/helpers/db.php';
$user = currentUser();

// Fetch user's posts — both published and drafts
$stmtPub = $pdo->prepare("SELECT * FROM blogPost WHERE user_id = :uid AND status = 'published' ORDER BY created_at DESC");
$stmtPub->execute(['uid' => $user['id']]);
$published = $stmtPub->fetchAll();

$stmtDraft = $pdo->prepare("SELECT * FROM blogPost WHERE user_id = :uid AND status = 'draft' ORDER BY created_at DESC");
$stmtDraft->execute(['uid' => $user['id']]);
$drafts = $stmtDraft->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Profile — Mini Blog</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../src/partials/navbar.php'; ?>

<div class="container py-5">
  <h2 class="mb-4">Your Profile</h2>

  <div class="card mb-4 p-4">
    <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
    <p><strong>Full Name:</strong> <?= htmlspecialchars($user['full_name'] ?? 'Not provided') ?></p>
  </div>

  <h4 class="mt-5 mb-3">Your Published Posts (<?= count($published) ?>)</h4>
  <?php if ($published): ?>
    <?php foreach ($published as $p): ?>
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title"><?= htmlspecialchars($p['title']) ?></h5>
          <p class="card-text text-muted"><?= htmlspecialchars($p['created_at']) ?></p>
          <a href="view_post.php?slug=<?= urlencode($p['slug']) ?>" class="btn btn-sm btn-outline-primary">View</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="alert alert-info">You haven’t published any posts yet.</div>
  <?php endif; ?>

  <h4 class="mt-5 mb-3">Your Drafts (<?= count($drafts) ?>)</h4>
  <?php if ($drafts): ?>
    <?php foreach ($drafts as $d): ?>
      <div class="card mb-3 border-warning">
        <div class="card-body">
          <h5 class="card-title"><?= htmlspecialchars($d['title']) ?></h5>
          <p class="card-text text-muted"><?= htmlspecialchars($d['created_at']) ?></p>
          <a href="edit_post.php?id=<?= urlencode($d['id']) ?>" class="btn btn-sm btn-outline-warning">Edit Draft</a>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="alert alert-secondary">No drafts saved.</div>
  <?php endif; ?>

</div>

</body>
</html>
