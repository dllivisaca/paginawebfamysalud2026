<?php
$servicesContent = is_array($pageContent ?? null) ? $pageContent : ["simple_fields" => [], "repeaters" => []];
$servicesFields = $servicesContent["simple_fields"] ?? [];
$servicesRepeaters = $servicesContent["repeaters"] ?? [];

function servicesFieldValue(array $fields, string $fieldKey, string $default = ""): string
{
    return (string) ($fields[$fieldKey]["value"] ?? $default);
}

function servicesFieldVisible(array $fields, string $fieldKey, bool $default = true): bool
{
    if (!isset($fields[$fieldKey])) {
        return $default;
    }

    return (bool) ($fields[$fieldKey]["is_visible"] ?? false);
}

function servicesVisibleRepeaterItems(array $items): array
{
    return array_values(array_filter($items, static function ($item): bool {
        return (bool) ($item["is_visible"] ?? false);
    }));
}

function servicesRepeaterField(array $fields, string $fieldKey, string $default = ""): string
{
    return trim((string) ($fields[$fieldKey] ?? $default));
}

function servicesNormalizeCustomHref(string $url): string
{
    $url = trim($url);

    if ($url === "") {
        return "";
    }

    if ($url[0] === "#" || $url[0] === "/") {
        return $url;
    }

    if (preg_match('~^(?:https?:)?//~i', $url) === 1) {
        return $url;
    }

    if (preg_match('~^[a-z][a-z0-9+.-]*:~i', $url) === 1) {
        return $url;
    }

    if (preg_match('~^www\.~i', $url) === 1) {
        return 'https://' . $url;
    }

    return $url;
}

function servicesRepeaterLinkHref(mysqli $conn, array $fields, string $fallbackUrl = "#", string $linkTypeKey = "link_type", string $pageIdKey = "page_id", string $urlKey = "link_url"): string
{
    $linkType = servicesRepeaterField($fields, $linkTypeKey);
    $pageId = (int) servicesRepeaterField($fields, $pageIdKey, "0");
    $customUrl = servicesNormalizeCustomHref(servicesRepeaterField($fields, $urlKey));

    if ($linkType !== "internal" && $linkType !== "custom") {
        $linkType = $customUrl !== "" ? "custom" : ($pageId > 0 ? "internal" : "custom");
    }

    if ($linkType === "internal" && $pageId > 0) {
        [, $pagesById] = getPageContentLinkablePages($conn, true);

        if (isset($pagesById[$pageId])) {
            return (string) ($pagesById[$pageId]["public_url"] ?? $fallbackUrl);
        }
    }

    return $customUrl !== "" ? $customUrl : $fallbackUrl;
}

function servicesRenderServiceItem(mysqli $conn, array $serviceItem): void
{
    $serviceFields = $serviceItem["fields"] ?? [];
    $iconClass = servicesRepeaterField($serviceFields, "icon_class");
    $title = servicesRepeaterField($serviceFields, "title");
    $description = servicesRepeaterField($serviceFields, "description");
    $variantClass = servicesRepeaterField($serviceFields, "variant_class");
    $columnClass = servicesRepeaterField($serviceFields, "column_class", "col-lg-6");
    $isEmergencyService = servicesRepeaterField($serviceFields, "category_key") === "emergency";
    $linkText = servicesRepeaterField($serviceFields, "link_text");
    $linkUrl = $isEmergencyService ? servicesNormalizeCustomHref(servicesRepeaterField($serviceFields, "link_url")) : servicesRepeaterLinkHref($conn, $serviceFields, "#");
    $emergencyButtonText = servicesRepeaterField($serviceFields, "emergency_button_text");
    $emergencyButtonIcon = servicesRepeaterField($serviceFields, "emergency_button_icon", "fa fa-phone");
    $emergencyButtonUrl = servicesRepeaterLinkHref($conn, $serviceFields, "#", "emergency_button_link_type", "emergency_button_page_id", "emergency_button_url");
    $directionsButtonText = servicesRepeaterField($serviceFields, "directions_button_text");
    $directionsButtonIcon = servicesRepeaterField($serviceFields, "directions_button_icon", "fa fa-map-marker-alt");
    $directionsButtonUrl = servicesRepeaterLinkHref($conn, $serviceFields, "#", "directions_button_link_type", "directions_button_page_id", "directions_button_url");
    $benefits = array_values(array_filter([
        servicesRepeaterField($serviceFields, "benefit_1"),
        servicesRepeaterField($serviceFields, "benefit_2"),
        servicesRepeaterField($serviceFields, "benefit_3"),
        servicesRepeaterField($serviceFields, "benefit_4"),
    ], static function (string $value): bool {
        return $value !== "";
    }));
    ?>
                <div class="<?php echo htmlspecialchars($columnClass !== "" ? $columnClass : "col-lg-6", ENT_QUOTES, "UTF-8"); ?>">
                  <div class="service-item<?php echo $variantClass !== "" ? " " . htmlspecialchars($variantClass, ENT_QUOTES, "UTF-8") : ""; ?>">
                    <?php if ($iconClass !== ""): ?>
                    <div class="service-icon-wrapper">
                      <i class="<?php echo htmlspecialchars($iconClass, ENT_QUOTES, "UTF-8"); ?>"></i>
                    </div>
                    <?php endif; ?>
                    <div class="service-details">
                      <?php if ($title !== ""): ?>
                      <h5><?php echo htmlspecialchars($title, ENT_QUOTES, "UTF-8"); ?></h5>
                      <?php endif; ?>
                      <?php if ($description !== ""): ?>
                      <p><?php echo htmlspecialchars($description, ENT_QUOTES, "UTF-8"); ?></p>
                      <?php endif; ?>
                      <?php if ($benefits !== []): ?>
                      <ul class="service-benefits">
                        <?php foreach ($benefits as $benefit): ?>
                        <li><i class="fa fa-check-circle"></i><?php echo htmlspecialchars($benefit, ENT_QUOTES, "UTF-8"); ?></li>
                        <?php endforeach; ?>
                      </ul>
                      <?php endif; ?>
                      <?php if ($variantClass === "emergency-highlight"): ?>
                        <?php if ($emergencyButtonText !== "" || $directionsButtonText !== ""): ?>
                      <div class="emergency-actions">
                        <?php if ($emergencyButtonText !== ""): ?>
                        <a href="<?php echo htmlspecialchars($emergencyButtonUrl !== "" ? $emergencyButtonUrl : "#", ENT_QUOTES, "UTF-8"); ?>" class="btn-emergency">
                          <?php if ($emergencyButtonIcon !== ""): ?><i class="<?php echo htmlspecialchars($emergencyButtonIcon, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?>
                          <span><?php echo htmlspecialchars($emergencyButtonText, ENT_QUOTES, "UTF-8"); ?></span>
                        </a>
                        <?php endif; ?>
                        <?php if ($directionsButtonText !== ""): ?>
                        <a href="<?php echo htmlspecialchars($directionsButtonUrl !== "" ? $directionsButtonUrl : "#", ENT_QUOTES, "UTF-8"); ?>" class="btn-directions">
                          <?php if ($directionsButtonIcon !== ""): ?><i class="<?php echo htmlspecialchars($directionsButtonIcon, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?>
                          <span><?php echo htmlspecialchars($directionsButtonText, ENT_QUOTES, "UTF-8"); ?></span>
                        </a>
                        <?php endif; ?>
                      </div>
                        <?php endif; ?>
                      <?php elseif ($linkText !== ""): ?>
                      <a href="<?php echo htmlspecialchars($linkUrl !== "" ? $linkUrl : "#", ENT_QUOTES, "UTF-8"); ?>" class="service-link">
                        <span><?php echo htmlspecialchars($linkText, ENT_QUOTES, "UTF-8"); ?></span>
                        <i class="fa fa-arrow-right"></i>
                      </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
    <?php
}

$h1TitleEscaped = htmlspecialchars($h1Title, ENT_QUOTES, "UTF-8");
$heroTitle = servicesFieldValue($servicesFields, "hero_title", (string) ($page["title"] ?? "Services"));
$heroSubtitle = servicesFieldValue($servicesFields, "hero_subtitle", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.");
$ctaIcon = servicesFieldValue($servicesFields, "cta_icon", "fa fa-calendar-check");
$ctaTitle = servicesFieldValue($servicesFields, "cta_title", "Ready to Schedule Your Appointment?");
$ctaText = servicesFieldValue($servicesFields, "cta_text", "Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur excepteur sint occaecat cupidatat non proident.");
$ctaPrimaryText = servicesFieldValue($servicesFields, "cta_primary_text", "Book Now");
$ctaPrimaryUrl = servicesNormalizeCustomHref(servicesFieldValue($servicesFields, "cta_primary_url", "appointment.html"));
$ctaSecondaryText = servicesFieldValue($servicesFields, "cta_secondary_text", "Contact Us");
$ctaSecondaryUrl = servicesNormalizeCustomHref(servicesFieldValue($servicesFields, "cta_secondary_url", "contact.html"));
$categoryItems = servicesVisibleRepeaterItems($servicesRepeaters["service_categories"] ?? []);
$serviceItems = servicesVisibleRepeaterItems($servicesRepeaters["services"] ?? []);
$servicesByCategory = [];

foreach ($serviceItems as $serviceItem) {
    $serviceFields = $serviceItem["fields"] ?? [];
    $categoryKey = servicesRepeaterField($serviceFields, "category_key");

    if ($categoryKey === "") {
        continue;
    }

    $servicesByCategory[$categoryKey][] = $serviceItem;
}

require __DIR__ . "/../../includes/header.php";
?>

  <main class="main">
    <div class="page-title">
      <div class="breadcrumbs">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#"><i class="bi bi-house"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="#">Category</a></li>
            <li class="breadcrumb-item active current"><?php echo htmlspecialchars($heroTitle !== "" ? $heroTitle : "Services", ENT_QUOTES, "UTF-8"); ?></li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <?php if (servicesFieldVisible($servicesFields, "hero_title") && $heroTitle !== ""): ?>
        <h1><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, "UTF-8"); ?></h1>
        <?php else: ?>
        <h1><?php echo $h1TitleEscaped; ?></h1>
        <?php endif; ?>
        <?php if (servicesFieldVisible($servicesFields, "hero_subtitle") && $heroSubtitle !== ""): ?>
        <p><?php echo htmlspecialchars($heroSubtitle, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>
      </div>
    </div>

    <section id="services" class="services section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <?php if ($categoryItems !== []): ?>
        <div class="services-tabs">
          <ul class="nav nav-tabs" role="tablist" data-aos="fade-up" data-aos-delay="200">
            <?php foreach ($categoryItems as $categoryIndex => $categoryItem): ?>
              <?php
              $categoryFields = $categoryItem["fields"] ?? [];
              $categoryKey = servicesRepeaterField($categoryFields, "category_key", "category-" . $categoryIndex);
              $categoryLabel = servicesRepeaterField($categoryFields, "label", $categoryKey);
              $tabId = "services-" . preg_replace('/[^a-z0-9_-]+/i', "-", strtolower($categoryKey));
              ?>
            <li class="nav-item" role="presentation">
              <button class="nav-link<?php echo $categoryIndex === 0 ? " active" : ""; ?>" id="<?php echo htmlspecialchars($tabId, ENT_QUOTES, "UTF-8"); ?>-tab" data-bs-toggle="tab" data-bs-target="#<?php echo htmlspecialchars($tabId, ENT_QUOTES, "UTF-8"); ?>" type="button" role="tab"><?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, "UTF-8"); ?></button>
            </li>
            <?php endforeach; ?>
          </ul>

          <div class="tab-content" data-aos="fade-up" data-aos-delay="300">
            <?php foreach ($categoryItems as $categoryIndex => $categoryItem): ?>
              <?php
              $categoryFields = $categoryItem["fields"] ?? [];
              $categoryKey = servicesRepeaterField($categoryFields, "category_key", "category-" . $categoryIndex);
              $tabId = "services-" . preg_replace('/[^a-z0-9_-]+/i', "-", strtolower($categoryKey));
              ?>
            <div class="tab-pane fade<?php echo $categoryIndex === 0 ? " show active" : ""; ?>" id="<?php echo htmlspecialchars($tabId, ENT_QUOTES, "UTF-8"); ?>" role="tabpanel">
              <div class="row g-4">
                <?php foreach ($servicesByCategory[$categoryKey] ?? [] as $serviceItem): ?>
                  <?php servicesRenderServiceItem($conn, $serviceItem); ?>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="services-cta" data-aos="fade-up" data-aos-delay="400">
          <div class="row">
            <div class="col-lg-8 mx-auto text-center">
              <div class="cta-content">
                <?php if (servicesFieldVisible($servicesFields, "cta_icon") && $ctaIcon !== ""): ?>
                <i class="<?php echo htmlspecialchars($ctaIcon, ENT_QUOTES, "UTF-8"); ?>"></i>
                <?php endif; ?>
                <?php if (servicesFieldVisible($servicesFields, "cta_title") && $ctaTitle !== ""): ?>
                <h3><?php echo htmlspecialchars($ctaTitle, ENT_QUOTES, "UTF-8"); ?></h3>
                <?php endif; ?>
                <?php if (servicesFieldVisible($servicesFields, "cta_text") && $ctaText !== ""): ?>
                <p><?php echo htmlspecialchars($ctaText, ENT_QUOTES, "UTF-8"); ?></p>
                <?php endif; ?>
                <div class="cta-buttons">
                  <?php if (servicesFieldVisible($servicesFields, "cta_primary_text") && $ctaPrimaryText !== ""): ?>
                  <a href="<?php echo htmlspecialchars($ctaPrimaryUrl !== "" ? $ctaPrimaryUrl : "#", ENT_QUOTES, "UTF-8"); ?>" class="btn-book"><?php echo htmlspecialchars($ctaPrimaryText, ENT_QUOTES, "UTF-8"); ?></a>
                  <?php endif; ?>
                  <?php if (servicesFieldVisible($servicesFields, "cta_secondary_text") && $ctaSecondaryText !== ""): ?>
                  <a href="<?php echo htmlspecialchars($ctaSecondaryUrl !== "" ? $ctaSecondaryUrl : "#", ENT_QUOTES, "UTF-8"); ?>" class="btn-contact"><?php echo htmlspecialchars($ctaSecondaryText, ENT_QUOTES, "UTF-8"); ?></a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

<?php require __DIR__ . "/../../includes/footer.php"; ?>
