<?php
require __DIR__ . '/../src/helpers/auth.php';
requireLogin();
$pdo = require __DIR__ . '/../src/helpers/db.php';
require __DIR__ . '/../src/helpers/slugify.php';
require __DIR__ . '/../src/helpers/markdown.php';

$errors = [];
$old = ['title'=>'', 'excerpt'=>'', 'content'=>'', 'status'=>'draft'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

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

        $stmt = $pdo->prepare("INSERT INTO blogPost (user_id, title, slug, content, excerpt, featured_image, status, created_at) VALUES (:uid, :title, :slug, :content, :excerpt, :fi, :status, NOW())");
        $stmt->execute([
            'uid' => currentUser()['id'],
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'excerpt' => $excerpt ?: null,
            'fi' => $featuredImagePath,
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
      <label class="form-label">Excerpt (optional)</label>
      <textarea name="excerpt" class="form-control"><?=htmlspecialchars($old['excerpt'])?></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Content (Markdown)</label>
      <textarea name="content" rows="10" class="form-control"><?=htmlspecialchars($old['content'])?></textarea>
      <div class="form-text">You can use Markdown. It will be rendered on the post view.</div>
    </div>
    <div class="mb-3">
      <label class="form-label">Featured Image (optional, max 2MB)</label>
      <input type="file" name="featured_image" class="form-control" accept="image/*">
    </div>
    <div class="mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="draft" <?= $old['status']=='draft' ? 'selected':'' ?>>Draft</option>
        <option value="published" <?= $old['status']=='published' ? 'selected':'' ?>>Published</option>
      </select>
    </div>
    <div>
      <button class="btn btn-primary">Save</button>
    </div>
  </form>
</div>
<script>
  // simple live markdown preview using a minimal approach:
  const textarea = document.querySelector('textarea[name="content"]');
  if (textarea) {
    const preview = document.createElement('div');
    preview.innerHTML = '<h5>Preview</h5><div id="md-preview" style="border:1px solid #e3e3e3; padding:10px; background:#fff;"></div>';
    textarea.parentNode.appendChild(preview);
    const mdPreview = document.getElementById('md-preview');

    function escapeHtml(str) {
      return str.replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; });
    }

    function simpleMarkdownToHtml(text) {
      // very lightweight conversions — headings, bold, italic, code, line breaks
      let s = escapeHtml(text);
      s = s.replace(/^\s*###### (.*$)/gim, '<h6>$1</h6>');
      s = s.replace(/^\s*##### (.*$)/gim, '<h5>$1</h5>');
      s = s.replace(/^\s*#### (.*$)/gim, '<h4>$1</h4>');
      s = s.replace(/^\s*### (.*$)/gim, '<h3>$1</h3>');
      s = s.replace(/^\s*## (.*$)/gim, '<h2>$1</h2>');
      s = s.replace(/^\s*# (.*$)/gim, '<h1>$1</h1>');
      s = s.replace(/\*\*(.*?)\*\*/gim, '<strong>$1</strong>');
      s = s.replace(/\*(.*?)\*/gim, '<em>$1</em>');
      s = s.replace(/`([^`]+)`/gim, '<code>$1</code>');
      s = s.replace(/\n/g, '<br>');
      return s;
    }

    textarea.addEventListener('input', () => {
      mdPreview.innerHTML = simpleMarkdownToHtml(textarea.value);
    });
    // initialize
    mdPreview.innerHTML = simpleMarkdownToHtml(textarea.value);
  }
</script>

</body>
</html>
