<?php
require_once "../auth-check.php";
require_once "../../db.php";
require_once "../../includes/page-content.php";
function escapeAdminFieldLabel($value)
{
    $value = (string) $value;

    if ($value === "") {
        return "";
    }

    if (preg_match('//u', $value) === 1) {
        return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
    }

    $converted = function_exists("iconv") ? @iconv("Windows-1252", "UTF-8//IGNORE", $value) : false;

    if (is_string($converted) && $converted !== "") {
        return htmlspecialchars($converted, ENT_QUOTES, "UTF-8");
    }

    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function getSimpleFieldUpload(array $files, string $fieldKey): ?array
{
    if (!isset($files["simple_fields"])) {
        return null;
    }

    $fieldFiles = $files["simple_fields"];

    if (!isset($fieldFiles["name"][$fieldKey]["upload"])) {
        return null;
    }

    return [
        "name" => (string) ($fieldFiles["name"][$fieldKey]["upload"] ?? ""),
        "type" => (string) ($fieldFiles["type"][$fieldKey]["upload"] ?? ""),
        "tmp_name" => (string) ($fieldFiles["tmp_name"][$fieldKey]["upload"] ?? ""),
        "error" => (int) ($fieldFiles["error"][$fieldKey]["upload"] ?? UPLOAD_ERR_NO_FILE),
        "size" => (int) ($fieldFiles["size"][$fieldKey]["upload"] ?? 0),
    ];
}

function storeSimpleFieldImageUpload(array $file, string $fieldKey, string $templateKey): array
{
    if (($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ["ok" => true, "path" => null, "error" => ""];
    }

    if (($file["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ["ok" => false, "path" => null, "error" => "No fue posible subir la imagen seleccionada."];
    }

    $tmpName = (string) ($file["tmp_name"] ?? "");

    if ($tmpName === "" || !is_uploaded_file($tmpName)) {
        return ["ok" => false, "path" => null, "error" => "No se encontró un archivo válido para la subida."];
    }

    $originalName = (string) ($file["name"] ?? "");
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ["jpg", "jpeg", "png", "webp"];

    if (!in_array($extension, $allowedExtensions, true)) {
        return ["ok" => false, "path" => null, "error" => "La imagen debe estar en formato JPG, JPEG, PNG o WEBP."];
    }

    $finfo = function_exists("finfo_open") ? finfo_open(FILEINFO_MIME_TYPE) : false;
    $mimeType = $finfo ? (string) finfo_file($finfo, $tmpName) : "";

    if ($finfo) {
        finfo_close($finfo);
    }

    $allowedMimeTypes = ["image/jpeg", "image/png", "image/webp"];

    if ($mimeType !== "" && !in_array($mimeType, $allowedMimeTypes, true)) {
        return ["ok" => false, "path" => null, "error" => "El archivo seleccionado no es una imagen válida."];
    }

    $uploadDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "img" . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "pages";

    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true) && !is_dir($uploadDirectory)) {
        return ["ok" => false, "path" => null, "error" => "No fue posible preparar la carpeta de imágenes."];
    }

    $safeTemplateKey = preg_replace('/[^a-z0-9]+/i', '-', strtolower($templateKey));
    $safeFieldKey = preg_replace('/[^a-z0-9]+/i', '-', strtolower($fieldKey));
    $safeTemplateKey = trim((string) $safeTemplateKey, '-');
    $safeFieldKey = trim((string) $safeFieldKey, '-');
    $fileName = ($safeTemplateKey !== '' ? $safeTemplateKey . '-' : '') . $safeFieldKey . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $extension;
    $targetAbsolutePath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmpName, $targetAbsolutePath)) {
        return ["ok" => false, "path" => null, "error" => "No fue posible guardar la imagen en el servidor."];
    }

    return [
        "ok" => true,
        "path" => "assets/img/uploads/pages/" . $fileName,
        "error" => "",
    ];
}
function renderAdminRepeaterSection(array $repeaterConfig, array $contentData, string $sectionClass = ""): void
{
    $repeaterKey = (string) ($repeaterConfig["repeater_key"] ?? "");
    $repeaterItems = $contentData["repeaters"][$repeaterKey] ?? [];
    ?>
    <div class="section-block<?php echo $sectionClass !== "" ? " " . htmlspecialchars($sectionClass, ENT_QUOTES, "UTF-8") : ""; ?><?php echo $repeaterKey === "hero_features" ? " hero-features-admin-section" : ""; ?>">
        <h3><?php echo htmlspecialchars((string) ($repeaterConfig["label"] ?? $repeaterKey), ENT_QUOTES, "UTF-8"); ?></h3>

        <?php foreach ($repeaterConfig["items"] as $itemConfig): ?>
            <?php
            $itemIndex = (int) $itemConfig["item_index"];
            $itemTitle = trim((string) ($itemConfig["item_label"] ?? ""));
            if ($itemTitle === "") {
                $itemTitle = "Estadística " . ($itemIndex + 1);
            }
            $itemData = $repeaterItems[$itemIndex] ?? ["fields" => [], "is_visible" => 1];
            $itemVisible = (int) ($itemData["is_visible"] ?? 1) === 1;
            ?>
            <div class="card">
                <div class="item-title">
                    <h3><?php echo escapeAdminFieldLabel($itemTitle); ?></h3>
                    <label class="toggle-row">
                        <input type="checkbox" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][is_visible]" value="1"<?php echo $itemVisible ? " checked" : ""; ?>>
                        <span>Mostrar este bloque</span>
                    </label>
                </div>

                <div class="field-grid">
                    <?php foreach ($repeaterConfig["fields"] as $fieldConfig): ?>
                        <?php
                        $fieldKey = (string) ($fieldConfig["field_key"] ?? "");
                        $fieldType = (string) ($fieldConfig["field_type"] ?? "text");
                        $fieldValue = (string) (($itemData["fields"][$fieldKey]["field_value"] ?? ""));
                        $fieldLabel = (string) ($fieldConfig["label"] ?? $fieldKey);
                        if ($repeaterKey === "about_stats" && $fieldKey === "value") {
                            $fieldLabel = "Valor";
                        }
                        ?>
                        <div class="field-group">
                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>"><?php echo escapeAdminFieldLabel($fieldLabel); ?></label>
                            <?php if ($fieldType === "image"): ?>
                                <input type="hidden" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                <div class="current-file"><strong>Archivo actual:</strong> <?php echo htmlspecialchars($fieldValue !== "" ? basename($fieldValue) : "Sin imagen seleccionada", ENT_QUOTES, "UTF-8"); ?></div>
                                <label class="file-input-label" for="repeater_file_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>">Reemplazar imagen</label>
                                <input class="form-file js-image-upload" type="file" id="repeater_file_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-preview-target="preview_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>">
                                <img id="preview_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>" src="<?php echo $fieldValue !== "" ? "../../" . htmlspecialchars(ltrim($fieldValue, "/"), ENT_QUOTES, "UTF-8") : ""; ?>" alt="" class="preview-image<?php echo $fieldValue !== "" ? "" : " is-empty"; ?>">
                            <?php else: ?>
                                <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$pageId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$status = (string) ($_GET["status"] ?? "");
$errors = [];
$successMessage = "";

if ($status === "updated") {
    $successMessage = "El contenido de la pagina se actualizo correctamente.";
}

$pageStmt = $conn->prepare(
    "SELECT id, title, page_key, slug, template_key, is_active
     FROM site_pages
     WHERE id = ?
     LIMIT 1"
);

$page = null;

if ($pageStmt && $pageId > 0) {
    $pageStmt->bind_param("i", $pageId);
    $pageStmt->execute();
    $pageResult = $pageStmt->get_result();
    $page = $pageResult ? $pageResult->fetch_assoc() : null;
    $pageStmt->close();
}

if (!$page) {
    $errors[] = "La pagina solicitada no existe.";
}

$templateKey = trim((string) ($page["template_key"] ?? ""));
$schema = $templateKey !== "" ? getPageContentTemplateSchema($templateKey) : null;

if ($page && !$schema) {
    $errors[] = "La plantilla actual de esta pagina aun no tiene edicion de contenido disponible.";
}

if ($page && $schema) {
    ensurePageContentRepeaterItems($conn, (int) $page["id"], $schema);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $page && $schema) {
    $csrfToken = (string) ($_POST["csrf_token"] ?? "");

    if (!hash_equals($_SESSION["csrf_token"], $csrfToken)) {
        $errors[] = "No se pudo validar la solicitud. Intenta de nuevo.";
    } else {
        foreach ($schema["simple_fields"] ?? [] as $fieldConfig) {
            $fieldKey = (string) ($fieldConfig["field_key"] ?? "");

            if ($fieldKey === "") {
                continue;
            }

            $fieldType = (string) ($fieldConfig["field_type"] ?? "text");
            $fieldValue = trim((string) ($_POST["simple_fields"][$fieldKey]["value"] ?? ""));
            $isVisible = isset($_POST["simple_fields"][$fieldKey]["is_visible"]) ? 1 : 0;

            if ($fieldType === "image") {
                $uploadedFile = getSimpleFieldUpload($_FILES, $fieldKey);
                $uploadResult = is_array($uploadedFile)
                    ? storeSimpleFieldImageUpload($uploadedFile, $fieldKey, $templateKey)
                    : ["ok" => true, "path" => null, "error" => ""];

                if (!($uploadResult["ok"] ?? false)) {
                    $errors[] = (string) ($uploadResult["error"] ?? "No fue posible subir la imagen.");
                    break;
                }

                if (!empty($uploadResult["path"])) {
                    $fieldValue = (string) $uploadResult["path"];
                }
            }

            if (!upsertPageContentField($conn, (int) $page["id"], $fieldKey, $fieldType, $fieldValue, $isVisible)) {
                $errors[] = "No fue posible guardar todos los campos simples.";
                break;
            }
        }

        if ($errors === []) {
            foreach ($schema["repeaters"] ?? [] as $repeaterConfig) {
                $repeaterKey = (string) ($repeaterConfig["repeater_key"] ?? "");

                if ($repeaterKey === "") {
                    continue;
                }

                foreach ($repeaterConfig["items"] ?? [] as $itemConfig) {
                    $itemIndex = (int) ($itemConfig["item_index"] ?? -1);

                    if ($itemIndex < 0) {
                        continue;
                    }

                    $itemVisible = isset($_POST["repeaters"][$repeaterKey][$itemIndex]["is_visible"]) ? 1 : 0;
                    $repeaterItemId = getRepeaterItemId($conn, (int) $page["id"], $repeaterKey, $itemIndex, $itemVisible);

                    if ($repeaterItemId <= 0) {
                        $errors[] = "No fue posible guardar la estructura de bloques repetibles.";
                        break 2;
                    }

                    foreach ($repeaterConfig["fields"] ?? [] as $fieldConfig) {
                        $fieldKey = (string) ($fieldConfig["field_key"] ?? "");

                        if ($fieldKey === "") {
                            continue;
                        }

                        $fieldType = (string) ($fieldConfig["field_type"] ?? "text");
                        $fieldValue = trim((string) ($_POST["repeaters"][$repeaterKey][$itemIndex]["fields"][$fieldKey] ?? ""));

                        if (!upsertRepeaterItemField($conn, $repeaterItemId, $fieldKey, $fieldType, $fieldValue)) {
                            $errors[] = "No fue posible guardar los bloques repetibles.";
                            break 3;
                        }
                    }
                }
            }
        }

        if ($errors === []) {
            header("Location: content.php?id=" . (int) $page["id"] . "&status=updated");
            exit;
        }
    }
}

$contentData = ($page && $schema)
    ? getPageContentData($conn, (int) $page["id"], $schema)
    : ["simple_fields" => [], "repeaters" => []];

$aboutStatsRepeaterConfig = null;
$linkableSitePages = [];
$linkableSitePagesById = [];
$buttonFieldGroups = [];
$imageFieldGroups = [];
$simpleFieldGroups = [];

if (($schema["template_key"] ?? "") === "about") {
    $simpleFieldGroups = [
        [
            "title" => "Texto introductorio",
            "description" => "Contenido principal que acompaña el inicio de la sección.",
            "field_keys" => ["intro_title", "intro_text_1", "intro_text_2"],
        ],
        [
            "title" => "Botones",
            "description" => "Textos y enlaces de los llamados a la acción.",
            "field_keys" => ["primary_cta_text", "primary_cta_link_type", "primary_cta_page_id", "primary_cta_url", "secondary_cta_text", "secondary_cta_link_type", "secondary_cta_page_id", "secondary_cta_url"],
        ],
        [
            "title" => "Imágenes",
            "description" => "Imagen principal y las dos imágenes secundarias de apoyo.",
            "field_keys" => ["main_image", "main_image_alt", "grid_image_1", "grid_image_1_alt", "grid_image_2", "grid_image_2_alt"],
        ],
        [
            "title" => "Certificaciones",
            "description" => "Encabezado del bloque de certificaciones antes de los logos repetibles.",
            "field_keys" => ["certifications_title", "certifications_text"],
        ],
    ];

    [$linkableSitePages, $linkableSitePagesById] = getPageContentLinkablePages($conn, true);
    $buttonFieldGroups = [
        "primary_cta" => [
            "title" => "Botón principal",
            "text_key" => "primary_cta_text",
            "link_type_key" => "primary_cta_link_type",
            "page_id_key" => "primary_cta_page_id",
            "url_key" => "primary_cta_url",
        ],
        "secondary_cta" => [
            "title" => "Botón secundario",
            "text_key" => "secondary_cta_text",
            "link_type_key" => "secondary_cta_link_type",
            "page_id_key" => "secondary_cta_page_id",
            "url_key" => "secondary_cta_url",
        ],
    ];
    $imageFieldGroups = [
        [
            "title" => "Imagen principal",
            "items" => [
                [
                    "image_key" => "main_image",
                    "alt_key" => "main_image_alt",
                ],
            ],
        ],
        [
            "title" => "Imágenes secundarias",
            "items" => [
                [
                    "item_title" => "Imagen secundaria 1",
                    "image_key" => "grid_image_1",
                    "alt_key" => "grid_image_1_alt",
                ],
                [
                    "item_title" => "Imagen secundaria 2",
                    "image_key" => "grid_image_2",
                    "alt_key" => "grid_image_2_alt",
                ],
            ],
        ],
    ];
    $aboutStatsRepeaterConfig = $schema["repeaters"][0] ?? null;
} elseif (($schema["template_key"] ?? "") === "home") {
    [$linkableSitePages, $linkableSitePagesById] = getPageContentLinkablePages($conn, true);
    $simpleFieldGroups = [
        [
            "title" => "Portada",
            "description" => "Contenido principal que aparece al inicio de la página.",
            "field_keys" => ["hero_badge", "hero_title", "hero_text"],
        ],
        [
            "title" => "Portada - Botones",
            "description" => "Llamados a la acción de la portada.",
            "render_mode" => "buttons",
            "button_groups" => [
                "hero_primary_cta" => [
                    "title" => "Botón principal",
                    "text_key" => "hero_primary_cta_text",
                    "link_type_key" => "hero_primary_cta_link_type",
                    "page_id_key" => "hero_primary_cta_page_id",
                    "url_key" => "hero_primary_cta_url",
                ],
                "hero_secondary_cta" => [
                    "title" => "Botón secundario",
                    "text_key" => "hero_secondary_cta_text",
                    "link_type_key" => "hero_secondary_cta_link_type",
                    "page_id_key" => "hero_secondary_cta_page_id",
                    "url_key" => "hero_secondary_cta_url",
                ],
            ],
            "field_keys" => ["hero_primary_cta_text", "hero_primary_cta_link_type", "hero_primary_cta_page_id", "hero_primary_cta_url", "hero_secondary_cta_text", "hero_secondary_cta_link_type", "hero_secondary_cta_page_id", "hero_secondary_cta_url"],
        ],
        [
            "title" => "Portada - Datos informativos",
            "description" => "Textos y datos informativos que se muestran debajo de los botones de la portada.",
            "field_keys" => ["hero_emergency_label", "hero_emergency_value", "hero_hours_label", "hero_hours_value"],
        ],
        [
            "title" => "Portada - Imagen",
            "description" => "Imagen principal de la portada.",
            "render_mode" => "images",
            "image_groups" => [
                [
                    "title" => "Imagen principal",
                    "items" => [
                        [
                            "image_key" => "hero_image",
                            "alt_key" => "hero_image_alt",
                        ],
                    ],
                ],
            ],
            "field_keys" => ["hero_image", "hero_image_alt"],
        ],
        [
            "title" => "Sobre nosotros",
            "description" => "Contenido principal de la sección Sobre nosotros.",
            "field_keys" => ["home_about_title", "home_about_lead", "home_about_text", "home_about_experience_years", "home_about_experience_text"],
        ],
        [
            "title" => "Home About - Botones",
            "description" => "Botones del bloque Home About.",
            "render_mode" => "buttons",
            "button_groups" => [
                "home_about_primary_cta" => [
                    "title" => "Botón principal",
                    "text_key" => "home_about_primary_cta_text",
                    "link_type_key" => "home_about_primary_cta_link_type",
                    "page_id_key" => "home_about_primary_cta_page_id",
                    "url_key" => "home_about_primary_cta_url",
                ],
                "home_about_secondary_cta" => [
                    "title" => "Botón secundario",
                    "text_key" => "home_about_secondary_cta_text",
                    "link_type_key" => "home_about_secondary_cta_link_type",
                    "page_id_key" => "home_about_secondary_cta_page_id",
                    "url_key" => "home_about_secondary_cta_url",
                ],
            ],
            "field_keys" => ["home_about_primary_cta_text", "home_about_primary_cta_link_type", "home_about_primary_cta_page_id", "home_about_primary_cta_url", "home_about_secondary_cta_text", "home_about_secondary_cta_link_type", "home_about_secondary_cta_page_id", "home_about_secondary_cta_url"],
        ],
        [
            "title" => "Home About - Imagen",
            "description" => "Imagen del bloque Home About.",
            "render_mode" => "images",
            "image_groups" => [
                [
                    "title" => "Imagen principal",
                    "items" => [
                        [
                            "image_key" => "home_about_image",
                            "alt_key" => "home_about_image_alt",
                        ],
                    ],
                ],
            ],
            "field_keys" => ["home_about_image", "home_about_image_alt"],
        ],
        [
            "title" => "Certificaciones Home",
            "description" => "Encabezado del bloque de certificaciones de Home.",
            "field_keys" => ["home_about_certifications_title"],
        ],
        [
            "title" => "Featured Departments",
            "description" => "Encabezado del bloque de departamentos destacados.",
            "field_keys" => ["featured_departments_title", "featured_departments_text"],
        ],
        [
            "title" => "Featured Services",
            "description" => "Encabezado del bloque de servicios destacados.",
            "field_keys" => ["featured_services_title", "featured_services_text"],
        ],
        [
            "title" => "Find A Doctor",
            "description" => "Textos y controles del buscador de doctores.",
            "field_keys" => ["find_doctor_title", "find_doctor_text", "doctor_search_placeholder", "doctor_specialty_placeholder", "doctor_search_button_text"],
        ],
        [
            "title" => "Call To Action",
            "description" => "Contenido principal del llamado a la acción.",
            "field_keys" => ["cta_title", "cta_text"],
        ],
        [
            "title" => "Call To Action - Botones",
            "description" => "Botones principales del llamado a la acción.",
            "render_mode" => "buttons",
            "button_groups" => [
                "cta_primary" => [
                    "title" => "Botón principal",
                    "text_key" => "cta_primary_text",
                    "link_type_key" => "cta_primary_link_type",
                    "page_id_key" => "cta_primary_page_id",
                    "url_key" => "cta_primary_url",
                ],
                "cta_secondary" => [
                    "title" => "Botón secundario",
                    "text_key" => "cta_secondary_text",
                    "link_type_key" => "cta_secondary_link_type",
                    "page_id_key" => "cta_secondary_page_id",
                    "url_key" => "cta_secondary_url",
                ],
            ],
            "field_keys" => ["cta_primary_text", "cta_primary_link_type", "cta_primary_page_id", "cta_primary_url", "cta_secondary_text", "cta_secondary_link_type", "cta_secondary_page_id", "cta_secondary_url"],
        ],
        [
            "title" => "Call To Action - Emergencia",
            "description" => "Bloque secundario de emergencia del llamado a la acción.",
            "field_keys" => ["cta_emergency_title", "cta_emergency_text", "cta_emergency_button_text", "cta_emergency_button_url"],
        ],
        [
            "title" => "Emergency Info",
            "description" => "Encabezados del bloque de información de emergencia.",
            "field_keys" => ["emergency_info_title", "emergency_info_text", "quick_actions_title", "emergency_tips_title"],
        ],
        [
            "title" => "Emergency Banner",
            "description" => "Banner principal del bloque de emergencia.",
            "field_keys" => ["emergency_banner_title", "emergency_banner_text", "emergency_banner_button_text", "emergency_banner_button_url"],
        ],
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar contenido</title>
    <link rel="stylesheet" href="content.css">
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; color: #1f2937; }
        .layout { min-height: 100vh; display: flex; align-items: flex-start; }
        .sidebar { width: 260px; background: #fff; border-right: 1px solid #e5e7eb; padding: 24px 18px; display: flex; flex-direction: column; gap: 22px; position: sticky; top: 0; height: 100vh; overflow-y: auto; flex-shrink: 0; }
        .brand { padding-bottom: 18px; border-bottom: 1px solid #e5e7eb; }
        .brand h2 { margin: 0; font-size: 22px; color: #198754; }
        .brand p { margin: 8px 0 0; color: #6b7280; font-size: 14px; line-height: 1.4; }
        .sidebar-section-title { margin: 0 0 10px; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: .08em; color: #9ca3af; }
        .nav { display: flex; flex-direction: column; gap: 8px; }
        .nav a { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #374151; padding: 11px 12px; border-radius: 10px; transition: background .2s ease, color .2s ease; }
        .nav a:hover { background: #eef8f2; color: #198754; }
        .nav a.active { background: #e9f7ef; color: #198754; font-weight: bold; }
        .nav-icon { width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; }
        .main { flex: 1; padding: 32px; min-width: 0; }
        .topbar { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 24px; }
        .page-title { margin: 0; font-size: 34px; line-height: 1.1; }
        .page-subtitle { margin: 10px 0 0; font-size: 16px; color: #6b7280; max-width: 760px; }
        .topbar-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 10px 16px; border-radius: 10px; text-decoration: none; border: 0; cursor: pointer; font-size: 14px; transition: background .2s ease, transform .2s ease; }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: #198754; color: #fff; }
        .btn-primary:hover { background: #157347; }
        .btn-outline { background: #fff; color: #198754; border: 1px solid #cfe7d8; }
        .btn-outline:hover { background: #eef8f2; }
        .btn-logout { background: #dc3545; color: #fff; }
        .btn-logout:hover { background: #bb2d3b; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; box-shadow: 0 8px 24px rgba(0,0,0,.04); margin-bottom: 18px; }
        .card h2 { margin: 0 0 10px; font-size: 22px; }
        .card p { margin: 0 0 18px; line-height: 1.6; color: #6b7280; }
        .flash-message { border-radius: 14px; padding: 14px 16px; margin-bottom: 18px; border: 1px solid #e5e7eb; }
        .flash-success { background: #e9f7ef; border-color: #cfe7d8; color: #146c43; }
        .flash-error { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
        .section-block { border: 1px solid #e5e7eb; border-radius: 14px; padding: 18px; margin-bottom: 16px; background: #f9fafb; }
        .repeater-after-certifications { margin-top: 24px; background: #fff; }
        .repeater-after-certifications .card { background: #f9fafb; }
        .hero-features-admin-section { background: #fff; }
        .hero-features-admin-section .card { background: #f9fafb; }
        .section-block h3 { margin: 0 0 8px; font-size: 18px; }
        .section-groups { display: grid; gap: 16px; }
        .content-subgroup { background: #fff; border: 1px solid #dbe4dc; border-radius: 14px; padding: 18px; }
        .content-subgroup-intro { padding-top: 14px; }
        .content-subgroup h4 { margin: 0 0 6px; font-size: 17px; color: #1f2937; }
        .content-subgroup-intro h4 { margin-bottom: 4px; }
        .content-subgroup p { margin: 0 0 16px; color: #6b7280; font-size: 14px; line-height: 1.5; }
        .content-subgroup-intro p { margin-bottom: 10px; }
        .field-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px 20px; }
        .content-subgroup-intro .field-grid { gap: 12px 20px; }
        .field-group { display: flex; flex-direction: column; gap: 8px; }
        .content-subgroup-intro .field-group { gap: 6px; }
        .field-group-full { grid-column: 1 / -1; }
        .field-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; min-height: 28px; }
        .field-header-actions { justify-content: flex-end; }
        .field-label { font-size: 14px; font-weight: bold; color: #374151; margin: 0; }
        .form-input, .form-textarea, .form-select { width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 10px 12px; font-size: 14px; font-family: inherit; font-weight: inherit; line-height: 1.5; background: #fff; }
        .form-textarea { min-height: 110px; resize: vertical; }
        .button-groups { display: grid; gap: 16px; }
        .button-link-card { border: 1px solid #dbe4dc; border-radius: 14px; padding: 16px; background: #f9fafb; display: grid; gap: 14px; }
        .button-link-card h5 { margin: 0; font-size: 16px; color: #1f2937; }
        .button-destination-box { border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; background: #fff; display: grid; gap: 12px; }
        .button-destination-grid { display: grid; gap: 12px; }
        .image-groups { display: grid; gap: 16px; }
        .image-section-card { border: 1px solid #dbe4dc; border-radius: 14px; padding: 16px; background: #f9fafb; display: grid; gap: 14px; }
        .image-section-card h5 { margin: 0; font-size: 16px; color: #1f2937; }
        .image-section-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .image-items { display: grid; gap: 14px; }
        .image-item-card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; background: #fff; display: grid; gap: 12px; }
        .image-item-card h6 { margin: 0; font-size: 14px; color: #374151; }
        .image-item-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .is-hidden { display: none; }
        .toggle-row { display: inline-flex; align-items: center; gap: 8px; min-height: 0; font-size: 13px; color: #4b5563; white-space: nowrap; padding: 4px 8px; border-radius: 999px; background: #f3f4f6; border: 1px solid #e5e7eb; }
        .toggle-row input { margin: 0; }
        .item-title { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 14px; }
        .about-stats-section .item-title h3 { margin: 0; font-size: 16px; color: #1f2937; }
        .preview-image { max-width: 180px; border: 1px solid #d1d5db; border-radius: 12px; padding: 6px; background: #fff; }
        .preview-image.is-empty { display: none; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 22px; }
        .muted { color: #6b7280; }
        @media (max-width: 991px) {
            .layout { flex-direction: column; }
            .sidebar { width: 100%; border-right: 0; border-bottom: 1px solid #e5e7eb; padding: 18px; position: static; top: auto; height: auto; overflow-y: visible; }
            .main { padding: 22px; }
            .topbar { flex-direction: column; align-items: stretch; }
            .topbar-actions { justify-content: flex-start; }
        }
        @media (max-width: 720px) {
            .field-grid { grid-template-columns: 1fr; }
            .main { padding: 16px; }
            .sidebar { padding: 16px; }
            .card { padding: 18px; }
            .page-title { font-size: 28px; }
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
                    <a href="../dashboard.php"><span class="nav-icon">&#127968;</span><span>Panel de inicio</span></a>
                    <a href="../menu/index.php"><span class="nav-icon">&#128203;</span><span>Men&uacute; de navegaci&oacute;n</span></a>
                </nav>
            </div>

            <div>
                <p class="sidebar-section-title">Contenido</p>
                <nav class="nav">
                    <a href="index.php" class="active"><span class="nav-icon">&#128196;</span><span>P&aacute;ginas del sitio</span></a>
                </nav>
            </div>
        </aside>

        <main class="main">
            <div class="topbar">
                <div>
                    <h1 class="page-title">Editar contenido</h1>
                    <p class="page-subtitle">
                        <?php if ($page): ?>
                            Gestiona el cuerpo editable de <span class="muted"><?php echo htmlspecialchars((string) ($page["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></span> seg&uacute;n su plantilla actual.
                        <?php else: ?>
                            No fue posible cargar la p&aacute;gina solicitada.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="topbar-actions">
                    <?php if ($page): ?>
                        <a href="edit.php?id=<?php echo (int) $page["id"]; ?>" class="btn btn-outline">Configuraci&oacute;n base</a>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-outline">Volver a p&aacute;ginas</a>
                    <form action="../logout.php" method="post" style="margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">
                        <button type="submit" class="btn btn-logout">Cerrar sesi&oacute;n</button>
                    </form>
                </div>
            </div>

            <?php if ($successMessage !== ""): ?>
                <div class="flash-message flash-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, "UTF-8"); ?></div>
            <?php endif; ?>

            <?php if ($errors !== []): ?>
                <div class="flash-message flash-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($page && $schema): ?>
                <section class="card">
                    <h2>Contenido de la plantilla <?php echo htmlspecialchars((string) ($schema["template_name"] ?? $templateKey), ENT_QUOTES, "UTF-8"); ?></h2>
                    <p>Los bloques repetibles tienen cantidad fija definida por la plantilla. Aqu&iacute; solo puedes editar valores y mostrar u ocultar cada elemento.</p>

                    <form action="content.php?id=<?php echo (int) $page["id"]; ?>" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">

                        <div class="section-block<?php echo $sectionClass !== "" ? " " . htmlspecialchars($sectionClass, ENT_QUOTES, "UTF-8") : ""; ?><?php echo $repeaterKey === "hero_features" ? " hero-features-admin-section" : ""; ?>">
                            <h3>Contenido b&aacute;sico</h3>
                            <?php if ($simpleFieldGroups !== []): ?>
                                <div class="section-groups">
                                    <?php foreach ($simpleFieldGroups as $groupConfig): ?>
                                        <?php $isIntroGroup = ((string) ($groupConfig["title"] ?? "")) === "Texto introductorio"; ?>
                                        <div class="content-subgroup <?php echo $isIntroGroup ? "content-subgroup-intro" : ""; ?>">
                                            <?php if (!$isIntroGroup): ?>
                                                <h4><?php echo htmlspecialchars((string) ($groupConfig["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></h4>
                                            <?php endif; ?>
                                            <?php if (!$isIntroGroup && ((string) ($groupConfig["description"] ?? "")) !== ""): ?>
                                                <p><?php echo htmlspecialchars((string) $groupConfig["description"], ENT_QUOTES, "UTF-8"); ?></p>
                                            <?php endif; ?>

                                            <?php
                                            $groupRenderMode = (string) ($groupConfig["render_mode"] ?? "");
                                            $groupButtonFieldGroups = isset($groupConfig["button_groups"]) && is_array($groupConfig["button_groups"]) ? $groupConfig["button_groups"] : $buttonFieldGroups;
                                            $groupImageFieldGroups = isset($groupConfig["image_groups"]) && is_array($groupConfig["image_groups"]) ? $groupConfig["image_groups"] : $imageFieldGroups;
                                            $isButtonsGroup = $groupRenderMode === "buttons" || ((string) ($groupConfig["title"] ?? "")) === "Botones";
                                            $isImagesGroup = $groupRenderMode === "images" || ((string) ($groupConfig["title"] ?? "")) === "Imágenes";
                                            ?>
                                            <?php if ($isButtonsGroup): ?>
                                                <div class="button-groups">
                                                    <?php foreach ($groupButtonFieldGroups as $buttonConfig): ?>
                                                        <?php
                                                        $textKey = (string) $buttonConfig["text_key"];
                                                        $linkTypeKey = (string) $buttonConfig["link_type_key"];
                                                        $pageIdKey = (string) $buttonConfig["page_id_key"];
                                                        $urlKey = (string) $buttonConfig["url_key"];
                                                        $textConfig = null;
                                                        $linkTypeConfig = null;
                                                        $pageIdConfig = null;
                                                        $urlConfig = null;

                                                        foreach ($schema["simple_fields"] as $simpleFieldConfig) {
                                                            $simpleFieldKey = (string) ($simpleFieldConfig["field_key"] ?? "");

                                                            if ($simpleFieldKey === $textKey) {
                                                                $textConfig = $simpleFieldConfig;
                                                            } elseif ($simpleFieldKey === $linkTypeKey) {
                                                                $linkTypeConfig = $simpleFieldConfig;
                                                            } elseif ($simpleFieldKey === $pageIdKey) {
                                                                $pageIdConfig = $simpleFieldConfig;
                                                            } elseif ($simpleFieldKey === $urlKey) {
                                                                $urlConfig = $simpleFieldConfig;
                                                            }
                                                        }

                                                        if (!is_array($textConfig) || !is_array($linkTypeConfig) || !is_array($pageIdConfig) || !is_array($urlConfig)) {
                                                            continue;
                                                        }

                                                        $textData = $contentData["simple_fields"][$textKey] ?? null;
                                                        $linkTypeData = $contentData["simple_fields"][$linkTypeKey] ?? null;
                                                        $pageIdData = $contentData["simple_fields"][$pageIdKey] ?? null;
                                                        $urlData = $contentData["simple_fields"][$urlKey] ?? null;
                                                        $textValue = (string) ($textData["field_value"] ?? "");
                                                        $textVisible = (int) ($textData["is_visible"] ?? 1) === 1;
                                                        $linkTypeValue = trim((string) ($linkTypeData["field_value"] ?? "custom"));
                                                        $pageIdValue = trim((string) ($pageIdData["field_value"] ?? ""));
                                                        $urlValue = (string) ($urlData["field_value"] ?? "");

                                                        if ($linkTypeValue !== "internal" && $linkTypeValue !== "custom") {
                                                            $linkTypeValue = $pageIdValue !== "" ? "internal" : "custom";
                                                        }
                                                        ?>
                                                        <div class="button-link-card">
                                                            <h5><?php echo escapeAdminFieldLabel((string) ($buttonConfig["title"] ?? "Botón")); ?></h5>

                                                            <div class="field-group">
                                                                <div class="field-header">
                                                                    <?php $buttonTextLabel = escapeAdminFieldLabel((string) ($textConfig["label"] ?? $textKey)); ?>
                                                                    <?php if ($templateKey === "home" && $textKey === "hero_primary_cta_text") { $buttonTextLabel = "Texto del botón principal"; } elseif ($templateKey === "home" && $textKey === "hero_secondary_cta_text") { $buttonTextLabel = "Texto del botón secundario"; } ?>
                                                                    <label class="field-label" for="simple_<?php echo htmlspecialchars($textKey, ENT_QUOTES, "UTF-8"); ?>"><?php echo $buttonTextLabel; ?></label>
                                                                    <label class="toggle-row">
                                                                        <input type="checkbox" name="simple_fields[<?php echo htmlspecialchars($textKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1"<?php echo $textVisible ? " checked" : ""; ?>>
                                                                        <span>Mostrar</span>
                                                                    </label>
                                                                </div>
                                                                <input class="form-input" type="text" id="simple_<?php echo htmlspecialchars($textKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($textKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($textValue, ENT_QUOTES, "UTF-8"); ?>">
                                                            </div>

                                                            <div class="button-destination-box">
                                                                <input type="hidden" name="simple_fields[<?php echo htmlspecialchars($linkTypeKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1">
                                                                <input type="hidden" name="simple_fields[<?php echo htmlspecialchars($pageIdKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1">
                                                                <input type="hidden" name="simple_fields[<?php echo htmlspecialchars($urlKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1">

                                                                <div class="field-group field-group-full">
                                                                    <label class="field-label" for="simple_<?php echo htmlspecialchars($linkTypeKey, ENT_QUOTES, "UTF-8"); ?>">Tipo de enlace</label>
                                                                    <select class="form-select js-link-type" id="simple_<?php echo htmlspecialchars($linkTypeKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($linkTypeKey, ENT_QUOTES, "UTF-8"); ?>][value]" data-link-scope="<?php echo htmlspecialchars((string) array_search($buttonConfig, $groupButtonFieldGroups, true), ENT_QUOTES, "UTF-8"); ?>">
                                                                        <option value="internal"<?php echo $linkTypeValue === "internal" ? " selected" : ""; ?>>P&aacute;gina interna</option>
                                                                        <option value="custom"<?php echo $linkTypeValue === "custom" ? " selected" : ""; ?>>URL personalizada</option>
                                                                    </select>
                                                                </div>

                                                                <div class="button-destination-grid">
                                                                    <div class="field-group field-group-full js-link-panel <?php echo $linkTypeValue === "internal" ? "" : "is-hidden"; ?>" data-link-panel="internal" data-link-scope="<?php echo htmlspecialchars((string) array_search($buttonConfig, $groupButtonFieldGroups, true), ENT_QUOTES, "UTF-8"); ?>">
                                                                        <label class="field-label" for="simple_<?php echo htmlspecialchars($pageIdKey, ENT_QUOTES, "UTF-8"); ?>">P&aacute;gina interna</label>
                                                                        <select class="form-select" id="simple_<?php echo htmlspecialchars($pageIdKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($pageIdKey, ENT_QUOTES, "UTF-8"); ?>][value]">
                                                                            <option value="">Selecciona una p&aacute;gina</option>
                                                                            <?php foreach ($linkableSitePages as $sitePageOption): ?>
                                                                                <option value="<?php echo (int) ($sitePageOption["id"] ?? 0); ?>"<?php echo (string) ($sitePageOption["id"] ?? "") === $pageIdValue ? " selected" : ""; ?>><?php echo htmlspecialchars((string) ($sitePageOption["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>

                                                                    <div class="field-group field-group-full js-link-panel <?php echo $linkTypeValue === "custom" ? "" : "is-hidden"; ?>" data-link-panel="custom" data-link-scope="<?php echo htmlspecialchars((string) array_search($buttonConfig, $groupButtonFieldGroups, true), ENT_QUOTES, "UTF-8"); ?>">
                                                                        <label class="field-label" for="simple_<?php echo htmlspecialchars($urlKey, ENT_QUOTES, "UTF-8"); ?>">URL personalizada</label>
                                                                        <input class="form-input" type="text" id="simple_<?php echo htmlspecialchars($urlKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($urlKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($urlValue, ENT_QUOTES, "UTF-8"); ?>" placeholder="https://wa.me/... o https://youtube.com/...">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php elseif ($isImagesGroup): ?>
                                                <div class="image-groups">
                                                    <?php foreach ($groupImageFieldGroups as $imageGroupConfig): ?>
                                                        <div class="image-section-card">
                                                            <?php if (count((array) ($imageGroupConfig["items"] ?? [])) === 1): ?>
                                                                <?php $imageItemConfig = $imageGroupConfig["items"][0] ?? []; ?>
                                                                <?php
                                                                $imageKey = (string) ($imageItemConfig["image_key"] ?? "");
                                                                $altKey = (string) ($imageItemConfig["alt_key"] ?? "");
                                                                $imageFieldConfig = null;
                                                                $altFieldConfig = null;
                                                                foreach ($schema["simple_fields"] as $simpleFieldConfig) {
                                                                    $simpleFieldKey = (string) ($simpleFieldConfig["field_key"] ?? "");
                                                                    if ($simpleFieldKey === $imageKey) {
                                                                        $imageFieldConfig = $simpleFieldConfig;
                                                                    } elseif ($simpleFieldKey === $altKey) {
                                                                        $altFieldConfig = $simpleFieldConfig;
                                                                    }
                                                                }
                                                                if (!is_array($imageFieldConfig) || !is_array($altFieldConfig)) {
                                                                    continue;
                                                                }
                                                                $imageFieldData = $contentData["simple_fields"][$imageKey] ?? null;
                                                                $altFieldData = $contentData["simple_fields"][$altKey] ?? null;
                                                                $imageValue = (string) ($imageFieldData["field_value"] ?? "");
                                                                $imageVisible = (int) ($imageFieldData["is_visible"] ?? 1) === 1;
                                                                $altValue = (string) ($altFieldData["field_value"] ?? "");
                                                                $altVisible = (int) ($altFieldData["is_visible"] ?? 1) === 1;
                                                                ?>
                                                                <div class="image-section-head">
                                                                    <h5><?php echo escapeAdminFieldLabel((string) ($imageGroupConfig["title"] ?? "Imágenes")); ?></h5>
                                                                    <label class="toggle-row">
                                                                        <input type="checkbox" name="simple_fields[<?php echo htmlspecialchars($imageKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1"<?php echo $imageVisible ? " checked" : ""; ?>>
                                                                        <span>Mostrar</span>
                                                                    </label>
                                                                </div>
                                                                <div class="field-grid">
                                                                    <div class="field-group">
                                                                        <input type="hidden" name="simple_fields[<?php echo htmlspecialchars($imageKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($imageValue, ENT_QUOTES, "UTF-8"); ?>">
                                                                        <div class="current-file"><strong>Archivo actual:</strong> <?php echo htmlspecialchars($imageValue !== "" ? basename($imageValue) : "Sin imagen seleccionada", ENT_QUOTES, "UTF-8"); ?></div>
                                                                        <label class="file-input-label" for="simple_file_<?php echo htmlspecialchars($imageKey, ENT_QUOTES, "UTF-8"); ?>">Reemplazar imagen</label>
                                                                        <input class="form-file js-image-upload" type="file" id="simple_file_<?php echo htmlspecialchars($imageKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($imageKey, ENT_QUOTES, "UTF-8"); ?>][upload]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-preview-target="preview_<?php echo htmlspecialchars($imageKey, ENT_QUOTES, "UTF-8"); ?>">
                                                                        <img id="preview_<?php echo htmlspecialchars($imageKey, ENT_QUOTES, "UTF-8"); ?>" src="<?php echo $imageValue !== "" ? "../../" . htmlspecialchars(ltrim($imageValue, "/"), ENT_QUOTES, "UTF-8") : ""; ?>" alt="" class="preview-image<?php echo $imageValue !== "" ? "" : " is-empty"; ?>">
                                                                    </div>
                                                                    <div class="field-group">
                                                                        <?php $altLabelHtml = escapeAdminFieldLabel((string) ($altFieldConfig["label"] ?? $altKey)); ?>
                                                                        <?php if ($templateKey === "home" && $altKey === "hero_image_alt") { $altLabelHtml = "Texto alternativo imagen principal"; } ?>
                                                                        <label class="field-label" for="simple_<?php echo htmlspecialchars($altKey, ENT_QUOTES, "UTF-8"); ?>"><?php echo $altLabelHtml; ?></label>
                                                                        <input class="form-input" type="text" id="simple_<?php echo htmlspecialchars($altKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($altKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($altValue, ENT_QUOTES, "UTF-8"); ?>">
                                                                    </div>
                                                                </div>
                                                            <?php else: ?>
                                                                <h5><?php echo escapeAdminFieldLabel((string) ($imageGroupConfig["title"] ?? "Imágenes")); ?></h5>
                                                                <div class="image-items">
                                                                    <?php foreach (($imageGroupConfig["items"] ?? []) as $imageItemConfig): ?>
                                                                        <?php
                                                                        $itemTitle = (string) ($imageItemConfig["item_title"] ?? "Imagen");
                                                                        $imageKey = (string) ($imageItemConfig["image_key"] ?? "");
                                                                        $altKey = (string) ($imageItemConfig["alt_key"] ?? "");
                                                                        $imageFieldConfig = null;
                                                                        $altFieldConfig = null;
                                                                        foreach ($schema["simple_fields"] as $simpleFieldConfig) {
                                                                            $simpleFieldKey = (string) ($simpleFieldConfig["field_key"] ?? "");
                                                                            if ($simpleFieldKey === $imageKey) {
                                                                                $imageFieldConfig = $simpleFieldConfig;
                                                                            } elseif ($simpleFieldKey === $altKey) {
                                                                                $altFieldConfig = $simpleFieldConfig;
                                                                            }
                                                                        }
                                                                        if (!is_array($imageFieldConfig) || !is_array($altFieldConfig)) {
                                                                            continue;
                                                                        }
                                                                        $imageFieldData = $contentData["simple_fields"][$imageKey] ?? null;
                                                                        $altFieldData = $contentData["simple_fields"][$altKey] ?? null;
                                                                        $imageValue = (string) ($imageFieldData["field_value"] ?? "");
                                                                        $imageVisible = (int) ($imageFieldData["is_visible"] ?? 1) === 1;
                                                                        $altValue = (string) ($altFieldData["field_value"] ?? "");
                                                                        $altVisible = (int) ($altFieldData["is_visible"] ?? 1) === 1;
                                                                        ?>
                                                                        <div class="image-item-card">
                                                                            <div class="image-item-head">
                                                                                <h6><?php echo escapeAdminFieldLabel($itemTitle); ?></h6>
                                                                                <label class="toggle-row">
                                                                                    <input type="checkbox" name="simple_fields[<?php echo htmlspecialchars($imageKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1"<?php echo $imageVisible ? " checked" : ""; ?>>
                                                                                    <span>Mostrar</span>
                                                                                </label>
                                                                            </div>
                                                                            <div class="field-grid">
                                                                                <div class="field-group">
                                                                                    <input type="hidden" name="simple_fields[<?php echo htmlspecialchars($imageKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($imageValue, ENT_QUOTES, "UTF-8"); ?>">
                                                                                    <div class="current-file"><strong>Archivo actual:</strong> <?php echo htmlspecialchars($imageValue !== "" ? basename($imageValue) : "Sin imagen seleccionada", ENT_QUOTES, "UTF-8"); ?></div>
                                                                                    <label class="file-input-label" for="simple_file_<?php echo htmlspecialchars($imageKey, ENT_QUOTES, "UTF-8"); ?>">Reemplazar imagen</label>
                                                                                    <input class="form-file js-image-upload" type="file" id="simple_file_<?php echo htmlspecialchars($imageKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($imageKey, ENT_QUOTES, "UTF-8"); ?>][upload]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-preview-target="preview_<?php echo htmlspecialchars($imageKey, ENT_QUOTES, "UTF-8"); ?>">
                                                                                    <?php if ($imageValue !== ""): ?>
                                                                                        <img src="../../<?php echo htmlspecialchars(ltrim($imageValue, "/"), ENT_QUOTES, "UTF-8"); ?>" alt="" class="preview-image">
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                                <div class="field-group">
                                                                                    <label class="field-label" for="simple_<?php echo htmlspecialchars($altKey, ENT_QUOTES, "UTF-8"); ?>"><?php echo escapeAdminFieldLabel((string) ($altFieldConfig["label"] ?? $altKey)); ?></label>
                                                                                    <input class="form-input" type="text" id="simple_<?php echo htmlspecialchars($altKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($altKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($altValue, ENT_QUOTES, "UTF-8"); ?>">
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="field-grid">
                                                    <?php foreach ($groupConfig["field_keys"] as $groupFieldKey): ?>
                                                        <?php
                                                        $fieldConfig = null;

                                                        foreach ($schema["simple_fields"] as $simpleFieldConfig) {
                                                            if ((string) ($simpleFieldConfig["field_key"] ?? "") === $groupFieldKey) {
                                                                $fieldConfig = $simpleFieldConfig;
                                                                break;
                                                            }
                                                        }

                                                        if (!is_array($fieldConfig)) {
                                                            continue;
                                                        }

                                                        $fieldData = $contentData["simple_fields"][$groupFieldKey] ?? null;
                                                        $fieldType = (string) ($fieldConfig["field_type"] ?? "text");
                                                        $fieldValue = (string) ($fieldData["field_value"] ?? "");
                                                        $fieldVisible = (int) ($fieldData["is_visible"] ?? 1) === 1;
                                                        $isTextarea = $fieldType === "textarea";
                                                        $isImage = $fieldType === "image";
                                                        $displayLabel = (string) ($fieldConfig["label"] ?? $groupFieldKey);
                                                        $displayLabelHtml = escapeAdminFieldLabel($displayLabel);

                                                        if ($templateKey === "home" && $groupFieldKey === "hero_badge") {
                                                            $displayLabelHtml = "Etiqueta destacada";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "hero_title") {
                                                            $displayLabelHtml = "Título principal";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "hero_text") {
                                                            $displayLabelHtml = "Texto principal";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "hero_emergency_label") {
                                                            $displayLabelHtml = "Texto informativo 1";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "hero_emergency_value") {
                                                            $displayLabelHtml = "Dato informativo 1";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "hero_hours_label") {
                                                            $displayLabelHtml = "Texto informativo 2";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "hero_hours_value") {
                                                            $displayLabelHtml = "Dato informativo 2";
                                                        } elseif ($isIntroGroup && $groupFieldKey === "intro_text_1") {
                                                            $displayLabelHtml = "P&aacute;rrafo 1";
                                                        } elseif ($isIntroGroup && $groupFieldKey === "intro_text_2") {
                                                            $displayLabelHtml = "P&aacute;rrafo 2";
                                                        }
                                                        ?>
                                                        <div class="field-group <?php echo $isTextarea ? "field-group-full" : ""; ?>">
                                                            <div class="field-header">
                                                                <label class="field-label" for="simple_<?php echo htmlspecialchars($groupFieldKey, ENT_QUOTES, "UTF-8"); ?>"><?php echo $displayLabelHtml; ?></label>
                                                                <label class="toggle-row">
                                                                    <input type="checkbox" name="simple_fields[<?php echo htmlspecialchars($groupFieldKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1"<?php echo $fieldVisible ? " checked" : ""; ?>>
                                                                    <span>Mostrar</span>
                                                                </label>
                                                            </div>

                                                            <?php if ($isTextarea): ?>
                                                                <textarea class="form-textarea" id="simple_<?php echo htmlspecialchars($groupFieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($groupFieldKey, ENT_QUOTES, "UTF-8"); ?>][value]"><?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?></textarea>
                                                            <?php elseif ($isImage): ?>
                                                                <input type="hidden" name="simple_fields[<?php echo htmlspecialchars($groupFieldKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                                                <div class="current-file"><strong>Archivo actual:</strong> <?php echo htmlspecialchars($fieldValue !== "" ? basename($fieldValue) : "Sin imagen seleccionada", ENT_QUOTES, "UTF-8"); ?></div>
                                                                <label class="file-input-label" for="simple_file_<?php echo htmlspecialchars($groupFieldKey, ENT_QUOTES, "UTF-8"); ?>">Reemplazar imagen</label>
                                                                <input class="form-file js-image-upload" type="file" id="simple_file_<?php echo htmlspecialchars($groupFieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($groupFieldKey, ENT_QUOTES, "UTF-8"); ?>][upload]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-preview-target="preview_<?php echo htmlspecialchars($groupFieldKey, ENT_QUOTES, "UTF-8"); ?>">
                                                            <?php else: ?>
                                                                <input class="form-input" type="text" id="simple_<?php echo htmlspecialchars($groupFieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($groupFieldKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                                            <?php endif; ?>

                                                            <?php if ($isImage): ?>
                                                                <img id="preview_<?php echo htmlspecialchars($groupFieldKey, ENT_QUOTES, "UTF-8"); ?>" src="<?php echo $fieldValue !== "" ? "../../" . htmlspecialchars(ltrim($fieldValue, "/"), ENT_QUOTES, "UTF-8") : ""; ?>" alt="" class="preview-image<?php echo $fieldValue !== "" ? "" : " is-empty"; ?>">
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                            <?php if ($isIntroGroup && is_array($aboutStatsRepeaterConfig)): ?>
                                                <?php renderAdminRepeaterSection($aboutStatsRepeaterConfig, $contentData, "about-stats-section"); ?>
                                            <?php endif; ?>
                                            <?php if ($templateKey === "home" && ((string) ($groupConfig["title"] ?? "")) === "Portada - Datos informativos"): ?>
                                                <?php foreach ($schema["repeaters"] as $repeaterConfig): ?>
                                                    <?php if (((string) ($repeaterConfig["repeater_key"] ?? "")) === "hero_features"): ?>
                                                        <?php renderAdminRepeaterSection($repeaterConfig, $contentData); ?>
                                                        <?php break; ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="field-grid">
                                    <?php foreach ($schema["simple_fields"] as $fieldConfig): ?>
                                        <?php
                                        $fieldKey = (string) $fieldConfig["field_key"];
                                        $fieldData = $contentData["simple_fields"][$fieldKey] ?? null;
                                        $fieldType = (string) ($fieldConfig["field_type"] ?? "text");
                                        $fieldValue = (string) ($fieldData["field_value"] ?? "");
                                        $fieldVisible = (int) ($fieldData["is_visible"] ?? 1) === 1;
                                        $isTextarea = $fieldType === "textarea";
                                        $isImage = $fieldType === "image";
                                        ?>
                                        <div class="field-group <?php echo $isTextarea ? "field-group-full" : ""; ?>">
                                            <div class="field-header">
                                                <label class="field-label" for="simple_<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>"><?php echo htmlspecialchars((string) ($fieldConfig["label"] ?? $fieldKey), ENT_QUOTES, "UTF-8"); ?></label>
                                                <label class="toggle-row">
                                                    <input type="checkbox" name="simple_fields[<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1"<?php echo $fieldVisible ? " checked" : ""; ?>>
                                                    <span>Mostrar</span>
                                                </label>
                                            </div>

                                            <?php if ($isTextarea): ?>
                                                <textarea class="form-textarea" id="simple_<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>][value]"><?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?></textarea>
                                            <?php elseif ($isImage): ?>
                                                <input type="hidden" name="simple_fields[<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                                <div class="current-file"><strong>Archivo actual:</strong> <?php echo htmlspecialchars($fieldValue !== "" ? basename($fieldValue) : "Sin imagen seleccionada", ENT_QUOTES, "UTF-8"); ?></div>
                                                <label class="file-input-label" for="simple_file_<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>">Reemplazar imagen</label>
                                                <input class="form-file js-image-upload" type="file" id="simple_file_<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>][upload]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-preview-target="preview_<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>">
                                            <?php else: ?>
                                                <input class="form-input" type="text" id="simple_<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                            <?php endif; ?>

                                            <?php if ($isImage): ?>
                                                <img id="preview_<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>" src="<?php echo $fieldValue !== "" ? "../../" . htmlspecialchars(ltrim($fieldValue, "/"), ENT_QUOTES, "UTF-8") : ""; ?>" alt="" class="preview-image<?php echo $fieldValue !== "" ? "" : " is-empty"; ?>">
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php foreach ($schema["repeaters"] as $repeaterIndex => $repeaterConfig): ?>
                            <?php if (is_array($aboutStatsRepeaterConfig) && $repeaterIndex === 0): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <?php if ($templateKey === "home" && ((string) ($repeaterConfig["repeater_key"] ?? "")) === "hero_features"): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <?php renderAdminRepeaterSection($repeaterConfig, $contentData, ($templateKey === "about" && $repeaterIndex === 1) ? "repeater-after-certifications" : ""); ?>
                        <?php endforeach; ?>

                        <div class="actions">
                            <button type="submit" class="btn btn-primary">Guardar contenido</button>
                            <a href="edit.php?id=<?php echo (int) $page["id"]; ?>" class="btn btn-outline">Volver a configuraci&oacute;n base</a>
                        </div>
                    </form>
                </section>
            <?php endif; ?>
        </main>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var selectors = document.querySelectorAll(".js-link-type");

            selectors.forEach(function (selector) {
                var syncPanels = function () {
                    var scope = selector.getAttribute("data-link-scope");
                    var value = selector.value === "internal" ? "internal" : "custom";
                    var panels = document.querySelectorAll('.js-link-panel[data-link-scope="' + scope + '"]');

                    panels.forEach(function (panel) {
                        panel.classList.toggle("is-hidden", panel.getAttribute("data-link-panel") !== value);
                    });
                };

                selector.addEventListener("change", syncPanels);
                syncPanels();
            });

            var imageInputs = document.querySelectorAll(".js-image-upload");

            imageInputs.forEach(function (input) {
                input.addEventListener("change", function () {
                    var targetId = input.getAttribute("data-preview-target");
                    var preview = targetId ? document.getElementById(targetId) : null;
                    var file = input.files && input.files[0] ? input.files[0] : null;

                    if (!preview || !file) {
                        return;
                    }

                    if (file.type && file.type.indexOf("image/") !== 0) {
                        return;
                    }

                    var reader = new FileReader();
                    reader.onload = function (event) {
                        preview.src = String(event.target && event.target.result ? event.target.result : "");
                        preview.classList.remove("is-empty");
                    };
                    reader.readAsDataURL(file);
                });
            });
        });
    </script>
</body>
</html>

















































