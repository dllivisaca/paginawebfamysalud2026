<?php
require_once "session-bootstrap.php";
require_once "../db.php";
require_once "admin-session-store.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit;
}

$csrfToken = $_POST["csrf_token"] ?? "";

if (!isset($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $csrfToken)) {
    header("Location: dashboard.php");
    exit;
}

if (isset($_SESSION["admin_id"]) && is_numeric($_SESSION["admin_id"])) {
    deactivateAdminUserSession($conn, (int) $_SESSION["admin_id"], session_id());
}

destroyCurrentAdminPhpSession();

header("Location: login.php");
exit;
