<?php
require_once "session-bootstrap.php";
require_once "../db.php";
require_once "admin-session-store.php";

function failLogin(): void
{
    header("Location: login.php?error=invalid");
    exit;
}

function logFailedAttempt(mysqli $conn, string $email, string $ipAddress): void
{
    $logSql = "INSERT INTO admin_login_attempts (email, ip_address, is_success)
               VALUES (?, ?, 0)";
    $logStmt = $conn->prepare($logSql);

    if ($logStmt) {
        $logStmt->bind_param("ss", $email, $ipAddress);
        $logStmt->execute();
    }
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$csrfToken = $_POST["csrf_token"] ?? "";

if (!isset($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $csrfToken)) {
    failLogin();
}

$ipAddress = getAdminSessionIpAddress();
$maxAttempts = 5;
$lockMinutes = 15;

if ($email === "" || $password === "") {
    failLogin();
}

$attemptSql = "SELECT COUNT(*) AS total_failed
               FROM admin_login_attempts
               WHERE attempted_at >= (NOW() - INTERVAL ? MINUTE)
                 AND is_success = 0
                 AND (email = ? OR ip_address = ?)";

$attemptStmt = $conn->prepare($attemptSql);

if (!$attemptStmt) {
    error_log("No se pudo preparar la consulta de intentos de login.");
    header("Location: login.php?error=unavailable");
    exit;
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
    error_log("No se pudo preparar la consulta de autenticacion.");
    header("Location: login.php?error=unavailable");
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    logFailedAttempt($conn, $email, $ipAddress);
    failLogin();
}

$user = $result->fetch_assoc();

if ((int) $user["is_active"] !== 1) {
    logFailedAttempt($conn, $email, $ipAddress);
    failLogin();
}

if (!password_verify($password, $user["password_hash"])) {
    logFailedAttempt($conn, $email, $ipAddress);
    failLogin();
}

session_regenerate_id(true);

$_SESSION["admin_id"] = (int) $user["id"];
$_SESSION["admin_name"] = $user["name"];
$_SESSION["admin_email"] = $user["email"];
$_SESSION["csrf_token"] = bin2hex(random_bytes(32));

$currentSessionId = session_id();

if ($currentSessionId === "" || !upsertAdminUserSession($conn, (int) $user["id"], $currentSessionId)) {
    error_log("No se pudo registrar la sesion activa del administrador.");
    destroyCurrentAdminPhpSession();
    header("Location: login.php?error=unavailable");
    exit;
}

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
