<?php
$doctorsContent = is_array($pageContent ?? null) ? $pageContent : ["simple_fields" => [], "repeaters" => []];
$doctorsFields = $doctorsContent["simple_fields"] ?? [];
$doctorsRepeaters = $doctorsContent["repeaters"] ?? [];

function doctorsFieldValue(array $fields, string $fieldKey, string $default = ""): string
{
    return (string) ($fields[$fieldKey]["value"] ?? $default);
}

function doctorsFieldVisible(array $fields, string $fieldKey, bool $default = true): bool
{
    if (!isset($fields[$fieldKey])) {
        return $default;
    }

    return (bool) ($fields[$fieldKey]["is_visible"] ?? false);
}

function doctorsVisibleRepeaterItems(array $items): array
{
    return array_values(array_filter($items, static function ($item): bool {
        return (bool) ($item["is_visible"] ?? false);
    }));
}

function doctorsRepeaterField(array $fields, string $fieldKey, string $default = ""): string
{
    return trim((string) ($fields[$fieldKey] ?? $default));
}

function doctorsNormalizeCustomHref(string $url): string
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
$heroTitle = doctorsFieldValue($doctorsFields, "hero_title", (string) ($page["title"] ?? "Doctors"));
$heroSubtitle = doctorsFieldValue($doctorsFields, "hero_subtitle", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.");
$doctorItems = doctorsVisibleRepeaterItems($doctorsRepeaters["doctors"] ?? []);

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
            <li class="breadcrumb-item active current"><?php echo htmlspecialchars($heroTitle !== "" ? $heroTitle : "Doctors", ENT_QUOTES, "UTF-8"); ?></li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <?php if (doctorsFieldVisible($doctorsFields, "hero_title") && $heroTitle !== ""): ?>
        <h1><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, "UTF-8"); ?></h1>
        <?php else: ?>
        <h1><?php echo $h1TitleEscaped; ?></h1>
        <?php endif; ?>
        <?php if (doctorsFieldVisible($doctorsFields, "hero_subtitle") && $heroSubtitle !== ""): ?>
        <p><?php echo htmlspecialchars($heroSubtitle, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>
      </div>
    </div><!-- End Page Title -->

    <!-- Doctors Section -->
    <section id="doctors" class="doctors section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4">

          <?php foreach ($doctorItems as $doctorIndex => $doctorItem): ?>
            <?php
            $doctorFields = $doctorItem["fields"] ?? [];
            $image = doctorsRepeaterField($doctorFields, "image");
            $imageAlt = doctorsRepeaterField($doctorFields, "image_alt");
            $name = doctorsRepeaterField($doctorFields, "name");
            $specialty = doctorsRepeaterField($doctorFields, "specialty");
            $bio = doctorsRepeaterField($doctorFields, "bio");
            $experience = doctorsRepeaterField($doctorFields, "experience");
            $buttonText = doctorsRepeaterField($doctorFields, "button_text");
            $buttonUrl = doctorsNormalizeCustomHref(doctorsRepeaterField($doctorFields, "button_url", "#"));
            $linkedinUrl = doctorsNormalizeCustomHref(doctorsRepeaterField($doctorFields, "linkedin_url", "#"));
            $twitterUrl = doctorsNormalizeCustomHref(doctorsRepeaterField($doctorFields, "twitter_url", "#"));
            $emailUrl = doctorsNormalizeCustomHref(doctorsRepeaterField($doctorFields, "email_url", "#"));
            $delay = 100 + (($doctorIndex % 4) * 100);
            ?>
          <div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
            <div class="doctor-card">
              <div class="doctor-image">
                <?php if ($image !== ""): ?>
                <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars($imageAlt !== "" ? $imageAlt : $name, ENT_QUOTES, "UTF-8"); ?>" class="img-fluid">
                <?php endif; ?>
                <div class="doctor-overlay">
                  <div class="doctor-social">
                    <a href="<?php echo htmlspecialchars($linkedinUrl !== "" ? $linkedinUrl : "#", ENT_QUOTES, "UTF-8"); ?>" class="social-link"><i class="bi bi-linkedin"></i></a>
                    <a href="<?php echo htmlspecialchars($twitterUrl !== "" ? $twitterUrl : "#", ENT_QUOTES, "UTF-8"); ?>" class="social-link"><i class="bi bi-twitter"></i></a>
                    <a href="<?php echo htmlspecialchars($emailUrl !== "" ? $emailUrl : "#", ENT_QUOTES, "UTF-8"); ?>" class="social-link"><i class="bi bi-envelope"></i></a>
                  </div>
                </div>
              </div>
              <div class="doctor-content">
                <?php if ($name !== ""): ?>
                <h4 class="doctor-name"><?php echo htmlspecialchars($name, ENT_QUOTES, "UTF-8"); ?></h4>
                <?php endif; ?>
                <?php if ($specialty !== ""): ?>
                <span class="doctor-specialty"><?php echo htmlspecialchars($specialty, ENT_QUOTES, "UTF-8"); ?></span>
                <?php endif; ?>
                <?php if ($bio !== ""): ?>
                <p class="doctor-bio"><?php echo htmlspecialchars($bio, ENT_QUOTES, "UTF-8"); ?></p>
                <?php endif; ?>
                <?php if ($experience !== ""): ?>
                <div class="doctor-experience">
                  <span class="experience-badge"><?php echo htmlspecialchars($experience, ENT_QUOTES, "UTF-8"); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($buttonText !== ""): ?>
                <a href="<?php echo htmlspecialchars($buttonUrl !== "" ? $buttonUrl : "#", ENT_QUOTES, "UTF-8"); ?>" class="btn-appointment"><?php echo htmlspecialchars($buttonText, ENT_QUOTES, "UTF-8"); ?></a>
                <?php endif; ?>
              </div>
            </div>
          </div><!-- End Doctor Card -->

          <?php endforeach; ?>

        </div>

      </div>

    </section><!-- /Doctors Section -->

  </main>

<?php require __DIR__ . "/../../includes/footer.php"; ?>
