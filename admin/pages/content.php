<?php
require_once "../auth-check.php";
require_once "../../db.php";
require_once "../../includes/page-content.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$pageId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$status = (string) ($_GET["status"] ?? "");
$errors = [];
$successMessage = "";

if ($status === "updated") {
    $successMessage = "El contenido de la pagina se actualizo correctamente.";
}

$pageStmt = $conn->prepare(
    "SELECT id, title, page_key, slug, template_key, is_active
     FROM site_pages
     WHERE id = ?
     LIMIT 1"
);

$page = null;

if ($pageStmt && $pageId > 0) {
    $pageStmt->bind_param("i", $pageId);
    $pageStmt->execute();
    $pageResult = $pageStmt->get_result();
    $page = $pageResult ? $pageResult->fetch_assoc() : null;
    $pageStmt->close();
}

if (!$page) {
    $errors[] = "La pagina solicitada no existe.";
}

$templateKey = trim((string) ($page["template_key"] ?? ""));
$schema = $templateKey !== "" ? getPageContentTemplateSchema($templateKey) : null;

if ($page && !$schema) {
    $errors[] = "La plantilla actual de esta pagina aun no tiene edicion de contenido disponible.";
}

if ($page && $schema) {
    ensurePageContentRepeaterItems($conn, (int) $page["id"], $schema);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $page && $schema) {
    $csrfToken = (string) ($_POST["csrf_token"] ?? "");

    if (!hash_equals($_SESSION["csrf_token"], $csrfToken)) {
        $errors[] = "No se pudo validar la solicitud. Intenta de nuevo.";
    } else {
        foreach ($schema["simple_fields"] ?? [] as $fieldConfig) {
            $fieldKey = (string) ($fieldConfig["field_key"] ?? "");

            if ($fieldKey === "") {
                continue;
            }

            $fieldType = (string) ($fieldConfig["field_type"] ?? "text");
            $fieldValue = trim((string) ($_POST["simple_fields"][$fieldKey]["value"] ?? ""));
            $isVisible = isset($_POST["simple_fields"][$fieldKey]["is_visible"]) ? 1 : 0;

            if (!upsertPageContentField($conn, (int) $page["id"], $fieldKey, $fieldType, $fieldValue, $isVisible)) {
                $errors[] = "No fue posible guardar todos los campos simples.";
                break;
            }
        }

        if ($errors === []) {
            foreach ($schema["repeaters"] ?? [] as $repeaterConfig) {
                $repeaterKey = (string) ($repeaterConfig["repeater_key"] ?? "");

                if ($repeaterKey === "") {
                    continue;
                }

                foreach ($repeaterConfig["items"] ?? [] as $itemConfig) {
                    $itemIndex = (int) ($itemConfig["item_index"] ?? -1);

                    if ($itemIndex < 0) {
                        continue;
                    }

                    $itemVisible = isset($_POST["repeaters"][$repeaterKey][$itemIndex]["is_visible"]) ? 1 : 0;
                    $repeaterItemId = getRepeaterItemId($conn, (int) $page["id"], $repeaterKey, $itemIndex, $itemVisible);

                    if ($repeaterItemId <= 0) {
                        $errors[] = "No fue posible guardar la estructura de bloques repetibles.";
                        break 2;
                    }

                    foreach ($repeaterConfig["fields"] ?? [] as $fieldConfig) {
                        $fieldKey = (string) ($fieldConfig["field_key"] ?? "");

                        if ($fieldKey === "") {
                            continue;
                        }

                        $fieldType = (string) ($fieldConfig["field_type"] ?? "text");
                        $fieldValue = trim((string) ($_POST["repeaters"][$repeaterKey][$itemIndex]["fields"][$fieldKey] ?? ""));

                        if (!upsertRepeaterItemField($conn, $repeaterItemId, $fieldKey, $fieldType, $fieldValue)) {
                            $errors[] = "No fue posible guardar los bloques repetibles.";
                            break 3;
                        }
                    }
                }
            }
        }

        if ($errors === []) {
            header("Location: content.php?id=" . (int) $page["id"] . "&status=updated");
            exit;
        }
    }
}

$contentData = ($page && $schema)
    ? getPageContentData($conn, (int) $page["id"], $schema)
    : ["simple_fields" => [], "repeaters" => []];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar contenido</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; color: #1f2937; }
        .layout { min-height: 100vh; display: flex; align-items: flex-start; }
        .sidebar { width: 260px; background: #fff; border-right: 1px solid #e5e7eb; padding: 24px 18px; display: flex; flex-direction: column; gap: 22px; position: sticky; top: 0; height: 100vh; overflow-y: auto; flex-shrink: 0; }
        .brand { padding-bottom: 18px; border-bottom: 1px solid #e5e7eb; }
        .brand h2 { margin: 0; font-size: 22px; color: #198754; }
        .brand p { margin: 8px 0 0; color: #6b7280; font-size: 14px; line-height: 1.4; }
        .sidebar-section-title { margin: 0 0 10px; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: .08em; color: #9ca3af; }
        .nav { display: flex; flex-direction: column; gap: 8px; }
        .nav a { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #374151; padding: 11px 12px; border-radius: 10px; transition: background .2s ease, color .2s ease; }
        .nav a:hover { background: #eef8f2; color: #198754; }
        .nav a.active { background: #e9f7ef; color: #198754; font-weight: bold; }
        .nav-icon { width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; }
        .main { flex: 1; padding: 32px; min-width: 0; }
        .topbar { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 24px; }
        .page-title { margin: 0; font-size: 34px; line-height: 1.1; }
        .page-subtitle { margin: 10px 0 0; font-size: 16px; color: #6b7280; max-width: 760px; }
        .topbar-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 10px 16px; border-radius: 10px; text-decoration: none; border: 0; cursor: pointer; font-size: 14px; transition: background .2s ease, transform .2s ease; }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: #198754; color: #fff; }
        .btn-primary:hover { background: #157347; }
        .btn-outline { background: #fff; color: #198754; border: 1px solid #cfe7d8; }
        .btn-outline:hover { background: #eef8f2; }
        .btn-logout { background: #dc3545; color: #fff; }
        .btn-logout:hover { background: #bb2d3b; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; box-shadow: 0 8px 24px rgba(0,0,0,.04); margin-bottom: 18px; }
        .card h2 { margin: 0 0 10px; font-size: 22px; }
        .card p { margin: 0 0 18px; line-height: 1.6; color: #6b7280; }
        .flash-message { border-radius: 14px; padding: 14px 16px; margin-bottom: 18px; border: 1px solid #e5e7eb; }
        .flash-success { background: #e9f7ef; border-color: #cfe7d8; color: #146c43; }
        .flash-error { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
        .section-block { border: 1px solid #e5e7eb; border-radius: 14px; padding: 18px; margin-bottom: 16px; background: #f9fafb; }
        .section-block h3 { margin: 0 0 8px; font-size: 18px; }
        .field-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px 20px; }
        .field-group { display: flex; flex-direction: column; gap: 8px; }
        .field-group-full { grid-column: 1 / -1; }
        .field-label { font-size: 14px; font-weight: bold; color: #374151; }
        .form-input, .form-textarea { width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 10px 12px; font-size: 14px; background: #fff; }
        .form-textarea { min-height: 110px; resize: vertical; }
        .toggle-row { display: flex; align-items: center; gap: 10px; min-height: 44px; }
        .toggle-row input { margin: 0; }
        .item-title { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 14px; }
        .preview-image { max-width: 180px; border: 1px solid #d1d5db; border-radius: 12px; padding: 6px; background: #fff; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 22px; }
        .muted { color: #6b7280; }
        @media (max-width: 991px) {
            .layout { flex-direction: column; }
            .sidebar { width: 100%; border-right: 0; border-bottom: 1px solid #e5e7eb; padding: 18px; position: static; top: auto; height: auto; overflow-y: visible; }
            .main { padding: 22px; }
            .topbar { flex-direction: column; align-items: stretch; }
            .topbar-actions { justify-content: flex-start; }
        }
        @media (max-width: 720px) {
            .field-grid { grid-template-columns: 1fr; }
            .main { padding: 16px; }
            .sidebar { padding: 16px; }
            .card { padding: 18px; }
            .page-title { font-size: 28px; }
        }
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
                    <a href="../dashboard.php"><span class="nav-icon">&#127968;</span><span>Panel de inicio</span></a>
                    <a href="../menu/index.php"><span class="nav-icon">&#128203;</span><span>Men&uacute; de navegaci&oacute;n</span></a>
                </nav>
            </div>

            <div>
                <p class="sidebar-section-title">Contenido</p>
                <nav class="nav">
                    <a href="index.php" class="active"><span class="nav-icon">&#128196;</span><span>P&aacute;ginas del sitio</span></a>
                </nav>
            </div>
        </aside>

        <main class="main">
            <div class="topbar">
                <div>
                    <h1 class="page-title">Editar contenido</h1>
                    <p class="page-subtitle">
                        <?php if ($page): ?>
                            Gestiona el cuerpo editable de <span class="muted"><?php echo htmlspecialchars((string) ($page["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></span> seg&uacute;n su plantilla actual.
                        <?php else: ?>
                            No fue posible cargar la p&aacute;gina solicitada.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="topbar-actions">
                    <?php if ($page): ?>
                        <a href="edit.php?id=<?php echo (int) $page["id"]; ?>" class="btn btn-outline">Configuraci&oacute;n base</a>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-outline">Volver a p&aacute;ginas</a>
                    <form action="../logout.php" method="post" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                        <button type="submit" class="btn btn-logout">Cerrar sesi&oacute;n</button>
                    </form>
                </div>
            </div>

            <?php if ($successMessage !== ""): ?>
                <div class="flash-message flash-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, "UTF-8"); ?></div>
            <?php endif; ?>

            <?php if ($errors !== []): ?>
                <div class="flash-message flash-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($page && $schema): ?>
                <section class="card">
                    <h2>Contenido de la plantilla <?php echo htmlspecialchars((string) ($schema["template_name"] ?? $templateKey), ENT_QUOTES, "UTF-8"); ?></h2>
                    <p>Los bloques repetibles tienen cantidad fija definida por la plantilla. Aqu&iacute; solo puedes editar valores y mostrar u ocultar cada elemento.</p>

                    <form action="content.php?id=<?php echo (int) $page["id"]; ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">

                        <div class="section-block">
                            <h3>Campos simples</h3>
                            <div class="field-grid">
                                <?php foreach ($schema["simple_fields"] as $fieldConfig): ?>
                                    <?php
                                    $fieldKey = (string) $fieldConfig["field_key"];
                                    $fieldData = $contentData["simple_fields"][$fieldKey] ?? null;
                                    $fieldType = (string) ($fieldConfig["field_type"] ?? "text");
                                    $fieldValue = (string) ($fieldData["field_value"] ?? "");
                                    $fieldVisible = (int) ($fieldData["is_visible"] ?? 1) === 1;
                                    $isTextarea = $fieldType === "textarea";
                                    $isImage = $fieldType === "image";
                                    ?>
                                    <div class="field-group <?php echo $isTextarea ? "field-group-full" : ""; ?>">
                                        <label class="field-label" for="simple_<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>"><?php echo htmlspecialchars((string) ($fieldConfig["label"] ?? $fieldKey), ENT_QUOTES, "UTF-8"); ?></label>

                                        <?php if ($isTextarea): ?>
                                            <textarea class="form-textarea" id="simple_<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>][value]"><?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?></textarea>
                                        <?php else: ?>
                                            <input class="form-input" type="text" id="simple_<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                        <?php endif; ?>

                                        <label class="toggle-row">
                                            <input type="checkbox" name="simple_fields[<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1"<?php echo $fieldVisible ? " checked" : ""; ?>>
                                            <span>Mostrar este campo</span>
                                        </label>

                                        <?php if ($isImage && $fieldValue !== ""): ?>
                                            <img src="../../<?php echo htmlspecialchars(ltrim($fieldValue, "/"), ENT_QUOTES, "UTF-8"); ?>" alt="" class="preview-image">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php foreach ($schema["repeaters"] as $repeaterConfig): ?>
                            <?php
                            $repeaterKey = (string) $repeaterConfig["repeater_key"];
                            $repeaterItems = $contentData["repeaters"][$repeaterKey] ?? [];
                            ?>
                            <div class="section-block">
                                <h3><?php echo htmlspecialchars((string) ($repeaterConfig["label"] ?? $repeaterKey), ENT_QUOTES, "UTF-8"); ?></h3>

                                <?php foreach ($repeaterConfig["items"] as $itemConfig): ?>
                                    <?php
                                    $itemIndex = (int) $itemConfig["item_index"];
                                    $itemLabel = (string) ($itemConfig["item_label"] ?? ("Item " . ($itemIndex + 1)));
                                    $itemData = $repeaterItems[$itemIndex] ?? ["fields" => [], "is_visible" => 1];
                                    $itemVisible = (int) ($itemData["is_visible"] ?? 1) === 1;
                                    ?>
                                    <div class="card">
                                        <div class="item-title">
                                            <h3><?php echo htmlspecialchars($itemLabel, ENT_QUOTES, "UTF-8"); ?></h3>
                                            <label class="toggle-row">
                                                <input type="checkbox" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][is_visible]" value="1"<?php echo $itemVisible ? " checked" : ""; ?>>
                                                <span>Mostrar este bloque</span>
                                            </label>
                                        </div>

                                        <div class="field-grid">
                                            <?php foreach ($repeaterConfig["fields"] as $fieldConfig): ?>
                                                <?php
                                                $fieldKey = (string) $fieldConfig["field_key"];
                                                $fieldType = (string) ($fieldConfig["field_type"] ?? "text");
                                                $fieldValue = (string) (($itemData["fields"][$fieldKey]["field_value"] ?? ""));
                                                ?>
                                                <div class="field-group">
                                                    <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>"><?php echo htmlspecialchars((string) ($fieldConfig["label"] ?? $fieldKey), ENT_QUOTES, "UTF-8"); ?></label>
                                                    <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">

                                                    <?php if ($fieldType === "image" && $fieldValue !== ""): ?>
                                                        <img src="../../<?php echo htmlspecialchars(ltrim($fieldValue, "/"), ENT_QUOTES, "UTF-8"); ?>" alt="" class="preview-image">
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="actions">
                            <button type="submit" class="btn btn-primary">Guardar contenido</button>
                            <a href="edit.php?id=<?php echo (int) $page["id"]; ?>" class="btn btn-outline">Volver a configuraci&oacute;n base</a>
                        </div>
                    </form>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>