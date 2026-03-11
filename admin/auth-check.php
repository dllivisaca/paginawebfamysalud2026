<?php
require_once __DIR__ . "/session-bootstrap.php";
require_once dirname(__DIR__) . "/db.php";
require_once __DIR__ . "/admin-session-store.php";

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
