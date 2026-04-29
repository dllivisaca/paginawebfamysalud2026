<?php

function getPageTemplateContentSchemas(): array
{
    return [
        "404" => require __DIR__ . "/404.php",
        "about" => require __DIR__ . "/about.php",
        "appointment" => require __DIR__ . "/appointment.php",
        "contact" => require __DIR__ . "/contact.php",
        "department-details" => require __DIR__ . "/department-details.php",
        "departments" => require __DIR__ . "/departments.php",
        "doctors" => require __DIR__ . "/doctors.php",
        "faq" => require __DIR__ . "/faq.php",
        "gallery" => require __DIR__ . "/gallery.php",
        "home" => require __DIR__ . "/home.php",
        "privacy" => require __DIR__ . "/privacy.php",
        "service-details" => require __DIR__ . "/service-details.php",
        "services" => require __DIR__ . "/services.php",
        "terms" => require __DIR__ . "/terms.php",
        "testimonials" => require __DIR__ . "/testimonials.php",
    ];
}
