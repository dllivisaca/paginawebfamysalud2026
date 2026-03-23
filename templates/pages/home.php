<?php
$homeContent = is_array($pageContent ?? null) ? $pageContent : ["simple_fields" => [], "repeaters" => []];
$homeFields = $homeContent["simple_fields"] ?? [];
$homeRepeaters = $homeContent["repeaters"] ?? [];

function homeFieldValue(array $fields, string $fieldKey, string $default = ""): string
{
    return (string) ($fields[$fieldKey]["value"] ?? $default);
}

function homeFieldVisible(array $fields, string $fieldKey, bool $default = true): bool
{
    if (!isset($fields[$fieldKey])) {
        return $default;
    }

    return (bool) ($fields[$fieldKey]["is_visible"] ?? false);
}

function homeVisibleRepeaterItems(array $items): array
{
    return array_values(array_filter($items, static function ($item): bool {
        return (bool) ($item["is_visible"] ?? false);
    }));
}

$heroBadge = homeFieldValue($homeFields, "hero_badge", "Leading Healthcare Specialists");
$heroTitle = homeFieldValue($homeFields, "hero_title", "Advanced Medical Care for Your Family's Health");
$heroText = homeFieldValue($homeFields, "hero_text", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.");
$heroImage = homeFieldValue($homeFields, "hero_image", "assets/img/health/showcase-1.webp");
$heroImageAlt = homeFieldValue($homeFields, "hero_image_alt", "Advanced Healthcare");
$heroPrimaryCtaText = homeFieldValue($homeFields, "hero_primary_cta_text", "Book Appointment");
$heroPrimaryCtaUrl = resolvePageContentLinkHref($conn, $homeFields, "hero_primary_cta", "appointment.php");
$heroSecondaryCtaText = homeFieldValue($homeFields, "hero_secondary_cta_text", "Explore Services");
$heroSecondaryCtaUrl = resolvePageContentLinkHref($conn, $homeFields, "hero_secondary_cta", "services.php");
$heroEmergencyLabel = homeFieldValue($homeFields, "hero_emergency_label", "Emergency Line");
$heroEmergencyValue = homeFieldValue($homeFields, "hero_emergency_value", "+1 (555) 987-6543");
$heroHoursLabel = homeFieldValue($homeFields, "hero_hours_label", "Working Hours");
$heroHoursValue = homeFieldValue($homeFields, "hero_hours_value", "Mon-Fri: 8AM-8PM");
$heroFeatures = homeVisibleRepeaterItems($homeRepeaters["hero_features"] ?? []);

$homeAboutImage = homeFieldValue($homeFields, "home_about_image", "assets/img/health/facilities-1.webp");
$homeAboutImageAlt = homeFieldValue($homeFields, "home_about_image_alt", "Modern Healthcare Facility");
$homeAboutExperienceYears = homeFieldValue($homeFields, "home_about_experience_years", "25+");
$homeAboutExperienceText = homeFieldValue($homeFields, "home_about_experience_text", "Years of Excellence");
$homeAboutTitle = homeFieldValue($homeFields, "home_about_title", "Committed to Exceptional Patient Care");
$homeAboutLead = homeFieldValue($homeFields, "home_about_lead", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.");
$homeAboutText = homeFieldValue($homeFields, "home_about_text", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin consequat magna eu accumsan mattis. Duis non augue in tortor facilisis tincidunt ac sit amet sapien. Suspendisse id risus non nisi sodales condimentum.");
$homeAboutPrimaryText = homeFieldValue($homeFields, "home_about_primary_cta_text", "Learn More About Us");
$homeAboutPrimaryUrl = resolvePageContentLinkHref($conn, $homeFields, "home_about_primary_cta", "about.php");
$homeAboutSecondaryText = homeFieldValue($homeFields, "home_about_secondary_cta_text", "Meet Our Team");
$homeAboutSecondaryUrl = resolvePageContentLinkHref($conn, $homeFields, "home_about_secondary_cta", "#");
$homeAboutCertificationsTitle = homeFieldValue($homeFields, "home_about_certifications_title", "Our Accreditations");
$homeAboutFeatures = homeVisibleRepeaterItems($homeRepeaters["home_about_features"] ?? []);
$homeCertifications = homeVisibleRepeaterItems($homeRepeaters["home_certifications"] ?? []);

$featuredDepartmentsTitle = homeFieldValue($homeFields, "featured_departments_title", "Featured Departments");
$featuredDepartmentsText = homeFieldValue($homeFields, "featured_departments_text", "Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit");
$featuredDepartments = homeVisibleRepeaterItems($homeRepeaters["featured_departments"] ?? []);

$featuredServicesTitle = homeFieldValue($homeFields, "featured_services_title", "Featured Services");
$featuredServicesText = homeFieldValue($homeFields, "featured_services_text", "Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit");
$featuredServices = homeVisibleRepeaterItems($homeRepeaters["featured_services"] ?? []);

$findDoctorTitle = homeFieldValue($homeFields, "find_doctor_title", "Find A Doctor");
$findDoctorText = homeFieldValue($homeFields, "find_doctor_text", "Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit");
$doctorSearchPlaceholder = homeFieldValue($homeFields, "doctor_search_placeholder", "Doctor name or keyword");
$doctorSpecialtyPlaceholder = homeFieldValue($homeFields, "doctor_specialty_placeholder", "Select Specialty");
$doctorSearchButtonText = homeFieldValue($homeFields, "doctor_search_button_text", "Search Doctor");
$featuredDoctors = homeVisibleRepeaterItems($homeRepeaters["featured_doctors"] ?? []);

$ctaTitle = homeFieldValue($homeFields, "cta_title", "Your Health is Our Priority");
$ctaText = homeFieldValue($homeFields, "cta_text", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.");
$ctaPrimaryText = homeFieldValue($homeFields, "cta_primary_text", "Book Appointment");
$ctaPrimaryUrl = resolvePageContentLinkHref($conn, $homeFields, "cta_primary", "appointment.php");
$ctaSecondaryText = homeFieldValue($homeFields, "cta_secondary_text", "Find a Doctor");
$ctaSecondaryUrl = resolvePageContentLinkHref($conn, $homeFields, "cta_secondary", "doctors.php");
$ctaEmergencyTitle = homeFieldValue($homeFields, "cta_emergency_title", "Medical Emergency?");
$ctaEmergencyText = homeFieldValue($homeFields, "cta_emergency_text", "Call our 24/7 emergency hotline for immediate assistance");
$ctaEmergencyButtonText = homeFieldValue($homeFields, "cta_emergency_button_text", "Call (555) 123-4567");
$ctaEmergencyButtonUrl = homeFieldValue($homeFields, "cta_emergency_button_url", "tel:911");
$ctaFeatures = homeVisibleRepeaterItems($homeRepeaters["cta_features"] ?? []);

$emergencyInfoTitle = homeFieldValue($homeFields, "emergency_info_title", "Emergency Info");
$emergencyInfoText = homeFieldValue($homeFields, "emergency_info_text", "Necessitatibus eius consequatur ex aliquid fuga eum quidem sint consectetur velit");
$emergencyBannerTitle = homeFieldValue($homeFields, "emergency_banner_title", "Medical Emergency?");
$emergencyBannerText = homeFieldValue($homeFields, "emergency_banner_text", "If you are experiencing a life-threatening emergency, call 911 immediately or go to your nearest emergency room.");
$emergencyBannerButtonText = homeFieldValue($homeFields, "emergency_banner_button_text", "Call 911");
$emergencyBannerButtonUrl = homeFieldValue($homeFields, "emergency_banner_button_url", "tel:911");
$quickActionsTitle = homeFieldValue($homeFields, "quick_actions_title", "Quick Actions");
$emergencyTipsTitle = homeFieldValue($homeFields, "emergency_tips_title", "When to Seek Emergency Care");
$emergencyContacts = homeVisibleRepeaterItems($homeRepeaters["emergency_contacts"] ?? []);
$quickActions = homeVisibleRepeaterItems($homeRepeaters["quick_actions"] ?? []);
$emergencyTips = homeVisibleRepeaterItems($homeRepeaters["emergency_tips"] ?? []);
$emergencyTipsLeft = array_slice($emergencyTips, 0, (int) ceil(count($emergencyTips) / 2));
$emergencyTipsRight = array_slice($emergencyTips, (int) ceil(count($emergencyTips) / 2));

require __DIR__ . "/../../includes/header.php";
?>

<main class="main">
  <section id="hero" class="hero section dark-background">
    <div class="container-fluid p-0">
      <div class="hero-wrapper">
        <div class="hero-image">
          <?php if (homeFieldVisible($homeFields, "hero_image") && $heroImage !== ""): ?>
            <img src="<?php echo htmlspecialchars($heroImage, ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars($heroImageAlt, ENT_QUOTES, "UTF-8"); ?>" class="img-fluid">
          <?php endif; ?>
        </div>
        <div class="hero-content">
          <div class="container">
            <div class="row">
              <div class="col-lg-7 col-md-10" data-aos="fade-right" data-aos-delay="100">
                <div class="content-box">
                  <?php if (homeFieldVisible($homeFields, "hero_badge") && $heroBadge !== ""): ?><span class="badge-accent" data-aos="fade-up" data-aos-delay="150"><?php echo htmlspecialchars($heroBadge, ENT_QUOTES, "UTF-8"); ?></span><?php endif; ?>
                  <?php if (homeFieldVisible($homeFields, "hero_title") && $heroTitle !== ""): ?><h1 data-aos="fade-up" data-aos-delay="200"><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, "UTF-8"); ?></h1><?php endif; ?>
                  <?php if (homeFieldVisible($homeFields, "hero_text") && $heroText !== ""): ?><p data-aos="fade-up" data-aos-delay="250"><?php echo nl2br(htmlspecialchars($heroText, ENT_QUOTES, "UTF-8")); ?></p><?php endif; ?>
                  <div class="cta-group" data-aos="fade-up" data-aos-delay="300">
                    <?php if (homeFieldVisible($homeFields, "hero_primary_cta_text") && $heroPrimaryCtaText !== ""): ?><a href="<?php echo htmlspecialchars($heroPrimaryCtaUrl, ENT_QUOTES, "UTF-8"); ?>" class="btn btn-primary"><?php echo htmlspecialchars($heroPrimaryCtaText, ENT_QUOTES, "UTF-8"); ?></a><?php endif; ?>
                    <?php if (homeFieldVisible($homeFields, "hero_secondary_cta_text") && $heroSecondaryCtaText !== ""): ?><a href="<?php echo htmlspecialchars($heroSecondaryCtaUrl, ENT_QUOTES, "UTF-8"); ?>" class="btn btn-outline"><?php echo htmlspecialchars($heroSecondaryCtaText, ENT_QUOTES, "UTF-8"); ?></a><?php endif; ?>
                  </div>
                  <div class="info-badges" data-aos="fade-up" data-aos-delay="350">
                    <?php if ($heroEmergencyLabel !== "" || $heroEmergencyValue !== ""): ?>
                      <div class="badge-item"><i class="bi bi-telephone-fill"></i><div class="badge-content"><span><?php echo htmlspecialchars($heroEmergencyLabel, ENT_QUOTES, "UTF-8"); ?></span><strong><?php echo htmlspecialchars($heroEmergencyValue, ENT_QUOTES, "UTF-8"); ?></strong></div></div>
                    <?php endif; ?>
                    <?php if ($heroHoursLabel !== "" || $heroHoursValue !== ""): ?>
                      <div class="badge-item"><i class="bi bi-clock-fill"></i><div class="badge-content"><span><?php echo htmlspecialchars($heroHoursLabel, ENT_QUOTES, "UTF-8"); ?></span><strong><?php echo htmlspecialchars($heroHoursValue, ENT_QUOTES, "UTF-8"); ?></strong></div></div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
            <?php if ($heroFeatures !== []): ?>
              <div class="features-wrapper"><div class="row gy-4">
                <?php foreach ($heroFeatures as $index => $featureItem): $featureFields = $featureItem["fields"] ?? []; ?>
                  <div class="col-lg-4"><div class="feature-item" data-aos="fade-up" data-aos-delay="<?php echo 450 + ($index * 50); ?>"><div class="feature-icon"><i class="<?php echo htmlspecialchars((string) ($featureFields["icon_class"] ?? ""), ENT_QUOTES, "UTF-8"); ?>"></i></div><div class="feature-text"><h3><?php echo htmlspecialchars((string) ($featureFields["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></h3><p><?php echo htmlspecialchars((string) ($featureFields["text"] ?? ""), ENT_QUOTES, "UTF-8"); ?></p></div></div></div>
                <?php endforeach; ?>
              </div></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="home-about" class="home-about section">
    <div class="container" data-aos="fade-up" data-aos-delay="100">
      <div class="row gy-5 align-items-center">
        <div class="col-lg-6" data-aos="fade-right" data-aos-delay="200">
          <div class="about-image">
            <?php if (homeFieldVisible($homeFields, "home_about_image") && $homeAboutImage !== ""): ?><img src="<?php echo htmlspecialchars($homeAboutImage, ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars($homeAboutImageAlt, ENT_QUOTES, "UTF-8"); ?>" class="img-fluid rounded-3 mb-4"><?php endif; ?>
            <?php if ((homeFieldVisible($homeFields, "home_about_experience_years") && $homeAboutExperienceYears !== "") || (homeFieldVisible($homeFields, "home_about_experience_text") && $homeAboutExperienceText !== "")): ?><div class="experience-badge"><?php if (homeFieldVisible($homeFields, "home_about_experience_years") && $homeAboutExperienceYears !== ""): ?><span class="years"><?php echo htmlspecialchars($homeAboutExperienceYears, ENT_QUOTES, "UTF-8"); ?></span><?php endif; ?><?php if (homeFieldVisible($homeFields, "home_about_experience_text") && $homeAboutExperienceText !== ""): ?><span class="text"><?php echo htmlspecialchars($homeAboutExperienceText, ENT_QUOTES, "UTF-8"); ?></span><?php endif; ?></div><?php endif; ?>
          </div>
        </div>
        <div class="col-lg-6" data-aos="fade-left" data-aos-delay="300">
          <div class="about-content">
            <?php if (homeFieldVisible($homeFields, "home_about_title") && $homeAboutTitle !== ""): ?><h2><?php echo htmlspecialchars($homeAboutTitle, ENT_QUOTES, "UTF-8"); ?></h2><?php endif; ?>
            <?php if (homeFieldVisible($homeFields, "home_about_lead") && $homeAboutLead !== ""): ?><p class="lead"><?php echo nl2br(htmlspecialchars($homeAboutLead, ENT_QUOTES, "UTF-8")); ?></p><?php endif; ?>
            <?php if (homeFieldVisible($homeFields, "home_about_text") && $homeAboutText !== ""): ?><p><?php echo nl2br(htmlspecialchars($homeAboutText, ENT_QUOTES, "UTF-8")); ?></p><?php endif; ?>
            <?php if ($homeAboutFeatures !== []): ?><div class="row g-4 mt-4"><?php foreach ($homeAboutFeatures as $index => $featureItem): $featureFields = $featureItem["fields"] ?? []; ?><div class="col-md-6" data-aos="fade-up" data-aos-delay="<?php echo 400 + ($index * 100); ?>"><div class="feature-item"><div class="icon"><i class="<?php echo htmlspecialchars((string) ($featureFields["icon_class"] ?? ""), ENT_QUOTES, "UTF-8"); ?>"></i></div><h4><?php echo htmlspecialchars((string) ($featureFields["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></h4><p><?php echo htmlspecialchars((string) ($featureFields["text"] ?? ""), ENT_QUOTES, "UTF-8"); ?></p></div></div><?php endforeach; ?></div><?php endif; ?>
            <div class="cta-wrapper mt-4">
              <?php if (homeFieldVisible($homeFields, "home_about_primary_cta_text") && $homeAboutPrimaryText !== ""): ?><a href="<?php echo htmlspecialchars($homeAboutPrimaryUrl, ENT_QUOTES, "UTF-8"); ?>" class="btn btn-primary"><?php echo htmlspecialchars($homeAboutPrimaryText, ENT_QUOTES, "UTF-8"); ?></a><?php endif; ?>
              <?php if (homeFieldVisible($homeFields, "home_about_secondary_cta_text") && $homeAboutSecondaryText !== ""): ?><a href="<?php echo htmlspecialchars($homeAboutSecondaryUrl, ENT_QUOTES, "UTF-8"); ?>" class="btn btn-outline"><?php echo htmlspecialchars($homeAboutSecondaryText, ENT_QUOTES, "UTF-8"); ?></a><?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php if ($homeAboutCertificationsTitle !== "" || $homeCertifications !== []): ?><div class="row mt-5 pt-4 certifications-row" data-aos="fade-up" data-aos-delay="600"><div class="col-12 text-center mb-4"><?php if ($homeAboutCertificationsTitle !== ""): ?><h4 class="certification-title"><?php echo htmlspecialchars($homeAboutCertificationsTitle, ENT_QUOTES, "UTF-8"); ?></h4><?php endif; ?></div><div class="col-12"><div class="certifications"><?php foreach ($homeCertifications as $index => $certificationItem): $certFields = $certificationItem["fields"] ?? []; ?><div class="certification-item" data-aos="zoom-in" data-aos-delay="<?php echo 700 + ($index * 100); ?>"><img src="<?php echo htmlspecialchars((string) ($certFields["image"] ?? ""), ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars((string) ($certFields["alt"] ?? ""), ENT_QUOTES, "UTF-8"); ?>"></div><?php endforeach; ?></div></div></div><?php endif; ?>
    </div>
  </section>

  <section id="featured-departments" class="featured-departments section">
    <div class="container section-title" data-aos="fade-up"><?php if ($featuredDepartmentsTitle !== ""): ?><h2><?php echo htmlspecialchars($featuredDepartmentsTitle, ENT_QUOTES, "UTF-8"); ?></h2><?php endif; ?><?php if ($featuredDepartmentsText !== ""): ?><p><?php echo nl2br(htmlspecialchars($featuredDepartmentsText, ENT_QUOTES, "UTF-8")); ?></p><?php endif; ?></div>
    <div class="container" data-aos="fade-up" data-aos-delay="100"><div class="row gy-4"><?php foreach ($featuredDepartments as $index => $departmentItem): $departmentFields = $departmentItem["fields"] ?? []; ?><div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo 100 + (($index % 3) * 100); ?>"><div class="department-card"><div class="department-image"><img src="<?php echo htmlspecialchars((string) ($departmentFields["image"] ?? ""), ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars((string) ($departmentFields["alt"] ?? ""), ENT_QUOTES, "UTF-8"); ?>" class="img-fluid"></div><div class="department-content"><div class="department-icon"><i class="<?php echo htmlspecialchars((string) ($departmentFields["icon_class"] ?? ""), ENT_QUOTES, "UTF-8"); ?>"></i></div><h3><?php echo htmlspecialchars((string) ($departmentFields["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></h3><p><?php echo htmlspecialchars((string) ($departmentFields["text"] ?? ""), ENT_QUOTES, "UTF-8"); ?></p><a href="<?php echo htmlspecialchars((string) ($departmentFields["link_url"] ?? "#"), ENT_QUOTES, "UTF-8"); ?>" class="btn-learn-more"><span><?php echo htmlspecialchars((string) ($departmentFields["link_text"] ?? ""), ENT_QUOTES, "UTF-8"); ?></span><i class="fas fa-arrow-right"></i></a></div></div></div><?php endforeach; ?></div></div>
  </section>

  <section id="featured-services" class="featured-services section light-background">
    <div class="container section-title" data-aos="fade-up"><?php if ($featuredServicesTitle !== ""): ?><h2><?php echo htmlspecialchars($featuredServicesTitle, ENT_QUOTES, "UTF-8"); ?></h2><?php endif; ?><?php if ($featuredServicesText !== ""): ?><p><?php echo nl2br(htmlspecialchars($featuredServicesText, ENT_QUOTES, "UTF-8")); ?></p><?php endif; ?></div>
    <div class="container" data-aos="fade-up" data-aos-delay="100"><div class="row gy-4"><?php foreach ($featuredServices as $index => $serviceItem): $serviceFields = $serviceItem["fields"] ?? []; ?><div class="col-lg-6" data-aos="fade-up" data-aos-delay="<?php echo 200 + ($index * 100); ?>"><div class="service-card"><div class="service-icon"><i class="<?php echo htmlspecialchars((string) ($serviceFields["icon_class"] ?? ""), ENT_QUOTES, "UTF-8"); ?>"></i></div><div class="service-content"><h3><?php echo htmlspecialchars((string) ($serviceFields["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></h3><p><?php echo htmlspecialchars((string) ($serviceFields["text"] ?? ""), ENT_QUOTES, "UTF-8"); ?></p><ul class="service-features"><?php foreach (["feature_1", "feature_2", "feature_3"] as $featureKey): $featureValue = (string) ($serviceFields[$featureKey] ?? ""); if ($featureValue === "") continue; ?><li><i class="fas fa-check-circle"></i><?php echo htmlspecialchars($featureValue, ENT_QUOTES, "UTF-8"); ?></li><?php endforeach; ?></ul><a href="<?php echo htmlspecialchars((string) ($serviceFields["link_url"] ?? "#"), ENT_QUOTES, "UTF-8"); ?>" class="service-btn"><?php echo htmlspecialchars((string) ($serviceFields["link_text"] ?? ""), ENT_QUOTES, "UTF-8"); ?><i class="fas fa-arrow-right"></i></a></div></div></div><?php endforeach; ?></div></div>
  </section>

  <section id="find-a-doctor" class="find-a-doctor section">
    <div class="container section-title" data-aos="fade-up"><?php if ($findDoctorTitle !== ""): ?><h2><?php echo htmlspecialchars($findDoctorTitle, ENT_QUOTES, "UTF-8"); ?></h2><?php endif; ?><?php if ($findDoctorText !== ""): ?><p><?php echo nl2br(htmlspecialchars($findDoctorText, ENT_QUOTES, "UTF-8")); ?></p><?php endif; ?></div>
    <div class="container" data-aos="fade-up" data-aos-delay="100">
      <div class="row justify-content-center" data-aos="fade-up" data-aos-delay="200"><div class="col-lg-12"><div class="search-container"><form class="search-form" action="forms/doctor-search.php" method="get"><div class="row g-3"><div class="col-md-4"><input type="text" class="form-control" name="doctor_name" placeholder="<?php echo htmlspecialchars($doctorSearchPlaceholder, ENT_QUOTES, "UTF-8"); ?>"></div><div class="col-md-4"><select class="form-select" name="specialty" id="specialty-select"><option value=""><?php echo htmlspecialchars($doctorSpecialtyPlaceholder, ENT_QUOTES, "UTF-8"); ?></option><option value="cardiology">Cardiology</option><option value="neurology">Neurology</option><option value="orthopedics">Orthopedics</option><option value="pediatrics">Pediatrics</option><option value="dermatology">Dermatology</option><option value="oncology">Oncology</option><option value="surgery">Surgery</option><option value="emergency">Emergency Medicine</option></select></div><div class="col-md-4"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-2"></i><?php echo htmlspecialchars($doctorSearchButtonText, ENT_QUOTES, "UTF-8"); ?></button></div></div></form></div></div></div>
      <div class="row" data-aos="fade-up" data-aos-delay="400"><?php foreach ($featuredDoctors as $doctorItem): $doctorFields = $doctorItem["fields"] ?? []; $availabilityClass = trim((string) ($doctorFields["availability_status"] ?? "")); ?><div class="col-lg-4 col-md-6 mb-4"><div class="doctor-card"><div class="doctor-image"><img src="<?php echo htmlspecialchars((string) ($doctorFields["image"] ?? ""), ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars((string) ($doctorFields["alt"] ?? ""), ENT_QUOTES, "UTF-8"); ?>" class="img-fluid"><div class="availability-badge <?php echo htmlspecialchars($availabilityClass, ENT_QUOTES, "UTF-8"); ?>"><?php echo htmlspecialchars((string) ($doctorFields["availability_text"] ?? ""), ENT_QUOTES, "UTF-8"); ?></div></div><div class="doctor-info"><h5><?php echo htmlspecialchars((string) ($doctorFields["name"] ?? ""), ENT_QUOTES, "UTF-8"); ?></h5><p class="specialty"><?php echo htmlspecialchars((string) ($doctorFields["specialty"] ?? ""), ENT_QUOTES, "UTF-8"); ?></p><p class="experience"><?php echo htmlspecialchars((string) ($doctorFields["experience"] ?? ""), ENT_QUOTES, "UTF-8"); ?></p><div class="rating"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><span class="rating-text"><?php echo htmlspecialchars((string) ($doctorFields["rating"] ?? ""), ENT_QUOTES, "UTF-8"); ?></span></div><div class="appointment-actions"><a href="<?php echo htmlspecialchars((string) ($doctorFields["profile_button_url"] ?? "#"), ENT_QUOTES, "UTF-8"); ?>" class="btn btn-outline-primary btn-sm"><?php echo htmlspecialchars((string) ($doctorFields["profile_button_text"] ?? ""), ENT_QUOTES, "UTF-8"); ?></a><a href="<?php echo htmlspecialchars((string) ($doctorFields["appointment_button_url"] ?? "#"), ENT_QUOTES, "UTF-8"); ?>" class="btn btn-primary btn-sm"><?php echo htmlspecialchars((string) ($doctorFields["appointment_button_text"] ?? ""), ENT_QUOTES, "UTF-8"); ?></a></div></div></div></div><?php endforeach; ?></div>
    </div>
  </section>

  <section id="call-to-action" class="call-to-action section">
    <div class="container" data-aos="fade-up" data-aos-delay="100">
      <div class="row justify-content-center"><div class="col-lg-8 text-center"><?php if ($ctaTitle !== ""): ?><h2 data-aos="fade-up" data-aos-delay="200"><?php echo htmlspecialchars($ctaTitle, ENT_QUOTES, "UTF-8"); ?></h2><?php endif; ?><?php if ($ctaText !== ""): ?><p data-aos="fade-up" data-aos-delay="250"><?php echo nl2br(htmlspecialchars($ctaText, ENT_QUOTES, "UTF-8")); ?></p><?php endif; ?><div class="cta-buttons" data-aos="fade-up" data-aos-delay="300"><?php if ($ctaPrimaryText !== ""): ?><a href="<?php echo htmlspecialchars($ctaPrimaryUrl, ENT_QUOTES, "UTF-8"); ?>" class="btn-primary"><?php echo htmlspecialchars($ctaPrimaryText, ENT_QUOTES, "UTF-8"); ?></a><?php endif; ?><?php if ($ctaSecondaryText !== ""): ?><a href="<?php echo htmlspecialchars($ctaSecondaryUrl, ENT_QUOTES, "UTF-8"); ?>" class="btn-secondary"><?php echo htmlspecialchars($ctaSecondaryText, ENT_QUOTES, "UTF-8"); ?></a><?php endif; ?></div></div></div>
      <?php if ($ctaFeatures !== []): ?><div class="row features-row" data-aos="fade-up" data-aos-delay="400"><?php foreach ($ctaFeatures as $featureItem): $featureFields = $featureItem["fields"] ?? []; ?><div class="col-lg-4 col-md-6 mb-4"><div class="feature-card"><div class="icon-wrapper"><i class="<?php echo htmlspecialchars((string) ($featureFields["icon_class"] ?? ""), ENT_QUOTES, "UTF-8"); ?>"></i></div><h5><?php echo htmlspecialchars((string) ($featureFields["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></h5><p><?php echo htmlspecialchars((string) ($featureFields["text"] ?? ""), ENT_QUOTES, "UTF-8"); ?></p><a href="<?php echo htmlspecialchars((string) ($featureFields["link_url"] ?? "#"), ENT_QUOTES, "UTF-8"); ?>" class="feature-link"><span><?php echo htmlspecialchars((string) ($featureFields["link_text"] ?? ""), ENT_QUOTES, "UTF-8"); ?></span><i class="bi bi-arrow-right"></i></a></div></div><?php endforeach; ?></div><?php endif; ?>
      <div class="emergency-alert" data-aos="zoom-in" data-aos-delay="500"><div class="row align-items-center"><div class="col-lg-8"><div class="emergency-content"><div class="emergency-icon"><i class="bi bi-telephone-fill"></i></div><div class="emergency-text"><h4><?php echo htmlspecialchars($ctaEmergencyTitle, ENT_QUOTES, "UTF-8"); ?></h4><p><?php echo htmlspecialchars($ctaEmergencyText, ENT_QUOTES, "UTF-8"); ?></p></div></div></div><div class="col-lg-4 text-end"><a href="<?php echo htmlspecialchars($ctaEmergencyButtonUrl, ENT_QUOTES, "UTF-8"); ?>" class="emergency-btn"><i class="bi bi-telephone-fill"></i><?php echo htmlspecialchars($ctaEmergencyButtonText, ENT_QUOTES, "UTF-8"); ?></a></div></div></div>
    </div>
  </section>

  <section id="emergency-info" class="emergency-info section">
    <div class="container section-title" data-aos="fade-up"><?php if ($emergencyInfoTitle !== ""): ?><h2><?php echo htmlspecialchars($emergencyInfoTitle, ENT_QUOTES, "UTF-8"); ?></h2><?php endif; ?><?php if ($emergencyInfoText !== ""): ?><p><?php echo nl2br(htmlspecialchars($emergencyInfoText, ENT_QUOTES, "UTF-8")); ?></p><?php endif; ?></div>
    <div class="container" data-aos="fade-up" data-aos-delay="100"><div class="row"><div class="col-lg-8 col-md-10 mx-auto"><div class="emergency-alert" data-aos="zoom-in" data-aos-delay="100"><div class="alert-icon"><i class="bi bi-exclamation-triangle-fill"></i></div><div class="alert-content"><h3><?php echo htmlspecialchars($emergencyBannerTitle, ENT_QUOTES, "UTF-8"); ?></h3><p><?php echo nl2br(htmlspecialchars($emergencyBannerText, ENT_QUOTES, "UTF-8")); ?></p></div><div class="alert-action"><a href="<?php echo htmlspecialchars($emergencyBannerButtonUrl, ENT_QUOTES, "UTF-8"); ?>" class="btn btn-emergency"><i class="bi bi-telephone-fill"></i><?php echo htmlspecialchars($emergencyBannerButtonText, ENT_QUOTES, "UTF-8"); ?></a></div></div><?php if ($emergencyContacts !== []): ?><div class="row emergency-contacts" data-aos="fade-up" data-aos-delay="200"><?php foreach ($emergencyContacts as $contactItem): $contactFields = $contactItem["fields"] ?? []; $variantClass = trim((string) ($contactFields["variant"] ?? "")); ?><div class="col-md-6 mb-4"><div class="contact-card<?php echo $variantClass !== "" ? " " . htmlspecialchars($variantClass, ENT_QUOTES, "UTF-8") : ""; ?>"><div class="card-icon"><i class="<?php echo htmlspecialchars((string) ($contactFields["icon_class"] ?? ""), ENT_QUOTES, "UTF-8"); ?>"></i></div><div class="card-content"><h4><?php echo htmlspecialchars((string) ($contactFields["title"] ?? ""), ENT_QUOTES, "UTF-8"); ?></h4><?php if ((string) ($contactFields["phone"] ?? "") !== ""): ?><p class="contact-info"><i class="bi bi-telephone"></i><span><?php echo htmlspecialchars((string) ($contactFields["phone"] ?? ""), ENT_QUOTES, "UTF-8"); ?></span></p><?php endif; ?><?php if ((string) ($contactFields["address"] ?? "") !== ""): ?><p class="address"><i class="bi bi-geo-alt"></i><?php echo htmlspecialchars((string) ($contactFields["address"] ?? ""), ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?><?php if ((string) ($contactFields["description"] ?? "") !== ""): ?><p class="description"><?php echo htmlspecialchars((string) ($contactFields["description"] ?? ""), ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?><?php if ((string) ($contactFields["hours"] ?? "") !== ""): ?><p class="hours"><?php echo htmlspecialchars((string) ($contactFields["hours"] ?? ""), ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?></div><div class="card-action"><a href="<?php echo htmlspecialchars((string) ($contactFields["button_url"] ?? "#"), ENT_QUOTES, "UTF-8"); ?>" class="btn btn-contact"><?php echo htmlspecialchars((string) ($contactFields["button_text"] ?? ""), ENT_QUOTES, "UTF-8"); ?></a></div></div></div><?php endforeach; ?></div><?php endif; ?><?php if ($quickActions !== []): ?><div class="quick-actions" data-aos="fade-up" data-aos-delay="300"><h4><?php echo htmlspecialchars($quickActionsTitle, ENT_QUOTES, "UTF-8"); ?></h4><div class="row"><?php foreach ($quickActions as $actionItem): $actionFields = $actionItem["fields"] ?? []; ?><div class="col-sm-6 col-lg-3"><a href="<?php echo htmlspecialchars((string) ($actionFields["url"] ?? "#"), ENT_QUOTES, "UTF-8"); ?>" class="action-link"><i class="<?php echo htmlspecialchars((string) ($actionFields["icon_class"] ?? ""), ENT_QUOTES, "UTF-8"); ?>"></i><span><?php echo htmlspecialchars((string) ($actionFields["label"] ?? ""), ENT_QUOTES, "UTF-8"); ?></span></a></div><?php endforeach; ?></div></div><?php endif; ?><?php if ($emergencyTips !== []): ?><div class="emergency-tips" data-aos="fade-up" data-aos-delay="400"><h4><?php echo htmlspecialchars($emergencyTipsTitle, ENT_QUOTES, "UTF-8"); ?></h4><div class="row"><div class="col-md-6"><ul class="emergency-list"><?php foreach ($emergencyTipsLeft as $tipItem): $tipFields = $tipItem["fields"] ?? []; ?><li><i class="bi bi-check-circle"></i> <?php echo htmlspecialchars((string) ($tipFields["text"] ?? ""), ENT_QUOTES, "UTF-8"); ?></li><?php endforeach; ?></ul></div><div class="col-md-6"><ul class="emergency-list"><?php foreach ($emergencyTipsRight as $tipItem): $tipFields = $tipItem["fields"] ?? []; ?><li><i class="bi bi-check-circle"></i> <?php echo htmlspecialchars((string) ($tipFields["text"] ?? ""), ENT_QUOTES, "UTF-8"); ?></li><?php endforeach; ?></ul></div></div></div><?php endif; ?></div></div></div>
  </section>
</main>

<?php require __DIR__ . "/../../includes/footer.php"; ?>
