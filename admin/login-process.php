<?php
session_start();
require_once "../db.php";

function getClientIpAddress(): string
{
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        return trim($_SERVER["HTTP_CLIENT_IP"]);
    }

    if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $parts = explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);
        return trim($parts[0]);
    }

    return $_SERVER["REMOTE_ADDR"] ?? "0.0.0.0";
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

$ipAddress = getClientIpAddress();
$maxAttempts = 5;
$lockMinutes = 15;

if ($email === "" || $password === "") {
    header("Location: login.php?error=invalid");
    exit;
}

$attemptSql = "SELECT COUNT(*) AS total_failed
               FROM admin_login_attempts
               WHERE attempted_at >= (NOW() - INTERVAL ? MINUTE)
                 AND is_success = 0
                 AND (email = ? OR ip_address = ?)";

$attemptStmt = $conn->prepare($attemptSql);

if (!$attemptStmt) {
    die("Error al preparar la consulta de intentos: " . $conn->error);
}

$attemptStmt->bind_param("iss", $lockMinutes, $email, $ipAddress);
$attemptStmt->execute();
$attemptResult = $attemptStmt->get_result();
$attemptRow = $attemptResult->fetch_assoc();

if ((int) $attemptRow["total_failed"] >= $maxAttempts) {
    header("Location: login.php?error=blocked");
    exit;
}

$sql = "SELECT id, name, email, password_hash, is_active
        FROM admin_users
        WHERE email = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta: " . $conn->error);
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $logSql = "INSERT INTO admin_login_attempts (email, ip_address, is_success)
               VALUES (?, ?, 0)";
    $logStmt = $conn->prepare($logSql);

    if ($logStmt) {
        $logStmt->bind_param("ss", $email, $ipAddress);
        $logStmt->execute();
    }

    header("Location: login.php?error=invalid");
    exit;
}

$user = $result->fetch_assoc();

if ((int) $user["is_active"] !== 1) {
    $logSql = "INSERT INTO admin_login_attempts (email, ip_address, is_success)
               VALUES (?, ?, 0)";
    $logStmt = $conn->prepare($logSql);

    if ($logStmt) {
        $logStmt->bind_param("ss", $email, $ipAddress);
        $logStmt->execute();
    }

    header("Location: login.php?error=inactive");
    exit;
}

if (!password_verify($password, $user["password_hash"])) {
    $logSql = "INSERT INTO admin_login_attempts (email, ip_address, is_success)
               VALUES (?, ?, 0)";
    $logStmt = $conn->prepare($logSql);

    if ($logStmt) {
        $logStmt->bind_param("ss", $email, $ipAddress);
        $logStmt->execute();
    }

    header("Location: login.php?error=invalid");
    exit;
}

session_regenerate_id(true);

session_regenerate_id(true);

$_SESSION["admin_id"] = (int) $user["id"];
$_SESSION["admin_name"] = $user["name"];
$_SESSION["admin_email"] = $user["email"];

$successLogSql = "INSERT INTO admin_login_attempts (email, ip_address, is_success)
                  VALUES (?, ?, 1)";
$successLogStmt = $conn->prepare($successLogSql);

if ($successLogStmt) {
    $successLogStmt->bind_param("ss", $email, $ipAddress);
    $successLogStmt->execute();
}

$updateSql = "UPDATE admin_users SET last_login = NOW() WHERE id = ?";
$updateStmt = $conn->prepare($updateSql);

if ($updateStmt) {
    $updateStmt->bind_param("i", $user["id"]);
    $updateStmt->execute();
}

header("Location: dashboard.php");
exit;