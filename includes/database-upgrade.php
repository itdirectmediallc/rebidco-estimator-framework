<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Creates or updates the Estimator Framework database tables.
 */
function bid_pdx_install_database() {

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $schema = bid_pdx_get_database_schema();

    foreach ($schema as $sql) {
        dbDelta($sql);
    }

    if (!bid_pdx_upgrade_condition_columns()) {
        return false;
    }

    bid_pdx_upgrade_leads_address_column();

    update_option('bid_pdx_db_version', BID_PDX_VERSION);

    return true;

}

/**
 * Explicitly ensure conditional-routing columns and indexes exist.
 *
 * This acts as a fallback when dbDelta does not complete an ALTER operation.
 */
function bid_pdx_upgrade_condition_columns() {

    global $wpdb;

    $tables = [
        $wpdb->prefix . 'bid_pdx_categories',
        $wpdb->prefix . 'bid_pdx_items',
    ];

    $columns = [
        'condition_category_id' => 'BIGINT UNSIGNED NOT NULL DEFAULT 0',
        'condition_item_ids'    => 'LONGTEXT NULL',
        'condition_match'       => "VARCHAR(20) NOT NULL DEFAULT 'any'",
    ];

    foreach ($tables as $table) {

        $existing_columns = $wpdb->get_col(
            "SHOW COLUMNS FROM {$table}",
            0
        );

        if (!is_array($existing_columns)) {
            return false;
        }

        foreach ($columns as $column_name => $definition) {
            if (in_array($column_name, $existing_columns, true)) {
                continue;
            }

            $added = $wpdb->query(
                "ALTER TABLE {$table}
                 ADD COLUMN {$column_name} {$definition}"
            );

            if ($added === false) {
                return false;
            }

            $existing_columns[] = $column_name;
        }

        $condition_index = $wpdb->get_var(
            "SHOW INDEX FROM {$table}
             WHERE Key_name = 'condition_category_id'"
        );

        if ($condition_index === null) {
            $index_added = $wpdb->query(
                "ALTER TABLE {$table}
                 ADD KEY condition_category_id (condition_category_id)"
            );

            if ($index_added === false) {
                return false;
            }
        }
    }

    return true;

}

/**
 * Make the old ZIP/address field large enough for project addresses.
 */
function bid_pdx_upgrade_leads_address_column() {

    global $wpdb;

    $table = $wpdb->prefix . 'bid_pdx_leads';

    $wpdb->query("ALTER TABLE {$table} MODIFY zip_code VARCHAR(255) NOT NULL DEFAULT ''");

}