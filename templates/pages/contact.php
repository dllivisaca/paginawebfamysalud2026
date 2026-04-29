<?php
$contactContent = is_array($pageContent ?? null) ? $pageContent : ["simple_fields" => [], "repeaters" => []];
$contactFields = $contactContent["simple_fields"] ?? [];
$contactRepeaters = $contactContent["repeaters"] ?? [];

function contactFieldValue(array $fields, string $fieldKey, string $default = ""): string
{
    return (string) ($fields[$fieldKey]["value"] ?? $default);
}

function contactFieldVisible(array $fields, string $fieldKey, bool $default = true): bool
{
    if (!isset($fields[$fieldKey])) {
        return $default;
    }

    return (bool) ($fields[$fieldKey]["is_visible"] ?? false);
}

function contactVisibleRepeaterItems(array $items): array
{
    return array_values(array_filter($items, static function ($item): bool {
        return (bool) ($item["is_visible"] ?? false);
    }));
}

function contactRepeaterField(array $fields, string $fieldKey, string $default = ""): string
{
    return trim((string) ($fields[$fieldKey] ?? $default));
}

function contactNormalizeCustomHref(string $url): string
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
$heroTitle = contactFieldValue($contactFields, "hero_title", (string) ($page["title"] ?? "Contact"));
$heroSubtitle = contactFieldValue($contactFields, "hero_subtitle", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.");
$infoTitle = contactFieldValue($contactFields, "info_title", "Contact Information");
$infoText = contactFieldValue($contactFields, "info_text", "Dignissimos deleniti accusamus rerum voluptate. Dignissimos rerum sit maiores reiciendis voluptate inventore ut.");
$socialTitle = contactFieldValue($contactFields, "social_title", "Follow Us");
$mapEmbedUrl = contactFieldValue($contactFields, "map_embed_url", "https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d48389.78314118045!2d-74.006138!3d40.710059!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a22a3bda30d%3A0xb89d1fe6bc499443!2sDowntown%20Conference%20Center!5e0!3m2!1sen!2sus!4v1676961268712!5m2!1sen!2sus");
$formTitle = contactFieldValue($contactFields, "form_title", "Send Us a Message");
$formText = contactFieldValue($contactFields, "form_text", "Lorem ipsum dolor sit amet consectetur adipiscing elit mauris hendrerit faucibus imperdiet nec eget felis.");
$formNameLabel = contactFieldValue($contactFields, "form_name_label", "Full Name");
$formEmailLabel = contactFieldValue($contactFields, "form_email_label", "Email Address");
$formSubjectLabel = contactFieldValue($contactFields, "form_subject_label", "Subject");
$formMessageLabel = contactFieldValue($contactFields, "form_message_label", "Your Message");
$formLoadingText = contactFieldValue($contactFields, "form_loading_text", "Loading");
$formSentText = contactFieldValue($contactFields, "form_sent_text", "Your message has been sent. Thank you!");
$formButtonText = contactFieldValue($contactFields, "form_button_text", "Send Message");
$infoCards = contactVisibleRepeaterItems($contactRepeaters["info_cards"] ?? []);
$socialLinks = contactVisibleRepeaterItems($contactRepeaters["social_links"] ?? []);

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
            <li class="breadcrumb-item active current"><?php echo htmlspecialchars($heroTitle !== "" ? $heroTitle : "Contact", ENT_QUOTES, "UTF-8"); ?></li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <?php if (contactFieldVisible($contactFields, "hero_title") && $heroTitle !== ""): ?>
        <h1><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, "UTF-8"); ?></h1>
        <?php else: ?>
        <h1><?php echo $h1TitleEscaped; ?></h1>
        <?php endif; ?>
        <?php if (contactFieldVisible($contactFields, "hero_subtitle") && $heroSubtitle !== ""): ?>
        <p><?php echo htmlspecialchars($heroSubtitle, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>
      </div>
    </div><!-- End Page Title -->

    <!-- Contact Section -->
    <section id="contact" class="contact section">

      <div class="container">
        <div class="contact-wrapper">
          <div class="contact-info-panel">
            <div class="contact-info-header">
              <?php if (contactFieldVisible($contactFields, "info_title") && $infoTitle !== ""): ?>
              <h3><?php echo htmlspecialchars($infoTitle, ENT_QUOTES, "UTF-8"); ?></h3>
              <?php endif; ?>
              <?php if (contactFieldVisible($contactFields, "info_text") && $infoText !== ""): ?>
              <p><?php echo htmlspecialchars($infoText, ENT_QUOTES, "UTF-8"); ?></p>
              <?php endif; ?>
            </div>

            <?php if ($infoCards !== []): ?>
            <div class="contact-info-cards">
              <?php foreach ($infoCards as $infoCard): ?>
                <?php
                $infoCardFields = $infoCard["fields"] ?? [];
                $iconClass = contactRepeaterField($infoCardFields, "icon_class");
                $title = contactRepeaterField($infoCardFields, "title");
                $text = contactRepeaterField($infoCardFields, "text");
                ?>
              <div class="info-card">
                <div class="icon-container">
                  <?php if ($iconClass !== ""): ?><i class="<?php echo htmlspecialchars($iconClass, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?>
                </div>
                <div class="card-content">
                  <?php if ($title !== ""): ?><h4><?php echo htmlspecialchars($title, ENT_QUOTES, "UTF-8"); ?></h4><?php endif; ?>
                  <?php if ($text !== ""): ?><p><?php echo htmlspecialchars($text, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
                </div>
              </div>

              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="social-links-panel">
              <?php if (contactFieldVisible($contactFields, "social_title") && $socialTitle !== ""): ?>
              <h5><?php echo htmlspecialchars($socialTitle, ENT_QUOTES, "UTF-8"); ?></h5>
              <?php endif; ?>
              <?php if ($socialLinks !== []): ?>
              <div class="social-icons">
                <?php foreach ($socialLinks as $socialLink): ?>
                  <?php
                  $socialFields = $socialLink["fields"] ?? [];
                  $iconClass = contactRepeaterField($socialFields, "icon_class");
                  $url = contactNormalizeCustomHref(contactRepeaterField($socialFields, "url", "#"));
                  ?>
                <a href="<?php echo htmlspecialchars($url !== "" ? $url : "#", ENT_QUOTES, "UTF-8"); ?>"><?php if ($iconClass !== ""): ?><i class="<?php echo htmlspecialchars($iconClass, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?></a>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="contact-form-panel">
            <?php if (contactFieldVisible($contactFields, "map_embed_url") && $mapEmbedUrl !== ""): ?>
            <div class="map-container">
              <iframe src="<?php echo htmlspecialchars($mapEmbedUrl, ENT_QUOTES, "UTF-8"); ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
            <?php endif; ?>

            <div class="form-container">
              <?php if (contactFieldVisible($contactFields, "form_title") && $formTitle !== ""): ?>
              <h3><?php echo htmlspecialchars($formTitle, ENT_QUOTES, "UTF-8"); ?></h3>
              <?php endif; ?>
              <?php if (contactFieldVisible($contactFields, "form_text") && $formText !== ""): ?>
              <p><?php echo htmlspecialchars($formText, ENT_QUOTES, "UTF-8"); ?></p>
              <?php endif; ?>

              <form action="forms/contact.php" method="post" class="php-email-form">
                <div class="form-floating mb-3">
                  <input type="text" class="form-control" id="nameInput" name="name" placeholder="<?php echo htmlspecialchars($formNameLabel, ENT_QUOTES, "UTF-8"); ?>" required="">
                  <label for="nameInput"><?php echo htmlspecialchars($formNameLabel, ENT_QUOTES, "UTF-8"); ?></label>
                </div>

                <div class="form-floating mb-3">
                  <input type="email" class="form-control" id="emailInput" name="email" placeholder="<?php echo htmlspecialchars($formEmailLabel, ENT_QUOTES, "UTF-8"); ?>" required="">
                  <label for="emailInput"><?php echo htmlspecialchars($formEmailLabel, ENT_QUOTES, "UTF-8"); ?></label>
                </div>

                <div class="form-floating mb-3">
                  <input type="text" class="form-control" id="subjectInput" name="subject" placeholder="<?php echo htmlspecialchars($formSubjectLabel, ENT_QUOTES, "UTF-8"); ?>" required="">
                  <label for="subjectInput"><?php echo htmlspecialchars($formSubjectLabel, ENT_QUOTES, "UTF-8"); ?></label>
                </div>

                <div class="form-floating mb-3">
                  <textarea class="form-control" id="messageInput" name="message" rows="5" placeholder="<?php echo htmlspecialchars($formMessageLabel, ENT_QUOTES, "UTF-8"); ?>" style="height: 150px" required=""></textarea>
                  <label for="messageInput"><?php echo htmlspecialchars($formMessageLabel, ENT_QUOTES, "UTF-8"); ?></label>
                </div>

                <div class="my-3">
                  <div class="loading"><?php echo htmlspecialchars($formLoadingText, ENT_QUOTES, "UTF-8"); ?></div>
                  <div class="error-message"></div>
                  <div class="sent-message"><?php echo htmlspecialchars($formSentText, ENT_QUOTES, "UTF-8"); ?></div>
                </div>

                <div class="d-grid">
                  <button type="submit" class="btn-submit"><?php echo htmlspecialchars($formButtonText, ENT_QUOTES, "UTF-8"); ?> <i class="bi bi-send-fill ms-2"></i></button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section><!-- /Contact Section -->

  </main>

<?php require __DIR__ . "/../../includes/footer.php"; ?>
