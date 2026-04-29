<?php
$termsContent = is_array($pageContent ?? null) ? $pageContent : ["simple_fields" => [], "repeaters" => []];
$termsFields = $termsContent["simple_fields"] ?? [];
$termsRepeaters = $termsContent["repeaters"] ?? [];

function termsFieldValue(array $fields, string $fieldKey, string $default = ""): string
{
    return (string) ($fields[$fieldKey]["value"] ?? $default);
}

function termsFieldVisible(array $fields, string $fieldKey, bool $default = true): bool
{
    if (!isset($fields[$fieldKey])) {
        return $default;
    }

    return (bool) ($fields[$fieldKey]["is_visible"] ?? false);
}

function termsVisibleRepeaterItems(array $items): array
{
    return array_values(array_filter($items, static function ($item): bool {
        return (bool) ($item["is_visible"] ?? false);
    }));
}

function termsRepeaterField(array $fields, string $fieldKey, string $default = ""): string
{
    return trim((string) ($fields[$fieldKey] ?? $default));
}

function termsNormalizeCustomHref(string $url): string
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
$heroTitle = termsFieldValue($termsFields, "hero_title", (string) ($page["title"] ?? "Terms"));
$heroSubtitle = termsFieldValue($termsFields, "hero_subtitle", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.");
$lastUpdated = termsFieldValue($termsFields, "last_updated", "Last Updated: February 27, 2025");
$termsTitle = termsFieldValue($termsFields, "terms_title", "Terms of Service");
$termsIntro = termsFieldValue($termsFields, "terms_intro", "Please read these terms of service carefully before using our services");
$agreementTitle = termsFieldValue($termsFields, "agreement_title", "1. Agreement to Terms");
$agreementText = termsFieldValue($termsFields, "agreement_text", "By accessing our website and services, you agree to be bound by these Terms of Service and all applicable laws and regulations. If you do not agree with any of these terms, you are prohibited from using or accessing our services.");
$agreementInfoIcon = termsFieldValue($termsFields, "agreement_info_icon", "bi bi-info-circle");
$agreementInfoText = termsFieldValue($termsFields, "agreement_info_text", "These terms apply to all users, visitors, and others who access or use our services.");
$ipTitle = termsFieldValue($termsFields, "ip_title", "2. Intellectual Property Rights");
$ipText = termsFieldValue($termsFields, "ip_text", "Our service and its original content, features, and functionality are owned by us and are protected by international copyright, trademark, patent, trade secret, and other intellectual property laws.");
$accountsTitle = termsFieldValue($termsFields, "accounts_title", "3. User Accounts");
$accountsText = termsFieldValue($termsFields, "accounts_text", "When you create an account with us, you must provide accurate, complete, and current information. Failure to do so constitutes a breach of the Terms, which may result in immediate termination of your account.");
$alertIcon = termsFieldValue($termsFields, "alert_icon", "bi bi-exclamation-triangle");
$alertTitle = termsFieldValue($termsFields, "alert_title", "Important Notice");
$alertText = termsFieldValue($termsFields, "alert_text", "You are responsible for safeguarding the password and for all activities that occur under your account.");
$prohibitedTitle = termsFieldValue($termsFields, "prohibited_title", "4. Prohibited Activities");
$prohibitedText = termsFieldValue($termsFields, "prohibited_text", "You may not access or use the Service for any purpose other than that for which we make it available.");
$disclaimerTitle = termsFieldValue($termsFields, "disclaimer_title", "5. Disclaimers");
$disclaimerText = termsFieldValue($termsFields, "disclaimer_text", 'Your use of our service is at your sole risk. The service is provided "AS IS" and "AS AVAILABLE" without warranties of any kind, whether express or implied.');
$disclaimerIntro = termsFieldValue($termsFields, "disclaimer_intro", "We do not guarantee that:");
$limitationTitle = termsFieldValue($termsFields, "limitation_title", "6. Limitation of Liability");
$limitationText = termsFieldValue($termsFields, "limitation_text", "In no event shall we be liable for any indirect, punitive, incidental, special, consequential, or exemplary damages arising out of or in connection with your use of the service.");
$indemnificationTitle = termsFieldValue($termsFields, "indemnification_title", "7. Indemnification");
$indemnificationText = termsFieldValue($termsFields, "indemnification_text", "You agree to defend, indemnify, and hold us harmless from and against any claims, liabilities, damages, losses, and expenses arising out of your use of the service.");
$terminationTitle = termsFieldValue($termsFields, "termination_title", "8. Termination");
$terminationText = termsFieldValue($termsFields, "termination_text", "We may terminate or suspend your account immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms.");
$lawTitle = termsFieldValue($termsFields, "law_title", "9. Governing Law");
$lawText = termsFieldValue($termsFields, "law_text", "These Terms shall be governed by and construed in accordance with the laws of [Your Country], without regard to its conflict of law provisions.");
$changesTitle = termsFieldValue($termsFields, "changes_title", "10. Changes to Terms");
$changesText = termsFieldValue($termsFields, "changes_text", "We reserve the right to modify or replace these Terms at any time. We will provide notice of any changes by posting the new Terms on this page.");
$noticeIcon = termsFieldValue($termsFields, "notice_icon", "bi bi-bell");
$noticeText = termsFieldValue($termsFields, "notice_text", "By continuing to access or use our service after those revisions become effective, you agree to be bound by the revised terms.");
$contactIcon = termsFieldValue($termsFields, "contact_icon", "bi bi-envelope");
$contactTitle = termsFieldValue($termsFields, "contact_title", "Questions About Terms?");
$contactText = termsFieldValue($termsFields, "contact_text", "If you have any questions about these Terms, please contact us.");
$contactLinkText = termsFieldValue($termsFields, "contact_link_text", "Contact Support");
$contactLinkUrl = termsNormalizeCustomHref(termsFieldValue($termsFields, "contact_link_url", "#"));
$ipItems = termsVisibleRepeaterItems($termsRepeaters["ip_items"] ?? []);
$prohibitedItems = termsVisibleRepeaterItems($termsRepeaters["prohibited_items"] ?? []);
$disclaimerItems = termsVisibleRepeaterItems($termsRepeaters["disclaimer_items"] ?? []);

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
            <li class="breadcrumb-item active current"><?php echo htmlspecialchars($heroTitle !== "" ? $heroTitle : "Terms", ENT_QUOTES, "UTF-8"); ?></li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <?php if (termsFieldVisible($termsFields, "hero_title") && $heroTitle !== ""): ?>
        <h1><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, "UTF-8"); ?></h1>
        <?php else: ?>
        <h1><?php echo $h1TitleEscaped; ?></h1>
        <?php endif; ?>
        <?php if (termsFieldVisible($termsFields, "hero_subtitle") && $heroSubtitle !== ""): ?>
        <p><?php echo htmlspecialchars($heroSubtitle, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>
      </div>
    </div><!-- End Page Title -->

    <!-- Terms Of Service Section -->
    <section id="terms-of-service" class="terms-of-service section">

      <div class="container" data-aos="fade-up">
        <!-- Page Header -->
        <div class="tos-header text-center" data-aos="fade-up">
          <?php if (termsFieldVisible($termsFields, "last_updated") && $lastUpdated !== ""): ?><span class="last-updated"><?php echo htmlspecialchars($lastUpdated, ENT_QUOTES, "UTF-8"); ?></span><?php endif; ?>
          <?php if (termsFieldVisible($termsFields, "terms_title") && $termsTitle !== ""): ?><h2><?php echo htmlspecialchars($termsTitle, ENT_QUOTES, "UTF-8"); ?></h2><?php endif; ?>
          <?php if (termsFieldVisible($termsFields, "terms_intro") && $termsIntro !== ""): ?><p><?php echo htmlspecialchars($termsIntro, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
        </div>

        <!-- Content -->
        <div class="tos-content" data-aos="fade-up" data-aos-delay="200">
          <!-- Agreement Section -->
          <div id="agreement" class="content-section">
            <?php if (termsFieldVisible($termsFields, "agreement_title") && $agreementTitle !== ""): ?><h3><?php echo htmlspecialchars($agreementTitle, ENT_QUOTES, "UTF-8"); ?></h3><?php endif; ?>
            <?php if (termsFieldVisible($termsFields, "agreement_text") && $agreementText !== ""): ?><p><?php echo htmlspecialchars($agreementText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
            <div class="info-box">
              <?php if ($agreementInfoIcon !== ""): ?><i class="<?php echo htmlspecialchars($agreementInfoIcon, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?>
              <?php if (termsFieldVisible($termsFields, "agreement_info_text") && $agreementInfoText !== ""): ?><p><?php echo htmlspecialchars($agreementInfoText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
            </div>
          </div>

          <!-- Intellectual Property -->
          <div id="intellectual-property" class="content-section">
            <?php if (termsFieldVisible($termsFields, "ip_title") && $ipTitle !== ""): ?><h3><?php echo htmlspecialchars($ipTitle, ENT_QUOTES, "UTF-8"); ?></h3><?php endif; ?>
            <?php if (termsFieldVisible($termsFields, "ip_text") && $ipText !== ""): ?><p><?php echo htmlspecialchars($ipText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
            <?php if ($ipItems !== []): ?>
            <ul class="list-items">
              <?php foreach ($ipItems as $item): ?>
                <?php $text = termsRepeaterField($item["fields"] ?? [], "text"); ?>
              <?php if ($text !== ""): ?><li><?php echo htmlspecialchars($text, ENT_QUOTES, "UTF-8"); ?></li><?php endif; ?>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>
          </div>

          <!-- User Accounts -->
          <div id="user-accounts" class="content-section">
            <?php if (termsFieldVisible($termsFields, "accounts_title") && $accountsTitle !== ""): ?><h3><?php echo htmlspecialchars($accountsTitle, ENT_QUOTES, "UTF-8"); ?></h3><?php endif; ?>
            <?php if (termsFieldVisible($termsFields, "accounts_text") && $accountsText !== ""): ?><p><?php echo htmlspecialchars($accountsText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
            <div class="alert-box">
              <?php if ($alertIcon !== ""): ?><i class="<?php echo htmlspecialchars($alertIcon, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?>
              <div class="alert-content">
                <?php if (termsFieldVisible($termsFields, "alert_title") && $alertTitle !== ""): ?><h5><?php echo htmlspecialchars($alertTitle, ENT_QUOTES, "UTF-8"); ?></h5><?php endif; ?>
                <?php if (termsFieldVisible($termsFields, "alert_text") && $alertText !== ""): ?><p><?php echo htmlspecialchars($alertText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Prohibited Activities -->
          <div id="prohibited" class="content-section">
            <?php if (termsFieldVisible($termsFields, "prohibited_title") && $prohibitedTitle !== ""): ?><h3><?php echo htmlspecialchars($prohibitedTitle, ENT_QUOTES, "UTF-8"); ?></h3><?php endif; ?>
            <?php if (termsFieldVisible($termsFields, "prohibited_text") && $prohibitedText !== ""): ?><p><?php echo htmlspecialchars($prohibitedText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
            <?php if ($prohibitedItems !== []): ?>
            <div class="prohibited-list">
              <?php foreach ($prohibitedItems as $item): ?>
                <?php
                $fields = $item["fields"] ?? [];
                $iconClass = termsRepeaterField($fields, "icon_class", "bi bi-x-circle");
                $text = termsRepeaterField($fields, "text");
                ?>
              <div class="prohibited-item">
                <?php if ($iconClass !== ""): ?><i class="<?php echo htmlspecialchars($iconClass, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?>
                <?php if ($text !== ""): ?><span><?php echo htmlspecialchars($text, ENT_QUOTES, "UTF-8"); ?></span><?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- Disclaimers -->
          <div id="disclaimer" class="content-section">
            <?php if (termsFieldVisible($termsFields, "disclaimer_title") && $disclaimerTitle !== ""): ?><h3><?php echo htmlspecialchars($disclaimerTitle, ENT_QUOTES, "UTF-8"); ?></h3><?php endif; ?>
            <?php if (termsFieldVisible($termsFields, "disclaimer_text") && $disclaimerText !== ""): ?><p><?php echo htmlspecialchars($disclaimerText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
            <div class="disclaimer-box">
              <?php if (termsFieldVisible($termsFields, "disclaimer_intro") && $disclaimerIntro !== ""): ?><p><?php echo htmlspecialchars($disclaimerIntro, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
              <?php if ($disclaimerItems !== []): ?>
              <ul>
                <?php foreach ($disclaimerItems as $item): ?>
                  <?php $text = termsRepeaterField($item["fields"] ?? [], "text"); ?>
                <?php if ($text !== ""): ?><li><?php echo htmlspecialchars($text, ENT_QUOTES, "UTF-8"); ?></li><?php endif; ?>
                <?php endforeach; ?>
              </ul>
              <?php endif; ?>
            </div>
          </div>

          <!-- Limitation of Liability -->
          <div id="limitation" class="content-section">
            <?php if (termsFieldVisible($termsFields, "limitation_title") && $limitationTitle !== ""): ?><h3><?php echo htmlspecialchars($limitationTitle, ENT_QUOTES, "UTF-8"); ?></h3><?php endif; ?>
            <?php if (termsFieldVisible($termsFields, "limitation_text") && $limitationText !== ""): ?><p><?php echo htmlspecialchars($limitationText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
          </div>

          <!-- Indemnification -->
          <div id="indemnification" class="content-section">
            <?php if (termsFieldVisible($termsFields, "indemnification_title") && $indemnificationTitle !== ""): ?><h3><?php echo htmlspecialchars($indemnificationTitle, ENT_QUOTES, "UTF-8"); ?></h3><?php endif; ?>
            <?php if (termsFieldVisible($termsFields, "indemnification_text") && $indemnificationText !== ""): ?><p><?php echo htmlspecialchars($indemnificationText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
          </div>

          <!-- Termination -->
          <div id="termination" class="content-section">
            <?php if (termsFieldVisible($termsFields, "termination_title") && $terminationTitle !== ""): ?><h3><?php echo htmlspecialchars($terminationTitle, ENT_QUOTES, "UTF-8"); ?></h3><?php endif; ?>
            <?php if (termsFieldVisible($termsFields, "termination_text") && $terminationText !== ""): ?><p><?php echo htmlspecialchars($terminationText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
          </div>

          <!-- Governing Law -->
          <div id="governing-law" class="content-section">
            <?php if (termsFieldVisible($termsFields, "law_title") && $lawTitle !== ""): ?><h3><?php echo htmlspecialchars($lawTitle, ENT_QUOTES, "UTF-8"); ?></h3><?php endif; ?>
            <?php if (termsFieldVisible($termsFields, "law_text") && $lawText !== ""): ?><p><?php echo htmlspecialchars($lawText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
          </div>

          <!-- Changes -->
          <div id="changes" class="content-section">
            <?php if (termsFieldVisible($termsFields, "changes_title") && $changesTitle !== ""): ?><h3><?php echo htmlspecialchars($changesTitle, ENT_QUOTES, "UTF-8"); ?></h3><?php endif; ?>
            <?php if (termsFieldVisible($termsFields, "changes_text") && $changesText !== ""): ?><p><?php echo htmlspecialchars($changesText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
            <div class="notice-box">
              <?php if ($noticeIcon !== ""): ?><i class="<?php echo htmlspecialchars($noticeIcon, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?>
              <?php if (termsFieldVisible($termsFields, "notice_text") && $noticeText !== ""): ?><p><?php echo htmlspecialchars($noticeText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Contact Section -->
        <div class="tos-contact" data-aos="fade-up" data-aos-delay="300">
          <div class="contact-box">
            <div class="contact-icon">
              <?php if (termsFieldVisible($termsFields, "contact_icon") && $contactIcon !== ""): ?><i class="<?php echo htmlspecialchars($contactIcon, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?>
            </div>
            <div class="contact-content">
              <?php if (termsFieldVisible($termsFields, "contact_title") && $contactTitle !== ""): ?><h4><?php echo htmlspecialchars($contactTitle, ENT_QUOTES, "UTF-8"); ?></h4><?php endif; ?>
              <?php if (termsFieldVisible($termsFields, "contact_text") && $contactText !== ""): ?><p><?php echo htmlspecialchars($contactText, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
              <?php if (termsFieldVisible($termsFields, "contact_link_text") && $contactLinkText !== ""): ?><a href="<?php echo htmlspecialchars($contactLinkUrl !== "" ? $contactLinkUrl : "#", ENT_QUOTES, "UTF-8"); ?>" class="contact-link"><?php echo htmlspecialchars($contactLinkText, ENT_QUOTES, "UTF-8"); ?></a><?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </section><!-- /Terms Of Service Section -->

  </main>

<?php require __DIR__ . "/../../includes/footer.php"; ?>
