<?php
require_once "../auth-check.php";
require_once "../../db.php";
require_once "menu-helpers.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

ensureHomeMenuItem($conn);

$menuLinkSchema = getMenuItemsLinkSupport($conn);
$supportsMenuLinkTypes = $menuLinkSchema["link_type"] && $menuLinkSchema["site_page_id"];

$itemId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$isCreateMode = $itemId <= 0 || ((string) ($_GET["action"] ?? "")) === "create";
$status = (string) ($_GET["status"] ?? "");
$errors = [];
$successMessage = "";
$isHomeItem = false;

$menuData = [
    "name" => "",
    "path" => "",
    "display_order" => 2,
    "target" => "_self",
    "is_active" => 1,
    "link_type" => "internal",
    "site_page_id" => 0,
    "parent_id" => null,
];

if ($status === "created") {
    $successMessage = "La opcion del menu se creo correctamente.";
} elseif ($status === "updated") {
    $successMessage = "La opcion del menu se actualizo correctamente.";
}

$existingItem = null;

if (!$isCreateMode) {
    $existingItem = getMenuItemById($conn, $itemId);

    if (!$existingItem || (int) ($existingItem["is_button"] ?? 0) !== 0) {
        $errors[] = "La opcion del menu solicitada no existe.";
    } else {
        $isHomeItem = isHomeMenuItem($existingItem);
        $menuData = [
            "name" => $isHomeItem ? "Inicio" : (string) ($existingItem["label"] ?? ""),
            "path" => (string) ($existingItem["url"] ?? ""),
            "display_order" => $isHomeItem ? 1 : (int) ($existingItem["display_order"] ?? 2),
            "target" => menuTarget((string) ($existingItem["target"] ?? "_self")),
            "is_active" => $isHomeItem ? 1 : (int) ($existingItem["is_active"] ?? 1),
            "link_type" => menuLinkType((string) ($existingItem["link_type"] ?? "custom"), $supportsMenuLinkTypes),
            "site_page_id" => (int) ($existingItem["site_page_id"] ?? 0),
            "parent_id" => isset($existingItem["parent_id"]) ? (int) $existingItem["parent_id"] : null,
        ];
    }
}

$linkedSitePageIds = [];
if (!$isCreateMode && (int) $menuData["site_page_id"] > 0) {
    $linkedSitePageIds[] = (int) $menuData["site_page_id"];
}

$sitePages = [];
$sitePagesById = [];
if ($supportsMenuLinkTypes) {
    [$sitePages, $sitePagesById] = getMenuSitePages($conn, $linkedSitePageIds, $isHomeItem);
}

if ($isCreateMode && $_SERVER["REQUEST_METHOD"] !== "POST" && ($sitePages === [] || !$supportsMenuLinkTypes)) {
    $menuData["link_type"] = "custom";
}

$parentOptions = getMenuParentOptions($conn, $isCreateMode ? 0 : $itemId);
$currentMainPosition = $isCreateMode ? null : (int) $menuData["display_order"];
$availableMainPositions = getAvailableMainMenuPositions($conn, $isCreateMode ? 0 : $itemId, $currentMainPosition);

if ($isCreateMode && $menuData["parent_id"] === null && $availableMainPositions !== [] && !in_array((int) $menuData["display_order"], $availableMainPositions, true)) {
    $menuData["display_order"] = (int) $availableMainPositions[0];
}

if ($menuData["parent_id"] !== null) {
    $rootDisplayOrder = getMenuRootDisplayOrder($conn, (int) $menuData["parent_id"]);
    if ($rootDisplayOrder !== null) {
        $menuData["display_order"] = $rootDisplayOrder;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = (string) ($_POST["csrf_token"] ?? "");

    if (!hash_equals($_SESSION["csrf_token"], $csrfToken)) {
        $errors[] = "No se pudo validar la solicitud. Intenta de nuevo.";
    } else {
        $submittedId = isset($_POST["item_id"]) ? (int) $_POST["item_id"] : 0;
        $isCreateSubmission = ((string) ($_POST["form_mode"] ?? "")) === "create";

        $isCreateMode = $isCreateSubmission;
        $itemId = $isCreateSubmission ? 0 : $submittedId;
        $existingItem = $isCreateSubmission ? null : getMenuItemById($conn, $submittedId);

        if (!$isCreateSubmission) {
            if (!$existingItem || (int) ($existingItem["is_button"] ?? 0) !== 0) {
                $errors[] = "La opcion del menu que intentas editar ya no existe.";
            }
        }

        $isHomeItem = !$isCreateSubmission && isHomeMenuItem($existingItem);

        $menuData = [
            "name" => textValue((string) ($_POST["name"] ?? "")),
            "path" => textValue((string) ($_POST["path"] ?? "")),
            "display_order" => (int) ($_POST["display_order"] ?? 0),
            "target" => menuTarget((string) ($_POST["target"] ?? "_self")),
            "is_active" => isset($_POST["is_active"]) ? 1 : 0,
            "link_type" => menuLinkType((string) ($_POST["link_type"] ?? "custom"), $supportsMenuLinkTypes),
            "site_page_id" => (int) ($_POST["site_page_id"] ?? 0),
            "parent_id" => isset($_POST["parent_id"]) ? (int) $_POST["parent_id"] : 0,
        ];

        $menuData["parent_id"] = (int) $menuData["parent_id"] > 0 ? (int) $menuData["parent_id"] : null;

        if ($isHomeItem) {
            $menuData["name"] = "Inicio";
            $menuData["display_order"] = 1;
            $menuData["is_active"] = 1;
            $menuData["parent_id"] = null;
        }

        if ($isCreateMode && countByType($conn, 0) >= 8) {
            $errors[] = "Ya tienes configuradas las 8 opciones permitidas para el menu.";
        }

        if ($menuData["link_type"] === "internal" && !$supportsMenuLinkTypes) {
            $errors[] = "Falta ejecutar la actualizacion de base de datos del menu para habilitar paginas internas.";
        }

        $linkedPage = null;

        if ($menuData["link_type"] === "internal") {
            if ($menuData["site_page_id"] <= 0) {
                $errors[] = "Debes seleccionar una pagina del sitio.";
            } else {
                $linkedPage = getSitePageById($conn, $menuData["site_page_id"]);
            }

            if (!$linkedPage || (!$isHomeItem && ($linkedPage["page_key"] ?? "") === "home")) {
                $errors[] = "La pagina seleccionada no es valida para el menu.";
            } else {
                if ($menuData["name"] === "") {
                    $menuData["name"] = textValue((string) ($linkedPage["title"] ?? ""));
                }

                $menuData["path"] = publicPageUrl((string) ($linkedPage["slug"] ?? ""), (string) ($linkedPage["page_key"] ?? ""));
            }
        } else {
            $menuData["site_page_id"] = 0;
            $menuData["path"] = menuCustomUrl($menuData["path"]);
        }

        if ($menuData["name"] === "") {
            $errors[] = "El nombre visible es obligatorio.";
        }

        if ($menuData["path"] === "") {
            $errors[] = "La direccion es obligatoria.";
        }

        if ($isHomeItem) {
            $menuData["display_order"] = 1;
            $menuData["is_active"] = 1;
            $menuData["parent_id"] = null;
        }

        $parentValidationError = null;
        if (!validateMenuParent($conn, $isCreateMode ? 0 : $itemId, $menuData["parent_id"], $parentValidationError)) {
            $errors[] = $parentValidationError ?? "La opcion superior seleccionada no es valida.";
        }

        if (!$isHomeItem && $menuData["parent_id"] !== null) {
            $rootDisplayOrder = getMenuRootDisplayOrder($conn, (int) $menuData["parent_id"]);
            if ($rootDisplayOrder === null || $rootDisplayOrder < 2 || $rootDisplayOrder > 8) {
                $errors[] = "No fue posible calcular la posicion heredada de la opcion superior.";
            } else {
                $menuData["display_order"] = $rootDisplayOrder;
            }
        } elseif (!$isHomeItem && ($menuData["display_order"] < 2 || $menuData["display_order"] > 8)) {
            $errors[] = "La posicion seleccionada no es valida.";
        } elseif (!$isHomeItem && !isMainMenuPositionAvailable($conn, $menuData["display_order"], $isCreateMode ? 0 : $itemId)) {
            $errors[] = "La posici&oacute;n seleccionada ya est&aacute; ocupada por otra opci&oacute;n principal.";
        }

        if ($errors === []) {
            if ($supportsMenuLinkTypes) {
                $sitePageId = $menuData["site_page_id"] > 0 ? $menuData["site_page_id"] : null;
                $parentId = $menuData["parent_id"] !== null ? (int) $menuData["parent_id"] : null;

                if ($isCreateMode) {
                    $stmt = $conn->prepare("INSERT INTO menu_items (parent_id, label, link_type, site_page_id, url, display_order, is_active, is_button, target, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, NOW(), NOW())");

                    if (!$stmt) {
                        $errors[] = "No fue posible guardar la opcion del menu.";
                    } else {
                        $stmt->bind_param("issisiis", $parentId, $menuData["name"], $menuData["link_type"], $sitePageId, $menuData["path"], $menuData["display_order"], $menuData["is_active"], $menuData["target"]);
                    }
                } else {
                    $stmt = $conn->prepare("UPDATE menu_items SET parent_id = ?, label = ?, link_type = ?, site_page_id = ?, url = ?, display_order = ?, is_active = ?, target = ?, updated_at = NOW() WHERE id = ? AND is_button = 0 LIMIT 1");

                    if (!$stmt) {
                        $errors[] = "No fue posible actualizar la opcion del menu.";
                    } else {
                        $stmt->bind_param("issisiisi", $parentId, $menuData["name"], $menuData["link_type"], $sitePageId, $menuData["path"], $menuData["display_order"], $menuData["is_active"], $menuData["target"], $itemId);
                    }
                }
            } else {
                $parentId = $menuData["parent_id"] !== null ? (int) $menuData["parent_id"] : null;

                if ($isCreateMode) {
                    $stmt = $conn->prepare("INSERT INTO menu_items (parent_id, label, url, display_order, is_active, is_button, target, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 0, ?, NOW(), NOW())");

                    if (!$stmt) {
                        $errors[] = "No fue posible guardar la opcion del menu.";
                    } else {
                        $stmt->bind_param("issiis", $parentId, $menuData["name"], $menuData["path"], $menuData["display_order"], $menuData["is_active"], $menuData["target"]);
                    }
                } else {
                    $stmt = $conn->prepare("UPDATE menu_items SET parent_id = ?, label = ?, url = ?, display_order = ?, is_active = ?, target = ?, updated_at = NOW() WHERE id = ? AND is_button = 0 LIMIT 1");

                    if (!$stmt) {
                        $errors[] = "No fue posible actualizar la opcion del menu.";
                    } else {
                        $stmt->bind_param("issiisi", $parentId, $menuData["name"], $menuData["path"], $menuData["display_order"], $menuData["is_active"], $menuData["target"], $itemId);
                    }
                }
            }

            if ($errors === [] && isset($stmt)) {
                $ok = $stmt->execute();
                $newItemId = $isCreateMode ? (int) $stmt->insert_id : $itemId;
                $stmt->close();

                if ($ok) {
                    redirectToMenuEdit($isCreateMode ? "created" : "updated", $newItemId);
                }

                $errors[] = "No fue posible guardar la opcion del menu.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isCreateMode ? "Crear nueva opcion del menu" : "Editar opcion del menu"; ?></title>
    <style>
        *{box-sizing:border-box}body{font-family:Arial,sans-serif;background:#f4f6f9;margin:0;color:#1f2937}.layout{min-height:100vh;display:flex;align-items:flex-start}.sidebar{width:260px;background:#fff;border-right:1px solid #e5e7eb;padding:24px 18px;display:flex;flex-direction:column;gap:22px;position:sticky;top:0;height:100vh;overflow-y:auto;flex-shrink:0}.brand{padding-bottom:18px;border-bottom:1px solid #e5e7eb}.brand h2{margin:0;font-size:22px;color:#198754}.brand p{margin:8px 0 0;color:#6b7280;font-size:14px;line-height:1.4}.sidebar-section-title{margin:0 0 10px;font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af}.nav{display:flex;flex-direction:column;gap:8px}.nav a{display:flex;align-items:center;gap:10px;text-decoration:none;color:#374151;padding:11px 12px;border-radius:10px;transition:background .2s ease,color .2s ease}.nav a:hover{background:#eef8f2;color:#198754}.nav a.active{background:#e9f7ef;color:#198754;font-weight:bold}.nav-icon{width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center}.main{flex:1;padding:32px;min-width:0}.topbar{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:24px}.page-title{margin:0;font-size:34px;line-height:1.1}.page-subtitle{margin:10px 0 0;font-size:16px;color:#6b7280;max-width:760px}.topbar-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.btn{display:inline-block;padding:10px 16px;border-radius:10px;text-decoration:none;border:0;cursor:pointer;font-size:14px;transition:background .2s ease,transform .2s ease}.btn:hover{transform:translateY(-1px)}.btn-outline{background:#fff;color:#198754;border:1px solid #cfe7d8}.btn-outline:hover{background:#eef8f2}.btn-logout{background:#dc3545;color:#fff}.btn-logout:hover{background:#bb2d3b}.btn-primary{background:#198754;color:#fff}.btn-primary:hover{background:#157347}.card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:24px;box-shadow:0 8px 24px rgba(0,0,0,.04);margin-bottom:18px}.card h2{margin:0 0 10px;font-size:22px}.card p{margin:0 0 18px;line-height:1.6;color:#6b7280}.alert{border-radius:12px;padding:14px 16px;margin-bottom:18px;font-size:14px}.alert-success{background:#e9f7ef;color:#146c43;border:1px solid #cfe7d8}.alert-error{background:#f8d7da;color:#842029;border:1px solid #f1b0b7}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.field-full{grid-column:1 / -1}.form-group{margin:0}label{display:block;margin-bottom:8px;font-weight:bold;color:#374151}input[type=text],select{width:100%;height:44px;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;font-size:14px;outline:none;background:#fff}.checkbox-row{display:flex;align-items:center;gap:10px;min-height:44px}.checkbox-row input{margin:0}.helper{font-size:13px;color:#6b7280;margin-top:8px}.readonly-box{display:flex;align-items:center;min-height:44px;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;background:#f9fafb;color:#374151}.actions-row{display:flex;gap:10px;flex-wrap:wrap;margin-top:20px}@media (max-width:991px){.layout{flex-direction:column}.sidebar{width:100%;border-right:0;border-bottom:1px solid #e5e7eb;padding:18px;position:static;top:auto;height:auto;overflow-y:visible}.main{padding:22px}.topbar{flex-direction:column;align-items:stretch}.topbar-actions{justify-content:flex-start}}@media (max-width:720px){.form-grid{grid-template-columns:1fr}.main{padding:16px}.sidebar{padding:16px}.card{padding:18px}.page-title{font-size:28px}}
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
                <div>
                    <h1 class="page-title"><?php echo $isCreateMode ? "Crear nueva opcion" : "Editar opcion del menu"; ?></h1>
                    <p class="page-subtitle"><?php echo $isHomeItem ? "La opcion Inicio es fija del sistema. Aqui solo puedes cambiar su destino, tipo de enlace y forma de apertura." : "Configura una opcion del menu de navegacion con un enlace personalizado o vinculada a una pagina real del sitio."; ?></p>
                </div>
                <div class="topbar-actions">
                    <a href="index.php" class="btn btn-outline">Volver al menu</a>
                    <a href="../dashboard.php" class="btn btn-outline">Ir al panel</a>
                    <form action="../logout.php" method="post" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                        <button type="submit" class="btn btn-logout">Cerrar sesi&oacute;n</button>
                    </form>
                </div>
            </div>

            <?php if ($successMessage !== ""): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, "UTF-8"); ?></div>
            <?php endif; ?>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></div>
            <?php endforeach; ?>

            <?php if (!$supportsMenuLinkTypes): ?>
                <div class="alert alert-error">Falta ejecutar la actualizacion de base de datos del menu para habilitar paginas internas. Mientras tanto, solo podras guardar enlaces personalizados.</div>
            <?php endif; ?>

            <section class="card">
                <h2><?php echo $isCreateMode ? "Datos de la opcion" : "Editar datos de la opcion"; ?></h2>
                <p><?php echo $isHomeItem ? "Inicio siempre permanece visible y en la primera posicion. Solo puedes actualizar a donde lleva el enlace." : "Completa solo lo necesario. Si eliges una pagina interna, la direccion se completara automaticamente."; ?></p>

                <form action="edit.php<?php echo !$isCreateMode ? "?id=" . urlencode((string) $itemId) : "?action=create"; ?>" method="post" data-menu-form="edit-option">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                    <input type="hidden" name="form_mode" value="<?php echo $isCreateMode ? "create" : "edit"; ?>">
                    <input type="hidden" name="item_id" value="<?php echo !$isCreateMode ? (int) $itemId : 0; ?>">

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="link_type">Tipo de opci&oacute;n</label>
                            <select id="link_type" name="link_type" data-menu-link-type>
                                <option value="internal" <?php echo $menuData["link_type"] === "internal" ? "selected" : ""; ?> <?php echo !$supportsMenuLinkTypes || $sitePages === [] ? "disabled" : ""; ?>>P&aacute;gina interna</option>
                                <option value="custom" <?php echo $menuData["link_type"] === "custom" ? "selected" : ""; ?>>Enlace personalizado</option>
                            </select>
                            <div class="helper"><?php echo $supportsMenuLinkTypes ? "Elige una pagina real del sitio o escribe un enlace manual." : "Las paginas internas se habilitaran cuando ejecutes el SQL pendiente."; ?></div>
                        </div>

                        <div class="form-group" data-menu-page-group style="<?php echo $menuData["link_type"] === "internal" ? "" : "display:none;"; ?>">
                            <label for="site_page_id">P&aacute;gina del sitio</label>
                            <select id="site_page_id" name="site_page_id" data-menu-page-select>
                                <option value="">Selecciona una p&aacute;gina</option>
                                <?php foreach ($sitePages as $page): ?>
                                    <option value="<?php echo (int) $page["id"]; ?>" data-page-title="<?php echo htmlspecialchars((string) $page["title"], ENT_QUOTES, "UTF-8"); ?>" data-page-url="<?php echo htmlspecialchars((string) $page["public_url"], ENT_QUOTES, "UTF-8"); ?>" <?php echo (int) $menuData["site_page_id"] === (int) $page["id"] ? "selected" : ""; ?>>
                                        <?php echo htmlspecialchars((string) $page["title"], ENT_QUOTES, "UTF-8"); ?><?php echo (int) $page["is_active"] === 1 ? "" : " (inactiva)"; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="helper"><?php echo $sitePages === [] ? "No hay paginas activas disponibles para vincular." : "Selecciona una pagina real del sitio."; ?></div>
                        </div>

                        <div class="form-group">
                            <label for="name">Nombre visible</label>
                            <?php if ($isHomeItem): ?>
                                <div class="readonly-box">Inicio</div>
                                <input type="hidden" id="name" name="name" value="Inicio" data-menu-name>
                                <div class="helper">Esta opcion fija del sistema siempre conserva el nombre Inicio.</div>
                            <?php else: ?>
                                <input type="text" id="name" name="name" maxlength="255" required value="<?php echo htmlspecialchars($menuData["name"], ENT_QUOTES, "UTF-8"); ?>" data-menu-name>
                                <div class="helper">Puedes ajustar el texto del menu aunque el item apunte a una pagina interna.</div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="path">Direcci&oacute;n</label>
                            <input type="text" id="path" name="path" maxlength="255" required value="<?php echo htmlspecialchars($menuData["path"], ENT_QUOTES, "UTF-8"); ?>" data-menu-url <?php echo $menuData["link_type"] === "internal" ? "readonly" : ""; ?>>
                            <div class="helper" data-menu-url-help><?php echo $menuData["link_type"] === "internal" ? "Se completa automaticamente segun la pagina seleccionada." : "Puedes escribir una URL, un anchor o un enlace externo."; ?></div>
                        </div>

                        <div class="form-group">
                            <label for="target">Abrir enlace</label>
                            <select id="target" name="target">
                                <option value="_self" <?php echo $menuData["target"] === "_self" ? "selected" : ""; ?>>En la misma pesta&ntilde;a</option>
                                <option value="_blank" <?php echo $menuData["target"] === "_blank" ? "selected" : ""; ?>>En una pesta&ntilde;a nueva</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="parent_id">Opci&oacute;n superior</label>
                            <?php if ($isHomeItem): ?>
                                <div class="readonly-box">Ninguna, ser&aacute; opci&oacute;n principal</div>
                                <input type="hidden" id="parent_id" name="parent_id" value="">
                                <div class="helper">Inicio no puede depender de otra opci&oacute;n.</div>
                            <?php else: ?>
                                <select id="parent_id" name="parent_id">
                                    <option value="">Ninguna, ser&aacute; opci&oacute;n principal</option>
                                    <?php foreach ($parentOptions as $parentOption): ?>
                                        <option value="<?php echo (int) $parentOption["id"]; ?>" data-root-order="<?php echo (int) ($parentOption["root_display_order"] ?? $parentOption["display_order"] ?? 0); ?>" <?php echo $menuData["parent_id"] !== null && (int) $menuData["parent_id"] === (int) $parentOption["id"] ? "selected" : ""; ?>>
                                            <?php echo str_repeat("&nbsp;&nbsp;", (int) ($parentOption["tree_depth"] ?? 0)); ?><?php echo (int) ($parentOption["tree_depth"] ?? 0) > 0 ? "&#8627; " : ""; ?><?php echo htmlspecialchars((string) ($parentOption["label"] ?? ""), ENT_QUOTES, "UTF-8"); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="helper">Puedes seleccionar una opci&oacute;n principal o de segundo nivel.</div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="display_order">Posici&oacute;n en el men&uacute;</label>
                            <?php if ($isHomeItem): ?>
                                <div class="readonly-box">1</div>
                                <input type="hidden" id="display_order" name="display_order" value="1">
                                <div class="helper">Inicio siempre permanece en la primera posicion del menu.</div>
                            <?php else: ?>
                                <select id="display_order_select" data-display-order-select<?php echo $menuData["parent_id"] !== null ? " disabled" : ""; ?>>
                                    <?php foreach ($availableMainPositions as $position): ?>
                                        <option value="<?php echo $position; ?>" <?php echo (int) $menuData["display_order"] === $position ? "selected" : ""; ?>><?php echo $position; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" id="display_order" name="display_order" value="<?php echo (int) $menuData["display_order"]; ?>" data-display-order-hidden>
                                <div class="helper" data-display-order-help><?php echo $menuData["parent_id"] !== null ? "La posici&oacute;n se hereda de la opci&oacute;n principal superior." : "La posici&oacute;n define el orden como opci&oacute;n principal."; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label>Mostrar en el men&uacute;</label>
                            <?php if ($isHomeItem): ?>
                                <div class="readonly-box">Visible siempre</div>
                                <input type="hidden" id="is_active" name="is_active" value="1">
                                <div class="helper">Esta opcion protegida siempre se mantiene visible.</div>
                            <?php else: ?>
                                <div class="checkbox-row">
                                    <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo (int) $menuData["is_active"] === 1 ? "checked" : ""; ?>>
                                    <label for="is_active" style="margin:0;font-weight:normal;">S&iacute;, mostrar</label>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>

                    <div class="actions-row">
                        <button type="submit" class="btn btn-primary"><?php echo $isCreateMode ? "Crear opci&oacute;n" : "Guardar cambios"; ?></button>
                        <a href="index.php" class="btn btn-outline">Cancelar</a>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <script>
        (function () {
            function bindMenuForm(form) {
                var linkTypeSelect = form.querySelector("[data-menu-link-type]");
                var pageSelect = form.querySelector("[data-menu-page-select]");
                var pageGroup = form.querySelector("[data-menu-page-group]");
                var nameInput = form.querySelector("[data-menu-name]");
                var urlInput = form.querySelector("[data-menu-url]");
                var urlHelp = form.querySelector("[data-menu-url-help]");
                var parentSelect = form.querySelector("#parent_id");
                var displayOrderSelect = form.querySelector("[data-display-order-select]");
                var displayOrderHidden = form.querySelector("[data-display-order-hidden]");
                var displayOrderHelp = form.querySelector("[data-display-order-help]");

                if (!linkTypeSelect || !nameInput || !urlInput) {
                    return;
                }

                var nameTouched = nameInput.value.trim() !== "";

                function currentLinkType() {
                    return linkTypeSelect.value === "internal" ? "internal" : "custom";
                }

                function selectedPageOption() {
                    return pageSelect ? pageSelect.options[pageSelect.selectedIndex] : null;
                }

                function selectedParentOption() {
                    return parentSelect ? parentSelect.options[parentSelect.selectedIndex] : null;
                }

                function syncDisplayOrderState() {
                    if (!displayOrderSelect || !displayOrderHidden) {
                        return;
                    }

                    var option = selectedParentOption();
                    var inheritedOrder = option && option.value !== "" ? option.getAttribute("data-root-order") : "";

                    if (inheritedOrder !== "") {
                        displayOrderSelect.value = inheritedOrder;
                        displayOrderHidden.value = inheritedOrder;
                        displayOrderSelect.disabled = true;
                        if (displayOrderHelp) {
                            displayOrderHelp.textContent = "La posici\u00f3n se hereda de la opci\u00f3n principal superior.";
                        }
                    } else {
                        displayOrderSelect.disabled = false;
                        displayOrderHidden.value = displayOrderSelect.value;
                        if (displayOrderHelp) {
                            displayOrderHelp.textContent = "La posici\u00f3n define el orden como opci\u00f3n principal.";
                        }
                    }
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

                    syncDisplayOrderState();
                }

                if (parentSelect) {
                    parentSelect.addEventListener("change", syncDisplayOrderState);
                }

                if (displayOrderSelect) {
                    displayOrderSelect.addEventListener("change", function () {
                        if (displayOrderHidden && !displayOrderSelect.disabled) {
                            displayOrderHidden.value = displayOrderSelect.value;
                        }
                    });
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
