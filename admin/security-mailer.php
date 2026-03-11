<?php

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

function getSecurityMailerClientIpAddress(): string
{
    $forwardedFor = $_SERVER["HTTP_X_FORWARDED_FOR"] ?? "";

    if ($forwardedFor !== "") {
        $parts = explode(",", $forwardedFor);
        $candidateIp = trim($parts[0]);

        if (filter_var($candidateIp, FILTER_VALIDATE_IP) !== false) {
            return $candidateIp;
        }
    }

    $remoteAddress = $_SERVER["REMOTE_ADDR"] ?? "Desconocida";

    if (filter_var($remoteAddress, FILTER_VALIDATE_IP) !== false) {
        return $remoteAddress;
    }

    return "Desconocida";
}

function loadSecurityMailerEnvironment(): bool
{
    static $isLoaded = false;

    if ($isLoaded) {
        return true;
    }

    $autoloadPath = dirname(__DIR__) . "/vendor/autoload.php";

    if (!is_file($autoloadPath)) {
        error_log("No se encontro vendor/autoload.php para el envio de correos de seguridad.");
        return false;
    }

    require_once $autoloadPath;

    $envPath = dirname(__DIR__) . "/.env";

    if (!is_file($envPath)) {
        error_log("No se encontro el archivo .env para el envio de correos de seguridad.");
        return false;
    }

    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();

    $isLoaded = true;

    return true;
}

function sendPasswordChangedSecurityEmail(string $recipientEmail, string $recipientName = ""): bool
{
    if (!loadSecurityMailerEnvironment()) {
        return false;
    }

    $mailHost = $_ENV["MAIL_HOST"] ?? "";
    $mailPort = $_ENV["MAIL_PORT"] ?? "";
    $mailUsername = $_ENV["MAIL_USERNAME"] ?? "";
    $mailPassword = $_ENV["MAIL_PASSWORD"] ?? "";
    $mailEncryption = $_ENV["MAIL_ENCRYPTION"] ?? "";
    $mailFromAddress = $_ENV["MAIL_FROM_ADDRESS"] ?? "";
    $mailFromName = $_ENV["MAIL_FROM_NAME"] ?? "";
    $smtpSecure = "";

    if (
        $mailHost === "" ||
        $mailPort === "" ||
        $mailUsername === "" ||
        $mailPassword === "" ||
        $mailEncryption === "" ||
        $mailFromAddress === "" ||
        $mailFromName === ""
    ) {
        error_log("Faltan variables SMTP requeridas en .env para el envio de correos de seguridad.");
        return false;
    }

    if ($mailEncryption === "tls") {
        $smtpSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($mailEncryption === "ssl") {
        $smtpSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        error_log("MAIL_ENCRYPTION debe ser tls o ssl para el envio de correos de seguridad.");
        return false;
    }

    if (filter_var($recipientEmail, FILTER_VALIDATE_EMAIL) === false) {
        error_log("El correo destinatario del aviso de seguridad no es valido.");
        return false;
    }

    $mailer = new PHPMailer(true);
    $timeZone = new DateTimeZone("America/Guayaquil");
    $formattedDate = (new DateTime("now", $timeZone))->format("Y-m-d H:i:s");
    $clientIp = getSecurityMailerClientIpAddress();
    $safeRecipientName = trim($recipientName) !== "" ? trim($recipientName) : "administrador";
    $subject = "Se cambió la contraseña de tu cuenta de administrador de la página web";
    $htmlBody = '<html><body style="font-family: Arial, sans-serif; color: #1f2937;">'
        . '<h2 style="color: #198754;">Aviso de seguridad</h2>'
        . '<p>Hola ' . htmlspecialchars($safeRecipientName, ENT_QUOTES, "UTF-8") . ',</p>'
        . '<p>Se registr&oacute; un cambio de contrase&ntilde;a en tu cuenta de administrador de la p&aacute;gina web de FamySALUD.</p>'
        . '<p><strong>Fecha y hora:</strong> ' . htmlspecialchars($formattedDate, ENT_QUOTES, "UTF-8") . '</p>'
        . '<p><strong>IP del cliente:</strong> ' . htmlspecialchars($clientIp, ENT_QUOTES, "UTF-8") . '</p>'
        . '<p>Si no reconoces esta acci&oacute;n, cambia tu contrase&ntilde;a inmediatamente y revisa el acceso a tu cuenta.</p>'
        . '</body></html>';
    $textBody = "Aviso de seguridad\n\n"
        . "Se registro un cambio de contrasena en tu cuenta de administrador de la pagina web de FamySALUD.\n"
        . "Fecha y hora: " . $formattedDate . "\n"
        . "IP del cliente: " . $clientIp . "\n\n"
        . "Si no reconoces esta accion, cambia tu contrasena inmediatamente y revisa el acceso a tu cuenta.\n";

    try {
        $mailer->CharSet = "UTF-8";
        $mailer->isSMTP();
        $mailer->Host = $mailHost;
        $mailer->Port = (int) $mailPort;
        $mailer->SMTPAuth = true;
        $mailer->Username = $mailUsername;
        $mailer->Password = $mailPassword;
        $mailer->SMTPSecure = $smtpSecure;

        $isLocalEnvironment =
            ($_SERVER["HTTP_HOST"] ?? "") === "localhost"
            || ($_SERVER["SERVER_NAME"] ?? "") === "localhost"
            || in_array($_SERVER["REMOTE_ADDR"] ?? "", ["127.0.0.1", "::1"], true);

        if ($isLocalEnvironment) {
            $mailer->SMTPOptions = [
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                    "allow_self_signed" => true,
                ],
            ];
        }

        $mailer->setFrom($mailFromAddress, $mailFromName);
        $mailer->addAddress($recipientEmail, $recipientName);
        $mailer->Subject = $subject;
        $mailer->isHTML(true);
        $mailer->Body = $htmlBody;
        $mailer->AltBody = $textBody;

        return $mailer->send();
    } catch (PHPMailerException $exception) {
        error_log("Error enviando correo de seguridad por cambio de contrasena: " . $exception->getMessage());
        return false;
    } catch (Throwable $throwable) {
        error_log("Fallo inesperado al enviar correo de seguridad por cambio de contrasena: " . $throwable->getMessage());
        return false;
    }
}
