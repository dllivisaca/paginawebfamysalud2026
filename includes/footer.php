<?php
if (!isset($publicSiteSettings) || !is_array($publicSiteSettings)) {
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
    $publicSiteSettings = getDefaultPublicSiteSettings();
}

$footerSiteName = trim((string) ($publicSiteSettings["site_name"] ?? ""));
if ($footerSiteName === "") {
    $footerSiteName = "FamySalud";
}
$footerSiteNameEscaped = htmlspecialchars($footerSiteName, ENT_QUOTES, "UTF-8");

$footerAboutText = trim((string) ($publicSiteSettings["footer_about_text"] ?? ""));
if ($footerAboutText === "") {
    $footerAboutText = "Atención médica con enfoque humano, cercano y profesional.\nInformación institucional y canales de contacto en proceso de actualización.";
}
$footerAboutParagraphs = array_values(array_filter(array_map("trim", preg_split('/\R+/', $footerAboutText))));

$footerCopyright = trim((string) ($publicSiteSettings["footer_copyright"] ?? ""));
if ($footerCopyright === "") {
    $footerCopyright = "Todos los derechos reservados";
}
$footerCopyrightEscaped = htmlspecialchars($footerCopyright, ENT_QUOTES, "UTF-8");

$footerSocialConfig = [
    ["key" => "twitter_url", "label" => "X", "icon" => "bi bi-twitter-x"],
    ["key" => "facebook_url", "label" => "Facebook", "icon" => "bi bi-facebook"],
    ["key" => "instagram_url", "label" => "Instagram", "icon" => "bi bi-instagram"],
    ["key" => "linkedin_url", "label" => "LinkedIn", "icon" => "bi bi-linkedin"],
    ["key" => "youtube_url", "label" => "YouTube", "icon" => "bi bi-youtube"],
];
$footerSocialLinks = [];
foreach ($footerSocialConfig as $socialItem) {
    $url = trim((string) ($publicSiteSettings[$socialItem["key"]] ?? ""));
    if ($url !== "") {
        $footerSocialLinks[] = [
            "url" => $url,
            "label" => $socialItem["label"],
            "icon" => $socialItem["icon"],
        ];
    }
}
?>  <footer id="footer" class="footer position-relative light-background">
    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-4 col-md-6 footer-about">
          <a href="index.php" class="logo d-flex align-items-center">
            <span class="sitename"><?php echo $footerSiteNameEscaped; ?></span>
          </a>
          <div class="footer-contact pt-3">
            <?php foreach ($footerAboutParagraphs as $footerAboutParagraph): ?>
              <p><?php echo htmlspecialchars($footerAboutParagraph, ENT_QUOTES, "UTF-8"); ?></p>
            <?php endforeach; ?>
          </div>
          <?php if ($footerSocialLinks !== []): ?>
          <div class="social-links d-flex mt-4">
            <?php foreach ($footerSocialLinks as $footerSocialLink): ?>
              <a href="<?php echo htmlspecialchars($footerSocialLink["url"], ENT_QUOTES, "UTF-8"); ?>" aria-label="<?php echo htmlspecialchars($footerSocialLink["label"], ENT_QUOTES, "UTF-8"); ?>"><i class="<?php echo htmlspecialchars($footerSocialLink["icon"], ENT_QUOTES, "UTF-8"); ?>"></i></a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Enlaces &uacute;tiles</h4>
          <ul>
            <li><a href="index.php">Inicio</a></li>
            <li><a href="<?php echo htmlspecialchars(publicPageUrl("nosotros"), ENT_QUOTES, "UTF-8"); ?>">Nosotros</a></li>
            <li><a href="services.html">Servicios</a></li>
            <li><a href="contact.html">Contacto</a></li>
            <li><a href="appointment.html">Agendar cita</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Servicios</h4>
          <ul>
            <li><a href="services.html">Consulta m&eacute;dica</a></li>
            <li><a href="departments.html">Especialidades</a></li>
            <li><a href="services.html">Atenci&oacute;n preventiva</a></li>
            <li><a href="services.html">Evaluaciones</a></li>
            <li><a href="services.html">Seguimiento</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Informaci&oacute;n</h4>
          <ul>
            <li><a href="departments.html">Especialidades</a></li>
            <li><a href="doctors.html">Doctores</a></li>
            <li><a href="privacy.html">Pol&iacute;tica de privacidad</a></li>
            <li><a href="terms.html">T&eacute;rminos del servicio</a></li>
            <li><a href="faq.html">Preguntas frecuentes</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-md-3 footer-links">
          <h4>Apoyo</h4>
          <ul>
            <li><a href="contact.html">Canales de contacto</a></li>
            <li><a href="appointment.html">Solicitar cita</a></li>
            <li><a href="services.html">Orientaci&oacute;n de servicios</a></li>
            <li><a href="privacy.html">Uso de datos</a></li>
            <li><a href="terms.html">Condiciones del sitio</a></li>
          </ul>
        </div>
      </div>
    </div>

    <div class="container copyright text-center mt-4">
      <p>&copy; <span>Copyright</span> <strong class="px-1 sitename"><?php echo $footerSiteNameEscaped; ?></strong> <span><?php echo $footerCopyrightEscaped; ?></span></p>
    </div>
  </footer>

  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <div id="preloader"></div>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/js/main.js"></script>

</body>
</html>

