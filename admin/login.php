<?php
session_start();

if (isset($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = $_GET["error"] ?? "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login administrador</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .login-container {
            max-width: 420px;
            margin: 80px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 28px;
            text-align: center;
        }

        p.subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #198754;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #157347;
        }

        .error {
            background: #f8d7da;
            color: #842029;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 18px;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h1>Panel Admin</h1>
        <p class="subtitle">Inicia sesión para continuar</p>

        <?php if ($error === "invalid"): ?>
            <div class="error">Correo o contraseña incorrectos.</div>
        <?php endif; ?>

        <?php if ($error === "inactive"): ?>
            <div class="error">Tu usuario está inactivo.</div>
        <?php endif; ?>

        <?php if ($error === "blocked"): ?>
            <div class="error">Demasiados intentos fallidos. Intenta nuevamente en 15 minutos.</div>
        <?php endif; ?>

        <form action="login-process.php" method="post">
            <div class="form-group">
                <label for="email">Correo</label>
                <input type="email" id="email" name="email" required autocomplete="email" autocapitalize="none" spellcheck="false" inputmode="email">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit">Ingresar</button>
        </form>
    </div>

</body>
</html>