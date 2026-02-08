<?php
require "db.php";
require "auth.php";
require_login();

$meName = $_SESSION["username"] ?? "User";

// target user: admin can switch, user always self
$target_user_id = current_user_id();
if (is_admin() && isset($_GET["user_id"])) {
  $target_user_id = max(1, (int)$_GET["user_id"]);
}

// fetch all users for admin dropdown
$allUsers = [];
if (is_admin()) {
  $res = $conn->query("SELECT id, username, role FROM users ORDER BY role DESC, username ASC");
  while ($row = $res->fetch_assoc()) $allUsers[] = $row;
}

// stats for target user
$stmt = $conn->prepare("SELECT COUNT(*) c FROM portfolio_projects WHERE user_id=?");
$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$cnt = (int)($stmt->get_result()->fetch_assoc()["c"] ?? 0);
$stmt->close();

$qs = is_admin() ? ("?user_id=" . $target_user_id) : "";

/* ✅ FIX: View Site should open the portfolio of the user admin is editing */
$targetUsername = $_SESSION["username"] ?? "";
$stmt = $conn->prepare("SELECT username FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$tmp = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!empty($tmp["username"])) $targetUsername = $tmp["username"];
$viewSiteUrl = "index.php?u=" . urlencode($targetUsername);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard • Portfolio CMS</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="topbar">
  <div class="wrap topbar-inner">
    <a class="brand" href="dashboard.php">
      <span class="brand-dot"></span>
      <span class="brand-text">Portfolio CMS</span>
    </a>

    <div class="topbar-right">
      <a class="pill" href="<?php echo htmlspecialchars($viewSiteUrl); ?>">View Site</a>
      <a class="pill pill-ghost" href="logout.php">Logout</a>

      <button class="pill pill-ghost" id="themeToggle" type="button">
        <span class="theme-label">Dark</span>
      </button>
    </div>
  </div>
</header>

<main class="wrap" style="padding:28px 0 60px">

  <!-- Dashboard Hero -->
  <section class="dash-hero">
    <div class="dash-hero-left">
      <div class="kicker">Dashboard</div>
      <h1 class="dash-title">Hi, <?php echo htmlspecialchars($meName); ?> </h1>
      <p class="dash-sub muted">
        Logged in as <b><?php echo htmlspecialchars($_SESSION["role"]); ?></b>.
      </p>
    </div>

    <div class="dash-hero-right">
      <div class="dash-stat">
        <div class="dash-stat-num"><?php echo (int)$cnt; ?></div>
        <div class="dash-stat-label">Projects</div>
      </div>

  </section>

  <!-- Admin user selector -->
  <?php if (is_admin()): ?>
    <section class="panel" style="margin-top:14px">
      <form method="get" class="dash-form-row">
        <div style="flex:1;min-width:280px">
          <label class="dash-label" for="user_id" style="margin-top:0">Manage user</label>
          <select class="dash-input" id="user_id" name="user_id" onchange="this.form.submit()">
            <?php foreach ($allUsers as $u): ?>
              <option value="<?php echo (int)$u["id"]; ?>" <?php if ((int)$u["id"] === $target_user_id) echo "selected"; ?>>
                <?php echo htmlspecialchars($u["username"] . " (" . $u["role"] . ")"); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="dash-actions">
          <button class="pill" type="submit" style="cursor:pointer">Load</button>
          <a class="pill pill-ghost" href="dashboard.php">Reset</a>
        </div>
      </form>

      <div class="muted" style="margin-top:10px;font-weight:850">
        You are editing user ID: <b><?php echo (int)$target_user_id; ?></b>
      </div>
    </section>
  <?php endif; ?>
  <?php if (is_admin()): ?>
  <a class="dash-card" href="add_user.php">
    <div class="dash-icon"></div>
    <h3 class="dash-card-title">Add User</h3>
    <p class="dash-card-sub muted">Create new user accounts (admin only).</p>
    <div class="dash-card-link">Open →</div>
  </a>
<?php endif; ?>


  <!-- Quick action cards -->
  <section style="margin-top:16px">
    <div class="dash-grid">
      <a class="dash-card" href="manage_projects.php<?php echo $qs; ?>">
        <div class="dash-icon"></div>
        <h3 class="dash-card-title">Manage Projects</h3>
        <p class="dash-card-sub muted">Add, edit, reorder, and update project details.</p>
        <div class="dash-card-link">Open →</div>
      </a>

      <a class="dash-card" href="manage_content.php<?php echo $qs; ?>">
        <div class="dash-icon"></div>
        <h3 class="dash-card-title">Manage Content / Skills</h3>
        <p class="dash-card-sub muted">Update hero, about, and skills sections.</p>
        <div class="dash-card-link">Open →</div>
      </a>

      <a class="dash-card" href="manage_contact.php<?php echo $qs; ?>">
        <div class="dash-icon"></div>
        <h3 class="dash-card-title">Manage Contact</h3>
        <p class="dash-card-sub muted">Edit email, phone, LinkedIn, location, footer links.</p>
        <div class="dash-card-link">Open →</div>
      </a>

      <a class="dash-card" href="upload_photo.php<?php echo $qs; ?>">
        <div class="dash-icon"></div>
        <h3 class="dash-card-title">Upload Photo</h3>
        <p class="dash-card-sub muted">Change your profile photo shown on the site.</p>
        <div class="dash-card-link">Open →</div>
      </a>
    </div>
  </section>
</main>
<!-- Dashboard-only CSS (small & clean, uses your main theme variables) -->
<style>
  .dash-hero{
    display:flex;
    justify-content:space-between;
    gap:14px;
    flex-wrap:wrap;
    padding:18px;
    border-radius:22px;
    border:1px solid var(--border);
    background: color-mix(in srgb, var(--surface) 86%, transparent);
    box-shadow: var(--shadow2);
  }
  .dash-title{
    margin:12px 0 0;
    font-size: clamp(26px, 3vw, 40px);
    letter-spacing: -.02em;
  }
  .dash-sub{ margin:10px 0 0; }

  .dash-hero-right{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    align-items:stretch;
  }
  .dash-stat{
    min-width: 160px;
    padding: 14px 16px;
    border-radius: 18px;
    border: 1px solid var(--border);
    background: color-mix(in srgb, var(--surface) 78%, transparent);
    text-align:center;
  }
  .dash-stat-num{ font-weight:1100; font-size: 1.8rem; line-height:1; }
  .dash-stat-label{ margin-top: 8px; font-weight: 950; color: var(--muted); }

  .dash-form-row{
    display:flex;
    gap:14px;
    flex-wrap:wrap;
    align-items:flex-end;
  }
  .dash-label{
    display:block;
    font-weight: 950;
    margin: 10px 0 8px;
    color: var(--muted);
  }
  .dash-input{
    width:100%;
    padding: 12px 14px;
    border-radius: 14px;
    border: 1px solid var(--border);
    background: color-mix(in srgb, var(--surface) 88%, transparent);
    color: var(--text);
    outline:none;
  }
  html[data-theme="light"] .dash-input{
    background: rgba(255,255,255,.86);
  }
  .dash-actions{ display:flex; gap:10px; flex-wrap:wrap; }

  .dash-grid{
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap:14px;
  }
  .dash-card{
    display:block;
    text-decoration:none;
    border-radius: 22px;
    border: 1px solid var(--border);
    background:
      linear-gradient(180deg,
        color-mix(in srgb, var(--surface) 92%, transparent),
        color-mix(in srgb, var(--surface) 84%, transparent)
      );
    box-shadow: var(--shadow2);
    padding: 16px;
    transition: var(--trans);
  }
  .dash-card:hover{
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--accent) 35%, var(--border2));
    box-shadow: var(--shadow);
  }
  .dash-icon{ font-size: 1.4rem; margin-bottom: 10px; }
  .dash-card-title{ margin:0 0 6px; font-weight:1100; letter-spacing:-.01em; }
  .dash-card-sub{ margin:0; line-height:1.65; }
  .dash-card-link{
    margin-top: 12px;
    font-weight: 1000;
    color: color-mix(in srgb, var(--accent2) 88%, var(--text));
  }
</style>
<a class="dash-card" href="manage_experience.php<?php echo $qs; ?>">
  <div class="dash-icon"></div>
  <h3 class="dash-card-title">Manage Experience</h3>
  <p class="dash-card-sub muted">Add and update work experience.</p>
  <div class="dash-card-link">Open →</div>
</a>

<a class="dash-card" href="manage_education.php<?php echo $qs; ?>">
  <div class="dash-icon"></div>
  <h3 class="dash-card-title">Manage Education</h3>
  <p class="dash-card-sub muted">Add and update education history.</p>
  <div class="dash-card-link">Open →</div>
</a>


<script src="theme.js"></script>
</body>
</html>
