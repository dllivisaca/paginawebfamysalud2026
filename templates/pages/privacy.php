<?php
$privacyContent = is_array($pageContent ?? null) ? $pageContent : ["simple_fields" => [], "repeaters" => []];
$privacyFields = $privacyContent["simple_fields"] ?? [];
$privacyRepeaters = $privacyContent["repeaters"] ?? [];

function privacyFieldValue(array $fields, string $fieldKey, string $default = ""): string
{
    return (string) ($fields[$fieldKey]["value"] ?? $default);
}

function privacyFieldVisible(array $fields, string $fieldKey, bool $default = true): bool
{
    if (!isset($fields[$fieldKey])) {
        return $default;
    }

    return (bool) ($fields[$fieldKey]["is_visible"] ?? false);
}

function privacyVisibleRepeaterItems(array $items): array
{
    return array_values(array_filter($items, static function ($item): bool {
        return (bool) ($item["is_visible"] ?? false);
    }));
}

function privacyRepeaterField(array $fields, string $fieldKey, string $default = ""): string
{
    return trim((string) ($fields[$fieldKey] ?? $default));
}

$h1TitleEscaped = htmlspecialchars($h1Title, ENT_QUOTES, "UTF-8");
$heroTitle = privacyFieldValue($privacyFields, "hero_title", (string) ($page["title"] ?? "Privacy"));
$heroSubtitle = privacyFieldValue($privacyFields, "hero_subtitle", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.");
$effectiveDate = privacyFieldValue($privacyFields, "effective_date", "Effective Date: February 27, 2025");
$policyTitle = privacyFieldValue($privacyFields, "policy_title", "Privacy Policy");
$policyIntro = privacyFieldValue($privacyFields, "policy_intro", "This Privacy Policy describes how we collect, use, process, and disclose your information, including personal information, in conjunction with your access to and use of our services.");
$introTitle = privacyFieldValue($privacyFields, "intro_title", "1. Introduction");
$introTextOne = privacyFieldValue($privacyFields, "intro_text_1", "When you use our services, you're trusting us with your information. We understand this is a big responsibility and work hard to protect your information and put you in control.");
$introTextTwo = privacyFieldValue($privacyFields, "intro_text_2", "This Privacy Policy is meant to help you understand what information we collect, why we collect it, and how you can update, manage, export, and delete your information.");
$collectionTitle = privacyFieldValue($privacyFields, "collection_title", "2. Information We Collect");
$collectionText = privacyFieldValue($privacyFields, "collection_text", "We collect information to provide better services to our users. The types of information we collect include:");
$providedTitle = privacyFieldValue($privacyFields, "provided_title", "2.1 Information You Provide");
$providedText = privacyFieldValue($privacyFields, "provided_text", "When you create an account or use our services, you provide us with personal information that includes:");
$automaticTitle = privacyFieldValue($privacyFields, "automatic_title", "2.2 Automatic Information");
$automaticText = privacyFieldValue($privacyFields, "automatic_text", "We automatically collect and store certain information when you use our services:");
$useTitle = privacyFieldValue($privacyFields, "use_title", "3. How We Use Your Information");
$useText = privacyFieldValue($privacyFields, "use_text", "We use the information we collect to provide, maintain, and improve our services. Specifically, we use your information to:");
$sharingTitle = privacyFieldValue($privacyFields, "sharing_title", "4. Information Sharing and Disclosure");
$sharingText = privacyFieldValue($privacyFields, "sharing_text", "We do not share personal information with companies, organizations, or individuals outside of our company except in the following cases:");
$consentTitle = privacyFieldValue($privacyFields, "consent_title", "4.1 With Your Consent");
$consentText = privacyFieldValue($privacyFields, "consent_text", "We will share personal information with companies, organizations, or individuals outside of our company when we have your consent to do so.");
$legalTitle = privacyFieldValue($privacyFields, "legal_title", "4.2 For Legal Reasons");
$legalText = privacyFieldValue($privacyFields, "legal_text", "We will share personal information if we have a good-faith belief that access, use, preservation, or disclosure of the information is reasonably necessary to:");
$securityTitle = privacyFieldValue($privacyFields, "security_title", "5. Data Security");
$securityText = privacyFieldValue($privacyFields, "security_text", "We work hard to protect our users from unauthorized access to or unauthorized alteration, disclosure, or destruction of information we hold. In particular:");
$rightsTitle = privacyFieldValue($privacyFields, "rights_title", "6. Your Rights and Choices");
$rightsText = privacyFieldValue($privacyFields, "rights_text", "You have certain rights regarding your personal information, including:");
$updatesTitle = privacyFieldValue($privacyFields, "updates_title", "7. Changes to This Policy");
$updatesTextOne = privacyFieldValue($privacyFields, "updates_text_1", "We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the effective date at the top.");
$updatesTextTwo = privacyFieldValue($privacyFields, "updates_text_2", "Your continued use of our services after any changes to this Privacy Policy constitutes your acceptance of such changes.");
$contactTitle = privacyFieldValue($privacyFields, "contact_title", "Contact Us");
$contactText = privacyFieldValue($privacyFields, "contact_text", "If you have any questions about this Privacy Policy or our practices, please contact us:");
$contactEmailLabel = privacyFieldValue($privacyFields, "contact_email_label", "Email:");
$contactEmail = privacyFieldValue($privacyFields, "contact_email", "privacy@example.com");
$contactAddressLabel = privacyFieldValue($privacyFields, "contact_address_label", "Address:");
$contactAddress = privacyFieldValue($privacyFields, "contact_address", "123 Privacy Street, Security City, 12345");
$providedItems = privacyVisibleRepeaterItems($privacyRepeaters["provided_items"] ?? []);
$automaticItems = privacyVisibleRepeaterItems($privacyRepeaters["automatic_items"] ?? []);
$useItems = privacyVisibleRepeaterItems($privacyRepeaters["use_items"] ?? []);
$legalItems = privacyVisibleRepeaterItems($privacyRepeaters["legal_items"] ?? []);
$securityItems = privacyVisibleRepeaterItems($privacyRepeaters["security_items"] ?? []);
$rightsItems = privacyVisibleRepeaterItems($privacyRepeaters["rights_items"] ?? []);

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
            <li class="breadcrumb-item active current"><?php echo htmlspecialchars($heroTitle !== "" ? $heroTitle : "Privacy", ENT_QUOTES, "UTF-8"); ?></li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <?php if (privacyFieldVisible($privacyFields, "hero_title") && $heroTitle !== ""): ?>
        <h1><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, "UTF-8"); ?></h1>
        <?php else: ?>
        <h1><?php echo $h1TitleEscaped; ?></h1>
        <?php endif; ?>
        <?php if (privacyFieldVisible($privacyFields, "hero_subtitle") && $heroSubtitle !== ""): ?>
        <p><?php echo htmlspecialchars($heroSubtitle, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>
      </div>
    </div><!-- End Page Title -->

    <!-- Privacy Section -->
    <section id="privacy" class="privacy section">

      <div class="container" data-aos="fade-up">
        <!-- Header -->
        <div class="privacy-header" data-aos="fade-up">
          <div class="header-content">
            <?php if (privacyFieldVisible($privacyFields, "effective_date") && $effectiveDate !== ""): ?><div class="last-updated"><?php echo htmlspecialchars($effectiveDate, ENT_QUOTES, "UTF-8"); ?></div><?php endif; ?>
            <?php if (privacyFieldVisible($privacyFields, "policy_title") && $policyTitle !== ""): ?><h1><?php echo htmlspecialchars($policyTitle, ENT_QUOTES, "UTF-8"); ?></h1><?php endif; ?>
            <?php if (privacyFieldVisible($privacyFields, "policy_intro") && $policyIntro !== ""): ?><p class="intro-text"><?php echo htmlspecialchars($policyIntro, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
          </div>
        </div>

        <!-- Main Content -->
        <div class="privacy-content" data-aos="fade-up">
          <!-- Introduction -->
          <div class="content-section">
            <?php if (privacyFieldVisible($privacyFields, "intro_title") && $introTitle !== ""): ?><h2><?php echo htmlspecialchars($introTitle, ENT_QUOTES, "UTF-8"); ?></h2><?php endif; ?>
            <?php if (privacyFieldVisible($privacyFields, "intro_text_1") && $introTextOne !== ""): ?><p><?php echo htmlspecialchars($introTextOne, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
            <?php if (privacyFieldVisible($privacyFields, "intro_text_2") && $introTextTwo !== ""): ?><p><?php echo htmlspecialchars($introTextTwo, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
          </div>

          <!-- Information Collection -->
          <div class="content-section">
            <?php if (privacyFieldVisible($privacyFields, "collection_title") && $collectionTitle !== ""): ?><h2><?php echo htmlspecialchars($collectionTitle, ENT_QUOTES, "UTF-8"); ?></h2><?php endif; ?>
            <?php if (privacyFieldVisible($privacyFields, "collection_text") && $collectionText !== ""): ?><p><?php echo htmlspecialchars($collectionText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>

            <?php if (privacyFieldVisible($privacyFields, "provided_title") && $providedTitle !== ""): ?><h3><?php echo htmlspecialchars($providedTitle, ENT_QUOTES, "UTF-8"); ?></h3><?php endif; ?>
            <?php if (privacyFieldVisible($privacyFields, "provided_text") && $providedText !== ""): ?><p><?php echo htmlspecialchars($providedText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
            <?php if ($providedItems !== []): ?>
            <ul>
              <?php foreach ($providedItems as $item): ?>
                <?php $text = privacyRepeaterField($item["fields"] ?? [], "text"); ?>
              <?php if ($text !== ""): ?><li><?php echo htmlspecialchars($text, ENT_QUOTES, "UTF-8"); ?></li><?php endif; ?>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <?php if (privacyFieldVisible($privacyFields, "automatic_title") && $automaticTitle !== ""): ?><h3><?php echo htmlspecialchars($automaticTitle, ENT_QUOTES, "UTF-8"); ?></h3><?php endif; ?>
            <?php if (privacyFieldVisible($privacyFields, "automatic_text") && $automaticText !== ""): ?><p><?php echo htmlspecialchars($automaticText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
            <?php if ($automaticItems !== []): ?>
            <ul>
              <?php foreach ($automaticItems as $item): ?>
                <?php $text = privacyRepeaterField($item["fields"] ?? [], "text"); ?>
              <?php if ($text !== ""): ?><li><?php echo htmlspecialchars($text, ENT_QUOTES, "UTF-8"); ?></li><?php endif; ?>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
          </div>

          <!-- Use of Information -->
          <div class="content-section">
            <?php if (privacyFieldVisible($privacyFields, "use_title") && $useTitle !== ""): ?><h2><?php echo htmlspecialchars($useTitle, ENT_QUOTES, "UTF-8"); ?></h2><?php endif; ?>
            <?php if (privacyFieldVisible($privacyFields, "use_text") && $useText !== ""): ?><p><?php echo htmlspecialchars($useText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
            <?php if ($useItems !== []): ?>
            <ul>
              <?php foreach ($useItems as $item): ?>
                <?php $text = privacyRepeaterField($item["fields"] ?? [], "text"); ?>
              <?php if ($text !== ""): ?><li><?php echo htmlspecialchars($text, ENT_QUOTES, "UTF-8"); ?></li><?php endif; ?>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
          </div>

          <!-- Information Sharing -->
          <div class="content-section">
            <?php if (privacyFieldVisible($privacyFields, "sharing_title") && $sharingTitle !== ""): ?><h2><?php echo htmlspecialchars($sharingTitle, ENT_QUOTES, "UTF-8"); ?></h2><?php endif; ?>
            <?php if (privacyFieldVisible($privacyFields, "sharing_text") && $sharingText !== ""): ?><p><?php echo htmlspecialchars($sharingText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>

            <?php if (privacyFieldVisible($privacyFields, "consent_title") && $consentTitle !== ""): ?><h3><?php echo htmlspecialchars($consentTitle, ENT_QUOTES, "UTF-8"); ?></h3><?php endif; ?>
            <?php if (privacyFieldVisible($privacyFields, "consent_text") && $consentText !== ""): ?><p><?php echo htmlspecialchars($consentText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>

            <?php if (privacyFieldVisible($privacyFields, "legal_title") && $legalTitle !== ""): ?><h3><?php echo htmlspecialchars($legalTitle, ENT_QUOTES, "UTF-8"); ?></h3><?php endif; ?>
            <?php if (privacyFieldVisible($privacyFields, "legal_text") && $legalText !== ""): ?><p><?php echo htmlspecialchars($legalText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
            <?php if ($legalItems !== []): ?>
            <ul>
              <?php foreach ($legalItems as $item): ?>
                <?php $text = privacyRepeaterField($item["fields"] ?? [], "text"); ?>
              <?php if ($text !== ""): ?><li><?php echo htmlspecialchars($text, ENT_QUOTES, "UTF-8"); ?></li><?php endif; ?>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
          </div>

          <!-- Data Security -->
          <div class="content-section">
            <?php if (privacyFieldVisible($privacyFields, "security_title") && $securityTitle !== ""): ?><h2><?php echo htmlspecialchars($securityTitle, ENT_QUOTES, "UTF-8"); ?></h2><?php endif; ?>
            <?php if (privacyFieldVisible($privacyFields, "security_text") && $securityText !== ""): ?><p><?php echo htmlspecialchars($securityText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
            <?php if ($securityItems !== []): ?>
            <ul>
              <?php foreach ($securityItems as $item): ?>
                <?php $text = privacyRepeaterField($item["fields"] ?? [], "text"); ?>
              <?php if ($text !== ""): ?><li><?php echo htmlspecialchars($text, ENT_QUOTES, "UTF-8"); ?></li><?php endif; ?>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
          </div>

          <!-- Your Rights -->
          <div class="content-section">
            <?php if (privacyFieldVisible($privacyFields, "rights_title") && $rightsTitle !== ""): ?><h2><?php echo htmlspecialchars($rightsTitle, ENT_QUOTES, "UTF-8"); ?></h2><?php endif; ?>
            <?php if (privacyFieldVisible($privacyFields, "rights_text") && $rightsText !== ""): ?><p><?php echo htmlspecialchars($rightsText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
            <?php if ($rightsItems !== []): ?>
            <ul>
              <?php foreach ($rightsItems as $item): ?>
                <?php $text = privacyRepeaterField($item["fields"] ?? [], "text"); ?>
              <?php if ($text !== ""): ?><li><?php echo htmlspecialchars($text, ENT_QUOTES, "UTF-8"); ?></li><?php endif; ?>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
          </div>

          <!-- Policy Updates -->
          <div class="content-section">
            <?php if (privacyFieldVisible($privacyFields, "updates_title") && $updatesTitle !== ""): ?><h2><?php echo htmlspecialchars($updatesTitle, ENT_QUOTES, "UTF-8"); ?></h2><?php endif; ?>
            <?php if (privacyFieldVisible($privacyFields, "updates_text_1") && $updatesTextOne !== ""): ?><p><?php echo htmlspecialchars($updatesTextOne, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
            <?php if (privacyFieldVisible($privacyFields, "updates_text_2") && $updatesTextTwo !== ""): ?><p><?php echo htmlspecialchars($updatesTextTwo, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
          </div>
        </div>

        <!-- Contact Section -->
        <div class="privacy-contact" data-aos="fade-up">
          <?php if (privacyFieldVisible($privacyFields, "contact_title") && $contactTitle !== ""): ?><h2><?php echo htmlspecialchars($contactTitle, ENT_QUOTES, "UTF-8"); ?></h2><?php endif; ?>
          <?php if (privacyFieldVisible($privacyFields, "contact_text") && $contactText !== ""): ?><p><?php echo htmlspecialchars($contactText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
          <div class="contact-details">
            <?php if (privacyFieldVisible($privacyFields, "contact_email") && $contactEmail !== ""): ?><p><strong><?php echo htmlspecialchars($contactEmailLabel, ENT_QUOTES, "UTF-8"); ?></strong> <?php echo htmlspecialchars($contactEmail, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
            <?php if (privacyFieldVisible($privacyFields, "contact_address") && $contactAddress !== ""): ?><p><strong><?php echo htmlspecialchars($contactAddressLabel, ENT_QUOTES, "UTF-8"); ?></strong> <?php echo htmlspecialchars($contactAddress, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
          </div>
        </div>

      </div>

    </section><!-- /Privacy Section -->

  </main>

<?php require __DIR__ . "/../../includes/footer.php"; ?>
