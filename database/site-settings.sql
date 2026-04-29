CREATE TABLE IF NOT EXISTS site_settings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    site_name VARCHAR(150) NULL,
    site_logo_path VARCHAR(255) NULL,
    footer_about_text TEXT NULL,
    footer_copyright VARCHAR(255) NULL,
    facebook_url VARCHAR(255) NULL,
    instagram_url VARCHAR(255) NULL,
    twitter_url VARCHAR(255) NULL,
    linkedin_url VARCHAR(255) NULL,
    youtube_url VARCHAR(255) NULL,
    background_color VARCHAR(20) NULL,
    default_color VARCHAR(20) NULL,
    heading_color VARCHAR(20) NULL,
    accent_color VARCHAR(20) NULL,
    nav_color VARCHAR(20) NULL,
    nav_hover_color VARCHAR(20) NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_settings (
    id,
    site_name,
    site_logo_path,
    footer_about_text,
    footer_copyright,
    facebook_url,
    instagram_url,
    twitter_url,
    linkedin_url,
    youtube_url,
    background_color,
    default_color,
    heading_color,
    accent_color,
    nav_color,
    nav_hover_color
) VALUES (
    1,
    'FamySalud',
    '',
    CONCAT(
        'Atención médica con enfoque humano, cercano y profesional.',
        CHAR(10),
        'Información institucional y canales de contacto en proceso de actualización.'
    ),
    'Todos los derechos reservados',
    '#',
    '#',
    '#',
    '#',
    '',
    '#ffffff',
    '#2c3031',
    '#18444c',
    '#049ebb',
    '#496268',
    '#049ebb'
)
ON DUPLICATE KEY UPDATE id = id;
