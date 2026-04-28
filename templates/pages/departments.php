<?php
$departmentsContent = is_array($pageContent ?? null) ? $pageContent : ["simple_fields" => [], "repeaters" => []];
$departmentsFields = $departmentsContent["simple_fields"] ?? [];
$departmentsRepeaters = $departmentsContent["repeaters"] ?? [];

function departmentsFieldValue(array $fields, string $fieldKey, string $default = ""): string
{
    return (string) ($fields[$fieldKey]["value"] ?? $default);
}

function departmentsFieldVisible(array $fields, string $fieldKey, bool $default = true): bool
{
    if (!isset($fields[$fieldKey])) {
        return $default;
    }

    return (bool) ($fields[$fieldKey]["is_visible"] ?? false);
}

function departmentsVisibleRepeaterItems(array $items): array
{
    return array_values(array_filter($items, static function ($item): bool {
        return (bool) ($item["is_visible"] ?? false);
    }));
}

function departmentsRepeaterLinkHref(mysqli $conn, array $fields, string $fallbackUrl = "#"): string
{
    $linkType = trim((string) ($fields["button_link_type"] ?? ""));
    $pageId = (int) trim((string) ($fields["button_page_id"] ?? "0"));
    $customUrl = trim((string) ($fields["button_url"] ?? ""));

    if ($linkType === "internal" && $pageId > 0) {
        [, $pagesById] = getPageContentLinkablePages($conn, true);

        if (isset($pagesById[$pageId])) {
            return (string) ($pagesById[$pageId]["public_url"] ?? $fallbackUrl);
        }
    }

    return $customUrl !== "" ? $customUrl : $fallbackUrl;
}

function departmentsRepeaterField(array $fields, string $fieldKey, string $default = ""): string
{
    return trim((string) ($fields[$fieldKey] ?? $default));
}

function renderDepartmentCard(mysqli $conn, array $departmentItem, int $delay = 200): void
{
    $departmentFields = $departmentItem["fields"] ?? [];
    $departmentIcon = departmentsRepeaterField($departmentFields, "icon_class", "bi bi-heart-pulse");
    $departmentTitle = departmentsRepeaterField($departmentFields, "title");
    $departmentSubtitle = departmentsRepeaterField($departmentFields, "subtitle");
    $departmentDescription = departmentsRepeaterField($departmentFields, "description");
    $departmentImage = departmentsRepeaterField($departmentFields, "image");
    $departmentImageAlt = departmentsRepeaterField($departmentFields, "image_alt", $departmentTitle);
    $statsNumber = departmentsRepeaterField($departmentFields, "stats_number");
    $statsLabel = departmentsRepeaterField($departmentFields, "stats_label");
    $departmentButtonText = departmentsRepeaterField($departmentFields, "button_text");
    $departmentButtonUrl = departmentsRepeaterLinkHref($conn, $departmentFields, "#");
    $features = array_values(array_filter([
        departmentsRepeaterField($departmentFields, "feature_1"),
        departmentsRepeaterField($departmentFields, "feature_2"),
        departmentsRepeaterField($departmentFields, "feature_3"),
    ], static function (string $value): bool {
        return $value !== "";
    }));
    ?>
    <div class="department-card" data-aos="zoom-in" data-aos-delay="<?php echo $delay; ?>">
      <div class="department-header">
        <?php if ($departmentIcon !== ""): ?>
          <div class="department-icon">
            <i class="<?php echo htmlspecialchars($departmentIcon, ENT_QUOTES, "UTF-8"); ?>"></i>
          </div>
        <?php endif; ?>
        <?php if ($departmentTitle !== ""): ?>
          <h3><?php echo htmlspecialchars($departmentTitle, ENT_QUOTES, "UTF-8"); ?></h3>
        <?php endif; ?>
        <?php if ($departmentSubtitle !== ""): ?>
          <p class="department-subtitle"><?php echo htmlspecialchars($departmentSubtitle, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>
      </div>

      <?php if ($departmentImage !== ""): ?>
        <div class="department-image-wrapper">
          <img src="<?php echo htmlspecialchars($departmentImage, ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars($departmentImageAlt, ENT_QUOTES, "UTF-8"); ?>" class="img-fluid" loading="lazy">
          <?php if ($statsNumber !== "" || $statsLabel !== ""): ?>
            <div class="department-stats">
              <div class="stat-item">
                <?php if ($statsNumber !== ""): ?><span class="stat-number"><?php echo htmlspecialchars($statsNumber, ENT_QUOTES, "UTF-8"); ?></span><?php endif; ?>
                <?php if ($statsLabel !== ""): ?><span class="stat-label"><?php echo htmlspecialchars($statsLabel, ENT_QUOTES, "UTF-8"); ?></span><?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="department-content">
        <?php if ($departmentDescription !== ""): ?>
          <p><?php echo nl2br(htmlspecialchars($departmentDescription, ENT_QUOTES, "UTF-8")); ?></p>
        <?php endif; ?>
        <?php if ($features !== []): ?>
          <ul class="department-highlights">
            <?php foreach ($features as $feature): ?>
              <li><i class="bi bi-check2"></i> <?php echo htmlspecialchars($feature, ENT_QUOTES, "UTF-8"); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <?php if ($departmentButtonText !== ""): ?>
          <a href="<?php echo htmlspecialchars($departmentButtonUrl, ENT_QUOTES, "UTF-8"); ?>" class="department-link"><?php echo htmlspecialchars($departmentButtonText, ENT_QUOTES, "UTF-8"); ?></a>
        <?php endif; ?>
      </div>
    </div>
    <?php
}

function renderFeaturedDepartment(mysqli $conn, array $departmentItem): void
{
    $departmentFields = $departmentItem["fields"] ?? [];
    $departmentIcon = departmentsRepeaterField($departmentFields, "icon_class", "bi bi-lightning-fill");
    $departmentTitle = departmentsRepeaterField($departmentFields, "title");
    $departmentSubtitle = departmentsRepeaterField($departmentFields, "subtitle");
    $departmentDescription = departmentsRepeaterField($departmentFields, "description");
    $departmentImage = departmentsRepeaterField($departmentFields, "image");
    $departmentImageAlt = departmentsRepeaterField($departmentFields, "image_alt", $departmentTitle);
    $featuredBadgeText = departmentsRepeaterField($departmentFields, "featured_badge_text", "Destacado");
    $departmentButtonText = departmentsRepeaterField($departmentFields, "button_text");
    $departmentButtonUrl = departmentsRepeaterLinkHref($conn, $departmentFields, "#");
    $achievements = array_filter([
        ["icon" => departmentsRepeaterField($departmentFields, "achievement_1_icon"), "text" => departmentsRepeaterField($departmentFields, "achievement_1_text")],
        ["icon" => departmentsRepeaterField($departmentFields, "achievement_2_icon"), "text" => departmentsRepeaterField($departmentFields, "achievement_2_text")],
    ], static function (array $achievement): bool {
        return $achievement["icon"] !== "" || $achievement["text"] !== "";
    });
    $tags = array_values(array_filter([
        departmentsRepeaterField($departmentFields, "tag_1"),
        departmentsRepeaterField($departmentFields, "tag_2"),
        departmentsRepeaterField($departmentFields, "tag_3"),
        departmentsRepeaterField($departmentFields, "tag_4"),
    ], static function (string $value): bool {
        return $value !== "";
    }));
    ?>
    <div class="featured-department">
      <div class="featured-header">
        <?php if ($featuredBadgeText !== ""): ?>
          <div class="featured-badge">
            <i class="bi bi-star-fill"></i>
            <span><?php echo htmlspecialchars($featuredBadgeText, ENT_QUOTES, "UTF-8"); ?></span>
          </div>
        <?php endif; ?>
        <?php if ($departmentIcon !== ""): ?>
          <div class="featured-icon">
            <i class="<?php echo htmlspecialchars($departmentIcon, ENT_QUOTES, "UTF-8"); ?>"></i>
          </div>
        <?php endif; ?>
        <?php if ($departmentTitle !== ""): ?>
          <h2><?php echo htmlspecialchars($departmentTitle, ENT_QUOTES, "UTF-8"); ?></h2>
        <?php endif; ?>
        <?php if ($departmentSubtitle !== ""): ?>
          <p class="featured-subtitle"><?php echo htmlspecialchars($departmentSubtitle, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>
      </div>

      <?php if ($departmentImage !== ""): ?>
        <div class="featured-image">
          <img src="<?php echo htmlspecialchars($departmentImage, ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars($departmentImageAlt, ENT_QUOTES, "UTF-8"); ?>" class="img-fluid" loading="lazy">
          <?php if ($achievements !== []): ?>
            <div class="featured-overlay">
              <div class="achievement-list">
                <?php foreach ($achievements as $achievement): ?>
                  <div class="achievement-item">
                    <?php if ($achievement["icon"] !== ""): ?><i class="<?php echo htmlspecialchars($achievement["icon"], ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?>
                    <?php if ($achievement["text"] !== ""): ?><span><?php echo htmlspecialchars($achievement["text"], ENT_QUOTES, "UTF-8"); ?></span><?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="featured-content">
        <?php if ($departmentDescription !== ""): ?>
          <p><?php echo nl2br(htmlspecialchars($departmentDescription, ENT_QUOTES, "UTF-8")); ?></p>
        <?php endif; ?>
        <?php if ($tags !== []): ?>
          <div class="featured-services">
            <?php foreach ($tags as $tag): ?>
              <div class="service-tag"><?php echo htmlspecialchars($tag, ENT_QUOTES, "UTF-8"); ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php if ($departmentButtonText !== ""): ?>
          <a href="<?php echo htmlspecialchars($departmentButtonUrl, ENT_QUOTES, "UTF-8"); ?>" class="featured-btn">
            <?php echo htmlspecialchars($departmentButtonText, ENT_QUOTES, "UTF-8"); ?>
            <i class="bi bi-arrow-right-circle"></i>
          </a>
        <?php endif; ?>
      </div>
    </div>
    <?php
}

$h1TitleEscaped = htmlspecialchars($h1Title, ENT_QUOTES, "UTF-8");
$pageDescriptionEscaped = $metaDescription !== ""
    ? htmlspecialchars($metaDescription, ENT_QUOTES, "UTF-8")
    : htmlspecialchars((string) ($page["title"] ?? ""), ENT_QUOTES, "UTF-8");

$heroTitle = departmentsFieldValue($departmentsFields, "hero_title", (string) ($page["title"] ?? "Departamentos"));
$heroSubtitle = departmentsFieldValue($departmentsFields, "hero_subtitle", "Conoce nuestras areas de atencion especializadas, disenadas para acompanar cada etapa del cuidado de tu salud.");
$introTitle = departmentsFieldValue($departmentsFields, "intro_title", "Atencion integral por especialidad");
$introText = departmentsFieldValue($departmentsFields, "intro_text", "Reunimos profesionales, tecnologia y procesos coordinados para brindar una experiencia clara, cercana y segura en cada servicio.");
$sectionTitle = departmentsFieldValue($departmentsFields, "section_title", "Nuestros departamentos");
$sectionSubtitle = departmentsFieldValue($departmentsFields, "section_subtitle", "Explora las principales areas de atencion disponibles para pacientes y familias.");
$ctaTitle = departmentsFieldValue($departmentsFields, "cta_title", "Necesitas orientacion para elegir un servicio?");
$ctaText = departmentsFieldValue($departmentsFields, "cta_text", "Nuestro equipo puede ayudarte a identificar el departamento adecuado segun tus necesidades de atencion.");
$ctaButtonText = departmentsFieldValue($departmentsFields, "cta_button_text", "Solicitar informacion");
$ctaButtonUrl = resolvePageContentLinkHref($conn, $departmentsFields, "cta_button", "contact.html");
$departmentItems = departmentsVisibleRepeaterItems($departmentsRepeaters["departments"] ?? []);
$featuredIndex = null;

foreach ($departmentItems as $index => $departmentItem) {
    $departmentFields = $departmentItem["fields"] ?? [];
    $layoutVariant = strtolower(departmentsRepeaterField($departmentFields, "layout_variant", "card"));

    if ($layoutVariant === "featured") {
        $featuredIndex = $index;
        break;
    }
}

if ($featuredIndex === null && $departmentItems !== []) {
    $featuredIndex = count($departmentItems) >= 3 ? 2 : 0;
}

$featuredDepartmentItem = $featuredIndex !== null ? ($departmentItems[$featuredIndex] ?? null) : null;
$leftDepartmentItems = $featuredIndex !== null ? array_slice($departmentItems, 0, $featuredIndex) : $departmentItems;
$rightDepartmentItems = $featuredIndex !== null ? array_slice($departmentItems, $featuredIndex + 1) : [];

require __DIR__ . "/../../includes/header.php";
?>

  <main class="main">
    <div class="page-title">
      <div class="breadcrumbs">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house"></i> Inicio</a></li>
            <li class="breadcrumb-item active current"><?php echo $h1TitleEscaped; ?></li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <?php if (departmentsFieldVisible($departmentsFields, "hero_title") && $heroTitle !== ""): ?>
          <h1><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, "UTF-8"); ?></h1>
        <?php else: ?>
          <h1><?php echo $h1TitleEscaped; ?></h1>
        <?php endif; ?>
        <?php if (departmentsFieldVisible($departmentsFields, "hero_subtitle") && $heroSubtitle !== ""): ?>
          <p><?php echo nl2br(htmlspecialchars($heroSubtitle, ENT_QUOTES, "UTF-8")); ?></p>
        <?php else: ?>
          <p><?php echo $pageDescriptionEscaped; ?></p>
        <?php endif; ?>
      </div>
    </div>

    <section id="departments-intro" class="section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row justify-content-center">
          <div class="col-lg-9 text-center">
            <?php if (departmentsFieldVisible($departmentsFields, "intro_title") && $introTitle !== ""): ?>
              <h2><?php echo htmlspecialchars($introTitle, ENT_QUOTES, "UTF-8"); ?></h2>
            <?php endif; ?>
            <?php if (departmentsFieldVisible($departmentsFields, "intro_text") && $introText !== ""): ?>
              <p><?php echo nl2br(htmlspecialchars($introText, ENT_QUOTES, "UTF-8")); ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <section id="departments" class="departments section">
      <div class="container section-title" data-aos="fade-up">
        <?php if (departmentsFieldVisible($departmentsFields, "section_title") && $sectionTitle !== ""): ?>
          <h2><?php echo htmlspecialchars($sectionTitle, ENT_QUOTES, "UTF-8"); ?></h2>
        <?php endif; ?>
        <?php if (departmentsFieldVisible($departmentsFields, "section_subtitle") && $sectionSubtitle !== ""): ?>
          <p><?php echo nl2br(htmlspecialchars($sectionSubtitle, ENT_QUOTES, "UTF-8")); ?></p>
        <?php endif; ?>
      </div>

      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row gy-4">
          <div class="col-lg-4" data-aos="zoom-in" data-aos-delay="200">
            <?php foreach ($leftDepartmentItems as $index => $departmentItem): ?>
              <?php renderDepartmentCard($conn, $departmentItem, 200 + ($index * 150)); ?>
            <?php endforeach; ?>
          </div>

          <?php if (is_array($featuredDepartmentItem)): ?>
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="250">
              <?php renderFeaturedDepartment($conn, $featuredDepartmentItem); ?>
            </div>
          <?php endif; ?>

          <div class="col-lg-4" data-aos="zoom-in" data-aos-delay="300">
            <?php foreach ($rightDepartmentItems as $index => $departmentItem): ?>
              <?php renderDepartmentCard($conn, $departmentItem, 300 + ($index * 100)); ?>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>

    <?php if ((departmentsFieldVisible($departmentsFields, "cta_title") && $ctaTitle !== "") || (departmentsFieldVisible($departmentsFields, "cta_text") && $ctaText !== "") || (departmentsFieldVisible($departmentsFields, "cta_button_text") && $ctaButtonText !== "")): ?>
      <section id="departments-cta" class="call-to-action section">
        <div class="container" data-aos="fade-up" data-aos-delay="100">
          <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
              <?php if (departmentsFieldVisible($departmentsFields, "cta_title") && $ctaTitle !== ""): ?>
                <h2><?php echo htmlspecialchars($ctaTitle, ENT_QUOTES, "UTF-8"); ?></h2>
              <?php endif; ?>
              <?php if (departmentsFieldVisible($departmentsFields, "cta_text") && $ctaText !== ""): ?>
                <p><?php echo nl2br(htmlspecialchars($ctaText, ENT_QUOTES, "UTF-8")); ?></p>
              <?php endif; ?>
              <?php if (departmentsFieldVisible($departmentsFields, "cta_button_text") && $ctaButtonText !== ""): ?>
                <div class="cta-buttons">
                  <a href="<?php echo htmlspecialchars($ctaButtonUrl, ENT_QUOTES, "UTF-8"); ?>" class="btn-primary"><?php echo htmlspecialchars($ctaButtonText, ENT_QUOTES, "UTF-8"); ?></a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>
    <?php endif; ?>
  </main>

<?php require __DIR__ . "/../../includes/footer.php"; ?>
