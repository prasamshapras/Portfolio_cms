<?php
require "db.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$users = [
  ["ram",   "ram123",   "user"],
  ["shyam", "shyam123", "user"],
  ["hari",  "hari123",  "user"],
  ["gita",  "gita123",  "user"],
  ["rita",  "rita123",  "user"],
];

echo "<h2>Create / Update Users</h2>";

foreach ($users as [$username, $password, $role]) {
  $hash = password_hash($password, PASSWORD_DEFAULT);

  // ✅ if user exists -> update password + role
  $stmt = $conn->prepare("
    INSERT INTO users (username, password_hash, role)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE
      password_hash = VALUES(password_hash),
      role = VALUES(role)
  ");
  $stmt->bind_param("sss", $username, $hash, $role);

  try {
    $stmt->execute();
    echo "✅ OK: <b>$username</b> / <b>$password</b> ($role)<br>";
  } catch (Throwable $e) {
    echo "❌ ERROR for <b>$username</b>: " . htmlspecialchars($e->getMessage()) . "<br>";
  }

  $stmt->close();
}

echo "<hr>✅ Done. Delete <b>create_users.php</b> now.";
