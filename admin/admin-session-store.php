<?php

function getAdminSessionIpAddress(): string
{
    return $_SERVER["REMOTE_ADDR"] ?? "0.0.0.0";
}

function getAdminSessionUserAgent(): string
{
    $userAgent = $_SERVER["HTTP_USER_AGENT"] ?? "";

    if ($userAgent === "") {
        return "";
    }

    return substr($userAgent, 0, 255);
}

function upsertAdminUserSession(mysqli $conn, int $adminUserId, string $sessionId): bool
{
    if ($adminUserId <= 0 || $sessionId === "") {
        return false;
    }

    $ipAddress = getAdminSessionIpAddress();
    $userAgent = getAdminSessionUserAgent();
    $findSql = "SELECT id FROM admin_user_sessions WHERE session_id = ? LIMIT 1";
    $findStmt = $conn->prepare($findSql);

    if (!$findStmt) {
        return false;
    }

    $findStmt->bind_param("s", $sessionId);
    $findStmt->execute();
    $findResult = $findStmt->get_result();
    $existingSession = $findResult->fetch_assoc();
    $findStmt->close();

    if ($existingSession) {
        $updateSql = "UPDATE admin_user_sessions
                      SET admin_user_id = ?, is_active = 1, ip_address = ?, user_agent = ?,
                          last_activity_at = NOW(), updated_at = NOW()
                      WHERE id = ?
                      LIMIT 1";
        $updateStmt = $conn->prepare($updateSql);

        if (!$updateStmt) {
            return false;
        }

        $sessionRowId = (int) $existingSession["id"];
        $updateStmt->bind_param("issi", $adminUserId, $ipAddress, $userAgent, $sessionRowId);
        $ok = $updateStmt->execute();
        $updateStmt->close();

        return $ok;
    }

    $insertSql = "INSERT INTO admin_user_sessions
                  (admin_user_id, session_id, is_active, ip_address, user_agent, last_activity_at, created_at, updated_at)
                  VALUES (?, ?, 1, ?, ?, NOW(), NOW(), NOW())";
    $insertStmt = $conn->prepare($insertSql);

    if (!$insertStmt) {
        return false;
    }

    $insertStmt->bind_param("isss", $adminUserId, $sessionId, $ipAddress, $userAgent);
    $ok = $insertStmt->execute();
    $insertStmt->close();

    return $ok;
}

function isAdminUserSessionActive(mysqli $conn, int $adminUserId, string $sessionId): bool
{
    if ($adminUserId <= 0 || $sessionId === "") {
        return false;
    }

    $selectSql = "SELECT id
                  FROM admin_user_sessions
                  WHERE admin_user_id = ? AND session_id = ? AND is_active = 1
                  LIMIT 1";
    $selectStmt = $conn->prepare($selectSql);

    if (!$selectStmt) {
        return false;
    }

    $selectStmt->bind_param("is", $adminUserId, $sessionId);
    $selectStmt->execute();
    $selectResult = $selectStmt->get_result();
    $sessionRow = $selectResult->fetch_assoc();
    $selectStmt->close();

    if (!$sessionRow) {
        return false;
    }

    $updateSql = "UPDATE admin_user_sessions
                  SET last_activity_at = NOW(), updated_at = NOW()
                  WHERE id = ?
                  LIMIT 1";
    $updateStmt = $conn->prepare($updateSql);

    if ($updateStmt) {
        $sessionRowId = (int) $sessionRow["id"];
        $updateStmt->bind_param("i", $sessionRowId);
        $updateStmt->execute();
        $updateStmt->close();
    }

    return true;
}

function deactivateAdminUserSession(mysqli $conn, int $adminUserId, string $sessionId): bool
{
    if ($adminUserId <= 0 || $sessionId === "") {
        return false;
    }

    $sql = "UPDATE admin_user_sessions
            SET is_active = 0, updated_at = NOW()
            WHERE admin_user_id = ? AND session_id = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("is", $adminUserId, $sessionId);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function deactivateOtherAdminUserSessions(mysqli $conn, int $adminUserId, string $currentSessionId): bool
{
    if ($adminUserId <= 0 || $currentSessionId === "") {
        return false;
    }

    $sql = "UPDATE admin_user_sessions
            SET is_active = 0, updated_at = NOW()
            WHERE admin_user_id = ? AND session_id <> ? AND is_active = 1";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("is", $adminUserId, $currentSessionId);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function destroyCurrentAdminPhpSession(): void
{
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
}
