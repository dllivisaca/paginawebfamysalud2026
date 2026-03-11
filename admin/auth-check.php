<?php
require_once "session-bootstrap.php";
require_once "../db.php";
require_once "admin-session-store.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$adminId = (int) $_SESSION["admin_id"];
$currentSessionId = session_id();

if (!isAdminUserSessionActive($conn, $adminId, $currentSessionId)) {
    destroyCurrentAdminPhpSession();
    header("Location: login.php");
    exit;
}
