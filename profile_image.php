<?php
require "db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

/*
  Supports:
  - profile_image.php?u=username   ✅ used by index.php
  - profile_image.php?user_id=123  ✅ old style
*/

$userId = (int)($_GET["user_id"] ?? 0);

if ($userId <= 0) {
  $username = trim($_GET["u"] ?? "");
  if ($username === "" && !empty($_SESSION["username"])) {
    $username = (string)$_SESSION["username"];
  }

  if ($username !== "") {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $rowU = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $userId = (int)($rowU["id"] ?? 0);
  }
}

if ($userId <= 0) {
  http_response_code(400);
  exit;
}

$stmt = $conn->prepare("SELECT mime, image FROM portfolio_profile WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || empty($row["image"])) {
  http_response_code(404);
  exit;
}

$mime = trim((string)($row["mime"] ?? ""));
if ($mime === "") $mime = "image/jpeg";

/* IMPORTANT: ensure nothing else is printed */
header("Content-Type: " . $mime);
header("Cache-Control: public, max-age=3600");
header("X-Content-Type-Options: nosniff");

echo $row["image"];
exit;
