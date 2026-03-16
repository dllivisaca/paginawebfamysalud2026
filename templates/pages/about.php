<?php
$aboutContent = is_array($pageContent ?? null) ? $pageContent : ["simple_fields" => [], "repeaters" => []];
$aboutFields = $aboutContent["simple_fields"] ?? [];
$aboutRepeaters = $aboutContent["repeaters"] ?? [];

function aboutFieldValue(array $fields, string $fieldKey, string $default = ""): string
{
    return (string) ($fields[$fieldKey]["value"] ?? $default);
}

function aboutFieldVisible(array $fields, string $fieldKey, bool $default = true): bool
{
    if (!isset($fields[$fieldKey])) {
        return $default;
    }

    return (bool) ($fields[$fieldKey]["is_visible"] ?? false);
}

$h1TitleEscaped = htmlspecialchars($h1Title, ENT_QUOTES, "UTF-8");
$pageDescriptionEscaped = $metaDescription !== ""
    ? htmlspecialchars($metaDescription, ENT_QUOTES, "UTF-8")
    : htmlspecialchars((string) ($page["title"] ?? ""), ENT_QUOTES, "UTF-8");

$introTitle = aboutFieldValue($aboutFields, "intro_title", "Comprometidos con la excelencia en salud");
$introText1 = aboutFieldValue($aboutFields, "intro_text_1", "Brindamos atención integral con enfoque humano, tecnología adecuada y procesos orientados al bienestar de cada paciente y su familia.");
$introText2 = aboutFieldValue($aboutFields, "intro_text_2", "Nuestro equipo trabaja para ofrecer una experiencia confiable, cercana y profesional, con servicios pensados para acompañarte en cada etapa de cuidado.");
$primaryCtaText = aboutFieldValue($aboutFields, "primary_cta_text", "Conoce a nuestros doctores");
$primaryCtaUrl = aboutFieldValue($aboutFields, "primary_cta_url", "doctors.html");
$secondaryCtaText = aboutFieldValue($aboutFields, "secondary_cta_text", "Ver servicios");
$secondaryCtaUrl = aboutFieldValue($aboutFields, "secondary_cta_url", "services.html");
$mainImage = aboutFieldValue($aboutFields, "main_image", "assets/img/health/consultation-3.webp");
$mainImageAlt = aboutFieldValue($aboutFields, "main_image_alt", "Consulta de salud");
$gridImage1 = aboutFieldValue($aboutFields, "grid_image_1", "assets/img/health/facilities-2.webp");
$gridImage1Alt = aboutFieldValue($aboutFields, "grid_image_1_alt", "Instalaciones médicas");
$gridImage2 = aboutFieldValue($aboutFields, "grid_image_2", "assets/img/health/staff-5.webp");
$gridImage2Alt = aboutFieldValue($aboutFields, "grid_image_2_alt", "Personal médico");
$certificationsTitle = aboutFieldValue($aboutFields, "certifications_title", "Acreditaciones y certificaciones");
$certificationsText = aboutFieldValue($aboutFields, "certifications_text", "Contamos con respaldos y estándares de calidad que fortalecen la confianza de nuestros pacientes.");
$statsItems = $aboutRepeaters["stats"] ?? [];
$certificationItems = $aboutRepeaters["certifications"] ?? [];

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
        <h1><?php echo $h1TitleEscaped; ?></h1>
        <p><?php echo $pageDescriptionEscaped; ?></p>
      </div>
    </div>

    <section id="about" class="about section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row gy-4">
          <div class="col-lg-6">
            <div class="content">
              <?php if (aboutFieldVisible($aboutFields, "intro_title") && $introTitle !== ""): ?>
                <h2><?php echo htmlspecialchars($introTitle, ENT_QUOTES, "UTF-8"); ?></h2>
              <?php endif; ?>

              <?php if (aboutFieldVisible($aboutFields, "intro_text_1") && $introText1 !== ""): ?>
                <p><?php echo nl2br(htmlspecialchars($introText1, ENT_QUOTES, "UTF-8")); ?></p>
              <?php endif; ?>

              <?php if (aboutFieldVisible($aboutFields, "intro_text_2") && $introText2 !== ""): ?>
                <p><?php echo nl2br(htmlspecialchars($introText2, ENT_QUOTES, "UTF-8")); ?></p>
              <?php endif; ?>

              <?php if ($statsItems !== []): ?>
                <div class="stats-container" data-aos="fade-up" data-aos-delay="200">
                  <div class="row gy-4">
                    <?php foreach ($statsItems as $statsItem): ?>
                      <?php if (!(bool) ($statsItem["is_visible"] ?? false)): continue; endif; ?>
                      <?php $statsFields = $statsItem["fields"] ?? []; ?>
                      <div class="col-sm-6 col-lg-12 col-xl-6">
                        <div class="stat-item">
                          <div class="stat-number">
                            <span><?php echo htmlspecialchars((string) ($statsFields["number"] ?? ""), ENT_QUOTES, "UTF-8"); ?></span><?php echo htmlspecialchars((string) ($statsFields["suffix"] ?? ""), ENT_QUOTES, "UTF-8"); ?>
                          </div>
                          <div class="stat-label"><?php echo htmlspecialchars((string) ($statsFields["label"] ?? ""), ENT_QUOTES, "UTF-8"); ?></div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>

              <?php if ((aboutFieldVisible($aboutFields, "primary_cta_text") && $primaryCtaText !== "") || (aboutFieldVisible($aboutFields, "secondary_cta_text") && $secondaryCtaText !== "")): ?>
                <div class="cta-buttons" data-aos="fade-up" data-aos-delay="300">
                  <?php if (aboutFieldVisible($aboutFields, "primary_cta_text") && $primaryCtaText !== ""): ?>
                    <a href="<?php echo htmlspecialchars($primaryCtaUrl, ENT_QUOTES, "UTF-8"); ?>" class="btn-primary"><?php echo htmlspecialchars($primaryCtaText, ENT_QUOTES, "UTF-8"); ?></a>
                  <?php endif; ?>
                  <?php if (aboutFieldVisible($aboutFields, "secondary_cta_text") && $secondaryCtaText !== ""): ?>
                    <a href="<?php echo htmlspecialchars($secondaryCtaUrl, ENT_QUOTES, "UTF-8"); ?>" class="btn-secondary"><?php echo htmlspecialchars($secondaryCtaText, ENT_QUOTES, "UTF-8"); ?></a>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="image-section" data-aos="fade-left" data-aos-delay="200">
              <?php if (aboutFieldVisible($aboutFields, "main_image") && $mainImage !== ""): ?>
                <div class="main-image">
                  <img src="<?php echo htmlspecialchars($mainImage, ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars($mainImageAlt, ENT_QUOTES, "UTF-8"); ?>" class="img-fluid">
                </div>
              <?php endif; ?>
              <?php if ((aboutFieldVisible($aboutFields, "grid_image_1") && $gridImage1 !== "") || (aboutFieldVisible($aboutFields, "grid_image_2") && $gridImage2 !== "")): ?>
                <div class="image-grid">
                  <?php if (aboutFieldVisible($aboutFields, "grid_image_1") && $gridImage1 !== ""): ?>
                    <div class="grid-item">
                      <img src="<?php echo htmlspecialchars($gridImage1, ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars($gridImage1Alt, ENT_QUOTES, "UTF-8"); ?>" class="img-fluid">
                    </div>
                  <?php endif; ?>
                  <?php if (aboutFieldVisible($aboutFields, "grid_image_2") && $gridImage2 !== ""): ?>
                    <div class="grid-item">
                      <img src="<?php echo htmlspecialchars($gridImage2, ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars($gridImage2Alt, ENT_QUOTES, "UTF-8"); ?>" class="img-fluid">
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <?php if ((aboutFieldVisible($aboutFields, "certifications_title") && $certificationsTitle !== "") || (aboutFieldVisible($aboutFields, "certifications_text") && $certificationsText !== "") || $certificationItems !== []): ?>
          <div class="certifications-section" data-aos="fade-up" data-aos-delay="400">
            <div class="row">
              <div class="col-lg-12">
                <div class="section-header">
                  <?php if (aboutFieldVisible($aboutFields, "certifications_title") && $certificationsTitle !== ""): ?>
                    <h3><?php echo htmlspecialchars($certificationsTitle, ENT_QUOTES, "UTF-8"); ?></h3>
                  <?php endif; ?>
                  <?php if (aboutFieldVisible($aboutFields, "certifications_text") && $certificationsText !== ""): ?>
                    <p><?php echo nl2br(htmlspecialchars($certificationsText, ENT_QUOTES, "UTF-8")); ?></p>
                  <?php endif; ?>
                </div>
                <?php if ($certificationItems !== []): ?>
                  <div class="certifications-grid">
                    <?php foreach ($certificationItems as $certificationItem): ?>
                      <?php if (!(bool) ($certificationItem["is_visible"] ?? false)): continue; endif; ?>
                      <?php $certFields = $certificationItem["fields"] ?? []; $certLogo = (string) ($certFields["logo_image"] ?? ""); ?>
                      <?php if ($certLogo === ""): continue; endif; ?>
                      <div class="certification-item">
                        <img src="<?php echo htmlspecialchars($certLogo, ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars((string) ($certFields["logo_alt"] ?? ""), ENT_QUOTES, "UTF-8"); ?>" class="img-fluid">
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

<?php require __DIR__ . "/../../includes/footer.php"; ?>