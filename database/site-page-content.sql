CREATE TABLE IF NOT EXISTS site_page_content_fields (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    site_page_id INT(10) UNSIGNED NOT NULL,
    field_key VARCHAR(150) NOT NULL,
    field_type VARCHAR(50) NOT NULL,
    field_value LONGTEXT NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_site_page_content_fields_page_field (site_page_id, field_key),
    KEY idx_site_page_content_fields_page (site_page_id),
    CONSTRAINT fk_site_page_content_fields_page FOREIGN KEY (site_page_id) REFERENCES site_pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_page_content_repeater_items (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    site_page_id INT(10) UNSIGNED NOT NULL,
    repeater_key VARCHAR(150) NOT NULL,
    item_index INT NOT NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_site_page_repeater_items_page_key_index (site_page_id, repeater_key, item_index),
    KEY idx_site_page_repeater_items_page_key (site_page_id, repeater_key),
    CONSTRAINT fk_site_page_repeater_items_page FOREIGN KEY (site_page_id) REFERENCES site_pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_page_content_repeater_item_fields (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    repeater_item_id INT(10) UNSIGNED NOT NULL,
    field_key VARCHAR(150) NOT NULL,
    field_type VARCHAR(50) NOT NULL,
    field_value LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_site_page_repeater_item_fields_item_field (repeater_item_id, field_key),
    KEY idx_site_page_repeater_item_fields_item (repeater_item_id),
    CONSTRAINT fk_site_page_repeater_item_fields_item FOREIGN KEY (repeater_item_id) REFERENCES site_page_content_repeater_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;