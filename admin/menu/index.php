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

function sanitizeMenuText(string $value, int $maxLength = 255): string
{
    $value = trim($value);

    if ($value === "") {
        return "";
    }

    return substr($value, 0, $maxLength);
}

$status = $_GET["status"] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = $_POST["csrf_token"] ?? "";

    if (!isset($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $csrfToken)) {
        redirectToMenu("error");
    }

    $action = $_POST["action"] ?? "";

    if ($action === "create") {
        $label = sanitizeMenuText($_POST["label"] ?? "");
        $url = sanitizeMenuText($_POST["url"] ?? "");
        $displayOrder = filter_input(INPUT_POST, "display_order", FILTER_VALIDATE_INT);
        $isActive = isset($_POST["is_active"]) ? 1 : 0;
        $isButton = isset($_POST["is_button"]) ? 1 : 0;
        $target = sanitizeMenuText($_POST["target"] ?? "_self", 50);

        if ($label === "" || $url === "" || $displayOrder === false) {
            redirectToMenu("invalid");
        }

        $insertSql = "INSERT INTO menu_items
                      (parent_id, label, url, display_order, is_active, is_button, target, created_at, updated_at)
                      VALUES (NULL, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $insertStmt = $conn->prepare($insertSql);

        if (!$insertStmt) {
            redirectToMenu("error");
        }

        $insertStmt->bind_param("ssiiss", $label, $url, $displayOrder, $isActive, $isButton, $target);
        $ok = $insertStmt->execute();
        $insertStmt->close();

        redirectToMenu($ok ? "created" : "error");
    }

    if ($action === "update") {
        $itemId = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
        $label = sanitizeMenuText($_POST["label"] ?? "");
        $url = sanitizeMenuText($_POST["url"] ?? "");
        $displayOrder = filter_input(INPUT_POST, "display_order", FILTER_VALIDATE_INT);

        if ($itemId === false || $itemId === null || $label === "" || $url === "" || $displayOrder === false) {
            redirectToMenu("invalid");
        }

        $updateSql = "UPDATE menu_items
                      SET label = ?, url = ?, display_order = ?, updated_at = NOW()
                      WHERE id = ?
                      LIMIT 1";
        $updateStmt = $conn->prepare($updateSql);

        if (!$updateStmt) {
            redirectToMenu("error");
        }

        $updateStmt->bind_param("ssii", $label, $url, $displayOrder, $itemId);
        $ok = $updateStmt->execute();
        $updateStmt->close();

        redirectToMenu($ok ? "updated" : "error");
    }

    if ($action === "toggle") {
        $itemId = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
        $nextState = filter_input(INPUT_POST, "next_state", FILTER_VALIDATE_INT);

        if ($itemId === false || $itemId === null || ($nextState !== 0 && $nextState !== 1)) {
            redirectToMenu("invalid");
        }

        $toggleSql = "UPDATE menu_items
                      SET is_active = ?, updated_at = NOW()
                      WHERE id = ?
                      LIMIT 1";
        $toggleStmt = $conn->prepare($toggleSql);

        if (!$toggleStmt) {
            redirectToMenu("error");
        }

        $toggleStmt->bind_param("ii", $nextState, $itemId);
        $ok = $toggleStmt->execute();
        $toggleStmt->close();

        redirectToMenu($ok ? "toggled" : "error");
    }

    redirectToMenu("error");
}

$items = [];
$normalItems = [];
$buttonItems = [];
$listSql = "SELECT id, parent_id, label, url, display_order, is_active, is_button, target
            FROM menu_items
            ORDER BY is_button ASC, display_order ASC, id ASC";
$listResult = $conn->query($listSql);

if ($listResult) {
    while ($row = $listResult->fetch_assoc()) {
        $items[] = $row;
    }
}

foreach ($items as $item) {
    if ((int) $item["is_button"] === 1) {
        $buttonItems[] = $item;
        continue;
    }

    $normalItems[] = $item;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Men&uacute; de navegaci&oacute;n</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 0; color: #1f2937; }
        .layout { min-height: 100vh; display: flex; }
        .sidebar { width: 260px; background: #ffffff; border-right: 1px solid #e5e7eb; padding: 24px 18px; display: flex; flex-direction: column; gap: 22px; }
        .brand { padding-bottom: 18px; border-bottom: 1px solid #e5e7eb; }
        .brand h2 { margin: 0; font-size: 22px; color: #198754; }
        .brand p { margin: 8px 0 0; color: #6b7280; font-size: 14px; line-height: 1.4; }
        .sidebar-section-title { margin: 0 0 10px; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af; }
        .nav { display: flex; flex-direction: column; gap: 8px; }
        .nav a { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #374151; padding: 11px 12px; border-radius: 10px; transition: background 0.2s ease, color 0.2s ease; }
        .nav a:hover { background: #eef8f2; color: #198754; }
        .nav a.active { background: #e9f7ef; color: #198754; font-weight: bold; }
        .nav-icon { width: 18px; height: 18px; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; }
        .main { flex: 1; padding: 32px; }
        .topbar { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 24px; }
        .page-title { margin: 0; font-size: 34px; line-height: 1.1; }
        .page-subtitle { margin: 10px 0 0; font-size: 16px; color: #6b7280; }
        .topbar-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 10px 16px; border-radius: 10px; text-decoration: none; border: 0; cursor: pointer; font-size: 14px; transition: background 0.2s ease, transform 0.2s ease; }
        .btn:hover { transform: translateY(-1px); }
        .btn-outline { background: #ffffff; color: #198754; border: 1px solid #cfe7d8; }
        .btn-outline:hover { background: #eef8f2; }
        .btn-logout { background: #dc3545; color: #ffffff; }
        .btn-logout:hover { background: #bb2d3b; }
        .btn-primary { background: #198754; color: #ffffff; }
        .btn-primary:hover { background: #157347; }
        .btn-warning { background: #fff4db; color: #996b00; border: 1px solid #f5deb3; }
        .btn-warning:hover { background: #fdecc8; }
        .card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04); margin-bottom: 18px; }
        .card h2 { margin: 0 0 10px; font-size: 22px; }
        .card p { margin: 0 0 18px; line-height: 1.6; color: #6b7280; }
        .alert { border-radius: 12px; padding: 14px 16px; margin-bottom: 18px; font-size: 14px; }
        .alert-success { background: #e9f7ef; color: #146c43; border: 1px solid #cfe7d8; }
        .alert-error { background: #f8d7da; color: #842029; border: 1px solid #f1b0b7; }
        .split-grid { display: grid; grid-template-columns: 1.3fr 0.9fr; gap: 18px; align-items: start; }
        .section-title { margin: 0 0 14px; font-size: 18px; }
        .empty-state { padding: 18px; border-radius: 12px; background: #f9fafb; border: 1px dashed #d1d5db; color: #6b7280; }
        .menu-list { display: grid; gap: 14px; }
        .menu-item { border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px; background: #fcfcfd; }
        .menu-item-header { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 12px; }
        .menu-item-title { margin: 0; font-size: 17px; }
        .badge-row { display: flex; gap: 8px; flex-wrap: wrap; }
        .badge { display: inline-flex; align-items: center; padding: 5px 10px; border-radius: 999px; font-size: 12px; font-weight: bold; }
        .badge-type { background: #eef8f2; color: #146c43; }
        .badge-button { background: #fff4db; color: #996b00; }
        .badge-active { background: #e9f7ef; color: #146c43; }
        .badge-inactive { background: #f8d7da; color: #842029; }
        .meta-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 14px; }
        .meta-box { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; }
        .meta-label { font-size: 12px; text-transform: uppercase; color: #9ca3af; margin-bottom: 6px; }
        .meta-value { font-size: 14px; color: #374151; word-break: break-word; }
        .inline-form-grid { display: grid; grid-template-columns: 1.1fr 1.4fr 140px auto; gap: 10px; align-items: end; }
        .create-form-grid { display: grid; grid-template-columns: 1fr 1fr 140px 140px 160px; gap: 12px; align-items: end; }
        .form-group { margin: 0; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #374151; }
        input[type="text"], input[type="number"], select { width: 100%; height: 44px; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 14px; outline: none; transition: border-color 0.2s ease, box-shadow 0.2s ease; background: #ffffff; }
        input[type="text"]:focus, input[type="number"]:focus, select:focus { border-color: #198754; box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.12); }
        .checkbox-row { display: flex; align-items: center; gap: 8px; height: 44px; }
        .checkbox-row input { margin: 0; }
        .actions-row { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px; }
        @media (max-width: 1100px) { .split-grid, .create-form-grid, .inline-form-grid, .meta-grid { grid-template-columns: 1fr; } }
        @media (max-width: 991px) { .layout { flex-direction: column; } .sidebar { width: 100%; border-right: 0; border-bottom: 1px solid #e5e7eb; padding: 18px; } .main { padding: 22px; } .topbar { flex-direction: column; align-items: stretch; } .topbar-actions { justify-content: flex-start; } }
        @media (max-width: 640px) { .sidebar { padding: 16px; } .main { padding: 16px; } .card, .menu-item { padding: 18px; border-radius: 14px; } .page-title { font-size: 26px; } }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="brand">
                <h2>Panel Admin</h2>
                <p>Gestiona el contenido principal del sitio desde un solo lugar.</p>
            </div>

            <div>
                <p class="sidebar-section-title">Principal</p>
                <nav class="nav">
                    <a href="../dashboard.php">
                        <span class="nav-icon">&#127968;</span>
                        <span>Panel de inicio</span>
                    </a>
                    <a href="index.php" class="active">
                        <span class="nav-icon">&#128203;</span>
                        <span>Men&uacute; de navegaci&oacute;n</span>
                    </a>
                </nav>
            </div>

            <div>
                <p class="sidebar-section-title">Configuraci&oacute;n</p>
                <nav class="nav">
                    <a href="../settings.php">
                        <span class="nav-icon">&#9881;</span>
                        <span>Configuraci&oacute;n</span>
                    </a>
                    <a href="../change-password.php">
                        <span class="nav-icon">&#128274;</span>
                        <span>Cambiar contrase&ntilde;a</span>
                    </a>
                </nav>
            </div>
        </aside>

        <main class="main">
            <div class="topbar">
                <div>
                    <h1 class="page-title">Men&uacute; de navegaci&oacute;n</h1>
                    <p class="page-subtitle">Administra los enlaces del header y el bot&oacute;n destacado del navbar.</p>
                </div>
                <div class="topbar-actions">
                    <a href="../dashboard.php" class="btn btn-outline">Volver al panel</a>
                    <form action="../logout.php" method="post" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                        <button type="submit" class="btn btn-logout">Cerrar sesi&oacute;n</button>
                    </form>
                </div>
            </div>

            <?php if ($status === "created" || $status === "updated" || $status === "toggled"): ?>
                <div class="alert alert-success">Los cambios del men&uacute; de navegaci&oacute;n se guardaron correctamente.</div>
            <?php endif; ?>
            <?php if ($status === "invalid" || $status === "error"): ?>
                <div class="alert alert-error">No se pudo procesar la acci&oacute;n solicitada. Revisa los datos e intenta nuevamente.</div>
            <?php endif; ?>

            <div class="split-grid">
                <section class="card">
                    <h2>Crear nuevo elemento</h2>
                    <p>Agrega un enlace normal del navbar o el bot&oacute;n destacado. Si la tabla est&aacute; vac&iacute;a, empieza desde aqu&iacute;.</p>
                    <form action="index.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                        <input type="hidden" name="action" value="create">
                        <div class="create-form-grid">
                            <div class="form-group">
                                <label for="new_label">Label</label>
                                <input type="text" id="new_label" name="label" maxlength="255" required>
                            </div>
                            <div class="form-group">
                                <label for="new_url">URL</label>
                                <input type="text" id="new_url" name="url" maxlength="255" required placeholder="#contact o /contact.html">
                            </div>
                            <div class="form-group">
                                <label for="new_order">Orden</label>
                                <input type="number" id="new_order" name="display_order" required>
                            </div>
                            <div class="form-group">
                                <label for="new_target">Target</label>
                                <select id="new_target" name="target">
                                    <option value="_self">_self</option>
                                    <option value="_blank">_blank</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Opciones</label>
                                <div class="checkbox-row">
                                    <input type="checkbox" id="new_active" name="is_active" value="1" checked>
                                    <label for="new_active" style="margin: 0; font-weight: normal;">Activo</label>
                                </div>
                                <div class="checkbox-row">
                                    <input type="checkbox" id="new_button" name="is_button" value="1">
                                    <label for="new_button" style="margin: 0; font-weight: normal;">Es bot&oacute;n destacado</label>
                                </div>
                            </div>
                        </div>
                        <div class="actions-row">
                            <button type="submit" class="btn btn-primary">Crear elemento</button>
                        </div>
                    </form>
                </section>

                <section class="card">
                    <h2>Resumen esperado</h2>
                    <p>Este m&oacute;dulo est&aacute; pensado para administrar hasta 8 enlaces normales de navegaci&oacute;n y 1 bot&oacute;n destacado.</p>
                    <div class="meta-grid">
                        <div class="meta-box"><div class="meta-label">Enlaces</div><div class="meta-value"><?php echo count($normalItems); ?> registrados</div></div>
                        <div class="meta-box"><div class="meta-label">Botones</div><div class="meta-value"><?php echo count($buttonItems); ?> registrados</div></div>
                        <div class="meta-box"><div class="meta-label">Tabla</div><div class="meta-value">menu_items</div></div>
                    </div>
                </section>
            </div>

            <section class="card">
                <h2 class="section-title">Enlaces del men&uacute; principal</h2>
                <?php if (count($normalItems) === 0): ?>
                    <div class="empty-state">A&uacute;n no hay elementos configurados para el men&uacute; de navegaci&oacute;n.</div>
                <?php else: ?>
                    <div class="menu-list">
                        <?php foreach ($normalItems as $item): ?>
                            <article class="menu-item">
                                <div class="menu-item-header">
                                    <h3 class="menu-item-title"><?php echo htmlspecialchars($item["label"], ENT_QUOTES, "UTF-8"); ?></h3>
                                    <div class="badge-row">
                                        <span class="badge badge-type">Enlace</span>
                                        <span class="badge <?php echo (int) $item["is_active"] === 1 ? "badge-active" : "badge-inactive"; ?>"><?php echo (int) $item["is_active"] === 1 ? "Activo" : "Inactivo"; ?></span>
                                    </div>
                                </div>
                                <div class="meta-grid">
                                    <div class="meta-box"><div class="meta-label">URL</div><div class="meta-value"><?php echo htmlspecialchars($item["url"], ENT_QUOTES, "UTF-8"); ?></div></div>
                                    <div class="meta-box"><div class="meta-label">Orden</div><div class="meta-value"><?php echo (int) $item["display_order"]; ?></div></div>
                                    <div class="meta-box"><div class="meta-label">Target</div><div class="meta-value"><?php echo htmlspecialchars($item["target"] ?: "_self", ENT_QUOTES, "UTF-8"); ?></div></div>
                                </div>
                                <form action="index.php" method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?php echo (int) $item["id"]; ?>">
                                    <div class="inline-form-grid">
                                        <div class="form-group">
                                            <label for="label_<?php echo (int) $item["id"]; ?>">Label</label>
                                            <input type="text" id="label_<?php echo (int) $item["id"]; ?>" name="label" maxlength="255" value="<?php echo htmlspecialchars($item["label"], ENT_QUOTES, "UTF-8"); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="url_<?php echo (int) $item["id"]; ?>">URL</label>
                                            <input type="text" id="url_<?php echo (int) $item["id"]; ?>" name="url" maxlength="255" value="<?php echo htmlspecialchars($item["url"], ENT_QUOTES, "UTF-8"); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="order_<?php echo (int) $item["id"]; ?>">Orden</label>
                                            <input type="number" id="order_<?php echo (int) $item["id"]; ?>" name="display_order" value="<?php echo (int) $item["display_order"]; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary">Guardar</button>
                                        </div>
                                    </div>
                                </form>
                                <div class="actions-row">
                                    <form action="index.php" method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo (int) $item["id"]; ?>">
                                        <input type="hidden" name="next_state" value="<?php echo (int) $item["is_active"] === 1 ? 0 : 1; ?>">
                                        <button type="submit" class="btn btn-warning"><?php echo (int) $item["is_active"] === 1 ? "Desactivar" : "Activar"; ?></button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card">
                <h2 class="section-title">Bot&oacute;n destacado</h2>
                <?php if (count($buttonItems) === 0): ?>
                    <div class="empty-state">A&uacute;n no hay un bot&oacute;n destacado configurado para el men&uacute; de navegaci&oacute;n.</div>
                <?php else: ?>
                    <div class="menu-list">
                        <?php foreach ($buttonItems as $item): ?>
                            <article class="menu-item">
                                <div class="menu-item-header">
                                    <h3 class="menu-item-title"><?php echo htmlspecialchars($item["label"], ENT_QUOTES, "UTF-8"); ?></h3>
                                    <div class="badge-row">
                                        <span class="badge badge-button">Bot&oacute;n</span>
                                        <span class="badge <?php echo (int) $item["is_active"] === 1 ? "badge-active" : "badge-inactive"; ?>"><?php echo (int) $item["is_active"] === 1 ? "Activo" : "Inactivo"; ?></span>
                                    </div>
                                </div>
                                <div class="meta-grid">
                                    <div class="meta-box"><div class="meta-label">URL</div><div class="meta-value"><?php echo htmlspecialchars($item["url"], ENT_QUOTES, "UTF-8"); ?></div></div>
                                    <div class="meta-box"><div class="meta-label">Orden</div><div class="meta-value"><?php echo (int) $item["display_order"]; ?></div></div>
                                    <div class="meta-box"><div class="meta-label">Target</div><div class="meta-value"><?php echo htmlspecialchars($item["target"] ?: "_self", ENT_QUOTES, "UTF-8"); ?></div></div>
                                </div>
                                <form action="index.php" method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?php echo (int) $item["id"]; ?>">
                                    <div class="inline-form-grid">
                                        <div class="form-group">
                                            <label for="button_label_<?php echo (int) $item["id"]; ?>">Label</label>
                                            <input type="text" id="button_label_<?php echo (int) $item["id"]; ?>" name="label" maxlength="255" value="<?php echo htmlspecialchars($item["label"], ENT_QUOTES, "UTF-8"); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="button_url_<?php echo (int) $item["id"]; ?>">URL</label>
                                            <input type="text" id="button_url_<?php echo (int) $item["id"]; ?>" name="url" maxlength="255" value="<?php echo htmlspecialchars($item["url"], ENT_QUOTES, "UTF-8"); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="button_order_<?php echo (int) $item["id"]; ?>">Orden</label>
                                            <input type="number" id="button_order_<?php echo (int) $item["id"]; ?>" name="display_order" value="<?php echo (int) $item["display_order"]; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary">Guardar</button>
                                        </div>
                                    </div>
                                </form>
                                <div class="actions-row">
                                    <form action="index.php" method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo (int) $item["id"]; ?>">
                                        <input type="hidden" name="next_state" value="<?php echo (int) $item["is_active"] === 1 ? 0 : 1; ?>">
                                        <button type="submit" class="btn btn-warning"><?php echo (int) $item["is_active"] === 1 ? "Desactivar" : "Activar"; ?></button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
