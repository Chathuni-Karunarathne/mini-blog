<?php
require_once __DIR__ . '/../helpers/auth.php';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/mini-blog/public/posts.php">Mini Blog</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="/mini-blog/public/posts.php">Home</a>
        </li>
        <?php if (isLoggedIn()): ?>
        <li class="nav-item">
          <a class="nav-link" href="/mini-blog/public/new_post.php">New Post</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/mini-blog/public/profile.php">Profile</a>
        </li>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav">
        <?php if (isLoggedIn()): ?>
          <li class="nav-item">
            <span class="navbar-text text-light me-3">
              <?= htmlspecialchars(currentUser()['username'] ?? 'User') ?>
            </span>
          </li>
          <li class="nav-item">
            <a class="btn btn-outline-light btn-sm" href="/mini-blog/public/logout.php">Logout</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="btn btn-outline-light btn-sm me-2" href="/mini-blog/public/login.php">Login</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-light btn-sm" href="/mini-blog/public/register.php">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
