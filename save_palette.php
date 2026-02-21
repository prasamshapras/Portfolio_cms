<?php
require "db.php";
require "auth.php";
require_login();

// palette key from JS
$palette = trim((string)($_POST["palette"] ?? ""));
if ($palette === "") {
  http_response_code(400);
  exit("Missing palette");
}

// allow-list (must match palette.js keys)
$allowed = ["blue","purple","green","orange","pink","teal","red","slate"];
if (!in_array($palette, $allowed, true)) {
  http_response_code(400);
  exit("Invalid palette");
}

// target: admin can save for selected user, otherwise self
$target_user_id = current_user_id();
if (is_admin() && isset($_POST["user_id"])) {
  $target_user_id = max(1, (int)$_POST["user_id"]);
}

// update DB
$stmt = $conn->prepare("UPDATE users SET accent_palette=? WHERE id=? LIMIT 1");
$stmt->bind_param("si", $palette, $target_user_id);
$stmt->execute();
$stmt->close();

echo "OK";
