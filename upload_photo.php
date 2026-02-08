<?php
require "db.php";
require "auth.php";
require_login();

$target_user_id = current_user_id();
if (is_admin() && isset($_GET["user_id"])) $target_user_id = max(1, (int)$_GET["user_id"]);

$msg = "";
$msgType = ""; // ok | err

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  csrf_check();

  if (!isset($_FILES["photo"]) || $_FILES["photo"]["error"] !== UPLOAD_ERR_OK) {
    $msg = "Upload failed. Please choose a valid image file.";
    $msgType = "err";
  } else {
    $tmp = $_FILES["photo"]["tmp_name"];
    $mime = mime_content_type($tmp) ?: "";
    $allowed = ["image/jpeg","image/png","image/webp"];

    if (!in_array($mime, $allowed, true)) {
      $msg = "Only JPG, PNG, or WEBP images are allowed.";
      $msgType = "err";
    } else {
      $bytes = file_get_contents($tmp);
      if ($bytes === false || strlen($bytes) > 2 * 1024 * 1024) {
        $msg = "Image too large (max 2MB). Please upload a smaller image.";
        $msgType = "err";
      } else {
        $stmt = $conn->prepare(
          "INSERT INTO portfolio_profile (user_id, mime, image)
           VALUES (?,?,?)
           ON DUPLICATE KEY UPDATE mime=VALUES(mime), image=VALUES(image)"
        );
        $null = NULL;
        $stmt->bind_param("isb", $target_user_id, $mime, $null);
        $stmt->send_long_data(2, $bytes);
        $stmt->execute();
        $stmt->close();

        $msg = "Photo updated successfully.";
        $msgType = "ok";
      }
    }
  }
}

$qs = is_admin() ? ("?user_id=" . $target_user_id) : "";

/* ✅ FIX: get target username (admin editing others) */
$stmt = $conn->prepare("SELECT username FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$tu = $stmt->get_result()->fetch_assoc();
$stmt->close();
$target_username = $tu["username"] ?? ($_SESSION["username"] ?? "");

/* ✅ FIX: View Site should open edited user's portfolio */
$viewSiteUrl = "index.php?u=" . urlencode($target_username);

/* ✅ Preview (your profile_image.php supports both; we’ll use ?u= for consistency) */
$previewUrl = "profile_image.php?u=" . urlencode($target_username) . "&v=" . time();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Upload Photo • Portfolio CMS</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="topbar">
  <div class="wrap topbar-inner">
    <a class="brand" href="dashboard.php<?php echo $qs; ?>">
      <span class="brand-dot"></span>
      <span class="brand-text">Portfolio CMS</span>
    </a>

    <div class="topbar-right">
      <a class="pill" href="dashboard.php<?php echo $qs; ?>">← Back</a>
      <a class="pill pill-ghost" href="<?php echo htmlspecialchars($viewSiteUrl); ?>">View Site</a>
      <button class="pill pill-ghost" id="themeToggle" type="button">
        <span class="theme-label">Dark</span>
      </button>
    </div>
  </div>
</header>

<main class="admin-shell">
  <div>
    <div class="admin-page-title">Upload Photo</div>
  </div>

  <?php if($msg): ?>
    <div class="admin-alert <?php echo $msgType === "ok" ? "ok" : "err"; ?>">
      <?php echo htmlspecialchars($msg); ?>
    </div>
  <?php endif; ?>

  <section class="upload-grid">

    <!-- Preview -->
    <div class="preview-card">
      <div class="admin-cardx-head" style="margin-bottom:10px">
      </div>

      <div class="preview-frame">
        <img id="imgPreview" class="preview-img" src="<?php echo htmlspecialchars($previewUrl); ?>" alt="Preview"
             onerror="this.style.display='none'; document.getElementById('previewEmpty').style.display='block';">
        <div id="previewEmpty" class="preview-empty" style="display:none;">
          No photo uploaded yet.
        </div>
      </div>

      <div class="help">
      </div>
    </div>

    <!-- Upload box -->
    <div class="dropzone">
      <div class="drop-head">
        <div>
          <h3 class="drop-title">Choose a new photo</h3>
        </div>
        <div class="item-badge">Max 2MB</div>
      </div>

      <form method="post" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">

        <input id="photoInput" class="file-input" type="file" name="photo"
               accept="image/jpeg,image/png,image/webp" required>

        <div class="file-row">
          <label class="file-btn" for="photoInput">Select Image</label>
          <div class="file-name" id="fileName">No file selected</div>
        </div>

        <div class="help">
          Allowed types: <b>JPG</b>, <b>PNG</b>, <b>WEBP</b>
        </div>

        <div class="admin-actions-row">
          <button class="admin-btn" type="submit">Upload & Save</button>
        </div>
      </form>
    </div>

  </section>
</main>

<script src="theme.js"></script>
<script>
  (function(){
    const input = document.getElementById("photoInput");
    const fileName = document.getElementById("fileName");
    const img = document.getElementById("imgPreview");
    const empty = document.getElementById("previewEmpty");

    if(!input) return;

    input.addEventListener("change", () => {
      const f = input.files && input.files[0];
      if(!f){
        fileName.textContent = "No file selected";
        return;
      }
      fileName.textContent = f.name;

      // live preview before upload
      const url = URL.createObjectURL(f);
      img.style.display = "block";
      img.src = url;
      if(empty) empty.style.display = "none";
    });
  })();
</script>
</body>
</html>
