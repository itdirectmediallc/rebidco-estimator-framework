<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Returns all database table definitions.
 */
function bid_pdx_get_database_schema() {

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $projects_table   = $wpdb->prefix . 'bid_pdx_projects';
    $categories_table = $wpdb->prefix . 'bid_pdx_categories';
    $items_table      = $wpdb->prefix . 'bid_pdx_items';
    $leads_table      = $wpdb->prefix . 'bid_pdx_leads';

    return [

        "CREATE TABLE {$projects_table} (

            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            description TEXT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)

        ) {$charset_collate};",

        "CREATE TABLE {$categories_table} (

            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            description TEXT NULL,
            selection_type VARCHAR(20) NOT NULL DEFAULT 'single',
            condition_category_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            condition_item_ids LONGTEXT NULL,
            condition_match VARCHAR(20) NOT NULL DEFAULT 'any',
            sort_order INT NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY condition_category_id (condition_category_id),
            UNIQUE KEY project_slug (project_id, slug)

        ) {$charset_collate};",

        "CREATE TABLE {$items_table} (

            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            category_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            description TEXT NULL,
            unit VARCHAR(100) NOT NULL DEFAULT '',
            price_min DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            price_max DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            condition_category_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            condition_item_ids LONGTEXT NULL,
            condition_match VARCHAR(20) NOT NULL DEFAULT 'any',
            sort_order INT NOT NULL DEFAULT 0,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            KEY category_id (category_id),
            KEY condition_category_id (condition_category_id),
            UNIQUE KEY category_slug (category_id, slug)

        ) {$charset_collate};",

        "CREATE TABLE {$leads_table} (

            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            project_name VARCHAR(255) NOT NULL DEFAULT '',
            customer_name VARCHAR(255) NOT NULL DEFAULT '',
            customer_email VARCHAR(255) NOT NULL DEFAULT '',
            customer_phone VARCHAR(100) NOT NULL DEFAULT '',
            zip_code VARCHAR(255) NOT NULL DEFAULT '',
            estimate_min DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            estimate_max DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            selected_options LONGTEXT NULL,
            message TEXT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'new',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            KEY project_id (project_id),
            KEY status (status),
            KEY customer_email (customer_email)

        ) {$charset_collate};"

    ];

}