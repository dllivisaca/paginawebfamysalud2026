<?php
require_once __DIR__ . "/db.php";

function publicPageUrl(string $slug): string
{
    $slug = trim($slug, "/");

    if ($slug === "" || $slug === "inicio") {
        return "index.php";
    }

    return "page.php?slug=" . rawurlencode($slug);
}

function resolveRequestedSlug(): string
{
    $slug = trim((string) ($_GET["slug"] ?? ""));

    if ($slug !== "") {
        return trim($slug, "/");
    }

    $path = parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_PATH);

    if (!is_string($path) || $path === "") {
        return "";
    }

    $candidate = trim($path, "/");

    if ($candidate === "" || $candidate === basename(__FILE__)) {
        return "";
    }

    return $candidate;
}

function renderPageNotFound(): void
{
    http_response_code(404);
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>P&aacute;gina no encontrada</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
</head>
<body>
  <main class="main">
    <section class="section">
      <div class="container py-5">
        <h1>P&aacute;gina no encontrada</h1>
        <p>La p&aacute;gina solicitada no existe o no est&aacute; disponible en este momento.</p>
        <p><a href="index.php">Volver al inicio</a></p>
      </div>
    </section>
  </main>
</body>
</html>
    <?php
    exit;
}

function resolvePageTemplatePath(string $templateKey): string
{
    $templateKey = trim($templateKey);

    if ($templateKey === "") {
        return "";
    }

    $templatesDirectory = __DIR__ . "/templates/pages";
    $candidateFiles = [];

    if (preg_match('/^[a-zA-Z0-9_-]+$/', $templateKey) === 1) {
        $candidateFiles[] = $templateKey . ".php";
    }

    $fallbackTemplateMap = [
        "about" => ["about_v1.php"],
    ];

    foreach ($fallbackTemplateMap[$templateKey] ?? [] as $fallbackFile) {
        if (!in_array($fallbackFile, $candidateFiles, true)) {
            $candidateFiles[] = $fallbackFile;
        }
    }

    foreach ($candidateFiles as $candidateFile) {
        $candidatePath = $templatesDirectory . "/" . $candidateFile;

        if (is_file($candidatePath)) {
            return $candidatePath;
        }
    }

    return "";
}

$slug = resolveRequestedSlug();

if ($slug === "") {
    renderPageNotFound();
}

$sql = "SELECT id, page_key, title, slug, template_key, is_active, seo_title, seo_description,
               seo_keywords, h1_title, meta_robots, og_title, og_description, og_image, canonical_url
        FROM site_pages
        WHERE slug = ? AND is_active = 1
        LIMIT 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    renderPageNotFound();
}

$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$page = $result->fetch_assoc();
$stmt->close();

if (!$page) {
    renderPageNotFound();
}

$templatePath = resolvePageTemplatePath((string) ($page["template_key"] ?? ""));

if ($templatePath === "" || !is_file($templatePath)) {
    renderPageNotFound();
}

$pageTitle = trim((string) ($page["seo_title"] ?? ""));
if ($pageTitle === "") {
    $pageTitle = trim((string) ($page["title"] ?? ""));
}

$metaDescription = trim((string) ($page["seo_description"] ?? ""));
$metaKeywords = trim((string) ($page["seo_keywords"] ?? ""));
$h1Title = trim((string) ($page["h1_title"] ?? ""));
if ($h1Title === "") {
    $h1Title = trim((string) ($page["title"] ?? ""));
}

$metaRobots = trim((string) ($page["meta_robots"] ?? ""));
if ($metaRobots === "") {
    $metaRobots = "index,follow";
}

$ogTitle = trim((string) ($page["og_title"] ?? ""));
if ($ogTitle === "") {
    $ogTitle = $pageTitle;
}

$ogDescription = trim((string) ($page["og_description"] ?? ""));
if ($ogDescription === "") {
    $ogDescription = $metaDescription;
}

$ogImage = trim((string) ($page["og_image"] ?? ""));
$canonicalUrl = trim((string) ($page["canonical_url"] ?? ""));
if ($canonicalUrl === "") {
    $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
    $host = trim((string) ($_SERVER["HTTP_HOST"] ?? ""));

    if ($host !== "") {
        $canonicalUrl = $scheme . "://" . $host . "/" . ltrim(publicPageUrl((string) $page["slug"]), "/");
    } else {
        // Si no hay host disponible en este entorno, se conserva un fallback relativo estable.
        $canonicalUrl = publicPageUrl((string) $page["slug"]);
    }
}

$currentPublicSlug = (string) ($page["slug"] ?? "");
$bodyClass = ((string) ($page["page_key"] ?? "site")) . "-page";

require $templatePath;
