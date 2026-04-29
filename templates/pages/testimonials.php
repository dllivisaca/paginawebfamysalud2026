<?php
$testimonialsContent = is_array($pageContent ?? null) ? $pageContent : ["simple_fields" => [], "repeaters" => []];
$testimonialsFields = $testimonialsContent["simple_fields"] ?? [];
$testimonialsRepeaters = $testimonialsContent["repeaters"] ?? [];

function testimonialsFieldValue(array $fields, string $fieldKey, string $default = ""): string
{
    return (string) ($fields[$fieldKey]["value"] ?? $default);
}

function testimonialsFieldVisible(array $fields, string $fieldKey, bool $default = true): bool
{
    if (!isset($fields[$fieldKey])) {
        return $default;
    }

    return (bool) ($fields[$fieldKey]["is_visible"] ?? false);
}

function testimonialsVisibleRepeaterItems(array $items): array
{
    return array_values(array_filter($items, static function ($item): bool {
        return (bool) ($item["is_visible"] ?? false);
    }));
}

function testimonialsRepeaterField(array $fields, string $fieldKey, string $default = ""): string
{
    return trim((string) ($fields[$fieldKey] ?? $default));
}

$h1TitleEscaped = htmlspecialchars($h1Title, ENT_QUOTES, "UTF-8");
$heroTitle = testimonialsFieldValue($testimonialsFields, "hero_title", (string) ($page["title"] ?? "Testimonials"));
$heroSubtitle = testimonialsFieldValue($testimonialsFields, "hero_subtitle", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.");
$featuredTestimonials = testimonialsVisibleRepeaterItems($testimonialsRepeaters["featured_testimonials"] ?? []);
$testimonialItems = testimonialsVisibleRepeaterItems($testimonialsRepeaters["testimonials"] ?? []);

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
            <li class="breadcrumb-item active current"><?php echo htmlspecialchars($heroTitle !== "" ? $heroTitle : "Testimonials", ENT_QUOTES, "UTF-8"); ?></li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <?php if (testimonialsFieldVisible($testimonialsFields, "hero_title") && $heroTitle !== ""): ?>
        <h1><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, "UTF-8"); ?></h1>
        <?php else: ?>
        <h1><?php echo $h1TitleEscaped; ?></h1>
        <?php endif; ?>
        <?php if (testimonialsFieldVisible($testimonialsFields, "hero_subtitle") && $heroSubtitle !== ""): ?>
        <p><?php echo htmlspecialchars($heroSubtitle, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>
      </div>
    </div><!-- End Page Title -->

    <!-- Featured Testimonials Section -->
    <section id="featured-testimonials" class="featured-testimonials section light-background">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="testimonials-slider swiper init-swiper">
          <script type="application/json" class="swiper-config">
            {
              "slidesPerView": 1,
              "loop": true,
              "speed": 600,
              "autoplay": {
                "delay": 5000
              },
              "navigation": {
                "nextEl": ".swiper-button-next",
                "prevEl": ".swiper-button-prev"
              }
            }
          </script>

          <div class="swiper-wrapper">

            <?php foreach ($featuredTestimonials as $featuredTestimonial): ?>
              <?php
              $featuredFields = $featuredTestimonial["fields"] ?? [];
              $title = testimonialsRepeaterField($featuredFields, "title");
              $textOne = testimonialsRepeaterField($featuredFields, "text_1");
              $textTwo = testimonialsRepeaterField($featuredFields, "text_2");
              $profileImage = testimonialsRepeaterField($featuredFields, "profile_image");
              $featuredImage = testimonialsRepeaterField($featuredFields, "featured_image", $profileImage);
              $imageAlt = testimonialsRepeaterField($featuredFields, "image_alt");
              $name = testimonialsRepeaterField($featuredFields, "name");
              $role = testimonialsRepeaterField($featuredFields, "role");
              ?>
            <div class="swiper-slide">
              <div class="testimonial-item">
                <div class="row">
                  <div class="col-lg-8">
                    <?php if ($title !== ""): ?><h2><?php echo htmlspecialchars($title, ENT_QUOTES, "UTF-8"); ?></h2><?php endif; ?>
                    <?php if ($textOne !== ""): ?>
                    <p>
                      <?php echo htmlspecialchars($textOne, ENT_QUOTES, "UTF-8"); ?>
                    </p>
                    <?php endif; ?>
                    <?php if ($textTwo !== ""): ?>
                    <p>
                      <?php echo htmlspecialchars($textTwo, ENT_QUOTES, "UTF-8"); ?>
                    </p>
                    <?php endif; ?>
                    <div class="profile d-flex align-items-center">
                      <?php if ($profileImage !== ""): ?><img src="<?php echo htmlspecialchars($profileImage, ENT_QUOTES, "UTF-8"); ?>" class="profile-img" alt="<?php echo htmlspecialchars($imageAlt, ENT_QUOTES, "UTF-8"); ?>"><?php endif; ?>
                      <div class="profile-info">
                        <?php if ($name !== ""): ?><h3><?php echo htmlspecialchars($name, ENT_QUOTES, "UTF-8"); ?></h3><?php endif; ?>
                        <?php if ($role !== ""): ?><span><?php echo htmlspecialchars($role, ENT_QUOTES, "UTF-8"); ?></span><?php endif; ?>
                      </div>
                    </div>
                  </div>
                  <div class="col-lg-4 d-none d-lg-block">
                    <div class="featured-img-wrapper">
                      <?php if ($featuredImage !== ""): ?><img src="<?php echo htmlspecialchars($featuredImage, ENT_QUOTES, "UTF-8"); ?>" class="featured-img" alt="<?php echo htmlspecialchars($imageAlt, ENT_QUOTES, "UTF-8"); ?>"><?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div><!-- End Testimonial Item -->

            <?php endforeach; ?>

          </div>

          <div class="swiper-navigation w-100 d-flex align-items-center justify-content-center">
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
          </div>

        </div>

      </div>

    </section><!-- /Featured Testimonials Section -->

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row g-4">
          <?php foreach ($testimonialItems as $testimonialIndex => $testimonialItem): ?>
            <?php
            $testimonialFields = $testimonialItem["fields"] ?? [];
            $rating = max(0, min(5, (int) testimonialsRepeaterField($testimonialFields, "rating", "5")));
            $text = testimonialsRepeaterField($testimonialFields, "text");
            $image = testimonialsRepeaterField($testimonialFields, "image");
            $imageAlt = testimonialsRepeaterField($testimonialFields, "image_alt", "Author");
            $name = testimonialsRepeaterField($testimonialFields, "name");
            $role = testimonialsRepeaterField($testimonialFields, "role");
            $delay = 100 + ($testimonialIndex * 100);
            ?>
          <!-- Testimonial Item <?php echo $testimonialIndex + 1; ?> -->
          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
            <div class="testimonial-item">
              <div class="stars">
                <?php for ($starIndex = 0; $starIndex < $rating; $starIndex++): ?>
                <i class="bi bi-star-fill"></i>
                <?php endfor; ?>
              </div>
              <?php if ($text !== ""): ?><p><?php echo htmlspecialchars($text, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
              <div class="testimonial-footer">
                <div class="testimonial-author">
                  <?php if ($image !== ""): ?><img src="<?php echo htmlspecialchars($image, ENT_QUOTES, "UTF-8"); ?>" alt="<?php echo htmlspecialchars($imageAlt, ENT_QUOTES, "UTF-8"); ?>" class="img-fluid rounded-circle" loading="lazy"><?php endif; ?>
                  <div>
                    <?php if ($name !== ""): ?><h5><?php echo htmlspecialchars($name, ENT_QUOTES, "UTF-8"); ?></h5><?php endif; ?>
                    <?php if ($role !== ""): ?><span><?php echo htmlspecialchars($role, ENT_QUOTES, "UTF-8"); ?></span><?php endif; ?>
                  </div>
                </div>
                <div class="quote-icon">
                  <i class="bi bi-quote"></i>
                </div>
              </div>
            </div>
          </div><!-- End Testimonial Item -->

          <?php endforeach; ?>

        </div>

      </div>

    </section><!-- /Testimonials Section -->

  </main>

<?php require __DIR__ . "/../../includes/footer.php"; ?>
