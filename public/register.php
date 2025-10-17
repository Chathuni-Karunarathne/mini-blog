<?php

require __DIR__ . '/../src/helpers/auth.php';
$pdo = require __DIR__ . '/../src/helpers/db.php'; // ✅ assign it to $pdo

$errors = [];
$old = ['username'=>'', 'email'=>'', 'full_name'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    $old = ['username'=>$username, 'email'=>$email, 'full_name'=>$full_name];

    // Basic validation
    if ($username === '' || !preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        $errors[] = "Username must be 3-50 chars and contain letters, numbers, or underscore.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($password !== $password_confirm) {
        $errors[] = "Passwords do not match.";
    }

    // Check uniqueness
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM user WHERE username = :u OR email = :e LIMIT 1");
        $stmt->execute(['u'=>$username, 'e'=>$email]);
        $exists = $stmt->fetch();
        if ($exists) {
            $errors[] = "Username or email already taken.";
        }
    }

    // Insert
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO user (username, email, password_hash, full_name) VALUES (:u, :e, :p, :f)");
        $stmt->execute(['u'=>$username, 'e'=>$email, 'p'=>$hash, 'f'=>$full_name]);
        header('Location: /mini-blog/public/login.php?registered=1');
        exit;
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Register — Mini Blog</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-body">
            <h3 class="card-title mb-3">Register</h3>

            <?php if (!empty($errors)): ?>
              <div class="alert alert-danger">
                <ul class="mb-0">
                  <?php foreach ($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <form method="post" novalidate>
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input name="username" class="form-control" value="<?=htmlspecialchars($old['username'])?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input name="email" type="email" class="form-control" value="<?=htmlspecialchars($old['email'])?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Full name (optional)</label>
                <input name="full_name" class="form-control" value="<?=htmlspecialchars($old['full_name'])?>">
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input name="password" type="password" class="form-control">
              </div>
              <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input name="password_confirm" type="password" class="form-control">
              </div>
              <div class="d-grid">
                <button class="btn btn-primary">Register</button>
              </div>
            </form>

            <hr>
            <p class="small mb-0">Already have an account? <a href="login.php">Log in</a></p>

          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
