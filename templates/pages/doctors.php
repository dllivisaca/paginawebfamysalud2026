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

function doctorsRepeaterFieldVisible(array $item, string $fieldKey, bool $default = true): bool
{
    if (!isset($item["field_visibility"]) || !is_array($item["field_visibility"])) {
        return $default;
    }

    if (!array_key_exists($fieldKey, $item["field_visibility"])) {
        return $default;
    }

    return (bool) $item["field_visibility"][$fieldKey];
}

function doctorsRepeaterLinkHref(mysqli $conn, array $fields, string $fallbackUrl = "#"): string
{
    $linkType = trim((string) ($fields["button_link_type"] ?? ""));
    $pageId = (int) trim((string) ($fields["button_page_id"] ?? "0"));
    $customUrl = doctorsNormalizeCustomHref((string) ($fields["button_url"] ?? ""));

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
            $buttonLinkType = doctorsRepeaterField($doctorFields, "button_link_type", "custom");
            $buttonUrl = doctorsRepeaterLinkHref($conn, $doctorFields, "#");
            $linkedinUrl = doctorsNormalizeCustomHref(doctorsRepeaterField($doctorFields, "linkedin_url", "#"));
            $twitterUrl = doctorsNormalizeCustomHref(doctorsRepeaterField($doctorFields, "twitter_url", "#"));
            $emailUrl = doctorsNormalizeCustomHref(doctorsRepeaterField($doctorFields, "email_url", "#"));
            $imageVisible = doctorsRepeaterFieldVisible($doctorItem, "image") && $image !== "";
            $imageAltVisible = doctorsRepeaterFieldVisible($doctorItem, "image_alt") && $imageAlt !== "";
            $nameVisible = doctorsRepeaterFieldVisible($doctorItem, "name") && $name !== "";
            $specialtyVisible = doctorsRepeaterFieldVisible($doctorItem, "specialty") && $specialty !== "";
            $bioVisible = doctorsRepeaterFieldVisible($doctorItem, "bio") && $bio !== "";
            $experienceVisible = doctorsRepeaterFieldVisible($doctorItem, "experience") && $experience !== "";
            $linkedinVisible = doctorsRepeaterFieldVisible($doctorItem, "linkedin_url") && $linkedinUrl !== "";
            $twitterVisible = doctorsRepeaterFieldVisible($doctorItem, "twitter_url") && $twitterUrl !== "";
            $emailVisible = doctorsRepeaterFieldVisible($doctorItem, "email_url") && $emailUrl !== "";
            $buttonTextVisible = doctorsRepeaterFieldVisible($doctorItem, "button_text") && $buttonText !== "";
            $buttonUrlVisible = $buttonUrl !== "" && ($buttonLinkType === "internal" || doctorsRepeaterFieldVisible($doctorItem, "button_url"));
            $socialsVisible = $linkedinVisible || $twitterVisible || $emailVisible;
            $delay = 100 + (($doctorIndex % 4) * 100);
            ?>
          <div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
            <div class="doctor-card">
              <div class="doctor-image">
                <?php if ($imageVisible): ?>
                <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars($imageAltVisible ? $imageAlt : ($nameVisible ? $name : ""), ENT_QUOTES, "UTF-8"); ?>" class="img-fluid">
                <?php endif; ?>
                <?php if ($socialsVisible): ?>
                <div class="doctor-overlay">
                  <div class="doctor-social">
                    <?php if ($linkedinVisible): ?>
                    <a href="<?php echo htmlspecialchars($linkedinUrl, ENT_QUOTES, "UTF-8"); ?>" class="social-link"><i class="bi bi-linkedin"></i></a>
                    <?php endif; ?>
                    <?php if ($twitterVisible): ?>
                    <a href="<?php echo htmlspecialchars($twitterUrl, ENT_QUOTES, "UTF-8"); ?>" class="social-link"><i class="bi bi-twitter"></i></a>
                    <?php endif; ?>
                    <?php if ($emailVisible): ?>
                    <a href="<?php echo htmlspecialchars($emailUrl, ENT_QUOTES, "UTF-8"); ?>" class="social-link"><i class="bi bi-envelope"></i></a>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endif; ?>
              </div>
              <div class="doctor-content">
                <?php if ($nameVisible): ?>
                <h4 class="doctor-name"><?php echo htmlspecialchars($name, ENT_QUOTES, "UTF-8"); ?></h4>
                <?php endif; ?>
                <?php if ($specialtyVisible): ?>
                <span class="doctor-specialty"><?php echo htmlspecialchars($specialty, ENT_QUOTES, "UTF-8"); ?></span>
                <?php endif; ?>
                <?php if ($bioVisible): ?>
                <p class="doctor-bio"><?php echo htmlspecialchars($bio, ENT_QUOTES, "UTF-8"); ?></p>
                <?php endif; ?>
                <?php if ($experienceVisible): ?>
                <div class="doctor-experience">
                  <span class="experience-badge"><?php echo htmlspecialchars($experience, ENT_QUOTES, "UTF-8"); ?></span>
                </div>
                <?php endif; ?>
                <?php if ($buttonTextVisible): ?>
                <a href="<?php echo htmlspecialchars($buttonUrlVisible ? $buttonUrl : "#", ENT_QUOTES, "UTF-8"); ?>" class="btn-appointment"><?php echo htmlspecialchars($buttonText, ENT_QUOTES, "UTF-8"); ?></a>
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
