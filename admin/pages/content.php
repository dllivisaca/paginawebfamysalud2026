﻿﻿﻿﻿﻿<?php
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
function renderAdminRepeaterSection(array $repeaterConfig, array $contentData, string $sectionClass = "", ?string $sectionTitle = null, string $servicesItemMode = ""): void
{
    $repeaterKey = (string) ($repeaterConfig["repeater_key"] ?? "");
    $repeaterItems = $contentData["repeaters"][$repeaterKey] ?? [];
    $renderItems = $repeaterConfig["items"] ?? [];
    $serviceCategoryOptions = [];

    foreach (($contentData["repeaters"]["service_categories"] ?? []) as $serviceCategoryItem) {
        $categoryKey = trim((string) ($serviceCategoryItem["fields"]["category_key"]["field_value"] ?? ""));
        $categoryLabel = trim((string) ($serviceCategoryItem["fields"]["label"]["field_value"] ?? ""));

        if ($categoryKey !== "") {
            $serviceCategoryOptions[$categoryKey] = $categoryLabel !== "" ? $categoryLabel : $categoryKey;
        }
    }
    if ($repeaterKey === "departments") {
        $featuredRenderItem = null;
        $normalRenderItems = [];
        foreach ($renderItems as $renderItemConfig) {
            $renderItemIndex = (int) ($renderItemConfig["item_index"] ?? -1);
            $renderItemData = $repeaterItems[$renderItemIndex] ?? ["fields" => []];
            $renderLayoutVariant = strtolower(trim((string) ($renderItemData["fields"]["layout_variant"]["field_value"] ?? "")));
            if ($featuredRenderItem === null && $renderLayoutVariant === "featured") {
                $featuredRenderItem = $renderItemConfig;
                continue;
            }
            $normalRenderItems[] = $renderItemConfig;
        }
        $renderItems = $featuredRenderItem !== null ? array_merge([$featuredRenderItem], $normalRenderItems) : $normalRenderItems;
    }
    global $linkableSitePages, $templateKey;
    $contactSocialLinkIconOptions = [
        "bi bi-facebook" => "Facebook",
        "bi bi-twitter-x" => "Twitter X",
        "bi bi-instagram" => "Instagram",
        "bi bi-linkedin" => "LinkedIn",
        "bi bi-youtube" => "YouTube",
    ];
    $repeaterTitle = $sectionTitle ?? ($repeaterKey === "home_about_features" ? "Sobre nosotros - Caracteristicas destacadas" : ($repeaterKey === "featured_doctors" ? "Doctores destacados - tarjetas" : (string) ($repeaterConfig["label"] ?? $repeaterKey)));
    if ($templateKey === "department-details" && $repeaterKey === "service_cards") {
        $repeaterTitle = "Servicios destacados";
    } elseif ($templateKey === "department-details" && $repeaterKey === "stats") {
        $repeaterTitle = "Estadísticas";
    } elseif ($templateKey === "department-details" && $repeaterKey === "key_services") {
        $repeaterTitle = "Items destacados";
    }
    ?>
    <div class="section-block<?php echo $sectionClass !== "" ? " " . htmlspecialchars($sectionClass, ENT_QUOTES, "UTF-8") : ""; ?><?php echo $repeaterKey === "hero_features" ? " hero-features-admin-section" : ""; ?><?php echo $repeaterKey === "home_about_features" ? " home-about-features-admin-section" : ""; ?><?php echo $repeaterKey === "home_certifications" ? " home-certifications-admin-section" : ""; ?><?php echo $repeaterKey === "featured_departments" ? " featured-departments-admin-section" : ""; ?><?php echo $repeaterKey === "featured_services" ? " featured-services-admin-section" : ""; ?><?php echo $repeaterKey === "services" ? " services-admin-section" : ""; ?><?php echo $repeaterKey === "featured_doctors" ? " featured-doctors-admin-section" : ""; ?><?php echo $repeaterKey === "doctors" ? " doctors-admin-section" : ""; ?><?php echo $repeaterKey === "cta_features" ? " cta-features-admin-section" : ""; ?><?php echo $repeaterKey === "emergency_contacts" ? " emergency-contacts-admin-section" : ""; ?><?php echo $repeaterKey === "quick_actions" ? " quick-actions-admin-section" : ""; ?><?php echo $repeaterKey === "departments" ? " departments-admin-section" : ""; ?><?php echo $repeaterKey === "service_categories" ? " service-categories-admin-section" : ""; ?><?php echo $repeaterKey === "emergency_tips" ? " emergency-tips-admin-section" : ""; ?><?php echo $templateKey === "contact" && $repeaterKey === "social_links" ? " contact-social-links-admin-section" : ""; ?><?php echo $templateKey === "department-details" && $repeaterKey === "stats" ? " department-stats-admin-section" : ""; ?>">
        <h3><?php echo htmlspecialchars($repeaterTitle, ENT_QUOTES, "UTF-8"); ?></h3>

        <?php foreach ($renderItems as $itemConfig): ?>
            <?php
            $itemIndex = (int) $itemConfig["item_index"];
            $itemTitle = trim((string) ($itemConfig["item_label"] ?? ""));
            if ($itemTitle === "") {
                $itemTitle = "Estadística " . ($itemIndex + 1);
            }
            $itemData = $repeaterItems[$itemIndex] ?? ["fields" => [], "is_visible" => 1];
            $isEmergencyServiceItem = $repeaterKey === "services" && trim((string) ($itemData["fields"]["category_key"]["field_value"] ?? ($itemConfig["defaults"]["category_key"] ?? ""))) === "emergency";
            if ($servicesItemMode === "general" && $isEmergencyServiceItem) {
                continue;
            }
            if ($servicesItemMode === "emergency" && !$isEmergencyServiceItem) {
                continue;
            }
            if ($repeaterKey === "service_categories") {
                $serviceCategoryTitle = trim((string) ($itemData["fields"]["label"]["field_value"] ?? ""));
                if ($serviceCategoryTitle !== "") {
                    $itemTitle = $serviceCategoryTitle;
                }
            }
            if ($repeaterKey === "services") {
                $serviceTitle = trim((string) ($itemData["fields"]["title"]["field_value"] ?? ""));
                if ($serviceTitle !== "") {
                    $itemTitle = $serviceTitle;
                }
            }
            if ($repeaterKey === "doctors") {
                $doctorNameTitle = trim((string) ($itemData["fields"]["name"]["field_value"] ?? ""));
                if ($doctorNameTitle !== "") {
                    $itemTitle = $doctorNameTitle;
                }
            }
            if ($repeaterKey === "info_cards") {
                $contactInfoCardTitle = trim((string) ($itemData["fields"]["title"]["field_value"] ?? ""));
                if ($contactInfoCardTitle !== "") {
                    $itemTitle = $contactInfoCardTitle;
                }
            }
            if ($templateKey === "contact" && $repeaterKey === "social_links") {
                $contactSocialLinkIconClass = trim((string) ($itemData["fields"]["icon_class"]["field_value"] ?? ""));
                if (isset($contactSocialLinkIconOptions[$contactSocialLinkIconClass])) {
                    $itemTitle = $contactSocialLinkIconOptions[$contactSocialLinkIconClass];
                }
            }
            $itemVisible = (int) ($itemData["is_visible"] ?? 1) === 1;
            $departmentsHiddenFields = [];
            $repeaterFields = $repeaterConfig["fields"];
            if ($templateKey === "department-details" && $repeaterKey === "service_cards") {
                $departmentServiceCardFieldOrder = ["title", "icon_class", "text"];
                usort($repeaterFields, function ($leftField, $rightField) use ($departmentServiceCardFieldOrder) {
                    $leftFieldKey = (string) ($leftField["field_key"] ?? "");
                    $rightFieldKey = (string) ($rightField["field_key"] ?? "");
                    $leftPosition = array_search($leftFieldKey, $departmentServiceCardFieldOrder, true);
                    $rightPosition = array_search($rightFieldKey, $departmentServiceCardFieldOrder, true);

                    return ($leftPosition === false ? PHP_INT_MAX : $leftPosition) <=> ($rightPosition === false ? PHP_INT_MAX : $rightPosition);
                });
            }
            if ($repeaterKey === "departments") {
                $departmentsLayoutVariant = strtolower(trim((string) ($itemData["fields"]["layout_variant"]["field_value"] ?? "")));
                $departmentsTitleValue = trim((string) ($itemData["fields"]["title"]["field_value"] ?? ""));
                if ($departmentsLayoutVariant === "featured") {
                    $itemTitle = $departmentsTitleValue !== "" ? "Departamento destacado: " . $departmentsTitleValue : "Departamento destacado";
                } else {
                    $itemTitle = $departmentsTitleValue !== "" ? "Departamento estándar: " . $departmentsTitleValue : "Departamento estándar";
                }
                $departmentsHiddenFields = $departmentsLayoutVariant === "featured"
                    ? ["featured_badge_text", "stats_number", "stats_label", "feature_1", "feature_2", "feature_3"]
                    : ["featured_badge_text", "achievement_1_icon", "achievement_1_text", "achievement_2_icon", "achievement_2_text", "tag_1", "tag_2", "tag_3", "tag_4"];
                if ($departmentsLayoutVariant === "featured") {
                    $departmentsFeaturedFieldOrder = ["icon_class", "title", "subtitle", "image", "image_alt", "description", "achievement_1_icon", "achievement_1_text", "achievement_2_icon", "achievement_2_text", "tag_1", "tag_2", "tag_3", "tag_4", "button_text", "button_link_type", "button_page_id", "button_url"];
                    $departmentsOriginalFieldOrder = [];
                    foreach ($repeaterFields as $repeaterFieldIndex => $repeaterFieldConfig) {
                        $departmentsOriginalFieldOrder[(string) ($repeaterFieldConfig["field_key"] ?? "")] = $repeaterFieldIndex;
                    }
                    usort($repeaterFields, function ($leftField, $rightField) use ($departmentsFeaturedFieldOrder, $departmentsOriginalFieldOrder) {
                        $leftFieldKey = (string) ($leftField["field_key"] ?? "");
                        $rightFieldKey = (string) ($rightField["field_key"] ?? "");
                        $leftPosition = array_search($leftFieldKey, $departmentsFeaturedFieldOrder, true);
                        $rightPosition = array_search($rightFieldKey, $departmentsFeaturedFieldOrder, true);
                        $leftOriginalPosition = $departmentsOriginalFieldOrder[$leftFieldKey] ?? PHP_INT_MAX;
                        $rightOriginalPosition = $departmentsOriginalFieldOrder[$rightFieldKey] ?? PHP_INT_MAX;

                        return [($leftPosition === false ? PHP_INT_MAX : $leftPosition), $leftOriginalPosition] <=> [($rightPosition === false ? PHP_INT_MAX : $rightPosition), $rightOriginalPosition];
                    });
                }
            }
            ?>
            <div class="card">
                <div class="item-title">
                    <?php
                    $itemTitleAttributes = "";
                    if ($repeaterKey === "info_cards") {
                        $itemTitleAttributes = ' class="js-contact-info-card-title" data-contact-info-card-item="' . $itemIndex . '" data-contact-info-card-fallback="' . htmlspecialchars($itemTitle, ENT_QUOTES, "UTF-8") . '"';
                    } elseif ($templateKey === "contact" && $repeaterKey === "social_links") {
                        $itemTitleAttributes = ' class="js-contact-social-link-title" data-contact-social-link-item="' . $itemIndex . '" data-contact-social-link-fallback="' . htmlspecialchars($itemTitle, ENT_QUOTES, "UTF-8") . '"';
                    }
                    ?>
                    <?php if (!($templateKey === "department-details" && in_array($repeaterKey, ["service_cards", "stats", "key_services"], true))): ?>
                        <h3<?php echo $itemTitleAttributes; ?>><?php echo escapeAdminFieldLabel($itemTitle); ?></h3>
                    <?php endif; ?>
                    <?php if ($repeaterKey === "doctors"): ?>
                        <input type="hidden" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][is_visible]" value="<?php echo $itemVisible ? "1" : "0"; ?>">
                    <?php else: ?>
                        <label class="toggle-row">
                            <input type="checkbox" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][is_visible]" value="1"<?php echo $itemVisible ? " checked" : ""; ?>>
                            <span>Mostrar este bloque</span>
                        </label>
                    <?php endif; ?>
                </div>

                <div class="field-grid">
                    <?php foreach ($repeaterFields as $fieldConfig): ?>
                        <?php
                        $fieldKey = (string) ($fieldConfig["field_key"] ?? "");
                        $fieldType = (string) ($fieldConfig["field_type"] ?? "text");
                        $fieldValue = (string) (($itemData["fields"][$fieldKey]["field_value"] ?? ""));
                        $fieldVisible = (int) (($itemData["fields"][$fieldKey]["is_visible"] ?? 1)) === 1;
                        $hiddenGeneralServiceFields = [
                            "column_class",
                            "variant_class",
                            "emergency_button_text",
                            "emergency_button_icon",
                            "emergency_button_link_type",
                            "emergency_button_page_id",
                            "emergency_button_url",
                            "directions_button_text",
                            "directions_button_icon",
                            "directions_button_link_type",
                            "directions_button_page_id",
                            "directions_button_url",
                        ];
                        $hiddenEmergencyServiceFields = [
                            "column_class",
                            "variant_class",
                            "link_text",
                            "link_type",
                            "page_id",
                            "link_url",
                        ];

                        if ($repeaterKey === "services" && $servicesItemMode === "general" && in_array($fieldKey, $hiddenGeneralServiceFields, true)) {
                            ?>
                            <input type="hidden" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">
                            <?php
                            continue;
                        }
                        if ($repeaterKey === "services" && $servicesItemMode === "emergency" && in_array($fieldKey, $hiddenEmergencyServiceFields, true)) {
                            ?>
                            <input type="hidden" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">
                            <?php
                            continue;
                        }
                        if ($repeaterKey === "service_categories" && $fieldKey === "category_key") {
                            ?>
                            <input type="hidden" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">
                            <?php
                            continue;
                        }
                        if ($repeaterKey === "departments" && $fieldKey === "layout_variant") {
                            $layoutVariantValue = $fieldValue === "featured" ? "featured" : "card";
                            ?>
                            <input type="hidden" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>]" value="<?php echo htmlspecialchars($layoutVariantValue, ENT_QUOTES, "UTF-8"); ?>">
                            <?php
                            continue;
                        }
                        if ($repeaterKey === "departments" && in_array($fieldKey, $departmentsHiddenFields, true)) {
                            ?>
                            <input type="hidden" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">
                            <?php
                            continue;
                        }
                        if ($templateKey === "department-details" && $repeaterKey === "key_services" && $fieldKey !== "text") {
                            continue;
                        }
                        $fieldLabel = (string) ($fieldConfig["label"] ?? $fieldKey);
                        if ($repeaterKey === "featured_doctors") {
                            if ($fieldKey === "alt") {
                                $fieldLabel = "Texto alternativo de la imagen";
                            } elseif ($fieldKey === "name") {
                                $fieldLabel = "Nombre del doctor";
                            } elseif ($fieldKey === "experience") {
                                $fieldLabel = "Texto de experiencia";
                            } elseif ($fieldKey === "rating") {
                                $fieldLabel = "Calificación";
                            } elseif ($fieldKey === "profile_button_text") {
                                $fieldLabel = "Texto del botón de perfil";
                            } elseif ($fieldKey === "profile_button_url") {
                                $fieldLabel = "URL del botón de perfil";
                            } elseif ($fieldKey === "appointment_button_text") {
                                $fieldLabel = "Texto del botón de cita";
                            } elseif ($fieldKey === "appointment_button_url") {
                                $fieldLabel = "URL del botón de cita";
                            }
                        } elseif ($repeaterKey === "services" && $fieldKey === "category_key") {
                            $fieldLabel = "Categoría del servicio";
                        } elseif ($repeaterKey === "emergency_contacts" && $fieldKey === "variant") {
                            $fieldLabel = "Prioridad del contacto";
                        } elseif ($templateKey === "department-details" && $repeaterKey === "stats" && $fieldKey === "number") {
                            $fieldLabel = "Número";
                        } elseif ($templateKey === "department-details" && $repeaterKey === "stats" && $fieldKey === "label") {
                            $fieldLabel = "Etiqueta";
                        }
                        $isHeroFeatureIconField = $repeaterKey === "hero_features" && $fieldKey === "icon_class";
                        $isHomeAboutFeatureIconField = $repeaterKey === "home_about_features" && $fieldKey === "icon_class";
                        $isCtaFeatureIconField = $repeaterKey === "cta_features" && $fieldKey === "icon_class";
                        $isQuickActionIconField = $repeaterKey === "quick_actions" && $fieldKey === "icon_class";
                        $isQuickActionLabelField = $repeaterKey === "quick_actions" && $fieldKey === "label";
                        $isQuickActionUrlField = $repeaterKey === "quick_actions" && $fieldKey === "url";
                        $isEmergencyContactIconField = $repeaterKey === "emergency_contacts" && $fieldKey === "icon_class";
                        $isDepartmentsIconField = $repeaterKey === "departments" && $fieldKey === "icon_class";
                        $isServiceIconField = $repeaterKey === "services" && $fieldKey === "icon_class";
                        $isContactInfoCardIconField = $templateKey === "contact" && $repeaterKey === "info_cards" && $fieldKey === "icon_class";
                        $isContactSocialLinkIconField = $templateKey === "contact" && $repeaterKey === "social_links" && $fieldKey === "icon_class";
                        $isDepartmentServiceCardIconField = $templateKey === "department-details" && $repeaterKey === "service_cards" && $fieldKey === "icon_class";
                        $isCtaFeatureLinkTextField = $repeaterKey === "cta_features" && $fieldKey === "link_text";
                        $isCtaFeatureLinkTypeField = $repeaterKey === "cta_features" && $fieldKey === "link_type";
                        $isCtaFeaturePageIdField = $repeaterKey === "cta_features" && $fieldKey === "page_id";
                        $isCtaFeatureLinkUrlField = $repeaterKey === "cta_features" && $fieldKey === "link_url";
                        $isFeaturedDepartmentIconField = $repeaterKey === "featured_departments" && $fieldKey === "icon_class";
                        $isFeaturedServiceIconField = $repeaterKey === "featured_services" && $fieldKey === "icon_class";
                        $isFeaturedDepartmentLinkTextField = $repeaterKey === "featured_departments" && $fieldKey === "link_text";
                        $isFeaturedDepartmentLinkTypeField = $repeaterKey === "featured_departments" && $fieldKey === "link_type";
                        $isFeaturedDepartmentPageIdField = $repeaterKey === "featured_departments" && $fieldKey === "page_id";
                        $isFeaturedDepartmentLinkUrlField = $repeaterKey === "featured_departments" && $fieldKey === "link_url";
                        $isFeaturedServiceLinkTextField = $repeaterKey === "featured_services" && $fieldKey === "link_text";
                        $isFeaturedServiceLinkTypeField = $repeaterKey === "featured_services" && $fieldKey === "link_type";
                        $isFeaturedServicePageIdField = $repeaterKey === "featured_services" && $fieldKey === "page_id";
                        $isFeaturedServiceLinkUrlField = $repeaterKey === "featured_services" && $fieldKey === "link_url";
                        $isServiceGeneralLinkTextField = $repeaterKey === "services" && $servicesItemMode === "general" && $fieldKey === "link_text";
                        $isServiceGeneralLinkTypeField = $repeaterKey === "services" && $servicesItemMode === "general" && $fieldKey === "link_type";
                        $isServiceGeneralPageIdField = $repeaterKey === "services" && $servicesItemMode === "general" && $fieldKey === "page_id";
                        $isServiceGeneralLinkUrlField = $repeaterKey === "services" && $servicesItemMode === "general" && $fieldKey === "link_url";
                        $isServiceEmergencyButtonTextField = $repeaterKey === "services" && $servicesItemMode === "emergency" && $fieldKey === "emergency_button_text";
                        $isServiceEmergencyButtonHiddenField = $repeaterKey === "services" && $servicesItemMode === "emergency" && in_array($fieldKey, ["emergency_button_icon", "emergency_button_link_type", "emergency_button_page_id", "emergency_button_url"], true);
                        $isServiceDirectionsButtonTextField = $repeaterKey === "services" && $servicesItemMode === "emergency" && $fieldKey === "directions_button_text";
                        $isServiceDirectionsButtonHiddenField = $repeaterKey === "services" && $servicesItemMode === "emergency" && in_array($fieldKey, ["directions_button_icon", "directions_button_link_type", "directions_button_page_id", "directions_button_url"], true);
                        $isFeaturedDoctorProfileButtonTextField = $repeaterKey === "featured_doctors" && $fieldKey === "profile_button_text";
                        $isFeaturedDoctorProfileButtonLinkTypeField = $repeaterKey === "featured_doctors" && $fieldKey === "profile_button_link_type";
                        $isFeaturedDoctorProfileButtonPageIdField = $repeaterKey === "featured_doctors" && $fieldKey === "profile_button_page_id";
                        $isFeaturedDoctorProfileButtonUrlField = $repeaterKey === "featured_doctors" && $fieldKey === "profile_button_url";
                        $isFeaturedDoctorAppointmentButtonTextField = $repeaterKey === "featured_doctors" && $fieldKey === "appointment_button_text";
                        $isFeaturedDoctorAppointmentButtonLinkTypeField = $repeaterKey === "featured_doctors" && $fieldKey === "appointment_button_link_type";
                        $isFeaturedDoctorAppointmentButtonPageIdField = $repeaterKey === "featured_doctors" && $fieldKey === "appointment_button_page_id";
                        $isFeaturedDoctorAppointmentButtonUrlField = $repeaterKey === "featured_doctors" && $fieldKey === "appointment_button_url";
                        $isDoctorsButtonTextField = $repeaterKey === "doctors" && $fieldKey === "button_text";
                        $isDoctorsButtonHiddenField = $repeaterKey === "doctors" && in_array($fieldKey, ["button_link_type", "button_page_id", "button_url"], true);
                        $isEmergencyContactButtonTextField = $repeaterKey === "emergency_contacts" && $fieldKey === "button_text";
                        $isEmergencyContactButtonLinkTypeField = $repeaterKey === "emergency_contacts" && $fieldKey === "button_link_type";
                        $isEmergencyContactButtonPageIdField = $repeaterKey === "emergency_contacts" && $fieldKey === "button_page_id";
                        $isEmergencyContactButtonUrlField = $repeaterKey === "emergency_contacts" && $fieldKey === "button_url";
                        $isDepartmentsFeaturedSubtitleField = $repeaterKey === "departments" && $departmentsLayoutVariant === "featured" && $fieldKey === "subtitle";
                        $isDepartmentsFeaturedDescriptionField = $repeaterKey === "departments" && $departmentsLayoutVariant === "featured" && $fieldKey === "description";
                        $isDepartmentsFeaturedButtonTextField = $repeaterKey === "departments" && $departmentsLayoutVariant === "featured" && $fieldKey === "button_text";
                        $isDepartmentsFeaturedButtonLinkTypeField = $repeaterKey === "departments" && $departmentsLayoutVariant === "featured" && $fieldKey === "button_link_type";
                        $isDepartmentsFeaturedButtonPageIdField = $repeaterKey === "departments" && $departmentsLayoutVariant === "featured" && $fieldKey === "button_page_id";
                        $isDepartmentsFeaturedButtonUrlField = $repeaterKey === "departments" && $departmentsLayoutVariant === "featured" && $fieldKey === "button_url";
                        $isDepartmentsStandardFeatureThreeField = $repeaterKey === "departments" && $departmentsLayoutVariant !== "featured" && $fieldKey === "feature_3";
                        $isDepartmentsStandardButtonTextField = $repeaterKey === "departments" && $departmentsLayoutVariant !== "featured" && $fieldKey === "button_text";
                        $isDepartmentsStandardButtonLinkTypeField = $repeaterKey === "departments" && $departmentsLayoutVariant !== "featured" && $fieldKey === "button_link_type";
                        $isDepartmentsStandardButtonPageIdField = $repeaterKey === "departments" && $departmentsLayoutVariant !== "featured" && $fieldKey === "button_page_id";
                        $isDepartmentsStandardButtonUrlField = $repeaterKey === "departments" && $departmentsLayoutVariant !== "featured" && $fieldKey === "button_url";
                        $isDepartmentServiceCardTextField = $templateKey === "department-details" && $repeaterKey === "service_cards" && $fieldKey === "text";
                        $featuredDepartmentLinkTypeValue = trim((string) (($itemData["fields"]["link_type"]["field_value"] ?? "")));
                        $featuredDepartmentPageIdValue = trim((string) (($itemData["fields"]["page_id"]["field_value"] ?? "")));
                        $featuredDepartmentLinkUrlValue = (string) (($itemData["fields"]["link_url"]["field_value"] ?? ""));
                        $featuredDepartmentLinkScope = $repeaterKey . "_" . $itemIndex . "_link";
                        if ($featuredDepartmentLinkTypeValue !== "internal" && $featuredDepartmentLinkTypeValue !== "custom") {
                            $featuredDepartmentLinkTypeValue = $featuredDepartmentLinkUrlValue !== "" ? "custom" : ($featuredDepartmentPageIdValue !== "" ? "internal" : "custom");
                        }
                        $featuredServiceLinkTypeValue = trim((string) (($itemData["fields"]["link_type"]["field_value"] ?? "")));
                        $featuredServicePageIdValue = trim((string) (($itemData["fields"]["page_id"]["field_value"] ?? "")));
                        $featuredServiceLinkUrlValue = (string) (($itemData["fields"]["link_url"]["field_value"] ?? ""));
                        $featuredServiceLinkScope = $repeaterKey . "_" . $itemIndex . "_link";
                        if ($featuredServiceLinkTypeValue !== "internal" && $featuredServiceLinkTypeValue !== "custom") {
                            $featuredServiceLinkTypeValue = $featuredServiceLinkUrlValue !== "" ? "custom" : ($featuredServicePageIdValue !== "" ? "internal" : "custom");
                        }
                        $serviceGeneralLinkTypeValue = trim((string) (($itemData["fields"]["link_type"]["field_value"] ?? "")));
                        $serviceGeneralPageIdValue = trim((string) (($itemData["fields"]["page_id"]["field_value"] ?? "")));
                        $serviceGeneralLinkUrlValue = (string) (($itemData["fields"]["link_url"]["field_value"] ?? ""));
                        $serviceGeneralLinkScope = $repeaterKey . "_" . $itemIndex . "_link";
                        if ($serviceGeneralLinkTypeValue !== "internal" && $serviceGeneralLinkTypeValue !== "custom") {
                            $serviceGeneralLinkTypeValue = $serviceGeneralLinkUrlValue !== "" ? "custom" : ($serviceGeneralPageIdValue !== "" ? "internal" : "custom");
                        }
                        $serviceEmergencyButtonIconValue = (string) (($itemData["fields"]["emergency_button_icon"]["field_value"] ?? ""));
                        $serviceEmergencyButtonLinkTypeValue = trim((string) (($itemData["fields"]["emergency_button_link_type"]["field_value"] ?? "")));
                        $serviceEmergencyButtonPageIdValue = trim((string) (($itemData["fields"]["emergency_button_page_id"]["field_value"] ?? "")));
                        $serviceEmergencyButtonUrlValue = (string) (($itemData["fields"]["emergency_button_url"]["field_value"] ?? ""));
                        $serviceEmergencyButtonLinkScope = $repeaterKey . "_" . $itemIndex . "_emergency_button_link";
                        if ($serviceEmergencyButtonLinkTypeValue !== "internal" && $serviceEmergencyButtonLinkTypeValue !== "custom") {
                            $serviceEmergencyButtonLinkTypeValue = $serviceEmergencyButtonUrlValue !== "" ? "custom" : ($serviceEmergencyButtonPageIdValue !== "" ? "internal" : "custom");
                        }
                        $serviceDirectionsButtonIconValue = (string) (($itemData["fields"]["directions_button_icon"]["field_value"] ?? ""));
                        $serviceEmergencyActionIconOptions = [
                            "fa fa-phone" => "Teléfono",
                            "fa fa-map-marker-alt" => "Marcador de mapa",
                        ];
                        $serviceDirectionsButtonLinkTypeValue = trim((string) (($itemData["fields"]["directions_button_link_type"]["field_value"] ?? "")));
                        $serviceDirectionsButtonPageIdValue = trim((string) (($itemData["fields"]["directions_button_page_id"]["field_value"] ?? "")));
                        $serviceDirectionsButtonUrlValue = (string) (($itemData["fields"]["directions_button_url"]["field_value"] ?? ""));
                        $serviceDirectionsButtonLinkScope = $repeaterKey . "_" . $itemIndex . "_directions_button_link";
                        if ($serviceDirectionsButtonLinkTypeValue !== "internal" && $serviceDirectionsButtonLinkTypeValue !== "custom") {
                            $serviceDirectionsButtonLinkTypeValue = $serviceDirectionsButtonUrlValue !== "" ? "custom" : ($serviceDirectionsButtonPageIdValue !== "" ? "internal" : "custom");
                        }
                        $ctaFeatureLinkTypeValue = trim((string) (($itemData["fields"]["link_type"]["field_value"] ?? "")));
                        $ctaFeaturePageIdValue = trim((string) (($itemData["fields"]["page_id"]["field_value"] ?? "")));
                        $ctaFeatureLinkUrlValue = (string) (($itemData["fields"]["link_url"]["field_value"] ?? ""));
                        $ctaFeatureLinkScope = $repeaterKey . "_" . $itemIndex . "_link";
                        if ($ctaFeatureLinkTypeValue !== "internal" && $ctaFeatureLinkTypeValue !== "custom") {
                            $ctaFeatureLinkTypeValue = $ctaFeatureLinkUrlValue !== "" ? "custom" : ($ctaFeaturePageIdValue !== "" ? "internal" : "custom");
                        }
                        $quickActionUrlValue = (string) (($itemData["fields"]["url"]["field_value"] ?? ""));
                        $quickActionLinkScope = $repeaterKey . "_" . $itemIndex . "_link";
                        $quickActionMatchedPageId = "";
                        foreach ($linkableSitePages as $sitePageOption) {
                            if ((string) ($sitePageOption["public_url"] ?? "") === $quickActionUrlValue) {
                                $quickActionMatchedPageId = (string) ($sitePageOption["id"] ?? "");
                                break;
                            }
                        }
                        $quickActionLinkTypeValue = $quickActionMatchedPageId !== "" ? "internal" : "custom";
                        $featuredDoctorProfileButtonLinkTypeValue = trim((string) (($itemData["fields"]["profile_button_link_type"]["field_value"] ?? "")));
                        $featuredDoctorProfileButtonPageIdValue = trim((string) (($itemData["fields"]["profile_button_page_id"]["field_value"] ?? "")));
                        $featuredDoctorProfileButtonUrlValue = (string) (($itemData["fields"]["profile_button_url"]["field_value"] ?? ""));
                        $featuredDoctorProfileButtonLinkScope = $repeaterKey . "_" . $itemIndex . "_profile_button_link";
                        if ($featuredDoctorProfileButtonLinkTypeValue !== "internal" && $featuredDoctorProfileButtonLinkTypeValue !== "custom") {
                            $featuredDoctorProfileButtonLinkTypeValue = $featuredDoctorProfileButtonUrlValue !== "" ? "custom" : ($featuredDoctorProfileButtonPageIdValue !== "" ? "internal" : "custom");
                        }
                        $featuredDoctorAppointmentButtonLinkTypeValue = trim((string) (($itemData["fields"]["appointment_button_link_type"]["field_value"] ?? "")));
                        $featuredDoctorAppointmentButtonPageIdValue = trim((string) (($itemData["fields"]["appointment_button_page_id"]["field_value"] ?? "")));
                        $featuredDoctorAppointmentButtonUrlValue = (string) (($itemData["fields"]["appointment_button_url"]["field_value"] ?? ""));
                        $featuredDoctorAppointmentButtonLinkScope = $repeaterKey . "_" . $itemIndex . "_appointment_button_link";
                        if ($featuredDoctorAppointmentButtonLinkTypeValue !== "internal" && $featuredDoctorAppointmentButtonLinkTypeValue !== "custom") {
                            $featuredDoctorAppointmentButtonLinkTypeValue = $featuredDoctorAppointmentButtonUrlValue !== "" ? "custom" : ($featuredDoctorAppointmentButtonPageIdValue !== "" ? "internal" : "custom");
                        }
                        $doctorsButtonLinkTypeValue = trim((string) (($itemData["fields"]["button_link_type"]["field_value"] ?? "")));
                        $doctorsButtonPageIdValue = trim((string) (($itemData["fields"]["button_page_id"]["field_value"] ?? "")));
                        $doctorsButtonUrlValue = (string) (($itemData["fields"]["button_url"]["field_value"] ?? ""));
                        $doctorsButtonUrlVisible = (int) (($itemData["fields"]["button_url"]["is_visible"] ?? 1)) === 1;
                        $doctorsButtonLinkScope = $repeaterKey . "_" . $itemIndex . "_button_link";
                        if ($doctorsButtonLinkTypeValue !== "internal" && $doctorsButtonLinkTypeValue !== "custom") {
                            $doctorsButtonLinkTypeValue = $doctorsButtonUrlValue !== "" ? "custom" : ($doctorsButtonPageIdValue !== "" ? "internal" : "custom");
                        }
                        $emergencyContactButtonLinkTypeValue = trim((string) (($itemData["fields"]["button_link_type"]["field_value"] ?? "")));
                        $emergencyContactButtonPageIdValue = trim((string) (($itemData["fields"]["button_page_id"]["field_value"] ?? "")));
                        $emergencyContactButtonUrlValue = (string) (($itemData["fields"]["button_url"]["field_value"] ?? ""));
                        $emergencyContactButtonLinkScope = $repeaterKey . "_" . $itemIndex . "_button_link";
                        if ($emergencyContactButtonLinkTypeValue !== "internal" && $emergencyContactButtonLinkTypeValue !== "custom") {
                            $emergencyContactButtonLinkTypeValue = $emergencyContactButtonUrlValue !== "" ? "custom" : ($emergencyContactButtonPageIdValue !== "" ? "internal" : "custom");
                        }
                        $departmentsFeaturedButtonLinkTypeValue = trim((string) (($itemData["fields"]["button_link_type"]["field_value"] ?? "")));
                        $departmentsFeaturedButtonPageIdValue = trim((string) (($itemData["fields"]["button_page_id"]["field_value"] ?? "")));
                        $departmentsFeaturedButtonUrlValue = (string) (($itemData["fields"]["button_url"]["field_value"] ?? ""));
                        $departmentsFeaturedButtonLinkScope = $repeaterKey . "_" . $itemIndex . "_button_link";
                        if ($departmentsFeaturedButtonLinkTypeValue !== "internal" && $departmentsFeaturedButtonLinkTypeValue !== "custom") {
                            $departmentsFeaturedButtonLinkTypeValue = $departmentsFeaturedButtonUrlValue !== "" ? "custom" : ($departmentsFeaturedButtonPageIdValue !== "" ? "internal" : "custom");
                        }
                        $departmentsStandardButtonLinkTypeValue = trim((string) (($itemData["fields"]["button_link_type"]["field_value"] ?? "")));
                        $departmentsStandardButtonPageIdValue = trim((string) (($itemData["fields"]["button_page_id"]["field_value"] ?? "")));
                        $departmentsStandardButtonUrlValue = (string) (($itemData["fields"]["button_url"]["field_value"] ?? ""));
                        $departmentsStandardButtonLinkScope = $repeaterKey . "_" . $itemIndex . "_standard_button_link";
                        if ($departmentsStandardButtonLinkTypeValue !== "internal" && $departmentsStandardButtonLinkTypeValue !== "custom") {
                            $departmentsStandardButtonLinkTypeValue = $departmentsStandardButtonUrlValue !== "" ? "custom" : ($departmentsStandardButtonPageIdValue !== "" ? "internal" : "custom");
                        }
                        $heroFeatureIconOptions = [
                            "bi bi-heart-pulse-fill" => "Corazón",
                            "bi bi-lungs-fill" => "Pulmones",
                            "bi bi-capsule" => "Cápsula",
                        ];
                        $homeAboutFeatureIconOptions = [
                            "bi bi-heart-pulse" => "Corazón",
                            "bi bi-star" => "Estrella",
                        ];
                        $ctaFeatureIconOptions = [
                            "bi bi-heart-pulse" => "Corazón",
                            "bi bi-calendar-check" => "Calendario",
                            "bi bi-people" => "Personas",
                        ];
                        $quickActionIconOptions = [
                            "bi bi-geo-alt-fill" => "Ubicación",
                            "bi bi-calendar-check" => "Calendario",
                            "bi bi-person-badge" => "Usuario / Doctor",
                            "bi bi-chat-dots" => "Chat",
                        ];
                        $emergencyContactIconOptions = [
                            "bi bi-hospital" => "Hospital",
                            "bi bi-clock" => "Reloj",
                            "bi bi-headset" => "Soporte",
                            "bi bi-heart-pulse" => "Corazón",
                        ];
                        $featuredDepartmentIconOptions = [
                            "fas fa-heartbeat" => "Latido",
                            "fas fa-brain" => "Cerebro",
                            "fas fa-bone" => "Hueso",
                            "fas fa-baby" => "Bebé",
                            "fas fa-shield-alt" => "Escudo",
                            "fas fa-ambulance" => "Ambulancia",
                        ];
                        $featuredServiceIconOptions = [
                            "fas fa-heartbeat" => "Latido",
                            "fas fa-brain" => "Cerebro",
                            "fas fa-bone" => "Hueso",
                            "fas fa-ambulance" => "Ambulancia",
                        ];
                        $serviceIconOptions = [
                            "fa fa-stethoscope" => "Estetoscopio",
                            "fa fa-syringe" => "Inyección",
                            "fa fa-baby" => "Bebé",
                            "fa fa-user-md" => "Doctor",
                            "fa fa-heartbeat" => "Corazón",
                            "fa fa-brain" => "Cerebro",
                            "fa fa-bone" => "Hueso",
                            "fa fa-user-nurse" => "Doctor especializado",
                            "fa fa-vial" => "Tubo de muestra",
                            "fa fa-x-ray" => "Imagen diagnóstica",
                            "fa fa-ambulance" => "Ambulancia",
                        ];
                        $departmentsIconOptions = [
                            "bi bi-heart-pulse" => "Cardiología",
                            "bi bi-shield-plus" => "Escudo médico",
                            "bi bi-lightning-charge-fill" => "Rayo",
                            "bi bi-bandaid" => "Curita",
                            "bi bi-emoji-smile" => "Sonrisa",
                        ];
                        $contactInfoCardIconOptions = [
                            "bi bi-pin-map-fill" => "Pin mapa",
                            "bi bi-envelope-open" => "Sobre abierto",
                            "bi bi-telephone-fill" => "Teléfono",
                            "bi bi-clock-history" => "Reloj",
                        ];
                        $departmentServiceCardIconOptions = [
                            "bi bi-heart-pulse" => "Pulso cardíaco",
                            "bi bi-activity" => "Signos vitales",
                            "bi bi-person-heart" => "Persona y corazón",
                        ];
                        if ($repeaterKey === "about_stats" && $fieldKey === "value") {
                            $fieldLabel = "Valor";
                        }
                        if ($isCtaFeatureLinkTypeField || $isCtaFeaturePageIdField || $isCtaFeatureLinkUrlField || $isFeaturedDepartmentLinkTypeField || $isFeaturedDepartmentPageIdField || $isFeaturedDepartmentLinkUrlField || $isFeaturedServiceLinkTypeField || $isFeaturedServicePageIdField || $isFeaturedServiceLinkUrlField || $isServiceGeneralLinkTypeField || $isServiceGeneralPageIdField || $isServiceGeneralLinkUrlField || $isServiceEmergencyButtonHiddenField || $isServiceDirectionsButtonHiddenField || $isFeaturedDoctorProfileButtonLinkTypeField || $isFeaturedDoctorProfileButtonPageIdField || $isFeaturedDoctorProfileButtonUrlField || $isFeaturedDoctorAppointmentButtonLinkTypeField || $isFeaturedDoctorAppointmentButtonPageIdField || $isFeaturedDoctorAppointmentButtonUrlField || $isDoctorsButtonHiddenField || $isEmergencyContactButtonLinkTypeField || $isEmergencyContactButtonPageIdField || $isEmergencyContactButtonUrlField || $isDepartmentsFeaturedButtonLinkTypeField || $isDepartmentsFeaturedButtonPageIdField || $isDepartmentsFeaturedButtonUrlField || $isDepartmentsStandardButtonLinkTypeField || $isDepartmentsStandardButtonPageIdField || $isDepartmentsStandardButtonUrlField) {
                            continue;
                        }
                        ?>
                        <div class="field-group<?php echo ($isQuickActionIconField || $isQuickActionLabelField || $isQuickActionUrlField || $isCtaFeatureLinkTextField || $isFeaturedDepartmentLinkTextField || $isFeaturedServiceLinkTextField || $isServiceGeneralLinkTextField || $isServiceEmergencyButtonTextField || $isServiceDirectionsButtonTextField || $isFeaturedDoctorProfileButtonTextField || $isFeaturedDoctorAppointmentButtonTextField || $isDoctorsButtonTextField || $isEmergencyContactButtonTextField || $isDepartmentsFeaturedSubtitleField || $isDepartmentsFeaturedDescriptionField || $isDepartmentsFeaturedButtonTextField || $isDepartmentsStandardFeatureThreeField || $isDepartmentsStandardButtonTextField || $isDepartmentServiceCardTextField) ? " field-group-full" : ""; ?>">
                            <?php if ($repeaterKey === "doctors"): ?>
                                <div class="field-header">
                                    <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>"><?php echo escapeAdminFieldLabel($fieldLabel); ?></label>
                                    <label class="toggle-row">
                                        <input type="checkbox" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields_visible][<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>]" value="1"<?php echo $fieldVisible ? " checked" : ""; ?>>
                                        <span>Mostrar</span>
                                    </label>
                                </div>
                            <?php elseif (!$isQuickActionUrlField && !$isCtaFeatureLinkTextField && !$isFeaturedDepartmentLinkTextField && !$isFeaturedServiceLinkTextField && !$isServiceGeneralLinkTextField && !$isServiceEmergencyButtonTextField && !$isServiceDirectionsButtonTextField && !$isFeaturedDoctorProfileButtonTextField && !$isFeaturedDoctorAppointmentButtonTextField && !$isEmergencyContactButtonTextField && !$isDepartmentsFeaturedButtonTextField && !$isDepartmentsStandardButtonTextField): ?>
                                <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>"><?php echo escapeAdminFieldLabel($fieldLabel); ?></label>
                            <?php endif; ?>
                            <?php if ($fieldType === "image"): ?>
                                <input type="hidden" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                <div class="current-file"><strong>Archivo actual:</strong> <?php echo htmlspecialchars($fieldValue !== "" ? basename($fieldValue) : "Sin imagen seleccionada", ENT_QUOTES, "UTF-8"); ?></div>
                                <label class="file-input-label" for="repeater_file_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>">Reemplazar imagen</label>
                                <input class="form-file js-image-upload" type="file" id="repeater_file_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-preview-target="preview_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>">
                                <img id="preview_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>" src="<?php echo $fieldValue !== "" ? "../../" . htmlspecialchars(ltrim($fieldValue, "/"), ENT_QUOTES, "UTF-8") : ""; ?>" alt="" class="preview-image<?php echo $fieldValue !== "" ? "" : " is-empty"; ?>">
                            <?php elseif ($isHeroFeatureIconField || $isHomeAboutFeatureIconField || $isCtaFeatureIconField || $isQuickActionIconField || $isEmergencyContactIconField || $isFeaturedDepartmentIconField || $isFeaturedServiceIconField || $isDepartmentsIconField || $isServiceIconField || $isContactInfoCardIconField || $isContactSocialLinkIconField || $isDepartmentServiceCardIconField): ?>
                                <select class="form-input" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>]"<?php echo $isContactSocialLinkIconField ? ' data-contact-social-link-icon-input="' . $itemIndex . '"' : ""; ?>>
                                    <?php $iconOptions = $isDepartmentServiceCardIconField ? $departmentServiceCardIconOptions : ($isContactSocialLinkIconField ? $contactSocialLinkIconOptions : ($isContactInfoCardIconField ? $contactInfoCardIconOptions : ($isHeroFeatureIconField ? $heroFeatureIconOptions : ($isHomeAboutFeatureIconField ? $homeAboutFeatureIconOptions : ($isCtaFeatureIconField ? $ctaFeatureIconOptions : ($isQuickActionIconField ? $quickActionIconOptions : ($isEmergencyContactIconField ? $emergencyContactIconOptions : ($isFeaturedDepartmentIconField ? $featuredDepartmentIconOptions : ($isDepartmentsIconField ? $departmentsIconOptions : ($isServiceIconField ? $serviceIconOptions : $featuredServiceIconOptions)))))))))); ?>
                                    <?php if ($isQuickActionIconField && $fieldValue !== "" && !array_key_exists($fieldValue, $quickActionIconOptions)): ?>
                                        <option value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>" selected>Icono actual: <?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?></option>
                                    <?php endif; ?>
                                    <?php foreach ($iconOptions as $iconValue => $iconLabel): ?>
                                        <option value="<?php echo htmlspecialchars($iconValue, ENT_QUOTES, "UTF-8"); ?>"<?php echo $fieldValue === $iconValue ? " selected" : ""; ?>><?php echo htmlspecialchars($iconLabel, ENT_QUOTES, "UTF-8"); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($isCtaFeatureLinkTextField): ?>
                                <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_text", ENT_QUOTES, "UTF-8"); ?>">Texto del enlace</label>
                                <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_text", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][link_text]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">

                                <div class="button-destination-box">
                                    <div class="field-group field-group-full">
                                        <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_type", ENT_QUOTES, "UTF-8"); ?>">Tipo de enlace</label>
                                        <select class="form-select js-link-type" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_type", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][link_type]" data-link-scope="<?php echo htmlspecialchars($ctaFeatureLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <option value="internal"<?php echo $ctaFeatureLinkTypeValue === "internal" ? " selected" : ""; ?>>P&aacute;gina interna</option>
                                            <option value="custom"<?php echo $ctaFeatureLinkTypeValue === "custom" ? " selected" : ""; ?>>URL personalizada</option>
                                        </select>
                                    </div>

                                    <div class="button-destination-grid">
                                        <div class="field-group field-group-full js-link-panel <?php echo $ctaFeatureLinkTypeValue === "internal" ? "" : "is-hidden"; ?>" data-link-panel="internal" data-link-scope="<?php echo htmlspecialchars($ctaFeatureLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_page_id", ENT_QUOTES, "UTF-8"); ?>">P&aacute;gina interna</label>
                                            <select class="form-select" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_page_id", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][page_id]">
                                                <option value="">Selecciona una p&aacute;gina</option>
                                                <?php foreach ($linkableSitePages as $sitePageOption): ?>
                                                    <option value="<?php echo (int) ($sitePageOption["id"] ?? 0); ?>"<?php echo (string) ($sitePageOption["id"] ?? "") === $ctaFeaturePageIdValue ? " selected" : ""; ?>><?php echo htmlspecialchars((string) ($sitePageOption["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="field-group field-group-full js-link-panel <?php echo $ctaFeatureLinkTypeValue === "custom" ? "" : "is-hidden"; ?>" data-link-panel="custom" data-link-scope="<?php echo htmlspecialchars($ctaFeatureLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_url", ENT_QUOTES, "UTF-8"); ?>">URL personalizada</label>
                                            <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_url", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][link_url]" value="<?php echo htmlspecialchars($ctaFeatureLinkUrlValue, ENT_QUOTES, "UTF-8"); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($isFeaturedDepartmentLinkTextField): ?>
                                <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_text", ENT_QUOTES, "UTF-8"); ?>">Texto del enlace</label>
                                <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_text", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][link_text]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">

                                <div class="button-destination-box">
                                    <div class="field-group field-group-full">
                                        <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_type", ENT_QUOTES, "UTF-8"); ?>">Tipo de enlace</label>
                                        <select class="form-select js-link-type" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_type", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][link_type]" data-link-scope="<?php echo htmlspecialchars($featuredDepartmentLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <option value="internal"<?php echo $featuredDepartmentLinkTypeValue === "internal" ? " selected" : ""; ?>>P&aacute;gina interna</option>
                                            <option value="custom"<?php echo $featuredDepartmentLinkTypeValue === "custom" ? " selected" : ""; ?>>URL personalizada</option>
                                        </select>
                                    </div>

                                    <div class="button-destination-grid">
                                        <div class="field-group field-group-full js-link-panel <?php echo $featuredDepartmentLinkTypeValue === "internal" ? "" : "is-hidden"; ?>" data-link-panel="internal" data-link-scope="<?php echo htmlspecialchars($featuredDepartmentLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_page_id", ENT_QUOTES, "UTF-8"); ?>">P&aacute;gina interna</label>
                                            <select class="form-select" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_page_id", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][page_id]">
                                                <option value="">Selecciona una p&aacute;gina</option>
                                                <?php foreach ($linkableSitePages as $sitePageOption): ?>
                                                    <option value="<?php echo (int) ($sitePageOption["id"] ?? 0); ?>"<?php echo (string) ($sitePageOption["id"] ?? "") === $featuredDepartmentPageIdValue ? " selected" : ""; ?>><?php echo htmlspecialchars((string) ($sitePageOption["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="field-group field-group-full js-link-panel <?php echo $featuredDepartmentLinkTypeValue === "custom" ? "" : "is-hidden"; ?>" data-link-panel="custom" data-link-scope="<?php echo htmlspecialchars($featuredDepartmentLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_url", ENT_QUOTES, "UTF-8"); ?>">URL personalizada</label>
                                            <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_url", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][link_url]" value="<?php echo htmlspecialchars($featuredDepartmentLinkUrlValue, ENT_QUOTES, "UTF-8"); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($isServiceGeneralLinkTextField): ?>
                                <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_text", ENT_QUOTES, "UTF-8"); ?>">Texto del botón</label>
                                <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_text", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][link_text]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">

                                <div class="button-destination-box">
                                    <div class="field-group field-group-full">
                                        <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_type", ENT_QUOTES, "UTF-8"); ?>">Tipo de enlace</label>
                                        <select class="form-select js-link-type" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_type", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][link_type]" data-link-scope="<?php echo htmlspecialchars($serviceGeneralLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <option value="internal"<?php echo $serviceGeneralLinkTypeValue === "internal" ? " selected" : ""; ?>>P&aacute;gina interna</option>
                                            <option value="custom"<?php echo $serviceGeneralLinkTypeValue === "custom" ? " selected" : ""; ?>>URL personalizada</option>
                                        </select>
                                    </div>

                                    <div class="button-destination-grid">
                                        <div class="field-group field-group-full js-link-panel <?php echo $serviceGeneralLinkTypeValue === "internal" ? "" : "is-hidden"; ?>" data-link-panel="internal" data-link-scope="<?php echo htmlspecialchars($serviceGeneralLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_page_id", ENT_QUOTES, "UTF-8"); ?>">P&aacute;gina interna</label>
                                            <select class="form-select" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_page_id", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][page_id]">
                                                <option value="">Selecciona una p&aacute;gina</option>
                                                <?php foreach ($linkableSitePages as $sitePageOption): ?>
                                                    <option value="<?php echo (int) ($sitePageOption["id"] ?? 0); ?>"<?php echo (string) ($sitePageOption["id"] ?? "") === $serviceGeneralPageIdValue ? " selected" : ""; ?>><?php echo htmlspecialchars((string) ($sitePageOption["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="field-group field-group-full js-link-panel <?php echo $serviceGeneralLinkTypeValue === "custom" ? "" : "is-hidden"; ?>" data-link-panel="custom" data-link-scope="<?php echo htmlspecialchars($serviceGeneralLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_url", ENT_QUOTES, "UTF-8"); ?>">URL personalizada</label>
                                            <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_url", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][link_url]" value="<?php echo htmlspecialchars($serviceGeneralLinkUrlValue, ENT_QUOTES, "UTF-8"); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($isFeaturedServiceLinkTextField): ?>
                                <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_text", ENT_QUOTES, "UTF-8"); ?>">Texto del enlace</label>
                                <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_text", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][link_text]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">

                                <div class="button-destination-box">
                                    <div class="field-group field-group-full">
                                        <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_type", ENT_QUOTES, "UTF-8"); ?>">Tipo de enlace</label>
                                        <select class="form-select js-link-type" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_type", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][link_type]" data-link-scope="<?php echo htmlspecialchars($featuredServiceLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <option value="internal"<?php echo $featuredServiceLinkTypeValue === "internal" ? " selected" : ""; ?>>P&aacute;gina interna</option>
                                            <option value="custom"<?php echo $featuredServiceLinkTypeValue === "custom" ? " selected" : ""; ?>>URL personalizada</option>
                                        </select>
                                    </div>

                                    <div class="button-destination-grid">
                                        <div class="field-group field-group-full js-link-panel <?php echo $featuredServiceLinkTypeValue === "internal" ? "" : "is-hidden"; ?>" data-link-panel="internal" data-link-scope="<?php echo htmlspecialchars($featuredServiceLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_page_id", ENT_QUOTES, "UTF-8"); ?>">P&aacute;gina interna</label>
                                            <select class="form-select" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_page_id", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][page_id]">
                                                <option value="">Selecciona una p&aacute;gina</option>
                                                <?php foreach ($linkableSitePages as $sitePageOption): ?>
                                                    <option value="<?php echo (int) ($sitePageOption["id"] ?? 0); ?>"<?php echo (string) ($sitePageOption["id"] ?? "") === $featuredServicePageIdValue ? " selected" : ""; ?>><?php echo htmlspecialchars((string) ($sitePageOption["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="field-group field-group-full js-link-panel <?php echo $featuredServiceLinkTypeValue === "custom" ? "" : "is-hidden"; ?>" data-link-panel="custom" data-link-scope="<?php echo htmlspecialchars($featuredServiceLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_url", ENT_QUOTES, "UTF-8"); ?>">URL personalizada</label>
                                            <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_url", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][link_url]" value="<?php echo htmlspecialchars($featuredServiceLinkUrlValue, ENT_QUOTES, "UTF-8"); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($isServiceEmergencyButtonTextField): ?>
                                <div class="button-destination-box">
                                    <h4>Botón de emergencia</h4>
                                    <div class="field-grid">
                                        <div class="field-group">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_emergency_button_text", ENT_QUOTES, "UTF-8"); ?>">Botón emergencia texto</label>
                                            <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_emergency_button_text", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][emergency_button_text]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                        </div>
                                        <div class="field-group">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_emergency_button_icon", ENT_QUOTES, "UTF-8"); ?>">Botón emergencia icono</label>
                                            <select class="form-input" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_emergency_button_icon", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][emergency_button_icon]">
                                                <?php foreach ($serviceEmergencyActionIconOptions as $iconValue => $iconLabel): ?>
                                                    <option value="<?php echo htmlspecialchars($iconValue, ENT_QUOTES, "UTF-8"); ?>"<?php echo $serviceEmergencyButtonIconValue === $iconValue ? " selected" : ""; ?>><?php echo htmlspecialchars($iconLabel, ENT_QUOTES, "UTF-8"); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="button-destination-grid">
                                        <div class="field-group field-group-full">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_emergency_button_link_type", ENT_QUOTES, "UTF-8"); ?>">Tipo de enlace</label>
                                            <select class="form-select js-link-type" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_emergency_button_link_type", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][emergency_button_link_type]" data-link-scope="<?php echo htmlspecialchars($serviceEmergencyButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                                <option value="internal"<?php echo $serviceEmergencyButtonLinkTypeValue === "internal" ? " selected" : ""; ?>>P&aacute;gina interna</option>
                                                <option value="custom"<?php echo $serviceEmergencyButtonLinkTypeValue === "custom" ? " selected" : ""; ?>>URL personalizada</option>
                                            </select>
                                        </div>
                                        <div class="field-group field-group-full js-link-panel <?php echo $serviceEmergencyButtonLinkTypeValue === "internal" ? "" : "is-hidden"; ?>" data-link-panel="internal" data-link-scope="<?php echo htmlspecialchars($serviceEmergencyButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_emergency_button_page_id", ENT_QUOTES, "UTF-8"); ?>">P&aacute;gina interna</label>
                                            <select class="form-select" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_emergency_button_page_id", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][emergency_button_page_id]">
                                                <option value="">Selecciona una p&aacute;gina</option>
                                                <?php foreach ($linkableSitePages as $sitePageOption): ?>
                                                    <option value="<?php echo (int) ($sitePageOption["id"] ?? 0); ?>"<?php echo (string) ($sitePageOption["id"] ?? "") === $serviceEmergencyButtonPageIdValue ? " selected" : ""; ?>><?php echo htmlspecialchars((string) ($sitePageOption["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field-group field-group-full js-link-panel <?php echo $serviceEmergencyButtonLinkTypeValue === "custom" ? "" : "is-hidden"; ?>" data-link-panel="custom" data-link-scope="<?php echo htmlspecialchars($serviceEmergencyButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_emergency_button_url", ENT_QUOTES, "UTF-8"); ?>">URL personalizada</label>
                                            <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_emergency_button_url", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][emergency_button_url]" value="<?php echo htmlspecialchars($serviceEmergencyButtonUrlValue, ENT_QUOTES, "UTF-8"); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($isServiceDirectionsButtonTextField): ?>
                                <div class="button-destination-box">
                                    <h4>Botón de direcciones</h4>
                                    <div class="field-grid">
                                        <div class="field-group">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_directions_button_text", ENT_QUOTES, "UTF-8"); ?>">Botón direcciones texto</label>
                                            <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_directions_button_text", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][directions_button_text]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                        </div>
                                        <div class="field-group">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_directions_button_icon", ENT_QUOTES, "UTF-8"); ?>">Botón direcciones icono</label>
                                            <select class="form-input" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_directions_button_icon", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][directions_button_icon]">
                                                <?php foreach ($serviceEmergencyActionIconOptions as $iconValue => $iconLabel): ?>
                                                    <option value="<?php echo htmlspecialchars($iconValue, ENT_QUOTES, "UTF-8"); ?>"<?php echo $serviceDirectionsButtonIconValue === $iconValue ? " selected" : ""; ?>><?php echo htmlspecialchars($iconLabel, ENT_QUOTES, "UTF-8"); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="button-destination-grid">
                                        <div class="field-group field-group-full">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_directions_button_link_type", ENT_QUOTES, "UTF-8"); ?>">Tipo de enlace</label>
                                            <select class="form-select js-link-type" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_directions_button_link_type", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][directions_button_link_type]" data-link-scope="<?php echo htmlspecialchars($serviceDirectionsButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                                <option value="internal"<?php echo $serviceDirectionsButtonLinkTypeValue === "internal" ? " selected" : ""; ?>>P&aacute;gina interna</option>
                                                <option value="custom"<?php echo $serviceDirectionsButtonLinkTypeValue === "custom" ? " selected" : ""; ?>>URL personalizada</option>
                                            </select>
                                        </div>
                                        <div class="field-group field-group-full js-link-panel <?php echo $serviceDirectionsButtonLinkTypeValue === "internal" ? "" : "is-hidden"; ?>" data-link-panel="internal" data-link-scope="<?php echo htmlspecialchars($serviceDirectionsButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_directions_button_page_id", ENT_QUOTES, "UTF-8"); ?>">P&aacute;gina interna</label>
                                            <select class="form-select" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_directions_button_page_id", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][directions_button_page_id]">
                                                <option value="">Selecciona una p&aacute;gina</option>
                                                <?php foreach ($linkableSitePages as $sitePageOption): ?>
                                                    <option value="<?php echo (int) ($sitePageOption["id"] ?? 0); ?>"<?php echo (string) ($sitePageOption["id"] ?? "") === $serviceDirectionsButtonPageIdValue ? " selected" : ""; ?>><?php echo htmlspecialchars((string) ($sitePageOption["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field-group field-group-full js-link-panel <?php echo $serviceDirectionsButtonLinkTypeValue === "custom" ? "" : "is-hidden"; ?>" data-link-panel="custom" data-link-scope="<?php echo htmlspecialchars($serviceDirectionsButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_directions_button_url", ENT_QUOTES, "UTF-8"); ?>">URL personalizada</label>
                                            <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_directions_button_url", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][directions_button_url]" value="<?php echo htmlspecialchars($serviceDirectionsButtonUrlValue, ENT_QUOTES, "UTF-8"); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($isFeaturedDoctorProfileButtonTextField): ?>
                                <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_profile_button_text", ENT_QUOTES, "UTF-8"); ?>">Texto del botón de perfil</label>
                                <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_profile_button_text", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][profile_button_text]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">

                                <div class="button-destination-box">
                                    <div class="field-group field-group-full">
                                        <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_profile_button_link_type", ENT_QUOTES, "UTF-8"); ?>">Tipo de enlace</label>
                                        <select class="form-select js-link-type" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_profile_button_link_type", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][profile_button_link_type]" data-link-scope="<?php echo htmlspecialchars($featuredDoctorProfileButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <option value="internal"<?php echo $featuredDoctorProfileButtonLinkTypeValue === "internal" ? " selected" : ""; ?>>P&aacute;gina interna</option>
                                            <option value="custom"<?php echo $featuredDoctorProfileButtonLinkTypeValue === "custom" ? " selected" : ""; ?>>URL personalizada</option>
                                        </select>
                                    </div>

                                    <div class="button-destination-grid">
                                        <div class="field-group field-group-full js-link-panel <?php echo $featuredDoctorProfileButtonLinkTypeValue === "internal" ? "" : "is-hidden"; ?>" data-link-panel="internal" data-link-scope="<?php echo htmlspecialchars($featuredDoctorProfileButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_profile_button_page_id", ENT_QUOTES, "UTF-8"); ?>">P&aacute;gina interna</label>
                                            <select class="form-select" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_profile_button_page_id", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][profile_button_page_id]">
                                                <option value="">Selecciona una p&aacute;gina</option>
                                                <?php foreach ($linkableSitePages as $sitePageOption): ?>
                                                    <option value="<?php echo (int) ($sitePageOption["id"] ?? 0); ?>"<?php echo (string) ($sitePageOption["id"] ?? "") === $featuredDoctorProfileButtonPageIdValue ? " selected" : ""; ?>><?php echo htmlspecialchars((string) ($sitePageOption["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="field-group field-group-full js-link-panel <?php echo $featuredDoctorProfileButtonLinkTypeValue === "custom" ? "" : "is-hidden"; ?>" data-link-panel="custom" data-link-scope="<?php echo htmlspecialchars($featuredDoctorProfileButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_profile_button_url", ENT_QUOTES, "UTF-8"); ?>">URL personalizada</label>
                                            <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_profile_button_url", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][profile_button_url]" value="<?php echo htmlspecialchars($featuredDoctorProfileButtonUrlValue, ENT_QUOTES, "UTF-8"); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($isFeaturedDoctorAppointmentButtonTextField): ?>
                                <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_appointment_button_text", ENT_QUOTES, "UTF-8"); ?>">Texto del botón de cita</label>
                                <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_appointment_button_text", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][appointment_button_text]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">

                                <div class="button-destination-box">
                                    <div class="field-group field-group-full">
                                        <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_appointment_button_link_type", ENT_QUOTES, "UTF-8"); ?>">Tipo de enlace</label>
                                        <select class="form-select js-link-type" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_appointment_button_link_type", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][appointment_button_link_type]" data-link-scope="<?php echo htmlspecialchars($featuredDoctorAppointmentButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <option value="internal"<?php echo $featuredDoctorAppointmentButtonLinkTypeValue === "internal" ? " selected" : ""; ?>>P&aacute;gina interna</option>
                                            <option value="custom"<?php echo $featuredDoctorAppointmentButtonLinkTypeValue === "custom" ? " selected" : ""; ?>>URL personalizada</option>
                                        </select>
                                    </div>

                                    <div class="button-destination-grid">
                                        <div class="field-group field-group-full js-link-panel <?php echo $featuredDoctorAppointmentButtonLinkTypeValue === "internal" ? "" : "is-hidden"; ?>" data-link-panel="internal" data-link-scope="<?php echo htmlspecialchars($featuredDoctorAppointmentButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_appointment_button_page_id", ENT_QUOTES, "UTF-8"); ?>">P&aacute;gina interna</label>
                                            <select class="form-select" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_appointment_button_page_id", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][appointment_button_page_id]">
                                                <option value="">Selecciona una p&aacute;gina</option>
                                                <?php foreach ($linkableSitePages as $sitePageOption): ?>
                                                    <option value="<?php echo (int) ($sitePageOption["id"] ?? 0); ?>"<?php echo (string) ($sitePageOption["id"] ?? "") === $featuredDoctorAppointmentButtonPageIdValue ? " selected" : ""; ?>><?php echo htmlspecialchars((string) ($sitePageOption["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="field-group field-group-full js-link-panel <?php echo $featuredDoctorAppointmentButtonLinkTypeValue === "custom" ? "" : "is-hidden"; ?>" data-link-panel="custom" data-link-scope="<?php echo htmlspecialchars($featuredDoctorAppointmentButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_appointment_button_url", ENT_QUOTES, "UTF-8"); ?>">URL personalizada</label>
                                            <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_appointment_button_url", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][appointment_button_url]" value="<?php echo htmlspecialchars($featuredDoctorAppointmentButtonUrlValue, ENT_QUOTES, "UTF-8"); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($isDoctorsButtonTextField): ?>
                                <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_text", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][button_text]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">

                                <div class="button-destination-box">
                                    <input type="hidden" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields_visible][button_link_type]" value="1">
                                    <input type="hidden" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields_visible][button_page_id]" value="1">
                                    <div class="field-group field-group-full">
                                        <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_link_type", ENT_QUOTES, "UTF-8"); ?>">Tipo de enlace</label>
                                        <select class="form-select js-link-type" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_link_type", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][button_link_type]" data-link-scope="<?php echo htmlspecialchars($doctorsButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <option value="internal"<?php echo $doctorsButtonLinkTypeValue === "internal" ? " selected" : ""; ?>>P&aacute;gina interna</option>
                                            <option value="custom"<?php echo $doctorsButtonLinkTypeValue === "custom" ? " selected" : ""; ?>>URL personalizada</option>
                                        </select>
                                    </div>

                                    <div class="button-destination-grid">
                                        <div class="field-group field-group-full js-link-panel <?php echo $doctorsButtonLinkTypeValue === "internal" ? "" : "is-hidden"; ?>" data-link-panel="internal" data-link-scope="<?php echo htmlspecialchars($doctorsButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_page_id", ENT_QUOTES, "UTF-8"); ?>">P&aacute;gina interna</label>
                                            <select class="form-select" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_page_id", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][button_page_id]">
                                                <option value="">Selecciona una p&aacute;gina</option>
                                                <?php foreach ($linkableSitePages as $sitePageOption): ?>
                                                    <option value="<?php echo (int) ($sitePageOption["id"] ?? 0); ?>"<?php echo (string) ($sitePageOption["id"] ?? "") === $doctorsButtonPageIdValue ? " selected" : ""; ?>><?php echo htmlspecialchars((string) ($sitePageOption["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="field-group field-group-full js-link-panel <?php echo $doctorsButtonLinkTypeValue === "custom" ? "" : "is-hidden"; ?>" data-link-panel="custom" data-link-scope="<?php echo htmlspecialchars($doctorsButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <div class="field-header">
                                                <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_url", ENT_QUOTES, "UTF-8"); ?>">URL personalizada</label>
                                                <label class="toggle-row">
                                                    <input type="checkbox" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields_visible][button_url]" value="1"<?php echo $doctorsButtonUrlVisible ? " checked" : ""; ?>>
                                                    <span>Mostrar</span>
                                                </label>
                                            </div>
                                            <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_url", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][button_url]" value="<?php echo htmlspecialchars($doctorsButtonUrlValue, ENT_QUOTES, "UTF-8"); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($isDepartmentsFeaturedButtonTextField): ?>
                                <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_text", ENT_QUOTES, "UTF-8"); ?>">Texto del botón</label>
                                <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_text", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][button_text]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">

                                <div class="button-destination-box">
                                    <div class="field-group field-group-full">
                                        <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_link_type", ENT_QUOTES, "UTF-8"); ?>">Tipo de enlace</label>
                                        <select class="form-select js-link-type" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_link_type", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][button_link_type]" data-link-scope="<?php echo htmlspecialchars($departmentsFeaturedButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <option value="internal"<?php echo $departmentsFeaturedButtonLinkTypeValue === "internal" ? " selected" : ""; ?>>P&aacute;gina interna</option>
                                            <option value="custom"<?php echo $departmentsFeaturedButtonLinkTypeValue === "custom" ? " selected" : ""; ?>>URL personalizada</option>
                                        </select>
                                    </div>

                                    <div class="button-destination-grid">
                                        <div class="field-group field-group-full js-link-panel <?php echo $departmentsFeaturedButtonLinkTypeValue === "internal" ? "" : "is-hidden"; ?>" data-link-panel="internal" data-link-scope="<?php echo htmlspecialchars($departmentsFeaturedButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_page_id", ENT_QUOTES, "UTF-8"); ?>">P&aacute;gina interna</label>
                                            <select class="form-select" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_page_id", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][button_page_id]">
                                                <option value="">Selecciona una p&aacute;gina</option>
                                                <?php foreach ($linkableSitePages as $sitePageOption): ?>
                                                    <option value="<?php echo (int) ($sitePageOption["id"] ?? 0); ?>"<?php echo (string) ($sitePageOption["id"] ?? "") === $departmentsFeaturedButtonPageIdValue ? " selected" : ""; ?>><?php echo htmlspecialchars((string) ($sitePageOption["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="field-group field-group-full js-link-panel <?php echo $departmentsFeaturedButtonLinkTypeValue === "custom" ? "" : "is-hidden"; ?>" data-link-panel="custom" data-link-scope="<?php echo htmlspecialchars($departmentsFeaturedButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_url", ENT_QUOTES, "UTF-8"); ?>">URL personalizada</label>
                                            <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_url", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][button_url]" value="<?php echo htmlspecialchars($departmentsFeaturedButtonUrlValue, ENT_QUOTES, "UTF-8"); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($isDepartmentsStandardButtonTextField): ?>
                                <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_text", ENT_QUOTES, "UTF-8"); ?>">Texto del botón</label>
                                <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_text", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][button_text]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">

                                <div class="button-destination-box">
                                    <div class="field-group field-group-full">
                                        <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_link_type", ENT_QUOTES, "UTF-8"); ?>">Tipo de enlace</label>
                                        <select class="form-select js-link-type" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_link_type", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][button_link_type]" data-link-scope="<?php echo htmlspecialchars($departmentsStandardButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <option value="internal"<?php echo $departmentsStandardButtonLinkTypeValue === "internal" ? " selected" : ""; ?>>P&aacute;gina interna</option>
                                            <option value="custom"<?php echo $departmentsStandardButtonLinkTypeValue === "custom" ? " selected" : ""; ?>>URL personalizada</option>
                                        </select>
                                    </div>

                                    <div class="button-destination-grid">
                                        <div class="field-group field-group-full js-link-panel <?php echo $departmentsStandardButtonLinkTypeValue === "internal" ? "" : "is-hidden"; ?>" data-link-panel="internal" data-link-scope="<?php echo htmlspecialchars($departmentsStandardButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_page_id", ENT_QUOTES, "UTF-8"); ?>">P&aacute;gina interna</label>
                                            <select class="form-select" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_page_id", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][button_page_id]">
                                                <option value="">Selecciona una p&aacute;gina</option>
                                                <?php foreach ($linkableSitePages as $sitePageOption): ?>
                                                    <option value="<?php echo (int) ($sitePageOption["id"] ?? 0); ?>"<?php echo (string) ($sitePageOption["id"] ?? "") === $departmentsStandardButtonPageIdValue ? " selected" : ""; ?>><?php echo htmlspecialchars((string) ($sitePageOption["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="field-group field-group-full js-link-panel <?php echo $departmentsStandardButtonLinkTypeValue === "custom" ? "" : "is-hidden"; ?>" data-link-panel="custom" data-link-scope="<?php echo htmlspecialchars($departmentsStandardButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_url", ENT_QUOTES, "UTF-8"); ?>">URL personalizada</label>
                                            <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_url", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][button_url]" value="<?php echo htmlspecialchars($departmentsStandardButtonUrlValue, ENT_QUOTES, "UTF-8"); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($isEmergencyContactButtonTextField): ?>
                                <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_text", ENT_QUOTES, "UTF-8"); ?>">Texto del botón</label>
                                <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_text", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][button_text]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">

                                <div class="button-destination-box">
                                    <div class="field-group field-group-full" style="display: none;">
                                        <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_link_type", ENT_QUOTES, "UTF-8"); ?>">Tipo de enlace</label>
                                        <select class="form-select js-link-type" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_link_type", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][button_link_type]" data-link-scope="<?php echo htmlspecialchars($emergencyContactButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <option value="internal">P&aacute;gina interna</option>
                                            <option value="custom" selected>URL personalizada</option>
                                        </select>
                                    </div>

                                    <div class="button-destination-grid">
                                        <div class="field-group field-group-full js-link-panel <?php echo $emergencyContactButtonLinkTypeValue === "internal" ? "" : "is-hidden"; ?>" data-link-panel="internal" data-link-scope="<?php echo htmlspecialchars($emergencyContactButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>" style="display: none;">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_page_id", ENT_QUOTES, "UTF-8"); ?>">P&aacute;gina interna</label>
                                            <select class="form-select" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_page_id", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][button_page_id]">
                                                <option value="">Selecciona una p&aacute;gina</option>
                                                <?php foreach ($linkableSitePages as $sitePageOption): ?>
                                                    <option value="<?php echo (int) ($sitePageOption["id"] ?? 0); ?>"<?php echo (string) ($sitePageOption["id"] ?? "") === $emergencyContactButtonPageIdValue ? " selected" : ""; ?>><?php echo htmlspecialchars((string) ($sitePageOption["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="field-group field-group-full js-link-panel" data-link-panel="custom" data-link-scope="<?php echo htmlspecialchars($emergencyContactButtonLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_url", ENT_QUOTES, "UTF-8"); ?>">Teléfono o enlace del botón</label>
                                            <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_button_url", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][button_url]" value="<?php echo htmlspecialchars($emergencyContactButtonUrlValue, ENT_QUOTES, "UTF-8"); ?>">
                                        </div>
                                    </div>
                                </div>

                            <?php elseif ($repeaterKey === "featured_doctors" && $fieldKey === "availability_status"): ?>
                                <select class="form-select" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>]" data-doctor-availability-item="<?php echo $itemIndex; ?>" data-doctor-availability-role="status">
                                    <option value="online"<?php echo $fieldValue === "online" ? " selected" : ""; ?>>Disponible</option>
                                    <option value="offline"<?php echo $fieldValue === "offline" ? " selected" : ""; ?>>No disponible</option>
                                    <option value="busy"<?php echo $fieldValue === "busy" ? " selected" : ""; ?>>En consulta</option>
                                </select>
                            <?php elseif ($repeaterKey === "featured_doctors" && $fieldKey === "availability_text"): ?>
                                <select class="form-select" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>]" data-doctor-availability-item="<?php echo $itemIndex; ?>" data-doctor-availability-role="text">
                                    <option value="Disponible"<?php echo $fieldValue === "Disponible" ? " selected" : ""; ?>>Disponible</option>
                                    <option value="No disponible"<?php echo $fieldValue === "No disponible" ? " selected" : ""; ?>>No disponible</option>
                                    <option value="En consulta"<?php echo $fieldValue === "En consulta" ? " selected" : ""; ?>>En consulta</option>
                                </select>
                            <?php elseif ($repeaterKey === "emergency_contacts" && $fieldKey === "variant"): ?>
                                <select class="form-select" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>]">
                                    <option value=""<?php echo $fieldValue === "" ? " selected" : ""; ?>>No urgente</option>
                                    <option value="urgent"<?php echo $fieldValue === "urgent" ? " selected" : ""; ?>>Urgente</option>
                                </select>
                            <?php elseif ($isQuickActionUrlField): ?>
                                <div class="button-destination-box">
                                    <div class="field-group field-group-full">
                                        <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_type", ENT_QUOTES, "UTF-8"); ?>">Tipo de enlace</label>
                                        <select class="form-select js-link-type js-quick-action-link-type" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_link_type", ENT_QUOTES, "UTF-8"); ?>" data-link-scope="<?php echo htmlspecialchars($quickActionLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <option value="internal"<?php echo $quickActionLinkTypeValue === "internal" ? " selected" : ""; ?>>P&aacute;gina interna</option>
                                            <option value="custom"<?php echo $quickActionLinkTypeValue === "custom" ? " selected" : ""; ?>>URL personalizada</option>
                                        </select>
                                    </div>

                                    <div class="button-destination-grid">
                                        <div class="field-group field-group-full js-link-panel <?php echo $quickActionLinkTypeValue === "internal" ? "" : "is-hidden"; ?>" data-link-panel="internal" data-link-scope="<?php echo htmlspecialchars($quickActionLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_page_url", ENT_QUOTES, "UTF-8"); ?>">P&aacute;gina interna</label>
                                            <select class="form-select js-quick-action-page" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_page_url", ENT_QUOTES, "UTF-8"); ?>" data-link-scope="<?php echo htmlspecialchars($quickActionLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                                <option value="">Selecciona una p&aacute;gina</option>
                                                <?php foreach ($linkableSitePages as $sitePageOption): ?>
                                                    <?php $sitePagePublicUrl = (string) ($sitePageOption["public_url"] ?? ""); ?>
                                                    <option value="<?php echo (int) ($sitePageOption["id"] ?? 0); ?>" data-public-url="<?php echo htmlspecialchars($sitePagePublicUrl, ENT_QUOTES, "UTF-8"); ?>"<?php echo (string) ($sitePageOption["id"] ?? "") === $quickActionMatchedPageId ? " selected" : ""; ?>><?php echo htmlspecialchars((string) ($sitePageOption["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="field-group field-group-full js-link-panel <?php echo $quickActionLinkTypeValue === "custom" ? "" : "is-hidden"; ?>" data-link-panel="custom" data-link-scope="<?php echo htmlspecialchars($quickActionLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                            <label class="field-label" for="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_url", ENT_QUOTES, "UTF-8"); ?>">URL personalizada</label>
                                            <input class="form-input js-quick-action-url" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_url", ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][url]" value="<?php echo htmlspecialchars($quickActionUrlValue, ENT_QUOTES, "UTF-8"); ?>" data-link-scope="<?php echo htmlspecialchars($quickActionLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($repeaterKey === "services" && $fieldKey === "category_key" && $serviceCategoryOptions !== []): ?>
                                <select class="form-select" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>]">
                                    <?php if ($fieldValue !== "" && !isset($serviceCategoryOptions[$fieldValue])): ?>
                                        <option value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>" selected><?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?></option>
                                    <?php endif; ?>
                                    <?php foreach ($serviceCategoryOptions as $categoryKey => $categoryLabel): ?>
                                        <option value="<?php echo htmlspecialchars($categoryKey, ENT_QUOTES, "UTF-8"); ?>"<?php echo $fieldValue === $categoryKey ? " selected" : ""; ?>><?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, "UTF-8"); ?></option>
                                    <?php endforeach; ?>
                                </select>                            <?php else: ?>
                                <input class="form-input" type="text" id="repeater_<?php echo htmlspecialchars($repeaterKey . "_" . $itemIndex . "_" . $fieldKey, ENT_QUOTES, "UTF-8"); ?>" name="repeaters[<?php echo htmlspecialchars($repeaterKey, ENT_QUOTES, "UTF-8"); ?>][<?php echo $itemIndex; ?>][fields][<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>"<?php echo $repeaterKey === "info_cards" && $fieldKey === "title" ? ' data-contact-info-card-title-input="' . $itemIndex . '"' : ""; ?>>
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
            $isVisible = (int) ($_POST["simple_fields"][$fieldKey]["is_visible"] ?? 0) === 1 ? 1 : 0;

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

                    $itemVisible = (int) ($_POST["repeaters"][$repeaterKey][$itemIndex]["is_visible"] ?? 0) === 1 ? 1 : 0;
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

                        if ($repeaterKey === "doctors") {
                            $fieldVisible = isset($_POST["repeaters"][$repeaterKey][$itemIndex]["fields_visible"][$fieldKey]) ? "1" : "0";

                            if (!upsertRepeaterItemField($conn, $repeaterItemId, "__visible_" . $fieldKey, "visibility", $fieldVisible)) {
                                $errors[] = "No fue posible guardar la visibilidad de los campos de doctores.";
                                break 3;
                            }
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
} elseif (($schema["template_key"] ?? "") === "departments") {
    [$linkableSitePages, $linkableSitePagesById] = getPageContentLinkablePages($conn, true);
    $simpleFieldGroups = [
        [
            "title" => "Encabezado",
            "description" => "Titulo y subtitulo principal de la pagina.",
            "field_keys" => ["hero_title", "hero_subtitle"],
        ],
    ];
} elseif (($schema["template_key"] ?? "") === "department-details") {
    [$linkableSitePages, $linkableSitePagesById] = getPageContentLinkablePages($conn, true);
} elseif (($schema["template_key"] ?? "") === "service-details") {
    $simpleFieldGroups = [
        [
            "title" => "Encabezado",
            "description" => "Titulo y subtitulo principal de la pagina.",
            "field_keys" => ["hero_title", "hero_subtitle"],
        ],
        [
            "title" => "Área informativa",
            "description" => "",
            "field_keys" => ["service_image", "service_image_alt", "service_tag", "service_title", "service_tagline", "service_text_1", "service_text_2", "features_title", "primary_button_text", "primary_button_url", "secondary_button_text", "secondary_button_url"],
        ],
        [
            "title" => "Agendamiento",
            "description" => "",
            "field_keys" => ["booking_title", "booking_text", "appointment_title", "appointment_text", "appointment_button_text", "appointment_button_url", "appointment_alternative_text", "appointment_phone_text", "appointment_phone_url"],
        ],
    ];
} elseif (($schema["template_key"] ?? "") === "doctors") {
    [$linkableSitePages, $linkableSitePagesById] = getPageContentLinkablePages($conn, true);
} elseif (($schema["template_key"] ?? "") === "contact") {
    $simpleFieldGroups = [
        [
            "title" => "Texto introductorio",
            "description" => "",
            "field_keys" => ["hero_title", "hero_subtitle"],
        ],
        [
            "title" => "Área informativa",
            "description" => "",
            "field_keys" => ["info_title", "info_text", "social_title"],
        ],
        [
            "title" => "Área de formulario",
            "description" => "",
            "field_keys" => ["map_embed_url", "form_title", "form_text", "form_name_label", "form_email_label", "form_subject_label", "form_message_label", "form_loading_text", "form_sent_text", "form_button_text"],
        ],
    ];
} elseif (($schema["template_key"] ?? "") === "services") {
    [$linkableSitePages, $linkableSitePagesById] = getPageContentLinkablePages($conn, true);
    $simpleFieldGroups = [
        [
            "title" => "Encabezado",
            "description" => "Titulo y subtitulo principal de la pagina.",
            "field_keys" => ["hero_title", "hero_subtitle"],
        ],
    ];
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
            "field_keys" => ["home_about_title", "home_about_lead", "home_about_text"],
        ],
        [
            "title" => "Sobre nosotros - Años de experiencia",
            "description" => "Datos del bloque de experiencia en Sobre nosotros.",
            "field_keys" => ["home_about_experience_years", "home_about_experience_text"],
        ],
        [
            "title" => "Sobre nosotros - Botones",
            "description" => "Botones del bloque Sobre nosotros.",
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
            "title" => "Sobre nosotros - Imagen",
            "description" => "Imagen del bloque Sobre nosotros.",
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
            "title" => "Certificaciones",
            "description" => "Encabezado del bloque de certificaciones.",
            "field_keys" => ["home_about_certifications_title"],
        ],
        [
            "title" => "Especialidades destacadas",
            "description" => "Encabezado del bloque de especialidades destacadas.",
            "field_keys" => ["featured_departments_title", "featured_departments_text"],
        ],
        [
            "title" => "Servicios destacados",
            "description" => "Encabezado del bloque de servicios destacados.",
            "field_keys" => ["featured_services_title", "featured_services_text"],
        ],
        [
            "title" => "Encuentra a tu especialista",
            "description" => "Textos y controles del buscador de doctores.",
            "field_keys" => ["find_doctor_title", "find_doctor_text", "doctor_search_placeholder", "doctor_specialty_placeholder", "doctor_search_button_text"],
        ],
        [
            "title" => "Llamado a la acción",
            "description" => "Contenido principal del llamado a la acción.",
            "field_keys" => ["cta_title", "cta_text"],
        ],
        [
            "title" => "Llamado a la acción - Botones",
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
            "title" => "Llamado a la acción - Emergencia",
            "description" => "Bloque secundario de emergencia del llamado a la acción.",
            "field_keys" => ["cta_emergency_title", "cta_emergency_text", "cta_emergency_button_text", "cta_emergency_button_url"],
        ],        [
            "title" => "Información de emergencia",
            "description" => "Encabezados del bloque de información de emergencia.",
            "field_keys" => ["emergency_info_title", "emergency_info_text"],
        ],
        [
            "title" => "Banner de emergencia",
            "description" => "Banner principal de la sección de emergencia.",
            "field_keys" => ["emergency_banner_title", "emergency_banner_text", "emergency_banner_button_text", "emergency_banner_button_url"],
        ],
        [
            "title" => "Acciones rápidas",
            "description" => "Encabezado de la sección de accesos rápidos.",
            "field_keys" => ["quick_actions_title"],
        ],
        [
            "title" => "Consejos de emergencia",
            "description" => "Encabezado del bloque de consejos de emergencia.",
            "field_keys" => ["emergency_tips_title"],
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
        .topbar { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 24px; padding: 12px 0; position: sticky; top: 0; z-index: 40; background: #f4f6f9; border-bottom: 1px solid #e5e7eb; box-shadow: 0 4px 12px rgba(0,0,0,.04); }
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
        .section-block.hero-features-admin-section > h3 { font-size: 17px; }
        .hero-features-admin-section .item-title h3 { font-size: 17px; }
        .section-block.home-about-features-admin-section > h3 { font-size: 17px; }
        .home-about-features-admin-section .item-title h3 { font-size: 17px; }
        .section-block.home-about-features-admin-section { background: #ffffff; }
        .home-about-features-admin-section .card { background: #f9fafb; }
        .section-block.home-certifications-admin-section { background: #fff; }
        .home-certifications-admin-section .card { background: #f9fafb; }
        .section-block.home-certifications-admin-section > h3 { font-size: 17px; }
        .home-certifications-admin-section .item-title h3 { font-size: 17px; }
        .section-block.featured-departments-admin-section { background: #fff; }
        .featured-departments-admin-section .card { background: #f9fafb; }
        .section-block.featured-departments-admin-section > h3 { font-size: 17px; }
        .featured-departments-admin-section .item-title h3 { font-size: 17px; }
        .section-block.departments-admin-section { background: #fff; margin-top: 28px; }
        .departments-admin-section .card { background: #f9fafb; }
        .section-block.departments-admin-section > h3 { font-size: 17px; }
        .departments-admin-section .item-title h3 { font-size: 17px; }
        .section-block.featured-services-admin-section { background: #fff; }
        .featured-services-admin-section .card { background: #f9fafb; }
        .section-block.services-admin-section { background: #fff; }
        .services-admin-section .card { background: #f9fafb; }
        .section-block.services-cta-admin-section { background: #fff; }
        .section-block.service-categories-admin-section { background: #fff; margin-top: 16px; }
        .service-categories-admin-section .card { background: #f9fafb; }
        .section-block.cta-features-admin-section { background: #fff; }
        .cta-features-admin-section .card { background: #f9fafb; }
        .section-block.cta-features-admin-section > h3 { font-size: 17px; }
        .cta-features-admin-section .item-title h3 { font-size: 17px; }
        .section-block.featured-doctors-admin-section { background: #fff; }
        .featured-doctors-admin-section .card { background: #f9fafb; }
        .section-block.doctors-admin-section { background: #fff; margin-top: 28px; }
        .doctors-admin-section .card { background: #f9fafb; }
        .section-block.emergency-contacts-admin-section { background: #fff; }
        .emergency-contacts-admin-section .card { background: #f9fafb; }
        .section-block.contact-info-cards-admin-section { background: #fff; }
        .contact-info-cards-admin-section .card { background: #f9fafb; }
        .section-block.contact-info-cards-admin-section > h3 { font-size: 17px; }
        .contact-info-cards-admin-section .item-title h3 { font-size: 17px; }
        .section-block.contact-social-links-admin-section { background: #fff; }
        .contact-social-links-admin-section .card { background: #f9fafb; }
        .section-block.department-intro-admin-section { background: #fff; }
        .section-block.department-info-one-admin-section { background: #fff; }
        .department-info-one-repeater { margin-top: 16px; }
        .section-block.department-stats-admin-section { background: #fff; }
        .department-stats-admin-section .card { background: #f9fafb; }
        .section-block.department-info-two-admin-section { background: #fff; }
        .section-block.department-cta-admin-section { background: #f9fafb; }
        .section-block.quick-actions-admin-section { background: #fff; }
        .quick-actions-admin-section .card { background: #f9fafb; }
        .section-block.emergency-tips-admin-section { background: #fff; margin-top: 16px; }
        .emergency-tips-admin-section .card { background: #f9fafb; }
        .section-block.emergency-contacts-admin-section > h3 { font-size: 17px; }
        .emergency-contacts-admin-section .item-title h3 { font-size: 17px; }
        .section-block.featured-doctors-admin-section > h3 { font-size: 17px; }
        .featured-doctors-admin-section .item-title h3 { font-size: 17px; }
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
                        <button type="submit" form="content-form" class="btn btn-primary">Guardar contenido</button>
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

                    <form id="content-form" action="content.php?id=<?php echo (int) $page["id"]; ?>" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>">

                        <div class="section-block<?php echo $sectionClass !== "" ? " " . htmlspecialchars($sectionClass, ENT_QUOTES, "UTF-8") : ""; ?><?php echo $repeaterKey === "hero_features" ? " hero-features-admin-section" : ""; ?><?php echo $repeaterKey === "home_about_features" ? " home-about-features-admin-section" : ""; ?><?php echo $repeaterKey === "home_certifications" ? " home-certifications-admin-section" : ""; ?><?php echo $repeaterKey === "featured_departments" ? " featured-departments-admin-section" : ""; ?><?php echo $repeaterKey === "featured_services" ? " featured-services-admin-section" : ""; ?><?php echo $repeaterKey === "featured_doctors" ? " featured-doctors-admin-section" : ""; ?><?php echo $repeaterKey === "cta_features" ? " cta-features-admin-section" : ""; ?><?php echo $repeaterKey === "emergency_contacts" ? " emergency-contacts-admin-section" : ""; ?><?php echo $repeaterKey === "quick_actions" ? " quick-actions-admin-section" : ""; ?><?php echo $repeaterKey === "departments" ? " departments-admin-section" : ""; ?><?php echo $repeaterKey === "emergency_tips" ? " emergency-tips-admin-section" : ""; ?>">
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
                                                                    <?php if ($templateKey === "home" && $textKey === "hero_primary_cta_text") { $buttonTextLabel = "Texto del botón principal"; } elseif ($templateKey === "home" && $textKey === "hero_secondary_cta_text") { $buttonTextLabel = "Texto del botón secundario"; } elseif ($templateKey === "home" && $textKey === "home_about_primary_cta_text") { $buttonTextLabel = "Texto del botón principal de Sobre nosotros"; } elseif ($templateKey === "home" && $textKey === "home_about_secondary_cta_text") { $buttonTextLabel = "Texto del botón secundario de Sobre nosotros"; } ?>
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
                                                                        <?php if ($templateKey === "home" && $altKey === "hero_image_alt") { $altLabelHtml = "Texto alternativo imagen principal"; } elseif ($templateKey === "home" && $altKey === "home_about_image_alt") { $altLabelHtml = "Texto alternativo imagen de Sobre nosotros"; } ?>
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
                                                        $hideVisibilityToggle = $templateKey === "home" && $groupFieldKey === "cta_emergency_button_url";
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
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "emergency_info_title") {
                                                            $displayLabelHtml = "Título de la sección";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "emergency_info_text") {
                                                            $displayLabelHtml = "Descripción de la sección";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "home_about_certifications_title") {
                                                            $displayLabelHtml = "Título de certificaciones";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "featured_departments_title") {
                                                            $displayLabelHtml = "Título de especialidades destacadas";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "featured_departments_text") {
                                                            $displayLabelHtml = "Texto introductorio de especialidades destacadas";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "featured_services_title") {
                                                            $displayLabelHtml = "Título de servicios destacados";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "featured_services_text") {
                                                            $displayLabelHtml = "Texto introductorio de servicios destacados";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "find_doctor_title") {
                                                            $displayLabelHtml = "Título de búsqueda de especialistas";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "find_doctor_text") {
                                                            $displayLabelHtml = "Texto introductorio de búsqueda de especialistas";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "doctor_search_placeholder") {
                                                            $displayLabelHtml = "Placeholder del buscador de especialista";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "doctor_specialty_placeholder") {
                                                            $displayLabelHtml = "Placeholder del selector de especialidad";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "doctor_search_button_text") {
                                                            $displayLabelHtml = "Texto del botón de búsqueda";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "cta_title") {
                                                            $displayLabelHtml = "Título del llamado a la acción";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "cta_text") {
                                                            $displayLabelHtml = "Texto descriptivo del llamado a la acción";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "cta_emergency_title") {
                                                            $displayLabelHtml = "Título";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "cta_emergency_text") {
                                                            $displayLabelHtml = "Texto";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "cta_emergency_button_text") {
                                                            $displayLabelHtml = "Texto del botón";
                                                        } elseif ($templateKey === "home" && $groupFieldKey === "cta_emergency_button_url") {
                                                            $displayLabelHtml = "Teléfono o enlace del botón";
                                                        } elseif ($groupFieldKey === "emergency_banner_title") {
                                                            $displayLabelHtml = "Título del banner";
                                                        } elseif ($groupFieldKey === "emergency_banner_text") {
                                                            $displayLabelHtml = "Texto del banner";
                                                        } elseif ($groupFieldKey === "emergency_banner_button_text") {
                                                            $displayLabelHtml = "Texto del botón";
                                                        } elseif ($groupFieldKey === "emergency_banner_button_url") {
                                                            $displayLabelHtml = "Teléfono o enlace del botón";
                                                        } elseif ($templateKey === "contact" && $groupFieldKey === "map_embed_url") {
                                                            $displayLabelHtml = "Ubicación en Google Maps";
                                                        } elseif ($templateKey === "contact" && $groupFieldKey === "form_title") {
                                                            $displayLabelHtml = "Título";
                                                        } elseif ($templateKey === "contact" && $groupFieldKey === "form_text") {
                                                            $displayLabelHtml = "Descripción";
                                                        } elseif ($templateKey === "contact" && $groupFieldKey === "form_name_label") {
                                                            $displayLabelHtml = "Texto dentro del campo Nombre";
                                                        } elseif ($templateKey === "contact" && $groupFieldKey === "form_email_label") {
                                                            $displayLabelHtml = "Texto dentro del campo Correo electrónico";
                                                        } elseif ($templateKey === "contact" && $groupFieldKey === "form_subject_label") {
                                                            $displayLabelHtml = "Texto dentro del campo Asunto";
                                                        } elseif ($templateKey === "contact" && $groupFieldKey === "form_message_label") {
                                                            $displayLabelHtml = "Texto dentro del campo Mensaje";
                                                        } elseif ($templateKey === "contact" && $groupFieldKey === "form_loading_text") {
                                                            $displayLabelHtml = "Mensaje de carga";
                                                        } elseif ($templateKey === "contact" && $groupFieldKey === "form_sent_text") {
                                                            $displayLabelHtml = "Mensaje de éxito";
                                                        } elseif ($templateKey === "contact" && $groupFieldKey === "form_button_text") {
                                                            $displayLabelHtml = "Texto del botón";
                                                        } elseif ($isIntroGroup && $groupFieldKey === "intro_text_1") {
                                                            $displayLabelHtml = "P&aacute;rrafo 1";
                                                        } elseif ($isIntroGroup && $groupFieldKey === "intro_text_2") {
                                                            $displayLabelHtml = "P&aacute;rrafo 2";
                                                        }
                                                        ?>
                                                        <div class="field-group <?php echo $isTextarea ? "field-group-full" : ""; ?>">
                                                            <div class="field-header">
                                                                <label class="field-label" for="simple_<?php echo htmlspecialchars($groupFieldKey, ENT_QUOTES, "UTF-8"); ?>"><?php echo $displayLabelHtml; ?></label>
                                                                <?php if ($hideVisibilityToggle): ?>
                                                                    <input type="hidden" name="simple_fields[<?php echo htmlspecialchars($groupFieldKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="<?php echo $fieldVisible ? "1" : "0"; ?>">
                                                                <?php else: ?>
                                                                    <label class="toggle-row">
                                                                        <?php if (in_array($groupFieldKey, ["home_about_experience_years", "home_about_experience_text"], true)): ?>
                                                                            <input type="hidden" name="simple_fields[<?php echo htmlspecialchars($groupFieldKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="0">
                                                                        <?php endif; ?>
                                                                        <input type="checkbox" name="simple_fields[<?php echo htmlspecialchars($groupFieldKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1"<?php echo $fieldVisible ? " checked" : ""; ?>>
                                                                        <span>Mostrar</span>
                                                                    </label>
                                                                <?php endif; ?>
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
                                                        <?php if ($templateKey === "contact" && ((string) ($groupConfig["title"] ?? "")) === "Área informativa" && $groupFieldKey === "info_text"): ?>
                                                            <div class="field-group-full">
                                                                <?php foreach ($schema["repeaters"] as $contactRepeaterConfig): ?>
                                                                    <?php if (((string) ($contactRepeaterConfig["repeater_key"] ?? "")) === "info_cards"): ?>
                                                                        <?php renderAdminRepeaterSection($contactRepeaterConfig, $contentData, "contact-info-cards-admin-section"); ?>
                                                                        <?php break; ?>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($templateKey === "contact" && ((string) ($groupConfig["title"] ?? "")) === "Área informativa" && $groupFieldKey === "social_title"): ?>
                                                            <div class="field-group-full">
                                                                <?php foreach ($schema["repeaters"] as $contactRepeaterConfig): ?>
                                                                    <?php if (((string) ($contactRepeaterConfig["repeater_key"] ?? "")) === "social_links"): ?>
                                                                        <?php renderAdminRepeaterSection($contactRepeaterConfig, $contentData); ?>
                                                                        <?php break; ?>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
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
                                            <?php if ($templateKey === "home" && ((string) ($groupConfig["title"] ?? "")) === "Sobre nosotros"): ?>
                                                <?php foreach ($schema["repeaters"] as $repeaterConfig): ?>
                                                    <?php if (((string) ($repeaterConfig["repeater_key"] ?? "")) === "home_about_features"): ?>
                                                        <?php renderAdminRepeaterSection($repeaterConfig, $contentData); ?>
                                                        <?php break; ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <?php if ($templateKey === "home" && ((string) ($groupConfig["title"] ?? "")) === "Llamado a la acción - Botones"): ?>
                                                <?php foreach ($schema["repeaters"] as $repeaterConfig): ?>
                                                    <?php if (((string) ($repeaterConfig["repeater_key"] ?? "")) === "cta_features"): ?>
                                                        <?php renderAdminRepeaterSection($repeaterConfig, $contentData); ?>
                                                        <?php break; ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <?php if ($templateKey === "home" && ((string) ($groupConfig["title"] ?? "")) === "Especialidades destacadas"): ?>
                                                <?php foreach ($schema["repeaters"] as $repeaterConfig): ?>
                                                    <?php if (((string) ($repeaterConfig["repeater_key"] ?? "")) === "featured_departments"): ?>
                                                        <?php renderAdminRepeaterSection($repeaterConfig, $contentData); ?>
                                                        <?php break; ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <?php if ($templateKey === "home" && ((string) ($groupConfig["title"] ?? "")) === "Servicios destacados"): ?>
                                                <?php foreach ($schema["repeaters"] as $repeaterConfig): ?>
                                                    <?php if (((string) ($repeaterConfig["repeater_key"] ?? "")) === "featured_services"): ?>
                                                        <?php renderAdminRepeaterSection($repeaterConfig, $contentData); ?>
                                                        <?php break; ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <?php if ($templateKey === "home" && ((string) ($groupConfig["title"] ?? "")) === "Encuentra a tu especialista"): ?>
                                                <?php foreach ($schema["repeaters"] as $repeaterConfig): ?>
                                                    <?php if (((string) ($repeaterConfig["repeater_key"] ?? "")) === "featured_doctors"): ?>
                                                        <?php renderAdminRepeaterSection($repeaterConfig, $contentData); ?>
                                                        <?php break; ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <?php if ($templateKey === "home" && ((string) ($groupConfig["title"] ?? "")) === "Certificaciones"): ?>
                                                <?php foreach ($schema["repeaters"] as $repeaterConfig): ?>
                                                    <?php if (((string) ($repeaterConfig["repeater_key"] ?? "")) === "home_certifications"): ?>
                                                        <?php renderAdminRepeaterSection($repeaterConfig, $contentData); ?>
                                                        <?php break; ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <?php if ($templateKey === "home" && ((string) ($groupConfig["title"] ?? "")) === "Banner de emergencia"): ?>
                                                <?php foreach ($schema["repeaters"] as $repeaterConfig): ?>
                                                    <?php if (((string) ($repeaterConfig["repeater_key"] ?? "")) === "emergency_contacts"): ?>
                                                        <?php renderAdminRepeaterSection($repeaterConfig, $contentData); ?>
                                                        <?php break; ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <?php if ($templateKey === "home" && ((string) ($groupConfig["title"] ?? "")) === "Acciones rápidas"): ?>
                                                <?php foreach ($schema["repeaters"] as $repeaterConfig): ?>
                                                    <?php if (((string) ($repeaterConfig["repeater_key"] ?? "")) === "quick_actions"): ?>
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
                                        if ($templateKey === "department-details" && in_array($fieldKey, ["intro_title", "intro_text", "overview_image", "overview_image_alt", "experience_number", "experience_text", "key_services_title", "key_services_text", "cta_title", "cta_text", "cta_primary_text", "cta_primary_url", "cta_secondary_text", "cta_secondary_url", "cta_image", "cta_image_alt"], true)) {
                                            continue;
                                        }
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
                                        <?php if ($templateKey === "department-details" && $fieldKey === "hero_subtitle"): ?>
                                            <div class="field-group-full">
                                                <div class="section-block department-intro-admin-section">
                                                    <h3>Introducción</h3>
                                                    <div class="field-grid">
                                                        <?php foreach (["intro_title", "intro_text"] as $departmentIntroFieldKey): ?>
                                                            <?php
                                                            $departmentIntroFieldConfig = null;
                                                            foreach ($schema["simple_fields"] as $simpleFieldConfig) {
                                                                if ((string) ($simpleFieldConfig["field_key"] ?? "") === $departmentIntroFieldKey) {
                                                                    $departmentIntroFieldConfig = $simpleFieldConfig;
                                                                    break;
                                                                }
                                                            }
                                                            if (!is_array($departmentIntroFieldConfig)) {
                                                                continue;
                                                            }
                                                            $departmentIntroFieldData = $contentData["simple_fields"][$departmentIntroFieldKey] ?? null;
                                                            $departmentIntroFieldType = (string) ($departmentIntroFieldConfig["field_type"] ?? "text");
                                                            $departmentIntroFieldValue = (string) ($departmentIntroFieldData["field_value"] ?? "");
                                                            $departmentIntroFieldVisible = (int) ($departmentIntroFieldData["is_visible"] ?? 1) === 1;
                                                            $departmentIntroIsTextarea = $departmentIntroFieldType === "textarea";
                                                            $departmentIntroLabel = $departmentIntroFieldKey === "intro_title" ? "Título" : "Descripción";
                                                            ?>
                                                            <div class="field-group <?php echo $departmentIntroIsTextarea ? "field-group-full" : ""; ?>">
                                                                <div class="field-header">
                                                                    <label class="field-label" for="simple_<?php echo htmlspecialchars($departmentIntroFieldKey, ENT_QUOTES, "UTF-8"); ?>"><?php echo $departmentIntroLabel; ?></label>
                                                                    <label class="toggle-row">
                                                                        <input type="checkbox" name="simple_fields[<?php echo htmlspecialchars($departmentIntroFieldKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1"<?php echo $departmentIntroFieldVisible ? " checked" : ""; ?>>
                                                                        <span>Mostrar</span>
                                                                    </label>
                                                                </div>

                                                                <?php if ($departmentIntroIsTextarea): ?>
                                                                    <textarea class="form-textarea" id="simple_<?php echo htmlspecialchars($departmentIntroFieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($departmentIntroFieldKey, ENT_QUOTES, "UTF-8"); ?>][value]"><?php echo htmlspecialchars($departmentIntroFieldValue, ENT_QUOTES, "UTF-8"); ?></textarea>
                                                                <?php else: ?>
                                                                    <input class="form-input" type="text" id="simple_<?php echo htmlspecialchars($departmentIntroFieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($departmentIntroFieldKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($departmentIntroFieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="field-group-full">
                                                <div class="section-block department-info-one-admin-section">
                                                    <h3>Área informativa 1</h3>
                                                    <div class="field-grid">
                                                        <?php foreach (["overview_image", "overview_image_alt", "experience_number", "experience_text"] as $departmentInfoOneFieldKey): ?>
                                                            <?php
                                                            $departmentInfoOneFieldConfig = null;
                                                            foreach ($schema["simple_fields"] as $simpleFieldConfig) {
                                                                if ((string) ($simpleFieldConfig["field_key"] ?? "") === $departmentInfoOneFieldKey) {
                                                                    $departmentInfoOneFieldConfig = $simpleFieldConfig;
                                                                    break;
                                                                }
                                                            }
                                                            if (!is_array($departmentInfoOneFieldConfig)) {
                                                                continue;
                                                            }
                                                            $departmentInfoOneFieldData = $contentData["simple_fields"][$departmentInfoOneFieldKey] ?? null;
                                                            $departmentInfoOneFieldType = (string) ($departmentInfoOneFieldConfig["field_type"] ?? "text");
                                                            $departmentInfoOneFieldValue = (string) ($departmentInfoOneFieldData["field_value"] ?? "");
                                                            $departmentInfoOneFieldVisible = (int) ($departmentInfoOneFieldData["is_visible"] ?? 1) === 1;
                                                            $departmentInfoOneIsImage = $departmentInfoOneFieldType === "image";
                                                            $departmentInfoOneLabel = (string) ($departmentInfoOneFieldConfig["label"] ?? $departmentInfoOneFieldKey);
                                                            if ($departmentInfoOneFieldKey === "overview_image") {
                                                                $departmentInfoOneLabel = "Imagen";
                                                            } elseif ($departmentInfoOneFieldKey === "overview_image_alt") {
                                                                $departmentInfoOneLabel = "Texto alternativo de la imagen";
                                                            } elseif ($departmentInfoOneFieldKey === "experience_number") {
                                                                $departmentInfoOneLabel = "Número de experiencia";
                                                            }
                                                            ?>
                                                            <div class="field-group">
                                                                <div class="field-header">
                                                                    <label class="field-label" for="simple_<?php echo htmlspecialchars($departmentInfoOneFieldKey, ENT_QUOTES, "UTF-8"); ?>"><?php echo htmlspecialchars($departmentInfoOneLabel, ENT_QUOTES, "UTF-8"); ?></label>
                                                                    <label class="toggle-row">
                                                                        <input type="checkbox" name="simple_fields[<?php echo htmlspecialchars($departmentInfoOneFieldKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1"<?php echo $departmentInfoOneFieldVisible ? " checked" : ""; ?>>
                                                                        <span>Mostrar</span>
                                                                    </label>
                                                                </div>

                                                                <?php if ($departmentInfoOneIsImage): ?>
                                                                    <input type="hidden" name="simple_fields[<?php echo htmlspecialchars($departmentInfoOneFieldKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($departmentInfoOneFieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                                                    <div class="current-file"><strong>Archivo actual:</strong> <?php echo htmlspecialchars($departmentInfoOneFieldValue !== "" ? basename($departmentInfoOneFieldValue) : "Sin imagen seleccionada", ENT_QUOTES, "UTF-8"); ?></div>
                                                                    <label class="file-input-label" for="simple_file_<?php echo htmlspecialchars($departmentInfoOneFieldKey, ENT_QUOTES, "UTF-8"); ?>">Reemplazar imagen</label>
                                                                    <input class="form-file js-image-upload" type="file" id="simple_file_<?php echo htmlspecialchars($departmentInfoOneFieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($departmentInfoOneFieldKey, ENT_QUOTES, "UTF-8"); ?>][upload]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-preview-target="preview_<?php echo htmlspecialchars($departmentInfoOneFieldKey, ENT_QUOTES, "UTF-8"); ?>">
                                                                    <img id="preview_<?php echo htmlspecialchars($departmentInfoOneFieldKey, ENT_QUOTES, "UTF-8"); ?>" src="<?php echo $departmentInfoOneFieldValue !== "" ? "../../" . htmlspecialchars(ltrim($departmentInfoOneFieldValue, "/"), ENT_QUOTES, "UTF-8") : ""; ?>" alt="" class="preview-image<?php echo $departmentInfoOneFieldValue !== "" ? "" : " is-empty"; ?>">
                                                                <?php else: ?>
                                                                    <input class="form-input" type="text" id="simple_<?php echo htmlspecialchars($departmentInfoOneFieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($departmentInfoOneFieldKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($departmentInfoOneFieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <div class="department-info-one-repeater">
                                                        <?php foreach ($schema["repeaters"] as $departmentInfoOneRepeaterConfig): ?>
                                                            <?php if (((string) ($departmentInfoOneRepeaterConfig["repeater_key"] ?? "")) === "service_cards"): ?>
                                                                <?php renderAdminRepeaterSection($departmentInfoOneRepeaterConfig, $contentData); ?>
                                                                <?php break; ?>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php foreach ($schema["repeaters"] as $repeaterIndex => $repeaterConfig): ?>
                            <?php if (is_array($aboutStatsRepeaterConfig) && $repeaterIndex === 0): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <?php if ($templateKey === "home" && in_array((string) ($repeaterConfig["repeater_key"] ?? ""), ["hero_features", "home_about_features", "home_certifications", "cta_features", "featured_departments", "featured_services", "featured_doctors", "emergency_contacts", "quick_actions"], true)): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <?php if ($templateKey === "contact" && ((string) ($repeaterConfig["repeater_key"] ?? "")) === "info_cards"): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <?php if ($templateKey === "contact" && ((string) ($repeaterConfig["repeater_key"] ?? "")) === "social_links"): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <?php if ($templateKey === "department-details" && ((string) ($repeaterConfig["repeater_key"] ?? "")) === "service_cards"): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <?php if ($templateKey === "department-details" && ((string) ($repeaterConfig["repeater_key"] ?? "")) === "key_services"): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <?php if ($templateKey === "services" && ((string) ($repeaterConfig["repeater_key"] ?? "")) === "services"): ?>
                                <?php renderAdminRepeaterSection($repeaterConfig, $contentData, ($templateKey === "about" && $repeaterIndex === 1) ? "repeater-after-certifications" : "", "Servicios - generales", "general"); ?>
                                <?php renderAdminRepeaterSection($repeaterConfig, $contentData, "services-emergency-admin-section", "Servicio destacado de emergencia", "emergency"); ?>
                            <?php else: ?>
                                <?php renderAdminRepeaterSection($repeaterConfig, $contentData, ($templateKey === "about" && $repeaterIndex === 1) ? "repeater-after-certifications" : ""); ?>
                            <?php endif; ?>
                            <?php if ($templateKey === "department-details" && ((string) ($repeaterConfig["repeater_key"] ?? "")) === "stats"): ?>
                                <div class="field-group-full">
                                    <div class="section-block department-info-two-admin-section">
                                        <h3>Área informativa 2</h3>
                                        <div class="field-grid">
                                            <?php foreach (["key_services_title", "key_services_text"] as $departmentInfoTwoFieldKey): ?>
                                                <?php
                                                $departmentInfoTwoFieldConfig = null;
                                                foreach ($schema["simple_fields"] as $simpleFieldConfig) {
                                                    if ((string) ($simpleFieldConfig["field_key"] ?? "") === $departmentInfoTwoFieldKey) {
                                                        $departmentInfoTwoFieldConfig = $simpleFieldConfig;
                                                        break;
                                                    }
                                                }
                                                if (!is_array($departmentInfoTwoFieldConfig)) {
                                                    continue;
                                                }
                                                $departmentInfoTwoFieldData = $contentData["simple_fields"][$departmentInfoTwoFieldKey] ?? null;
                                                $departmentInfoTwoFieldType = (string) ($departmentInfoTwoFieldConfig["field_type"] ?? "text");
                                                $departmentInfoTwoFieldValue = (string) ($departmentInfoTwoFieldData["field_value"] ?? "");
                                                $departmentInfoTwoFieldVisible = (int) ($departmentInfoTwoFieldData["is_visible"] ?? 1) === 1;
                                                $departmentInfoTwoIsTextarea = $departmentInfoTwoFieldType === "textarea";
                                                $departmentInfoTwoIsImage = $departmentInfoTwoFieldType === "image";
                                                $departmentInfoTwoLabel = $departmentInfoTwoFieldKey === "key_services_title" ? "Título" : "Descripción";
                                                ?>
                                                <div class="field-group <?php echo $departmentInfoTwoIsTextarea ? "field-group-full" : ""; ?>">
                                                    <div class="field-header">
                                                        <label class="field-label" for="simple_<?php echo htmlspecialchars($departmentInfoTwoFieldKey, ENT_QUOTES, "UTF-8"); ?>"><?php echo $departmentInfoTwoLabel; ?></label>
                                                        <label class="toggle-row">
                                                            <input type="checkbox" name="simple_fields[<?php echo htmlspecialchars($departmentInfoTwoFieldKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1"<?php echo $departmentInfoTwoFieldVisible ? " checked" : ""; ?>>
                                                            <span>Mostrar</span>
                                                        </label>
                                                    </div>

                                                    <?php if ($departmentInfoTwoIsTextarea): ?>
                                                        <textarea class="form-textarea" id="simple_<?php echo htmlspecialchars($departmentInfoTwoFieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($departmentInfoTwoFieldKey, ENT_QUOTES, "UTF-8"); ?>][value]"><?php echo htmlspecialchars($departmentInfoTwoFieldValue, ENT_QUOTES, "UTF-8"); ?></textarea>
                                                    <?php elseif ($departmentInfoTwoIsImage): ?>
                                                        <input type="hidden" name="simple_fields[<?php echo htmlspecialchars($departmentInfoTwoFieldKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($departmentInfoTwoFieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                                        <div class="current-file"><strong>Archivo actual:</strong> <?php echo htmlspecialchars($departmentInfoTwoFieldValue !== "" ? basename($departmentInfoTwoFieldValue) : "Sin imagen seleccionada", ENT_QUOTES, "UTF-8"); ?></div>
                                                        <label class="file-input-label" for="simple_file_<?php echo htmlspecialchars($departmentInfoTwoFieldKey, ENT_QUOTES, "UTF-8"); ?>">Reemplazar imagen</label>
                                                        <input class="form-file js-image-upload" type="file" id="simple_file_<?php echo htmlspecialchars($departmentInfoTwoFieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($departmentInfoTwoFieldKey, ENT_QUOTES, "UTF-8"); ?>][upload]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-preview-target="preview_<?php echo htmlspecialchars($departmentInfoTwoFieldKey, ENT_QUOTES, "UTF-8"); ?>">
                                                        <img id="preview_<?php echo htmlspecialchars($departmentInfoTwoFieldKey, ENT_QUOTES, "UTF-8"); ?>" src="<?php echo $departmentInfoTwoFieldValue !== "" ? "../../" . htmlspecialchars(ltrim($departmentInfoTwoFieldValue, "/"), ENT_QUOTES, "UTF-8") : ""; ?>" alt="" class="preview-image<?php echo $departmentInfoTwoFieldValue !== "" ? "" : " is-empty"; ?>">
                                                    <?php else: ?>
                                                        <input class="form-input" type="text" id="simple_<?php echo htmlspecialchars($departmentInfoTwoFieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($departmentInfoTwoFieldKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($departmentInfoTwoFieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                            <div class="field-group-full">
                                                <?php foreach ($schema["repeaters"] as $departmentInfoTwoRepeaterConfig): ?>
                                                    <?php if (((string) ($departmentInfoTwoRepeaterConfig["repeater_key"] ?? "")) === "key_services"): ?>
                                                        <?php renderAdminRepeaterSection($departmentInfoTwoRepeaterConfig, $contentData); ?>
                                                        <?php break; ?>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="field-group-full">
                                                <div class="section-block department-cta-admin-section">
                                                    <h3>Llamado a la acción</h3>
                                                    <div class="field-grid">
                                                        <?php foreach (["cta_title", "cta_text", "cta_image", "cta_image_alt"] as $departmentCtaFieldKey): ?>
                                                            <?php
                                                            $departmentCtaFieldConfig = null;
                                                            foreach ($schema["simple_fields"] as $simpleFieldConfig) {
                                                                if ((string) ($simpleFieldConfig["field_key"] ?? "") === $departmentCtaFieldKey) {
                                                                    $departmentCtaFieldConfig = $simpleFieldConfig;
                                                                    break;
                                                                }
                                                            }
                                                            if (!is_array($departmentCtaFieldConfig)) {
                                                                continue;
                                                            }
                                                            $departmentCtaFieldData = $contentData["simple_fields"][$departmentCtaFieldKey] ?? null;
                                                            $departmentCtaFieldType = (string) ($departmentCtaFieldConfig["field_type"] ?? "text");
                                                            $departmentCtaFieldValue = (string) ($departmentCtaFieldData["field_value"] ?? "");
                                                            $departmentCtaFieldVisible = (int) ($departmentCtaFieldData["is_visible"] ?? 1) === 1;
                                                            $departmentCtaIsTextarea = $departmentCtaFieldType === "textarea";
                                                            $departmentCtaIsImage = $departmentCtaFieldType === "image";
                                                            $departmentCtaLabel = (string) ($departmentCtaFieldConfig["label"] ?? $departmentCtaFieldKey);
                                                            if ($departmentCtaFieldKey === "cta_title") {
                                                                $departmentCtaLabel = "Título";
                                                            } elseif ($departmentCtaFieldKey === "cta_text") {
                                                                $departmentCtaLabel = "Descripción";
                                                            } elseif ($departmentCtaFieldKey === "cta_image") {
                                                                $departmentCtaLabel = "Imagen";
                                                            } elseif ($departmentCtaFieldKey === "cta_image_alt") {
                                                                $departmentCtaLabel = "Texto alternativo de la imagen";
                                                            }
                                                            ?>
                                                            <div class="field-group <?php echo $departmentCtaIsTextarea ? "field-group-full" : ""; ?>">
                                                                <div class="field-header">
                                                                    <label class="field-label" for="simple_<?php echo htmlspecialchars($departmentCtaFieldKey, ENT_QUOTES, "UTF-8"); ?>"><?php echo $departmentCtaLabel; ?></label>
                                                                    <label class="toggle-row">
                                                                        <input type="checkbox" name="simple_fields[<?php echo htmlspecialchars($departmentCtaFieldKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1"<?php echo $departmentCtaFieldVisible ? " checked" : ""; ?>>
                                                                        <span>Mostrar</span>
                                                                    </label>
                                                                </div>

                                                                <?php if ($departmentCtaIsTextarea): ?>
                                                                    <textarea class="form-textarea" id="simple_<?php echo htmlspecialchars($departmentCtaFieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($departmentCtaFieldKey, ENT_QUOTES, "UTF-8"); ?>][value]"><?php echo htmlspecialchars($departmentCtaFieldValue, ENT_QUOTES, "UTF-8"); ?></textarea>
                                                                <?php elseif ($departmentCtaIsImage): ?>
                                                                    <input type="hidden" name="simple_fields[<?php echo htmlspecialchars($departmentCtaFieldKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($departmentCtaFieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                                                    <div class="current-file"><strong>Archivo actual:</strong> <?php echo htmlspecialchars($departmentCtaFieldValue !== "" ? basename($departmentCtaFieldValue) : "Sin imagen seleccionada", ENT_QUOTES, "UTF-8"); ?></div>
                                                                    <label class="file-input-label" for="simple_file_<?php echo htmlspecialchars($departmentCtaFieldKey, ENT_QUOTES, "UTF-8"); ?>">Reemplazar imagen</label>
                                                                    <input class="form-file js-image-upload" type="file" id="simple_file_<?php echo htmlspecialchars($departmentCtaFieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($departmentCtaFieldKey, ENT_QUOTES, "UTF-8"); ?>][upload]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-preview-target="preview_<?php echo htmlspecialchars($departmentCtaFieldKey, ENT_QUOTES, "UTF-8"); ?>">
                                                                    <img id="preview_<?php echo htmlspecialchars($departmentCtaFieldKey, ENT_QUOTES, "UTF-8"); ?>" src="<?php echo $departmentCtaFieldValue !== "" ? "../../" . htmlspecialchars(ltrim($departmentCtaFieldValue, "/"), ENT_QUOTES, "UTF-8") : ""; ?>" alt="" class="preview-image<?php echo $departmentCtaFieldValue !== "" ? "" : " is-empty"; ?>">
                                                                <?php else: ?>
                                                                    <input class="form-input" type="text" id="simple_<?php echo htmlspecialchars($departmentCtaFieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($departmentCtaFieldKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($departmentCtaFieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <?php foreach ([
                                                            ["title" => "Botón principal", "text_key" => "cta_primary_text", "url_key" => "cta_primary_url"],
                                                            ["title" => "Botón secundario", "text_key" => "cta_secondary_text", "url_key" => "cta_secondary_url"],
                                                        ] as $departmentCtaButtonConfig): ?>
                                                            <?php
                                                            $departmentCtaTextKey = (string) $departmentCtaButtonConfig["text_key"];
                                                            $departmentCtaUrlKey = (string) $departmentCtaButtonConfig["url_key"];
                                                            $departmentCtaTextData = $contentData["simple_fields"][$departmentCtaTextKey] ?? null;
                                                            $departmentCtaUrlData = $contentData["simple_fields"][$departmentCtaUrlKey] ?? null;
                                                            $departmentCtaTextValue = (string) ($departmentCtaTextData["field_value"] ?? "");
                                                            $departmentCtaUrlValue = (string) ($departmentCtaUrlData["field_value"] ?? "");
                                                            $departmentCtaTextVisible = (int) ($departmentCtaTextData["is_visible"] ?? 1) === 1;
                                                            $departmentCtaUrlVisible = (int) ($departmentCtaUrlData["is_visible"] ?? 1) === 1 ? 1 : 0;
                                                            $departmentCtaLinkScope = "department_details_" . $departmentCtaUrlKey;
                                                            $departmentCtaSelectedInternalUrl = "";

                                                            foreach ($linkableSitePages as $sitePageOption) {
                                                                $sitePagePublicUrl = (string) ($sitePageOption["public_url"] ?? "");

                                                                if ($sitePagePublicUrl !== "" && $sitePagePublicUrl === $departmentCtaUrlValue) {
                                                                    $departmentCtaSelectedInternalUrl = $sitePagePublicUrl;
                                                                    break;
                                                                }
                                                            }

                                                            $departmentCtaLinkTypeValue = $departmentCtaSelectedInternalUrl !== "" ? "internal" : "custom";
                                                            $departmentCtaCustomUrlValue = $departmentCtaLinkTypeValue === "custom" ? $departmentCtaUrlValue : "";
                                                            ?>
                                                            <div class="field-group field-group-full">
                                                                <div class="button-destination-box">
                                                                    <div class="field-header">
                                                                        <h4><?php echo htmlspecialchars((string) $departmentCtaButtonConfig["title"], ENT_QUOTES, "UTF-8"); ?></h4>
                                                                        <label class="toggle-row">
                                                                            <input type="checkbox" name="simple_fields[<?php echo htmlspecialchars($departmentCtaTextKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1"<?php echo $departmentCtaTextVisible ? " checked" : ""; ?>>
                                                                            <span>Mostrar</span>
                                                                        </label>
                                                                    </div>
                                                                    <input type="hidden" name="simple_fields[<?php echo htmlspecialchars($departmentCtaUrlKey, ENT_QUOTES, "UTF-8"); ?>][value]" class="js-department-cta-url" data-link-scope="<?php echo htmlspecialchars($departmentCtaLinkScope, ENT_QUOTES, "UTF-8"); ?>" value="<?php echo htmlspecialchars($departmentCtaUrlValue, ENT_QUOTES, "UTF-8"); ?>">
                                                                    <input type="hidden" name="simple_fields[<?php echo htmlspecialchars($departmentCtaUrlKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="<?php echo $departmentCtaUrlVisible; ?>">
                                                                    <div class="field-group field-group-full">
                                                                        <label class="field-label" for="simple_<?php echo htmlspecialchars($departmentCtaTextKey, ENT_QUOTES, "UTF-8"); ?>">Texto botón <?php echo $departmentCtaTextKey === "cta_primary_text" ? "principal" : "secundario"; ?></label>
                                                                        <input class="form-input" type="text" id="simple_<?php echo htmlspecialchars($departmentCtaTextKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($departmentCtaTextKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($departmentCtaTextValue, ENT_QUOTES, "UTF-8"); ?>">
                                                                    </div>
                                                                    <div class="button-destination-grid">
                                                                        <div class="field-group field-group-full">
                                                                            <label class="field-label" for="simple_<?php echo htmlspecialchars($departmentCtaUrlKey, ENT_QUOTES, "UTF-8"); ?>_link_type">Tipo de enlace</label>
                                                                            <select class="form-select js-link-type js-department-cta-link-type" id="simple_<?php echo htmlspecialchars($departmentCtaUrlKey, ENT_QUOTES, "UTF-8"); ?>_link_type" data-link-scope="<?php echo htmlspecialchars($departmentCtaLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                                                                <option value="internal"<?php echo $departmentCtaLinkTypeValue === "internal" ? " selected" : ""; ?>>P&aacute;gina interna</option>
                                                                                <option value="custom"<?php echo $departmentCtaLinkTypeValue === "custom" ? " selected" : ""; ?>>URL personalizada</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="field-group field-group-full js-link-panel <?php echo $departmentCtaLinkTypeValue === "internal" ? "" : "is-hidden"; ?>" data-link-panel="internal" data-link-scope="<?php echo htmlspecialchars($departmentCtaLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                                                            <label class="field-label" for="simple_<?php echo htmlspecialchars($departmentCtaUrlKey, ENT_QUOTES, "UTF-8"); ?>_page">P&aacute;gina interna</label>
                                                                            <select class="form-select js-department-cta-page" id="simple_<?php echo htmlspecialchars($departmentCtaUrlKey, ENT_QUOTES, "UTF-8"); ?>_page" data-link-scope="<?php echo htmlspecialchars($departmentCtaLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                                                                <option value="">Selecciona una p&aacute;gina</option>
                                                                                <?php foreach ($linkableSitePages as $sitePageOption): ?>
                                                                                    <?php $sitePagePublicUrl = (string) ($sitePageOption["public_url"] ?? ""); ?>
                                                                                    <option value="<?php echo htmlspecialchars($sitePagePublicUrl, ENT_QUOTES, "UTF-8"); ?>" data-public-url="<?php echo htmlspecialchars($sitePagePublicUrl, ENT_QUOTES, "UTF-8"); ?>"<?php echo $sitePagePublicUrl !== "" && $sitePagePublicUrl === $departmentCtaSelectedInternalUrl ? " selected" : ""; ?>><?php echo htmlspecialchars((string) ($sitePageOption["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="field-group field-group-full js-link-panel <?php echo $departmentCtaLinkTypeValue === "custom" ? "" : "is-hidden"; ?>" data-link-panel="custom" data-link-scope="<?php echo htmlspecialchars($departmentCtaLinkScope, ENT_QUOTES, "UTF-8"); ?>">
                                                                            <label class="field-label" for="simple_<?php echo htmlspecialchars($departmentCtaUrlKey, ENT_QUOTES, "UTF-8"); ?>_custom">URL personalizada</label>
                                                                            <input class="form-input js-department-cta-custom-url" type="text" id="simple_<?php echo htmlspecialchars($departmentCtaUrlKey, ENT_QUOTES, "UTF-8"); ?>_custom" data-link-scope="<?php echo htmlspecialchars($departmentCtaLinkScope, ENT_QUOTES, "UTF-8"); ?>" value="<?php echo htmlspecialchars($departmentCtaCustomUrlValue, ENT_QUOTES, "UTF-8"); ?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($templateKey === "services" && ((string) ($repeaterConfig["repeater_key"] ?? "")) === "services"): ?>
                                <div class="section-block services-cta-admin-section">
                                    <h3>Servicios - Invitación a agendar</h3>

                                    <div class="field-grid">
                                        <?php foreach ($schema["simple_fields"] as $fieldConfig): ?>
                                            <?php
                                            $fieldKey = (string) ($fieldConfig["field_key"] ?? "");

                                            if (!str_starts_with($fieldKey, "cta_")) {
                                                continue;
                                            }

                                            if (in_array($fieldKey, ["cta_primary_link_type", "cta_primary_page_id", "cta_primary_url", "cta_secondary_link_type", "cta_secondary_page_id", "cta_secondary_url"], true)) {
                                                continue;
                                            }

                                            $fieldData = $contentData["simple_fields"][$fieldKey] ?? null;
                                            $fieldType = (string) ($fieldConfig["field_type"] ?? "text");
                                            $fieldValue = (string) ($fieldData["field_value"] ?? "");
                                            $fieldVisible = (int) ($fieldData["is_visible"] ?? 1) === 1;
                                            $isTextarea = $fieldType === "textarea";
                                            ?>
                                            <?php if ($fieldKey === "cta_primary_text" || $fieldKey === "cta_secondary_text"): ?>
                                                <?php
                                                $buttonPrefix = $fieldKey === "cta_primary_text" ? "cta_primary" : "cta_secondary";
                                                $buttonTitle = $buttonPrefix === "cta_primary" ? "Botón principal" : "Botón secundario";
                                                $linkTypeKey = $buttonPrefix . "_link_type";
                                                $pageIdKey = $buttonPrefix . "_page_id";
                                                $urlKey = $buttonPrefix . "_url";
                                                $linkTypeValue = trim((string) (($contentData["simple_fields"][$linkTypeKey]["field_value"] ?? "")));
                                                $pageIdValue = trim((string) (($contentData["simple_fields"][$pageIdKey]["field_value"] ?? "")));
                                                $urlValue = (string) (($contentData["simple_fields"][$urlKey]["field_value"] ?? ""));
                                                $linkScope = "services_cta_" . $buttonPrefix;
                                                if ($linkTypeValue !== "internal" && $linkTypeValue !== "custom") {
                                                    $linkTypeValue = $urlValue !== "" ? "custom" : ($pageIdValue !== "" ? "internal" : "custom");
                                                }
                                                ?>
                                                <div class="field-group field-group-full">
                                                    <div class="button-destination-box">
                                                        <div class="field-header">
                                                            <h4><?php echo htmlspecialchars($buttonTitle, ENT_QUOTES, "UTF-8"); ?></h4>
                                                            <label class="toggle-row">
                                                                <input type="checkbox" name="simple_fields[<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1"<?php echo $fieldVisible ? " checked" : ""; ?>>
                                                                <span>Mostrar</span>
                                                            </label>
                                                        </div>
                                                        <input type="hidden" name="simple_fields[<?php echo htmlspecialchars($linkTypeKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1">
                                                        <input type="hidden" name="simple_fields[<?php echo htmlspecialchars($pageIdKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1">
                                                        <input type="hidden" name="simple_fields[<?php echo htmlspecialchars($urlKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1">
                                                        <div class="field-group field-group-full">
                                                            <label class="field-label" for="simple_<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>">Texto del botón</label>
                                                            <input class="form-input" type="text" id="simple_<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                                        </div>
                                                        <div class="button-destination-grid">
                                                            <div class="field-group field-group-full">
                                                                <label class="field-label" for="simple_<?php echo htmlspecialchars($linkTypeKey, ENT_QUOTES, "UTF-8"); ?>">Tipo de enlace</label>
                                                                <select class="form-select js-link-type" id="simple_<?php echo htmlspecialchars($linkTypeKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($linkTypeKey, ENT_QUOTES, "UTF-8"); ?>][value]" data-link-scope="<?php echo htmlspecialchars($linkScope, ENT_QUOTES, "UTF-8"); ?>">
                                                                    <option value="internal"<?php echo $linkTypeValue === "internal" ? " selected" : ""; ?>>P&aacute;gina interna</option>
                                                                    <option value="custom"<?php echo $linkTypeValue === "custom" ? " selected" : ""; ?>>URL personalizada</option>
                                                                </select>
                                                            </div>
                                                            <div class="field-group field-group-full js-link-panel <?php echo $linkTypeValue === "internal" ? "" : "is-hidden"; ?>" data-link-panel="internal" data-link-scope="<?php echo htmlspecialchars($linkScope, ENT_QUOTES, "UTF-8"); ?>">
                                                                <label class="field-label" for="simple_<?php echo htmlspecialchars($pageIdKey, ENT_QUOTES, "UTF-8"); ?>">P&aacute;gina interna</label>
                                                                <select class="form-select" id="simple_<?php echo htmlspecialchars($pageIdKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($pageIdKey, ENT_QUOTES, "UTF-8"); ?>][value]">
                                                                    <option value="">Selecciona una p&aacute;gina</option>
                                                                    <?php foreach ($linkableSitePages as $sitePageOption): ?>
                                                                        <option value="<?php echo (int) ($sitePageOption["id"] ?? 0); ?>"<?php echo (string) ($sitePageOption["id"] ?? "") === $pageIdValue ? " selected" : ""; ?>><?php echo htmlspecialchars((string) ($sitePageOption["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="field-group field-group-full js-link-panel <?php echo $linkTypeValue === "custom" ? "" : "is-hidden"; ?>" data-link-panel="custom" data-link-scope="<?php echo htmlspecialchars($linkScope, ENT_QUOTES, "UTF-8"); ?>">
                                                                <label class="field-label" for="simple_<?php echo htmlspecialchars($urlKey, ENT_QUOTES, "UTF-8"); ?>">URL personalizada</label>
                                                                <input class="form-input" type="text" id="simple_<?php echo htmlspecialchars($urlKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($urlKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($urlValue, ENT_QUOTES, "UTF-8"); ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="field-group <?php echo $isTextarea ? "field-group-full" : ""; ?>">
                                                    <div class="field-header">
                                                        <label class="field-label" for="simple_<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>"><?php echo htmlspecialchars((string) ($fieldConfig["label"] ?? $fieldKey), ENT_QUOTES, "UTF-8"); ?></label>
                                                        <label class="toggle-row">
                                                            <input type="checkbox" name="simple_fields[<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>][is_visible]" value="1"<?php echo $fieldVisible ? " checked" : ""; ?>>
                                                            <span>Mostrar</span>
                                                        </label>
                                                    </div>

                                                    <?php if ($fieldKey === "cta_icon"): ?>
                                                        <select class="form-input" id="simple_<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>][value]">
                                                            <option value="fa fa-calendar-check"<?php echo $fieldValue === "fa fa-calendar-check" ? " selected" : ""; ?>>Calendario</option>
                                                        </select>
                                                    <?php elseif ($isTextarea): ?>
                                                        <textarea class="form-textarea" id="simple_<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>][value]"><?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?></textarea>
                                                    <?php else: ?>
                                                        <input class="form-input" type="text" id="simple_<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>" name="simple_fields[<?php echo htmlspecialchars($fieldKey, ENT_QUOTES, "UTF-8"); ?>][value]" value="<?php echo htmlspecialchars($fieldValue, ENT_QUOTES, "UTF-8"); ?>">
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <div class="actions">
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

                    if (selector.classList.contains("js-quick-action-link-type")) {
                        var quickActionUrlInput = document.querySelector('.js-quick-action-url[data-link-scope="' + scope + '"]');
                        var quickActionPageSelect = document.querySelector('.js-quick-action-page[data-link-scope="' + scope + '"]');

                        if (value === "internal" && quickActionUrlInput && quickActionPageSelect) {
                            var selectedPage = quickActionPageSelect.options[quickActionPageSelect.selectedIndex];
                            quickActionUrlInput.value = selectedPage ? (selectedPage.getAttribute("data-public-url") || "") : "";
                        } else if (value === "custom" && quickActionUrlInput) {
                            var customUrl = quickActionUrlInput.value.trim();
                            if (customUrl !== "" && !/^(?:https?:\/\/|mailto:|tel:|#)/i.test(customUrl)) {
                                quickActionUrlInput.value = "https://" + customUrl;
                            }
                        }
                    }
                };

                var quickActionPageSelect = document.querySelector('.js-quick-action-page[data-link-scope="' + selector.getAttribute("data-link-scope") + '"]');
                if (quickActionPageSelect) {
                    quickActionPageSelect.addEventListener("change", syncPanels);
                }

                selector.addEventListener("change", syncPanels);
                syncPanels();
            });

            var departmentCtaLinkSelectors = document.querySelectorAll(".js-department-cta-link-type");

            departmentCtaLinkSelectors.forEach(function (selector) {
                var scope = selector.getAttribute("data-link-scope");
                var finalUrlInput = document.querySelector('.js-department-cta-url[data-link-scope="' + scope + '"]');
                var pageSelect = document.querySelector('.js-department-cta-page[data-link-scope="' + scope + '"]');
                var customUrlInput = document.querySelector('.js-department-cta-custom-url[data-link-scope="' + scope + '"]');
                var syncDepartmentCtaUrl = function () {
                    if (!finalUrlInput) {
                        return;
                    }

                    if (selector.value === "internal" && pageSelect) {
                        var selectedPage = pageSelect.options[pageSelect.selectedIndex];
                        finalUrlInput.value = selectedPage ? (selectedPage.getAttribute("data-public-url") || "") : "";
                    } else if (customUrlInput) {
                        finalUrlInput.value = customUrlInput.value;
                    }
                };

                if (pageSelect) {
                    pageSelect.addEventListener("change", syncDepartmentCtaUrl);
                }

                if (customUrlInput) {
                    customUrlInput.addEventListener("input", syncDepartmentCtaUrl);
                }

                selector.addEventListener("change", syncDepartmentCtaUrl);
                syncDepartmentCtaUrl();
            });

            var doctorAvailabilityMap = {
                online: "Disponible",
                offline: "No disponible",
                busy: "En consulta"
            };
            var doctorStatusSelectors = document.querySelectorAll('[data-doctor-availability-role="status"]');
            var syncDoctorAvailabilityText = function (itemIndex) {
                var statusSelector = document.querySelector('[data-doctor-availability-role="status"][data-doctor-availability-item="' + itemIndex + '"]');
                var textSelector = document.querySelector('[data-doctor-availability-role="text"][data-doctor-availability-item="' + itemIndex + '"]');

                if (!statusSelector || !textSelector) {
                    return;
                }

                var mappedText = doctorAvailabilityMap[statusSelector.value] || doctorAvailabilityMap.online;
                textSelector.value = mappedText;
            };

            doctorStatusSelectors.forEach(function (selector) {
                var itemIndex = selector.getAttribute("data-doctor-availability-item");

                selector.addEventListener("change", function () {
                    syncDoctorAvailabilityText(itemIndex);
                });

                syncDoctorAvailabilityText(itemIndex);
            });

            var contactInfoCardTitleInputs = document.querySelectorAll("[data-contact-info-card-title-input]");

            contactInfoCardTitleInputs.forEach(function (input) {
                var itemIndex = input.getAttribute("data-contact-info-card-title-input");
                var title = document.querySelector('[data-contact-info-card-item="' + itemIndex + '"]');

                if (!title) {
                    return;
                }

                var syncContactInfoCardTitle = function () {
                    var fallback = title.getAttribute("data-contact-info-card-fallback") || "";
                    var value = input.value.trim();
                    title.textContent = value !== "" ? value : fallback;
                };

                input.addEventListener("input", syncContactInfoCardTitle);
                syncContactInfoCardTitle();
            });

            var contactSocialLinkIconInputs = document.querySelectorAll("[data-contact-social-link-icon-input]");

            contactSocialLinkIconInputs.forEach(function (input) {
                var itemIndex = input.getAttribute("data-contact-social-link-icon-input");
                var title = document.querySelector('[data-contact-social-link-item="' + itemIndex + '"]');

                if (!title) {
                    return;
                }

                var syncContactSocialLinkTitle = function () {
                    var selectedOption = input.options[input.selectedIndex];
                    var fallback = title.getAttribute("data-contact-social-link-fallback") || "";
                    title.textContent = selectedOption ? selectedOption.text : fallback;
                };

                input.addEventListener("change", syncContactSocialLinkTitle);
                syncContactSocialLinkTitle();
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


































































