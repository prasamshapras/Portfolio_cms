<?php
require "db.php";
require "auth.php";
require_login();

$target_user_id = current_user_id();
if (is_admin() && isset($_GET["user_id"])) $target_user_id = max(1, (int)$_GET["user_id"]);

$sections = ["home","skills","contact"];
$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  csrf_check();
  foreach ($sections as $s) {
    $val = (string)($_POST[$s] ?? "");
    $stmt = $conn->prepare("INSERT INTO portfolio_content(user_id, section, content) VALUES(?,?,?)
      ON DUPLICATE KEY UPDATE content=VALUES(content)");
    $stmt->bind_param("iss", $target_user_id, $s, $val);
    $stmt->execute();
    $stmt->close();
  }
  $msg = "Saved.";
}

$data = [];
$stmt = $conn->prepare("SELECT section, content FROM portfolio_content WHERE user_id=?");
$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $data[$row["section"]] = $row["content"];
$stmt->close();

function v($data,$k){ return htmlspecialchars($data[$k] ?? ""); }
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
  <title>Manage Content • Portfolio CMS</title>
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
    <div class="admin-page-title">Manage Content</div>
    <div class="admin-page-sub">Update your homepage hero text, skills, and contact text.</div>
  </div>

  <?php if($msg): ?>
    <div class="admin-alert ok"><?php echo htmlspecialchars($msg); ?></div>
  <?php endif; ?>

  <form method="post" class="admin-form">
    <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">

    <div class="admin-cardx">
      <div class="admin-cardx-head">
        <h3>Home</h3>
        <div class="muted">Headline / intro</div>
      </div>
      <textarea class="admin-textarea" name="home"><?php echo v($data,"home"); ?></textarea>
    </div>

    <div style="height:12px"></div>

    <div class="admin-cardx">
      <div class="admin-cardx-head">
        <h3>Skills</h3>
        <div class="muted">Your tools & technologies</div>
      </div>
      <textarea class="admin-textarea" name="skills"><?php echo v($data,"skills"); ?></textarea>
    </div>

    <div style="height:12px"></div>

    <div class="admin-cardx">
      <div class="admin-cardx-head">
        <h3>Contact Text</h3>
        <div class="muted">Optional message</div>
      </div>
      <textarea class="admin-textarea" name="contact"><?php echo v($data,"contact"); ?></textarea>
    </div>

    <div class="admin-actions-row">
      <button class="admin-btn" type="submit">Save Changes</button>
    </div>
  </form>
</main>

<script src="theme.js"></script>
</body>
</html>
