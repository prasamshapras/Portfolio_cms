<?php
require "db.php";
require "auth.php";
require_login();

$target_user_id = current_user_id();
if (is_admin() && isset($_GET["user_id"])) $target_user_id = max(1, (int)$_GET["user_id"]);

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  csrf_check();
  $action = $_POST["action"] ?? "";

  if ($action === "create") {
    $title = trim($_POST["title"] ?? "");
    $desc  = trim($_POST["description"] ?? "");
    $tech  = trim($_POST["tech"] ?? "");
    $live  = clean_url((string)($_POST["live_url"] ?? ""));
    $gh    = clean_url((string)($_POST["github_url"] ?? ""));

    if ($title !== "" && $desc !== "") {
      $stmt = $conn->prepare("INSERT INTO portfolio_projects(user_id,title,description,tech,live_url,github_url) VALUES(?,?,?,?,?,?)");
      $stmt->bind_param("isssss", $target_user_id, $title, $desc, $tech, $live, $gh);
      $stmt->execute();
      $stmt->close();
      $msg = "Project added.";
    } else $msg = "Title and Description required.";
  }

  if ($action === "update") {
    $id    = (int)($_POST["id"] ?? 0);
    $title = trim($_POST["title"] ?? "");
    $desc  = trim($_POST["description"] ?? "");
    $tech  = trim($_POST["tech"] ?? "");

    if ($id > 0 && $title !== "" && $desc !== "") {
      $stmt = $conn->prepare("UPDATE portfolio_projects SET title=?, description=?, tech=? WHERE id=? AND user_id=?");
      $stmt->bind_param("sssii", $title, $desc, $tech, $id, $target_user_id);
      $stmt->execute();
      $stmt->close();
      $msg = "Updated.";
    } else $msg = "Invalid update.";
  }

  if ($action === "delete") {
    $id = (int)($_POST["id"] ?? 0);
    if ($id > 0) {
      $stmt = $conn->prepare("DELETE FROM portfolio_projects WHERE id=? AND user_id=?");
      $stmt->bind_param("ii", $id, $target_user_id);
      $stmt->execute();
      $stmt->close();
      $msg = "Deleted.";
    }
  }
}

$projects = [];
$stmt = $conn->prepare("SELECT * FROM portfolio_projects WHERE user_id=? ORDER BY id DESC");
$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $projects[] = $row;
$stmt->close();

$qs = is_admin() ? ("?user_id=" . $target_user_id) : "";

/* ✅ FIX: View Site should open edited user's portfolio */
$stmt = $conn->prepare("SELECT username FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$tu = $stmt->get_result()->fetch_assoc();
$stmt->close();
$target_username = $tu["username"] ?? ($_SESSION["username"] ?? "");
$viewSiteUrl = "index.php?u=" . urlencode($target_username);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage Projects • Portfolio CMS</title>
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
    <div class="admin-page-title">Manage Projects</div>
  </div>

  <?php if($msg): ?>
    <div class="admin-alert ok"><?php echo htmlspecialchars($msg); ?></div>
  <?php endif; ?>

  <section class="admin-layout">

    <!-- Add new -->
    <div class="admin-cardx">
      <div class="admin-cardx-head">
        <h3>Add New Project</h3>
      </div>

      <form method="post" class="admin-form">
        <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="action" value="create">

        <label class="admin-label">Title</label>
        <input class="admin-input" name="title" required>

        <div class="admin-row">
          <div>
            <label class="admin-label">Tech (comma separated)</label>
            <input class="admin-input" name="tech" placeholder="PHP, MySQL, JavaScript">
          </div>
       
        </div>

        <label class="admin-label">GitHub URL</label>
        <input class="admin-input" name="github_url" placeholder="https://github.com/...">

        <label class="admin-label">Description</label>
        <textarea class="admin-textarea" name="description" required></textarea>

        <div class="admin-actions-row">
          <button class="admin-btn" type="submit">Add Project</button>
        </div>
      </form>
    </div>

    <!-- Existing list -->
    <div class="admin-cardx">
      <div class="admin-cardx-head">
        <h3>Existing Projects</h3>
        <div class="muted"><?php echo count($projects); ?> total</div>
      </div>

      <?php if(count($projects) === 0): ?>
        <div class="panel" style="margin-top:10px">
          <b>No projects yet.</b><br>
          <span class="muted">Add your first project using the form on the left.</span>
        </div>
      <?php else: ?>
        <div class="item-list">
          <?php foreach($projects as $p): ?>
            <details class="item">
              <summary class="item-summary">
                <div>
                  <div class="item-title"><?php echo htmlspecialchars($p["title"]); ?></div>
                  <div class="item-meta">
                 <?php if (!empty($p["tech"])): ?>
                      • <?php echo htmlspecialchars($p["tech"]); ?>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="item-badge">Edit</div>
              </summary>

              <div class="item-body">
                <form method="post" class="admin-form">
                  <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">
                  <input type="hidden" name="id" value="<?php echo (int)$p["id"]; ?>">

                  <label class="admin-label">Title</label>
                  <input class="admin-input" name="title" value="<?php echo htmlspecialchars($p["title"]); ?>" required>

                  <label class="admin-label">Tech</label>
                  <input class="admin-input" name="tech" value="<?php echo htmlspecialchars($p["tech"]); ?>">

                  <label class="admin-label">Description</label>
                  <textarea class="admin-textarea" name="description" required><?php echo htmlspecialchars($p["description"]); ?></textarea>

                  <div class="admin-actions-row">
                    <button class="admin-btn" name="action" value="update" type="submit">Save Changes</button>
                    <button class="admin-btn danger" name="action" value="delete" type="submit"
                      onclick="return confirm('Delete this project?')">Delete</button>
                  </div>
                </form>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </section>
</main>

<script src="theme.js"></script>
</body>
</html>
