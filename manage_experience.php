<?php
require "db.php";
require "auth.php";
require_login();

$target_user_id = current_user_id();
if (is_admin() && isset($_GET["user_id"])) {
  $target_user_id = max(1, (int)$_GET["user_id"]);
}

$qs = is_admin() ? ("?user_id=" . $target_user_id) : "";

$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  csrf_check();
  $action = $_POST["action"] ?? "";

  if ($action === "add") {
    $company = trim($_POST["company"] ?? "");
    $title = trim($_POST["title"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $employment_type = trim($_POST["employment_type"] ?? "");
    $start_date = trim($_POST["start_date"] ?? "");
    $end_date = trim($_POST["end_date"] ?? "");
    $is_current = isset($_POST["is_current"]) ? 1 : 0;
    if ($is_current) $end_date = "";
    $description = trim($_POST["description"] ?? "");
    $sort_order = (int)($_POST["sort_order"] ?? 0);

    if ($company === "" || $title === "") {
      $err = "Company and Title are required.";
    } else {
      $stmt = $conn->prepare("
        INSERT INTO experience
          (user_id, company, title, location, employment_type, start_date, end_date, is_current, description, sort_order)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->bind_param(
        "issssssisi",
        $target_user_id,
        $company,
        $title,
        $location,
        $employment_type,
        $start_date,
        $end_date,
        $is_current,
        $description,
        $sort_order
      );
      $stmt->execute();
      $stmt->close();
      header("Location: manage_experience.php" . $qs);
      exit;
    }
  }

  if ($action === "delete") {
    $id = (int)($_POST["id"] ?? 0);
    $stmt = $conn->prepare("DELETE FROM experience WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $target_user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_experience.php" . $qs);
    exit;
  }
}

/* fetch list */
$list = [];
$stmt = $conn->prepare("SELECT * FROM experience WHERE user_id=? ORDER BY sort_order ASC, id DESC");
$stmt->bind_param("i", $target_user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $list[] = $row;
$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage Experience • Portfolio CMS</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="topbar">
  <div class="wrap topbar-inner">
    <a class="brand" href="dashboard.php<?php echo htmlspecialchars($qs); ?>">
      <span class="brand-dot"></span>
      <span class="brand-text">Portfolio CMS</span>
    </a>

    <div class="topbar-right">
      <a class="pill pill-ghost" href="dashboard.php<?php echo htmlspecialchars($qs); ?>">Back</a>
      <a class="pill pill-ghost" href="logout.php">Logout</a>
      <button class="pill pill-ghost" id="themeToggle" type="button">
        <span class="theme-label">Dark</span>
      </button>
    </div>
  </div>
</header>

<main class="admin-shell">
  <div class="admin-top">
    <div>
      <h1 class="admin-page-title">Manage Experience</h1>
      <p class="admin-page-sub">Add and manage work experience entries.</p>
    </div>
  </div>

  <?php if ($err): ?>
    <div class="admin-alert err"><?php echo htmlspecialchars($err); ?></div>
  <?php endif; ?>

  <div class="admin-layout">

    <!-- Add form -->
    <div class="admin-cardx">
      <div class="admin-cardx-head">
        <h3>Add Experience</h3>
        <span class="muted">User ID: <?php echo (int)$target_user_id; ?></span>
      </div>

      <form method="post" class="admin-form">
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <input type="hidden" name="action" value="add">

        <label class="dash-label">Company *</label>
        <input class="dash-input" name="company" required>

        <label class="dash-label">Title *</label>
        <input class="dash-input" name="title" required>

        <div class="admin-row">
          <div>
            <label class="dash-label">Location</label>
            <input class="dash-input" name="location" placeholder="e.g. Kathmandu">
          </div>
          <div>
            <label class="dash-label">Employment Type</label>
            <input class="dash-input" name="employment_type" placeholder="Full-time / Internship">
          </div>
        </div>

        <div class="admin-row">
          <div>
            <label class="dash-label">Start</label>
            <input class="dash-input" name="start_date" placeholder="e.g. 2023-01 or 2023">
          </div>
          <div>
            <label class="dash-label">End</label>
            <input class="dash-input" name="end_date" placeholder="Leave empty if current">
          </div>
        </div>

        <label class="dash-label" style="display:flex;gap:10px;align-items:center">
          <input type="checkbox" name="is_current" value="1">
          Currently working here
        </label>

        <label class="dash-label">Description</label>
        <textarea class="admin-textarea" name="description" placeholder="Responsibilities, achievements..."></textarea>

        <label class="dash-label">Sort Order</label>
        <input class="dash-input" type="number" name="sort_order" value="0">

        <div class="admin-actions-row">
          <button class="admin-btn" type="submit">Add</button>
        </div>
      </form>
    </div>

    <!-- List -->
    <div class="admin-cardx">
      <div class="admin-cardx-head">
        <h3>Experience List</h3>
        <span class="muted"><?php echo count($list); ?> items</span>
      </div>

      <div class="item-list">
        <?php foreach ($list as $e): ?>
          <?php
            $range = trim(($e["start_date"] ?? "") . " - " . ((int)$e["is_current"] === 1 ? "Present" : ($e["end_date"] ?? "")));
          ?>
          <details class="item">
            <summary class="item-summary">
              <div>
                <h4 class="item-title"><?php echo htmlspecialchars($e["title"]); ?></h4>
                <div class="item-meta">
                  <?php echo htmlspecialchars($e["company"]); ?>
                  <?php if (!empty($e["employment_type"])): ?> • <?php echo htmlspecialchars($e["employment_type"]); ?><?php endif; ?>
                  <?php if ($range !== "-"): ?> • <?php echo htmlspecialchars($range); ?><?php endif; ?>
                </div>
              </div>
              <span class="item-badge">ID <?php echo (int)$e["id"]; ?></span>
            </summary>

            <div class="item-body">
              <?php
                $meta = [];
                if (!empty($e["location"])) $meta[] = $e["location"];
                if (!empty($meta)):
              ?>
                <div class="muted" style="font-weight:900;margin-bottom:8px">
                  <?php echo htmlspecialchars(implode(" • ", $meta)); ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($e["description"])): ?>
                <div class="muted" style="font-weight:850;line-height:1.7">
                  <?php echo nl2br(htmlspecialchars($e["description"])); ?>
                </div>
              <?php endif; ?>

              <form method="post" style="margin-top:12px" onsubmit="return confirm('Delete this experience?');">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)$e["id"]; ?>">
                <button class="admin-btn danger" type="submit">Delete</button>
              </form>
            </div>
          </details>
        <?php endforeach; ?>

        <?php if (!$list): ?>
          <div class="muted" style="font-weight:900">No experience entries yet.</div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</main>

<script src="theme.js"></script>
</body>
</html>
