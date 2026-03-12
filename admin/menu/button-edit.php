<?php
require_once "../auth-check.php";
require_once "../../db.php";
require_once "menu-helpers.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

ensureHomeMenuItem($conn);

$buttonId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$isCreateMode = $buttonId <= 0 || ((string) ($_GET["action"] ?? "")) === "create";
$status = (string) ($_GET["status"] ?? "");
$errors = [];
$successMessage = "";

$buttonData = [
    "text" => "",
    "path" => "",
    "target" => "_self",
    "is_active" => 1,
];

if ($status === "created") {
    $successMessage = "El boton principal se creo correctamente.";
} elseif ($status === "updated") {
    $successMessage = "El boton principal se actualizo correctamente.";
}

$existingButton = null;

if (!$isCreateMode) {
    $stmt = $conn->prepare("SELECT id, label, url, target, is_active, is_button FROM menu_items WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $buttonId);
        $stmt->execute();
        $result = $stmt->get_result();
        $existingButton = $result ? $result->fetch_assoc() : null;
        $stmt->close();
    }

    if (!$existingButton || (int) ($existingButton["is_button"] ?? 0) !== 1) {
        $errors[] = "El boton principal solicitado no existe.";
    } else {
        $buttonData = [
            "text" => (string) ($existingButton["label"] ?? ""),
            "path" => (string) ($existingButton["url"] ?? ""),
            "target" => menuTarget((string) ($existingButton["target"] ?? "_self")),
            "is_active" => (int) ($existingButton["is_active"] ?? 1),
        ];
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = (string) ($_POST["csrf_token"] ?? "");

    if (!hash_equals($_SESSION["csrf_token"], $csrfToken)) {
        $errors[] = "No se pudo validar la solicitud. Intenta de nuevo.";
    } else {
        $submittedId = isset($_POST["button_id"]) ? (int) $_POST["button_id"] : 0;
        $isCreateSubmission = ((string) ($_POST["form_mode"] ?? "")) === "create";

        $isCreateMode = $isCreateSubmission;
        $buttonId = $isCreateSubmission ? 0 : $submittedId;
        $existingButton = null;

        if (!$isCreateSubmission) {
            $reloadStmt = $conn->prepare("SELECT id, is_button FROM menu_items WHERE id = ? LIMIT 1");
            if ($reloadStmt) {
                $reloadStmt->bind_param("i", $buttonId);
                $reloadStmt->execute();
                $reloadResult = $reloadStmt->get_result();
                $existingButton = $reloadResult ? $reloadResult->fetch_assoc() : null;
                $reloadStmt->close();
            }

            if (!$existingButton || (int) ($existingButton["is_button"] ?? 0) !== 1) {
                $errors[] = "El boton principal que intentas editar ya no existe.";
            }
        }

        $buttonData = [
            "text" => textValue((string) ($_POST["button_text"] ?? "")),
            "path" => buttonPath((string) ($_POST["button_path"] ?? "")),
            "target" => menuTarget((string) ($_POST["target"] ?? "_self")),
            "is_active" => isset($_POST["is_active"]) ? 1 : 0,
        ];

        if ($buttonData["text"] === "" || $buttonData["path"] === "") {
            $errors[] = "Debes completar el texto y la direccion del boton principal.";
        }

        if ($isCreateMode && countByType($conn, 1) >= 1) {
            $errors[] = "Solo puedes tener un boton principal configurado.";
        }

        if ($errors === []) {
            if ($isCreateMode) {
                $stmt = $conn->prepare("INSERT INTO menu_items (parent_id, label, url, display_order, is_active, is_button, target, created_at, updated_at) VALUES (NULL, ?, ?, 9, ?, 1, ?, NOW(), NOW())");
                if (!$stmt) {
                    $errors[] = "No fue posible crear el boton principal.";
                } else {
                    $stmt->bind_param("ssis", $buttonData["text"], $buttonData["path"], $buttonData["is_active"], $buttonData["target"]);
                }
            } else {
                $stmt = $conn->prepare("UPDATE menu_items SET label = ?, url = ?, is_active = ?, target = ?, updated_at = NOW() WHERE id = ? AND is_button = 1 LIMIT 1");
                if (!$stmt) {
                    $errors[] = "No fue posible actualizar el boton principal.";
                } else {
                    $stmt->bind_param("ssisi", $buttonData["text"], $buttonData["path"], $buttonData["is_active"], $buttonData["target"], $buttonId);
                }
            }

            if ($errors === [] && isset($stmt)) {
                $ok = $stmt->execute();
                $newButtonId = $isCreateMode ? (int) $stmt->insert_id : $buttonId;
                $stmt->close();

                if ($ok) {
                    redirectToMenuButtonEdit($isCreateMode ? "created" : "updated", $newButtonId);
                }

                $errors[] = "No fue posible guardar el boton principal.";
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
    <title><?php echo $isCreateMode ? "Crear boton principal" : "Editar boton principal"; ?></title>
    <style>
        *{box-sizing:border-box}body{font-family:Arial,sans-serif;background:#f4f6f9;margin:0;color:#1f2937}.layout{min-height:100vh;display:flex;align-items:flex-start}.sidebar{width:260px;background:#fff;border-right:1px solid #e5e7eb;padding:24px 18px;display:flex;flex-direction:column;gap:22px;position:sticky;top:0;height:100vh;overflow-y:auto;flex-shrink:0}.brand{padding-bottom:18px;border-bottom:1px solid #e5e7eb}.brand h2{margin:0;font-size:22px;color:#198754}.brand p{margin:8px 0 0;color:#6b7280;font-size:14px;line-height:1.4}.sidebar-section-title{margin:0 0 10px;font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af}.nav{display:flex;flex-direction:column;gap:8px}.nav a{display:flex;align-items:center;gap:10px;text-decoration:none;color:#374151;padding:11px 12px;border-radius:10px;transition:background .2s ease,color .2s ease}.nav a:hover{background:#eef8f2;color:#198754}.nav a.active{background:#e9f7ef;color:#198754;font-weight:bold}.nav-icon{width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center}.main{flex:1;padding:32px;min-width:0}.topbar{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:24px}.page-title{margin:0;font-size:34px;line-height:1.1}.page-subtitle{margin:10px 0 0;font-size:16px;color:#6b7280;max-width:760px}.topbar-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.btn{display:inline-block;padding:10px 16px;border-radius:10px;text-decoration:none;border:0;cursor:pointer;font-size:14px;transition:background .2s ease,transform .2s ease}.btn:hover{transform:translateY(-1px)}.btn-outline{background:#fff;color:#198754;border:1px solid #cfe7d8}.btn-outline:hover{background:#eef8f2}.btn-logout{background:#dc3545;color:#fff}.btn-logout:hover{background:#bb2d3b}.btn-primary{background:#198754;color:#fff}.btn-primary:hover{background:#157347}.card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:24px;box-shadow:0 8px 24px rgba(0,0,0,.04);margin-bottom:18px}.card h2{margin:0 0 10px;font-size:22px}.card p{margin:0 0 18px;line-height:1.6;color:#6b7280}.alert{border-radius:12px;padding:14px 16px;margin-bottom:18px;font-size:14px}.alert-success{background:#e9f7ef;color:#146c43;border:1px solid #cfe7d8}.alert-error{background:#f8d7da;color:#842029;border:1px solid #f1b0b7}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.field-full{grid-column:1 / -1}.form-group{margin:0}label{display:block;margin-bottom:8px;font-weight:bold;color:#374151}input[type=text],select{width:100%;height:44px;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;font-size:14px;outline:none;background:#fff}.checkbox-row{display:flex;align-items:center;gap:10px;min-height:44px}.checkbox-row input{margin:0}.helper{font-size:13px;color:#6b7280;margin-top:8px}.actions-row{display:flex;gap:10px;flex-wrap:wrap;margin-top:20px}@media (max-width:991px){.layout{flex-direction:column}.sidebar{width:100%;border-right:0;border-bottom:1px solid #e5e7eb;padding:18px;position:static;top:auto;height:auto;overflow-y:visible}.main{padding:22px}.topbar{flex-direction:column;align-items:stretch}.topbar-actions{justify-content:flex-start}}@media (max-width:720px){.form-grid{grid-template-columns:1fr}.main{padding:16px}.sidebar{padding:16px}.card{padding:18px}.page-title{font-size:28px}}
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
                    <h1 class="page-title"><?php echo $isCreateMode ? "Crear boton principal" : "Editar boton principal"; ?></h1>
                    <p class="page-subtitle">Configura el boton destacado del encabezado sin mezclar su edicion con el listado principal del menu.</p>
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

            <section class="card">
                <h2><?php echo $isCreateMode ? "Datos del boton principal" : "Editar boton principal"; ?></h2>
                <p>Este boton sirve para destacar una accion importante del sitio, como una cita, contacto o acceso directo.</p>

                <form action="button-edit.php<?php echo !$isCreateMode ? "?id=" . urlencode((string) $buttonId) : "?action=create"; ?>" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                    <input type="hidden" name="form_mode" value="<?php echo $isCreateMode ? "create" : "edit"; ?>">
                    <input type="hidden" name="button_id" value="<?php echo !$isCreateMode ? (int) $buttonId : 0; ?>">

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="button_text">Texto del bot&oacute;n</label>
                            <input type="text" id="button_text" name="button_text" maxlength="255" required value="<?php echo htmlspecialchars($buttonData["text"], ENT_QUOTES, "UTF-8"); ?>">
                        </div>

                        <div class="form-group">
                            <label for="button_path">Direcci&oacute;n del bot&oacute;n</label>
                            <input type="text" id="button_path" name="button_path" maxlength="255" required value="<?php echo htmlspecialchars($buttonData["path"], ENT_QUOTES, "UTF-8"); ?>">
                            <div class="helper">Aqu&iacute; puedes escribir un enlace completo real, por ejemplo: https://midominio.com/agendar o https://wa.me/593...</div>
                        </div>

                        <div class="form-group">
                            <label for="target">Abrir enlace</label>
                            <select id="target" name="target">
                                <option value="_self" <?php echo $buttonData["target"] === "_self" ? "selected" : ""; ?>>En la misma pesta&ntilde;a</option>
                                <option value="_blank" <?php echo $buttonData["target"] === "_blank" ? "selected" : ""; ?>>En una pesta&ntilde;a nueva</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Mostrar bot&oacute;n principal</label>
                            <div class="checkbox-row">
                                <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo (int) $buttonData["is_active"] === 1 ? "checked" : ""; ?>>
                                <label for="is_active" style="margin:0;font-weight:normal;">S&iacute;, mostrar</label>
                            </div>
                        </div>
                    </div>

                    <div class="actions-row">
                        <button type="submit" class="btn btn-primary"><?php echo $isCreateMode ? "Crear boton principal" : "Guardar cambios"; ?></button>
                        <a href="index.php" class="btn btn-outline">Cancelar</a>
                    </div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
