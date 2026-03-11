<?php
require_once "auth-check.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$status = $_GET["status"] ?? "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar contrase&ntilde;a</title>
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
        }

        .sidebar {
            width: 260px;
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
            padding: 24px 18px;
            display: flex;
            flex-direction: column;
            gap: 22px;
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

        .muted {
            color: #6b7280;
        }

        .page-subtitle {
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

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
        }

        .card h2 {
            margin: 0 0 10px;
            font-size: 22px;
        }

        .card p {
            margin: 0 0 18px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .password-field {
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #374151;
        }

        input[type="password"],
        input[type="text"] {
            width: 100%;
            height: 44px;
            padding: 12px 94px 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input[type="password"]:focus,
        input[type="text"]:focus {
            border-color: #198754;
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.12);
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            width: auto;
            padding: 6px 10px;
            border: none;
            border-radius: 8px;
            background: #e9f7ef;
            color: #198754;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
        }

        .toggle-password:hover {
            background: #d7ebdf;
        }

        .hint {
            margin-top: 6px;
            font-size: 13px;
            color: #6b7280;
            line-height: 1.5;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 6px;
        }

        .btn-primary {
            background: #198754;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #157347;
        }

        .alert {
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .alert-success {
            background: #e9f7ef;
            color: #146c43;
            border: 1px solid #cfe7d8;
        }

        .alert-error {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f1b0b7;
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

            .card {
                padding: 18px;
                border-radius: 14px;
            }

            .page-title {
                font-size: 26px;
            }

            .page-subtitle {
                font-size: 15px;
            }

            .nav a {
                padding: 10px 11px;
            }

            .actions .btn {
                width: 100%;
                text-align: center;
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
                    <a href="dashboard.php">
                        <span class="nav-icon">&#127968;</span>
                        <span>Panel de inicio</span>
                    </a>

                    <a href="menu/index.php">
                        <span class="nav-icon">&#128203;</span>
                        <span>Men&uacute; del sitio</span>
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

                    <a href="change-password.php" class="active">
                        <span class="nav-icon">&#128274;</span>
                        <span>Cambiar contrase&ntilde;a</span>
                    </a>
                </nav>
            </div>
        </aside>

        <main class="main">
            <div class="topbar">
                <div>
                    <h1 class="page-title">Cambiar contrase&ntilde;a</h1>
                    <p class="page-subtitle muted">
                        Actualiza tu clave de acceso de forma segura.
                    </p>
                </div>

                <div class="topbar-actions">
                    <a href="dashboard.php" class="btn btn-outline">Volver al panel</a>

                    <form action="logout.php" method="post" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                        <button type="submit" class="btn btn-logout">Cerrar sesi&oacute;n</button>
                    </form>
                </div>
            </div>

            <section class="card">
                <h2>Seguridad de la cuenta</h2>
                <p class="muted">
                    Ingresa tu contrase&ntilde;a actual y luego escribe tu nueva contrase&ntilde;a dos veces para confirmarla.
                </p>

                <?php if ($status === "success"): ?>
                    <div class="alert alert-success">La contrase&ntilde;a se actualiz&oacute; correctamente.</div>
                <?php endif; ?>

                <?php if ($status === "invalid_current"): ?>
                    <div class="alert alert-error">La contrase&ntilde;a actual no es correcta.</div>
                <?php endif; ?>

                <?php if ($status === "mismatch"): ?>
                    <div class="alert alert-error">La nueva contrase&ntilde;a y su confirmaci&oacute;n no coinciden.</div>
                <?php endif; ?>

                <?php if ($status === "weak"): ?>
                    <div class="alert alert-error">La nueva contrase&ntilde;a debe tener entre 12 y 128 caracteres e incluir may&uacute;sculas, min&uacute;sculas, n&uacute;meros y s&iacute;mbolos.</div>
                <?php endif; ?>

                <?php if ($status === "reused"): ?>
                    <div class="alert alert-error">La nueva contrase&ntilde;a debe ser distinta a la actual.</div>
                <?php endif; ?>

                <?php if ($status === "error"): ?>
                    <div class="alert alert-error">No se pudo actualizar la contrase&ntilde;a. Intenta nuevamente.</div>
                <?php endif; ?>

                <form action="change-password-process.php" method="post" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">

                    <div class="form-group">
                        <label for="current_password">Contrase&ntilde;a actual</label>
                        <div class="password-field">
                            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                            <button type="button" class="toggle-password" data-target="current_password">Ver</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="new_password">Nueva contrase&ntilde;a</label>
                        <div class="password-field">
                            <input type="password" id="new_password" name="new_password" required autocomplete="new-password" minlength="12" maxlength="128">
                            <button type="button" class="toggle-password" data-target="new_password">Ver</button>
                        </div>
                        <div class="hint">Usa entre 12 y 128 caracteres, incluyendo may&uacute;sculas, min&uacute;sculas, n&uacute;meros y s&iacute;mbolos.</div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirmar nueva contrase&ntilde;a</label>
                        <div class="password-field">
                            <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" minlength="12" maxlength="128">
                            <button type="button" class="toggle-password" data-target="confirm_password">Ver</button>
                        </div>
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn btn-primary">Guardar nueva contrase&ntilde;a</button>
                        <a href="dashboard.php" class="btn btn-outline">Cancelar</a>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <script>
        (function () {
            var toggleButtons = document.querySelectorAll(".toggle-password");

            toggleButtons.forEach(function (button) {
                button.addEventListener("click", function () {
                    var input = document.getElementById(button.getAttribute("data-target"));

                    if (!input) {
                        return;
                    }

                    var isHidden = input.type === "password";
                    input.type = isHidden ? "text" : "password";
                    button.textContent = isHidden ? "Ocultar" : "Ver";
                });
            });
        }());
    </script>

</body>
</html>
