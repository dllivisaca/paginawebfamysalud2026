<?php
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
</head>
<body class="<?php echo $bodyClassEscaped; ?>">

  <header id="header" class="header d-flex align-items-center fixed-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
      <a href="index.php" class="logo d-flex align-items-center me-auto me-xl-0">
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
        <h1 class="sitename">FamySalud</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.php"<?php echo $currentPublicSlug === "inicio" ? ' class="active"' : ""; ?>>Inicio</a></li>
          <li><a href="<?php echo htmlspecialchars(publicPageUrl("nosotros"), ENT_QUOTES, "UTF-8"); ?>"<?php echo $currentPublicSlug === "nosotros" ? ' class="active"' : ""; ?>>Nosotros</a></li>
          <li><a href="departments.html">Especialidades</a></li>
          <li><a href="services.html">Servicios</a></li>
          <li><a href="doctors.html">Doctores</a></li>
          <li><a href="contact.html">Contacto</a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      <a class="btn-getstarted" href="appointment.html">Agendar cita</a>
    </div>
  </header>
