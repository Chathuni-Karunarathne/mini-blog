<?php
require __DIR__ . '/../src/helpers/auth.php';
$pdo = require __DIR__ . '/../src/helpers/db.php';

$errors = [];     

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username_or_email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usernameOrEmail === '' || $password === '') {
        $errors[] = "Please fill both fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM user WHERE username = :u OR email = :u LIMIT 1");
        $stmt->execute(['u' => $usernameOrEmail]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user);
            header('Location: /mini-blog/public/posts.php');
            exit;
        } else {
            $errors[] = "Invalid credentials.";
        }
    }
}

$justRegistered = isset($_GET['registered']);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login — Mini Blog</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/mini-blog/public/assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600&display=swap" rel="stylesheet">
  <link rel="icon" type="image/x-icon" href="/mini-blog/favicon.ICO">
</head>
<body class="auth-animated">
  <main class="page-content d-flex align-items-center justify-content-center">
    <div class="container py-5">
      <div class="row justify-content-center w-100">
        <div class="col-md-5">
          <div class="card shadow-sm glass-card">
            <div class="card-body">
              <h2 class="text-center mb-2 fw-bold">Hello again</h2>
              <p class="text-center text-muted mb-3">Welcome back — sign in to continue.</p>
          

              <?php if ($justRegistered): ?>
                <div class="alert alert-success" role="status">Registration successful! Please log in.</div>
              <?php endif; ?>

              <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert" aria-live="assertive">
                  <ul class="mb-0">
                    <?php foreach ($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>

              <form method="post" autocomplete="on">
                <div class="mb-3">
                  <label for="username_or_email" class="form-label">Username or Email</label>
                  <input id="username_or_email" name="username_or_email" class="form-control" value="<?=htmlspecialchars($_POST['username_or_email'] ?? '')?>" required autofocus autocomplete="username">
                </div>
                <div class="mb-3">
                  <label for="password" class="form-label">Password</label>
                  <input id="password" name="password" type="password" class="form-control" required autocomplete="current-password">
                </div>
                <div class="d-grid">
                  <button type="submit" class="btn btn-accent">Login</button>
                </div>
              </form>

              <hr>
              <p class="small mb-0">Don't have an account? <a href="register.php">Register</a></p>

            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>
</body>
</html>
