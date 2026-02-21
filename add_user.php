<?php
require_once "db.php";
require_once "auth.php";
require_login();

if (!is_admin()) {
  http_response_code(403);
  exit("Admins only");
}

$err = "";
$ok  = "";

function normalize_display_name(string $name): string {
  $name = trim($name);
  $name = preg_replace('/\s+/', ' ', $name);
  return $name;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  csrf_check();

  $email = trim((string)($_POST["username"] ?? ""));
  $dname = normalize_display_name((string)($_POST["display_name"] ?? ""));
  $role  = (string)($_POST["role"] ?? "user");
  $pass  = (string)($_POST["password"] ?? "");

  if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = "Please enter a valid email address.";
  } elseif ($dname === "") {
    $err = "Display name is required.";
  } elseif (mb_strlen($dname) > 60) {
    $err = "Display name too long (max 60 characters).";
  } elseif ($pass === "") {
    $err = "Password is required.";
  } elseif (!in_array($role, ["user", "admin"], true)) {
    $err = "Invalid role.";
  } else {

    // Check duplicate email
    $stmt = $conn->prepare("SELECT id FROM users WHERE LOWER(username)=LOWER(?) LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    if ($exists) {
      $err = "This email is already registered.";
    } else {

      $hash = password_hash($pass, PASSWORD_DEFAULT);

      $ins = $conn->prepare("
        INSERT INTO users (username, display_name, password_hash, role)
        VALUES (?, ?, ?, ?)
      ");
      $ins->bind_param("ssss", $email, $dname, $hash, $role);
      $ins->execute();
      $ins->close();

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

<main class="wrap" style="padding:28px 0 60px">

<section class="panel">
  <div class="kicker">Add User</div>
  <h1 class="dash-title" style="margin-top:8px">Create a new account</h1>

  <?php if ($err): ?>
    <div class="admin-alert" style="margin-top:14px">
      <?php echo htmlspecialchars($err); ?>
    </div>
  <?php elseif ($ok): ?>
    <div class="admin-alert" style="margin-top:14px;border-color:var(--accent);">
      <?php echo htmlspecialchars($ok); ?>
    </div>
  <?php endif; ?>

  <form method="post" class="auth-form" style="margin-top:16px" autocomplete="off">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">

    <div style="display:flex; gap:14px; flex-wrap:wrap">

      <div style="flex:1; min-width:260px">
        <label class="admin-label" for="username">Email (Username)</label>
        <input
          class="admin-input"
          id="username"
          name="username"
          type="email"
          required
          value="<?php echo htmlspecialchars($_POST["username"] ?? ""); ?>"
        />
      </div>

      <div style="flex:1; min-width:220px">
        <label class="admin-label" for="role">Role</label>
        <select class="admin-input" id="role" name="role">
          <?php $r = (string)($_POST["role"] ?? "user"); ?>
          <option value="user"  <?php if ($r === "user")  echo "selected"; ?>>user</option>
          <option value="admin" <?php if ($r === "admin") echo "selected"; ?>>admin</option>
        </select>
      </div>

    </div>

    <label class="admin-label" for="display_name" style="margin-top:14px">Display Name</label>
    <input
      class="admin-input"
      id="display_name"
      name="display_name"
      required
      value="<?php echo htmlspecialchars($_POST["display_name"] ?? ""); ?>"
    />

    <label class="admin-label" for="password" style="margin-top:14px">Password</label>
    <div class="input-wrap">
      <input
        class="admin-input"
        id="password"
        name="password"
        type="password"
        required
        autocomplete="new-password"
      />

      <button class="btn-icon" type="button" id="togglePass" aria-label="Toggle password visibility">
        <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
          <circle cx="12" cy="12" r="3"></circle>
        </svg>
      </button>
    </div>

    <button class="admin-btn" type="submit" style="margin-top:16px">
      Create User
    </button>

  </form>
</section>

</main>

<script src="theme.js"></script>

<script>
(function () {
  const btn = document.getElementById("togglePass");
  const input = document.getElementById("password");
  const icon = document.getElementById("eyeIcon");
  if (!btn || !input || !icon) return;

  btn.addEventListener("click", function () {
    const isPassword = input.type === "password";
    input.type = isPassword ? "text" : "password";

    icon.innerHTML = isPassword
      ? `<path d="M17.94 17.94A10.94 10.94 0 0 1 12 20C5 20 1 12 1 12a21.77 21.77 0 0 1 5.06-6.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.82 21.82 0 0 1-3.34 4.66M1 1l22 22"></path>`
      : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
         <circle cx="12" cy="12" r="3"></circle>`;
  });
})();
</script>

</body>
</html>