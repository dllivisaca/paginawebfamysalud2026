<?php
$h1TitleEscaped = htmlspecialchars($h1Title, ENT_QUOTES, "UTF-8");
$pageDescriptionEscaped = $metaDescription !== ""
    ? htmlspecialchars($metaDescription, ENT_QUOTES, "UTF-8")
    : htmlspecialchars((string) ($page["title"] ?? ""), ENT_QUOTES, "UTF-8");

require __DIR__ . "/../../includes/header.php";
?>

  <main class="main">
    <div class="page-title">
      <div class="breadcrumbs">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house"></i> Inicio</a></li>
            <li class="breadcrumb-item active current"><?php echo $h1TitleEscaped; ?></li>
          </ol>
        </nav>
      </div>

      <div class="title-wrapper">
        <h1><?php echo $h1TitleEscaped; ?></h1>
        <p><?php echo $pageDescriptionEscaped; ?></p>
      </div>
    </div>

    <section id="about" class="about section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row gy-4">
          <div class="col-lg-6">
            <div class="content">
              <h2>Comprometidos con la excelencia en salud</h2>
              <p>
                Brindamos atenci&oacute;n integral con enfoque humano, tecnolog&iacute;a adecuada y procesos orientados al bienestar de cada paciente y su familia.
              </p>
              <p>
                Nuestro equipo trabaja para ofrecer una experiencia confiable, cercana y profesional, con servicios pensados para acompa&ntilde;arte en cada etapa de cuidado.
              </p>

              <div class="stats-container" data-aos="fade-up" data-aos-delay="200">
                <div class="row gy-4">
                  <div class="col-sm-6 col-lg-12 col-xl-6">
                    <div class="stat-item">
                      <div class="stat-number">
                        <span data-purecounter-start="0" data-purecounter-end="25" data-purecounter-duration="1" class="purecounter"></span>+
                      </div>
                      <div class="stat-label">A&ntilde;os de experiencia</div>
                    </div>
                  </div>
                  <div class="col-sm-6 col-lg-12 col-xl-6">
                    <div class="stat-item">
                      <div class="stat-number">
                        <span data-purecounter-start="0" data-purecounter-end="50000" data-purecounter-duration="2" class="purecounter"></span>+
                      </div>
                      <div class="stat-label">Pacientes atendidos</div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="cta-buttons" data-aos="fade-up" data-aos-delay="300">
                <a href="doctors.html" class="btn-primary">Conoce a nuestros doctores</a>
                <a href="services.html" class="btn-secondary">Ver servicios</a>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="image-section" data-aos="fade-left" data-aos-delay="200">
              <div class="main-image">
                <img src="assets/img/health/consultation-3.webp" alt="Consulta de salud" class="img-fluid">
              </div>
              <div class="image-grid">
                <div class="grid-item">
                  <img src="assets/img/health/facilities-2.webp" alt="Instalaciones m&eacute;dicas" class="img-fluid">
                </div>
                <div class="grid-item">
                  <img src="assets/img/health/staff-5.webp" alt="Personal m&eacute;dico" class="img-fluid">
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="certifications-section" data-aos="fade-up" data-aos-delay="400">
          <div class="row">
            <div class="col-lg-12">
              <div class="section-header">
                <h3>Acreditaciones y certificaciones</h3>
                <p>Contamos con respaldos y est&aacute;ndares de calidad que fortalecen la confianza de nuestros pacientes.</p>
              </div>
              <div class="certifications-grid">
                <div class="certification-item">
                  <img src="assets/img/clients/clients-1.webp" alt="Acreditaci&oacute;n" class="img-fluid">
                </div>
                <div class="certification-item">
                  <img src="assets/img/clients/clients-2.webp" alt="Certificaci&oacute;n" class="img-fluid">
                </div>
                <div class="certification-item">
                  <img src="assets/img/clients/clients-3.webp" alt="Certificaci&oacute;n de calidad" class="img-fluid">
                </div>
                <div class="certification-item">
                  <img src="assets/img/clients/clients-4.webp" alt="Acreditaci&oacute;n institucional" class="img-fluid">
                </div>
                <div class="certification-item">
                  <img src="assets/img/clients/clients-5.webp" alt="Respaldo profesional" class="img-fluid">
                </div>
                <div class="certification-item">
                  <img src="assets/img/clients/clients-6.webp" alt="Organizaci&oacute;n de salud" class="img-fluid">
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

<?php require __DIR__ . "/../../includes/footer.php"; ?>
