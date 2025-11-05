<?php
require __DIR__ . '/../src/helpers/auth.php';
requireLogin();
$pdo = require __DIR__ . '/../src/helpers/db.php';
require __DIR__ . '/../src/helpers/slugify.php';
require __DIR__ . '/../src/helpers/markdown.php';

// Initialize default values to avoid undefined variable warnings
$title = '';
$excerpt = '';
$content = '';
$status = 'draft';
$bg_color = '#ffffff';

$old = ['title' => '', 'excerpt' => '', 'content' => '', 'status' => 'draft', 'bg_color' => '#ffffff'];
$errors = [];


$errors = [];
$bg_color = $_POST['bg_color'] ?? '#ffffff';
$old = ['title'=>$title, 'excerpt'=>$excerpt, 'content'=>$content, 'status'=>$status, 'bg_color'=>$bg_color];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $action = $_POST['action'] ?? 'draft';
$status = ($action === 'publish') ? 'published' : 'draft';


    $old = ['title'=>$title, 'excerpt'=>$excerpt, 'content'=>$content, 'status'=>$status];

    if ($title === '' || strlen($title) < 3) {
        $errors[] = "Please provide a title (min 3 characters).";
    }
    if ($content === '' || strlen($content) < 10) {
        $errors[] = "Please provide content (min 10 characters).";
    }

    // handle image upload if present
    $featuredImagePath = null;
    if (!empty($_FILES['featured_image']) && $_FILES['featured_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowed = ['image/jpeg','image/png','image/gif'];
        $file = $_FILES['featured_image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading image.";
        } elseif ($file['size'] > (2 * 1024 * 1024)) {
            $errors[] = "Image must be under 2MB.";
        } elseif (!in_array(mime_content_type($file['tmp_name']), $allowed)) {
            $errors[] = "Only JPG, PNG, GIF images allowed.";
        } else {
            // safe unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safe = bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = __DIR__ . '/../uploads/' . $safe;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                $errors[] = "Failed to move uploaded file.";
            } else {
                $featuredImagePath = 'uploads/' . $safe;
            }
        }
    }

    if (empty($errors)) {
        // create slug and ensure uniqueness
        $slugBase = slugify($title);
        $slug = $slugBase;
        $i = 1;
        while (true) {
            $stmt = $pdo->prepare("SELECT id FROM blogPost WHERE slug = :s LIMIT 1");
            $stmt->execute(['s'=>$slug]);
            if (!$stmt->fetch()) break;
            $slug = $slugBase . '-' . $i;
            $i++;
        }

       $stmt = $pdo->prepare("INSERT INTO blogPost (user_id, title, slug, content, excerpt, featured_image, bg_color, status, created_at) VALUES (:uid, :title, :slug, :content, :excerpt, :fi, :bg, :status, NOW())");
$stmt->execute([
  'uid' => currentUser()['id'],
  'title' => $title,
  'slug' => $slug,
  'content' => $content,
  'excerpt' => $excerpt ?: null,
  'fi' => $featuredImagePath,
  'bg' => $bg_color,
  'status' => $status
]);

        header('Location: posts.php');
        exit;
    }
}

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>New Post — Mini Blog</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- EasyMDE Markdown Editor -->
<link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
<script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>

</head>
<body class="bg-light">
<?php include __DIR__ . '/../src/partials/navbar.php'; ?>

<div class="container py-4">
  <a href="posts.php" class="btn btn-link">&larr; Back</a>
  <h2>New Post</h2>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul><?php foreach ($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <div class="mb-3">
      <label class="form-label">Title</label>
      <input name="title" class="form-control" value="<?=htmlspecialchars($old['title'])?>">
    </div>
    <div class="mb-3">
  <label class="form-label">Genre</label>
  <input name="excerpt" class="form-control" placeholder="e.g., Technology, Travel, Food" value="<?=htmlspecialchars($old['excerpt'])?>">
  <div class="form-text">Specify the category or genre of your post.</div>
</div>

    <div class="mb-3">
  <label class="form-label">Content</label>
  <textarea id="content-editor" name="content" rows="10" class="form-control"><?=htmlspecialchars($old['content'])?></textarea>
</div>

    <div class="mb-3">
      <label class="form-label">Featured Image (optional, max 2MB)</label>
      <input type="file" name="featured_image" class="form-control" accept="image/*">
    </div>

    <div class="mb-3">
  <label class="form-label d-block">Background Color</label>
  <div class="d-flex flex-wrap gap-2">
    <?php
    $colors = ['#ffffff', '#f8f9fa', '#fff3cd', '#e0f7fa', '#e8f5e9', '#fce4ec', '#e3f2fd', '#f3e5f5'];
    foreach ($colors as $c):
    ?>
      <span 
        class="color-circle <?= (($old['bg_color'] ?? '#ffffff') === $c) ? 'selected' : '' ?>" 
        data-color="<?= $c ?>" 
        style="display:inline-block; width:30px; height:30px; border-radius:50%; background:<?= $c ?>; border:2px solid <?= (($old['bg_color'] ?? '#ffffff') === $c) ? '#007bff' : '#ccc' ?>; cursor:pointer;">
      </span>
    <?php endforeach; ?>
    <input type="hidden" name="bg_color" id="bg_color" value="<?= htmlspecialchars($old['bg_color'] ?? '#ffffff') ?>">
  </div>
  <div class="form-text">Click a color circle to choose your background.</div>
</div>

<script>
document.querySelectorAll('.color-circle').forEach(circle => {
  circle.addEventListener('click', () => {
    document.querySelectorAll('.color-circle').forEach(c => c.style.border = '2px solid #ccc');
    circle.style.border = '2px solid #007bff';
    document.getElementById('bg_color').value = circle.dataset.color;
  });
});
</script>


    <div class="d-flex gap-2">
  <button type="submit" name="action" value="draft" class="btn btn-secondary"> Save as Draft</button>
  <button type="submit" name="action" value="publish" class="btn btn-primary"> Publish</button>
</div>

  </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const easyMDE = new EasyMDE({
    element: document.getElementById("content-editor"),
    spellChecker: false,
    autosave: {
      enabled: false
    },
    placeholder: "Write your post content here...",
    status: false,
    toolbar: [
      "bold", "italic", "heading", "|",
      "quote", "unordered-list", "ordered-list", "|",
      "link", "image", "code", "|",
      "preview", "side-by-side", "fullscreen", "|",
      "guide"
    ],
    renderingConfig: {
      singleLineBreaks: false,
      codeSyntaxHighlighting: true
    }
  });
});
</script>

</body>
</html>
