<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function require_login(): void {
  if (empty($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
  }
}

function is_admin(): bool {
  return (($_SESSION["role"] ?? "user") === "admin");
}

function current_user_id(): int {
  return (int)($_SESSION["user_id"] ?? 0);
}

function csrf_token(): string {
  if (empty($_SESSION["csrf"])) $_SESSION["csrf"] = bin2hex(random_bytes(32));
  return $_SESSION["csrf"];
}

function csrf_check(): void {
  $token = (string)($_POST["csrf"] ?? "");
  if (!$token || !hash_equals($_SESSION["csrf"] ?? "", $token)) {
    http_response_code(403);
    exit("Invalid CSRF token");
  }
}

function clean_url(string $url): string {
  $url = trim($url);
  if ($url === "") return "";
  if (!preg_match('#^https?://#i', $url)) return "";
  return $url;
}
