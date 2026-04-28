<?php

function getPageTemplateContentSchemas(): array
{
    return [
        "about" => require __DIR__ . "/about.php",
        "departments" => require __DIR__ . "/departments.php",
        "home" => require __DIR__ . "/home.php",
    ];
}
