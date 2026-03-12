<?php
require_once "../auth-check.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$pageId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$isCreateMode = $pageId <= 0 || ((string) ($_GET["action"] ?? "")) === "create";
$status = (string) ($_GET["status"] ?? "");
$errors = [];
$successMessage = "";

$pageData = [
    "title" => "",
    "page_key" => "",
    "slug" => "",
    "template_key" => "",
    "is_active" => 1,
    "seo_title" => "",
    "seo_description" => "",
    "seo_keywords" => "",
    "h1_title" => "",
    "meta_robots" => "index,follow",
    "og_title" => "",
    "og_description" => "",
    "canonical_url" => "",
];

if ($status === "created") {
    $successMessage = "La pagina se creo correctamente.";
} elseif ($status === "updated") {
    $successMessage = "La pagina se actualizo correctamente.";
}

$existingPage = null;

if (!$isCreateMode) {
    $selectStmt = $conn->prepare(
        "SELECT id, title, page_key, slug, template_key, is_active, seo_title, seo_description,
                seo_keywords, h1_title, meta_robots, og_title, og_description, canonical_url
         FROM site_pages
         WHERE id = ?
         LIMIT 1"
    );

    if ($selectStmt) {
        $selectStmt->bind_param("i", $pageId);
        $selectStmt->execute();
        $selectResult = $selectStmt->get_result();
        $existingPage = $selectResult ? $selectResult->fetch_assoc() : null;
        $selectStmt->close();
    }

    if ($existingPage) {
        $pageData = [
            "title" => (string) ($existingPage["title"] ?? ""),
            "page_key" => (string) ($existingPage["page_key"] ?? ""),
            "slug" => (string) ($existingPage["slug"] ?? ""),
            "template_key" => (string) ($existingPage["template_key"] ?? ""),
            "is_active" => (int) ($existingPage["is_active"] ?? 0),
            "seo_title" => (string) ($existingPage["seo_title"] ?? ""),
            "seo_description" => (string) ($existingPage["seo_description"] ?? ""),
            "seo_keywords" => (string) ($existingPage["seo_keywords"] ?? ""),
            "h1_title" => (string) ($existingPage["h1_title"] ?? ""),
            "meta_robots" => (string) ($existingPage["meta_robots"] ?? "index,follow"),
            "og_title" => (string) ($existingPage["og_title"] ?? ""),
            "og_description" => (string) ($existingPage["og_description"] ?? ""),
            "canonical_url" => (string) ($existingPage["canonical_url"] ?? ""),
        ];
    } else {
        $errors[] = "La pagina solicitada no existe.";
    }
}

$isHomePage = !$isCreateMode && $existingPage && ((string) ($existingPage["page_key"] ?? "")) === "home";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = (string) ($_POST["csrf_token"] ?? "");

    if (!hash_equals($_SESSION["csrf_token"], $csrfToken)) {
        $errors[] = "No se pudo validar la solicitud. Intenta de nuevo.";
    } else {
        $submittedId = isset($_POST["page_id"]) ? (int) $_POST["page_id"] : 0;
        $isCreateSubmission = ((string) ($_POST["form_mode"] ?? "")) === "create";

        if ($isCreateSubmission) {
            $isCreateMode = true;
            $pageId = 0;
            $existingPage = null;
            $isHomePage = false;
        } else {
            $pageId = $submittedId;
            $isCreateMode = false;

            $reloadStmt = $conn->prepare(
                "SELECT id, title, page_key, slug, template_key, is_active, seo_title, seo_description,
                        seo_keywords, h1_title, meta_robots, og_title, og_description, canonical_url
                 FROM site_pages
                 WHERE id = ?
                 LIMIT 1"
            );

            if ($reloadStmt) {
                $reloadStmt->bind_param("i", $pageId);
                $reloadStmt->execute();
                $reloadResult = $reloadStmt->get_result();
                $existingPage = $reloadResult ? $reloadResult->fetch_assoc() : null;
                $reloadStmt->close();
            }

            if (!$existingPage) {
                $errors[] = "La pagina que intentas editar ya no existe.";
            }

            $isHomePage = $existingPage && ((string) ($existingPage["page_key"] ?? "")) === "home";
        }

        $pageData = [
            "title" => trim((string) ($_POST["title"] ?? "")),
            "page_key" => trim((string) ($_POST["page_key"] ?? "")),
            "slug" => trim((string) ($_POST["slug"] ?? "")),
            "template_key" => trim((string) ($_POST["template_key"] ?? "")),
            "is_active" => isset($_POST["is_active"]) ? (int) $_POST["is_active"] : 0,
            "seo_title" => trim((string) ($_POST["seo_title"] ?? "")),
            "seo_description" => trim((string) ($_POST["seo_description"] ?? "")),
            "seo_keywords" => trim((string) ($_POST["seo_keywords"] ?? "")),
            "h1_title" => trim((string) ($_POST["h1_title"] ?? "")),
            "meta_robots" => trim((string) ($_POST["meta_robots"] ?? "")),
            "og_title" => trim((string) ($_POST["og_title"] ?? "")),
            "og_description" => trim((string) ($_POST["og_description"] ?? "")),
            "canonical_url" => trim((string) ($_POST["canonical_url"] ?? "")),
        ];

        if (!$isCreateMode && $existingPage) {
            $pageData["page_key"] = (string) ($existingPage["page_key"] ?? "home");
        }

        if ($isHomePage && $existingPage) {
            $pageData["slug"] = (string) ($existingPage["slug"] ?? "");
            $pageData["is_active"] = 1;
        }

        if ($pageData["meta_robots"] === "") {
            $pageData["meta_robots"] = "index,follow";
        }

        if ($pageData["title"] === "") {
            $errors[] = "El titulo visible es obligatorio.";
        }

        if ($pageData["page_key"] === "") {
            $errors[] = "La clave interna es obligatoria.";
        }

        if ($pageData["slug"] === "") {
            $errors[] = "La URL amigable es obligatoria.";
        }

        if ($pageData["template_key"] === "") {
            $errors[] = "La plantilla es obligatoria.";
        }

        if ($pageData["is_active"] !== 0 && $pageData["is_active"] !== 1) {
            $errors[] = "El estado seleccionado no es valido.";
        }

        if (!$isCreateMode && $isHomePage && $pageData["is_active"] !== 1) {
            $errors[] = "La pagina Inicio esta protegida y debe permanecer activa.";
        }

        if ($errors === []) {
            $excludeId = $isCreateMode ? 0 : $pageId;

            $pageKeyStmt = $conn->prepare("SELECT id FROM site_pages WHERE page_key = ? AND id <> ? LIMIT 1");

            if ($pageKeyStmt) {
                $pageKeyStmt->bind_param("si", $pageData["page_key"], $excludeId);
                $pageKeyStmt->execute();
                $pageKeyResult = $pageKeyStmt->get_result();
                $pageKeyExists = $pageKeyResult && $pageKeyResult->fetch_assoc();
                $pageKeyStmt->close();

                if ($pageKeyExists) {
                    $errors[] = "La clave interna ya esta en uso por otra pagina.";
                }
            }

            $slugStmt = $conn->prepare("SELECT id FROM site_pages WHERE slug = ? AND id <> ? LIMIT 1");

            if ($slugStmt) {
                $slugStmt->bind_param("si", $pageData["slug"], $excludeId);
                $slugStmt->execute();
                $slugResult = $slugStmt->get_result();
                $slugExists = $slugResult && $slugResult->fetch_assoc();
                $slugStmt->close();

                if ($slugExists) {
                    $errors[] = "La URL amigable ya esta en uso por otra pagina.";
                }
            }
        }

        if ($errors === []) {
            if ($isCreateMode) {
                $insertStmt = $conn->prepare(
                    "INSERT INTO site_pages
                        (title, page_key, slug, template_key, is_active, seo_title, seo_description, seo_keywords,
                         h1_title, meta_robots, og_title, og_description, canonical_url)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                if ($insertStmt) {
                    $insertStmt->bind_param(
                        "ssssissssssss",
                        $pageData["title"],
                        $pageData["page_key"],
                        $pageData["slug"],
                        $pageData["template_key"],
                        $pageData["is_active"],
                        $pageData["seo_title"],
                        $pageData["seo_description"],
                        $pageData["seo_keywords"],
                        $pageData["h1_title"],
                        $pageData["meta_robots"],
                        $pageData["og_title"],
                        $pageData["og_description"],
                        $pageData["canonical_url"]
                    );

                    if ($insertStmt->execute()) {
                        $newPageId = (int) $insertStmt->insert_id;
                        $insertStmt->close();
                        header("Location: edit.php?id=" . $newPageId . "&status=created");
                        exit;
                    }

                    $insertStmt->close();
                }

                $errors[] = "No fue posible crear la pagina. Revisa los datos e intenta de nuevo.";
            } else {
                $updateStmt = $conn->prepare(
                    "UPDATE site_pages
                     SET title = ?, page_key = ?, slug = ?, template_key = ?, is_active = ?, seo_title = ?,
                         seo_description = ?, seo_keywords = ?, h1_title = ?, meta_robots = ?, og_title = ?,
                         og_description = ?, canonical_url = ?
                     WHERE id = ?
                     LIMIT 1"
                );

                if ($updateStmt) {
                    $updateStmt->bind_param(
                        "ssssissssssssi",
                        $pageData["title"],
                        $pageData["page_key"],
                        $pageData["slug"],
                        $pageData["template_key"],
                        $pageData["is_active"],
                        $pageData["seo_title"],
                        $pageData["seo_description"],
                        $pageData["seo_keywords"],
                        $pageData["h1_title"],
                        $pageData["meta_robots"],
                        $pageData["og_title"],
                        $pageData["og_description"],
                        $pageData["canonical_url"],
                        $pageId
                    );

                    if ($updateStmt->execute()) {
                        $updateStmt->close();
                        header("Location: edit.php?id=" . $pageId . "&status=updated");
                        exit;
                    }

                    $updateStmt->close();
                }

                $errors[] = "No fue posible guardar los cambios de la pagina.";
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
    <title><?php echo $isCreateMode ? "Crear nueva pagina" : "Editar pagina"; ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
            color: #1f2937;
        }

        .layout {
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
        }

        .sidebar {
            width: 260px;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            gap: 22px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            flex-shrink: 0;
        }

        .brand {
            padding-bottom: 18px;
            border-bottom: 1px solid #e5e7eb;
        }

        .brand h2 {
            margin: 0;
            font-size: 22px;
            color: #198754;
        }

        .brand p {
            margin: 8px 0 0;
            color: #6b7280;
            font-size: 14px;
            line-height: 1.4;
        }

        .sidebar-section-title {
            margin: 0 0 10px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #9ca3af;
        }

        .nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #374151;
            padding: 11px 12px;
            border-radius: 10px;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .nav a:hover {
            background: #eef8f2;
            color: #198754;
        }

        .nav a.active {
            background: #e9f7ef;
            color: #198754;
            font-weight: bold;
        }

        .nav-icon {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .main {
            flex: 1;
            padding: 32px;
            min-width: 0;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 24px;
        }

        .page-title {
            margin: 0;
            font-size: 34px;
            line-height: 1.1;
        }

        .page-subtitle {
            margin: 10px 0 0;
            font-size: 16px;
            color: #6b7280;
            max-width: 760px;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
            margin-bottom: 18px;
        }

        .card h2 {
            margin: 0 0 10px;
            font-size: 22px;
        }

        .card p {
            margin: 0 0 18px;
            color: #6b7280;
            line-height: 1.6;
        }

        .flash-message {
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 18px;
            border: 1px solid #e5e7eb;
        }

        .flash-success {
            background: #e9f7ef;
            border-color: #cfe7d8;
            color: #146c43;
        }

        .flash-error {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
        }

        .btn {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 10px;
            text-decoration: none;
            border: 0;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: #198754;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #157347;
        }

        .btn-outline {
            background: #ffffff;
            color: #198754;
            border: 1px solid #cfe7d8;
        }

        .btn-outline:hover {
            background: #eef8f2;
        }

        .btn-logout {
            background: #dc3545;
            color: #ffffff;
        }

        .btn-logout:hover {
            background: #bb2d3b;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px 20px;
        }

        .field-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .field-group-full {
            grid-column: 1 / -1;
        }

        .field-label {
            font-size: 14px;
            font-weight: bold;
            color: #374151;
        }

        .field-help {
            margin: 0;
            font-size: 13px;
            color: #6b7280;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 14px;
            padding: 12px 14px;
            font-family: inherit;
            color: #1f2937;
            background: #ffffff;
        }

        .form-textarea {
            min-height: 110px;
            resize: vertical;
        }

        .readonly-box {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 12px 14px;
            background: #f9fafb;
            color: #4b5563;
            font-size: 14px;
        }

        .protected-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #eef2ff;
            color: #4338ca;
            font-size: 12px;
            font-weight: bold;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .muted {
            color: #6b7280;
        }

        @media (max-width: 991px) {
            .layout {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                border-right: 0;
                border-bottom: 1px solid #e5e7eb;
                padding: 18px;
                position: static;
                top: auto;
                height: auto;
                overflow-y: visible;
            }

            .main {
                padding: 22px;
            }

            .topbar {
                flex-direction: column;
                align-items: stretch;
            }

            .topbar-actions {
                justify-content: flex-start;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .sidebar {
                padding: 16px;
            }

            .main {
                padding: 16px;
            }

            .card {
                padding: 18px;
                border-radius: 14px;
            }

            .page-title {
                font-size: 26px;
            }
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
                    <a href="../dashboard.php">
                        <span class="nav-icon">&#127968;</span>
                        <span>Panel de inicio</span>
                    </a>

                    <a href="../menu/index.php">
                        <span class="nav-icon">&#128203;</span>
                        <span>Men&uacute; de navegaci&oacute;n</span>
                    </a>
                </nav>
            </div>

            <div>
                <p class="sidebar-section-title">Contenido</p>
                <nav class="nav">
                    <a href="index.php" class="active">
                        <span class="nav-icon">&#128196;</span>
                        <span>P&aacute;ginas del sitio</span>
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
                    <h1 class="page-title"><?php echo $isCreateMode ? "Crear nueva p&aacute;gina" : "Editar p&aacute;gina"; ?></h1>
                    <p class="page-subtitle">
                        <?php if ($isCreateMode): ?>
                            Registra una nueva p&aacute;gina real en <span class="muted">site_pages</span> con su configuraci&oacute;n base.
                        <?php else: ?>
                            Actualiza la configuraci&oacute;n principal de la p&aacute;gina seleccionada dentro de <span class="muted">site_pages</span>.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="topbar-actions">
                    <a href="index.php" class="btn btn-outline">Volver a p&aacute;ginas</a>

                    <form action="../logout.php" method="post" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                        <button type="submit" class="btn btn-logout">Cerrar sesi&oacute;n</button>
                    </form>
                </div>
            </div>

            <?php if ($successMessage !== ""): ?>
                <div class="flash-message flash-success">
                    <?php echo htmlspecialchars($successMessage, ENT_QUOTES, "UTF-8"); ?>
                </div>
            <?php endif; ?>

            <?php if ($errors !== []): ?>
                <div class="flash-message flash-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!$isCreateMode && !$existingPage): ?>
                <section class="card">
                    <h2>P&aacute;gina no disponible</h2>
                    <p>No fue posible cargar la p&aacute;gina solicitada. Regresa al listado para continuar.</p>
                    <div class="actions">
                        <a href="index.php" class="btn btn-primary">Volver al listado</a>
                    </div>
                </section>
            <?php else: ?>
                <section class="card">
                    <h2><?php echo $isCreateMode ? "Datos de la nueva p&aacute;gina" : "Datos de la p&aacute;gina"; ?></h2>
                    <p>
                        Completa la informaci&oacute;n base de la p&aacute;gina.
                        <?php if ($isHomePage): ?>
                            <span class="protected-badge">Inicio est&aacute; protegida</span>
                        <?php endif; ?>
                    </p>

                    <form action="edit.php<?php echo !$isCreateMode ? "?id=" . urlencode((string) $pageId) : "?action=create"; ?>" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                        <input type="hidden" name="form_mode" value="<?php echo $isCreateMode ? "create" : "edit"; ?>">
                        <input type="hidden" name="page_id" value="<?php echo htmlspecialchars((string) $pageId, ENT_QUOTES, "UTF-8"); ?>">

                        <div class="form-grid">
                            <div class="field-group">
                                <label class="field-label" for="title">T&iacute;tulo visible</label>
                                <input class="form-input" type="text" id="title" name="title" value="<?php echo htmlspecialchars($pageData["title"], ENT_QUOTES, "UTF-8"); ?>" required>
                            </div>

                            <div class="field-group">
                                <label class="field-label" for="template_key">Plantilla</label>
                                <input class="form-input" type="text" id="template_key" name="template_key" value="<?php echo htmlspecialchars($pageData["template_key"], ENT_QUOTES, "UTF-8"); ?>" required>
                            </div>

                            <div class="field-group">
                                <label class="field-label" for="page_key">Clave interna del sistema</label>
                                <?php if ($isCreateMode): ?>
                                    <input class="form-input" type="text" id="page_key" name="page_key" value="<?php echo htmlspecialchars($pageData["page_key"], ENT_QUOTES, "UTF-8"); ?>" required>
                                    <p class="field-help">Identificador interno &uacute;nico de la p&aacute;gina. Despu&eacute;s de crearla ya no se podr&aacute; editar.</p>
                                <?php else: ?>
                                    <div class="readonly-box"><?php echo htmlspecialchars($pageData["page_key"], ENT_QUOTES, "UTF-8"); ?></div>
                                    <input type="hidden" name="page_key" value="<?php echo htmlspecialchars($pageData["page_key"], ENT_QUOTES, "UTF-8"); ?>">
                                    <p class="field-help">Esta clave interna pertenece al sistema y no se puede modificar.</p>
                                <?php endif; ?>
                            </div>

                            <div class="field-group">
                                <label class="field-label" for="slug">URL amigable</label>
                                <?php if ($isHomePage): ?>
                                    <div class="readonly-box"><?php echo htmlspecialchars($pageData["slug"], ENT_QUOTES, "UTF-8"); ?></div>
                                    <input type="hidden" name="slug" value="<?php echo htmlspecialchars($pageData["slug"], ENT_QUOTES, "UTF-8"); ?>">
                                    <p class="field-help">La URL amigable de Inicio queda bloqueada por seguridad.</p>
                                <?php else: ?>
                                    <input class="form-input" type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($pageData["slug"], ENT_QUOTES, "UTF-8"); ?>" required>
                                <?php endif; ?>
                            </div>

                            <div class="field-group">
                                <label class="field-label" for="is_active">Estado</label>
                                <?php if ($isHomePage): ?>
                                    <div class="readonly-box">Activa</div>
                                    <input type="hidden" name="is_active" value="1">
                                    <p class="field-help">La p&aacute;gina Inicio siempre permanece activa.</p>
                                <?php else: ?>
                                    <select class="form-select" id="is_active" name="is_active">
                                        <option value="1"<?php echo (int) $pageData["is_active"] === 1 ? " selected" : ""; ?>>Activa</option>
                                        <option value="0"<?php echo (int) $pageData["is_active"] === 0 ? " selected" : ""; ?>>Inactiva</option>
                                    </select>
                                <?php endif; ?>
                            </div>

                            <div class="field-group">
                                <label class="field-label" for="meta_robots">Instrucciones para robots</label>
                                <input class="form-input" type="text" id="meta_robots" name="meta_robots" value="<?php echo htmlspecialchars($pageData["meta_robots"], ENT_QUOTES, "UTF-8"); ?>">
                                <p class="field-help">Si lo dejas vac&iacute;o, se guardar&aacute; como <span class="muted">index,follow</span>.</p>
                            </div>

                            <div class="field-group field-group-full">
                                <label class="field-label" for="h1_title">T&iacute;tulo principal visible</label>
                                <input class="form-input" type="text" id="h1_title" name="h1_title" value="<?php echo htmlspecialchars($pageData["h1_title"], ENT_QUOTES, "UTF-8"); ?>">
                            </div>

                            <div class="field-group field-group-full">
                                <label class="field-label" for="seo_title">T&iacute;tulo SEO</label>
                                <input class="form-input" type="text" id="seo_title" name="seo_title" value="<?php echo htmlspecialchars($pageData["seo_title"], ENT_QUOTES, "UTF-8"); ?>">
                            </div>

                            <div class="field-group field-group-full">
                                <label class="field-label" for="seo_description">Descripci&oacute;n SEO</label>
                                <textarea class="form-textarea" id="seo_description" name="seo_description"><?php echo htmlspecialchars($pageData["seo_description"], ENT_QUOTES, "UTF-8"); ?></textarea>
                            </div>

                            <div class="field-group field-group-full">
                                <label class="field-label" for="seo_keywords">Palabras clave SEO</label>
                                <textarea class="form-textarea" id="seo_keywords" name="seo_keywords"><?php echo htmlspecialchars($pageData["seo_keywords"], ENT_QUOTES, "UTF-8"); ?></textarea>
                            </div>

                            <div class="field-group field-group-full">
                                <label class="field-label" for="og_title">T&iacute;tulo Open Graph</label>
                                <input class="form-input" type="text" id="og_title" name="og_title" value="<?php echo htmlspecialchars($pageData["og_title"], ENT_QUOTES, "UTF-8"); ?>">
                            </div>

                            <div class="field-group field-group-full">
                                <label class="field-label" for="og_description">Descripci&oacute;n Open Graph</label>
                                <textarea class="form-textarea" id="og_description" name="og_description"><?php echo htmlspecialchars($pageData["og_description"], ENT_QUOTES, "UTF-8"); ?></textarea>
                            </div>

                            <div class="field-group field-group-full">
                                <label class="field-label" for="canonical_url">URL can&oacute;nica</label>
                                <input class="form-input" type="text" id="canonical_url" name="canonical_url" value="<?php echo htmlspecialchars($pageData["canonical_url"], ENT_QUOTES, "UTF-8"); ?>">
                            </div>
                        </div>

                        <div class="actions">
                            <button type="submit" class="btn btn-primary"><?php echo $isCreateMode ? "Crear p&aacute;gina" : "Guardar cambios"; ?></button>
                            <a href="index.php" class="btn btn-outline">Cancelar</a>
                        </div>
                    </form>
                </section>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
