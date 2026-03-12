<?php
require_once "../auth-check.php";
require_once "../../db.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function redirectToPagesIndex(string $status, string $message = ""): void
{
    $query = ["status" => $status];

    if ($message !== "") {
        $query["message"] = $message;
    }

    header("Location: index.php?" . http_build_query($query));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = (string) ($_POST["csrf_token"] ?? "");

    if (!hash_equals($_SESSION["csrf_token"], $csrfToken)) {
        redirectToPagesIndex("error", "No se pudo validar la solicitud. Intenta de nuevo.");
    }

    $action = (string) ($_POST["action"] ?? "");
    $pageId = isset($_POST["page_id"]) ? (int) $_POST["page_id"] : 0;

    if ($pageId <= 0) {
        redirectToPagesIndex("error", "La pagina seleccionada no es valida.");
    }

    $pageStmt = $conn->prepare("SELECT id, title, page_key, is_active FROM site_pages WHERE id = ? LIMIT 1");

    if (!$pageStmt) {
        redirectToPagesIndex("error", "No fue posible consultar la pagina seleccionada.");
    }

    $pageStmt->bind_param("i", $pageId);
    $pageStmt->execute();
    $pageResult = $pageStmt->get_result();
    $selectedPage = $pageResult ? $pageResult->fetch_assoc() : null;
    $pageStmt->close();

    if (!$selectedPage) {
        redirectToPagesIndex("error", "La pagina seleccionada ya no existe.");
    }

    $isHomePage = ((string) ($selectedPage["page_key"] ?? "")) === "home";

    if ($action === "toggle_active") {
        $newState = isset($_POST["new_state"]) ? (int) $_POST["new_state"] : -1;

        if ($newState !== 0 && $newState !== 1) {
            redirectToPagesIndex("error", "El estado solicitado no es valido.");
        }

        if ($isHomePage && $newState === 0) {
            redirectToPagesIndex("error", "La pagina Inicio esta protegida y no se puede desactivar.");
        }

        $toggleStmt = $conn->prepare("UPDATE site_pages SET is_active = ? WHERE id = ? LIMIT 1");

        if (!$toggleStmt) {
            redirectToPagesIndex("error", "No fue posible actualizar el estado de la pagina.");
        }

        $toggleStmt->bind_param("ii", $newState, $pageId);
        $toggleStmt->execute();
        $toggleStmt->close();

        redirectToPagesIndex($newState === 1 ? "activated" : "deactivated");
    }

    if ($action === "delete") {
        if ($isHomePage) {
            redirectToPagesIndex("error", "La pagina Inicio esta protegida y no se puede eliminar.");
        }

        $deleteStmt = $conn->prepare("DELETE FROM site_pages WHERE id = ? LIMIT 1");

        if (!$deleteStmt) {
            redirectToPagesIndex("error", "No fue posible eliminar la pagina seleccionada.");
        }

        $deleteStmt->bind_param("i", $pageId);
        $deleteStmt->execute();
        $deleteStmt->close();

        redirectToPagesIndex("deleted");
    }

    redirectToPagesIndex("error", "La accion solicitada no es valida.");
}

$flashStatus = (string) ($_GET["status"] ?? "");
$flashMessage = trim((string) ($_GET["message"] ?? ""));

$sitePages = [];
$activePagesCount = 0;
$result = $conn->query("SELECT id, title, page_key, slug, template_key, is_active FROM site_pages ORDER BY id ASC");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row["id"] = (int) ($row["id"] ?? 0);
        $row["is_active"] = (int) ($row["is_active"] ?? 0);
        $row["is_home_page"] = ((string) ($row["page_key"] ?? "")) === "home";
        $sitePages[] = $row;

        if ($row["is_active"] === 1) {
            $activePagesCount++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P&aacute;ginas del sitio</title>
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

        .btn-outline {
            background: #ffffff;
            color: #198754;
            border: 1px solid #cfe7d8;
        }

        .btn-outline:hover {
            background: #eef8f2;
        }

        .btn-primary {
            background: #198754;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #157347;
        }

        .btn-warning {
            background: #f59e0b;
            color: #ffffff;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-danger {
            background: #dc3545;
            color: #ffffff;
        }

        .btn-danger:hover {
            background: #bb2d3b;
        }

        .btn-logout {
            background: #dc3545;
            color: #ffffff;
        }

        .btn-logout:hover {
            background: #bb2d3b;
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
            line-height: 1.6;
            color: #6b7280;
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

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .summary-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px;
        }

        .summary-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .summary-value {
            font-size: 22px;
            font-weight: bold;
        }

        .pages-table-wrapper {
            overflow-x: auto;
        }

        .pages-table {
            width: 100%;
            border-collapse: collapse;
        }

        .pages-table th,
        .pages-table td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .pages-table th {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-active {
            background: #e9f7ef;
            color: #146c43;
        }

        .status-inactive {
            background: #f3f4f6;
            color: #4b5563;
        }

        .muted {
            color: #6b7280;
        }

        .protected-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #eef2ff;
            color: #4338ca;
            font-size: 12px;
            font-weight: bold;
        }

        .actions-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .inline-form {
            margin: 0;
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

            .summary-grid {
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
                    <h1 class="page-title">P&aacute;ginas del sitio</h1>
                    <p class="page-subtitle">
                        Desde aqu&iacute; se administrar&aacute;n las p&aacute;ginas p&uacute;blicas principales del sitio web y su base de configuraci&oacute;n inicial.
                    </p>
                </div>

                <div class="topbar-actions">
                    <a href="edit.php?action=create" class="btn btn-primary">Crear nueva p&aacute;gina</a>
                    <a href="../dashboard.php" class="btn btn-outline">Volver al panel</a>

                    <form action="../logout.php" method="post" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                        <button type="submit" class="btn btn-logout">Cerrar sesi&oacute;n</button>
                    </form>
                </div>
            </div>

            <?php if ($flashStatus !== "" || $flashMessage !== ""): ?>
                <?php
                $resolvedMessage = $flashMessage;

                if ($resolvedMessage === "") {
                    if ($flashStatus === "activated") {
                        $resolvedMessage = "La pagina se activo correctamente.";
                    } elseif ($flashStatus === "deactivated") {
                        $resolvedMessage = "La pagina se desactivo correctamente.";
                    } elseif ($flashStatus === "deleted") {
                        $resolvedMessage = "La pagina se elimino correctamente.";
                    }
                }
                ?>
                <div class="flash-message <?php echo $flashStatus === "error" ? "flash-error" : "flash-success"; ?>">
                    <?php echo htmlspecialchars($resolvedMessage, ENT_QUOTES, "UTF-8"); ?>
                </div>
            <?php endif; ?>

            <section class="card">
                <h2>Resumen</h2>
                <p>Resumen de las p&aacute;ginas registradas actualmente en la tabla <span class="muted">site_pages</span>.</p>
                <div class="summary-grid">
                    <div class="summary-box">
                        <div class="summary-label">P&aacute;ginas registradas</div>
                        <div class="summary-value"><?php echo count($sitePages); ?></div>
                    </div>
                    <div class="summary-box">
                        <div class="summary-label">P&aacute;ginas activas</div>
                        <div class="summary-value"><?php echo $activePagesCount; ?></div>
                    </div>
                </div>
            </section>

            <section class="card">
                <h2>P&aacute;ginas registradas</h2>
                <p>Aqu&iacute; se muestran &uacute;nicamente las p&aacute;ginas reales existentes en <span class="muted">site_pages</span>.</p>

                <div class="pages-table-wrapper">
                    <table class="pages-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>T&iacute;tulo visible</th>
                                <th>URL amigable</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($sitePages !== []): ?>
                                <?php foreach ($sitePages as $pageItem): ?>
                                    <?php $isActive = (int) $pageItem["is_active"] === 1; ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string) $pageItem["id"], ENT_QUOTES, "UTF-8"); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars((string) ($pageItem["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?>
                                            <?php if ($pageItem["is_home_page"]): ?>
                                                <div class="protected-badge">P&aacute;gina protegida</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars((string) ($pageItem["slug"] ?? ""), ENT_QUOTES, "UTF-8"); ?></td>
                                        <td>
                                            <span class="status-pill <?php echo $isActive ? "status-active" : "status-inactive"; ?>">
                                                <?php echo $isActive ? "Activa" : "Inactiva"; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="actions-group">
                                                <a href="edit.php?id=<?php echo urlencode((string) $pageItem["id"]); ?>" class="btn btn-primary">Editar</a>

                                                <?php if (!$pageItem["is_home_page"]): ?>
                                                    <form action="index.php" method="post" class="inline-form">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                                                        <input type="hidden" name="action" value="toggle_active">
                                                        <input type="hidden" name="page_id" value="<?php echo htmlspecialchars((string) $pageItem["id"], ENT_QUOTES, "UTF-8"); ?>">
                                                        <input type="hidden" name="new_state" value="<?php echo $isActive ? "0" : "1"; ?>">
                                                        <button type="submit" class="btn btn-warning">
                                                            <?php echo $isActive ? "Desactivar" : "Activar"; ?>
                                                        </button>
                                                    </form>

                                                    <form action="index.php" method="post" class="inline-form" onsubmit="return confirm('¿Seguro que deseas eliminar esta página? Esta acción no se puede deshacer.');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="page_id" value="<?php echo htmlspecialchars((string) $pageItem["id"], ENT_QUOTES, "UTF-8"); ?>">
                                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="muted">No hay p&aacute;ginas registradas en la tabla <span class="muted">site_pages</span>.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
