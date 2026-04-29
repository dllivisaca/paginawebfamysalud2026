<?php

return [
    "template_key" => "faq",
    "template_name" => "Preguntas frecuentes",
    "simple_fields" => [
        ["field_key" => "hero_title", "label" => "Titulo principal", "field_type" => "text", "default" => "Frequenty Asked Questions", "default_visible" => 1],
        ["field_key" => "hero_subtitle", "label" => "Subtitulo principal", "field_type" => "textarea", "default" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", "default_visible" => 1],
        ["field_key" => "contact_icon", "label" => "Contacto icono", "field_type" => "text", "default" => "bi bi-question-circle", "default_visible" => 1],
        ["field_key" => "contact_title", "label" => "Contacto titulo", "field_type" => "text", "default" => "Still Have Questions?", "default_visible" => 1],
        ["field_key" => "contact_text", "label" => "Contacto texto", "field_type" => "textarea", "default" => "Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula. Vestibulum ac diam sit amet quam vehicula elementum.", "default_visible" => 1],
    ],
    "repeaters" => [
        [
            "repeater_key" => "contact_options",
            "label" => "Contact options",
            "fields" => [
                ["field_key" => "icon_class", "label" => "Icono", "field_type" => "text", "default" => ""],
                ["field_key" => "label", "label" => "Label", "field_type" => "text", "default" => ""],
                ["field_key" => "url", "label" => "URL", "field_type" => "url", "default" => "#"],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Email Support", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-envelope", "label" => "Email Support", "url" => "#"]],
                ["item_index" => 1, "item_label" => "Live Chat", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-chat-dots", "label" => "Live Chat", "url" => "#"]],
                ["item_index" => 2, "item_label" => "Call Us", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-telephone", "label" => "Call Us", "url" => "#"]],
            ],
        ],
        [
            "repeater_key" => "faq_items",
            "label" => "FAQ items",
            "fields" => [
                ["field_key" => "question", "label" => "Pregunta", "field_type" => "text", "default" => ""],
                ["field_key" => "answer", "label" => "Respuesta", "field_type" => "textarea", "default" => ""],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Vivamus suscipit tortor eget felis porttitor volutpat?", "default_visible" => 1, "defaults" => ["question" => "Vivamus suscipit tortor eget felis porttitor volutpat?", "answer" => "Nulla quis lorem ut libero malesuada feugiat. Vestibulum ac diam sit amet quam vehicula elementum sed sit amet dui. Curabitur aliquet quam id dui posuere blandit. Nulla porttitor accumsan tincidunt."]],
                ["item_index" => 1, "item_label" => "Curabitur aliquet quam id dui posuere blandit?", "default_visible" => 1, "defaults" => ["question" => "Curabitur aliquet quam id dui posuere blandit?", "answer" => "Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel, ullamcorper sit amet ligula. Proin eget tortor risus. Mauris blandit aliquet elit, eget tincidunt nibh pulvinar."]],
                ["item_index" => 2, "item_label" => "Sed porttitor lectus nibh ullamcorper sit amet?", "default_visible" => 1, "defaults" => ["question" => "Sed porttitor lectus nibh ullamcorper sit amet?", "answer" => "Curabitur non nulla sit amet nisl tempus convallis quis ac lectus. Praesent sapien massa, convallis a pellentesque nec, egestas non nisi. Donec sollicitudin molestie malesuada. Vestibulum ac diam sit amet quam vehicula elementum."]],
                ["item_index" => 3, "item_label" => "Nulla quis lorem ut libero malesuada feugiat?", "default_visible" => 1, "defaults" => ["question" => "Nulla quis lorem ut libero malesuada feugiat?", "answer" => "Donec sollicitudin molestie malesuada. Quisque velit nisi, pretium ut lacinia in, elementum id enim. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec velit neque, auctor sit amet aliquam vel."]],
            ],
        ],
    ],
];
