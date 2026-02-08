<?php
require "db.php";
require "auth.php";
require_login();
if (!is_admin()) { http_response_code(403); exit("Forbidden"); }

$err = "";
$ok  = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  csrf_check();

  $username = trim($_POST["username"] ?? "");
  $password = (string)($_POST["password"] ?? "");
  $role     = (($_POST["role"] ?? "user") === "admin") ? "admin" : "user";

  if ($username === "" || $password === "") {
    $err = "Username and password are required.";
  } elseif (!preg_match('/^[a-zA-Z0-9_]{3,32}$/', $username)) {
    $err = "Username must be 3–32 characters (letters, numbers, underscore).";
  } else {
    // ensure unique username
    $stmt = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($exists) {
      $err = "That username already exists.";
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);

      $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
      $stmt->bind_param("sss", $username, $hash, $role);
      $stmt->execute();
      $stmt->close();

      $ok = "User created successfully.";
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Add User • Portfolio CMS</title>
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
      <a class="pill pill-ghost" href="dashboard.php">Back</a>
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
      <h1 class="admin-page-title">Add User</h1>
      <p class="admin-page-sub">Create a new account </p>
    </div>
  </div>

  <?php if ($err): ?>
    <div class="admin-alert err"><?php echo htmlspecialchars($err); ?></div>
  <?php endif; ?>

  <?php if ($ok): ?>
    <div class="admin-alert ok"><?php echo htmlspecialchars($ok); ?></div>
  <?php endif; ?>

  <div class="admin-cardx">
    <form method="post" class="admin-form" autocomplete="off">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">

      <div class="admin-row">
        <div>
          <label class="dash-label" for="username">Username</label>
          <input class="dash-input" id="username" name="username" required placeholder="e.g. hari_01">
        </div>

        <div>
          <label class="dash-label" for="role">Role</label>
          <select class="dash-input" id="role" name="role">
            <option value="user">user</option>
            <option value="admin">admin</option>
          </select>
        </div>
      </div>

      <label class="dash-label" for="password">Password</label>
      <div class="input-wrap input-wrap-pro">
        <input class="dash-input auth-input-pro" id="password" name="password" type="password" required placeholder="Create a strong password">
        <button class="pass-toggle-inline" type="button" id="togglePass" aria-controls="password" aria-pressed="false">Show</button>
      </div>

      <div class="admin-actions-row">
        <button class="admin-btn" type="submit">Create User</button>
        <a class="admin-btn secondary" href="dashboard.php">Cancel</a>
      </div>
    </form>
  </div>
</main>

<script src="theme.js"></script>
<script>
(function () {
  const btn = document.getElementById("togglePass");
  const input = document.getElementById("password");
  if (!btn || !input) return;

  btn.addEventListener("click", () => {
    const hidden = input.type === "password";
    input.type = hidden ? "text" : "password";
    btn.textContent = hidden ? "Hide" : "Show";
    btn.setAttribute("aria-pressed", hidden ? "true" : "false");
  });
})();
</script>
</body>
</html>
