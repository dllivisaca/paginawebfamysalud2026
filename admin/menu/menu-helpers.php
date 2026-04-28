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
    $selectFields = "id, parent_id, item_key, label, url, display_order, is_active, is_button, target";
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

    $row["parent_id"] = isset($row["parent_id"]) ? (int) $row["parent_id"] : null;
    $row["link_type"] = ($row["link_type"] ?? "custom") === "internal" ? "internal" : "custom";
    $row["site_page_id"] = isset($row["site_page_id"]) ? (int) $row["site_page_id"] : 0;

    return $row;
}

function getMenuItemDepth(mysqli $conn, int $itemId): int
{
    if ($itemId <= 0) {
        return 0;
    }

    $depth = 0;
    $currentId = $itemId;
    $visited = [];

    while ($currentId > 0 && !isset($visited[$currentId]) && $depth < 20) {
        $visited[$currentId] = true;
        $item = getMenuItemById($conn, $currentId);

        if (!$item) {
            break;
        }

        $depth++;
        $currentId = isset($item["parent_id"]) ? (int) $item["parent_id"] : 0;
    }

    return $depth;
}

function wouldCreateMenuCycle(mysqli $conn, int $itemId, ?int $parentId): bool
{
    if ($itemId <= 0 || $parentId === null) {
        return false;
    }

    $currentId = $parentId;
    $visited = [];

    while ($currentId > 0 && !isset($visited[$currentId])) {
        if ($currentId === $itemId) {
            return true;
        }

        $visited[$currentId] = true;
        $item = getMenuItemById($conn, $currentId);

        if (!$item) {
            return false;
        }

        $currentId = isset($item["parent_id"]) ? (int) $item["parent_id"] : 0;
    }

    return false;
}

function validateMenuParent(mysqli $conn, int $itemId, ?int $parentId, ?string &$error = null): bool
{
    $error = null;

    if ($parentId === null) {
        return true;
    }

    if ($itemId > 0 && $parentId === $itemId) {
        $error = "Una opcion no puede ser superior de si misma.";
        return false;
    }

    $parentItem = getMenuItemById($conn, $parentId);
    if (!$parentItem || (int) ($parentItem["is_button"] ?? 0) !== 0 || isHomeMenuItem($parentItem)) {
        $error = "La opcion superior seleccionada no es valida.";
        return false;
    }

    if (wouldCreateMenuCycle($conn, $itemId, $parentId)) {
        $error = "La opcion superior seleccionada crearia un ciclo.";
        return false;
    }

    if (getMenuItemDepth($conn, $parentId) >= 3) {
        $error = "La jerarquia solo permite un maximo de 3 niveles.";
        return false;
    }

    return true;
}

function buildAdminMenuTree(array $items): array
{
    $itemsById = [];
    $itemsByParent = [];

    foreach ($items as $item) {
        $item["id"] = (int) ($item["id"] ?? 0);
        $item["parent_id"] = isset($item["parent_id"]) ? (int) $item["parent_id"] : null;
        $itemsById[$item["id"]] = $item;
    }

    foreach ($itemsById as $item) {
        $parentId = $item["parent_id"];
        $parentKey = ($parentId !== null && isset($itemsById[$parentId])) ? (string) $parentId : "root";
        $itemsByParent[$parentKey][] = $item;
    }

    foreach ($itemsByParent as &$siblings) {
        usort($siblings, function (array $first, array $second): int {
            $orderCompare = ((int) ($first["display_order"] ?? 0)) <=> ((int) ($second["display_order"] ?? 0));
            return $orderCompare !== 0 ? $orderCompare : ((int) ($first["id"] ?? 0)) <=> ((int) ($second["id"] ?? 0));
        });
    }
    unset($siblings);

    $flatTree = [];
    $visited = [];
    $appendChildren = function ($parentId, int $depth, string $prefix) use (&$appendChildren, &$itemsByParent, &$flatTree, &$visited): void {
        $parentKey = $parentId === null ? "root" : (string) $parentId;
        $position = 0;

        foreach ($itemsByParent[$parentKey] ?? [] as $item) {
            $itemId = (int) ($item["id"] ?? 0);
            if (isset($visited[$itemId])) {
                continue;
            }

            $visited[$itemId] = true;
            $position++;
            $number = $depth === 0 ? (string) ((int) ($item["display_order"] ?? $position)) : $prefix . "." . $position;
            $item["tree_depth"] = $depth;
            $item["tree_number"] = $number;
            $flatTree[] = $item;
            $appendChildren($itemId, $depth + 1, $number);
        }
    };

    $appendChildren(null, 0, "");

    foreach ($itemsById as $item) {
        $itemId = (int) ($item["id"] ?? 0);
        if (!isset($visited[$itemId])) {
            $item["tree_depth"] = 0;
            $item["tree_number"] = (string) ((int) ($item["display_order"] ?? 0));
            $flatTree[] = $item;
            $visited[$itemId] = true;
        }
    }

    return $flatTree;
}

function getMenuParentOptions(mysqli $conn, int $excludeItemId = 0): array
{
    $schema = getMenuItemsLinkSupport($conn);
    $selectFields = "id, parent_id, item_key, label, url, display_order, is_active, is_button, target";
    if ($schema["link_type"]) {
        $selectFields .= ", link_type";
    }
    if ($schema["site_page_id"]) {
        $selectFields .= ", site_page_id";
    }

    $items = [];
    $result = $conn->query("SELECT {$selectFields} FROM menu_items WHERE is_button = 0 ORDER BY display_order ASC, id ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row["id"] = (int) ($row["id"] ?? 0);
            $row["parent_id"] = isset($row["parent_id"]) ? (int) $row["parent_id"] : null;
            $row["link_type"] = ($row["link_type"] ?? "custom") === "internal" ? "internal" : "custom";
            $row["site_page_id"] = isset($row["site_page_id"]) ? (int) $row["site_page_id"] : 0;
            $items[] = $row;
        }
    }

    $options = [];
    foreach (buildAdminMenuTree($items) as $item) {
        $itemId = (int) ($item["id"] ?? 0);
        if ($itemId <= 0 || $itemId === $excludeItemId || isHomeMenuItem($item) || (int) ($item["tree_depth"] ?? 0) >= 2) {
            continue;
        }

        if (wouldCreateMenuCycle($conn, $excludeItemId, $itemId)) {
            continue;
        }

        $options[] = $item;
    }

    return $options;
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
