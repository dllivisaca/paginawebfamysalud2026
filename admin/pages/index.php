<?php
require_once "../auth-check.php";
require_once "../../db.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$expectedPages = [
    [
        "name" => "Inicio",
        "expected_slug" => "home",
        "page_keys" => ["home"],
        "slug_aliases" => ["home", "inicio"],
    ],
    [
        "name" => "Nosotros",
        "expected_slug" => "nosotros",
        "page_keys" => ["about"],
        "slug_aliases" => ["nosotros"],
    ],
    [
        "name" => "Especialidades",
        "expected_slug" => "especialidades",
        "page_keys" => ["specialties", "departments"],
        "slug_aliases" => ["especialidades"],
    ],
    [
        "name" => "Servicios",
        "expected_slug" => "servicios",
        "page_keys" => ["services"],
        "slug_aliases" => ["servicios"],
    ],
    [
        "name" => "Salud Ocupacional",
        "expected_slug" => "salud-ocupacional",
        "page_keys" => ["occupational_health", "salud_ocupacional"],
        "slug_aliases" => ["salud-ocupacional"],
    ],
    [
        "name" => "Doctores",
        "expected_slug" => "doctores",
        "page_keys" => ["doctors"],
        "slug_aliases" => ["doctores"],
    ],
    [
        "name" => "Promociones",
        "expected_slug" => "promociones",
        "page_keys" => ["promotions"],
        "slug_aliases" => ["promociones"],
    ],
    [
        "name" => "Contacto",
        "expected_slug" => "contacto",
        "page_keys" => ["contact"],
        "slug_aliases" => ["contacto"],
    ],
];

$sitePages = [];
$sitePagesByKey = [];
$sitePagesBySlug = [];
$result = $conn->query("SELECT page_key, title, slug, template_key, is_active FROM site_pages ORDER BY id ASC");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sitePages[] = $row;
        $pageKey = (string) ($row["page_key"] ?? "");
        $slug = (string) ($row["slug"] ?? "");

        if ($pageKey !== "") {
            $sitePagesByKey[$pageKey] = $row;
        }

        if ($slug !== "") {
            $sitePagesBySlug[$slug] = $row;
        }
    }
}

$pagesOverview = [];

foreach ($expectedPages as $expectedPage) {
    $matchedPage = null;

    foreach ($expectedPage["page_keys"] as $pageKey) {
        if (isset($sitePagesByKey[$pageKey])) {
            $matchedPage = $sitePagesByKey[$pageKey];
            break;
        }
    }

    if ($matchedPage === null) {
        foreach ($expectedPage["slug_aliases"] as $slugAlias) {
            if (isset($sitePagesBySlug[$slugAlias])) {
                $matchedPage = $sitePagesBySlug[$slugAlias];
                break;
            }
        }
    }

    $pagesOverview[] = [
        "name" => $expectedPage["name"],
        "slug" => $matchedPage["slug"] ?? $expectedPage["expected_slug"],
        "template_key" => $matchedPage["template_key"] ?? "Pendiente",
        "status" => $matchedPage !== null ? "Configurada" : "Pendiente",
        "is_configured" => $matchedPage !== null,
    ];
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

        .status-configured {
            background: #e9f7ef;
            color: #146c43;
        }

        .status-pending {
            background: #fff4db;
            color: #996b00;
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
                    <a href="../dashboard.php" class="btn btn-outline">Volver al panel</a>

                    <form action="../logout.php" method="post" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                        <button type="submit" class="btn btn-logout">Cerrar sesi&oacute;n</button>
                    </form>
                </div>
            </div>

            <section class="card">
                <h2>Resumen</h2>
                <p>Vista inicial de las p&aacute;ginas objetivo del sitio y su estado actual dentro de la base de datos.</p>
                <div class="summary-grid">
                    <div class="summary-box">
                        <div class="summary-label">P&aacute;ginas configuradas</div>
                        <div class="summary-value"><?php echo count(array_filter($pagesOverview, static fn(array $page): bool => $page["is_configured"])); ?></div>
                    </div>
                    <div class="summary-box">
                        <div class="summary-label">P&aacute;ginas pendientes</div>
                        <div class="summary-value"><?php echo count(array_filter($pagesOverview, static fn(array $page): bool => !$page["is_configured"])); ?></div>
                    </div>
                </div>
            </section>

            <section class="card">
                <h2>Estado de p&aacute;ginas</h2>
                <p>Las p&aacute;ginas ya existentes en <span class="muted">site_pages</span> aparecen como configuradas. Las dem&aacute;s quedan marcadas como pendientes para su siguiente fase de configuraci&oacute;n.</p>

                <div class="pages-table-wrapper">
                    <table class="pages-table">
                        <thead>
                            <tr>
                                <th>Nombre visible</th>
                                <th>Slug</th>
                                <th>Template actual</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagesOverview as $pageItem): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pageItem["name"], ENT_QUOTES, "UTF-8"); ?></td>
                                    <td><?php echo htmlspecialchars($pageItem["slug"], ENT_QUOTES, "UTF-8"); ?></td>
                                    <td><?php echo htmlspecialchars($pageItem["template_key"], ENT_QUOTES, "UTF-8"); ?></td>
                                    <td>
                                        <span class="status-pill <?php echo $pageItem["is_configured"] ? "status-configured" : "status-pending"; ?>">
                                            <?php echo htmlspecialchars($pageItem["status"], ENT_QUOTES, "UTF-8"); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
