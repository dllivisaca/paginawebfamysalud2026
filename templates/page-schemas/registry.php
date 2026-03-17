<?php

function getPageTemplateContentSchemas(): array
{
    return [
        "about" => require __DIR__ . "/about.php",
        "home" => require __DIR__ . "/home.php",
    ];
}
