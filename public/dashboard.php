<?php
require __DIR__ . '/../src/helpers/auth.php';
requireLogin();
$user = currentUser();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Dashboard — Mini Blog</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="card">
      <div class="card-body">
        <h4>Welcome, <?=htmlspecialchars($user['username'] ?? $user['email'])?></h4>
        <p>Full name: <?=htmlspecialchars($user['full_name'] ?? '-')?></p>

        <a class="btn btn-primary" href="logout.php">Logout</a>
        <a class="btn btn-outline-secondary" href="index.php">View public site</a>
      </div>
    </div>
  </div>
</body>
</html>
