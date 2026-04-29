<?php
$departmentDetailsContent = is_array($pageContent ?? null) ? $pageContent : ["simple_fields" => [], "repeaters" => []];
$departmentDetailsFields = $departmentDetailsContent["simple_fields"] ?? [];
$departmentDetailsRepeaters = $departmentDetailsContent["repeaters"] ?? [];

function departmentDetailsFieldValue(array $fields, string $fieldKey, string $default = ""): string
{
    return (string) ($fields[$fieldKey]["value"] ?? $default);
}

function departmentDetailsFieldVisible(array $fields, string $fieldKey, bool $default = true): bool
{
    if (!isset($fields[$fieldKey])) {
        return $default;
    }

    return (bool) ($fields[$fieldKey]["is_visible"] ?? false);
}

function departmentDetailsVisibleRepeaterItems(array $items): array
{
    return array_values(array_filter($items, static function ($item): bool {
        return (bool) ($item["is_visible"] ?? false);
    }));
}

function departmentDetailsRepeaterField(array $fields, string $fieldKey, string $default = ""): string
{
    return trim((string) ($fields[$fieldKey] ?? $default));
}

function departmentDetailsNormalizeCustomHref(string $url): string
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

$h1TitleEscaped = htmlspecialchars($h1Title, ENT_QUOTES, "UTF-8");
$heroTitle = departmentDetailsFieldValue($departmentDetailsFields, "hero_title", (string) ($page["title"] ?? "Department Details"));
$heroSubtitle = departmentDetailsFieldValue($departmentDetailsFields, "hero_subtitle", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.");
$introTitle = departmentDetailsFieldValue($departmentDetailsFields, "intro_title", "Cardiology Department");
$introText = departmentDetailsFieldValue($departmentDetailsFields, "intro_text", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation.");
$overviewImage = departmentDetailsFieldValue($departmentDetailsFields, "overview_image", "assets/img/health/cardiology-1.webp");
$overviewImageAlt = departmentDetailsFieldValue($departmentDetailsFields, "overview_image_alt", "Cardiology Department");
$experienceNumber = departmentDetailsFieldValue($departmentDetailsFields, "experience_number", "15+");
$experienceText = departmentDetailsFieldValue($departmentDetailsFields, "experience_text", "Years of Excellence");
$keyServicesTitle = departmentDetailsFieldValue($departmentDetailsFields, "key_services_title", "Our Specialized Services");
$keyServicesText = departmentDetailsFieldValue($departmentDetailsFields, "key_services_text", "Donec rutrum congue leo eget malesuada. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Vestibulum ac diam sit amet quam vehicula.");
$ctaTitle = departmentDetailsFieldValue($departmentDetailsFields, "cta_title", "Expert Care When You Need It Most");
$ctaText = departmentDetailsFieldValue($departmentDetailsFields, "cta_text", "Vivamus suscipit tortor eget felis porttitor volutpat. Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Proin eget tortor risus.");
$ctaPrimaryText = departmentDetailsFieldValue($departmentDetailsFields, "cta_primary_text", "Book Appointment");
$ctaPrimaryUrl = departmentDetailsNormalizeCustomHref(departmentDetailsFieldValue($departmentDetailsFields, "cta_primary_url", "appointment.html"));
$ctaSecondaryText = departmentDetailsFieldValue($departmentDetailsFields, "cta_secondary_text", "Learn More");
$ctaSecondaryUrl = departmentDetailsNormalizeCustomHref(departmentDetailsFieldValue($departmentDetailsFields, "cta_secondary_url", "services.html"));
$ctaImage = departmentDetailsFieldValue($departmentDetailsFields, "cta_image", "assets/img/health/cardiology-3.webp");
$ctaImageAlt = departmentDetailsFieldValue($departmentDetailsFields, "cta_image_alt", "Cardiology Team");
$serviceCards = departmentDetailsVisibleRepeaterItems($departmentDetailsRepeaters["service_cards"] ?? []);
$stats = departmentDetailsVisibleRepeaterItems($departmentDetailsRepeaters["stats"] ?? []);
$keyServices = departmentDetailsVisibleRepeaterItems($departmentDetailsRepeaters["key_services"] ?? []);

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
            <li class="breadcrumb-item active current"><?php echo htmlspecialchars($heroTitle !== "" ? $heroTitle : "Department Details", ENT_QUOTES, "UTF-8"); ?></li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <?php if (departmentDetailsFieldVisible($departmentDetailsFields, "hero_title") && $heroTitle !== ""): ?>
        <h1><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, "UTF-8"); ?></h1>
        <?php else: ?>
        <h1><?php echo $h1TitleEscaped; ?></h1>
        <?php endif; ?>
        <?php if (departmentDetailsFieldVisible($departmentDetailsFields, "hero_subtitle") && $heroSubtitle !== ""): ?>
        <p><?php echo htmlspecialchars($heroSubtitle, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>
      </div>
    </div><!-- End Page Title -->

    <!-- Department Details Section -->
    <section id="department-details" class="department-details section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4">
          <div class="col-lg-8 mx-auto text-center intro" data-aos="fade-up" data-aos-delay="200">
            <?php if (departmentDetailsFieldVisible($departmentDetailsFields, "intro_title") && $introTitle !== ""): ?>
            <h2><?php echo htmlspecialchars($introTitle, ENT_QUOTES, "UTF-8"); ?></h2>
            <?php endif; ?>
            <div class="divider mx-auto"></div>
            <?php if (departmentDetailsFieldVisible($departmentDetailsFields, "intro_text") && $introText !== ""): ?>
            <p class="lead"><?php echo htmlspecialchars($introText, ENT_QUOTES, "UTF-8"); ?></p>
            <?php endif; ?>
          </div>
        </div>

        <div class="department-overview mt-5">
          <div class="row gy-4">
            <div class="col-lg-6" data-aos="fade-right" data-aos-delay="300">
              <div class="department-image">
                <?php if (departmentDetailsFieldVisible($departmentDetailsFields, "overview_image") && $overviewImage !== ""): ?>
                <img src="<?php echo htmlspecialchars($overviewImage, ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars($overviewImageAlt, ENT_QUOTES, "UTF-8"); ?>" class="img-fluid rounded-lg">
                <?php endif; ?>
                <div class="experience-badge">
                  <?php if (departmentDetailsFieldVisible($departmentDetailsFields, "experience_number") && $experienceNumber !== ""): ?><span><?php echo htmlspecialchars($experienceNumber, ENT_QUOTES, "UTF-8"); ?></span><?php endif; ?>
                  <?php if (departmentDetailsFieldVisible($departmentDetailsFields, "experience_text") && $experienceText !== ""): ?><p><?php echo htmlspecialchars($experienceText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
                </div>
              </div>
            </div>

            <div class="col-lg-6" data-aos="fade-left" data-aos-delay="300">
              <div class="department-services">
                <?php foreach ($serviceCards as $serviceCard): ?>
                  <?php
                  $serviceCardFields = $serviceCard["fields"] ?? [];
                  $iconClass = departmentDetailsRepeaterField($serviceCardFields, "icon_class");
                  $title = departmentDetailsRepeaterField($serviceCardFields, "title");
                  $text = departmentDetailsRepeaterField($serviceCardFields, "text");
                  ?>
                <div class="service-card">
                  <div class="icon"><?php if ($iconClass !== ""): ?><i class="<?php echo htmlspecialchars($iconClass, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?></div>
                  <div class="content">
                    <?php if ($title !== ""): ?><h4><?php echo htmlspecialchars($title, ENT_QUOTES, "UTF-8"); ?></h4><?php endif; ?>
                    <?php if ($text !== ""): ?><p><?php echo htmlspecialchars($text, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
                  </div>
                </div>

                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <?php if ($stats !== []): ?>
        <div class="department-stats" data-aos="fade-up" data-aos-delay="400">
          <div class="row gy-4">
            <?php foreach ($stats as $stat): ?>
              <?php
              $statFields = $stat["fields"] ?? [];
              $end = departmentDetailsRepeaterField($statFields, "number");
              $label = departmentDetailsRepeaterField($statFields, "label");
              ?>
            <div class="col-md-3 col-6">
              <div class="stat-item">
                <div class="number purecounter" data-purecounter-start="0" data-purecounter-end="<?php echo htmlspecialchars($end, ENT_QUOTES, "UTF-8"); ?>" data-purecounter-duration="2">0</div>
                <?php if ($label !== ""): ?><p><?php echo htmlspecialchars($label, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="key-services mt-5" data-aos="fade-up" data-aos-delay="500">
          <div class="row gy-4">
            <div class="col-lg-5" data-aos="fade-right" data-aos-delay="600">
              <?php if (departmentDetailsFieldVisible($departmentDetailsFields, "key_services_title") && $keyServicesTitle !== ""): ?>
              <h3><?php echo htmlspecialchars($keyServicesTitle, ENT_QUOTES, "UTF-8"); ?></h3>
              <?php endif; ?>
              <?php if (departmentDetailsFieldVisible($departmentDetailsFields, "key_services_text") && $keyServicesText !== ""): ?>
              <p><?php echo htmlspecialchars($keyServicesText, ENT_QUOTES, "UTF-8"); ?></p>
              <?php endif; ?>
              <?php if ($keyServices !== []): ?>
              <ul class="service-list">
                <?php foreach ($keyServices as $keyService): ?>
                  <?php $text = departmentDetailsRepeaterField($keyService["fields"] ?? [], "text"); ?>
                <?php if ($text !== ""): ?><li><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($text, ENT_QUOTES, "UTF-8"); ?></li><?php endif; ?>
                <?php endforeach; ?>
              </ul>
              <?php endif; ?>
            </div>
            <div class="col-lg-7" data-aos="fade-left" data-aos-delay="600">
              <div class="cta-wrapper">
                <div class="cta-content">
                  <?php if (departmentDetailsFieldVisible($departmentDetailsFields, "cta_title") && $ctaTitle !== ""): ?>
                  <h3><?php echo htmlspecialchars($ctaTitle, ENT_QUOTES, "UTF-8"); ?></h3>
                  <?php endif; ?>
                  <?php if (departmentDetailsFieldVisible($departmentDetailsFields, "cta_text") && $ctaText !== ""): ?>
                  <p><?php echo htmlspecialchars($ctaText, ENT_QUOTES, "UTF-8"); ?></p>
                  <?php endif; ?>
                  <div class="cta-buttons">
                    <?php if (departmentDetailsFieldVisible($departmentDetailsFields, "cta_primary_text") && $ctaPrimaryText !== ""): ?>
                    <a href="<?php echo htmlspecialchars($ctaPrimaryUrl !== "" ? $ctaPrimaryUrl : "#", ENT_QUOTES, "UTF-8"); ?>" class="btn btn-primary"><?php echo htmlspecialchars($ctaPrimaryText, ENT_QUOTES, "UTF-8"); ?></a>
                    <?php endif; ?>
                    <?php if (departmentDetailsFieldVisible($departmentDetailsFields, "cta_secondary_text") && $ctaSecondaryText !== ""): ?>
                    <a href="<?php echo htmlspecialchars($ctaSecondaryUrl !== "" ? $ctaSecondaryUrl : "#", ENT_QUOTES, "UTF-8"); ?>" class="btn btn-outline"><?php echo htmlspecialchars($ctaSecondaryText, ENT_QUOTES, "UTF-8"); ?></a>
                    <?php endif; ?>
                  </div>
                </div>
                <?php if (departmentDetailsFieldVisible($departmentDetailsFields, "cta_image") && $ctaImage !== ""): ?>
                <div class="cta-image">
                  <img src="<?php echo htmlspecialchars($ctaImage, ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars($ctaImageAlt, ENT_QUOTES, "UTF-8"); ?>" class="img-fluid rounded-lg">
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Department Details Section -->

  </main>

<?php require __DIR__ . "/../../includes/footer.php"; ?>
