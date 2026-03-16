<?php

return [
    "template_key" => "about",
    "template_name" => "Nosotros",
    "simple_fields" => [
        ["field_key" => "intro_title", "label" => "Subtítulo de la página", "field_type" => "text", "default" => "Comprometidos con la excelencia en salud", "default_visible" => 1],
        ["field_key" => "intro_text_1", "label" => "Párrafo 1", "field_type" => "textarea", "default" => "Brindamos atención integral con enfoque humano, tecnología adecuada y procesos orientados al bienestar de cada paciente y su familia.", "default_visible" => 1],
        ["field_key" => "intro_text_2", "label" => "Párrafo 2", "field_type" => "textarea", "default" => "Nuestro equipo trabaja para ofrecer una experiencia confiable, cercana y profesional, con servicios pensados para acompañarte en cada etapa de cuidado.", "default_visible" => 1],
        ["field_key" => "primary_cta_text", "label" => "Texto botón principal", "field_type" => "text", "default" => "Conoce a nuestros doctores", "default_visible" => 1],
        ["field_key" => "primary_cta_url", "label" => "URL botón principal", "field_type" => "url", "default" => "doctors.html", "default_visible" => 1],
        ["field_key" => "secondary_cta_text", "label" => "Texto botón secundario", "field_type" => "text", "default" => "Ver servicios", "default_visible" => 1],
        ["field_key" => "secondary_cta_url", "label" => "URL botón secundario", "field_type" => "url", "default" => "services.html", "default_visible" => 1],
        ["field_key" => "main_image", "label" => "Imagen principal", "field_type" => "image", "default" => "assets/img/health/consultation-3.webp", "default_visible" => 1],
        ["field_key" => "main_image_alt", "label" => "Texto alternativo imagen principal", "field_type" => "text", "default" => "Consulta de salud", "default_visible" => 1],
        ["field_key" => "grid_image_1", "label" => "Imagen secundaria 1", "field_type" => "image", "default" => "assets/img/health/facilities-2.webp", "default_visible" => 1],
        ["field_key" => "grid_image_1_alt", "label" => "Texto alternativo imagen secundaria 1", "field_type" => "text", "default" => "Instalaciones médicas", "default_visible" => 1],
        ["field_key" => "grid_image_2", "label" => "Imagen secundaria 2", "field_type" => "image", "default" => "assets/img/health/staff-5.webp", "default_visible" => 1],
        ["field_key" => "grid_image_2_alt", "label" => "Texto alternativo imagen secundaria 2", "field_type" => "text", "default" => "Personal médico", "default_visible" => 1],
        ["field_key" => "certifications_title", "label" => "Título de certificaciones", "field_type" => "text", "default" => "Acreditaciones y certificaciones", "default_visible" => 1],
        ["field_key" => "certifications_text", "label" => "Texto de certificaciones", "field_type" => "textarea", "default" => "Contamos con respaldos y estándares de calidad que fortalecen la confianza de nuestros pacientes.", "default_visible" => 1],
    ],
    "repeaters" => [
        [
            "repeater_key" => "stats",
            "label" => "Estadísticas",
            "fields" => [
                ["field_key" => "number", "label" => "Número", "field_type" => "text", "default" => ""],
                ["field_key" => "suffix", "label" => "Sufijo", "field_type" => "text", "default" => "+"],
                ["field_key" => "label", "label" => "Etiqueta", "field_type" => "text", "default" => ""],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Estadística 1", "default_visible" => 1, "defaults" => ["number" => "25", "suffix" => "+", "label" => "Años de experiencia"]],
                ["item_index" => 1, "item_label" => "Estadística 2", "default_visible" => 1, "defaults" => ["number" => "50000", "suffix" => "+", "label" => "Pacientes atendidos"]],
            ],
        ],
        [
            "repeater_key" => "certifications",
            "label" => "Logos de certificaciones",
            "fields" => [
                ["field_key" => "logo_image", "label" => "Logo", "field_type" => "image", "default" => ""],
                ["field_key" => "logo_alt", "label" => "Texto alternativo", "field_type" => "text", "default" => ""],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Logo 1", "default_visible" => 1, "defaults" => ["logo_image" => "assets/img/clients/clients-1.webp", "logo_alt" => "Acreditación"]],
                ["item_index" => 1, "item_label" => "Logo 2", "default_visible" => 1, "defaults" => ["logo_image" => "assets/img/clients/clients-2.webp", "logo_alt" => "Certificación"]],
                ["item_index" => 2, "item_label" => "Logo 3", "default_visible" => 1, "defaults" => ["logo_image" => "assets/img/clients/clients-3.webp", "logo_alt" => "Certificación de calidad"]],
                ["item_index" => 3, "item_label" => "Logo 4", "default_visible" => 1, "defaults" => ["logo_image" => "assets/img/clients/clients-4.webp", "logo_alt" => "Acreditación institucional"]],
                ["item_index" => 4, "item_label" => "Logo 5", "default_visible" => 1, "defaults" => ["logo_image" => "assets/img/clients/clients-5.webp", "logo_alt" => "Respaldo profesional"]],
                ["item_index" => 5, "item_label" => "Logo 6", "default_visible" => 1, "defaults" => ["logo_image" => "assets/img/clients/clients-6.webp", "logo_alt" => "Organización de salud"]],
            ],
        ],
    ],
];