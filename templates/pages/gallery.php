<?php
$galleryContent = is_array($pageContent ?? null) ? $pageContent : ["simple_fields" => [], "repeaters" => []];
$galleryFields = $galleryContent["simple_fields"] ?? [];
$galleryRepeaters = $galleryContent["repeaters"] ?? [];

function galleryFieldValue(array $fields, string $fieldKey, string $default = ""): string
{
    return (string) ($fields[$fieldKey]["value"] ?? $default);
}

function galleryFieldVisible(array $fields, string $fieldKey, bool $default = true): bool
{
    if (!isset($fields[$fieldKey])) {
        return $default;
    }

    return (bool) ($fields[$fieldKey]["is_visible"] ?? false);
}

function galleryVisibleRepeaterItems(array $items): array
{
    return array_values(array_filter($items, static function ($item): bool {
        return (bool) ($item["is_visible"] ?? false);
    }));
}

function galleryRepeaterField(array $fields, string $fieldKey, string $default = ""): string
{
    return trim((string) ($fields[$fieldKey] ?? $default));
}

$h1TitleEscaped = htmlspecialchars($h1Title, ENT_QUOTES, "UTF-8");
$heroTitle = galleryFieldValue($galleryFields, "hero_title", (string) ($page["title"] ?? "Gallery"));
$heroSubtitle = galleryFieldValue($galleryFields, "hero_subtitle", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.");
$galleryFilters = galleryVisibleRepeaterItems($galleryRepeaters["gallery_filters"] ?? []);
$galleryItems = galleryVisibleRepeaterItems($galleryRepeaters["gallery_items"] ?? []);

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
            <li class="breadcrumb-item active current"><?php echo htmlspecialchars($heroTitle !== "" ? $heroTitle : "Gallery", ENT_QUOTES, "UTF-8"); ?></li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <?php if (galleryFieldVisible($galleryFields, "hero_title") && $heroTitle !== ""): ?>
        <h1><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, "UTF-8"); ?></h1>
        <?php else: ?>
        <h1><?php echo $h1TitleEscaped; ?></h1>
        <?php endif; ?>
        <?php if (galleryFieldVisible($galleryFields, "hero_subtitle") && $heroSubtitle !== ""): ?>
        <p><?php echo htmlspecialchars($heroSubtitle, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>
      </div>
    </div><!-- End Page Title -->

    <!-- Gallery Section -->
    <section id="gallery" class="gallery section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="isotope-layout" data-default-filter="*" data-layout="masonry" data-sort="original-order">
          <?php if ($galleryFilters !== []): ?>
          <ul class="gallery-filters isotope-filters" data-aos="fade-up" data-aos-delay="100">
            <?php foreach ($galleryFilters as $filterIndex => $galleryFilter): ?>
              <?php
              $filterFields = $galleryFilter["fields"] ?? [];
              $filterValue = galleryRepeaterField($filterFields, "filter");
              $filterLabel = galleryRepeaterField($filterFields, "label");
              ?>
            <?php if ($filterLabel !== ""): ?><li data-filter="<?php echo htmlspecialchars($filterValue !== "" ? $filterValue : "*", ENT_QUOTES, "UTF-8"); ?>"<?php echo $filterIndex === 0 ? ' class="filter-active"' : ""; ?>><?php echo htmlspecialchars($filterLabel, ENT_QUOTES, "UTF-8"); ?></li><?php endif; ?>
            <?php endforeach; ?>
          </ul><!-- End Gallery Filters -->
          <?php endif; ?>

          <div class="row g-4 isotope-container" data-aos="fade-up" data-aos-delay="200">
            <?php foreach ($galleryItems as $galleryItem): ?>
              <?php
              $itemFields = $galleryItem["fields"] ?? [];
              $filterClass = galleryRepeaterField($itemFields, "filter_class");
              $image = galleryRepeaterField($itemFields, "image");
              $imageAlt = galleryRepeaterField($itemFields, "image_alt", "Gallery Image");
              $lightboxImage = galleryRepeaterField($itemFields, "lightbox_image", $image);
              $title = galleryRepeaterField($itemFields, "title");
              $description = galleryRepeaterField($itemFields, "description");
              ?>
            <div class="col-lg-4 col-md-6 gallery-item isotope-item <?php echo htmlspecialchars($filterClass, ENT_QUOTES, "UTF-8"); ?>">
              <div class="gallery-card">
                <div class="gallery-img">
                  <?php if ($image !== ""): ?><img src="<?php echo htmlspecialchars($image, ENT_QUOTES, "UTF-8"); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($imageAlt, ENT_QUOTES, "UTF-8"); ?>" loading="lazy"><?php endif; ?>
                  <div class="gallery-overlay">
                    <div class="gallery-info">
                      <?php if ($title !== ""): ?><h4><?php echo htmlspecialchars($title, ENT_QUOTES, "UTF-8"); ?></h4><?php endif; ?>
                      <?php if ($description !== ""): ?><p><?php echo htmlspecialchars($description, ENT_QUOTES, "UTF-8"); ?></p><?php endif; ?>
                      <?php if ($lightboxImage !== ""): ?>
                      <a href="<?php echo htmlspecialchars($lightboxImage, ENT_QUOTES, "UTF-8"); ?>" class="glightbox gallery-link" data-gallery="gallery-images">
                        <i class="bi bi-plus-circle"></i>
                      </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div><!-- End Gallery Item -->

            <?php endforeach; ?>
          </div><!-- End Gallery Container -->
        </div>

      </div>

    </section><!-- /Gallery Section -->

  </main>

<?php require __DIR__ . "/../../includes/footer.php"; ?>
