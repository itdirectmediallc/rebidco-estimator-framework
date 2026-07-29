<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once BID_PDX_PATH . 'includes/database-schema.php';
require_once BID_PDX_PATH . 'includes/database-upgrade.php';

/**
 * Runs when the plugin is activated.
 */
function bid_pdx_activate_plugin() {
    bid_pdx_install_database();
}

/**
 * Automatically run database upgrades when the plugin version changes.
 */
function bid_pdx_maybe_upgrade_database() {

    $installed = get_option('bid_pdx_db_version', '0.0.0');

    if (version_compare($installed, BID_PDX_VERSION, '<')) {

        bid_pdx_install_database();

    }

}

add_action('plugins_loaded', 'bid_pdx_maybe_upgrade_database');