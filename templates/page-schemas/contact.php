<?php

return [
    "template_key" => "contact",
    "template_name" => "Contacto",
    "simple_fields" => [
        ["field_key" => "hero_title", "label" => "Titulo principal", "field_type" => "text", "default" => "Contact", "default_visible" => 1],
        ["field_key" => "hero_subtitle", "label" => "Subtitulo principal", "field_type" => "textarea", "default" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", "default_visible" => 1],
        ["field_key" => "info_title", "label" => "Informacion titulo", "field_type" => "text", "default" => "Contact Information", "default_visible" => 1],
        ["field_key" => "info_text", "label" => "Informacion texto", "field_type" => "textarea", "default" => "Dignissimos deleniti accusamus rerum voluptate. Dignissimos rerum sit maiores reiciendis voluptate inventore ut.", "default_visible" => 1],
        ["field_key" => "social_title", "label" => "Redes titulo", "field_type" => "text", "default" => "Follow Us", "default_visible" => 1],
        ["field_key" => "map_embed_url", "label" => "Mapa embed URL", "field_type" => "url", "default" => "https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d48389.78314118045!2d-74.006138!3d40.710059!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a22a3bda30d%3A0xb89d1fe6bc499443!2sDowntown%20Conference%20Center!5e0!3m2!1sen!2sus!4v1676961268712!5m2!1sen!2sus", "default_visible" => 1],
        ["field_key" => "form_title", "label" => "Formulario titulo", "field_type" => "text", "default" => "Send Us a Message", "default_visible" => 1],
        ["field_key" => "form_text", "label" => "Formulario texto", "field_type" => "textarea", "default" => "Lorem ipsum dolor sit amet consectetur adipiscing elit mauris hendrerit faucibus imperdiet nec eget felis.", "default_visible" => 1],
        ["field_key" => "form_name_label", "label" => "Formulario nombre label", "field_type" => "text", "default" => "Full Name", "default_visible" => 1],
        ["field_key" => "form_email_label", "label" => "Formulario email label", "field_type" => "text", "default" => "Email Address", "default_visible" => 1],
        ["field_key" => "form_subject_label", "label" => "Formulario asunto label", "field_type" => "text", "default" => "Subject", "default_visible" => 1],
        ["field_key" => "form_message_label", "label" => "Formulario mensaje label", "field_type" => "text", "default" => "Your Message", "default_visible" => 1],
        ["field_key" => "form_loading_text", "label" => "Formulario loading texto", "field_type" => "text", "default" => "Loading", "default_visible" => 1],
        ["field_key" => "form_sent_text", "label" => "Formulario mensaje enviado", "field_type" => "text", "default" => "Your message has been sent. Thank you!", "default_visible" => 1],
        ["field_key" => "form_button_text", "label" => "Formulario boton texto", "field_type" => "text", "default" => "Send Message", "default_visible" => 1],
    ],
    "repeaters" => [
        [
            "repeater_key" => "info_cards",
            "label" => "Tarjetas de informacion",
            "fields" => [
                ["field_key" => "icon_class", "label" => "Icono", "field_type" => "text", "default" => ""],
                ["field_key" => "title", "label" => "Titulo", "field_type" => "text", "default" => ""],
                ["field_key" => "text", "label" => "Texto", "field_type" => "text", "default" => ""],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Our Location", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-pin-map-fill", "title" => "Our Location", "text" => "4952 Hilltop Dr, Anytown, CA 90210"]],
                ["item_index" => 1, "item_label" => "Email Us", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-envelope-open", "title" => "Email Us", "text" => "info@example.com"]],
                ["item_index" => 2, "item_label" => "Call Us", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-telephone-fill", "title" => "Call Us", "text" => "+1 (555) 123-4567"]],
                ["item_index" => 3, "item_label" => "Working Hours", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-clock-history", "title" => "Working Hours", "text" => "Monday-Saturday: 9AM - 7PM"]],
            ],
        ],
        [
            "repeater_key" => "social_links",
            "label" => "Redes sociales",
            "fields" => [
                ["field_key" => "icon_class", "label" => "Icono", "field_type" => "text", "default" => ""],
                ["field_key" => "url", "label" => "URL", "field_type" => "url", "default" => "#"],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Facebook", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-facebook", "url" => "#"]],
                ["item_index" => 1, "item_label" => "Twitter X", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-twitter-x", "url" => "#"]],
                ["item_index" => 2, "item_label" => "Instagram", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-instagram", "url" => "#"]],
                ["item_index" => 3, "item_label" => "LinkedIn", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-linkedin", "url" => "#"]],
                ["item_index" => 4, "item_label" => "YouTube", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-youtube", "url" => "#"]],
            ],
        ],
    ],
];
