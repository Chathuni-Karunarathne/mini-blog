<?php
require __DIR__ . '/../src/helpers/auth.php';
requireLogin();
$pdo = require __DIR__ . '/../src/helpers/db.php';
$user = currentUser();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // --- Image upload ---
    $profileImagePath = $user['profile_image'] ?? null;
    if (!empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        $file = $_FILES['profile_image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading profile image.";
        } elseif ($file['size'] > (2 * 1024 * 1024)) {
            $errors[] = "Profile image must be under 2MB.";
        } elseif (!in_array(mime_content_type($file['tmp_name']), $allowed)) {
            $errors[] = "Only JPG, PNG, GIF images allowed.";
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safe = bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = __DIR__ . '/../uploads/profile_' . $safe;

            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                $errors[] = "Failed to move uploaded profile image.";
            } else {
                // remove old image if exists
                if ($profileImagePath && file_exists(__DIR__ . '/../' . $profileImagePath)) {
                    @unlink(__DIR__ . '/../' . $profileImagePath);
                }
                $profileImagePath = 'uploads/profile_' . $safe;
            }
        }
    }

    if (empty($errors)) {
        $params = [
            'full_name' => $full_name,
            'email' => $email,
            'profile_image' => $profileImagePath,
            'id' => $user['id'],
        ];
        $sql = "UPDATE user SET full_name=:full_name, email=:email, profile_image=:profile_image";

        // handle password update only if entered
        if (!empty($password)) {
            $sql .= ", password_hash=:password_hash";
            $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id=:id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // update session user data
        $stmt2 = $pdo->prepare("SELECT * FROM user WHERE id=:id");
        $stmt2->execute(['id' => $user['id']]);
        $updatedUser = $stmt2->fetch();
        loginUser($updatedUser);

        $success = "Profile updated successfully!";
        $user = $updatedUser;
    }
}

// fetch posts
$stmtPub = $pdo->prepare("SELECT * FROM blogPost WHERE user_id = :uid AND status='published' ORDER BY created_at DESC");
$stmtPub->execute(['uid' => $user['id']]);
$published = $stmtPub->fetchAll();

$stmtDraft = $pdo->prepare("SELECT * FROM blogPost WHERE user_id = :uid AND status='draft' ORDER BY created_at DESC");
$stmtDraft->execute(['uid' => $user['id']]);
$drafts = $stmtDraft->fetchAll();

// fetch deleted posts (soft-deleted)
$stmtDel = $pdo->prepare("SELECT * FROM blogPost WHERE user_id = :uid AND is_deleted = 1 ORDER BY updated_at DESC");
$stmtDel->execute(['uid' => $user['id']]);
$deleted = $stmtDel->fetchAll();
 

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Profile — Mini Blog</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/mini-blog/public/assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600&display=swap" rel="stylesheet">

</head>
<body class="bg-light">
<?php include __DIR__ . '/../src/partials/navbar.php'; ?>

<div class="container py-5">
  <h2>Your Profile</h2>

  <?php if ($errors): ?>
    <div class="alert alert-danger"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="row">
    <div class="col-md-4 text-center">
      <?php
$profileImage = !empty($user['profile_image'])
    ? '/mini-blog/' . htmlspecialchars($user['profile_image'])
    : '/mini-blog/public/assets/profile-pic.png';
?>
<img src="<?= $profileImage ?>" class="rounded-circle me-3 border shadow-sm" width="110" height="110" style="object-fit: cover;">
    </div>

    <div class="col-md-8">
      <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">New Password (leave blank to keep current)</label>
          <input type="password" name="password" class="form-control" placeholder="Enter new password">
        </div>
        <div class="mb-3">
          <label class="form-label">Profile Image</label>
          <input type="file" name="profile_image" class="form-control" accept="image/*">
        </div>
        <button class="btn btn-primary">Update Profile</button>
      </form>
    </div>
  </div>

  <hr class="my-5">

 <h4 class="mb-3">Published Posts (<?= count($published) ?>)</h4>
 <div class="mb-3"></div>
<?php if ($published): ?>
  <div class="d-flex flex-column gap-3">
    <?php foreach ($published as $p): ?>
      <div class="card shadow-sm">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($p['title']) ?></h5>
            <p class="card-text text-muted mb-2"><?= htmlspecialchars($p['excerpt'] ?: 'No category') ?></p>
            <div class="d-flex flex-wrap gap-2">
              <a href="view_post.php?slug=<?= urlencode($p['slug']) ?>" class="btn btn-sm btn-primary">View</a>
              <a href="edit_post.php?id=<?= urlencode($p['id']) ?>" class="btn btn-sm btn-warning text-white">Edit</a>
              <form method="post" action="delete_post.php" style="display:inline">
                <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(ensureCsrfToken()) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="alert alert-info">You have no published posts.</div>
<?php endif; ?>

<hr>
<h4 class="mt-4 mb-3">Drafts (<?= count($drafts) ?>)</h4>
<div class="mb-3"></div>
<?php if ($drafts): ?>
  <div class="d-flex flex-column gap-3">
    <?php foreach ($drafts as $d): ?>
      <div class="card shadow-sm">
        <div class="card shadow-sm border-warning">
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($d['title']) ?></h5>
            <p class="card-text text-muted mb-2"><?= htmlspecialchars($d['excerpt'] ?: 'No category') ?></p>
            <div class="d-flex flex-wrap gap-2">
              <a href="edit_post.php?id=<?= urlencode($d['id']) ?>" class="btn btn-sm btn-warning text-white">Edit</a>
              <form method="post" action="publish_post.php" style="display:inline">
                <input type="hidden" name="id" value="<?= htmlspecialchars($d['id']) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(ensureCsrfToken()) ?>">
                <button type="submit" class="btn btn-sm btn-success">Publish</button>
              </form>
              <form method="post" action="delete_post.php" style="display:inline">
                <input type="hidden" name="id" value="<?= htmlspecialchars($d['id']) ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(ensureCsrfToken()) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="alert alert-secondary">No drafts yet.</div>
<?php endif; ?>
<hr>
<h4 class="mt-4 text-danger">Deleted Posts (<?= count($deleted) ?>)</h4>
<div class="mb-3"></div>
<?php if (!empty($deleted)): ?>
  <div class="d-flex flex-column gap-3">
    <?php foreach ($deleted as $d): ?>
      <div class="card shadow-sm">
        <div class="card shadow-sm border-danger">
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($d['title']) ?></h5>
            <p class="card-text text-muted mb-2">
              <?= htmlspecialchars($d['excerpt'] ?: 'No category') ?><br>
              <small>Deleted on: <?= htmlspecialchars($d['updated_at'] ?? $d['created_at']) ?></small>
            </p>
            <div class="d-flex flex-wrap gap-2">
              <form method="post" action="restore_post.php" class="d-inline">
                <input type="hidden" name="id" value="<?= htmlspecialchars($d['id']) ?>">
                <input type="hidden" name="action" value="publish">
                <button type="submit" class="btn btn-sm btn-success">Restore as Published</button>
              </form>
              <form method="post" action="restore_post.php" class="d-inline">
                <input type="hidden" name="id" value="<?= htmlspecialchars($d['id']) ?>">
                <input type="hidden" name="action" value="draft">
                <button type="submit" class="btn btn-sm btn-warning text-white">Restore as Draft</button>
              </form>
              <form method="post" action="permanent_delete.php" class="d-inline" onsubmit="return confirm('Permanently delete this post?');">
                <input type="hidden" name="id" value="<?= htmlspecialchars($d['id']) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete Permanently</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <p class="text-muted">No deleted posts.</p>
<?php endif; ?>
</div>
</body>
</html>

<?php include __DIR__ . '/../src/partials/footer.php'; ?>
