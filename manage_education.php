<?php
require "db.php";
require "auth.php";
require_login();

$target_user_id = current_user_id();
if (is_admin() && isset($_GET["user_id"])) {
  $target_user_id = max(1, (int)$_GET["user_id"]);
}

$qs = is_admin() ? ("?user_id=" . $target_user_id) : "";

$msg = "";
$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  csrf_check();
  $action = $_POST["action"] ?? "";

  if ($action === "add") {
    $institution = trim($_POST["institution"] ?? "");
    $degree = trim($_POST["degree"] ?? "");
    $field = trim($_POST["field"] ?? "");
    $start_year = trim($_POST["start_year"] ?? "");
    $end_year = trim($_POST["end_year"] ?? "");
    $grade = trim($_POST["grade"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $sort_order = (int)($_POST["sort_order"] ?? 0);

    if ($institution === "" || $degree === "") {
      $err = "Institution and Degree are required.";
    } else {
      $stmt = $conn->prepare("
        INSERT INTO education
          (user_id, institution, degree, field, start_year, end_year, grade, description, sort_order)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->bind_param(
        "isssssssi",
        $target_user_id,
        $institution,
        $degree,
        $field,
        $start_year,
        $end_year,
        $grade,
        $description,
        $sort_order
      );
      $stmt->execute();
      $stmt->close();
      header("Location: manage_education.php" . $qs);
      exit;
    }
  }

  if ($action === "delete") {
    $id = (int)($_POST["id"] ?? 0);
    $stmt = $conn->prepare("DELETE FROM education WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $target_user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_education.php" . $qs);
    exit;
  }
}

/* fetch list */
$list = [];
$stmt = $conn->prepare("SELECT * FROM education WHERE user_id=? ORDER BY sort_order ASC, id DESC");
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
  <title>Manage Education • Portfolio CMS</title>
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
      <h1 class="admin-page-title">Manage Education</h1>
      <p class="admin-page-sub">Add and manage education entries.</p>
    </div>
  </div>

  <?php if ($err): ?>
    <div class="admin-alert err"><?php echo htmlspecialchars($err); ?></div>
  <?php endif; ?>

  <div class="admin-layout">

    <!-- Add form -->
    <div class="admin-cardx">
      <div class="admin-cardx-head">
        <h3>Add Education</h3>
        <span class="muted">User ID: <?php echo (int)$target_user_id; ?></span>
      </div>

      <form method="post" class="admin-form">
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <input type="hidden" name="action" value="add">

        <label class="dash-label">Institution *</label>
        <input class="dash-input" name="institution" required>

        <label class="dash-label">Degree *</label>
        <input class="dash-input" name="degree" required>

        <div class="admin-row">
          <div>
            <label class="dash-label">Field</label>
            <input class="dash-input" name="field">
          </div>
          <div>
            <label class="dash-label">Grade</label>
            <input class="dash-input" name="grade">
          </div>
        </div>

        <div class="admin-row">
          <div>
            <label class="dash-label">Start Year</label>
            <input class="dash-input" name="start_year" placeholder="e.g. 2019">
          </div>
          <div>
            <label class="dash-label">End Year</label>
            <input class="dash-input" name="end_year" placeholder="e.g. 2023">
          </div>
        </div>

        <label class="dash-label">Description</label>
        <textarea class="admin-textarea" name="description" placeholder="Optional details..."></textarea>

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
        <h3>Education List</h3>
        <span class="muted"><?php echo count($list); ?> items</span>
      </div>

      <div class="item-list">
        <?php foreach ($list as $e): ?>
          <details class="item">
            <summary class="item-summary">
              <div>
                <h4 class="item-title"><?php echo htmlspecialchars($e["degree"]); ?></h4>
                <div class="item-meta">
                  <?php echo htmlspecialchars($e["institution"]); ?>
                  <?php if (!empty($e["start_year"]) || !empty($e["end_year"])): ?>
                    • <?php echo htmlspecialchars(trim(($e["start_year"] ?? "") . " - " . ($e["end_year"] ?? ""))); ?>
                  <?php endif; ?>
                </div>
              </div>
              <span class="item-badge">ID <?php echo (int)$e["id"]; ?></span>
            </summary>

            <div class="item-body">
              <?php if (!empty($e["field"]) || !empty($e["grade"])): ?>
                <div class="muted" style="font-weight:900;margin-bottom:8px">
                  <?php
                    $meta = [];
                    if (!empty($e["field"])) $meta[] = $e["field"];
                    if (!empty($e["grade"])) $meta[] = "Grade: " . $e["grade"];
                    echo htmlspecialchars(implode(" • ", $meta));
                  ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($e["description"])): ?>
                <div class="muted" style="font-weight:850;line-height:1.7">
                  <?php echo nl2br(htmlspecialchars($e["description"])); ?>
                </div>
              <?php endif; ?>

              <form method="post" style="margin-top:12px" onsubmit="return confirm('Delete this education?');">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)$e["id"]; ?>">
                <button class="admin-btn danger" type="submit">Delete</button>
              </form>
            </div>
          </details>
        <?php endforeach; ?>

        <?php if (!$list): ?>
          <div class="muted" style="font-weight:900">No education entries yet.</div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</main>

<script src="theme.js"></script>
</body>
</html>
