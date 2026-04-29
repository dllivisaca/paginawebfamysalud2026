<?php

function getPageTemplateContentSchemas(): array
{
    return [
        "about" => require __DIR__ . "/about.php",
        "contact" => require __DIR__ . "/contact.php",
        "department-details" => require __DIR__ . "/department-details.php",
        "departments" => require __DIR__ . "/departments.php",
        "doctors" => require __DIR__ . "/doctors.php",
        "home" => require __DIR__ . "/home.php",
        "services" => require __DIR__ . "/services.php",
    ];
}
