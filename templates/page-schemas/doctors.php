<?php

return [
    "template_key" => "doctors",
    "template_name" => "Doctores",
    "simple_fields" => [
        ["field_key" => "hero_title", "label" => "Titulo principal", "field_type" => "text", "default" => "Doctors", "default_visible" => 1],
        ["field_key" => "hero_subtitle", "label" => "Subtitulo principal", "field_type" => "textarea", "default" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", "default_visible" => 1],
    ],
    "repeaters" => [
        [
            "repeater_key" => "doctors",
            "label" => "Doctores",
            "fields" => [
                ["field_key" => "image", "label" => "Imagen", "field_type" => "image", "default" => ""],
                ["field_key" => "image_alt", "label" => "Texto alternativo imagen", "field_type" => "text", "default" => ""],
                ["field_key" => "name", "label" => "Nombre", "field_type" => "text", "default" => ""],
                ["field_key" => "specialty", "label" => "Especialidad", "field_type" => "text", "default" => ""],
                ["field_key" => "bio", "label" => "Biografia", "field_type" => "textarea", "default" => ""],
                ["field_key" => "experience", "label" => "Experiencia", "field_type" => "text", "default" => ""],
                ["field_key" => "linkedin_url", "label" => "LinkedIn URL", "field_type" => "url", "default" => "#"],
                ["field_key" => "twitter_url", "label" => "Twitter URL", "field_type" => "url", "default" => "#"],
                ["field_key" => "email_url", "label" => "Email URL", "field_type" => "url", "default" => "#"],
                ["field_key" => "button_text", "label" => "Texto del boton", "field_type" => "text", "default" => "Book Appointment"],
                ["field_key" => "button_link_type", "label" => "Tipo de enlace", "field_type" => "text", "default" => "custom"],
                ["field_key" => "button_page_id", "label" => "Pagina interna", "field_type" => "text", "default" => ""],
                ["field_key" => "button_url", "label" => "URL del boton", "field_type" => "url", "default" => "appointment.html"],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Dr. Jennifer Martinez", "default_visible" => 1, "defaults" => ["image" => "assets/img/health/staff-3.webp", "image_alt" => "Dr. Jennifer Martinez", "name" => "Dr. Jennifer Martinez", "specialty" => "Chief of Cardiology", "bio" => "Mauris blandit aliquet elit eget tincidunt nibh pulvinar a. Curabitur arcu erat accumsan id imperdiet et porttitor at.", "experience" => "15+ Years Experience", "linkedin_url" => "#", "twitter_url" => "#", "email_url" => "#", "button_text" => "Book Appointment", "button_url" => "appointment.html"]],
                ["item_index" => 1, "item_label" => "Dr. Michael Chen", "default_visible" => 1, "defaults" => ["image" => "assets/img/health/staff-7.webp", "image_alt" => "Dr. Michael Chen", "name" => "Dr. Michael Chen", "specialty" => "Orthopedic Surgeon", "bio" => "Vestibulum ac diam sit amet quam vehicula elementum sed sit amet dui. Nulla quis lorem ut libero malesuada feugiat.", "experience" => "12+ Years Experience", "linkedin_url" => "#", "twitter_url" => "#", "email_url" => "#", "button_text" => "Book Appointment", "button_url" => "appointment.html"]],
                ["item_index" => 2, "item_label" => "Dr. Sarah Williams", "default_visible" => 1, "defaults" => ["image" => "assets/img/health/staff-11.webp", "image_alt" => "Dr. Sarah Williams", "name" => "Dr. Sarah Williams", "specialty" => "Pediatric Specialist", "bio" => "Donec rutrum congue leo eget malesuada. Sed porttitor lectus nibh. Curabitur non nulla sit amet nisl tempus convallis.", "experience" => "18+ Years Experience", "linkedin_url" => "#", "twitter_url" => "#", "email_url" => "#", "button_text" => "Book Appointment", "button_url" => "appointment.html"]],
                ["item_index" => 3, "item_label" => "Dr. Robert Anderson", "default_visible" => 1, "defaults" => ["image" => "assets/img/health/staff-14.webp", "image_alt" => "Dr. Robert Anderson", "name" => "Dr. Robert Anderson", "specialty" => "Neurologist", "bio" => "Proin eget tortor risus. Pellentesque in ipsum id orci porta dapibus. Mauris blandit aliquet elit eget tincidunt.", "experience" => "20+ Years Experience", "linkedin_url" => "#", "twitter_url" => "#", "email_url" => "#", "button_text" => "Book Appointment", "button_url" => "appointment.html"]],
                ["item_index" => 4, "item_label" => "Dr. Lisa Thompson", "default_visible" => 1, "defaults" => ["image" => "assets/img/health/staff-5.webp", "image_alt" => "Dr. Lisa Thompson", "name" => "Dr. Lisa Thompson", "specialty" => "Emergency Medicine", "bio" => "Vivamus magna justo lacinia eget consectetur sed convallis at tellus. Quisque velit nisi pretium ut lacinia in elementum.", "experience" => "14+ Years Experience", "linkedin_url" => "#", "twitter_url" => "#", "email_url" => "#", "button_text" => "Book Appointment", "button_url" => "appointment.html"]],
                ["item_index" => 5, "item_label" => "Dr. David Rodriguez", "default_visible" => 1, "defaults" => ["image" => "assets/img/health/staff-9.webp", "image_alt" => "Dr. David Rodriguez", "name" => "Dr. David Rodriguez", "specialty" => "Dermatologist", "bio" => "Cras ultricies ligula sed magna dictum porta. Lorem ipsum dolor sit amet consectetur adipiscing elit pellentesque habitant.", "experience" => "16+ Years Experience", "linkedin_url" => "#", "twitter_url" => "#", "email_url" => "#", "button_text" => "Book Appointment", "button_url" => "appointment.html"]],
                ["item_index" => 6, "item_label" => "Dr. Amanda Clark", "default_visible" => 1, "defaults" => ["image" => "assets/img/health/staff-2.webp", "image_alt" => "Dr. Amanda Clark", "name" => "Dr. Amanda Clark", "specialty" => "Oncologist", "bio" => "Sed porttitor lectus nibh. Curabitur aliquet quam id dui posuere blandit proin eget tortor risus pellentesque habitant.", "experience" => "22+ Years Experience", "linkedin_url" => "#", "twitter_url" => "#", "email_url" => "#", "button_text" => "Book Appointment", "button_url" => "appointment.html"]],
                ["item_index" => 7, "item_label" => "Dr. James Wilson", "default_visible" => 1, "defaults" => ["image" => "assets/img/health/staff-12.webp", "image_alt" => "Dr. James Wilson", "name" => "Dr. James Wilson", "specialty" => "General Surgery", "bio" => "Nulla porttitor accumsan tincidunt. Mauris blandit aliquet elit eget tincidunt nibh pulvinar a curabitur arcu erat accumsan.", "experience" => "19+ Years Experience", "linkedin_url" => "#", "twitter_url" => "#", "email_url" => "#", "button_text" => "Book Appointment", "button_url" => "appointment.html"]],
            ],
        ],
    ],
];
