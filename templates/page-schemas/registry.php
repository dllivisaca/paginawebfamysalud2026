<?php

function getPageTemplateContentSchemas(): array
{
    return [
        "about" => require __DIR__ . "/about.php",
        "appointment" => require __DIR__ . "/appointment.php",
        "contact" => require __DIR__ . "/contact.php",
        "department-details" => require __DIR__ . "/department-details.php",
        "departments" => require __DIR__ . "/departments.php",
        "doctors" => require __DIR__ . "/doctors.php",
        "faq" => require __DIR__ . "/faq.php",
        "gallery" => require __DIR__ . "/gallery.php",
        "home" => require __DIR__ . "/home.php",
        "service-details" => require __DIR__ . "/service-details.php",
        "services" => require __DIR__ . "/services.php",
        "testimonials" => require __DIR__ . "/testimonials.php",
    ];
}
