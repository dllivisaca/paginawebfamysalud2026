<?php
require_once "auth-check.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de inicio</title>
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
            font-size: 36px;
            line-height: 1.1;
        }

        .muted {
            color: #6b7280;
        }

        .welcome-text {
            margin: 10px 0 0;
            font-size: 16px;
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

        .hero-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
        }

        .hero-card h2 {
            margin: 0 0 10px;
            font-size: 22px;
        }

        .hero-card p {
            margin: 0;
            color: #6b7280;
            line-height: 1.6;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
        }

        .card h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 20px;
        }

        .card p {
            margin: 0 0 14px;
            line-height: 1.6;
        }

        .card a {
            text-decoration: none;
            color: #198754;
            font-weight: bold;
        }

        .quick-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .quick-link {
            display: block;
            text-decoration: none;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            color: #374151;
            padding: 14px 16px;
            border-radius: 12px;
            transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .quick-link:hover {
            background: #eef8f2;
            border-color: #cfe7d8;
            color: #198754;
        }

        .quick-link strong {
            display: block;
            margin-bottom: 4px;
            font-size: 15px;
        }

        .quick-link span {
            font-size: 13px;
            color: #6b7280;
        }

        .mobile-menu-toggle {
            display: none;
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

            .grid,
            .quick-list {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
                align-items: stretch;
            }

            .topbar-actions {
                justify-content: flex-start;
            }

            .page-title {
                font-size: 30px;
            }
        }

        @media (max-width: 640px) {
            .sidebar {
                padding: 16px;
            }

            .main {
                padding: 16px;
            }

            .hero-card,
            .card {
                padding: 18px;
                border-radius: 14px;
            }

            .page-title {
                font-size: 26px;
            }

            .welcome-text {
                font-size: 15px;
            }

            .nav a {
                padding: 10px 11px;
            }
        }
    </style>
</head>
<body>

    <div class="layout">
        <aside class="sidebar">
            <div class="brand">
                <h2>Panel Admin</h2>
                <p>Gestiona el contenido principal de tu sitio web desde un solo lugar.</p>
            </div>

            <div>
                <p class="sidebar-section-title">Principal</p>
                <nav class="nav">
                    <a href="dashboard.php" class="active">
                        <span class="nav-icon">&#127968;</span>
                        <span>Panel de inicio</span>
                    </a>

                    <a href="menu/index.php">
                        <span class="nav-icon">&#128203;</span>
                        <span>Men&uacute; de navegaci&oacute;n</span>
                    </a>
                </nav>
            </div>

            <div>
                <p class="sidebar-section-title">Contenido</p>
                <nav class="nav">
                    <a href="pages/index.php">
                        <span class="nav-icon">&#128196;</span>
                        <span>P&aacute;ginas del sitio</span>
                    </a>
                </nav>
            </div>

            <div>
                <p class="sidebar-section-title">Configuraci&oacute;n</p>
                <nav class="nav">
                    <a href="settings.php">
                        <span class="nav-icon">&#9881;</span>
                        <span>Configuraci&oacute;n</span>
                    </a>

                    <a href="change-password.php">
                        <span class="nav-icon">&#128274;</span>
                        <span>Cambiar contrase&ntilde;a</span>
                    </a>
                </nav>
            </div>
        </aside>

        <main class="main">
            <div class="topbar">
                <div>
                    <h1 class="page-title">Panel de inicio</h1>
                    <p class="welcome-text muted">
                        Hola, <?php echo htmlspecialchars($_SESSION["admin_name"], ENT_QUOTES, "UTF-8"); ?>
                    </p>
                </div>

                <div class="topbar-actions">
                    <a href="change-password.php" class="btn btn-outline">Cambiar contrase&ntilde;a</a>

                    <form action="logout.php" method="post" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                        <button type="submit" class="btn btn-logout">Cerrar sesi&oacute;n</button>
                    </form>
                </div>
            </div>

            <section class="hero-card">
                <h2>Te damos la bienvenida al panel</h2>
                <p>
                    Desde aqu&iacute; puedes editar las secciones principales del sitio web, mantener actualizado el men&uacute; visible para los usuarios
                    y acceder a opciones de configuraci&oacute;n de tu cuenta.
                </p>
            </section>

            <section class="grid">
                <div class="card">
                    <h3>Men&uacute; de navegaci&oacute;n</h3>
                    <p class="muted">
                        Administra los enlaces del header y el bot&oacute;n destacado del navbar.
                    </p>
                    <a href="menu/index.php">Ir al m&oacute;dulo de navegaci&oacute;n</a>
                </div>

                <div class="card">
                    <h3>Configuraci&oacute;n</h3>
                    <p class="muted">
                        Accede a las opciones de configuraci&oacute;n general de la cuenta y seguridad del panel.
                    </p>
                    <a href="change-password.php">Ir a cambiar contrase&ntilde;a</a>
                </div>
            </section>

            <section class="card" style="margin-top: 18px;">
                <h3>Accesos r&aacute;pidos</h3>
                <p class="muted">
                    Usa estos accesos para entrar m&aacute;s r&aacute;pido a los m&oacute;dulos principales del panel.
                </p>

                <div class="quick-list">
                    <a href="menu/index.php" class="quick-link">
                        <strong>Men&uacute; de navegaci&oacute;n</strong>
                        <span>Edita los enlaces principales y el bot&oacute;n destacado</span>
                    </a>

                    <a href="settings.php" class="quick-link">
                        <strong>Configuraci&oacute;n</strong>
                        <span>Revisa opciones generales del panel</span>
                    </a>

                    <a href="change-password.php" class="quick-link">
                        <strong>Cambiar contrase&ntilde;a</strong>
                        <span>Actualiza tu clave de acceso</span>
                    </a>

                    <a href="logout.php" class="quick-link" onclick="event.preventDefault(); this.closest('main').querySelector('form[action=\'logout.php\'] button').click();">
                        <strong>Cerrar sesi&oacute;n</strong>
                        <span>Salir del panel administrativo</span>
                    </a>
                </div>
            </section>
        </main>
    </div>

</body>
</html>
