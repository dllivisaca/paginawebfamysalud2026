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
          <?php foreach ($departmentItems as $index => $departmentItem): ?>
            <?php
            $departmentFields = $departmentItem["fields"] ?? [];
            $departmentIcon = trim((string) ($departmentFields["icon_class"] ?? "bi bi-heart-pulse"));
            $departmentTitle = trim((string) ($departmentFields["title"] ?? ""));
            $departmentDescription = trim((string) ($departmentFields["description"] ?? ""));
            $departmentImage = trim((string) ($departmentFields["image"] ?? ""));
            $departmentImageAlt = trim((string) ($departmentFields["image_alt"] ?? $departmentTitle));
            $departmentButtonText = trim((string) ($departmentFields["button_text"] ?? ""));
            $departmentButtonUrl = departmentsRepeaterLinkHref($conn, $departmentFields, "#");
            ?>
            <div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="<?php echo 150 + (($index % 3) * 100); ?>">
              <div class="department-card">
                <div class="department-header">
                  <?php if ($departmentIcon !== ""): ?>
                    <div class="department-icon">
                      <i class="<?php echo htmlspecialchars($departmentIcon, ENT_QUOTES, "UTF-8"); ?>"></i>
                    </div>
                  <?php endif; ?>
                  <?php if ($departmentTitle !== ""): ?>
                    <h3><?php echo htmlspecialchars($departmentTitle, ENT_QUOTES, "UTF-8"); ?></h3>
                  <?php endif; ?>
                </div>

                <?php if ($departmentImage !== ""): ?>
                  <div class="department-image-wrapper">
                    <img src="<?php echo htmlspecialchars($departmentImage, ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars($departmentImageAlt, ENT_QUOTES, "UTF-8"); ?>" class="img-fluid" loading="lazy">
                  </div>
                <?php endif; ?>

                <div class="department-content">
                  <?php if ($departmentDescription !== ""): ?>
                    <p><?php echo nl2br(htmlspecialchars($departmentDescription, ENT_QUOTES, "UTF-8")); ?></p>
                  <?php endif; ?>
                  <?php if ($departmentButtonText !== ""): ?>
                    <a href="<?php echo htmlspecialchars($departmentButtonUrl, ENT_QUOTES, "UTF-8"); ?>" class="department-link"><?php echo htmlspecialchars($departmentButtonText, ENT_QUOTES, "UTF-8"); ?></a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
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
