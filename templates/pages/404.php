<?php
$error404Content = is_array($pageContent ?? null) ? $pageContent : ["simple_fields" => [], "repeaters" => []];
$error404Fields = $error404Content["simple_fields"] ?? [];

function error404FieldValue(array $fields, string $fieldKey, string $default = ""): string
{
    return (string) ($fields[$fieldKey]["value"] ?? $default);
}

function error404FieldVisible(array $fields, string $fieldKey, bool $default = true): bool
{
    if (!isset($fields[$fieldKey])) {
        return $default;
    }

    return (bool) ($fields[$fieldKey]["is_visible"] ?? false);
}

function error404NormalizeCustomHref(string $url): string
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
$heroTitle = error404FieldValue($error404Fields, "hero_title", (string) ($page["title"] ?? "404"));
$heroSubtitle = error404FieldValue($error404Fields, "hero_subtitle", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.");
$errorIcon = error404FieldValue($error404Fields, "error_icon", "bi bi-exclamation-circle");
$errorCode = error404FieldValue($error404Fields, "error_code", "404");
$errorTitle = error404FieldValue($error404Fields, "error_title", "Oops! Page Not Found");
$errorText = error404FieldValue($error404Fields, "error_text", "The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.");
$searchPlaceholder = error404FieldValue($error404Fields, "search_placeholder", "Search for pages...");
$searchAriaLabel = error404FieldValue($error404Fields, "search_aria_label", "Search");
$searchIcon = error404FieldValue($error404Fields, "search_icon", "bi bi-search");
$buttonText = error404FieldValue($error404Fields, "button_text", "Back to Home");
$buttonUrl = error404NormalizeCustomHref(error404FieldValue($error404Fields, "button_url", "/"));

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
            <li class="breadcrumb-item active current"><?php echo htmlspecialchars($heroTitle !== "" ? $heroTitle : "404", ENT_QUOTES, "UTF-8"); ?></li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <?php if (error404FieldVisible($error404Fields, "hero_title") && $heroTitle !== ""): ?>
        <h1><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, "UTF-8"); ?></h1>
        <?php else: ?>
        <h1><?php echo $h1TitleEscaped; ?></h1>
        <?php endif; ?>
        <?php if (error404FieldVisible($error404Fields, "hero_subtitle") && $heroSubtitle !== ""): ?>
        <p><?php echo htmlspecialchars($heroSubtitle, ENT_QUOTES, "UTF-8"); ?></p>
        <?php endif; ?>
      </div>
    </div><!-- End Page Title -->

    <!-- Error 404 Section -->
    <section id="error-404" class="error-404 section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="text-center">
          <div class="error-icon mb-4" data-aos="zoom-in" data-aos-delay="200">
            <?php if (error404FieldVisible($error404Fields, "error_icon") && $errorIcon !== ""): ?><i class="<?php echo htmlspecialchars($errorIcon, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?>
          </div>

          <?php if (error404FieldVisible($error404Fields, "error_code") && $errorCode !== ""): ?>
          <h1 class="error-code mb-4" data-aos="fade-up" data-aos-delay="300"><?php echo htmlspecialchars($errorCode, ENT_QUOTES, "UTF-8"); ?></h1>
          <?php endif; ?>

          <?php if (error404FieldVisible($error404Fields, "error_title") && $errorTitle !== ""): ?>
          <h2 class="error-title mb-3" data-aos="fade-up" data-aos-delay="400"><?php echo htmlspecialchars($errorTitle, ENT_QUOTES, "UTF-8"); ?></h2>
          <?php endif; ?>

          <?php if (error404FieldVisible($error404Fields, "error_text") && $errorText !== ""): ?>
          <p class="error-text mb-4" data-aos="fade-up" data-aos-delay="500">
            <?php echo htmlspecialchars($errorText, ENT_QUOTES, "UTF-8"); ?>
          </p>
          <?php endif; ?>

          <div class="search-box mb-4" data-aos="fade-up" data-aos-delay="600">
            <form action="#" class="search-form">
              <div class="input-group">
                <input type="text" class="form-control" placeholder="<?php echo htmlspecialchars($searchPlaceholder, ENT_QUOTES, "UTF-8"); ?>" aria-label="<?php echo htmlspecialchars($searchAriaLabel, ENT_QUOTES, "UTF-8"); ?>">
                <button class="btn search-btn" type="submit">
                  <?php if ($searchIcon !== ""): ?><i class="<?php echo htmlspecialchars($searchIcon, ENT_QUOTES, "UTF-8"); ?>"></i><?php endif; ?>
                </button>
              </div>
            </form>
          </div>

          <div class="error-action" data-aos="fade-up" data-aos-delay="700">
            <?php if (error404FieldVisible($error404Fields, "button_text") && $buttonText !== ""): ?><a href="<?php echo htmlspecialchars($buttonUrl !== "" ? $buttonUrl : "/", ENT_QUOTES, "UTF-8"); ?>" class="btn btn-primary"><?php echo htmlspecialchars($buttonText, ENT_QUOTES, "UTF-8"); ?></a><?php endif; ?>
          </div>
        </div>

      </div>

    </section><!-- /Error 404 Section -->

  </main>

<?php require __DIR__ . "/../../includes/footer.php"; ?>
