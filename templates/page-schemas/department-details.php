<?php

return [
    "template_key" => "department-details",
    "template_name" => "Detalles de departamento",
    "simple_fields" => [
        ["field_key" => "hero_title", "label" => "Titulo principal", "field_type" => "text", "default" => "Department Details", "default_visible" => 1],
        ["field_key" => "hero_subtitle", "label" => "Subtitulo principal", "field_type" => "textarea", "default" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", "default_visible" => 1],
        ["field_key" => "intro_title", "label" => "Intro titulo", "field_type" => "text", "default" => "Cardiology Department", "default_visible" => 1],
        ["field_key" => "intro_text", "label" => "Intro texto", "field_type" => "textarea", "default" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation.", "default_visible" => 1],
        ["field_key" => "overview_image", "label" => "Overview imagen", "field_type" => "image", "default" => "assets/img/health/cardiology-1.webp", "default_visible" => 1],
        ["field_key" => "overview_image_alt", "label" => "Overview imagen alt", "field_type" => "text", "default" => "Cardiology Department", "default_visible" => 1],
        ["field_key" => "experience_number", "label" => "Experiencia numero", "field_type" => "text", "default" => "15+", "default_visible" => 1],
        ["field_key" => "experience_text", "label" => "Experiencia texto", "field_type" => "text", "default" => "Years of Excellence", "default_visible" => 1],
        ["field_key" => "key_services_title", "label" => "Servicios clave titulo", "field_type" => "text", "default" => "Our Specialized Services", "default_visible" => 1],
        ["field_key" => "key_services_text", "label" => "Servicios clave texto", "field_type" => "textarea", "default" => "Donec rutrum congue leo eget malesuada. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Vestibulum ac diam sit amet quam vehicula.", "default_visible" => 1],
        ["field_key" => "cta_title", "label" => "CTA titulo", "field_type" => "text", "default" => "Expert Care When You Need It Most", "default_visible" => 1],
        ["field_key" => "cta_text", "label" => "CTA texto", "field_type" => "textarea", "default" => "Vivamus suscipit tortor eget felis porttitor volutpat. Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Proin eget tortor risus.", "default_visible" => 1],
        ["field_key" => "cta_primary_text", "label" => "CTA boton principal texto", "field_type" => "text", "default" => "Book Appointment", "default_visible" => 1],
        ["field_key" => "cta_primary_url", "label" => "CTA boton principal URL", "field_type" => "url", "default" => "appointment.html", "default_visible" => 1],
        ["field_key" => "cta_secondary_text", "label" => "CTA boton secundario texto", "field_type" => "text", "default" => "Learn More", "default_visible" => 1],
        ["field_key" => "cta_secondary_url", "label" => "CTA boton secundario URL", "field_type" => "url", "default" => "services.html", "default_visible" => 1],
        ["field_key" => "cta_image", "label" => "CTA imagen", "field_type" => "image", "default" => "assets/img/health/cardiology-3.webp", "default_visible" => 1],
        ["field_key" => "cta_image_alt", "label" => "CTA imagen alt", "field_type" => "text", "default" => "Cardiology Team", "default_visible" => 1],
    ],
    "repeaters" => [
        [
            "repeater_key" => "service_cards",
            "label" => "Service cards",
            "fields" => [
                ["field_key" => "icon_class", "label" => "Icono", "field_type" => "text", "default" => ""],
                ["field_key" => "title", "label" => "Titulo", "field_type" => "text", "default" => ""],
                ["field_key" => "text", "label" => "Texto", "field_type" => "textarea", "default" => ""],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Comprehensive Cardiac Care", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-heart-pulse", "title" => "Comprehensive Cardiac Care", "text" => "Pellentesque in ipsum id orci porta dapibus. Vivamus magna justo, lacinia eget consectetur sed, convallis at tellus."]],
                ["item_index" => 1, "item_label" => "Advanced Diagnostics", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-activity", "title" => "Advanced Diagnostics", "text" => "Curabitur arcu erat, accumsan id imperdiet et, porttitor at sem. Nulla porttitor accumsan tincidunt."]],
                ["item_index" => 2, "item_label" => "Personalized Treatment Plans", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-person-heart", "title" => "Personalized Treatment Plans", "text" => "Mauris blandit aliquet elit, eget tincidunt nibh pulvinar a. Vestibulum ac diam sit amet quam vehicula elementum."]],
            ],
        ],
        [
            "repeater_key" => "stats",
            "label" => "Stats",
            "fields" => [
                ["field_key" => "number", "label" => "Numero", "field_type" => "text", "default" => ""],
                ["field_key" => "label", "label" => "Label", "field_type" => "text", "default" => ""],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Patients Treated", "default_visible" => 1, "defaults" => ["number" => "2500", "label" => "Patients Treated"]],
                ["item_index" => 1, "item_label" => "Specialized Doctors", "default_visible" => 1, "defaults" => ["number" => "12", "label" => "Specialized Doctors"]],
                ["item_index" => 2, "item_label" => "Success Rate", "default_visible" => 1, "defaults" => ["number" => "98", "label" => "Success Rate"]],
                ["item_index" => 3, "item_label" => "Hours Service", "default_visible" => 1, "defaults" => ["number" => "24", "label" => "Hours Service"]],
            ],
        ],
        [
            "repeater_key" => "key_services",
            "label" => "Key services",
            "fields" => [
                ["field_key" => "text", "label" => "Texto", "field_type" => "text", "default" => ""],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Comprehensive cardiac evaluations", "default_visible" => 1, "defaults" => ["text" => "Comprehensive cardiac evaluations"]],
                ["item_index" => 1, "item_label" => "Advanced interventional procedures", "default_visible" => 1, "defaults" => ["text" => "Advanced interventional procedures"]],
                ["item_index" => 2, "item_label" => "Heart failure management plans", "default_visible" => 1, "defaults" => ["text" => "Heart failure management plans"]],
                ["item_index" => 3, "item_label" => "Preventive cardiology consultations", "default_visible" => 1, "defaults" => ["text" => "Preventive cardiology consultations"]],
                ["item_index" => 4, "item_label" => "Cardiac rehabilitation programs", "default_visible" => 1, "defaults" => ["text" => "Cardiac rehabilitation programs"]],
            ],
        ],
    ],
];
