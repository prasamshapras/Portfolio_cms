<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/*
  IMPORTANT:
  Session ini settings MUST be set before session_start().
  Also: only start session once.
*/
if (session_status() === PHP_SESSION_NONE) {
  ini_set('session.cookie_httponly', '1');
  ini_set('session.use_strict_mode', '1');
  session_start();
}

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "portfolio_cms";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$conn->set_charset("utf8mb4");
