<?php
// HARD GUARD: prevents double-loading even if included twice
if (isset($GLOBALS["__AUTH_PHP_LOADED__"])) {
  return;
}
$GLOBALS["__AUTH_PHP_LOADED__"] = true;

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!function_exists("require_login")) {
  function require_login(): void {
    if (empty($_SESSION["user_id"])) {
      header("Location: login.php");
      exit;
    }
  }
}

if (!function_exists("is_admin")) {
  function is_admin(): bool {
    return (($_SESSION["role"] ?? "user") === "admin");
  }
}

if (!function_exists("current_user_id")) {
  function current_user_id(): int {
    return (int)($_SESSION["user_id"] ?? 0);
  }
}

if (!function_exists("csrf_token")) {
  function csrf_token(): string {
    if (empty($_SESSION["csrf"])) $_SESSION["csrf"] = bin2hex(random_bytes(32));
    return $_SESSION["csrf"];
  }
}

if (!function_exists("csrf_check")) {
  function csrf_check(): void {
    $token = (string)($_POST["csrf"] ?? "");
    if (!$token || !hash_equals($_SESSION["csrf"] ?? "", $token)) {
      http_response_code(403);
      exit("Invalid CSRF token");
    }
  }
}

if (!function_exists("clean_url")) {
  function clean_url(string $url): string {
    $url = trim($url);
    if ($url === "") return "";
    if (!preg_match('#^https?://#i', $url)) return "";
    return $url;
  }
}
