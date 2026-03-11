<?php
require_once "auth-check.php";
require_once "../db.php";

function redirectWithStatus(string $status): void
{
    header("Location: change-password.php?status=" . urlencode($status));
    exit;
}

function isStrongPassword(string $password): bool
{
    if (strlen($password) < 12 || strlen($password) > 128) {
        return false;
    }

    return preg_match('/[a-z]/', $password)
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[^a-zA-Z0-9]/', $password);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirectWithStatus("error");
}

if (
    empty($_POST["csrf_token"]) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    redirectWithStatus("error");
}

$currentPassword = is_string($_POST["current_password"] ?? null) ? $_POST["current_password"] : "";
$newPassword = is_string($_POST["new_password"] ?? null) ? $_POST["new_password"] : "";
$confirmPassword = is_string($_POST["confirm_password"] ?? null) ? $_POST["confirm_password"] : "";

if ($currentPassword === "" || $newPassword === "" || $confirmPassword === "") {
    redirectWithStatus("error");
}

if ($newPassword !== $confirmPassword) {
    redirectWithStatus("mismatch");
}

if (!isStrongPassword($newPassword)) {
    redirectWithStatus("weak");
}

if (!isset($_SESSION["admin_id"]) || !is_numeric($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$adminId = (int) $_SESSION["admin_id"];
$sql = "SELECT password_hash FROM admin_users WHERE id = ? AND is_active = 1 LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    redirectWithStatus("error");
}

$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if (!$admin || !isset($admin["password_hash"])) {
    redirectWithStatus("error");
}

if (!password_verify($currentPassword, $admin["password_hash"])) {
    redirectWithStatus("invalid_current");
}

if (password_verify($newPassword, $admin["password_hash"])) {
    redirectWithStatus("reused");
}

$newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

if ($newPasswordHash === false) {
    redirectWithStatus("error");
}

$updateSql = "UPDATE admin_users SET password_hash = ?, updated_at = NOW() WHERE id = ? LIMIT 1";
$updateStmt = $conn->prepare($updateSql);

if (!$updateStmt) {
    redirectWithStatus("error");
}

$updateStmt->bind_param("si", $newPasswordHash, $adminId);
$ok = $updateStmt->execute();
$updateStmt->close();

if (!$ok) {
    redirectWithStatus("error");
}

session_regenerate_id(true);
$_SESSION["csrf_token"] = bin2hex(random_bytes(32));

redirectWithStatus("success");
