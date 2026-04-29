<?php
if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . "/../db.php";
}

if (!function_exists("publicPageUrl")) {
    function publicPageUrl(string $slug): string
    {
        $slug = trim($slug, "/");

        if ($slug === "" || $slug === "inicio") {
            return "index.php";
        }

        return "page.php?slug=" . rawurlencode($slug);
    }
}

if (!function_exists("isAbsoluteMenuUrl")) {
    function isAbsoluteMenuUrl(string $url): bool
    {
        return (bool) preg_match('#^https?://#i', $url);
    }
}

if (!function_exists("isDirectMenuUrl")) {
    function isDirectMenuUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === "" || $url === "index.php") {
            return true;
        }

        if (isAbsoluteMenuUrl($url)) {
            return true;
        }

        if (str_starts_with($url, "#") || preg_match('#^(mailto:|tel:)#i', $url)) {
            return true;
        }

        if (preg_match('/\.php(?:[?#].*)?$/i', $url)) {
            return true;
        }

        return (bool) preg_match('/\.[a-z0-9]{2,5}(?:[?#].*)?$/i', $url);
    }
}

if (!function_exists("resolveMenuItemHref")) {
    function resolveMenuItemHref(string $url, bool $isButton = false): string
    {
        $url = trim($url);

        if ($url === "" || $url === "index.php") {
            return "index.php";
        }

        if (isDirectMenuUrl($url)) {
            return $url;
        }

        return publicPageUrl($url);
    }
}

if (!function_exists("resolveMenuItemTarget")) {
    function resolveMenuItemTarget(string $target): string
    {
        return $target === "_blank" ? "_blank" : "_self";
    }
}

if (!function_exists("resolveMenuItemSlug")) {
    function resolveMenuItemSlug(string $url): string
    {
        $url = trim($url);

        if ($url === "" || $url === "index.php") {
            return "";
        }

        if (preg_match('/^page\.php\?slug=([^&]+)/i', $url, $matches) === 1) {
            return rawurldecode((string) $matches[1]);
        }

        if (isDirectMenuUrl($url)) {
            return "";
        }

        return trim($url, "/");
    }
}

$publicMenuItems = [];
$publicMenuButton = null;

if (isset($conn) && $conn instanceof mysqli) {
    $menuSql = "SELECT id, parent_id, item_key, label, url, target
                FROM menu_items
                WHERE is_button = 0 AND is_active = 1
                ORDER BY display_order ASC, id ASC";
    $menuResult = $conn->query($menuSql);

    if ($menuResult) {
        while ($row = $menuResult->fetch_assoc()) {
            $row["id"] = (int) ($row["id"] ?? 0);
            $row["parent_id"] = isset($row["parent_id"]) ? (int) $row["parent_id"] : null;
            $publicMenuItems[] = $row;
        }
    }

    $buttonSql = "SELECT id, label, url, target
                  FROM menu_items
                  WHERE is_button = 1 AND is_active = 1
                  ORDER BY display_order ASC, id ASC
                  LIMIT 1";
    $buttonResult = $conn->query($buttonSql);

    if ($buttonResult) {
        $publicMenuButton = $buttonResult->fetch_assoc() ?: null;
    }
}

if (count($publicMenuItems) === 0) {
    $publicMenuItems = [
        [
            "id" => 1,
            "parent_id" => null,
            "item_key" => "home",
            "label" => "Inicio",
            "url" => "index.php",
            "target" => "_self",
        ],
        [
            "id" => 2,
            "parent_id" => null,
            "item_key" => "about",
            "label" => "Nosotros",
            "url" => "nosotros",
            "target" => "_self",
        ],
    ];
}

$publicMenuItemsByParent = [];
foreach ($publicMenuItems as $menuItem) {
    $parentKey = isset($menuItem["parent_id"]) && (int) $menuItem["parent_id"] > 0 ? (string) ((int) $menuItem["parent_id"]) : "root";
    $publicMenuItemsByParent[$parentKey][] = $menuItem;
}

if (!function_exists("renderPublicMenuItem")) {
    function renderPublicMenuItem(array $menuItem, array $itemsByParent, string $currentPublicSlug): void
    {
        $menuItemId = (int) ($menuItem["id"] ?? 0);
        $children = $itemsByParent[(string) $menuItemId] ?? [];
        $hasChildren = $children !== [];
        $menuItemLabel = htmlspecialchars((string) ($menuItem["label"] ?? ""), ENT_QUOTES, "UTF-8");
        $menuItemUrl = resolveMenuItemHref((string) ($menuItem["url"] ?? ""));
        $menuItemTarget = resolveMenuItemTarget((string) ($menuItem["target"] ?? "_self"));
        $menuItemKey = (string) ($menuItem["item_key"] ?? "");
        $menuItemSlug = resolveMenuItemSlug((string) ($menuItem["url"] ?? ""));
        $menuIsHome = $menuItemUrl === "index.php" || $menuItemKey === "home" || in_array($menuItemSlug, ["", "inicio", "home"], true);
        $menuIsActive = $menuIsHome
            ? in_array($currentPublicSlug, ["", "inicio", "home"], true)
            : $menuItemSlug !== "" && $menuItemSlug === $currentPublicSlug;
        ?>
            <li<?php echo $hasChildren ? ' class="dropdown"' : ""; ?>>
              <a href="<?php echo htmlspecialchars($menuItemUrl, ENT_QUOTES, "UTF-8"); ?>" target="<?php echo htmlspecialchars($menuItemTarget, ENT_QUOTES, "UTF-8"); ?>"<?php echo $menuIsActive ? ' class="active"' : ""; ?>><?php if ($hasChildren): ?><span><?php echo $menuItemLabel; ?></span> <i class="bi bi-chevron-down toggle-dropdown"></i><?php else: ?><?php echo $menuItemLabel; ?><?php endif; ?></a>
              <?php if ($hasChildren): ?>
                <ul>
                  <?php foreach ($children as $childItem): ?>
                    <?php renderPublicMenuItem($childItem, $itemsByParent, $currentPublicSlug); ?>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </li>
        <?php
    }
}

$pageTitleEscaped = htmlspecialchars($pageTitle, ENT_QUOTES, "UTF-8");
$metaDescriptionEscaped = htmlspecialchars($metaDescription, ENT_QUOTES, "UTF-8");
$metaKeywordsEscaped = htmlspecialchars($metaKeywords, ENT_QUOTES, "UTF-8");
$metaRobotsEscaped = htmlspecialchars($metaRobots, ENT_QUOTES, "UTF-8");
$ogTitleEscaped = htmlspecialchars($ogTitle, ENT_QUOTES, "UTF-8");
$ogDescriptionEscaped = htmlspecialchars($ogDescription, ENT_QUOTES, "UTF-8");
$canonicalUrlEscaped = htmlspecialchars($canonicalUrl, ENT_QUOTES, "UTF-8");
$ogImageEscaped = htmlspecialchars($ogImage, ENT_QUOTES, "UTF-8");
$bodyClassEscaped = htmlspecialchars($bodyClass ?? "site-page", ENT_QUOTES, "UTF-8");
$currentPublicSlug = (string) ($currentPublicSlug ?? "");

if (!function_exists("getDefaultPublicSiteSettings")) {
    function getDefaultPublicSiteSettings(): array
    {
        return [
            "__has_row" => false,
            "site_name" => "FamySalud",
            "site_logo_path" => "",
            "footer_about_text" => "Atención médica con enfoque humano, cercano y profesional.\nInformación institucional y canales de contacto en proceso de actualización.",
            "footer_copyright" => "Todos los derechos reservados",
            "facebook_url" => "#",
            "instagram_url" => "#",
            "twitter_url" => "#",
            "linkedin_url" => "#",
            "youtube_url" => "",
            "background_color" => "#ffffff",
            "default_color" => "#2c3031",
            "heading_color" => "#18444c",
            "accent_color" => "#049ebb",
            "nav_color" => "#496268",
            "nav_hover_color" => "#049ebb",
        ];
    }
}

if (!function_exists("isSafePublicColorValue")) {
    function isSafePublicColorValue(string $value): bool
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', trim($value)) === 1;
    }
}

if (!function_exists("siteSettingsTableExists")) {
    function siteSettingsTableExists(mysqli $conn): bool
    {
        $result = $conn->query("SHOW TABLES LIKE 'site_settings'");
        return $result instanceof mysqli_result && $result->num_rows > 0;
    }
}

if (!function_exists("loadPublicSiteSettings")) {
    function loadPublicSiteSettings(mysqli $conn): array
    {
        $settings = getDefaultPublicSiteSettings();
        if (!siteSettingsTableExists($conn)) {
            return $settings;
        }
        $stmt = $conn->prepare("SELECT site_name, site_logo_path, footer_about_text, footer_copyright, facebook_url, instagram_url, twitter_url, linkedin_url, youtube_url, background_color, default_color, heading_color, accent_color, nav_color, nav_hover_color FROM site_settings WHERE id = 1 LIMIT 1");
        if (!$stmt) {
            return $settings;
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        if (!is_array($row)) {
            return $settings;
        }
        $settings["__has_row"] = true;
        foreach (["site_name", "site_logo_path", "footer_about_text", "footer_copyright"] as $textKey) {
            $value = trim((string) ($row[$textKey] ?? ""));
            if ($value !== "") {
                $settings[$textKey] = $value;
            }
        }
        foreach (["facebook_url", "instagram_url", "twitter_url", "linkedin_url", "youtube_url"] as $urlKey) {
            $settings[$urlKey] = trim((string) ($row[$urlKey] ?? ""));
        }
        foreach (["background_color", "default_color", "heading_color", "accent_color", "nav_color", "nav_hover_color"] as $colorKey) {
            $value = trim((string) ($row[$colorKey] ?? ""));
            if (isSafePublicColorValue($value)) {
                $settings[$colorKey] = $value;
            }
        }
        return $settings;
    }
}

$publicSiteSettings = isset($conn) && $conn instanceof mysqli ? loadPublicSiteSettings($conn) : getDefaultPublicSiteSettings();
$publicSiteName = trim((string) ($publicSiteSettings["site_name"] ?? ""));
if ($publicSiteName === "") {
    $publicSiteName = "FamySalud";
}
$publicSiteNameEscaped = htmlspecialchars($publicSiteName, ENT_QUOTES, "UTF-8");
$publicSiteLogoPath = trim((string) ($publicSiteSettings["site_logo_path"] ?? ""));
$publicSiteLogoPathEscaped = htmlspecialchars($publicSiteLogoPath, ENT_QUOTES, "UTF-8");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title><?php echo $pageTitleEscaped; ?></title>
  <meta name="description" content="<?php echo $metaDescriptionEscaped; ?>">
  <?php if ($metaKeywordsEscaped !== ""): ?>
  <meta name="keywords" content="<?php echo $metaKeywordsEscaped; ?>">
  <?php endif; ?>
  <meta name="robots" content="<?php echo $metaRobotsEscaped; ?>">
  <meta property="og:title" content="<?php echo $ogTitleEscaped; ?>">
  <meta property="og:description" content="<?php echo $ogDescriptionEscaped; ?>">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?php echo $canonicalUrlEscaped; ?>">
  <?php if ($ogImageEscaped !== ""): ?>
  <meta property="og:image" content="<?php echo $ogImageEscaped; ?>">
  <?php endif; ?>
  <link rel="canonical" href="<?php echo $canonicalUrlEscaped; ?>">

  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <style>
    :root {
      --background-color: <?php echo htmlspecialchars((string) $publicSiteSettings["background_color"], ENT_QUOTES, "UTF-8"); ?>;
      --default-color: <?php echo htmlspecialchars((string) $publicSiteSettings["default_color"], ENT_QUOTES, "UTF-8"); ?>;
      --heading-color: <?php echo htmlspecialchars((string) $publicSiteSettings["heading_color"], ENT_QUOTES, "UTF-8"); ?>;
      --accent-color: <?php echo htmlspecialchars((string) $publicSiteSettings["accent_color"], ENT_QUOTES, "UTF-8"); ?>;
      --nav-color: <?php echo htmlspecialchars((string) $publicSiteSettings["nav_color"], ENT_QUOTES, "UTF-8"); ?>;
      --nav-hover-color: <?php echo htmlspecialchars((string) $publicSiteSettings["nav_hover_color"], ENT_QUOTES, "UTF-8"); ?>;
    }
  </style>
</head>
<body class="<?php echo $bodyClassEscaped; ?>">

  <header id="header" class="header d-flex align-items-center fixed-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
      <a href="index.php" class="logo d-flex align-items-center me-auto me-xl-0">
        <?php if ($publicSiteLogoPath !== ""): ?>
          <img src="<?php echo $publicSiteLogoPathEscaped; ?>" alt="<?php echo $publicSiteNameEscaped; ?>">
        <?php else: ?>
          <svg class="my-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <g id="iconCarrier">
            <path d="M22 22L2 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
            <path d="M17 22V6C17 4.11438 17 3.17157 16.4142 2.58579C15.8284 2 14.8856 2 13 2H11C9.11438 2 8.17157 2 7.58579 2.58579C7 3.17157 7 4.11438 7 6V22" stroke="currentColor" stroke-width="1.5"></path>
            <path opacity="0.5" d="M21 22V8.5C21 7.09554 21 6.39331 20.6629 5.88886C20.517 5.67048 20.3295 5.48298 20.1111 5.33706C19.6067 5 18.9045 5 17.5 5" stroke="currentColor" stroke-width="1.5"></path>
            <path opacity="0.5" d="M3 22V8.5C3 7.09554 3 6.39331 3.33706 5.88886C3.48298 5.67048 3.67048 5.48298 3.88886 5.33706C4.39331 5 5.09554 5 6.5 5" stroke="currentColor" stroke-width="1.5"></path>
            <path d="M12 22V19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
            <path opacity="0.5" d="M10 12H14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
            <path opacity="0.5" d="M5.5 11H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
            <path opacity="0.5" d="M5.5 14H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
            <path opacity="0.5" d="M17 11H18.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
            <path opacity="0.5" d="M17 14H18.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
            <path opacity="0.5" d="M5.5 8H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
            <path opacity="0.5" d="M17 8H18.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
            <path opacity="0.5" d="M10 15H14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
            <path d="M12 9V5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            <path d="M14 7L10 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
          </g>
        </svg>
        <?php endif; ?>
        <h1 class="sitename"><?php echo $publicSiteNameEscaped; ?></h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <?php foreach ($publicMenuItemsByParent["root"] ?? [] as $menuItem): ?>
            <?php renderPublicMenuItem($menuItem, $publicMenuItemsByParent, $currentPublicSlug); ?>
          <?php endforeach; ?>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      <?php if ($publicMenuButton !== null): ?>
        <?php
        $buttonHref = resolveMenuItemHref((string) ($publicMenuButton["url"] ?? ""), true);
        $buttonTarget = resolveMenuItemTarget((string) ($publicMenuButton["target"] ?? "_self"));
        $buttonLabel = htmlspecialchars((string) ($publicMenuButton["label"] ?? "Agendar cita"), ENT_QUOTES, "UTF-8");
        ?>
        <a class="btn-getstarted" href="<?php echo htmlspecialchars($buttonHref, ENT_QUOTES, "UTF-8"); ?>" target="<?php echo htmlspecialchars($buttonTarget, ENT_QUOTES, "UTF-8"); ?>"><?php echo $buttonLabel; ?></a>
      <?php else: ?>
        <a class="btn-getstarted" href="appointment.html">Agendar cita</a>
      <?php endif; ?>
    </div>
  </header>

