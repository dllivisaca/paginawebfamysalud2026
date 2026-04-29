<?php

return [
    "template_key" => "404",
    "template_name" => "Error 404",
    "simple_fields" => [
        ["field_key" => "hero_title", "label" => "Titulo principal", "field_type" => "text", "default" => "404", "default_visible" => 1],
        ["field_key" => "hero_subtitle", "label" => "Subtitulo principal", "field_type" => "textarea", "default" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", "default_visible" => 1],
        ["field_key" => "error_icon", "label" => "Error icono", "field_type" => "text", "default" => "bi bi-exclamation-circle", "default_visible" => 1],
        ["field_key" => "error_code", "label" => "Codigo error", "field_type" => "text", "default" => "404", "default_visible" => 1],
        ["field_key" => "error_title", "label" => "Error titulo", "field_type" => "text", "default" => "Oops! Page Not Found", "default_visible" => 1],
        ["field_key" => "error_text", "label" => "Error texto", "field_type" => "textarea", "default" => "The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.", "default_visible" => 1],
        ["field_key" => "search_placeholder", "label" => "Busqueda placeholder", "field_type" => "text", "default" => "Search for pages...", "default_visible" => 1],
        ["field_key" => "search_aria_label", "label" => "Busqueda aria label", "field_type" => "text", "default" => "Search", "default_visible" => 1],
        ["field_key" => "search_icon", "label" => "Busqueda icono", "field_type" => "text", "default" => "bi bi-search", "default_visible" => 1],
        ["field_key" => "button_text", "label" => "Boton texto", "field_type" => "text", "default" => "Back to Home", "default_visible" => 1],
        ["field_key" => "button_url", "label" => "Boton URL", "field_type" => "url", "default" => "/", "default_visible" => 1],
    ],
    "repeaters" => [],
];
