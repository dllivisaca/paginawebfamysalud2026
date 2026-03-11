<?php
require_once "auth-check.php";
require_once "../db.php";
require_once "admin-session-store.php";
require_once "security-mailer.php";

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
$sql = "SELECT name, email, password_hash FROM admin_users WHERE id = ? AND is_active = 1 LIMIT 1";
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

$historySql = "SELECT password_hash
               FROM admin_password_history
               WHERE admin_user_id = ?
               ORDER BY id DESC
               LIMIT 5";
$historyStmt = $conn->prepare($historySql);

if (!$historyStmt) {
    redirectWithStatus("error");
}

$historyStmt->bind_param("i", $adminId);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();

while ($historyRow = $historyResult->fetch_assoc()) {
    if (isset($historyRow["password_hash"]) && password_verify($newPassword, $historyRow["password_hash"])) {
        $historyStmt->close();
        redirectWithStatus("reused");
    }
}

$historyStmt->close();

$newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

if ($newPasswordHash === false) {
    redirectWithStatus("error");
}

$conn->begin_transaction();

try {
    $insertHistorySql = "INSERT INTO admin_password_history (admin_user_id, password_hash)
                         VALUES (?, ?)";
    $insertHistoryStmt = $conn->prepare($insertHistorySql);

    if (!$insertHistoryStmt) {
        throw new Exception("No se pudo preparar el historial de contraseñas.");
    }

    $currentPasswordHash = $admin["password_hash"];
    $insertHistoryStmt->bind_param("is", $adminId, $currentPasswordHash);

    if (!$insertHistoryStmt->execute()) {
        $insertHistoryStmt->close();
        throw new Exception("No se pudo guardar la contraseña anterior en el historial.");
    }

    $insertHistoryStmt->close();

    $updateSql = "UPDATE admin_users
                  SET password_hash = ?, updated_at = NOW()
                  WHERE id = ?
                  LIMIT 1";
    $updateStmt = $conn->prepare($updateSql);

    if (!$updateStmt) {
        throw new Exception("No se pudo preparar la actualización de contraseña.");
    }

    $updateStmt->bind_param("si", $newPasswordHash, $adminId);

    if (!$updateStmt->execute()) {
        $updateStmt->close();
        throw new Exception("No se pudo actualizar la nueva contraseña.");
    }

    $updateStmt->close();

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    redirectWithStatus("error");
}

session_regenerate_id(true);
$_SESSION["csrf_token"] = bin2hex(random_bytes(32));

$currentSessionId = session_id();

if ($currentSessionId === "" || !upsertAdminUserSession($conn, $adminId, $currentSessionId)) {
    error_log("No se pudo registrar la sesion actual tras el cambio de contrasena.");
    destroyCurrentAdminPhpSession();
    header("Location: login.php?error=unavailable");
    exit;
}

if (!deactivateOtherAdminUserSessions($conn, $adminId, $currentSessionId)) {
    error_log("No se pudieron invalidar las otras sesiones activas del administrador.");
    destroyCurrentAdminPhpSession();
    header("Location: login.php?error=unavailable");
    exit;
}

if (!empty($admin["email"])) {
    sendPasswordChangedSecurityEmail(
        $admin["email"],
        $admin["name"] ?? ""
    );
}

redirectWithStatus("success");
