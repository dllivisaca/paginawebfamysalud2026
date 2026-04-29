<?php
$faqContent = is_array($pageContent ?? null) ? $pageContent : ["simple_fields" => [], "repeaters" => []];
$faqFields = $faqContent["simple_fields"] ?? [];
$faqRepeaters = $faqContent["repeaters"] ?? [];

function faqFieldValue(array $fields, string $fieldKey, string $default = ""): string
{
    return (string) ($fields[$fieldKey]["value"] ?? $default);
}

function faqFieldVisible(array $fields, string $fieldKey, bool $default = true): bool
{
    if (!isset($fields[$fieldKey])) {
        return $default;
    }

    return (bool) ($fields[$fieldKey]["is_visible"] ?? false);
}

function faqVisibleRepeaterItems(array $items): array
{
    return array_values(array_filter($items, static function ($item): bool {
        return (bool) ($item["is_visible"] ?? false);
    }));
}

function faqRepeaterField(array $fields, string $fieldKey, string $default = ""): string
{
    return trim((string) ($fields[$fieldKey] ?? $default));
}

function faqNormalizeCustomHref(string $url): string
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
$heroTitle = faqFieldValue($faqFields, "hero_title", (string) ($page["title"] ?? "Frequenty Asked Questions"));
$heroSubtitle = faqFieldValue($faqFields, "hero_subtitle", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.");
$contactIcon = faqFieldValue($faqFields, "contact_icon", "bi bi-question-circle");
$contactTitle = faqFieldValue($faqFields, "contact_title", "Still Have Questions?");
$contactText = faqFieldValue($faqFields, "contact_text", "Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula. Vestibulum ac diam sit amet quam vehicula elementum.");
$contactOptions = faqVisibleRepeaterItems($faqRepeaters["contact_options"] ?? []);
$faqItems = faqVisibleRepeaterItems($faqRepeaters["faq_items"] ?? []);

require __DIR__ . "/../../includes/header.php";
?>

  <main class="main">

    <!-- Page Title -->
    <div class="page-title position-relative">
      <div class="breadcrumbs">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#"><i class="bi bi-house"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="#">Category</a></li>
            <li class="breadcrumb-item active current"><?php echo htmlspecialchars($heroTitle !== "" ? $heroTitle : "Frequenty Asked Questions", ENT_QUOTES, "UTF-8"); ?></li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <?php if (faqFieldVisible($faqFields, "hero_title") && $heroTitle !== ""): ?>
        <h1><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, "UTF-8"); ?></h1>
        <?php else: ?>
        <h1><?php echo $h1TitleEscaped; ?></h1>
        <?php endif; ?>
        <?php if (faqFieldVisible($faqFields, "hero_subtitle") && $heroSubtitle !== ""): ?>
        <p><?php echo htmlspecialchars($heroSubtitle, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>
      </div>
    </div><!-- End Page Title -->

    <!-- Faq Section -->
    <section id="faq" class="faq section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-5">
          <div class="col-lg-6" data-aos="zoom-out" data-aos-delay="200">
            <div class="faq-contact-card">
              <div class="card-icon">
                <?php if (faqFieldVisible($faqFields, "contact_icon") && $contactIcon !== ""): ?><i class="<?php echo htmlspecialchars($contactIcon, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?>
              </div>
              <div class="card-content">
                <?php if (faqFieldVisible($faqFields, "contact_title") && $contactTitle !== ""): ?>
                <h3><?php echo htmlspecialchars($contactTitle, ENT_QUOTES, "UTF-8"); ?></h3>
                <?php endif; ?>
                <?php if (faqFieldVisible($faqFields, "contact_text") && $contactText !== ""): ?>
                <p><?php echo htmlspecialchars($contactText, ENT_QUOTES, "UTF-8"); ?></p>
                <?php endif; ?>
                <?php if ($contactOptions !== []): ?>
                <div class="contact-options">
                  <?php foreach ($contactOptions as $contactOption): ?>
                    <?php
                    $optionFields = $contactOption["fields"] ?? [];
                    $iconClass = faqRepeaterField($optionFields, "icon_class");
                    $label = faqRepeaterField($optionFields, "label");
                    $url = faqNormalizeCustomHref(faqRepeaterField($optionFields, "url", "#"));
                    ?>
                  <a href="<?php echo htmlspecialchars($url !== "" ? $url : "#", ENT_QUOTES, "UTF-8"); ?>" class="contact-option">
                    <?php if ($iconClass !== ""): ?><i class="<?php echo htmlspecialchars($iconClass, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?>
                    <?php if ($label !== ""): ?><span><?php echo htmlspecialchars($label, ENT_QUOTES, "UTF-8"); ?></span><?php endif; ?>
                  </a>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="300">
            <div class="faq-accordion">
              <?php foreach ($faqItems as $faqIndex => $faqItem): ?>
                <?php
                $faqItemFields = $faqItem["fields"] ?? [];
                $question = faqRepeaterField($faqItemFields, "question");
                $answer = faqRepeaterField($faqItemFields, "answer");
                $delay = $faqIndex === 1 ? ' data-aos="zoom-in" data-aos-delay="200"' : "";
                ?>
              <div class="faq-item<?php echo $faqIndex === 0 ? " faq-active" : ""; ?>"<?php echo $delay; ?>>
                <div class="faq-header">
                  <?php if ($question !== ""): ?><h3><?php echo htmlspecialchars($question, ENT_QUOTES, "UTF-8"); ?></h3><?php endif; ?>
                  <i class="bi bi-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-content">
                  <?php if ($answer !== ""): ?>
                  <p>
                    <?php echo htmlspecialchars($answer, ENT_QUOTES, "UTF-8"); ?>
                  </p>
                  <?php endif; ?>
                </div>
              </div><!-- End FAQ Item-->

              <?php endforeach; ?>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Faq Section -->

  </main>

<?php require __DIR__ . "/../../includes/footer.php"; ?>
