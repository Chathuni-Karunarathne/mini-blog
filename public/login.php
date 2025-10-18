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
            header('Location: /mini-blog/public/dashboard.php');
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
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card shadow-sm">
          <div class="card-body">
            <h3 class="card-title mb-3">Login</h3>

            <?php if ($justRegistered): ?>
              <div class="alert alert-success">Registration successful! Please log in.</div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
              <div class="alert alert-danger">
                <ul class="mb-0">
                  <?php foreach ($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <form method="post">
              <div class="mb-3">
                <label class="form-label">Username or Email</label>
                <input name="username_or_email" class="form-control">
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input name="password" type="password" class="form-control">
              </div>
              <div class="d-grid">
                <button class="btn btn-primary">Login</button>
              </div>
            </form>

            <hr>
            <p class="small mb-0">Don't have an account? <a href="register.php">Register</a></p>

          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
