<?php

if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . "/../db.php";
}

require_once __DIR__ . "/../templates/page-schemas/registry.php";
if (!function_exists("publicPageUrl")) {
    function publicPageUrl(string $slug): string
    {
        $slug = trim($slug, "/");

        if ($slug === "" || $slug === "inicio") {
            return "index.php";
        }

        return "page.php?slug=" . rawurlencode($slug);
    }
}

function getPageContentTemplateSchema(string $templateKey): ?array
{
    $schemas = getPageTemplateContentSchemas();
    return $schemas[$templateKey] ?? null;
}

function pageContentSchemaSupportsTemplate(string $templateKey): bool
{
    return getPageContentTemplateSchema($templateKey) !== null;
}

function getPageContentSimpleDefaults(array $schema): array
{
    $defaults = [];

    foreach ($schema["simple_fields"] ?? [] as $field) {
        $fieldKey = (string) ($field["field_key"] ?? "");

        if ($fieldKey === "") {
            continue;
        }

        $defaults[$fieldKey] = [
            "field_key" => $fieldKey,
            "field_type" => (string) ($field["field_type"] ?? "text"),
            "field_value" => (string) ($field["default"] ?? ""),
            "is_visible" => isset($field["default_visible"]) ? (int) $field["default_visible"] : 1,
        ];
    }

    return $defaults;
}

function getPageContentRepeaterDefaults(array $schema): array
{
    $repeaters = [];

    foreach ($schema["repeaters"] ?? [] as $repeater) {
        $repeaterKey = (string) ($repeater["repeater_key"] ?? "");

        if ($repeaterKey === "") {
            continue;
        }

        $repeaters[$repeaterKey] = [];

        foreach ($repeater["items"] ?? [] as $itemConfig) {
            $fields = [];

            foreach ($repeater["fields"] ?? [] as $fieldConfig) {
                $fieldKey = (string) ($fieldConfig["field_key"] ?? "");

                if ($fieldKey === "") {
                    continue;
                }

                $fields[$fieldKey] = [
                    "field_key" => $fieldKey,
                    "field_type" => (string) ($fieldConfig["field_type"] ?? "text"),
                    "field_value" => (string) ($itemConfig["defaults"][$fieldKey] ?? $fieldConfig["default"] ?? ""),
                    "is_visible" => 1,
                ];
            }

            $itemIndex = (int) ($itemConfig["item_index"] ?? -1);

            if ($itemIndex < 0) {
                continue;
            }

            $repeaters[$repeaterKey][$itemIndex] = [
                "item_index" => $itemIndex,
                "is_visible" => isset($itemConfig["default_visible"]) ? (int) $itemConfig["default_visible"] : 1,
                "fields" => $fields,
            ];
        }

        ksort($repeaters[$repeaterKey]);
    }

    return $repeaters;
}

function ensurePageContentRepeaterItems(mysqli $conn, int $pageId, array $schema): void
{
    if ($pageId <= 0) {
        return;
    }

    foreach ($schema["repeaters"] ?? [] as $repeater) {
        $repeaterKey = (string) ($repeater["repeater_key"] ?? "");

        if ($repeaterKey === "") {
            continue;
        }

        foreach ($repeater["items"] ?? [] as $itemConfig) {
            $itemIndex = (int) ($itemConfig["item_index"] ?? -1);

            if ($itemIndex < 0) {
                continue;
            }

            $defaultVisible = isset($itemConfig["default_visible"]) ? (int) $itemConfig["default_visible"] : 1;
            $stmt = $conn->prepare(
                "INSERT INTO site_page_content_repeater_items
                    (site_page_id, repeater_key, item_index, is_visible)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE updated_at = updated_at"
            );

            if (!$stmt) {
                continue;
            }

            $stmt->bind_param("isii", $pageId, $repeaterKey, $itemIndex, $defaultVisible);
            $stmt->execute();
            $stmt->close();
        }
    }
}

function getPageContentData(mysqli $conn, int $pageId, array $schema): array
{
    $simpleFields = getPageContentSimpleDefaults($schema);
    $repeaters = getPageContentRepeaterDefaults($schema);

    if ($pageId <= 0) {
        return [
            "simple_fields" => $simpleFields,
            "repeaters" => $repeaters,
        ];
    }

    $simpleStmt = $conn->prepare(
        "SELECT field_key, field_type, field_value, is_visible
         FROM site_page_content_fields
         WHERE site_page_id = ?"
    );

    if ($simpleStmt) {
        $simpleStmt->bind_param("i", $pageId);
        $simpleStmt->execute();
        $simpleResult = $simpleStmt->get_result();

        while ($simpleResult && ($row = $simpleResult->fetch_assoc())) {
            $fieldKey = (string) ($row["field_key"] ?? "");

            if ($fieldKey === "" || !isset($simpleFields[$fieldKey])) {
                continue;
            }

            $simpleFields[$fieldKey]["field_type"] = (string) ($row["field_type"] ?? $simpleFields[$fieldKey]["field_type"]);
            $simpleFields[$fieldKey]["field_value"] = (string) ($row["field_value"] ?? "");
            $simpleFields[$fieldKey]["is_visible"] = (int) ($row["is_visible"] ?? 1);
        }

        $simpleStmt->close();
    }

    $repeaterStmt = $conn->prepare(
        "SELECT ri.id, ri.repeater_key, ri.item_index, ri.is_visible, rif.field_key, rif.field_type, rif.field_value
         FROM site_page_content_repeater_items ri
         LEFT JOIN site_page_content_repeater_item_fields rif ON rif.repeater_item_id = ri.id
         WHERE ri.site_page_id = ?
         ORDER BY ri.repeater_key ASC, ri.item_index ASC, rif.id ASC"
    );

    if ($repeaterStmt) {
        $repeaterStmt->bind_param("i", $pageId);
        $repeaterStmt->execute();
        $repeaterResult = $repeaterStmt->get_result();

        while ($repeaterResult && ($row = $repeaterResult->fetch_assoc())) {
            $repeaterKey = (string) ($row["repeater_key"] ?? "");
            $itemIndex = (int) ($row["item_index"] ?? -1);

            if ($repeaterKey === "" || $itemIndex < 0 || !isset($repeaters[$repeaterKey][$itemIndex])) {
                continue;
            }

            $repeaters[$repeaterKey][$itemIndex]["is_visible"] = (int) ($row["is_visible"] ?? 1);
            $fieldKey = (string) ($row["field_key"] ?? "");

            if (str_starts_with($fieldKey, "__visible_")) {
                $visibleFieldKey = substr($fieldKey, 10);

                if (isset($repeaters[$repeaterKey][$itemIndex]["fields"][$visibleFieldKey])) {
                    $repeaters[$repeaterKey][$itemIndex]["fields"][$visibleFieldKey]["is_visible"] = (string) ($row["field_value"] ?? "1") === "1" ? 1 : 0;
                }

                continue;
            }

            if ($fieldKey === "" || !isset($repeaters[$repeaterKey][$itemIndex]["fields"][$fieldKey])) {
                continue;
            }

            $repeaters[$repeaterKey][$itemIndex]["fields"][$fieldKey]["field_type"] = (string) ($row["field_type"] ?? $repeaters[$repeaterKey][$itemIndex]["fields"][$fieldKey]["field_type"]);
            $repeaters[$repeaterKey][$itemIndex]["fields"][$fieldKey]["field_value"] = (string) ($row["field_value"] ?? "");
        }

        $repeaterStmt->close();
    }

    return [
        "simple_fields" => $simpleFields,
        "repeaters" => $repeaters,
    ];
}

function upsertPageContentField(mysqli $conn, int $pageId, string $fieldKey, string $fieldType, string $fieldValue, int $isVisible): bool
{
    $stmt = $conn->prepare(
        "INSERT INTO site_page_content_fields
            (site_page_id, field_key, field_type, field_value, is_visible)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            field_type = VALUES(field_type),
            field_value = VALUES(field_value),
            is_visible = VALUES(is_visible),
            updated_at = CURRENT_TIMESTAMP"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("isssi", $pageId, $fieldKey, $fieldType, $fieldValue, $isVisible);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function getRepeaterItemId(mysqli $conn, int $pageId, string $repeaterKey, int $itemIndex, int $visible): int
{
    $stmt = $conn->prepare(
        "INSERT INTO site_page_content_repeater_items
            (site_page_id, repeater_key, item_index, is_visible)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            is_visible = VALUES(is_visible),
            updated_at = CURRENT_TIMESTAMP,
            id = LAST_INSERT_ID(id)"
    );

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("isii", $pageId, $repeaterKey, $itemIndex, $visible);
    $ok = $stmt->execute();
    $insertId = $ok ? (int) $conn->insert_id : 0;
    $stmt->close();

    return $insertId;
}

function upsertRepeaterItemField(mysqli $conn, int $repeaterItemId, string $fieldKey, string $fieldType, string $fieldValue): bool
{
    $stmt = $conn->prepare(
        "INSERT INTO site_page_content_repeater_item_fields
            (repeater_item_id, field_key, field_type, field_value)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            field_type = VALUES(field_type),
            field_value = VALUES(field_value),
            updated_at = CURRENT_TIMESTAMP"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("isss", $repeaterItemId, $fieldKey, $fieldType, $fieldValue);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function buildPageContentView(array $schema, array $contentData): array
{
    $simpleView = [];

    foreach ($contentData["simple_fields"] ?? [] as $fieldKey => $fieldData) {
        $simpleView[$fieldKey] = [
            "value" => (string) ($fieldData["field_value"] ?? ""),
            "is_visible" => (int) ($fieldData["is_visible"] ?? 1) === 1,
        ];
    }

    $repeatersView = [];

    foreach ($contentData["repeaters"] ?? [] as $repeaterKey => $items) {
        $repeatersView[$repeaterKey] = [];

        foreach ($items as $itemIndex => $itemData) {
            $fields = [];
            $fieldVisibility = [];

            foreach ($itemData["fields"] ?? [] as $fieldKey => $fieldData) {
                $fields[$fieldKey] = (string) ($fieldData["field_value"] ?? "");
                $fieldVisibility[$fieldKey] = (int) ($fieldData["is_visible"] ?? 1) === 1;
            }

            $repeatersView[$repeaterKey][$itemIndex] = [
                "is_visible" => (int) ($itemData["is_visible"] ?? 1) === 1,
                "fields" => $fields,
                "field_visibility" => $fieldVisibility,
            ];
        }
    }

    return [
        "simple_fields" => $simpleView,
        "repeaters" => $repeatersView,
    ];
}
function getPageContentLinkablePages(mysqli $conn, bool $includeHomePage = true): array
{
    static $cache = [];

    $cacheKey = $includeHomePage ? "with_home" : "without_home";

    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    $pages = [];
    $pagesById = [];
    $sql = "SELECT id, page_key, title, slug, is_active
            FROM site_pages
            WHERE is_active = 1";

    if (!$includeHomePage) {
        $sql .= " AND page_key <> 'home'";
    }

    $sql .= " ORDER BY title ASC, id ASC";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row["id"] = (int) ($row["id"] ?? 0);
            $row["public_url"] = publicPageUrl((string) ($row["slug"] ?? ""));
            $pages[] = $row;
            $pagesById[$row["id"]] = $row;
        }
    }

    $cache[$cacheKey] = [$pages, $pagesById];
    return $cache[$cacheKey];
}

function resolvePageContentLinkHref(mysqli $conn, array $simpleFields, string $prefix, string $fallbackUrl = ""): string
{
    $linkType = trim((string) ($simpleFields[$prefix . "_link_type"]["value"] ?? ""));
    $pageId = (int) trim((string) ($simpleFields[$prefix . "_page_id"]["value"] ?? "0"));
    $customUrl = trim((string) ($simpleFields[$prefix . "_url"]["value"] ?? ""));

    if ($linkType === "internal" && $pageId > 0) {
        [, $pagesById] = getPageContentLinkablePages($conn, true);

        if (isset($pagesById[$pageId])) {
            return (string) ($pagesById[$pageId]["public_url"] ?? "");
        }
    }

    if ($customUrl !== "") {
        return $customUrl;
    }

    return $fallbackUrl;
}

