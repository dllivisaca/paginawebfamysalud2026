<?php
$appointmentContent = is_array($pageContent ?? null) ? $pageContent : ["simple_fields" => [], "repeaters" => []];
$appointmentFields = $appointmentContent["simple_fields"] ?? [];
$appointmentRepeaters = $appointmentContent["repeaters"] ?? [];

function appointmentFieldValue(array $fields, string $fieldKey, string $default = ""): string
{
    return (string) ($fields[$fieldKey]["value"] ?? $default);
}

function appointmentFieldVisible(array $fields, string $fieldKey, bool $default = true): bool
{
    if (!isset($fields[$fieldKey])) {
        return $default;
    }

    return (bool) ($fields[$fieldKey]["is_visible"] ?? false);
}

function appointmentVisibleRepeaterItems(array $items): array
{
    return array_values(array_filter($items, static function ($item): bool {
        return (bool) ($item["is_visible"] ?? false);
    }));
}

function appointmentRepeaterField(array $fields, string $fieldKey, string $default = ""): string
{
    return trim((string) ($fields[$fieldKey] ?? $default));
}

$h1TitleEscaped = htmlspecialchars($h1Title, ENT_QUOTES, "UTF-8");
$heroTitle = appointmentFieldValue($appointmentFields, "hero_title", (string) ($page["title"] ?? "Appointment"));
$heroSubtitle = appointmentFieldValue($appointmentFields, "hero_subtitle", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.");
$infoTitle = appointmentFieldValue($appointmentFields, "info_title", "Quick & Easy Online Booking");
$infoText = appointmentFieldValue($appointmentFields, "info_text", "Book your appointment in just a few simple steps. Our healthcare professionals are ready to provide you with the best medical care tailored to your needs.");
$emergencyTitle = appointmentFieldValue($appointmentFields, "emergency_title", "Emergency Hotline");
$emergencyIcon = appointmentFieldValue($appointmentFields, "emergency_icon", "bi bi-telephone-fill me-2");
$emergencyText = appointmentFieldValue($appointmentFields, "emergency_text", "Call <strong>+1 (555) 911-4567</strong> for urgent medical assistance");
$namePlaceholder = appointmentFieldValue($appointmentFields, "name_placeholder", "Your Full Name");
$emailPlaceholder = appointmentFieldValue($appointmentFields, "email_placeholder", "Your Email");
$phonePlaceholder = appointmentFieldValue($appointmentFields, "phone_placeholder", "Your Phone Number");
$departmentPlaceholder = appointmentFieldValue($appointmentFields, "department_placeholder", "Select Department");
$doctorPlaceholder = appointmentFieldValue($appointmentFields, "doctor_placeholder", "Select Doctor");
$messagePlaceholder = appointmentFieldValue($appointmentFields, "message_placeholder", "Please describe your symptoms or reason for visit (optional)");
$loadingText = appointmentFieldValue($appointmentFields, "loading_text", "Loading");
$sentMessage = appointmentFieldValue($appointmentFields, "sent_message", "Your appointment request has been sent successfully. We will contact you shortly!");
$buttonText = appointmentFieldValue($appointmentFields, "button_text", "Book Appointment");
$buttonIcon = appointmentFieldValue($appointmentFields, "button_icon", "bi bi-calendar-plus me-2");
$infoItems = appointmentVisibleRepeaterItems($appointmentRepeaters["info_items"] ?? []);
$departments = appointmentVisibleRepeaterItems($appointmentRepeaters["departments"] ?? []);
$doctors = appointmentVisibleRepeaterItems($appointmentRepeaters["doctors"] ?? []);
$processSteps = appointmentVisibleRepeaterItems($appointmentRepeaters["process_steps"] ?? []);

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
            <li class="breadcrumb-item active current"><?php echo htmlspecialchars($heroTitle !== "" ? $heroTitle : "Appointment", ENT_QUOTES, "UTF-8"); ?></li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <?php if (appointmentFieldVisible($appointmentFields, "hero_title") && $heroTitle !== ""): ?>
        <h1><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, "UTF-8"); ?></h1>
        <?php else: ?>
        <h1><?php echo $h1TitleEscaped; ?></h1>
        <?php endif; ?>
        <?php if (appointmentFieldVisible($appointmentFields, "hero_subtitle") && $heroSubtitle !== ""): ?>
        <p><?php echo htmlspecialchars($heroSubtitle, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>
      </div>
    </div><!-- End Page Title -->

    <!-- Appointmnet Section -->
    <section id="appointmnet" class="appointmnet section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-4">

          <!-- Appointment Info -->
          <div class="col-lg-6">
            <div class="appointment-info">
              <?php if (appointmentFieldVisible($appointmentFields, "info_title") && $infoTitle !== ""): ?>
              <h3><?php echo htmlspecialchars($infoTitle, ENT_QUOTES, "UTF-8"); ?></h3>
              <?php endif; ?>
              <?php if (appointmentFieldVisible($appointmentFields, "info_text") && $infoText !== ""): ?>
              <p class="mb-4"><?php echo htmlspecialchars($infoText, ENT_QUOTES, "UTF-8"); ?></p>
              <?php endif; ?>

              <?php if ($infoItems !== []): ?>
              <div class="info-items">
                <?php foreach ($infoItems as $infoIndex => $infoItem): ?>
                  <?php
                  $infoFields = $infoItem["fields"] ?? [];
                  $iconClass = appointmentRepeaterField($infoFields, "icon_class");
                  $title = appointmentRepeaterField($infoFields, "title");
                  $text = appointmentRepeaterField($infoFields, "text");
                  $delay = 200 + ($infoIndex * 50);
                  ?>
                <div class="info-item d-flex align-items-center mb-3" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                  <div class="icon-wrapper me-3">
                    <?php if ($iconClass !== ""): ?><i class="<?php echo htmlspecialchars($iconClass, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?>
                  </div>
                  <div>
                    <?php if ($title !== ""): ?><h5><?php echo htmlspecialchars($title, ENT_QUOTES, "UTF-8"); ?></h5><?php endif; ?>
                    <?php if ($text !== ""): ?><p class="mb-0"><?php echo htmlspecialchars($text, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
                  </div>
                </div><!-- End Info Item -->

                <?php endforeach; ?>
              </div>
              <?php endif; ?>

              <div class="emergency-contact mt-4" data-aos="fade-up" data-aos-delay="350">
                <div class="emergency-card p-3">
                  <?php if (appointmentFieldVisible($appointmentFields, "emergency_title") && $emergencyTitle !== ""): ?>
                  <h6 class="mb-2"><?php if ($emergencyIcon !== ""): ?><i class="<?php echo htmlspecialchars($emergencyIcon, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?><?php echo htmlspecialchars($emergencyTitle, ENT_QUOTES, "UTF-8"); ?></h6>
                  <?php endif; ?>
                  <?php if (appointmentFieldVisible($appointmentFields, "emergency_text") && $emergencyText !== ""): ?>
                  <p class="mb-0"><?php echo $emergencyText; ?></p>
                  <?php endif; ?>
                </div>
              </div>

            </div>
          </div><!-- End Appointment Info -->

          <!-- Appointment Form -->
          <div class="col-lg-6">
            <div class="appointment-form-wrapper" data-aos="fade-up" data-aos-delay="200">
              <form action="forms/appointment.php" method="post" class="appointment-form php-email-form">
                <div class="row gy-3">

                  <div class="col-md-6">
                    <input type="text" name="name" class="form-control" placeholder="<?php echo htmlspecialchars($namePlaceholder, ENT_QUOTES, "UTF-8"); ?>" required="">
                  </div>

                  <div class="col-md-6">
                    <input type="email" name="email" class="form-control" placeholder="<?php echo htmlspecialchars($emailPlaceholder, ENT_QUOTES, "UTF-8"); ?>" required="">
                  </div>

                  <div class="col-md-6">
                    <input type="tel" name="phone" class="form-control" placeholder="<?php echo htmlspecialchars($phonePlaceholder, ENT_QUOTES, "UTF-8"); ?>" required="">
                  </div>

                  <div class="col-md-6">
                    <select name="department" class="form-select" required="">
                      <option value=""><?php echo htmlspecialchars($departmentPlaceholder, ENT_QUOTES, "UTF-8"); ?></option>
                      <?php foreach ($departments as $department): ?>
                        <?php
                        $departmentFields = $department["fields"] ?? [];
                        $value = appointmentRepeaterField($departmentFields, "value");
                        $label = appointmentRepeaterField($departmentFields, "label");
                        ?>
                      <?php if ($label !== ""): ?><option value="<?php echo htmlspecialchars($value, ENT_QUOTES, "UTF-8"); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, "UTF-8"); ?></option><?php endif; ?>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="col-md-6">
                    <input type="date" name="date" class="form-control" required="">
                  </div>

                  <div class="col-md-6">
                    <select name="doctor" class="form-select" required="">
                      <option value=""><?php echo htmlspecialchars($doctorPlaceholder, ENT_QUOTES, "UTF-8"); ?></option>
                      <?php foreach ($doctors as $doctor): ?>
                        <?php
                        $doctorFields = $doctor["fields"] ?? [];
                        $value = appointmentRepeaterField($doctorFields, "value");
                        $label = appointmentRepeaterField($doctorFields, "label");
                        ?>
                      <?php if ($label !== ""): ?><option value="<?php echo htmlspecialchars($value, ENT_QUOTES, "UTF-8"); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, "UTF-8"); ?></option><?php endif; ?>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="col-12">
                    <textarea class="form-control" name="message" rows="5" placeholder="<?php echo htmlspecialchars($messagePlaceholder, ENT_QUOTES, "UTF-8"); ?>"></textarea>
                  </div>

                  <div class="col-12">
                    <div class="loading"><?php echo htmlspecialchars($loadingText, ENT_QUOTES, "UTF-8"); ?></div>
                    <div class="error-message"></div>
                    <div class="sent-message"><?php echo htmlspecialchars($sentMessage, ENT_QUOTES, "UTF-8"); ?></div>

                    <button type="submit" class="btn btn-appointment w-100">
                      <?php if ($buttonIcon !== ""): ?><i class="<?php echo htmlspecialchars($buttonIcon, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?><?php echo htmlspecialchars($buttonText, ENT_QUOTES, "UTF-8"); ?>
                    </button>
                  </div>

                </div>
              </form>
            </div>
          </div><!-- End Appointment Form -->

        </div>

        <!-- Process Steps -->
        <?php if ($processSteps !== []): ?>
        <div class="process-steps mt-5" data-aos="fade-up" data-aos-delay="300">
          <div class="row text-center gy-4">
            <?php foreach ($processSteps as $processStep): ?>
              <?php
              $stepFields = $processStep["fields"] ?? [];
              $number = appointmentRepeaterField($stepFields, "number");
              $iconClass = appointmentRepeaterField($stepFields, "icon_class");
              $title = appointmentRepeaterField($stepFields, "title");
              $text = appointmentRepeaterField($stepFields, "text");
              ?>
            <div class="col-lg-3 col-md-6">
              <div class="step-item">
                <?php if ($number !== ""): ?><div class="step-number"><?php echo htmlspecialchars($number, ENT_QUOTES, "UTF-8"); ?></div><?php endif; ?>
                <div class="step-icon">
                  <?php if ($iconClass !== ""): ?><i class="<?php echo htmlspecialchars($iconClass, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?>
                </div>
                <?php if ($title !== ""): ?><h5><?php echo htmlspecialchars($title, ENT_QUOTES, "UTF-8"); ?></h5><?php endif; ?>
                <?php if ($text !== ""): ?><p><?php echo htmlspecialchars($text, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
              </div>
            </div><!-- End Step -->

            <?php endforeach; ?>
          </div>
        </div><!-- End Process Steps -->
        <?php endif; ?>

      </div>

    </section><!-- /Appointmnet Section -->

  </main>

<?php require __DIR__ . "/../../includes/footer.php"; ?>
