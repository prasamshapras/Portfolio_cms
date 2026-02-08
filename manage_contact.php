<?php
require "db.php";
require "auth.php";
require_login();

$target_user_id = current_user_id();
if (is_admin() && isset($_GET["user_id"])) $target_user_id = max(1, (int)$_GET["user_id"]);

$msg = "";
$contact = [
  "email"=>"","github"=>"","linkedin"=>"","location"=>"","availability"=>"",
  "footer_github"=>"","footer_linkedin"=>""
];

$stmt = $conn->prepare("SELECT * FROM portfolio_contact WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();
if ($row) $contact = array_merge($contact, $row);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  csrf_check();
  foreach ($contact as $k => $_) $contact[$k] = trim((string)($_POST[$k] ?? ""));

  $stmt = $conn->prepare(
    "INSERT INTO portfolio_contact (user_id,email,github,linkedin,location,availability,footer_github,footer_linkedin)
     VALUES (?,?,?,?,?,?,?,?)
     ON DUPLICATE KEY UPDATE
       email=VALUES(email), github=VALUES(github), linkedin=VALUES(linkedin),
       location=VALUES(location), availability=VALUES(availability),
       footer_github=VALUES(footer_github), footer_linkedin=VALUES(footer_linkedin)"
  );
  $stmt->bind_param("isssssss", $target_user_id,
    $contact["email"], $contact["github"], $contact["linkedin"],
    $contact["location"], $contact["availability"],
    $contact["footer_github"], $contact["footer_linkedin"]
  );
  $stmt->execute();
  $stmt->close();
  $msg = "Saved.";
}

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
  <title>Manage Contact • Portfolio CMS</title>
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
    <div class="admin-page-title">Manage Contact</div>
    <div class="admin-page-sub">Update your contact details shown on the portfolio website.</div>
  </div>

  <?php if($msg): ?>
    <div class="admin-alert ok"><?php echo htmlspecialchars($msg); ?></div>
  <?php endif; ?>

  <div class="admin-cardx">
    <div class="admin-cardx-head">
      <h3>Contact Information</h3>
      <div class="muted">Keep it accurate and professional</div>
    </div>

    <form method="post" class="admin-form">
      <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">

      <div class="admin-row">
        <div>
          <label class="admin-label">Email</label>
          <input class="admin-input" name="email" value="<?php echo htmlspecialchars($contact["email"]); ?>">
        </div>
        <div>
          <label class="admin-label">Location</label>
          <input class="admin-input" name="location" value="<?php echo htmlspecialchars($contact["location"]); ?>">
        </div>
      </div>

      <div class="admin-row">
        <div>
          <label class="admin-label">GitHub</label>
          <input class="admin-input" name="github" value="<?php echo htmlspecialchars($contact["github"]); ?>">
        </div>
        <div>
          <label class="admin-label">LinkedIn</label>
          <input class="admin-input" name="linkedin" value="<?php echo htmlspecialchars($contact["linkedin"]); ?>">
        </div>
      </div>

      <div class="admin-row">
        <div>
          <label class="admin-label">Availability</label>
          <input class="admin-input" name="availability" value="<?php echo htmlspecialchars($contact["availability"]); ?>">
        </div>
        <div>
          <label class="admin-label">Footer GitHub</label>
          <input class="admin-input" name="footer_github" value="<?php echo htmlspecialchars($contact["footer_github"]); ?>">
        </div>
      </div>

      <label class="admin-label">Footer LinkedIn</label>
      <input class="admin-input" name="footer_linkedin" value="<?php echo htmlspecialchars($contact["footer_linkedin"]); ?>">

      <div class="admin-actions-row">
        <button class="admin-btn" type="submit">Save Changes</button>
      </div>
    </form>
  </div>
</main>

<script src="theme.js"></script>
</body>
</html>
