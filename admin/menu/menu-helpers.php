<?php

function redirectToMenu(string $status): void
{
    header("Location: index.php?status=" . urlencode($status));
    exit;
}

function redirectToMenuEdit(string $status, int $itemId = 0, bool $isCreate = false): void
{
    $query = ["status" => $status];

    if ($itemId > 0) {
        $query["id"] = $itemId;
    } elseif ($isCreate) {
        $query["action"] = "create";
    }

    header("Location: edit.php?" . http_build_query($query));
    exit;
}

function redirectToMenuButtonEdit(string $status, int $buttonId = 0, bool $isCreate = false): void
{
    $query = ["status" => $status];

    if ($buttonId > 0) {
        $query["id"] = $buttonId;
    } elseif ($isCreate) {
        $query["action"] = "create";
    }

    header("Location: button-edit.php?" . http_build_query($query));
    exit;
}

function textValue(string $value, int $maxLength = 255): string
{
    $value = trim($value);
    return $value === "" ? "" : substr($value, 0, $maxLength);
}

function menuPath(string $value): string
{
    $value = trim($value);
    $value = str_replace("\\", "/", $value);
    $value = preg_replace('#^https?://[^/]+/?#i', "", $value) ?? $value;
    $value = trim($value, "/");
    $value = preg_replace('/\.php$/i', "", $value) ?? $value;

    if (function_exists("iconv")) {
        $converted = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }

    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9\/\-\s]/', "", $value) ?? $value;
    $value = preg_replace('/\s+/', "-", $value) ?? $value;
    $value = preg_replace('/-+/', "-", $value) ?? $value;
    $value = preg_replace('#/+#', "/", $value) ?? $value;

    return trim($value, "-/");
}

function menuTarget(string $value): string
{
    return $value === "_blank" ? "_blank" : "_self";
}

function buttonPath(string $value): string
{
    return textValue(trim($value), 255);
}

function publicPageUrl(string $slug, string $pageKey = ""): string
{
    $slug = trim($slug, "/");
    $pageKey = trim($pageKey);

    if ($slug === "" || $slug === "inicio" || $slug === "home" || $pageKey === "home") {
        return "index.php";
    }

    return "page.php?slug=" . rawurlencode($slug);
}

function menuCustomUrl(string $value): string
{
    $value = textValue(str_replace("\\", "/", trim($value)), 255);

    if ($value === "") {
        return "";
    }

    if (
        preg_match('#^https?://#i', $value)
        || str_starts_with($value, "#")
        || preg_match('#^(mailto:|tel:)#i', $value)
        || preg_match('/\.php(?:[?#].*)?$/i', $value)
        || preg_match('/\.[a-z0-9]{2,5}(?:[?#].*)?$/i', $value)
    ) {
        return $value;
    }

    return menuPath($value);
}

function menuLinkType(string $value, bool $supportsLinkedPages): string
{
    if (!$supportsLinkedPages) {
        return "custom";
    }

    return $value === "internal" ? "internal" : "custom";
}

function getMenuItemsLinkSupport(mysqli $conn): array
{
    $fields = [];
    $result = $conn->query("SHOW COLUMNS FROM menu_items");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $fieldName = (string) ($row["Field"] ?? "");
            if ($fieldName !== "") {
                $fields[$fieldName] = true;
            }
        }
    }

    return [
        "link_type" => isset($fields["link_type"]),
        "site_page_id" => isset($fields["site_page_id"]),
    ];
}

function countByType(mysqli $conn, int $isButton): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM menu_items WHERE is_button = ?");
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param("i", $isButton);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return (int) ($row["total"] ?? 0);
}

function ensureHomeMenuItem(mysqli $conn): void
{
    $sql = "INSERT INTO menu_items (parent_id, item_key, label, url, display_order, is_active, is_button, target, created_at, updated_at)
            VALUES (NULL, 'home', 'Inicio', 'index.php', 1, 1, 0, '_self', NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                label = 'Inicio',
                url = 'index.php',
                display_order = 1,
                is_active = 1,
                is_button = 0,
                target = '_self',
                updated_at = NOW()";

    $conn->query($sql);
}

function getMenuItemById(mysqli $conn, int $id): ?array
{
    $schema = getMenuItemsLinkSupport($conn);
    $selectFields = "id, item_key, label, url, display_order, is_active, is_button, target";
    if ($schema["link_type"]) {
        $selectFields .= ", link_type";
    }
    if ($schema["site_page_id"]) {
        $selectFields .= ", site_page_id";
    }

    $stmt = $conn->prepare("SELECT {$selectFields} FROM menu_items WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    $row["link_type"] = ($row["link_type"] ?? "custom") === "internal" ? "internal" : "custom";
    $row["site_page_id"] = isset($row["site_page_id"]) ? (int) $row["site_page_id"] : 0;

    return $row;
}

function isHomeMenuItem(?array $item): bool
{
    return is_array($item) && (($item["item_key"] ?? "") === "home");
}

function getSitePageById(mysqli $conn, int $pageId): ?array
{
    $stmt = $conn->prepare("SELECT id, page_key, title, slug, is_active FROM site_pages WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $pageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function getMenuSitePages(mysqli $conn, array $linkedSitePageIds = [], bool $includeHomePage = false): array
{
    $sitePages = [];
    $sitePagesById = [];

    $sitePagesSql = "SELECT id, page_key, title, slug, is_active
                     FROM site_pages
                     WHERE 1 = 1";

    if (!$includeHomePage) {
        $sitePagesSql .= " AND page_key <> 'home'";
    }

    if ($linkedSitePageIds !== []) {
        $sitePagesSql .= " AND (is_active = 1 OR id IN (" . implode(",", array_map("intval", $linkedSitePageIds)) . "))";
    } else {
        $sitePagesSql .= " AND is_active = 1";
    }

    $sitePagesSql .= " ORDER BY is_active DESC, title ASC, id ASC";
    $sitePagesResult = $conn->query($sitePagesSql);

    if ($sitePagesResult) {
        while ($row = $sitePagesResult->fetch_assoc()) {
            $row["id"] = (int) ($row["id"] ?? 0);
            $row["is_active"] = (int) ($row["is_active"] ?? 0);
            $row["public_url"] = publicPageUrl((string) ($row["slug"] ?? ""), (string) ($row["page_key"] ?? ""));
            $sitePages[] = $row;
            $sitePagesById[$row["id"]] = $row;
        }
    }

    return [$sitePages, $sitePagesById];
}
