<?php
require_once "../auth-check.php";
require_once "../../db.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function redirectToMenu(string $status): void
{
    header("Location: index.php?status=" . urlencode($status));
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
    $selectFields = "id, item_key, is_button";
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

    return $row ?: null;
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

$status = $_GET["status"] ?? "";

ensureHomeMenuItem($conn);
$menuLinkSchema = getMenuItemsLinkSupport($conn);
$supportsMenuLinkTypes = $menuLinkSchema["link_type"] && $menuLinkSchema["site_page_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = $_POST["csrf_token"] ?? "";
    if (!isset($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $csrfToken)) {
        redirectToMenu("error");
    }

    $action = $_POST["action"] ?? "";

    if ($action === "create_option") {
        if (countByType($conn, 0) >= 8) {
            redirectToMenu("limit_options");
        }

        $name = textValue($_POST["name"] ?? "");
        $position = filter_input(INPUT_POST, "display_order", FILTER_VALIDATE_INT);
        $target = menuTarget($_POST["target"] ?? "_self");
        $isActive = isset($_POST["is_active"]) ? 1 : 0;
        $linkType = menuLinkType((string) ($_POST["link_type"] ?? "custom"), $supportsMenuLinkTypes);
        $sitePageId = filter_input(INPUT_POST, "site_page_id", FILTER_VALIDATE_INT);
        $linkedPage = null;

        if ($linkType === "internal" && !$supportsMenuLinkTypes) {
            redirectToMenu("migration_required");
        }

        if ($linkType === "internal") {
            if ($sitePageId === false || $sitePageId === null) {
                redirectToMenu("invalid");
            }

            $linkedPage = getSitePageById($conn, (int) $sitePageId);
            if (!$linkedPage || ($linkedPage["page_key"] ?? "") === "home") {
                redirectToMenu("invalid");
            }

            if ($name === "") {
                $name = textValue((string) ($linkedPage["title"] ?? ""));
            }

            $path = publicPageUrl((string) ($linkedPage["slug"] ?? ""), (string) ($linkedPage["page_key"] ?? ""));
        } else {
            $path = menuCustomUrl($_POST["path"] ?? "");
            $sitePageId = null;
        }

        if ($name === "" || $path === "" || $position === false || $position < 2 || $position > 8) {
            redirectToMenu("invalid");
        }

        if ($supportsMenuLinkTypes) {
            $stmt = $conn->prepare("INSERT INTO menu_items (parent_id, label, link_type, site_page_id, url, display_order, is_active, is_button, target, created_at, updated_at) VALUES (NULL, ?, ?, ?, ?, ?, ?, 0, ?, NOW(), NOW())");
        } else {
            $stmt = $conn->prepare("INSERT INTO menu_items (parent_id, label, url, display_order, is_active, is_button, target, created_at, updated_at) VALUES (NULL, ?, ?, ?, ?, 0, ?, NOW(), NOW())");
        }

        if (!$stmt) {
            redirectToMenu("error");
        }

        if ($supportsMenuLinkTypes) {
            $stmt->bind_param("ssisiis", $name, $linkType, $sitePageId, $path, $position, $isActive, $target);
        } else {
            $stmt->bind_param("ssiis", $name, $path, $position, $isActive, $target);
        }
        $ok = $stmt->execute();
        $stmt->close();
        redirectToMenu($ok ? "created" : "error");
    }

    if ($action === "save_button") {
        $buttonId = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
        $text = textValue($_POST["button_text"] ?? "");
        $path = buttonPath($_POST["button_path"] ?? "");
        $target = menuTarget($_POST["target"] ?? "_self");
        $isActive = isset($_POST["is_active"]) ? 1 : 0;

        if ($text === "" || $path === "") {
            redirectToMenu("invalid");
        }

        if (($buttonId === false || $buttonId === null) && countByType($conn, 1) >= 1) {
            redirectToMenu("limit_button");
        }

        if ($buttonId !== false && $buttonId !== null) {
            $stmt = $conn->prepare("UPDATE menu_items SET label = ?, url = ?, is_active = ?, target = ?, updated_at = NOW() WHERE id = ? AND is_button = 1 LIMIT 1");
            if (!$stmt) {
                redirectToMenu("error");
            }
            $stmt->bind_param("ssisi", $text, $path, $isActive, $target, $buttonId);
            $ok = $stmt->execute();
            $stmt->close();
            redirectToMenu($ok ? "updated" : "error");
        }

        $stmt = $conn->prepare("INSERT INTO menu_items (parent_id, label, url, display_order, is_active, is_button, target, created_at, updated_at) VALUES (NULL, ?, ?, 9, ?, 1, ?, NOW(), NOW())");
        if (!$stmt) {
            redirectToMenu("error");
        }
        $stmt->bind_param("ssis", $text, $path, $isActive, $target);
        $ok = $stmt->execute();
        $stmt->close();
        redirectToMenu($ok ? "created" : "error");
    }

    if ($action === "update_option") {
        $itemId = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
        $name = textValue($_POST["name"] ?? "");
        $position = filter_input(INPUT_POST, "display_order", FILTER_VALIDATE_INT);
        $linkType = menuLinkType((string) ($_POST["link_type"] ?? "custom"), $supportsMenuLinkTypes);
        $sitePageId = filter_input(INPUT_POST, "site_page_id", FILTER_VALIDATE_INT);
        $linkedPage = null;

        if ($itemId === false || $itemId === null) {
            redirectToMenu("invalid");
        }

        $currentItem = getMenuItemById($conn, (int) $itemId);
        if (!$currentItem || (int) ($currentItem["is_button"] ?? 0) !== 0) {
            redirectToMenu("invalid");
        }

        if (isHomeMenuItem($currentItem)) {
            redirectToMenu("protected");
        }

        if ($linkType === "internal" && !$supportsMenuLinkTypes) {
            redirectToMenu("migration_required");
        }

        if ($linkType === "internal") {
            if ($sitePageId === false || $sitePageId === null) {
                redirectToMenu("invalid");
            }

            $linkedPage = getSitePageById($conn, (int) $sitePageId);
            if (!$linkedPage || ($linkedPage["page_key"] ?? "") === "home") {
                redirectToMenu("invalid");
            }

            if ($name === "") {
                $name = textValue((string) ($linkedPage["title"] ?? ""));
            }

            $path = publicPageUrl((string) ($linkedPage["slug"] ?? ""), (string) ($linkedPage["page_key"] ?? ""));
        } else {
            $path = menuCustomUrl($_POST["path"] ?? "");
            $sitePageId = null;
        }

        if ($name === "" || $path === "" || $position === false || $position < 2 || $position > 8) {
            redirectToMenu("invalid");
        }

        if ($supportsMenuLinkTypes) {
            $stmt = $conn->prepare("UPDATE menu_items SET label = ?, link_type = ?, site_page_id = ?, url = ?, display_order = ?, updated_at = NOW() WHERE id = ? AND is_button = 0 LIMIT 1");
        } else {
            $stmt = $conn->prepare("UPDATE menu_items SET label = ?, url = ?, display_order = ?, updated_at = NOW() WHERE id = ? AND is_button = 0 LIMIT 1");
        }

        if (!$stmt) {
            redirectToMenu("error");
        }

        if ($supportsMenuLinkTypes) {
            $stmt->bind_param("ssisii", $name, $linkType, $sitePageId, $path, $position, $itemId);
        } else {
            $stmt->bind_param("ssii", $name, $path, $position, $itemId);
        }
        $ok = $stmt->execute();
        $stmt->close();
        redirectToMenu($ok ? "updated" : "error");
    }

    if ($action === "toggle") {
        $itemId = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
        $nextState = filter_input(INPUT_POST, "next_state", FILTER_VALIDATE_INT);

        if ($itemId === false || $itemId === null || ($nextState !== 0 && $nextState !== 1)) {
            redirectToMenu("invalid");
        }

        $currentItem = getMenuItemById($conn, (int) $itemId);
        if (!$currentItem) {
            redirectToMenu("invalid");
        }

        if (isHomeMenuItem($currentItem)) {
            redirectToMenu("protected");
        }

        $stmt = $conn->prepare("UPDATE menu_items SET is_active = ?, updated_at = NOW() WHERE id = ? LIMIT 1");
        if (!$stmt) {
            redirectToMenu("error");
        }
        $stmt->bind_param("ii", $nextState, $itemId);
        $ok = $stmt->execute();
        $stmt->close();
        redirectToMenu($ok ? "toggled" : "error");
    }

    if ($action === "delete") {
        $itemId = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

        if ($itemId === false || $itemId === null) {
            redirectToMenu("invalid");
        }

        $currentItem = getMenuItemById($conn, (int) $itemId);
        if (!$currentItem) {
            redirectToMenu("invalid");
        }

        if (isHomeMenuItem($currentItem)) {
            redirectToMenu("protected");
        }

        $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ? LIMIT 1");
        if (!$stmt) {
            redirectToMenu("error");
        }
        $stmt->bind_param("i", $itemId);
        $ok = $stmt->execute();
        $stmt->close();
        redirectToMenu($ok ? "deleted" : "error");
    }

    redirectToMenu("error");
}

$items = [];
$menuOptions = [];
$primaryButton = null;
$homeItem = null;
$selectFields = "id, item_key, label, url, display_order, is_active, is_button, target";
if ($supportsMenuLinkTypes) {
    $selectFields .= ", link_type, site_page_id";
}
$result = $conn->query("SELECT {$selectFields} FROM menu_items ORDER BY is_button ASC, display_order ASC, id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($supportsMenuLinkTypes) {
            $row["link_type"] = ($row["link_type"] ?? "custom") === "internal" ? "internal" : "custom";
            $row["site_page_id"] = isset($row["site_page_id"]) ? (int) $row["site_page_id"] : 0;
        } else {
            $row["link_type"] = "custom";
            $row["site_page_id"] = 0;
        }
        $items[] = $row;
    }
}

foreach ($items as $item) {
    if ((int) $item["is_button"] === 1) {
        if ($primaryButton === null) {
            $primaryButton = $item;
        }
    } else {
        if (($item["item_key"] ?? "") === "home") {
            $homeItem = $item;
        } else {
            $menuOptions[] = $item;
        }
    }
}

$linkedSitePageIds = [];
foreach ($menuOptions as $item) {
    if (($item["link_type"] ?? "custom") === "internal" && (int) ($item["site_page_id"] ?? 0) > 0) {
        $linkedSitePageIds[] = (int) $item["site_page_id"];
    }
}

$linkedSitePageIds = array_values(array_unique($linkedSitePageIds));
$sitePages = [];
$sitePagesById = [];

if ($supportsMenuLinkTypes) {
    $sitePagesSql = "SELECT id, page_key, title, slug, is_active
                     FROM site_pages
                     WHERE page_key <> 'home'";

    if ($linkedSitePageIds !== []) {
        $sitePagesSql .= " AND (is_active = 1 OR id IN (" . implode(",", $linkedSitePageIds) . "))";
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
}

foreach ($menuOptions as &$item) {
    $linkedPageId = (int) ($item["site_page_id"] ?? 0);
    $item["linked_page"] = $linkedPageId > 0 && isset($sitePagesById[$linkedPageId]) ? $sitePagesById[$linkedPageId] : null;
}
unset($item);

$buttonConfigured = $primaryButton !== null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Men&uacute; de navegaci&oacute;n</title>
    <style>
        *{box-sizing:border-box}body{font-family:Arial,sans-serif;background:#f4f6f9;margin:0;color:#1f2937}.layout{min-height:100vh;display:flex;align-items:flex-start}.sidebar{width:260px;background:#fff;border-right:1px solid #e5e7eb;padding:24px 18px;display:flex;flex-direction:column;gap:22px;position:sticky;top:0;height:100vh;overflow-y:auto;flex-shrink:0}.brand{padding-bottom:18px;border-bottom:1px solid #e5e7eb}.brand h2{margin:0;font-size:22px;color:#198754}.brand p{margin:8px 0 0;color:#6b7280;font-size:14px;line-height:1.4}.sidebar-section-title{margin:0 0 10px;font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af}.nav{display:flex;flex-direction:column;gap:8px}.nav a{display:flex;align-items:center;gap:10px;text-decoration:none;color:#374151;padding:11px 12px;border-radius:10px;transition:background .2s ease,color .2s ease}.nav a:hover{background:#eef8f2;color:#198754}.nav a.active{background:#e9f7ef;color:#198754;font-weight:bold}.nav-icon{width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center}.main{flex:1;padding:32px;min-width:0}.topbar{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:24px}.page-title{margin:0;font-size:34px;line-height:1.1}.page-subtitle{margin:10px 0 0;font-size:16px;color:#6b7280;max-width:700px}.topbar-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.btn{display:inline-block;padding:10px 16px;border-radius:10px;text-decoration:none;border:0;cursor:pointer;font-size:14px;transition:background .2s ease,transform .2s ease}.btn:hover{transform:translateY(-1px)}.btn-outline{background:#fff;color:#198754;border:1px solid #cfe7d8}.btn-outline:hover{background:#eef8f2}.btn-logout{background:#dc3545;color:#fff}.btn-logout:hover{background:#bb2d3b}.btn-primary{background:#198754;color:#fff}.btn-primary:hover{background:#157347}.btn-soft{background:#fff4db;color:#996b00;border:1px solid #f5deb3}.btn-soft:hover{background:#fdecc8}.card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:24px;box-shadow:0 8px 24px rgba(0,0,0,.04);margin-bottom:18px}.card h2,.section-title{margin:0 0 10px;font-size:22px}.card p{margin:0 0 18px;line-height:1.6;color:#6b7280}.alert{border-radius:12px;padding:14px 16px;margin-bottom:18px;font-size:14px}.alert-success{background:#e9f7ef;color:#146c43;border:1px solid #cfe7d8}.alert-error{background:#f8d7da;color:#842029;border:1px solid #f1b0b7}.summary-grid,.form-grid,.form-grid-compact,.meta-grid{display:grid;gap:14px}.summary-grid,.form-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.form-grid-compact{grid-template-columns:1.3fr 1fr 1fr 1fr;align-items:end}.summary-box,.meta-box{background:#f9fafb;border:1px solid #e5e7eb;border-radius:14px;padding:16px}.summary-label,.meta-label{font-size:12px;color:#6b7280;margin-bottom:6px;text-transform:uppercase}.summary-value{font-size:22px;font-weight:bold}.form-group{margin:0}label{display:block;margin-bottom:8px;font-weight:bold;color:#374151}input[type=text],select{width:100%;height:44px;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;font-size:14px;outline:none;background:#fff}.input-prefix{display:flex;align-items:center;border:1px solid #d1d5db;border-radius:10px;overflow:hidden;background:#fff}.input-prefix span{padding:0 12px;color:#6b7280;background:#f9fafb;border-right:1px solid #e5e7eb;height:44px;display:inline-flex;align-items:center}.input-prefix input{border:0;border-radius:0}.checkbox-row{display:flex;align-items:center;gap:10px;min-height:44px}.checkbox-row input{margin:0}.helper{font-size:13px;color:#6b7280;margin-top:8px}.empty-state{padding:18px;border-radius:12px;background:#f9fafb;border:1px dashed #d1d5db;color:#6b7280}.menu-list{display:grid;gap:14px}.menu-item{border:1px solid #e5e7eb;border-radius:14px;padding:18px;background:#fcfcfd}.item-header{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px}.item-title{margin:0;font-size:18px}.status-pill{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:bold}.status-visible{background:#e9f7ef;color:#146c43}.status-hidden{background:#f8d7da;color:#842029}.actions-row{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}@media (max-width:1100px){.summary-grid,.form-grid,.form-grid-compact,.meta-grid{grid-template-columns:1fr}}@media (max-width:991px){.layout{flex-direction:column}.sidebar{width:100%;border-right:0;border-bottom:1px solid #e5e7eb;padding:18px;position:static;top:auto;height:auto;overflow-y:visible}.main{padding:22px}.topbar{flex-direction:column;align-items:stretch}.topbar-actions{justify-content:flex-start}}@media (max-width:640px){.sidebar{padding:16px}.main{padding:16px}.card,.menu-item{padding:18px;border-radius:14px}.page-title{font-size:26px}}
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="brand"><h2>Panel Admin</h2><p>Gestiona el contenido principal del sitio desde un solo lugar.</p></div>
            <div><p class="sidebar-section-title">Principal</p><nav class="nav"><a href="../dashboard.php"><span class="nav-icon">&#127968;</span><span>Panel de inicio</span></a><a href="index.php" class="active"><span class="nav-icon">&#128203;</span><span>Men&uacute; de navegaci&oacute;n</span></a></nav></div>
            <div><p class="sidebar-section-title">Contenido</p><nav class="nav"><a href="../pages/index.php"><span class="nav-icon">&#128196;</span><span>P&aacute;ginas del sitio</span></a></nav></div>
            <div><p class="sidebar-section-title">Configuraci&oacute;n</p><nav class="nav"><a href="../settings.php"><span class="nav-icon">&#9881;</span><span>Configuraci&oacute;n</span></a><a href="../change-password.php"><span class="nav-icon">&#128274;</span><span>Cambiar contrase&ntilde;a</span></a></nav></div>
        </aside>
        <main class="main">
            <div class="topbar">
                <div><h1 class="page-title">Men&uacute; de navegaci&oacute;n</h1><p class="page-subtitle">Define las opciones visibles del men&uacute; superior de tu sitio web.</p></div>
                <div class="topbar-actions"><a href="../dashboard.php" class="btn btn-outline">Volver al panel</a><form action="../logout.php" method="post" style="margin:0;"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>"><button type="submit" class="btn btn-logout">Cerrar sesi&oacute;n</button></form></div>
            </div>

            <?php if ($status === "created" || $status === "updated" || $status === "toggled" || $status === "deleted"): ?><div class="alert alert-success">Los cambios se guardaron correctamente.</div><?php endif; ?>
            <?php if ($status === "invalid" || $status === "error"): ?><div class="alert alert-error">No se pudo guardar la informaci&oacute;n. Revisa los datos e intenta nuevamente.</div><?php endif; ?>
            <?php if ($status === "protected"): ?><div class="alert alert-error">La opci&oacute;n Inicio es fija del sistema y no se puede editar, ocultar ni eliminar.</div><?php endif; ?>
            <?php if ($status === "limit_options"): ?><div class="alert alert-error">Ya tienes configuradas las 8 opciones permitidas para el men&uacute;.</div><?php endif; ?>
            <?php if ($status === "limit_button"): ?><div class="alert alert-error">Solo puedes tener un bot&oacute;n principal configurado.</div><?php endif; ?>
            <?php if ($status === "migration_required"): ?><div class="alert alert-error">Falta ejecutar la actualizaci&oacute;n de base de datos del men&uacute; para habilitar p&aacute;ginas internas.</div><?php endif; ?>
            <?php if (!$supportsMenuLinkTypes): ?><div class="alert alert-error">El formulario ya est&aacute; preparado para p&aacute;ginas internas, pero primero debes ejecutar manualmente el SQL de actualizaci&oacute;n en <span class="muted">menu_items</span>.</div><?php endif; ?>

            <section class="card">
                <h2>Resumen</h2>
                <div class="summary-grid">
                    <div class="summary-box"><div class="summary-label">Opciones del men&uacute;</div><div class="summary-value"><?php echo count($menuOptions) + ($homeItem !== null ? 1 : 0); ?> de 8</div></div>
                    <div class="summary-box"><div class="summary-label">Bot&oacute;n principal</div><div class="summary-value"><?php echo $buttonConfigured ? "Configurado" : "Pendiente"; ?></div></div>
                </div>
            </section>

            <section class="card">
                <h2>Agregar opci&oacute;n del men&uacute;</h2>
                <p>Puedes agregar hasta 8 opciones al men&uacute;.</p>
                <form action="index.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                    <input type="hidden" name="action" value="create_option">
                    <div class="form-grid" data-menu-form="create-option">
                        <div class="form-group"><label for="new_link_type">Tipo de opci&oacute;n</label><select id="new_link_type" name="link_type" data-menu-link-type><option value="custom">Enlace personalizado</option><option value="internal" <?php echo !$supportsMenuLinkTypes || $sitePages === [] ? "disabled" : ""; ?>>P&aacute;gina interna</option></select><div class="helper"><?php echo $supportsMenuLinkTypes ? "Elige una p&aacute;gina real del sitio o escribe un enlace manual." : "Primero ejecuta el SQL pendiente para habilitar p&aacute;ginas internas."; ?></div></div>
                        <div class="form-group" data-menu-page-group style="display:none;"><label for="new_site_page_id">P&aacute;gina del sitio</label><select id="new_site_page_id" name="site_page_id" data-menu-page-select><option value="">Selecciona una p&aacute;gina</option><?php foreach ($sitePages as $page): ?><option value="<?php echo (int) $page["id"]; ?>" data-page-title="<?php echo htmlspecialchars((string) $page["title"], ENT_QUOTES, "UTF-8"); ?>" data-page-url="<?php echo htmlspecialchars((string) $page["public_url"], ENT_QUOTES, "UTF-8"); ?>"><?php echo htmlspecialchars((string) $page["title"], ENT_QUOTES, "UTF-8"); ?><?php echo (int) $page["is_active"] === 1 ? "" : " (inactiva)"; ?></option><?php endforeach; ?></select><div class="helper"><?php echo $sitePages === [] ? "No hay p&aacute;ginas activas disponibles para vincular." : "Selecciona una p&aacute;gina real del sitio."; ?></div></div>
                        <div class="form-group"><label for="new_name">Nombre del item del men&uacute;</label><input type="text" id="new_name" name="name" maxlength="255" required data-menu-name><div class="helper">Puedes ajustar el texto aunque el item apunte a una p&aacute;gina interna.</div></div>
                        <div class="form-group"><label for="new_path">Direcci&oacute;n de la p&aacute;gina</label><input type="text" id="new_path" name="path" maxlength="255" required data-menu-url><div class="helper" data-menu-url-help>Escribe la direcci&oacute;n manualmente si se trata de un enlace personalizado.</div></div>
                        <div class="form-group"><label for="new_target">Abrir enlace</label><select id="new_target" name="target"><option value="_self">En la misma pesta&ntilde;a</option><option value="_blank">En una pesta&ntilde;a nueva</option></select></div>
                        <div class="form-group"><label for="new_order">Posici&oacute;n en el men&uacute;</label><select id="new_order" name="display_order"><?php for ($i = 2; $i <= 8; $i++): ?><option value="<?php echo $i; ?>"><?php echo $i; ?></option><?php endfor; ?></select><div class="helper">La posici&oacute;n 1 est&aacute; reservada para Inicio.</div></div>
                        <div class="form-group"><label>Mostrar en el men&uacute;</label><div class="checkbox-row"><input type="checkbox" id="new_active" name="is_active" value="1" checked><label for="new_active" style="margin:0;font-weight:normal;">S&iacute;, mostrar</label></div></div>
                    </div>
                    <div class="actions-row"><button type="submit" class="btn btn-primary">Agregar opci&oacute;n</button></div>
                </form>
            </section>

            <section class="card">
                <h2>Configurar bot&oacute;n principal</h2>
                <p>Este bot&oacute;n sirve para resaltar una acci&oacute;n importante.</p>
                <form action="index.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                    <input type="hidden" name="action" value="save_button">
                    <input type="hidden" name="id" value="<?php echo $primaryButton !== null ? (int) $primaryButton["id"] : ""; ?>">
                    <div class="form-grid">
                        <div class="form-group"><label for="button_text">Texto del bot&oacute;n</label><input type="text" id="button_text" name="button_text" maxlength="255" required value="<?php echo htmlspecialchars($primaryButton["label"] ?? "", ENT_QUOTES, "UTF-8"); ?>"></div>
                        <div class="form-group"><label for="button_path">Direcci&oacute;n del bot&oacute;n</label><input type="text" id="button_path" name="button_path" maxlength="255" required value="<?php echo htmlspecialchars($primaryButton["url"] ?? "", ENT_QUOTES, "UTF-8"); ?>"><div class="helper">Aqu&iacute; puedes escribir un enlace completo real, por ejemplo: https://midominio.com/agendar o https://wa.me/593...</div></div>
                        <div class="form-group"><label for="button_target">Abrir enlace</label><select id="button_target" name="target"><option value="_self" <?php echo ($primaryButton["target"] ?? "_self") === "_self" ? "selected" : ""; ?>>En la misma pesta&ntilde;a</option><option value="_blank" <?php echo ($primaryButton["target"] ?? "_self") === "_blank" ? "selected" : ""; ?>>En una pesta&ntilde;a nueva</option></select></div>
                        <div class="form-group"><label>Mostrar bot&oacute;n principal</label><div class="checkbox-row"><input type="checkbox" id="button_active" name="is_active" value="1" <?php echo (($primaryButton["is_active"] ?? 1) == 1) ? "checked" : ""; ?>><label for="button_active" style="margin:0;font-weight:normal;">S&iacute;, mostrar</label></div></div>
                    </div>
                    <div class="actions-row"><button type="submit" class="btn btn-primary"><?php echo $primaryButton !== null ? "Guardar bot&oacute;n principal" : "Crear bot&oacute;n principal"; ?></button></div>
                </form>
            </section>

            <section class="card">
                <h2 class="section-title">Vista actual del men&uacute;</h2>
                <h3 class="section-title">Opciones del men&uacute;</h3>

                <?php if ($homeItem !== null): ?>
                    <article class="menu-item" style="margin-bottom:14px;">
                        <div class="item-header">
                            <h4 class="item-title"><?php echo htmlspecialchars($homeItem["label"], ENT_QUOTES, "UTF-8"); ?></h4>
                            <span class="status-pill status-visible">Fija</span>
                        </div>
                        <div class="meta-grid">
                            <div class="meta-box">
                                <div class="meta-label">Direcci&oacute;n</div>
                                <div class="meta-value"><?php echo htmlspecialchars($homeItem["url"], ENT_QUOTES, "UTF-8"); ?></div>
                            </div>
                            <div class="meta-box">
                                <div class="meta-label">Posici&oacute;n</div>
                                <div class="meta-value">1</div>
                            </div>
                            <div class="meta-box">
                                <div class="meta-label">Apertura</div>
                                <div class="meta-value">En la misma pesta&ntilde;a</div>
                            </div>
                        </div>
                        <div class="helper" style="margin-top:12px;">Esta opci&oacute;n es fija del sistema y siempre aparece primero.</div>
                    </article>
                <?php endif; ?>
                <?php if (count($menuOptions) === 0): ?>
                    <div class="empty-state">A&uacute;n no hay opciones configuradas para el men&uacute; superior.</div>
                <?php else: ?>
                    <div class="menu-list">
                        <?php foreach ($menuOptions as $item): ?>
                            <article class="menu-item">
                                <div class="item-header"><h4 class="item-title"><?php echo htmlspecialchars($item["label"], ENT_QUOTES, "UTF-8"); ?></h4><span class="status-pill <?php echo (int) $item["is_active"] === 1 ? "status-visible" : "status-hidden"; ?>"><?php echo (int) $item["is_active"] === 1 ? "Visible" : "Oculta"; ?></span></div>
                                <div class="meta-grid">
                                    <div class="meta-box"><div class="meta-label">Direcci&oacute;n</div><div class="meta-value"><?php echo htmlspecialchars($item["url"], ENT_QUOTES, "UTF-8"); ?></div></div>
                                    <div class="meta-box"><div class="meta-label">Tipo</div><div class="meta-value"><?php echo ($item["link_type"] ?? "custom") === "internal" ? "P&aacute;gina interna" : "Enlace personalizado"; ?></div></div>
                                    <div class="meta-box"><div class="meta-label">Posici&oacute;n</div><div class="meta-value"><?php echo (int) $item["display_order"]; ?></div></div>
                                    <div class="meta-box"><div class="meta-label">Apertura</div><div class="meta-value"><?php echo ($item["target"] ?? "_self") === "_blank" ? "En una pesta&ntilde;a nueva" : "En la misma pesta&ntilde;a"; ?></div></div>
                                </div>
                                <form action="index.php" method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                                    <input type="hidden" name="action" value="update_option">
                                    <input type="hidden" name="id" value="<?php echo (int) $item["id"]; ?>">
                                    <div class="form-grid" data-menu-form="option-<?php echo (int) $item["id"]; ?>">
                                        <div class="form-group"><label for="link_type_<?php echo (int) $item["id"]; ?>">Tipo de opci&oacute;n</label><select id="link_type_<?php echo (int) $item["id"]; ?>" name="link_type" data-menu-link-type><option value="custom" <?php echo ($item["link_type"] ?? "custom") === "custom" ? "selected" : ""; ?>>Enlace personalizado</option><option value="internal" <?php echo ($item["link_type"] ?? "custom") === "internal" ? "selected" : ""; ?> <?php echo !$supportsMenuLinkTypes || $sitePages === [] ? "disabled" : ""; ?>>P&aacute;gina interna</option></select></div>
                                        <div class="form-group" data-menu-page-group style="<?php echo ($item["link_type"] ?? "custom") === "internal" ? "" : "display:none;"; ?>"><label for="site_page_id_<?php echo (int) $item["id"]; ?>">P&aacute;gina del sitio</label><select id="site_page_id_<?php echo (int) $item["id"]; ?>" name="site_page_id" data-menu-page-select><option value="">Selecciona una p&aacute;gina</option><?php foreach ($sitePages as $page): ?><option value="<?php echo (int) $page["id"]; ?>" data-page-title="<?php echo htmlspecialchars((string) $page["title"], ENT_QUOTES, "UTF-8"); ?>" data-page-url="<?php echo htmlspecialchars((string) $page["public_url"], ENT_QUOTES, "UTF-8"); ?>" <?php echo (int) ($item["site_page_id"] ?? 0) === (int) $page["id"] ? "selected" : ""; ?>><?php echo htmlspecialchars((string) $page["title"], ENT_QUOTES, "UTF-8"); ?><?php echo (int) $page["is_active"] === 1 ? "" : " (inactiva)"; ?></option><?php endforeach; ?></select></div>
                                        <div class="form-group"><label for="name_<?php echo (int) $item["id"]; ?>">Nombre visible</label><input type="text" id="name_<?php echo (int) $item["id"]; ?>" name="name" maxlength="255" required value="<?php echo htmlspecialchars($item["label"], ENT_QUOTES, "UTF-8"); ?>" data-menu-name></div>
                                        <div class="form-group"><label for="path_<?php echo (int) $item["id"]; ?>">Direcci&oacute;n</label><input type="text" id="path_<?php echo (int) $item["id"]; ?>" name="path" maxlength="255" required value="<?php echo htmlspecialchars($item["url"], ENT_QUOTES, "UTF-8"); ?>" data-menu-url <?php echo ($item["link_type"] ?? "custom") === "internal" ? "readonly" : ""; ?>><div class="helper" data-menu-url-help><?php echo ($item["link_type"] ?? "custom") === "internal" ? "Se completa autom&aacute;ticamente seg&uacute;n la p&aacute;gina seleccionada." : "Puedes escribir una URL, un anchor o un enlace externo."; ?></div></div>
                                        <div class="form-group"><label for="order_<?php echo (int) $item["id"]; ?>">Posici&oacute;n</label><select id="order_<?php echo (int) $item["id"]; ?>" name="display_order"><?php for ($i = 2; $i <= 8; $i++): ?><option value="<?php echo $i; ?>" <?php echo (int) $item["display_order"] === $i ? "selected" : ""; ?>><?php echo $i; ?></option><?php endfor; ?></select></div>
                                        <div class="form-group"><button type="submit" class="btn btn-primary">Editar</button></div>
                                    </div>
                                </form>
                                <div class="actions-row"><form action="index.php" method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?php echo (int) $item["id"]; ?>"><input type="hidden" name="next_state" value="<?php echo (int) $item["is_active"] === 1 ? 0 : 1; ?>"><button type="submit" class="btn btn-soft"><?php echo (int) $item["is_active"] === 1 ? "Ocultar" : "Mostrar"; ?></button></form><form action="index.php" method="post" onsubmit="return confirm('¿Seguro que deseas eliminar esta opción del menú?');"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $item["id"]; ?>"><button type="submit" class="btn btn-soft">Eliminar</button></form></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <h3 class="section-title" style="margin-top:20px;">Bot&oacute;n principal</h3>
                <?php if ($primaryButton === null): ?>
                    <div class="empty-state">Todav&iacute;a no has configurado el bot&oacute;n principal.</div>
                <?php else: ?>
                    <article class="menu-item">
                        <div class="item-header"><h4 class="item-title"><?php echo htmlspecialchars($primaryButton["label"], ENT_QUOTES, "UTF-8"); ?></h4><span class="status-pill <?php echo (int) $primaryButton["is_active"] === 1 ? "status-visible" : "status-hidden"; ?>"><?php echo (int) $primaryButton["is_active"] === 1 ? "Visible" : "Oculto"; ?></span></div>
                        <div class="meta-grid">
                            <div class="meta-box"><div class="meta-label">Direcci&oacute;n</div><div class="meta-value"><?php echo htmlspecialchars($primaryButton["url"], ENT_QUOTES, "UTF-8"); ?></div></div>
                            <div class="meta-box"><div class="meta-label">Posici&oacute;n</div><div class="meta-value">Bot&oacute;n principal</div></div>
                            <div class="meta-box"><div class="meta-label">Apertura</div><div class="meta-value"><?php echo ($primaryButton["target"] ?? "_self") === "_blank" ? "En una pesta&ntilde;a nueva" : "En la misma pesta&ntilde;a"; ?></div></div>
                        </div>
                        <div class="actions-row"><form action="index.php" method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?php echo (int) $primaryButton["id"]; ?>"><input type="hidden" name="next_state" value="<?php echo (int) $primaryButton["is_active"] === 1 ? 0 : 1; ?>"><button type="submit" class="btn btn-soft"><?php echo (int) $primaryButton["is_active"] === 1 ? "Ocultar" : "Mostrar"; ?></button></form><form action="index.php" method="post" onsubmit="return confirm('¿Seguro que deseas eliminar este botón principal?');"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $primaryButton["id"]; ?>"><button type="submit" class="btn btn-soft">Eliminar</button></form></div>
                    </article>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script>
        (function () {
            function normalizeSlug(value) {
                if (!value) {
                    return "";
                }
                var normalized = value.toLowerCase().trim();
                normalized = normalized.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                normalized = normalized.replace(/^https?:\/\/[^/]+\/?/i, "");
                normalized = normalized.replace(/\.php$/i, "");
                normalized = normalized.replace(/[^a-z0-9\/\-\s]/g, "");
                normalized = normalized.replace(/\s+/g, "-");
                normalized = normalized.replace(/-+/g, "-");
                normalized = normalized.replace(/\/+/g, "/");
                normalized = normalized.replace(/^\/+|\/+$/g, "");
                return normalized.replace(/^-+|-+$/g, "");
            }

            function bindMenuForm(form) {
                var linkTypeSelect = form.querySelector("[data-menu-link-type]");
                var pageSelect = form.querySelector("[data-menu-page-select]");
                var pageGroup = form.querySelector("[data-menu-page-group]");
                var nameInput = form.querySelector("[data-menu-name]");
                var urlInput = form.querySelector("[data-menu-url]");
                var urlHelp = form.querySelector("[data-menu-url-help]");

                if (!linkTypeSelect || !nameInput || !urlInput) {
                    return;
                }

                var nameTouched = false;

                function currentLinkType() {
                    return linkTypeSelect.value === "internal" ? "internal" : "custom";
                }

                function selectedPageOption() {
                    return pageSelect ? pageSelect.options[pageSelect.selectedIndex] : null;
                }

                function syncFromSelectedPage() {
                    if (currentLinkType() !== "internal" || !pageSelect) {
                        return;
                    }

                    var option = selectedPageOption();
                    if (!option || option.value === "") {
                        urlInput.value = "";
                        return;
                    }

                    if (!nameTouched || nameInput.value.trim() === "") {
                        nameInput.value = option.getAttribute("data-page-title") || "";
                    }

                    urlInput.value = option.getAttribute("data-page-url") || "";
                }

                function syncFormState() {
                    var internalMode = currentLinkType() === "internal";

                    if (pageGroup) {
                        pageGroup.style.display = internalMode ? "" : "none";
                    }

                    if (pageSelect) {
                        pageSelect.disabled = !internalMode;
                    }

                    urlInput.readOnly = internalMode;

                    if (urlHelp) {
                        urlHelp.textContent = internalMode
                            ? "Se completa automaticamente segun la pagina seleccionada."
                            : "Puedes escribir una URL, un anchor o un enlace externo.";
                    }

                    if (internalMode) {
                        syncFromSelectedPage();
                    }
                }

                nameInput.addEventListener("input", function () {
                    nameTouched = nameInput.value.trim() !== "";
                });

                urlInput.addEventListener("input", function () {
                    if (!urlInput.readOnly) {
                        urlInput.value = urlInput.value.trim();
                    }
                });

                if (pageSelect) {
                    pageSelect.addEventListener("change", function () {
                        syncFromSelectedPage();
                    });
                }

                linkTypeSelect.addEventListener("change", function () {
                    syncFormState();
                });

                syncFormState();
            }

            document.querySelectorAll("[data-menu-form]").forEach(function (form) {
                bindMenuForm(form);
            });
        }());
    </script>
</body>
</html>
