<?php
require_once "session-bootstrap.php";

if (isset($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = $_GET["error"] ?? "";
$rememberedEmail = $_COOKIE["admin_remember_email"] ?? "";
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
        input[type="password"],
        input[type="text"] {
            width: 100%;
            height: 40px;
            padding: 12px 48px 12px 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="text"]:focus {
            border-color: #198754;
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.12);
        }

        .password-field {
            position: relative;
        }

        .password-field input {
            padding-right: 48px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 8px;
            background: #e9f3ee;
            color: #198754;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
        }

        .toggle-password:hover {
            background: #d7ebdf;
        }

        .toggle-password:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.15);
        }

        .toggle-password svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
        }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
        }

        .remember-row input[type="checkbox"] {
            width: auto;
            margin: 0;
            accent-color: #a64ac9;
        }

        .remember-row label {
            margin: 0;
            font-weight: normal;
            color: #444;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #198754;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }

        .submit-btn:hover {
            background: #157347;
        }

        .error {
            background: #f8d7da;
            color: #842029;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 18px;
        }

        .helper-text {
            font-size: 12px;
            color: #777;
            margin-top: 6px;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h1>Panel Admin</h1>
        <p class="subtitle">Inicia sesi&oacute;n para continuar</p>

        <?php if ($error === "invalid"): ?>
            <div class="error">Correo o contrase&ntilde;a incorrectos.</div>
        <?php endif; ?>

        <?php if ($error === "blocked"): ?>
            <div class="error">Demasiados intentos fallidos. Intenta nuevamente en 15 minutos.</div>
        <?php endif; ?>

        <?php if ($error === "unavailable"): ?>
            <div class="error">El sistema no est&aacute; disponible temporalmente.</div>
        <?php endif; ?>

        <form action="login-process.php" method="post" autocomplete="on" id="adminLoginForm">
            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>"
            >

            <div class="form-group">
                <label for="email">Correo</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($rememberedEmail, ENT_QUOTES, "UTF-8"); ?>"
                    required
                    autocomplete="username"
                    autocapitalize="none"
                    spellcheck="false"
                    inputmode="email"
                >
            </div>

            <div class="form-group">
                <label for="password">Contrase&ntilde;a</label>
                <div class="password-field">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        id="togglePassword"
                        aria-controls="password"
                        aria-label="Mostrar contrase&ntilde;a"
                        aria-pressed="false"
                    >
                        <svg id="eyeOpenIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                        </svg>

                        <svg id="eyeClosedIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.584 10.587a2 2 0 1 0 2.829 2.828" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.09A10.94 10.94 0 0 1 12 5c4.478 0 8.268 2.943 9.542 7a10.964 10.964 0 0 1-4.12 5.09" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.228 6.228A10.962 10.962 0 0 0 2.458 12c1.274 4.057 5.064 7 9.542 7 2.048 0 3.964-.616 5.562-1.672" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="remember-row">
                <input
                    type="checkbox"
                    id="remember_credentials"
                    name="remember_credentials"
                    value="1"
                    <?php echo $rememberedEmail !== "" ? "checked" : ""; ?>
                >
                <label for="remember_credentials">Recordarme</label>
            </div>

            <button type="submit" class="submit-btn">Ingresar</button>
        </form>
    </div>

    <script>
        (function () {
            var passwordInput = document.getElementById("password");
            var toggleButton = document.getElementById("togglePassword");
            var eyeOpenIcon = document.getElementById("eyeOpenIcon");
            var eyeClosedIcon = document.getElementById("eyeClosedIcon");
            var loginForm = document.getElementById("adminLoginForm");
            var emailInput = document.getElementById("email");
            var rememberCheckbox = document.getElementById("remember_credentials");

            toggleButton.addEventListener("click", function () {
                var isHidden = passwordInput.getAttribute("data-visible") !== "true";

                passwordInput.type = isHidden ? "text" : "password";
                passwordInput.setAttribute("data-visible", isHidden ? "true" : "false");

                toggleButton.setAttribute("aria-label", isHidden ? "Ocultar contraseña" : "Mostrar contraseña");
                toggleButton.setAttribute("aria-pressed", isHidden ? "true" : "false");

                eyeOpenIcon.style.display = isHidden ? "none" : "block";
                eyeClosedIcon.style.display = isHidden ? "block" : "none";
            });

            loginForm.addEventListener("submit", function () {
                var expires = "expires=Thu, 01 Jan 1970 00:00:00 GMT;";

                passwordInput.type = "password";
                passwordInput.setAttribute("data-visible", "false");

                eyeOpenIcon.style.display = "block";
                eyeClosedIcon.style.display = "none";
                toggleButton.setAttribute("aria-label", "Mostrar contraseña");
                toggleButton.setAttribute("aria-pressed", "false");

                if (rememberCheckbox.checked && emailInput.value.trim() !== "") {
                    var expiryDate = new Date();
                    expiryDate.setDate(expiryDate.getDate() + 30);

                    document.cookie =
                        "admin_remember_email=" + encodeURIComponent(emailInput.value.trim()) +
                        ";expires=" + expiryDate.toUTCString() +
                        ";path=/;SameSite=Lax";

                    return;
                }

                document.cookie = "admin_remember_email=;" + expires + "path=/;SameSite=Lax";
            });
        }());
    </script>

</body>
</html>