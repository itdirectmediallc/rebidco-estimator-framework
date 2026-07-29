<?php
/*
Plugin Name: Estimator Framework
Description: Reusable WordPress estimator framework with site-specific branding, pricing options, and lead capture.
Version: 2.1.69
Author: Estimator Framework
*/

if (!defined('ABSPATH')) {
    exit;
}

define('ESTIMATOR_FRAMEWORK_VERSION', '2.1.69');
define('ESTIMATOR_FRAMEWORK_PATH', plugin_dir_path(__FILE__));
define('ESTIMATOR_FRAMEWORK_URL', plugin_dir_url(__FILE__));

define('BID_PDX_VERSION', ESTIMATOR_FRAMEWORK_VERSION);
define('BID_PDX_PATH', ESTIMATOR_FRAMEWORK_PATH);
define('BID_PDX_URL', ESTIMATOR_FRAMEWORK_URL);

require_once ESTIMATOR_FRAMEWORK_PATH . 'includes/site-profile.php';
require_once ESTIMATOR_FRAMEWORK_PATH . 'includes/pwa.php';
require_once ESTIMATOR_FRAMEWORK_PATH . 'includes/database.php';
require_once ESTIMATOR_FRAMEWORK_PATH . 'includes/class-project-manager.php';
require_once ESTIMATOR_FRAMEWORK_PATH . 'includes/class-category-manager.php';
require_once ESTIMATOR_FRAMEWORK_PATH . 'includes/class-item-manager.php';
require_once ESTIMATOR_FRAMEWORK_PATH . 'includes/class-lead-manager.php';
require_once ESTIMATOR_FRAMEWORK_PATH . 'includes/class-estimator-engine.php';
require_once ESTIMATOR_FRAMEWORK_PATH . 'includes/lead-actions.php';
require_once ESTIMATOR_FRAMEWORK_PATH . 'includes/contractor-actions.php';
require_once ESTIMATOR_FRAMEWORK_PATH . 'includes/shortcodes.php';
require_once ESTIMATOR_FRAMEWORK_PATH . 'includes/default-data-attic-conversions.php';
require_once ESTIMATOR_FRAMEWORK_PATH . 'includes/default-data-master.php';

if (is_admin()) {
    require_once ESTIMATOR_FRAMEWORK_PATH . 'admin/menu.php';
}

function estimator_framework_enqueue_frontend_assets() {
    wp_enqueue_style(
        'estimator-framework-frontend',
        ESTIMATOR_FRAMEWORK_URL . 'assets/css/frontend.css',
        [],
        ESTIMATOR_FRAMEWORK_VERSION
    );


}
add_action('wp_enqueue_scripts', 'estimator_framework_enqueue_frontend_assets');

register_activation_hook(__FILE__, 'bid_pdx_activate_plugin');
