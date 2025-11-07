<?php
require __DIR__ . '/../src/helpers/auth.php';
$pdo = require __DIR__ . '/../src/helpers/db.php';
require __DIR__ . '/../src/helpers/markdown.php';

// Get search and sort inputs
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'desc'; // newest first by default

// Base SQL
$sql = "SELECT p.*, u.username FROM blogPost p JOIN user u ON p.user_id = u.id WHERE p.status = 'published' AND p.is_deleted = 0";


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
// Randomize order so postcards appear arranged randomly
if (!empty($posts)) {
  shuffle($posts);
}
?>


<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>All Posts — Mini Blog</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/mini-blog/public/assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600&display=swap" rel="stylesheet">
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
  <div class="card shadow-sm glass-card">
    <div class="card-body">

  <?php if (isLoggedIn()): ?>
  <h2 class="text-muted mb-4"><span class="icon icon--accent" aria-hidden="true">
  </span> Welcome, <strong><?= htmlspecialchars(currentUser()['username']) ?></strong>!</h2>
<?php endif; ?>

  <form method="get" class="row g-2 mb-4 align-items-center">

  <!-- Search bar with icon + elegant sort select -->
  <div class="col-12">
    <div class="search-bar">
      <div class="input-group">
        <input
          type="text"
          name="search"
          class="form-control search-input"
          placeholder="Search posts by title, content, author, or category..."
          value="<?= htmlspecialchars($search) ?>"
          aria-label="Search posts"
        >
        <button
          type="submit"
          name="action"
          value="search"
          class="btn search-btn"
          aria-label="Search"
        >
          <svg viewBox="0 0 24 24" class="icon" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
            <title>Search</title>
            <circle cx="11" cy="11" r="6" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></line>
          </svg>
        </button>
      </div>

      <select name="sort" class="form-select w-auto ms-2 sort-select" onchange="this.form.submit()" aria-label="Sort posts">
        <option value="desc" <?= $sort === 'desc' ? 'selected' : '' ?>>Most Recent</option>
        <option value="asc" <?= $sort === 'asc' ? 'selected' : '' ?>>Oldest</option>
        <option value="all" <?= $sort === 'all' ? 'selected' : '' ?>>All Posts</option>
      </select>
    </div>
  </div>

</form>

  <!-- Masonry-style postcard layout (no horizontal scrolling) -->
  <div class="masonry">
    <?php foreach ($posts as $p): ?>
      <?php
        // determine orientation for styling (portrait vs landscape) based on image dimensions
        $orientation = '';
        if (!empty($p['featured_image'])) {
          $imgPath = __DIR__ . '/../' . ltrim($p['featured_image'], '/');
          if (file_exists($imgPath)) {
            $sz = @getimagesize($imgPath);
            if ($sz && isset($sz[0], $sz[1])) {
              $orientation = ($sz[0] >= $sz[1]) ? 'landscape' : 'portrait';
            }
          }
        }
      ?>
      <div class="masonry-item <?= $orientation ?>">
        <div class="card mb-3 post-card" tabindex="0" style="background: <?= htmlspecialchars($p['bg_color'] ?? '#ffffff') ?>;">
    <svg class="border-loop" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
  <rect class="border-path" x="2" y="2" width="calc(100% - 4px)" height="calc(100% - 4px)" rx="16" ry="16" fill="none"></rect>
</svg>
          <?php if ($p['featured_image']): ?>
            <div class="card-img-top">
              <img loading="lazy" src="/mini-blog/<?=htmlspecialchars($p['featured_image'])?>" alt="<?=htmlspecialchars($p['title'])?>">
            </div>
          <?php endif; ?>
          <div class="card-body">
            <h5 class="card-title"><a href="view_post.php?slug=<?=urlencode($p['slug'])?>"><?=htmlspecialchars($p['title'])?></a></h5>
            <p class="card-text"><small class="text-muted">By <?=htmlspecialchars($p['username'])?> — <?=htmlspecialchars($p['created_at'])?></small></p>
            <?php if ($p['excerpt']): ?>
              <p class="card-text post-excerpt"><?=htmlspecialchars($p['excerpt'])?></p>
            <?php else: ?>
              <p class="card-text post-excerpt"><?=nl2br(htmlspecialchars(substr(strip_tags($p['content']), 0, 200)))?>...</p>
            <?php endif; ?>
            <a href="view_post.php?slug=<?=urlencode($p['slug'])?>" class="stretched-link"></a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if (empty($posts)): ?>
    <div class="alert alert-info">No posts published yet.</div>
  <?php endif; ?>
    </div>
  </div>

    <script>
    (function(){
      const viewport = document.querySelector('.carousel-viewport');
      if (!viewport) return;
      const track = viewport.querySelector('.carousel-track');
      const prev = document.querySelector('.carousel-btn.prev');
      const next = document.querySelector('.carousel-btn.next');

      // make sure cards are keyboard-focusable (for older browsers where tabindex on elements might be stripped)
      Array.from(track.querySelectorAll('.card')).forEach(c => { if(!c.hasAttribute('tabindex')) c.setAttribute('tabindex','0'); });

      const updateButtons = () => {
        const atStart = viewport.scrollLeft <= 5;
        const atEnd = Math.ceil(viewport.scrollLeft + viewport.clientWidth) >= track.scrollWidth - 5;
        prev.disabled = atStart;
        next.disabled = atEnd;
        prev.setAttribute('aria-disabled', atStart ? 'true' : 'false');
        next.setAttribute('aria-disabled', atEnd ? 'true' : 'false');
      };

      const pageScroll = () => viewport.clientWidth;

      const doScroll = (amount) => {
        viewport.scrollBy({ left: amount, behavior: 'smooth' });
        setTimeout(updateButtons, 450);
      };

      prev.addEventListener('click', () => doScroll(-pageScroll()));
      next.addEventListener('click', () => doScroll(pageScroll()));

      // Keyboard navigation: when viewport has focus, allow arrows to scroll; when a card has focus, Enter/Space opens the post
      viewport.setAttribute('tabindex','0');
      viewport.addEventListener('keydown', (ev) => {
        if (ev.key === 'ArrowRight') { ev.preventDefault(); doScroll(pageScroll()); }
        else if (ev.key === 'ArrowLeft') { ev.preventDefault(); doScroll(-pageScroll()); }
        else if (ev.key === 'Home') { ev.preventDefault(); viewport.scrollTo({ left: 0, behavior: 'smooth' }); setTimeout(updateButtons, 450); }
        else if (ev.key === 'End') { ev.preventDefault(); viewport.scrollTo({ left: track.scrollWidth, behavior: 'smooth' }); setTimeout(updateButtons, 450); }
      });

      // Allow Enter/Space on focused card to open the post (activate stretched link)
      track.addEventListener('keydown', (ev) => {
        const card = ev.target.closest('.card');
        if (!card) return;
        if (ev.key === 'Enter' || ev.key === ' ') {
          ev.preventDefault();
          const link = card.querySelector('a.stretched-link');
          if (link) window.location = link.getAttribute('href');
        }
        // left/right while focused on a card should move viewport to next/prev card
        if (ev.key === 'ArrowRight') { ev.preventDefault(); doScroll(pageScroll()); }
        if (ev.key === 'ArrowLeft') { ev.preventDefault(); doScroll(-pageScroll()); }
      });

      viewport.addEventListener('scroll', updateButtons);
      window.addEventListener('resize', updateButtons);
      updateButtons();
    })();
    </script>

    <script>
document.addEventListener('DOMContentLoaded', function() {
  // animate each card in sequence with a small stagger
  const cards = document.querySelectorAll('.masonry .post-card');
  cards.forEach((card, i) => {
    // small staggered delay so outline draws one after another
    setTimeout(() => card.classList.add('animate'), i * 110);
  });

  // optional: ensure hover still works after animation
});
</script>


  <?php include __DIR__ . '/../src/partials/footer.php'; ?>

  </body>
  </html>
