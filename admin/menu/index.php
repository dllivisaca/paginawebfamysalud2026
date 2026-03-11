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

$status = $_GET["status"] ?? "";

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
        $path = menuPath($_POST["path"] ?? "");
        $position = filter_input(INPUT_POST, "display_order", FILTER_VALIDATE_INT);
        $target = menuTarget($_POST["target"] ?? "_self");
        $isActive = isset($_POST["is_active"]) ? 1 : 0;

        if ($name === "" || $path === "" || $position === false || $position < 1 || $position > 8) {
            redirectToMenu("invalid");
        }

        $stmt = $conn->prepare("INSERT INTO menu_items (parent_id, label, url, display_order, is_active, is_button, target, created_at, updated_at) VALUES (NULL, ?, ?, ?, ?, 0, ?, NOW(), NOW())");
        if (!$stmt) {
            redirectToMenu("error");
        }
        $stmt->bind_param("ssiis", $name, $path, $position, $isActive, $target);
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
        $path = menuPath($_POST["path"] ?? "");
        $position = filter_input(INPUT_POST, "display_order", FILTER_VALIDATE_INT);

        if ($itemId === false || $itemId === null || $name === "" || $path === "" || $position === false || $position < 1 || $position > 8) {
            redirectToMenu("invalid");
        }

        $stmt = $conn->prepare("UPDATE menu_items SET label = ?, url = ?, display_order = ?, updated_at = NOW() WHERE id = ? AND is_button = 0 LIMIT 1");
        if (!$stmt) {
            redirectToMenu("error");
        }
        $stmt->bind_param("ssii", $name, $path, $position, $itemId);
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
$result = $conn->query("SELECT id, label, url, display_order, is_active, is_button, target FROM menu_items ORDER BY is_button ASC, display_order ASC, id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

foreach ($items as $item) {
    if ((int) $item["is_button"] === 1) {
        if ($primaryButton === null) {
            $primaryButton = $item;
        }
    } else {
        $menuOptions[] = $item;
    }
}

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
            <div><p class="sidebar-section-title">Configuraci&oacute;n</p><nav class="nav"><a href="../settings.php"><span class="nav-icon">&#9881;</span><span>Configuraci&oacute;n</span></a><a href="../change-password.php"><span class="nav-icon">&#128274;</span><span>Cambiar contrase&ntilde;a</span></a></nav></div>
        </aside>
        <main class="main">
            <div class="topbar">
                <div><h1 class="page-title">Men&uacute; de navegaci&oacute;n</h1><p class="page-subtitle">Define las opciones visibles del men&uacute; superior de tu sitio web.</p></div>
                <div class="topbar-actions"><a href="../dashboard.php" class="btn btn-outline">Volver al panel</a><form action="../logout.php" method="post" style="margin:0;"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>"><button type="submit" class="btn btn-logout">Cerrar sesi&oacute;n</button></form></div>
            </div>

            <?php if ($status === "created" || $status === "updated" || $status === "toggled" || $status === "deleted"): ?><div class="alert alert-success">Los cambios se guardaron correctamente.</div><?php endif; ?>
            <?php if ($status === "invalid" || $status === "error"): ?><div class="alert alert-error">No se pudo guardar la informaci&oacute;n. Revisa los datos e intenta nuevamente.</div><?php endif; ?>
            <?php if ($status === "limit_options"): ?><div class="alert alert-error">Ya tienes configuradas las 8 opciones permitidas para el men&uacute;.</div><?php endif; ?>
            <?php if ($status === "limit_button"): ?><div class="alert alert-error">Solo puedes tener un bot&oacute;n principal configurado.</div><?php endif; ?>

            <section class="card">
                <h2>Resumen</h2>
                <div class="summary-grid">
                    <div class="summary-box"><div class="summary-label">Opciones del men&uacute;</div><div class="summary-value"><?php echo count($menuOptions); ?> de 8</div></div>
                    <div class="summary-box"><div class="summary-label">Bot&oacute;n principal</div><div class="summary-value"><?php echo $buttonConfigured ? "Configurado" : "Pendiente"; ?></div></div>
                </div>
            </section>

            <section class="card">
                <h2>Agregar opci&oacute;n del men&uacute;</h2>
                <p>Puedes agregar hasta 8 opciones al men&uacute;.</p>
                <form action="index.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                    <input type="hidden" name="action" value="create_option">
                    <div class="form-grid">
                        <div class="form-group"><label for="new_name">Nombre de la p&aacute;gina</label><input type="text" id="new_name" name="name" maxlength="255" required data-slug-source="menu-option"></div>
                        <div class="form-group"><label for="new_path">Direcci&oacute;n de la p&aacute;gina</label><div class="input-prefix"><span>/</span><input type="text" id="new_path" name="path" maxlength="255" required data-slug-target="menu-option"></div><div class="helper">Se genera autom&aacute;ticamente, pero puedes editarla manualmente si lo necesitas.</div></div>
                        <div class="form-group"><label for="new_target">Abrir enlace</label><select id="new_target" name="target"><option value="_self">En la misma pesta&ntilde;a</option><option value="_blank">En una pesta&ntilde;a nueva</option></select></div>
                        <div class="form-group"><label for="new_order">Posici&oacute;n en el men&uacute;</label><select id="new_order" name="display_order"><?php for ($i = 1; $i <= 8; $i++): ?><option value="<?php echo $i; ?>"><?php echo $i; ?></option><?php endfor; ?></select></div>
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
                <?php if (count($menuOptions) === 0): ?>
                    <div class="empty-state">A&uacute;n no hay opciones configuradas para el men&uacute; superior.</div>
                <?php else: ?>
                    <div class="menu-list">
                        <?php foreach ($menuOptions as $item): ?>
                            <article class="menu-item">
                                <div class="item-header"><h4 class="item-title"><?php echo htmlspecialchars($item["label"], ENT_QUOTES, "UTF-8"); ?></h4><span class="status-pill <?php echo (int) $item["is_active"] === 1 ? "status-visible" : "status-hidden"; ?>"><?php echo (int) $item["is_active"] === 1 ? "Visible" : "Oculta"; ?></span></div>
                                <div class="meta-grid">
                                    <div class="meta-box"><div class="meta-label">Direcci&oacute;n</div><div class="meta-value">/<?php echo htmlspecialchars($item["url"], ENT_QUOTES, "UTF-8"); ?></div></div>
                                    <div class="meta-box"><div class="meta-label">Posici&oacute;n</div><div class="meta-value"><?php echo (int) $item["display_order"]; ?></div></div>
                                    <div class="meta-box"><div class="meta-label">Apertura</div><div class="meta-value"><?php echo ($item["target"] ?? "_self") === "_blank" ? "En una pesta&ntilde;a nueva" : "En la misma pesta&ntilde;a"; ?></div></div>
                                </div>
                                <form action="index.php" method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                                    <input type="hidden" name="action" value="update_option">
                                    <input type="hidden" name="id" value="<?php echo (int) $item["id"]; ?>">
                                    <div class="form-grid-compact">
                                        <div class="form-group"><label for="name_<?php echo (int) $item["id"]; ?>">Nombre visible</label><input type="text" id="name_<?php echo (int) $item["id"]; ?>" name="name" maxlength="255" required value="<?php echo htmlspecialchars($item["label"], ENT_QUOTES, "UTF-8"); ?>" data-slug-source="option-<?php echo (int) $item["id"]; ?>"></div>
                                        <div class="form-group"><label for="path_<?php echo (int) $item["id"]; ?>">Direcci&oacute;n</label><div class="input-prefix"><span>/</span><input type="text" id="path_<?php echo (int) $item["id"]; ?>" name="path" maxlength="255" required value="<?php echo htmlspecialchars($item["url"], ENT_QUOTES, "UTF-8"); ?>" data-slug-target="option-<?php echo (int) $item["id"]; ?>"></div></div>
                                        <div class="form-group"><label for="order_<?php echo (int) $item["id"]; ?>">Posici&oacute;n</label><select id="order_<?php echo (int) $item["id"]; ?>" name="display_order"><?php for ($i = 1; $i <= 8; $i++): ?><option value="<?php echo $i; ?>" <?php echo (int) $item["display_order"] === $i ? "selected" : ""; ?>><?php echo $i; ?></option><?php endfor; ?></select></div>
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

            document.querySelectorAll("[data-slug-target]").forEach(function (input) {
                input.dataset.touched = input.value.trim() !== "" ? "true" : "false";
                input.addEventListener("input", function () {
                    input.dataset.touched = "true";
                    input.value = normalizeSlug(input.value);
                });
            });

            document.querySelectorAll("[data-slug-source]").forEach(function (input) {
                input.addEventListener("input", function () {
                    var key = input.getAttribute("data-slug-source");
                    var target = document.querySelector('[data-slug-target="' + key + '"]');
                    if (!target) {
                        return;
                    }
                    if (target.dataset.touched === "true" && target.value.trim() !== "") {
                        return;
                    }
                    target.value = normalizeSlug(input.value);
                });
            });
        }());
    </script>
</body>
</html>
