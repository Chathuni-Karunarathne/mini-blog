<?php
require __DIR__ . '/../src/helpers/auth.php';
requireLogin();
$pdo = require __DIR__ . '/../src/helpers/db.php';
require __DIR__ . '/../src/helpers/slugify.php';
require __DIR__ . '/../src/helpers/markdown.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo "Invalid post id"; exit;
}

$stmt = $pdo->prepare("SELECT * FROM blogPost WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $id]);
$post = $stmt->fetch();
if (!$post) {
    echo "Post not found"; exit;
}
if ($post['user_id'] != currentUser()['id']) {
    http_response_code(403); echo "Not allowed"; exit;
}

$errors = [];
$old = $post;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

    if ($title === '' || strlen($title) < 3) $errors[] = "Title required (3+ chars).";
    if ($content === '' || strlen($content) < 10) $errors[] = "Content required (10+ chars).";

    // handle image upload replacement
    $featuredImagePath = $post['featured_image'];
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
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safe = bin2hex(random_bytes(8)) . '.' . $ext;
            $dest = __DIR__ . '/../uploads/' . $safe;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                $errors[] = "Failed to move uploaded file.";
            } else {
                // optionally delete old image
                if ($featuredImagePath && file_exists(__DIR__ . '/../' . $featuredImagePath)) {
                    @unlink(__DIR__ . '/../' . $featuredImagePath);
                }
                $featuredImagePath = 'uploads/' . $safe;
            }
        }
    }

    if (empty($errors)) {
        // update slug if title changed
        $slug = $post['slug'];
        if ($title !== $post['title']) {
            $slugBase = slugify($title);
            $slug = $slugBase;
            $i = 1;
            while (true) {
                $stmt = $pdo->prepare("SELECT id FROM blogPost WHERE slug = :s AND id != :id LIMIT 1");
                $stmt->execute(['s'=>$slug, 'id'=>$post['id']]);
                if (!$stmt->fetch()) break;
                $slug = $slugBase . '-' . $i;
                $i++;
            }
        }

        $stmt = $pdo->prepare("UPDATE blogPost SET title=:title, slug=:slug, content=:content, excerpt=:excerpt, featured_image=:fi, status=:status, updated_at=NOW() WHERE id=:id");
        $stmt->execute([
            'title'=>$title,
            'slug'=>$slug,
            'content'=>$content,
            'excerpt'=>$excerpt ?: null,
            'fi'=>$featuredImagePath,
            'status'=>$status,
            'id'=>$post['id']
        ]);

        header('Location: view_post.php?slug=' . urlencode($slug));
        exit;
    }

    // re-fill $old for the form
    $old = ['title'=>$title, 'excerpt'=>$excerpt, 'content'=>$content, 'status'=>$status, 'featured_image'=>$featuredImagePath];
}

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Edit Post — Mini Blog</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../src/partials/navbar.php'; ?>
<div class="container py-4">
  <a href="posts.php" class="btn btn-link">&larr; Back</a>
  <h2>Edit Post</h2>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul><?php foreach ($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <div class="mb-3">
      <label class="form-label">Title</label>
      <input name="title" class="form-control" value="<?=htmlspecialchars($old['title'] ?? '')?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Excerpt</label>
      <textarea name="excerpt" class="form-control"><?=htmlspecialchars($old['excerpt'] ?? '')?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Content (Markdown)</label>
      <textarea name="content" rows="10" class="form-control"><?=htmlspecialchars($old['content'] ?? '')?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Featured Image (leave empty to keep current)</label>
      <?php if (!empty($old['featured_image'])): ?>
        <div class="mb-2"><img src="/mini-blog/<?=htmlspecialchars($old['featured_image'])?>" style="max-width:200px;"></div>
      <?php endif; ?>
      <input type="file" name="featured_image" class="form-control" accept="image/*">
    </div>

    <div class="mb-3">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="draft" <?= ($old['status'] ?? '')=='draft' ? 'selected':'' ?>>Draft</option>
        <option value="published" <?= ($old['status'] ?? '')=='published' ? 'selected':'' ?>>Published</option>
      </select>
    </div>

    <div><button class="btn btn-primary">Update</button></div>
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

