<?php
require_once __DIR__ . '/../helpers/auth.php';
?>
<nav class="navbar navbar-expand-lg glass-navbar shadow-sm">
  <div class="container">
    <?php $current = basename($_SERVER['SCRIPT_NAME'] ?? ''); ?>
    <a class="navbar-brand" href="/mini-blog/public/posts.php">Wordora</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?= ($current === 'posts.php') ? 'active' : '' ?>" href="/mini-blog/public/posts.php">Home</a>
        </li>
        <?php if (isLoggedIn()): ?>
        <li class="nav-item">
          <a class="nav-link <?= ($current === 'new_post.php') ? 'active' : '' ?>" href="/mini-blog/public/new_post.php">New Post</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($current === 'profile.php') ? 'active' : '' ?>" href="/mini-blog/public/profile.php">Profile</a>
        </li>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav">
        <?php if (isLoggedIn()): ?>
          <li class="nav-item">
            <a class="btn btn-accent ms-auto" href="/mini-blog/public/logout.php">Logout</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="btn btn-accent btn-sm me-2" href="/mini-blog/public/login.php">Login</a>
          </li>
          <li class="nav-item">
            <a class="btn btn-accent btn-sm" href="/mini-blog/public/register.php">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
