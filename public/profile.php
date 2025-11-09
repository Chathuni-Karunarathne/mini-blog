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

  // --- Image upload / removal ---
  $profileImagePath = $user['profile_image'] ?? null;

  // Handle explicit removal request (small inline form posts remove_profile_image=1)
  if (!empty($_POST['remove_profile_image'])) {
    if ($profileImagePath && file_exists(__DIR__ . '/../' . $profileImagePath)) {
      @unlink(__DIR__ . '/../' . $profileImagePath);
    }
    $profileImagePath = null;
  }
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

    // Prevent duplicate email unique constraint error: ensure email isn't used by another account
    if (!empty($email)) {
      $check = $pdo->prepare("SELECT id FROM user WHERE email = :email AND id != :id LIMIT 1");
      $check->execute(['email' => $email, 'id' => $user['id']]);
      if ($check->fetch()) {
        $errors[] = "That email is already registered to another account.";
      }
    }

    if (empty($errors)) {
      $stmt = $pdo->prepare($sql);
      $stmt->execute($params);
    }

        // update session user data
        $stmt2 = $pdo->prepare("SELECT * FROM user WHERE id=:id");
        $stmt2->execute(['id' => $user['id']]);
        $updatedUser = $stmt2->fetch();
        loginUser($updatedUser);

        $success = "Profile updated successfully!";
        $user = $updatedUser;
    }
}

// If the form was submitted but had errors, prefer submitted values for the form
$form_full_name = $_POST['full_name'] ?? $user['full_name'] ?? '';
$form_email = $_POST['email'] ?? $user['email'] ?? '';
$form_profile_image = $user['profile_image'] ?? null;

// Decide which profile image to display: prefer any updated/removed value from this request
$displayProfilePath = $profileImagePath ?? $form_profile_image ?? $user['profile_image'] ?? null;

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
  <link rel="icon" type="image/x-icon" href="/mini-blog/favicon.ICO">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../src/partials/navbar.php'; ?>

<div class="container py-5">
  
  <?php if ($errors): ?>
    <div class="alert alert-danger" role="alert" aria-live="assertive"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success" role="status"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
  <div class="row align-items-center">
        <div class="col-md-4 text-center">
      <?php
$profileImage = !empty($displayProfilePath)
  ? '/mini-blog/' . htmlspecialchars($displayProfilePath)
  : '/mini-blog/public/assets/profile-pic.jpg';
?>
  <div class="profile-avatar-wrap" aria-hidden="true">
    <img src="<?= $profileImage ?>" class="profile-avatar" alt="Profile picture">
  </div>
    </div>

    <div class="col-md-8">
      <form method="post" enctype="multipart/form-data" autocomplete="on" role="form">
        <div class="mb-3">
          <label for="full_name" class="form-label">Full name</label>
          <input id="full_name" type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($form_full_name) ?>">
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input id="email" type="email" name="email" class="form-control" value="<?= htmlspecialchars($form_email) ?>" required autocomplete="email">
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">New password <small class="text-muted">(leave blank to keep current)</small></label>
          <input id="password" type="password" name="password" class="form-control" placeholder="Enter new password" autocomplete="new-password">
        </div>
        <div class="mb-3">
          <label for="profile_image" class="form-label">Profile image</label>
          <div class="d-flex align-items-center gap-2">
            <div style="flex:1;">
              <input id="profile_image" type="file" name="profile_image" class="form-control" accept="image/*">
            </div>
            <?php if (!empty($form_profile_image)): ?>
              <div class="d-flex gap-2">
                <button type="submit" name="remove_profile_image" value="1" class="btn btn-sm btn-outline-secondary">Remove photo</button>
                <a href="<?= htmlspecialchars($profileImage) ?>" target="_blank" class="btn btn-sm btn-outline-primary">View full</a>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-cream">Update profile</button>
        </div>
      </form>
        </div>
      </div>
    </div>
  </div>

  <hr class="my-5">

  <!-- Profile posts card with tabs attached to the top (Google-style) -->
  <div class="card shadow-sm profile-card">
    <div class="card-body p-3">
      <div class="profile-tabs" role="tablist" aria-label="Profile posts">
        <button type="button" role="tab" aria-selected="true" aria-controls="panel-published" class="tab active" id="tab-published">Published <span class="count"><?= count($published) ?></span></button>
        <button type="button" role="tab" aria-selected="false" aria-controls="panel-drafts" class="tab" id="tab-drafts">Drafts <span class="count"><?= count($drafts) ?></span></button>
        <button type="button" role="tab" aria-selected="false" aria-controls="panel-deleted" class="tab" id="tab-deleted">Deleted <span class="count"><?= count($deleted) ?></span></button>
      </div>

      <div class="profile-panels-wrap">
        <div id="panel-published" class="tab-panel" role="tabpanel" aria-labelledby="tab-published">
      <h4 class="mb-3">Published Posts (<?= count($published) ?>)</h4>
      <div class="mb-3"></div>
      <?php if ($published): ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ($published as $p): ?>
            <div class="card shadow-sm">
              <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($p['title']) ?></h5>
                <p class="card-text text-muted mb-2"><?= htmlspecialchars($p['excerpt'] ?: 'No category') ?></p>
                <div class="d-flex flex-wrap gap-2">
                  <a href="view_post.php?slug=<?= urlencode($p['slug']) ?>" class="btn btn-sm btn-primary">View</a>
                  <a href="edit_post.php?id=<?= urlencode($p['id']) ?>" class="btn btn-sm btn-cream">Edit</a>
                  <form method="post" action="delete_post.php" style="display:inline">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(ensureCsrfToken()) ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-info">You have no published posts.</div>
      <?php endif; ?>
    </div>

    <div id="panel-drafts" class="tab-panel" role="tabpanel" aria-labelledby="tab-drafts" hidden>
      <h4 class="mt-4 mb-3">Drafts (<?= count($drafts) ?>)</h4>
      <div class="mb-3"></div>
      <?php if ($drafts): ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ($drafts as $d): ?>
            <div class="card shadow-sm border-warning">
              <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($d['title']) ?></h5>
                <p class="card-text text-muted mb-2"><?= htmlspecialchars($d['excerpt'] ?: 'No category') ?></p>
                <div class="d-flex flex-wrap gap-2">
                  <a href="edit_post.php?id=<?= urlencode($d['id']) ?>" class="btn btn-sm btn-cream">Edit</a>
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
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-secondary">No drafts yet.</div>
      <?php endif; ?>
    </div>

    <div id="panel-deleted" class="tab-panel" role="tabpanel" aria-labelledby="tab-deleted" hidden>
      <h4 class="mt-4 text-danger">Deleted Posts (<?= count($deleted) ?>)</h4>
      <div class="mb-3"></div>
      <?php if (!empty($deleted)): ?>
        <div class="d-flex flex-column gap-3">
          <?php foreach ($deleted as $d): ?>
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
                    <button type="submit" class="btn btn-sm btn-cream">Restore as Draft</button>
                  </form>
                  <form method="post" action="permanent_delete.php" class="d-inline" onsubmit="return confirm('Permanently delete this post?');">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($d['id']) ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Delete Permanently</button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-muted">No deleted posts.</p>
      <?php endif; ?>
    </div>
      </div>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function(){
    const tabs = Array.from(document.querySelectorAll('.profile-tabs .tab'));
    const panels = Array.from(document.querySelectorAll('.tab-panel'));
    function activateTab(tab){
      tabs.forEach(t=>{ t.classList.remove('active'); t.setAttribute('aria-selected', 'false'); });
      panels.forEach(p=> p.hidden = true);
      tab.classList.add('active');
      tab.setAttribute('aria-selected', 'true');
      const panel = document.getElementById(tab.getAttribute('aria-controls'));
      if(panel) panel.hidden = false;
      tab.focus();
    }
    tabs.forEach((tab, idx)=>{
      tab.addEventListener('click', ()=> activateTab(tab));
      tab.addEventListener('keydown', (e)=>{
        if(e.key === 'ArrowRight' || e.key === 'ArrowLeft'){
          const dir = e.key === 'ArrowRight' ? 1 : -1;
          const next = tabs[(idx + dir + tabs.length) % tabs.length];
          next.focus();
        }
        if(e.key === 'Enter' || e.key === ' '){ activateTab(tab); e.preventDefault(); }
      });
    });
  });
  </script>
</div>
</body>
</html>

<?php include __DIR__ . '/../src/partials/footer.php'; ?>
