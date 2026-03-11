<?php
require_once "auth-check.php";

/*
|--------------------------------------------------------------------------
| IMPORTANTE
|--------------------------------------------------------------------------
| Reemplaza la siguiente línea por el archivo real donde creas $conn
| o tu conexión mysqli.
*/
require_once "../db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: change-password.php?status=error");
    exit;
}

if (
    empty($_POST["csrf_token"]) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    header("Location: change-password.php?status=error");
    exit;
}

$currentPassword = $_POST["current_password"] ?? "";
$newPassword = $_POST["new_password"] ?? "";
$confirmPassword = $_POST["confirm_password"] ?? "";

$currentPassword = trim($currentPassword);
$newPassword = trim($newPassword);
$confirmPassword = trim($confirmPassword);

if ($currentPassword === "" || $newPassword === "" || $confirmPassword === "") {
    header("Location: change-password.php?status=error");
    exit;
}

if ($newPassword !== $confirmPassword) {
    header("Location: change-password.php?status=mismatch");
    exit;
}

/*
|--------------------------------------------------------------------------
| Reglas mínimas de contraseña
|--------------------------------------------------------------------------
| Ajusté algo razonable y simple:
| - mínimo 8 caracteres
| - al menos 1 letra minúscula
| - al menos 1 letra mayúscula
| - al menos 1 número
*/
if (
    strlen($newPassword) < 8 ||
    !preg_match('/[a-z]/', $newPassword) ||
    !preg_match('/[A-Z]/', $newPassword) ||
    !preg_match('/[0-9]/', $newPassword)
) {
    header("Location: change-password.php?status=weak");
    exit;
}

if (!isset($_SESSION["admin_id"]) || !is_numeric($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$adminId = (int) $_SESSION["admin_id"];

/*
|--------------------------------------------------------------------------
| Verificar contraseña actual
|--------------------------------------------------------------------------
*/
$sql = "SELECT password_hash FROM admin_users WHERE id = ? AND is_active = 1 LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    header("Location: change-password.php?status=error");
    exit;
}

$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if (!$admin) {
    header("Location: change-password.php?status=error");
    exit;
}

if (!password_verify($currentPassword, $admin["password_hash"])) {
    header("Location: change-password.php?status=invalid_current");
    exit;
}

/*
|--------------------------------------------------------------------------
| Evitar reutilizar la misma contraseña
|--------------------------------------------------------------------------
*/
if (password_verify($newPassword, $admin["password_hash"])) {
    header("Location: change-password.php?status=weak");
    exit;
}

$newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

/*
|--------------------------------------------------------------------------
| Actualizar contraseña
|--------------------------------------------------------------------------
*/
$updateSql = "UPDATE admin_users SET password_hash = ?, updated_at = NOW() WHERE id = ? LIMIT 1";
$updateStmt = $conn->prepare($updateSql);

if (!$updateStmt) {
    header("Location: change-password.php?status=error");
    exit;
}

$updateStmt->bind_param("si", $newPasswordHash, $adminId);
$ok = $updateStmt->execute();
$updateStmt->close();

if (!$ok) {
    header("Location: change-password.php?status=error");
    exit;
}

header("Location: change-password.php?status=success");
exit;