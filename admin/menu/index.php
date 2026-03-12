<?php
require_once "../auth-check.php";
require_once "../../db.php";
require_once "menu-helpers.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

ensureHomeMenuItem($conn);

$status = (string) ($_GET["status"] ?? "");
$menuLinkSchema = getMenuItemsLinkSupport($conn);
$supportsMenuLinkTypes = $menuLinkSchema["link_type"] && $menuLinkSchema["site_page_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = (string) ($_POST["csrf_token"] ?? "");
    if (!isset($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $csrfToken)) {
        redirectToMenu("error");
    }

    $action = (string) ($_POST["action"] ?? "");

    if ($action === "save_button") {
        $buttonId = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
        $text = textValue((string) ($_POST["button_text"] ?? ""));
        $path = buttonPath((string) ($_POST["button_path"] ?? ""));
        $target = menuTarget((string) ($_POST["target"] ?? "_self"));
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
        $row["link_type"] = ($row["link_type"] ?? "custom") === "internal" ? "internal" : "custom";
        $row["site_page_id"] = isset($row["site_page_id"]) ? (int) $row["site_page_id"] : 0;
        $items[] = $row;
    }
}

foreach ($items as $item) {
    if ((int) $item["is_button"] === 1) {
        if ($primaryButton === null) {
            $primaryButton = $item;
        }
        continue;
    }

    if (($item["item_key"] ?? "") === "home") {
        $homeItem = $item;
    } else {
        $menuOptions[] = $item;
    }
}

$visibleOptionsCount = 0;
foreach ($menuOptions as $item) {
    if ((int) ($item["is_active"] ?? 0) === 1) {
        $visibleOptionsCount++;
    }
}

$buttonConfigured = $primaryButton !== null;
$totalOptionsCount = count($menuOptions) + ($homeItem !== null ? 1 : 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Men&uacute; de navegaci&oacute;n</title>
    <style>
        *{box-sizing:border-box}body{font-family:Arial,sans-serif;background:#f4f6f9;margin:0;color:#1f2937}.layout{min-height:100vh;display:flex;align-items:flex-start}.sidebar{width:260px;background:#fff;border-right:1px solid #e5e7eb;padding:24px 18px;display:flex;flex-direction:column;gap:22px;position:sticky;top:0;height:100vh;overflow-y:auto;flex-shrink:0}.brand{padding-bottom:18px;border-bottom:1px solid #e5e7eb}.brand h2{margin:0;font-size:22px;color:#198754}.brand p{margin:8px 0 0;color:#6b7280;font-size:14px;line-height:1.4}.sidebar-section-title{margin:0 0 10px;font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af}.nav{display:flex;flex-direction:column;gap:8px}.nav a{display:flex;align-items:center;gap:10px;text-decoration:none;color:#374151;padding:11px 12px;border-radius:10px;transition:background .2s ease,color .2s ease}.nav a:hover{background:#eef8f2;color:#198754}.nav a.active{background:#e9f7ef;color:#198754;font-weight:bold}.nav-icon{width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center}.main{flex:1;padding:32px;min-width:0}.topbar{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:24px}.page-title{margin:0;font-size:34px;line-height:1.1}.page-subtitle{margin:10px 0 0;font-size:16px;color:#6b7280;max-width:760px}.topbar-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.btn{display:inline-block;padding:10px 16px;border-radius:10px;text-decoration:none;border:0;cursor:pointer;font-size:14px;transition:background .2s ease,transform .2s ease}.btn:hover{transform:translateY(-1px)}.btn-outline{background:#fff;color:#198754;border:1px solid #cfe7d8}.btn-outline:hover{background:#eef8f2}.btn-logout{background:#dc3545;color:#fff}.btn-logout:hover{background:#bb2d3b}.btn-primary{background:#198754;color:#fff}.btn-primary:hover{background:#157347}.btn-soft{background:#fff4db;color:#996b00;border:1px solid #f5deb3}.btn-soft:hover{background:#fdecc8}.btn-danger{background:#f8d7da;color:#842029;border:1px solid #f1b0b7}.btn-danger:hover{background:#f1c4ca}.card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:24px;box-shadow:0 8px 24px rgba(0,0,0,.04);margin-bottom:18px}.card h2{margin:0 0 10px;font-size:22px}.card p{margin:0 0 18px;line-height:1.6;color:#6b7280}.alert{border-radius:12px;padding:14px 16px;margin-bottom:18px;font-size:14px}.alert-success{background:#e9f7ef;color:#146c43;border:1px solid #cfe7d8}.alert-error{background:#f8d7da;color:#842029;border:1px solid #f1b0b7}.summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.summary-box{background:#f9fafb;border:1px solid #e5e7eb;border-radius:14px;padding:16px}.summary-label{font-size:12px;color:#6b7280;margin-bottom:6px;text-transform:uppercase}.summary-value{font-size:22px;font-weight:bold}.pages-table-wrapper{overflow-x:auto}.pages-table{width:100%;border-collapse:collapse;min-width:920px}.pages-table th,.pages-table td{padding:14px 12px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}.pages-table th{font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;background:#f9fafb}.pages-table tbody tr:hover{background:#fcfdfc}.status-pill{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:bold}.status-visible{background:#e9f7ef;color:#146c43}.status-hidden{background:#f3f4f6;color:#4b5563}.status-fixed{background:#eef8f2;color:#198754}.muted{color:#6b7280}.actions-group{display:flex;gap:8px;flex-wrap:wrap}.inline-form{margin:0}.empty-state{padding:18px;border-radius:12px;background:#f9fafb;border:1px dashed #d1d5db;color:#6b7280}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.form-group{margin:0}label{display:block;margin-bottom:8px;font-weight:bold;color:#374151}input[type=text],select{width:100%;height:44px;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;font-size:14px;outline:none;background:#fff}.checkbox-row{display:flex;align-items:center;gap:10px;min-height:44px}.checkbox-row input{margin:0}.helper{font-size:13px;color:#6b7280;margin-top:8px}.protected-badge{display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;background:#eef8f2;color:#198754;font-size:12px;font-weight:bold;margin-top:8px}@media (max-width:1100px){.summary-grid,.form-grid{grid-template-columns:1fr}}@media (max-width:991px){.layout{flex-direction:column}.sidebar{width:100%;border-right:0;border-bottom:1px solid #e5e7eb;padding:18px;position:static;top:auto;height:auto;overflow-y:visible}.main{padding:22px}.topbar{flex-direction:column;align-items:stretch}.topbar-actions{justify-content:flex-start}}@media (max-width:640px){.sidebar{padding:16px}.main{padding:16px}.card{padding:18px;border-radius:14px}.page-title{font-size:26px}}
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
                    <h1 class="page-title">Men&uacute; de navegaci&oacute;n</h1>
                    <p class="page-subtitle">Administra las opciones visibles del men&uacute; superior con una vista resumida, acciones claras y formularios separados para crear o editar.</p>
                </div>
                <div class="topbar-actions">
                    <a href="edit.php?action=create" class="btn btn-primary">Crear nueva opci&oacute;n</a>
                    <a href="../dashboard.php" class="btn btn-outline">Volver al panel</a>
                    <form action="../logout.php" method="post" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                        <button type="submit" class="btn btn-logout">Cerrar sesi&oacute;n</button>
                    </form>
                </div>
            </div>

            <?php if (in_array($status, ["created", "updated", "toggled", "deleted"], true)): ?><div class="alert alert-success">Los cambios se guardaron correctamente.</div><?php endif; ?>
            <?php if (in_array($status, ["invalid", "error"], true)): ?><div class="alert alert-error">No se pudo guardar la informaci&oacute;n. Revisa los datos e intenta nuevamente.</div><?php endif; ?>
            <?php if ($status === "protected"): ?><div class="alert alert-error">La opci&oacute;n Inicio es fija del sistema y no se puede editar, ocultar ni eliminar.</div><?php endif; ?>
            <?php if ($status === "limit_button"): ?><div class="alert alert-error">Solo puedes tener un bot&oacute;n principal configurado.</div><?php endif; ?>
            <?php if ($status === "migration_required"): ?><div class="alert alert-error">Falta ejecutar la actualizaci&oacute;n de base de datos del men&uacute; para habilitar p&aacute;ginas internas.</div><?php endif; ?>
            <?php if (!$supportsMenuLinkTypes): ?><div class="alert alert-error">El listado ya est&aacute; preparado para p&aacute;ginas internas, pero primero debes ejecutar manualmente el SQL pendiente en <span class="muted">menu_items</span>.</div><?php endif; ?>

            <section class="card">
                <h2>Resumen</h2>
                <p>Vista resumida de las opciones del men&uacute; y su estado actual dentro del sitio.</p>
                <div class="summary-grid">
                    <div class="summary-box"><div class="summary-label">Opciones registradas</div><div class="summary-value"><?php echo $totalOptionsCount; ?> de 8</div></div>
                    <div class="summary-box"><div class="summary-label">Opciones visibles</div><div class="summary-value"><?php echo $visibleOptionsCount + ($homeItem !== null ? 1 : 0); ?></div></div>
                    <div class="summary-box"><div class="summary-label">Bot&oacute;n principal</div><div class="summary-value"><?php echo $buttonConfigured ? "Configurado" : "Pendiente"; ?></div></div>
                </div>
            </section>

            <section class="card">
                <h2>Opciones del men&uacute;</h2>
                <p>Aqu&iacute; se muestran las opciones actuales del men&uacute; con acciones r&aacute;pidas para administrar su visibilidad y edici&oacute;n.</p>

                <div class="pages-table-wrapper">
                    <table class="pages-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre visible</th>
                                <th>Tipo de opci&oacute;n</th>
                                <th>Direcci&oacute;n</th>
                                <th>Posici&oacute;n</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($homeItem !== null): ?>
                                <tr>
                                    <td><?php echo (int) $homeItem["id"]; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars((string) $homeItem["label"], ENT_QUOTES, "UTF-8"); ?>
                                        <div class="protected-badge">Opci&oacute;n protegida</div>
                                    </td>
                                    <td>P&aacute;gina interna</td>
                                    <td><?php echo htmlspecialchars((string) $homeItem["url"], ENT_QUOTES, "UTF-8"); ?></td>
                                    <td>1</td>
                                    <td><span class="status-pill status-fixed">Fija</span></td>
                                    <td class="muted">Sin acciones disponibles</td>
                                </tr>
                            <?php endif; ?>

                            <?php if ($menuOptions !== []): ?>
                                <?php foreach ($menuOptions as $item): ?>
                                    <?php $isActive = (int) ($item["is_active"] ?? 0) === 1; ?>
                                    <tr>
                                        <td><?php echo (int) $item["id"]; ?></td>
                                        <td><?php echo htmlspecialchars((string) ($item["label"] ?? ""), ENT_QUOTES, "UTF-8"); ?></td>
                                        <td><?php echo ($item["link_type"] ?? "custom") === "internal" ? "P&aacute;gina interna" : "Enlace personalizado"; ?></td>
                                        <td><?php echo htmlspecialchars((string) ($item["url"] ?? ""), ENT_QUOTES, "UTF-8"); ?></td>
                                        <td><?php echo (int) ($item["display_order"] ?? 0); ?></td>
                                        <td><span class="status-pill <?php echo $isActive ? "status-visible" : "status-hidden"; ?>"><?php echo $isActive ? "Visible" : "Oculta"; ?></span></td>
                                        <td>
                                            <div class="actions-group">
                                                <a href="edit.php?id=<?php echo (int) $item["id"]; ?>" class="btn btn-primary">Editar</a>
                                                <form action="index.php" method="post" class="inline-form">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                                                    <input type="hidden" name="action" value="toggle">
                                                    <input type="hidden" name="id" value="<?php echo (int) $item["id"]; ?>">
                                                    <input type="hidden" name="next_state" value="<?php echo $isActive ? 0 : 1; ?>">
                                                    <button type="submit" class="btn btn-soft"><?php echo $isActive ? "Ocultar" : "Mostrar"; ?></button>
                                                </form>
                                                <form action="index.php" method="post" class="inline-form" onsubmit="return confirm('¿Seguro que deseas eliminar esta opción del menú?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo (int) $item["id"]; ?>">
                                                    <button type="submit" class="btn btn-danger">Eliminar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php elseif ($homeItem === null): ?>
                                <tr>
                                    <td colspan="7" class="muted">A&uacute;n no hay opciones configuradas para el men&uacute; superior.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card">
                <h2>Bot&oacute;n principal</h2>
                <p>Este bot&oacute;n se mantiene en esta pantalla como configuraci&oacute;n r&aacute;pida independiente del listado principal.</p>
                <form action="index.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                    <input type="hidden" name="action" value="save_button">
                    <input type="hidden" name="id" value="<?php echo $primaryButton !== null ? (int) $primaryButton["id"] : ""; ?>">
                    <div class="form-grid">
                        <div class="form-group"><label for="button_text">Texto del bot&oacute;n</label><input type="text" id="button_text" name="button_text" maxlength="255" required value="<?php echo htmlspecialchars((string) ($primaryButton["label"] ?? ""), ENT_QUOTES, "UTF-8"); ?>"></div>
                        <div class="form-group"><label for="button_path">Direcci&oacute;n del bot&oacute;n</label><input type="text" id="button_path" name="button_path" maxlength="255" required value="<?php echo htmlspecialchars((string) ($primaryButton["url"] ?? ""), ENT_QUOTES, "UTF-8"); ?>"><div class="helper">Aqu&iacute; puedes escribir un enlace completo real, por ejemplo: https://midominio.com/agendar o https://wa.me/593...</div></div>
                        <div class="form-group"><label for="button_target">Abrir enlace</label><select id="button_target" name="target"><option value="_self" <?php echo (($primaryButton["target"] ?? "_self") === "_self") ? "selected" : ""; ?>>En la misma pesta&ntilde;a</option><option value="_blank" <?php echo (($primaryButton["target"] ?? "_self") === "_blank") ? "selected" : ""; ?>>En una pesta&ntilde;a nueva</option></select></div>
                        <div class="form-group"><label>Mostrar bot&oacute;n principal</label><div class="checkbox-row"><input type="checkbox" id="button_active" name="is_active" value="1" <?php echo ((int) ($primaryButton["is_active"] ?? 1) === 1) ? "checked" : ""; ?>><label for="button_active" style="margin:0;font-weight:normal;">S&iacute;, mostrar</label></div></div>
                    </div>
                    <div class="actions-group" style="margin-top:20px;">
                        <button type="submit" class="btn btn-primary"><?php echo $primaryButton !== null ? "Guardar bot&oacute;n principal" : "Crear bot&oacute;n principal"; ?></button>
                        <?php if ($primaryButton !== null): ?>
                            <form action="index.php" method="post" class="inline-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo (int) $primaryButton["id"]; ?>">
                                <input type="hidden" name="next_state" value="<?php echo ((int) ($primaryButton["is_active"] ?? 0) === 1) ? 0 : 1; ?>">
                                <button type="submit" class="btn btn-soft"><?php echo ((int) ($primaryButton["is_active"] ?? 0) === 1) ? "Ocultar" : "Mostrar"; ?></button>
                            </form>
                            <form action="index.php" method="post" class="inline-form" onsubmit="return confirm('¿Seguro que deseas eliminar este botón principal?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int) $primaryButton["id"]; ?>">
                                <button type="submit" class="btn btn-danger">Eliminar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
