<?php

return [
    "template_key" => "gallery",
    "template_name" => "Galeria",
    "simple_fields" => [
        ["field_key" => "hero_title", "label" => "Titulo principal", "field_type" => "text", "default" => "Gallery", "default_visible" => 1],
        ["field_key" => "hero_subtitle", "label" => "Subtitulo principal", "field_type" => "textarea", "default" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", "default_visible" => 1],
    ],
    "repeaters" => [
        [
            "repeater_key" => "gallery_filters",
            "label" => "Gallery filters",
            "fields" => [
                ["field_key" => "filter", "label" => "Filtro", "field_type" => "text", "default" => ""],
                ["field_key" => "label", "label" => "Label", "field_type" => "text", "default" => ""],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "All", "default_visible" => 1, "defaults" => ["filter" => "*", "label" => "All"]],
                ["item_index" => 1, "item_label" => "Nature", "default_visible" => 1, "defaults" => ["filter" => ".filter-nature", "label" => "Nature"]],
                ["item_index" => 2, "item_label" => "Architecture", "default_visible" => 1, "defaults" => ["filter" => ".filter-architecture", "label" => "Architecture"]],
                ["item_index" => 3, "item_label" => "People", "default_visible" => 1, "defaults" => ["filter" => ".filter-people", "label" => "People"]],
                ["item_index" => 4, "item_label" => "Travel", "default_visible" => 1, "defaults" => ["filter" => ".filter-travel", "label" => "Travel"]],
            ],
        ],
        [
            "repeater_key" => "gallery_items",
            "label" => "Gallery items",
            "fields" => [
                ["field_key" => "filter_class", "label" => "Clase filtro", "field_type" => "text", "default" => ""],
                ["field_key" => "image", "label" => "Imagen", "field_type" => "image", "default" => ""],
                ["field_key" => "image_alt", "label" => "Imagen alt", "field_type" => "text", "default" => "Gallery Image"],
                ["field_key" => "lightbox_image", "label" => "Lightbox imagen", "field_type" => "image", "default" => ""],
                ["field_key" => "title", "label" => "Titulo", "field_type" => "text", "default" => ""],
                ["field_key" => "description", "label" => "Descripcion", "field_type" => "textarea", "default" => ""],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Nature Exploration", "default_visible" => 1, "defaults" => ["filter_class" => "filter-nature", "image" => "assets/img/gallery/gallery-1.webp", "image_alt" => "Gallery Image", "lightbox_image" => "assets/img/gallery/gallery-1.webp", "title" => "Nature Exploration", "description" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit."]],
                ["item_index" => 1, "item_label" => "Modern Architecture", "default_visible" => 1, "defaults" => ["filter_class" => "filter-architecture", "image" => "assets/img/gallery/gallery-2.webp", "image_alt" => "Gallery Image", "lightbox_image" => "assets/img/gallery/gallery-2.webp", "title" => "Modern Architecture", "description" => "Praesent commodo cursus magna, vel scelerisque nisl consectetur."]],
                ["item_index" => 2, "item_label" => "Urban Life", "default_visible" => 1, "defaults" => ["filter_class" => "filter-people", "image" => "assets/img/gallery/gallery-3.webp", "image_alt" => "Gallery Image", "lightbox_image" => "assets/img/gallery/gallery-3.webp", "title" => "Urban Life", "description" => "Fusce dapibus, tellus ac cursus commodo, tortor mauris."]],
                ["item_index" => 3, "item_label" => "Travel Destinations", "default_visible" => 1, "defaults" => ["filter_class" => "filter-travel", "image" => "assets/img/gallery/gallery-4.webp", "image_alt" => "Gallery Image", "lightbox_image" => "assets/img/gallery/gallery-4.webp", "title" => "Travel Destinations", "description" => "Aenean lacinia bibendum nulla sed consectetur."]],
                ["item_index" => 4, "item_label" => "Natural Wonders", "default_visible" => 1, "defaults" => ["filter_class" => "filter-nature", "image" => "assets/img/gallery/gallery-5.webp", "image_alt" => "Gallery Image", "lightbox_image" => "assets/img/gallery/gallery-5.webp", "title" => "Natural Wonders", "description" => "Cras mattis consectetur purus sit amet fermentum."]],
                ["item_index" => 5, "item_label" => "Historic Buildings", "default_visible" => 1, "defaults" => ["filter_class" => "filter-architecture", "image" => "assets/img/gallery/gallery-6.webp", "image_alt" => "Gallery Image", "lightbox_image" => "assets/img/gallery/gallery-6.webp", "title" => "Historic Buildings", "description" => "Nullam id dolor id nibh ultricies vehicula ut id elit."]],
                ["item_index" => 6, "item_label" => "Community Events", "default_visible" => 1, "defaults" => ["filter_class" => "filter-people", "image" => "assets/img/gallery/gallery-7.webp", "image_alt" => "Gallery Image", "lightbox_image" => "assets/img/gallery/gallery-7.webp", "title" => "Community Events", "description" => "Donec ullamcorper nulla non metus auctor fringilla."]],
                ["item_index" => 7, "item_label" => "Exotic Locations", "default_visible" => 1, "defaults" => ["filter_class" => "filter-travel", "image" => "assets/img/gallery/gallery-8.webp", "image_alt" => "Gallery Image", "lightbox_image" => "assets/img/gallery/gallery-8.webp", "title" => "Exotic Locations", "description" => "Sed posuere consectetur est at lobortis."]],
            ],
        ],
    ],
];
