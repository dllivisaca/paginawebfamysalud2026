<?php
require_once "../auth-check.php";

$pageId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar p&aacute;gina</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #1f2937;
        }

        .wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            width: 100%;
            max-width: 680px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
        }

        h1 {
            margin: 0 0 10px;
            font-size: 30px;
        }

        p {
            margin: 0 0 16px;
            color: #6b7280;
            line-height: 1.6;
        }

        .page-id {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 999px;
            background: #e9f7ef;
            color: #146c43;
            font-weight: bold;
            margin-bottom: 18px;
        }

        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 10px;
            text-decoration: none;
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
    </style>
</head>
<body>
    <div class="wrapper">
        <section class="card">
            <h1>Editar p&aacute;gina</h1>
            <p>Base m&iacute;nima de la pantalla de edici&oacute;n para futuras mejoras del administrador.</p>
            <div class="page-id">ID recibido: <?php echo htmlspecialchars((string) $pageId, ENT_QUOTES, "UTF-8"); ?></div>
            <p>Aqu&iacute; se implementar&aacute; despu&eacute;s el editor de la p&aacute;gina seleccionada.</p>
            <div class="actions">
                <a href="index.php" class="btn btn-primary">Volver a p&aacute;ginas</a>
                <a href="../dashboard.php" class="btn btn-outline">Ir al panel</a>
            </div>
        </section>
    </div>
</body>
</html>
