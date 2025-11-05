<?php
require __DIR__ . '/../src/helpers/auth.php';
$pdo = require __DIR__ . '/../src/helpers/db.php';
require __DIR__ . '/../src/helpers/markdown.php';

// Get search and sort inputs
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'desc'; // newest first by default

// Base SQL
$sql = "SELECT p.*, u.username 
        FROM blogPost p 
        JOIN user u ON p.user_id = u.id 
        WHERE p.status = 'published'";

// If a search term is entered, match it against title, content, author, or category
$params = [];
if ($search !== '') {
    $sql .= " AND (
        p.title LIKE :term
        OR p.content LIKE :term
        OR p.excerpt LIKE :term
        OR u.username LIKE :term
        OR u.full_name LIKE :term
    )";
    $params['term'] = "%$search%";
}


// Sorting 
if ($sort === 'asc') {
    $sql .= " ORDER BY p.created_at ASC";
} elseif ($sort === 'desc') {
    $sql .= " ORDER BY p.created_at DESC";
} else {
    // "all" - show everything without order change
    $sql .= " ORDER BY p.id ASC";
}


$stmt = $pdo->prepare($sql);
$stmt->execute($params);
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

<?php if ($search): ?>
  <p class="text-muted mb-4">
    Showing results for <strong>"<?= htmlspecialchars($search) ?>"</strong>
  </p>
<?php endif; ?>

  
  </div>
  <div class="bg-white p-3 rounded shadow-sm mb-4">

  <?php if (isLoggedIn()): ?>
  <h2 class="text-muted mb-4">👋 Welcome, <strong><?= htmlspecialchars(currentUser()['username']) ?></strong>!</h2>
<?php endif; ?>

  <form method="get" class="row g-2 mb-4 align-items-center">

  <!-- Search bar with icon -->
  <div class="col-md-8">
    <div class="input-group">
  <input 
    type="text" 
    name="search" 
    class="form-control border-end-0" 
    placeholder="Search posts by title, content, author, or category..." 
    value="<?= htmlspecialchars($search) ?>"
  >
  <button 
    type="submit" 
    name="action" 
    value="search" 
    class="btn btn-outline-primary border-start-0"
    style="border-color: #ced4da; background-color: white;"
  >
    🔍
  </button>
</div>

  </div>

  <!-- Sorting dropdown -->
  <div class="col-md-4 text-end">
    <select name="sort" class="form-select w-auto d-inline-block ms-2" onchange="this.form.submit()">
      <option value="desc" <?= $sort === 'desc' ? 'selected' : '' ?>>Most Recent</option>
      <option value="asc" <?= $sort === 'asc' ? 'selected' : '' ?>>Oldest</option>
      <option value="all" <?= $sort === 'all' ? 'selected' : '' ?>>All Posts</option>
    </select>
  </div>

</form>
</div>

  <?php foreach ($posts as $p): ?>
    <div class="card mb-3" style="background: <?= htmlspecialchars($p['bg_color'] ?? '#ffffff') ?>;">
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
