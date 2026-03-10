<?php
require_once "auth-check.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .btn {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 8px;
            text-decoration: none;
            background: #198754;
            color: #fff;
        }

        .btn-logout {
            background: #dc3545;
        }

        .card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 16px;
        }

        .card a {
            text-decoration: none;
            color: #198754;
            font-weight: bold;
        }

        .muted {
            color: #666;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="topbar">
            <div>
                <h1>Dashboard</h1>
                <p class="muted">Bienvenida, <?php echo htmlspecialchars($_SESSION["admin_name"]); ?></p>
            </div>

            <a href="logout.php" class="btn btn-logout">Cerrar sesión</a>
        </div>

        <div class="card">
            <h3>Menú del sitio</h3>
            <p class="muted">Administra los ítems del menú principal y sus submenús.</p>
            <a href="menu/index.php">Ir al módulo de menú</a>
        </div>
    </div>

</body>
</html>