<?php
$serviceDetailsContent = is_array($pageContent ?? null) ? $pageContent : ["simple_fields" => [], "repeaters" => []];
$serviceDetailsFields = $serviceDetailsContent["simple_fields"] ?? [];
$serviceDetailsRepeaters = $serviceDetailsContent["repeaters"] ?? [];

function serviceDetailsFieldValue(array $fields, string $fieldKey, string $default = ""): string
{
    return (string) ($fields[$fieldKey]["value"] ?? $default);
}

function serviceDetailsFieldVisible(array $fields, string $fieldKey, bool $default = true): bool
{
    if (!isset($fields[$fieldKey])) {
        return $default;
    }

    return (bool) ($fields[$fieldKey]["is_visible"] ?? false);
}

function serviceDetailsVisibleRepeaterItems(array $items): array
{
    return array_values(array_filter($items, static function ($item): bool {
        return (bool) ($item["is_visible"] ?? false);
    }));
}

function serviceDetailsRepeaterField(array $fields, string $fieldKey, string $default = ""): string
{
    return trim((string) ($fields[$fieldKey] ?? $default));
}

function serviceDetailsNormalizeCustomHref(string $url): string
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

function serviceDetailsNormalizePhoneOrHref(string $value): string
{
    $value = trim($value);

    if ($value === "") {
        return "";
    }

    if ($value[0] === "#" || $value[0] === "/" || preg_match('~^[a-z][a-z0-9+.-]*:~i', $value) === 1 || preg_match('~^(?:https?:)?//~i', $value) === 1) {
        return serviceDetailsNormalizeCustomHref($value);
    }

    if (preg_match('/^[0-9\s+().-]+$/', $value) === 1 && preg_match('/\d/', $value) === 1) {
        $phoneNumber = ($value[0] === "+" ? "+" : "") . preg_replace('/\D+/', '', $value);

        return $phoneNumber !== "" && $phoneNumber !== "+" ? "tel:" . $phoneNumber : "";
    }

    return serviceDetailsNormalizeCustomHref($value);
}

function serviceDetailsButtonHref(mysqli $conn, array $fields, string $prefix, string $fallbackUrl = "#"): string
{
    $linkType = trim(serviceDetailsFieldValue($fields, $prefix . "_button_link_type", ""));
    $pageId = (int) trim(serviceDetailsFieldValue($fields, $prefix . "_button_page_id", "0"));
    $customUrl = serviceDetailsNormalizeCustomHref(serviceDetailsFieldValue($fields, $prefix . "_button_url", ""));

    if ($linkType === "internal" && $pageId > 0) {
        [, $pagesById] = getPageContentLinkablePages($conn, true);

        if (isset($pagesById[$pageId])) {
            return (string) ($pagesById[$pageId]["public_url"] ?? $fallbackUrl);
        }
    }

    return $customUrl !== "" ? $customUrl : $fallbackUrl;
}

function serviceDetailsRepeaterLinkHref(mysqli $conn, array $fields, string $fallbackUrl = "#"): string
{
    $linkType = trim((string) ($fields["link_type"] ?? ""));
    $pageId = (int) trim((string) ($fields["page_id"] ?? "0"));
    $customUrl = serviceDetailsNormalizeCustomHref((string) ($fields["link_url"] ?? ""));

    if ($linkType === "internal" && $pageId > 0) {
        [, $pagesById] = getPageContentLinkablePages($conn, true);

        if (isset($pagesById[$pageId])) {
            return (string) ($pagesById[$pageId]["public_url"] ?? $fallbackUrl);
        }
    }

    return $customUrl !== "" ? $customUrl : $fallbackUrl;
}

$h1TitleEscaped = htmlspecialchars($h1Title, ENT_QUOTES, "UTF-8");
$heroTitle = serviceDetailsFieldValue($serviceDetailsFields, "hero_title", (string) ($page["title"] ?? "Service Details"));
$heroSubtitle = serviceDetailsFieldValue($serviceDetailsFields, "hero_subtitle", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.");
$serviceImage = serviceDetailsFieldValue($serviceDetailsFields, "service_image", "assets/img/health/cardiology-3.webp");
$serviceImageAlt = serviceDetailsFieldValue($serviceDetailsFields, "service_image_alt", "Cardiology Services");
$serviceTag = serviceDetailsFieldValue($serviceDetailsFields, "service_tag", "Specialized Care");
$serviceTitle = serviceDetailsFieldValue($serviceDetailsFields, "service_title", "Comprehensive Cardiology Services");
$serviceTagline = serviceDetailsFieldValue($serviceDetailsFields, "service_tagline", "Advanced heart care with cutting-edge technology and compassionate expertise");
$serviceTextOne = serviceDetailsFieldValue($serviceDetailsFields, "service_text_1", "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.");
$serviceTextTwo = serviceDetailsFieldValue($serviceDetailsFields, "service_text_2", "Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.");
$featuresTitle = serviceDetailsFieldValue($serviceDetailsFields, "features_title", "Our Services Include:");
$primaryButtonText = serviceDetailsFieldValue($serviceDetailsFields, "primary_button_text", "Schedule Consultation");
$primaryButtonUrl = serviceDetailsButtonHref($conn, $serviceDetailsFields, "primary", "#");
$secondaryButtonText = serviceDetailsFieldValue($serviceDetailsFields, "secondary_button_text", "Learn More");
$secondaryButtonUrl = serviceDetailsButtonHref($conn, $serviceDetailsFields, "secondary", "#");
$bookingTitle = serviceDetailsFieldValue($serviceDetailsFields, "booking_title", "Ready to Schedule Your Appointment?");
$bookingText = serviceDetailsFieldValue($serviceDetailsFields, "booking_text", "Our cardiology specialists are available for consultations Monday through Friday. Same-day appointments available for urgent cases.");
$appointmentTitle = serviceDetailsFieldValue($serviceDetailsFields, "appointment_title", "Book Your Visit");
$appointmentText = serviceDetailsFieldValue($serviceDetailsFields, "appointment_text", "Quick and easy online scheduling");
$appointmentButtonText = serviceDetailsFieldValue($serviceDetailsFields, "appointment_button_text", "Book Appointment");
$appointmentButtonUrl = serviceDetailsNormalizeCustomHref(serviceDetailsFieldValue($serviceDetailsFields, "appointment_button_url", "appointment.html"));
$appointmentAlternativeText = serviceDetailsFieldValue($serviceDetailsFields, "appointment_alternative_text", "Or call us at");
$appointmentPhoneText = serviceDetailsFieldValue($serviceDetailsFields, "appointment_phone_text", "+1 (555) 123-4567");
$appointmentPhoneUrl = serviceDetailsNormalizePhoneOrHref(serviceDetailsFieldValue($serviceDetailsFields, "appointment_phone_url", "tel:+15551234567"));
$appointmentPhoneUrlVisible = serviceDetailsFieldVisible($serviceDetailsFields, "appointment_phone_url");
$features = serviceDetailsVisibleRepeaterItems($serviceDetailsRepeaters["features"] ?? []);
$serviceCards = serviceDetailsVisibleRepeaterItems($serviceDetailsRepeaters["service_cards"] ?? []);
$availabilityItems = serviceDetailsVisibleRepeaterItems($serviceDetailsRepeaters["availability_items"] ?? []);

require __DIR__ . "/../../includes/header.php";
?>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title">
      <div class="breadcrumbs">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#"><i class="bi bi-house"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="#">Category</a></li>
            <li class="breadcrumb-item active current"><?php echo htmlspecialchars($heroTitle !== "" ? $heroTitle : "Service Details", ENT_QUOTES, "UTF-8"); ?></li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "hero_title") && $heroTitle !== ""): ?>
        <h1><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, "UTF-8"); ?></h1>
        <?php else: ?>
        <h1><?php echo $h1TitleEscaped; ?></h1>
        <?php endif; ?>
        <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "hero_subtitle") && $heroSubtitle !== ""): ?>
        <p><?php echo htmlspecialchars($heroSubtitle, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>
      </div>
    </div><!-- End Page Title -->

    <!-- Service Details 2 Section -->
    <section id="service-details-2" class="service-details-2 section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-5">

          <div class="col-lg-6" data-aos="zoom-in" data-aos-delay="200">
            <div class="service-image">
              <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "service_image") && $serviceImage !== ""): ?>
              <img src="<?php echo htmlspecialchars($serviceImage, ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars($serviceImageAlt, ENT_QUOTES, "UTF-8"); ?>" class="img-fluid">
              <?php endif; ?>
              <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "service_tag") && $serviceTag !== ""): ?>
              <div class="service-tag">
                <span><?php echo htmlspecialchars($serviceTag, ENT_QUOTES, "UTF-8"); ?></span>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-lg-6" data-aos="fade-left" data-aos-delay="300">
            <div class="service-content">
              <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "service_title") && $serviceTitle !== ""): ?>
              <h2><?php echo htmlspecialchars($serviceTitle, ENT_QUOTES, "UTF-8"); ?></h2>
              <?php endif; ?>
              <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "service_tagline") && $serviceTagline !== ""): ?>
              <p class="service-tagline"><?php echo htmlspecialchars($serviceTagline, ENT_QUOTES, "UTF-8"); ?></p>
              <?php endif; ?>

              <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "service_text_1") && $serviceTextOne !== ""): ?>
              <p><?php echo htmlspecialchars($serviceTextOne, ENT_QUOTES, "UTF-8"); ?></p>
              <?php endif; ?>

              <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "service_text_2") && $serviceTextTwo !== ""): ?>
              <p><?php echo htmlspecialchars($serviceTextTwo, ENT_QUOTES, "UTF-8"); ?></p>
              <?php endif; ?>

              <div class="service-features">
                <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "features_title") && $featuresTitle !== ""): ?>
                <h4><?php echo htmlspecialchars($featuresTitle, ENT_QUOTES, "UTF-8"); ?></h4>
                <?php endif; ?>
                <?php if ($features !== []): ?>
                <ul>
                  <?php foreach ($features as $feature): ?>
                    <?php $featureText = serviceDetailsRepeaterField($feature["fields"] ?? [], "text"); ?>
                  <?php if ($featureText !== ""): ?><li><i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($featureText, ENT_QUOTES, "UTF-8"); ?></li><?php endif; ?>
                  <?php endforeach; ?>
                </ul>
                <?php endif; ?>
              </div>

              <div class="service-actions">
                <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "primary_button_text") && $primaryButtonText !== ""): ?>
                <a href="<?php echo htmlspecialchars($primaryButtonUrl !== "" ? $primaryButtonUrl : "#", ENT_QUOTES, "UTF-8"); ?>" class="btn-primary"><?php echo htmlspecialchars($primaryButtonText, ENT_QUOTES, "UTF-8"); ?></a>
                <?php endif; ?>
                <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "secondary_button_text") && $secondaryButtonText !== ""): ?>
                <a href="<?php echo htmlspecialchars($secondaryButtonUrl !== "" ? $secondaryButtonUrl : "#", ENT_QUOTES, "UTF-8"); ?>" class="btn-secondary"><?php echo htmlspecialchars($secondaryButtonText, ENT_QUOTES, "UTF-8"); ?></a>
                <?php endif; ?>
              </div>
            </div>
          </div>

        </div>

        <?php if ($serviceCards !== []): ?>
        <div class="row mt-5">

          <?php foreach ($serviceCards as $cardIndex => $serviceCard): ?>
            <?php
            $cardFields = $serviceCard["fields"] ?? [];
            $iconClass = serviceDetailsRepeaterField($cardFields, "icon_class");
            $title = serviceDetailsRepeaterField($cardFields, "title");
            $text = serviceDetailsRepeaterField($cardFields, "text");
            $linkText = serviceDetailsRepeaterField($cardFields, "link_text");
            $linkUrl = serviceDetailsRepeaterLinkHref($conn, $cardFields, "#");
            $delay = 100 + ($cardIndex * 100);
            ?>
          <div class="col-lg-4" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
            <div class="service-card">
              <div class="card-icon">
                <?php if ($iconClass !== ""): ?><i class="<?php echo htmlspecialchars($iconClass, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?>
              </div>
              <?php if ($title !== ""): ?><h4><?php echo htmlspecialchars($title, ENT_QUOTES, "UTF-8"); ?></h4><?php endif; ?>
              <?php if ($text !== ""): ?><p><?php echo htmlspecialchars($text, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
              <?php if ($linkText !== ""): ?>
              <a href="<?php echo htmlspecialchars($linkUrl !== "" ? $linkUrl : "#", ENT_QUOTES, "UTF-8"); ?>" class="card-link">
                <span><?php echo htmlspecialchars($linkText, ENT_QUOTES, "UTF-8"); ?></span>
                <i class="bi bi-arrow-right"></i>
              </a>
              <?php endif; ?>
            </div>
          </div>

          <?php endforeach; ?>

        </div>
        <?php endif; ?>

        <div class="row mt-5">

          <div class="col-lg-8" data-aos="fade-right" data-aos-delay="100">
            <div class="booking-section">
              <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "booking_title") && $bookingTitle !== ""): ?>
              <h3><?php echo htmlspecialchars($bookingTitle, ENT_QUOTES, "UTF-8"); ?></h3>
              <?php endif; ?>
              <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "booking_text") && $bookingText !== ""): ?>
              <p><?php echo htmlspecialchars($bookingText, ENT_QUOTES, "UTF-8"); ?></p>
              <?php endif; ?>

              <?php if ($availabilityItems !== []): ?>
              <div class="availability-info">
                <?php foreach ($availabilityItems as $availabilityItem): ?>
                  <?php
                  $availabilityFields = $availabilityItem["fields"] ?? [];
                  $iconClass = serviceDetailsRepeaterField($availabilityFields, "icon_class");
                  $title = serviceDetailsRepeaterField($availabilityFields, "title");
                  $text = serviceDetailsRepeaterField($availabilityFields, "text");
                  ?>
                <div class="info-item">
                  <?php if ($iconClass !== ""): ?><i class="<?php echo htmlspecialchars($iconClass, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?>
                  <div>
                    <?php if ($title !== ""): ?><strong><?php echo htmlspecialchars($title, ENT_QUOTES, "UTF-8"); ?></strong><?php endif; ?>
                    <?php if ($text !== ""): ?><span><?php echo htmlspecialchars($text, ENT_QUOTES, "UTF-8"); ?></span><?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-lg-4" data-aos="fade-left" data-aos-delay="200">
            <div class="appointment-card">
              <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "appointment_title") && $appointmentTitle !== ""): ?>
              <h4><?php echo htmlspecialchars($appointmentTitle, ENT_QUOTES, "UTF-8"); ?></h4>
              <?php endif; ?>
              <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "appointment_text") && $appointmentText !== ""): ?>
              <p><?php echo htmlspecialchars($appointmentText, ENT_QUOTES, "UTF-8"); ?></p>
              <?php endif; ?>
              <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "appointment_button_text") && $appointmentButtonText !== ""): ?>
              <a href="<?php echo htmlspecialchars($appointmentButtonUrl !== "" ? $appointmentButtonUrl : "#", ENT_QUOTES, "UTF-8"); ?>" class="btn-appointment"><?php echo htmlspecialchars($appointmentButtonText, ENT_QUOTES, "UTF-8"); ?></a>
              <?php endif; ?>
              <div class="contact-alternative">
                <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "appointment_alternative_text") && $appointmentAlternativeText !== ""): ?><span><?php echo htmlspecialchars($appointmentAlternativeText, ENT_QUOTES, "UTF-8"); ?></span><?php endif; ?>
                <?php if (serviceDetailsFieldVisible($serviceDetailsFields, "appointment_phone_text") && $appointmentPhoneText !== ""): ?>
                  <?php if ($appointmentPhoneUrlVisible && $appointmentPhoneUrl !== ""): ?>
                    <a href="<?php echo htmlspecialchars($appointmentPhoneUrl, ENT_QUOTES, "UTF-8"); ?>"><?php echo htmlspecialchars($appointmentPhoneText, ENT_QUOTES, "UTF-8"); ?></a>
                  <?php else: ?>
                    <span><?php echo htmlspecialchars($appointmentPhoneText, ENT_QUOTES, "UTF-8"); ?></span>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>

        </div>

      </div>

    </section><!-- /Service Details 2 Section -->

  </main>

<?php require __DIR__ . "/../../includes/footer.php"; ?>
