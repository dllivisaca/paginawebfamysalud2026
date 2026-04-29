<?php

return [
    "template_key" => "appointment",
    "template_name" => "Agenda tu cita",
    "simple_fields" => [
        ["field_key" => "hero_title", "label" => "Titulo principal", "field_type" => "text", "default" => "Appointment", "default_visible" => 1],
        ["field_key" => "hero_subtitle", "label" => "Subtitulo principal", "field_type" => "textarea", "default" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.", "default_visible" => 1],
        ["field_key" => "info_title", "label" => "Informacion titulo", "field_type" => "text", "default" => "Quick & Easy Online Booking", "default_visible" => 1],
        ["field_key" => "info_text", "label" => "Informacion texto", "field_type" => "textarea", "default" => "Book your appointment in just a few simple steps. Our healthcare professionals are ready to provide you with the best medical care tailored to your needs.", "default_visible" => 1],
        ["field_key" => "emergency_title", "label" => "Emergencia titulo", "field_type" => "text", "default" => "Emergency Hotline", "default_visible" => 1],
        ["field_key" => "emergency_icon", "label" => "Emergencia icono", "field_type" => "text", "default" => "bi bi-telephone-fill me-2", "default_visible" => 1],
        ["field_key" => "emergency_text", "label" => "Emergencia texto", "field_type" => "textarea", "default" => "Call <strong>+1 (555) 911-4567</strong> for urgent medical assistance", "default_visible" => 1],
        ["field_key" => "name_placeholder", "label" => "Nombre placeholder", "field_type" => "text", "default" => "Your Full Name", "default_visible" => 1],
        ["field_key" => "email_placeholder", "label" => "Email placeholder", "field_type" => "text", "default" => "Your Email", "default_visible" => 1],
        ["field_key" => "phone_placeholder", "label" => "Telefono placeholder", "field_type" => "text", "default" => "Your Phone Number", "default_visible" => 1],
        ["field_key" => "department_placeholder", "label" => "Departamento placeholder", "field_type" => "text", "default" => "Select Department", "default_visible" => 1],
        ["field_key" => "doctor_placeholder", "label" => "Doctor placeholder", "field_type" => "text", "default" => "Select Doctor", "default_visible" => 1],
        ["field_key" => "message_placeholder", "label" => "Mensaje placeholder", "field_type" => "textarea", "default" => "Please describe your symptoms or reason for visit (optional)", "default_visible" => 1],
        ["field_key" => "loading_text", "label" => "Loading texto", "field_type" => "text", "default" => "Loading", "default_visible" => 1],
        ["field_key" => "sent_message", "label" => "Mensaje enviado", "field_type" => "textarea", "default" => "Your appointment request has been sent successfully. We will contact you shortly!", "default_visible" => 1],
        ["field_key" => "button_text", "label" => "Boton texto", "field_type" => "text", "default" => "Book Appointment", "default_visible" => 1],
        ["field_key" => "button_icon", "label" => "Boton icono", "field_type" => "text", "default" => "bi bi-calendar-plus me-2", "default_visible" => 1],
    ],
    "repeaters" => [
        [
            "repeater_key" => "info_items",
            "label" => "Info items",
            "fields" => [
                ["field_key" => "icon_class", "label" => "Icono", "field_type" => "text", "default" => ""],
                ["field_key" => "title", "label" => "Titulo", "field_type" => "text", "default" => ""],
                ["field_key" => "text", "label" => "Texto", "field_type" => "text", "default" => ""],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Flexible Scheduling", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-calendar-check", "title" => "Flexible Scheduling", "text" => "Choose from available time slots that fit your busy schedule"]],
                ["item_index" => 1, "item_label" => "Quick Response", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-stopwatch", "title" => "Quick Response", "text" => "Get confirmation within 15 minutes of submitting your request"]],
                ["item_index" => 2, "item_label" => "Expert Medical Care", "default_visible" => 1, "defaults" => ["icon_class" => "bi bi-shield-check", "title" => "Expert Medical Care", "text" => "Board-certified doctors and specialists at your service"]],
            ],
        ],
        [
            "repeater_key" => "departments",
            "label" => "Departamentos",
            "fields" => [
                ["field_key" => "value", "label" => "Valor", "field_type" => "text", "default" => ""],
                ["field_key" => "label", "label" => "Label", "field_type" => "text", "default" => ""],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Cardiology", "default_visible" => 1, "defaults" => ["value" => "cardiology", "label" => "Cardiology"]],
                ["item_index" => 1, "item_label" => "Neurology", "default_visible" => 1, "defaults" => ["value" => "neurology", "label" => "Neurology"]],
                ["item_index" => 2, "item_label" => "Orthopedics", "default_visible" => 1, "defaults" => ["value" => "orthopedics", "label" => "Orthopedics"]],
                ["item_index" => 3, "item_label" => "Pediatrics", "default_visible" => 1, "defaults" => ["value" => "pediatrics", "label" => "Pediatrics"]],
                ["item_index" => 4, "item_label" => "Dermatology", "default_visible" => 1, "defaults" => ["value" => "dermatology", "label" => "Dermatology"]],
                ["item_index" => 5, "item_label" => "General Medicine", "default_visible" => 1, "defaults" => ["value" => "general", "label" => "General Medicine"]],
            ],
        ],
        [
            "repeater_key" => "doctors",
            "label" => "Doctores",
            "fields" => [
                ["field_key" => "value", "label" => "Valor", "field_type" => "text", "default" => ""],
                ["field_key" => "label", "label" => "Label", "field_type" => "text", "default" => ""],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Dr. Sarah Johnson", "default_visible" => 1, "defaults" => ["value" => "dr-johnson", "label" => "Dr. Sarah Johnson"]],
                ["item_index" => 1, "item_label" => "Dr. Michael Martinez", "default_visible" => 1, "defaults" => ["value" => "dr-martinez", "label" => "Dr. Michael Martinez"]],
                ["item_index" => 2, "item_label" => "Dr. Lisa Chen", "default_visible" => 1, "defaults" => ["value" => "dr-chen", "label" => "Dr. Lisa Chen"]],
                ["item_index" => 3, "item_label" => "Dr. Raj Patel", "default_visible" => 1, "defaults" => ["value" => "dr-patel", "label" => "Dr. Raj Patel"]],
                ["item_index" => 4, "item_label" => "Dr. Emily Williams", "default_visible" => 1, "defaults" => ["value" => "dr-williams", "label" => "Dr. Emily Williams"]],
                ["item_index" => 5, "item_label" => "Dr. David Thompson", "default_visible" => 1, "defaults" => ["value" => "dr-thompson", "label" => "Dr. David Thompson"]],
            ],
        ],
        [
            "repeater_key" => "process_steps",
            "label" => "Process steps",
            "fields" => [
                ["field_key" => "number", "label" => "Numero", "field_type" => "text", "default" => ""],
                ["field_key" => "icon_class", "label" => "Icono", "field_type" => "text", "default" => ""],
                ["field_key" => "title", "label" => "Titulo", "field_type" => "text", "default" => ""],
                ["field_key" => "text", "label" => "Texto", "field_type" => "text", "default" => ""],
            ],
            "items" => [
                ["item_index" => 0, "item_label" => "Fill Details", "default_visible" => 1, "defaults" => ["number" => "1", "icon_class" => "bi bi-person-fill", "title" => "Fill Details", "text" => "Provide your personal information and select your preferred department"]],
                ["item_index" => 1, "item_label" => "Choose Date", "default_visible" => 1, "defaults" => ["number" => "2", "icon_class" => "bi bi-calendar-event", "title" => "Choose Date", "text" => "Select your preferred date and time slot from available options"]],
                ["item_index" => 2, "item_label" => "Confirmation", "default_visible" => 1, "defaults" => ["number" => "3", "icon_class" => "bi bi-check-circle", "title" => "Confirmation", "text" => "Receive instant confirmation and appointment details via email or SMS"]],
                ["item_index" => 3, "item_label" => "Get Treatment", "default_visible" => 1, "defaults" => ["number" => "4", "icon_class" => "bi bi-heart-pulse", "title" => "Get Treatment", "text" => "Visit our clinic at your scheduled time and receive quality healthcare"]],
            ],
        ],
    ],
];
