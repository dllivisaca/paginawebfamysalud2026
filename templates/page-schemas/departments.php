<?php

return [
    "template_key" => "departments",
    "template_name" => "Departamentos",
    "simple_fields" => [
        ["field_key" => "hero_title", "label" => "Titulo principal", "field_type" => "text", "default" => "Departamentos", "default_visible" => 1],
        ["field_key" => "hero_subtitle", "label" => "Subtitulo principal", "field_type" => "textarea", "default" => "Conoce nuestras areas de atencion especializadas, disenadas para acompanar cada etapa del cuidado de tu salud.", "default_visible" => 1],
        ["field_key" => "intro_title", "label" => "Titulo introductorio", "field_type" => "text", "default" => "Atencion integral por especialidad", "default_visible" => 1],
        ["field_key" => "intro_text", "label" => "Texto introductorio", "field_type" => "textarea", "default" => "Reunimos profesionales, tecnologia y procesos coordinados para brindar una experiencia clara, cercana y segura en cada servicio.", "default_visible" => 1],
        ["field_key" => "section_title", "label" => "Titulo de seccion", "field_type" => "text", "default" => "Nuestros departamentos", "default_visible" => 1],
        ["field_key" => "section_subtitle", "label" => "Subtitulo de seccion", "field_type" => "textarea", "default" => "Explora las principales areas de atencion disponibles para pacientes y familias.", "default_visible" => 1],
        ["field_key" => "cta_title", "label" => "Titulo CTA", "field_type" => "text", "default" => "Necesitas orientacion para elegir un servicio?", "default_visible" => 1],
        ["field_key" => "cta_text", "label" => "Texto CTA", "field_type" => "textarea", "default" => "Nuestro equipo puede ayudarte a identificar el departamento adecuado segun tus necesidades de atencion.", "default_visible" => 1],
        ["field_key" => "cta_button_text", "label" => "Texto boton CTA", "field_type" => "text", "default" => "Solicitar informacion", "default_visible" => 1],
        ["field_key" => "cta_button_link_type", "label" => "Tipo de enlace boton CTA", "field_type" => "text", "default" => "custom", "default_visible" => 1],
        ["field_key" => "cta_button_page_id", "label" => "Pagina interna boton CTA", "field_type" => "text", "default" => "", "default_visible" => 1],
        ["field_key" => "cta_button_url", "label" => "URL boton CTA", "field_type" => "url", "default" => "contact.html", "default_visible" => 1],
    ],
    "repeaters" => [
        [
            "repeater_key" => "departments",
            "label" => "Departamentos",
            "fields" => [
                ["field_key" => "icon_class", "label" => "Icono", "field_type" => "text", "default" => ""],
                ["field_key" => "title", "label" => "Titulo", "field_type" => "text", "default" => ""],
                ["field_key" => "description", "label" => "Descripcion", "field_type" => "textarea", "default" => ""],
                ["field_key" => "image", "label" => "Imagen", "field_type" => "image", "default" => ""],
                ["field_key" => "image_alt", "label" => "Texto alternativo imagen", "field_type" => "text", "default" => ""],
                ["field_key" => "button_text", "label" => "Texto del boton", "field_type" => "text", "default" => ""],
                ["field_key" => "button_link_type", "label" => "Tipo de enlace", "field_type" => "text", "default" => "custom"],
                ["field_key" => "button_page_id", "label" => "Pagina interna", "field_type" => "text", "default" => ""],
                ["field_key" => "button_url", "label" => "URL del boton", "field_type" => "url", "default" => ""],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Cardiologia", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-heart-pulse", "title" => "Cardiologia", "description" => "Evaluacion, diagnostico y seguimiento de condiciones del corazon y el sistema vascular.", "image" => "assets/img/health/cardiology-2.webp", "image_alt" => "Cardiologia", "button_text" => "Ver mas", "button_link_type" => "custom", "button_page_id" => "", "button_url" => "department-details.html"]],
                ["item_index" => 1, "item_label" => "Dermatologia", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-shield-plus", "title" => "Dermatologia", "description" => "Atencion especializada para el cuidado de la piel, prevencion y tratamiento dermatologico.", "image" => "assets/img/health/dermatology-3.webp", "image_alt" => "Dermatologia", "button_text" => "Ver mas", "button_link_type" => "custom", "button_page_id" => "", "button_url" => "department-details.html"]],
                ["item_index" => 2, "item_label" => "Neurologia", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-lightning-fill", "title" => "Neurologia", "description" => "Acompanamiento medico para condiciones del cerebro, nervios y sistema neurologico.", "image" => "assets/img/health/neurology-4.webp", "image_alt" => "Neurologia", "button_text" => "Ver mas", "button_link_type" => "custom", "button_page_id" => "", "button_url" => "department-details.html"]],
                ["item_index" => 3, "item_label" => "Ortopedia", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-bandaid", "title" => "Ortopedia", "description" => "Servicios orientados al cuidado de huesos, articulaciones, lesiones y movilidad.", "image" => "assets/img/health/orthopedics-4.webp", "image_alt" => "Ortopedia", "button_text" => "Ver mas", "button_link_type" => "custom", "button_page_id" => "", "button_url" => "department-details.html"]],
                ["item_index" => 4, "item_label" => "Pediatria", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-emoji-smile", "title" => "Pediatria", "description" => "Cuidado integral para ninos y adolescentes, con enfoque preventivo y familiar.", "image" => "assets/img/health/pediatrics-2.webp", "image_alt" => "Pediatria", "button_text" => "Ver mas", "button_link_type" => "custom", "button_page_id" => "", "button_url" => "department-details.html"]],
                ["item_index" => 5, "item_label" => "Urgencias", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-hospital", "title" => "Urgencias", "description" => "Respuesta oportuna para situaciones que requieren valoracion medica prioritaria.", "image" => "assets/img/health/emergency-2.webp", "image_alt" => "Urgencias", "button_text" => "Ver mas", "button_link_type" => "custom", "button_page_id" => "", "button_url" => "department-details.html"]],
            ],
        ],
    ],
];
