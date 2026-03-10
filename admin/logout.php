<?php
require_once "session-bootstrap.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: dashboard.php");
    exit;
}

$csrfToken = $_POST["csrf_token"] ?? "";

if (!isset($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $csrfToken)) {
    header("Location: dashboard.php");
    exit;
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header("Location: login.php");
exit;
