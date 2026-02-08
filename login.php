<?php
require "db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username = trim($_POST["username"] ?? "");
  $password = (string)($_POST["password"] ?? "");

  $stmt = $conn->prepare("SELECT id, username, password_hash, role FROM users WHERE username=? LIMIT 1");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();

  if ($row && password_verify($password, $row["password_hash"])) {
    session_regenerate_id(true);
    $_SESSION["user_id"] = (int)$row["id"];
    $_SESSION["username"] = (string)$row["username"];
    $_SESSION["role"] = (string)$row["role"];

    header("Location: dashboard.php");
    exit;
  } else {
    $error = "Invalid username or password.";
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login </title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">

  <div class="admin-card auth-card">
    <div class="auth-top">
      <div>
        <div class="auth-kicker">Portfolio CMS</div>
        <h2 class="admin-title" style="margin:6px 0 0">Welcome back</h2>

      </div>

      <button class="theme-toggle" id="themeToggle" type="button" title="Toggle theme">
         <span class="theme-label">Dark</span>
      </button>
    </div>

    <?php if ($error): ?>
      <div class="admin-alert"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" class="auth-form" autocomplete="on">
      <label class="admin-label" for="username">Username</label>
      <input class="admin-input" id="username" name="username" required autocomplete="username" />

      <label class="admin-label" for="password">Password</label>
      <div class="input-wrap">
        <input class="admin-input" id="password" name="password" type="password" required autocomplete="current-password" />
        <button class="btn-icon" type="button" id="togglePass" aria-label="Show password" title="Show/Hide">
          👁
        </button>
      </div>

      <button class="admin-btn" type="submit">Login</button>

      <div class="auth-meta">
      </div>
    </form>
  </div>

  <script src="theme.js"></script>
  <script>
    (function () {
      const btn = document.getElementById("togglePass");
      const input = document.getElementById("password");
      if (!btn || !input) return;

      btn.addEventListener("click", () => {
        const isPw = input.type === "password";
        input.type = isPw ? "text" : "password";
        btn.textContent = isPw ? "" : "👁";
        btn.setAttribute("aria-label", isPw ? "Hide password" : "Show password");
      });
    })();
  </script>
</body>
</html>
