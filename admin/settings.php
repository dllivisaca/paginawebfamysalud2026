<?php
require_once "auth-check.php";
require_once __DIR__ . "/../db.php";

$defaultSettings = [
    "site_name" => "FamySalud",
    "site_logo_path" => "",
    "footer_about_text" => "Atención médica con enfoque humano, cercano y profesional.\nInformación institucional y canales de contacto en proceso de actualización.",
    "footer_copyright" => "Todos los derechos reservados",
    "facebook_url" => "#",
    "instagram_url" => "#",
    "twitter_url" => "#",
    "linkedin_url" => "#",
    "youtube_url" => "",
    "background_color" => "#ffffff",
    "default_color" => "#2c3031",
    "heading_color" => "#18444c",
    "accent_color" => "#049ebb",
    "nav_color" => "#496268",
    "nav_hover_color" => "#049ebb",
];

function settingsTableExists(mysqli $conn): bool
{
    $result = $conn->query("SHOW TABLES LIKE 'site_settings'");
    return $result instanceof mysqli_result && $result->num_rows > 0;
}

function cleanSettingText(string $value, int $maxLength): string
{
    $value = trim(strip_tags($value));
    return function_exists("mb_substr") ? mb_substr($value, 0, $maxLength, "UTF-8") : substr($value, 0, $maxLength);
}

function cleanSettingTextarea(string $value): string
{
    $value = trim(strip_tags($value));
    return function_exists("mb_substr") ? mb_substr($value, 0, 2000, "UTF-8") : substr($value, 0, 2000);
}

function cleanSettingColor(string $value, string $fallback): string
{
    $value = trim($value);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $fallback;
}

$tableExists = isset($conn) && $conn instanceof mysqli && settingsTableExists($conn);
$settings = $defaultSettings;
$successMessage = "";
$errorMessage = "";

if ($tableExists) {
    $stmt = $conn->prepare("SELECT site_name, site_logo_path, footer_about_text, footer_copyright, facebook_url, instagram_url, twitter_url, linkedin_url, youtube_url, background_color, default_color, heading_color, accent_color, nav_color, nav_hover_color FROM site_settings WHERE id = 1 LIMIT 1");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        if (is_array($row)) {
            foreach ($settings as $key => $value) {
                $settings[$key] = (string) ($row[$key] ?? $value);
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!$tableExists) {
        $errorMessage = "La tabla site_settings a&uacute;n no existe. Ejecuta primero database/site-settings.sql en phpMyAdmin.";
    } else {
        $settings = [
            "site_name" => cleanSettingText((string) ($_POST["site_name"] ?? ""), 150),
            "site_logo_path" => cleanSettingText((string) ($_POST["site_logo_path"] ?? ""), 255),
            "footer_about_text" => cleanSettingTextarea((string) ($_POST["footer_about_text"] ?? "")),
            "footer_copyright" => cleanSettingText((string) ($_POST["footer_copyright"] ?? ""), 255),
            "facebook_url" => cleanSettingText((string) ($_POST["facebook_url"] ?? ""), 255),
            "instagram_url" => cleanSettingText((string) ($_POST["instagram_url"] ?? ""), 255),
            "twitter_url" => cleanSettingText((string) ($_POST["twitter_url"] ?? ""), 255),
            "linkedin_url" => cleanSettingText((string) ($_POST["linkedin_url"] ?? ""), 255),
            "youtube_url" => cleanSettingText((string) ($_POST["youtube_url"] ?? ""), 255),
            "background_color" => cleanSettingColor((string) ($_POST["background_color"] ?? ""), $defaultSettings["background_color"]),
            "default_color" => cleanSettingColor((string) ($_POST["default_color"] ?? ""), $defaultSettings["default_color"]),
            "heading_color" => cleanSettingColor((string) ($_POST["heading_color"] ?? ""), $defaultSettings["heading_color"]),
            "accent_color" => cleanSettingColor((string) ($_POST["accent_color"] ?? ""), $defaultSettings["accent_color"]),
            "nav_color" => cleanSettingColor((string) ($_POST["nav_color"] ?? ""), $defaultSettings["nav_color"]),
            "nav_hover_color" => cleanSettingColor((string) ($_POST["nav_hover_color"] ?? ""), $defaultSettings["nav_hover_color"]),
        ];
        if ($settings["site_name"] === "") { $settings["site_name"] = $defaultSettings["site_name"]; }
        if ($settings["footer_about_text"] === "") { $settings["footer_about_text"] = $defaultSettings["footer_about_text"]; }
        if ($settings["footer_copyright"] === "") { $settings["footer_copyright"] = $defaultSettings["footer_copyright"]; }

        $stmt = $conn->prepare("INSERT INTO site_settings (id, site_name, site_logo_path, footer_about_text, footer_copyright, facebook_url, instagram_url, twitter_url, linkedin_url, youtube_url, background_color, default_color, heading_color, accent_color, nav_color, nav_hover_color) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE site_name = VALUES(site_name), site_logo_path = VALUES(site_logo_path), footer_about_text = VALUES(footer_about_text), footer_copyright = VALUES(footer_copyright), facebook_url = VALUES(facebook_url), instagram_url = VALUES(instagram_url), twitter_url = VALUES(twitter_url), linkedin_url = VALUES(linkedin_url), youtube_url = VALUES(youtube_url), background_color = VALUES(background_color), default_color = VALUES(default_color), heading_color = VALUES(heading_color), accent_color = VALUES(accent_color), nav_color = VALUES(nav_color), nav_hover_color = VALUES(nav_hover_color)");
        if ($stmt) {
            $stmt->bind_param("sssssssssssssss", $settings["site_name"], $settings["site_logo_path"], $settings["footer_about_text"], $settings["footer_copyright"], $settings["facebook_url"], $settings["instagram_url"], $settings["twitter_url"], $settings["linkedin_url"], $settings["youtube_url"], $settings["background_color"], $settings["default_color"], $settings["heading_color"], $settings["accent_color"], $settings["nav_color"], $settings["nav_hover_color"]);
            $successMessage = $stmt->execute() ? "La personalizaci&oacute;n del sitio se guard&oacute; correctamente." : "";
            $errorMessage = $successMessage === "" ? "No fue posible guardar la personalizaci&oacute;n." : "";
            $stmt->close();
        } else {
            $errorMessage = "No fue posible preparar el guardado de la personalizaci&oacute;n.";
        }
    }
}

function settingValue(array $settings, string $key): string
{
    return htmlspecialchars((string) ($settings[$key] ?? ""), ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personalizaci&oacute;n del sitio</title>
    <style>
        *{box-sizing:border-box}body{font-family:Arial,sans-serif;background:#f4f6f9;margin:0;padding:0;color:#1f2937}.layout{min-height:100vh;display:flex;align-items:flex-start}.sidebar{width:260px;background:#ffffff;border-right:1px solid #e5e7eb;padding:24px 18px;display:flex;flex-direction:column;gap:22px;position:sticky;top:0;height:100vh;overflow-y:auto;flex-shrink:0}.brand{padding-bottom:18px;border-bottom:1px solid #e5e7eb}.brand h2{margin:0;font-size:22px;color:#198754}.brand p{margin:8px 0 0;color:#6b7280;font-size:14px;line-height:1.4}.sidebar-section-title{margin:0 0 10px;font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af}.nav{display:flex;flex-direction:column;gap:8px}.nav a{display:flex;align-items:center;gap:10px;text-decoration:none;color:#374151;padding:11px 12px;border-radius:10px;transition:background .2s ease,color .2s ease}.nav a:hover{background:#eef8f2;color:#198754}.nav a.active{background:#e9f7ef;color:#198754;font-weight:bold}.nav-icon{width:18px;height:18px;flex-shrink:0;display:inline-flex;align-items:center;justify-content:center}.main{flex:1;padding:32px;min-width:0}.topbar{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:24px}.page-title{margin:0;font-size:36px;line-height:1.1}.page-subtitle{margin:10px 0 0;font-size:16px;color:#6b7280;max-width:760px;line-height:1.5}.topbar-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.btn{display:inline-block;padding:10px 16px;border-radius:10px;text-decoration:none;border:0;cursor:pointer;font-size:14px;transition:background .2s ease,transform .2s ease}.btn:hover{transform:translateY(-1px)}.btn-outline{background:#ffffff;color:#198754;border:1px solid #cfe7d8}.btn-outline:hover{background:#eef8f2}.btn-logout{background:#dc3545;color:#ffffff}.btn-logout:hover{background:#bb2d3b}.btn-primary{background:#198754;color:#ffffff}.btn-primary:hover{background:#157347}.card{background:#ffffff;border:1px solid #e5e7eb;border-radius:16px;padding:24px;box-shadow:0 8px 24px rgba(0,0,0,.04);margin-bottom:18px}.card h2{margin:0 0 16px;font-size:22px}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.field-full{grid-column:1/-1}label{display:block;margin-bottom:8px;font-weight:bold;color:#374151}input[type=text],input[type=url],input[type=color],textarea{width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:10px;font-size:14px;outline:none;background:#ffffff;color:#1f2937}input[type=color]{height:44px;padding:4px}textarea{min-height:120px;resize:vertical;line-height:1.5}.helper{font-size:13px;color:#6b7280;margin-top:8px;line-height:1.4}.actions-row{display:flex;gap:10px;flex-wrap:wrap;margin-top:20px}.alert{border-radius:12px;padding:14px 16px;margin-bottom:18px;font-size:14px;line-height:1.5}.alert-success{background:#e9f7ef;color:#146c43;border:1px solid #cfe7d8}.alert-error{background:#f8d7da;color:#842029;border:1px solid #f1b0b7}@media(max-width:991px){.layout{flex-direction:column}.sidebar{width:100%;border-right:0;border-bottom:1px solid #e5e7eb;padding:18px;position:static;top:auto;height:auto;overflow-y:visible}.main{padding:22px}.topbar{flex-direction:column;align-items:stretch}.topbar-actions{justify-content:flex-start}.form-grid{grid-template-columns:1fr}.page-title{font-size:30px}}@media(max-width:640px){.sidebar{padding:16px}.main{padding:16px}.card{padding:18px;border-radius:14px}.page-title{font-size:26px}.nav a{padding:10px 11px}}
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
                    <a href="dashboard.php"><span class="nav-icon">&#127968;</span><span>Panel de inicio</span></a>
                    <a href="menu/index.php"><span class="nav-icon">&#128203;</span><span>Men&uacute; de navegaci&oacute;n</span></a>
                </nav>
            </div>
            <div>
                <p class="sidebar-section-title">Contenido</p>
                <nav class="nav">
                    <a href="pages/index.php"><span class="nav-icon">&#128196;</span><span>P&aacute;ginas del sitio</span></a>
                </nav>
            </div>
            <div>
                <p class="sidebar-section-title">Configuraci&oacute;n</p>
                <nav class="nav">
                    <a href="settings.php" class="active"><span class="nav-icon">&#9881;</span><span>Configuraci&oacute;n</span></a>
                    <a href="change-password.php"><span class="nav-icon">&#128274;</span><span>Cambiar contrase&ntilde;a</span></a>
                </nav>
            </div>
        </aside>
        <main class="main">
            <div class="topbar">
                <div>
                    <h1 class="page-title">Personalizaci&oacute;n del sitio</h1>
                    <p class="page-subtitle">Edita solo los datos globales del frontend p&uacute;blico: logo, nombre, footer, redes y variables de color.</p>
                </div>
                <div class="topbar-actions">
                    <a href="dashboard.php" class="btn btn-outline">Volver al panel</a>
                    <form action="logout.php" method="post" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                        <button type="submit" class="btn btn-logout">Cerrar sesi&oacute;n</button>
                    </form>
                </div>
            </div>
            <?php if (!$tableExists): ?><div class="alert alert-error">La tabla site_settings a&uacute;n no existe. Ejecuta primero database/site-settings.sql en phpMyAdmin.</div><?php endif; ?>
            <?php if ($successMessage !== ""): ?><div class="alert alert-success"><?php echo $successMessage; ?></div><?php endif; ?>
            <?php if ($errorMessage !== ""): ?><div class="alert alert-error"><?php echo $errorMessage; ?></div><?php endif; ?>
            <form method="post" action="settings.php">
                <section class="card">
                    <h2>Identidad</h2>
                    <div class="form-grid">
                        <div>
                            <label for="site_name">Nombre del sitio</label>
                            <input type="text" id="site_name" name="site_name" value="<?php echo settingValue($settings, "site_name"); ?>" maxlength="150">
                        </div>
                        <div>
                            <label for="site_logo_path">Ruta del logo</label>
                            <input type="text" id="site_logo_path" name="site_logo_path" value="<?php echo settingValue($settings, "site_logo_path"); ?>" maxlength="255" placeholder="assets/img/logo.webp">
                            <div class="helper">Deja este campo vac&iacute;o para usar el &iacute;cono actual del header.</div>
                        </div>
                    </div>
                </section>
                <section class="card">
                    <h2>Footer global</h2>
                    <div class="form-grid">
                        <div class="field-full">
                            <label for="footer_about_text">Texto institucional</label>
                            <textarea id="footer_about_text" name="footer_about_text"><?php echo settingValue($settings, "footer_about_text"); ?></textarea>
                            <div class="helper">Cada salto de l&iacute;nea se muestra como un p&aacute;rrafo en el footer.</div>
                        </div>
                        <div class="field-full">
                            <label for="footer_copyright">Copyright</label>
                            <input type="text" id="footer_copyright" name="footer_copyright" value="<?php echo settingValue($settings, "footer_copyright"); ?>" maxlength="255">
                        </div>
                    </div>
                </section>
                <section class="card">
                    <h2>Redes sociales</h2>
                    <div class="form-grid">
                        <div><label for="twitter_url">X / Twitter</label><input type="text" id="twitter_url" name="twitter_url" value="<?php echo settingValue($settings, "twitter_url"); ?>" maxlength="255"></div>
                        <div><label for="facebook_url">Facebook</label><input type="text" id="facebook_url" name="facebook_url" value="<?php echo settingValue($settings, "facebook_url"); ?>" maxlength="255"></div>
                        <div><label for="instagram_url">Instagram</label><input type="text" id="instagram_url" name="instagram_url" value="<?php echo settingValue($settings, "instagram_url"); ?>" maxlength="255"></div>
                        <div><label for="linkedin_url">LinkedIn</label><input type="text" id="linkedin_url" name="linkedin_url" value="<?php echo settingValue($settings, "linkedin_url"); ?>" maxlength="255"></div>
                        <div><label for="youtube_url">YouTube</label><input type="text" id="youtube_url" name="youtube_url" value="<?php echo settingValue($settings, "youtube_url"); ?>" maxlength="255"></div>
                    </div>
                </section>
                <section class="card">
                    <h2>Colores del frontend</h2>
                    <div class="form-grid">
                        <div><label for="background_color">Background</label><input type="color" id="background_color" name="background_color" value="<?php echo settingValue($settings, "background_color"); ?>"></div>
                        <div><label for="default_color">Texto general</label><input type="color" id="default_color" name="default_color" value="<?php echo settingValue($settings, "default_color"); ?>"></div>
                        <div><label for="heading_color">T&iacute;tulos</label><input type="color" id="heading_color" name="heading_color" value="<?php echo settingValue($settings, "heading_color"); ?>"></div>
                        <div><label for="accent_color">Acento</label><input type="color" id="accent_color" name="accent_color" value="<?php echo settingValue($settings, "accent_color"); ?>"></div>
                        <div><label for="nav_color">Men&uacute;</label><input type="color" id="nav_color" name="nav_color" value="<?php echo settingValue($settings, "nav_color"); ?>"></div>
                        <div><label for="nav_hover_color">Men&uacute; hover</label><input type="color" id="nav_hover_color" name="nav_hover_color" value="<?php echo settingValue($settings, "nav_hover_color"); ?>"></div>
                    </div>
                    <div class="helper">Estos valores sobrescriben variables CSS existentes en el frontend p&uacute;blico.</div>
                </section>
                <div class="actions-row">
                    <button type="submit" class="btn btn-primary" <?php echo $tableExists ? "" : "disabled"; ?>>Guardar personalizaci&oacute;n</button>
                    <a href="dashboard.php" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        </main>
    </div>
</body>
</html>
