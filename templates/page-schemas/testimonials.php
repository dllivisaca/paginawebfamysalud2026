<?php

return [
    "template_key" => "testimonials",
    "template_name" => "Testimonios",
    "simple_fields" => [
        ["field_key" => "hero_title", "label" => "Titulo principal", "field_type" => "text", "default" => "Testimonials", "default_visible" => 1],
        ["field_key" => "hero_subtitle", "label" => "Subtitulo principal", "field_type" => "textarea", "default" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", "default_visible" => 1],
    ],
    "repeaters" => [
        [
            "repeater_key" => "featured_testimonials",
            "label" => "Featured testimonials",
            "fields" => [
                ["field_key" => "title", "label" => "Titulo", "field_type" => "text", "default" => ""],
                ["field_key" => "text_1", "label" => "Texto 1", "field_type" => "textarea", "default" => ""],
                ["field_key" => "text_2", "label" => "Texto 2", "field_type" => "textarea", "default" => ""],
                ["field_key" => "profile_image", "label" => "Imagen perfil", "field_type" => "image", "default" => ""],
                ["field_key" => "featured_image", "label" => "Imagen destacada", "field_type" => "image", "default" => ""],
                ["field_key" => "image_alt", "label" => "Imagen alt", "field_type" => "text", "default" => ""],
                ["field_key" => "name", "label" => "Nombre", "field_type" => "text", "default" => ""],
                ["field_key" => "role", "label" => "Rol", "field_type" => "text", "default" => ""],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Saul Goodman", "default_visible" => 1, "defaults" => ["title" => "Sed ut perspiciatis unde omnis", "text_1" => "Proin iaculis purus consequat sem cure digni ssim donec porttitora entum suscipit rhoncus. Accusantium quam, ultricies eget id, aliquam eget nibh et. Maecen aliquam, risus at semper.", "text_2" => "Beatae magnam dolore quia ipsum. Voluptatem totam et qui dolore dignissimos. Amet quia sapiente laudantium nihil illo et assumenda sit cupiditate. Nam perspiciatis perferendis minus consequatur. Enim ut eos quo.", "profile_image" => "assets/img/person/person-m-7.webp", "featured_image" => "assets/img/person/person-m-7.webp", "image_alt" => "", "name" => "Saul Goodman", "role" => "Client"]],
                ["item_index" => 1, "item_label" => "Sara Wilsson", "default_visible" => 1, "defaults" => ["title" => "Nemo enim ipsam voluptatem", "text_1" => "Export tempor illum tamen malis malis eram quae irure esse labore quem cillum quid cillum eram malis quorum velit fore eram velit sunt aliqua noster fugiat irure amet legam anim culpa.", "text_2" => "Dolorem excepturi esse qui amet maxime quibusdam aut repellendus voluptatum. Corrupti enim a repellat cumque est laborum fuga consequuntur. Dolorem nostrum deleniti quas voluptatem iure dolorum rerum. Repudiandae doloribus ut repellat harum vero aut. Modi aut velit aperiam aspernatur odit ut vitae.", "profile_image" => "assets/img/person/person-f-8.webp", "featured_image" => "assets/img/person/person-f-8.webp", "image_alt" => "", "name" => "Sara Wilsson", "role" => "Designer"]],
                ["item_index" => 2, "item_label" => "Matt Brandon", "default_visible" => 1, "defaults" => ["title" => "Labore nostrum eos impedit", "text_1" => "Fugiat enim eram quae cillum dolore dolor amet nulla culpa multos export minim fugiat minim velit minim dolor enim duis veniam ipsum anim magna sunt elit fore quem dolore labore illum veniam.", "text_2" => "Itaque ut explicabo vero occaecati est quam rerum sed. Numquam tempora aut aut quaerat quia illum. Nobis quia autem odit ipsam numquam. Doloribus sit sint corporis eius totam fuga. Hic nostrum suscipit corrupti nam expedita adipisci aut optio.", "profile_image" => "assets/img/person/person-m-9.webp", "featured_image" => "assets/img/person/person-m-9.webp", "image_alt" => "", "name" => "Matt Brandon", "role" => "Freelancer"]],
                ["item_index" => 3, "item_label" => "Jena Karlis", "default_visible" => 1, "defaults" => ["title" => "Impedit dolor facilis nulla", "text_1" => "Enim nisi quem export duis labore cillum quae magna enim sint quorum nulla quem veniam duis minim tempor labore quem eram duis noster aute amet eram fore quis sint minim.", "text_2" => "Omnis aspernatur accusantium qui delectus praesentium repellendus. Facilis sint odio aspernatur voluptas commodi qui qui qui pariatur. Corrupti deleniti itaque quaerat ipsum deleniti culpa tempora tempore. Et consequatur exercitationem hic aspernatur nobis est voluptatibus architecto laborum.", "profile_image" => "assets/img/person/person-f-10.webp", "featured_image" => "assets/img/person/person-f-10.webp", "image_alt" => "", "name" => "Jena Karlis", "role" => "Store Owner"]],
            ],
        ],
        [
            "repeater_key" => "testimonials",
            "label" => "Testimonials",
            "fields" => [
                ["field_key" => "rating", "label" => "Rating", "field_type" => "text", "default" => "5"],
                ["field_key" => "text", "label" => "Texto", "field_type" => "textarea", "default" => ""],
                ["field_key" => "image", "label" => "Imagen", "field_type" => "image", "default" => ""],
                ["field_key" => "image_alt", "label" => "Imagen alt", "field_type" => "text", "default" => "Author"],
                ["field_key" => "name", "label" => "Nombre", "field_type" => "text", "default" => ""],
                ["field_key" => "role", "label" => "Rol", "field_type" => "text", "default" => ""],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Michael Anderson", "default_visible" => 1, "defaults" => ["rating" => "5", "text" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce posuere metus vitae arcu imperdiet, id aliquet ante scelerisque. Sed sit amet sem vitae urna fringilla tempus.", "image" => "assets/img/person/person-m-3.webp", "image_alt" => "Author", "name" => "Michael Anderson", "role" => "Software Developer"]],
                ["item_index" => 1, "item_label" => "Sophia Martinez", "default_visible" => 1, "defaults" => ["rating" => "5", "text" => "Cras fermentum odio eu feugiat lide par naso tierra. Justo eget nada terra videa magna derita valies darta donna mare fermentum iaculis eu non diam phasellus.", "image" => "assets/img/person/person-f-5.webp", "image_alt" => "Author", "name" => "Sophia Martinez", "role" => "Marketing Specialist"]],
                ["item_index" => 2, "item_label" => "David Wilson", "default_visible" => 1, "defaults" => ["rating" => "5", "text" => "Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum.", "image" => "assets/img/person/person-m-7.webp", "image_alt" => "Author", "name" => "David Wilson", "role" => "Graphic Designer"]],
                ["item_index" => 3, "item_label" => "Emily Johnson", "default_visible" => 1, "defaults" => ["rating" => "5", "text" => "Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis.", "image" => "assets/img/person/person-f-9.webp", "image_alt" => "Author", "name" => "Emily Johnson", "role" => "UX Designer"]],
                ["item_index" => 4, "item_label" => "Olivia Thompson", "default_visible" => 1, "defaults" => ["rating" => "5", "text" => "Praesent nonummy mi in odio. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices.", "image" => "assets/img/person/person-f-11.webp", "image_alt" => "Author", "name" => "Olivia Thompson", "role" => "Entrepreneur"]],
                ["item_index" => 5, "item_label" => "James Taylor", "default_visible" => 1, "defaults" => ["rating" => "5", "text" => "Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium.", "image" => "assets/img/person/person-m-12.webp", "image_alt" => "Author", "name" => "James Taylor", "role" => "Financial Analyst"]],
            ],
        ],
    ],
];
