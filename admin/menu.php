<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once BID_PDX_PATH . 'admin/conditional-fields.php';

require_once BID_PDX_PATH . 'admin/projects.php';
require_once BID_PDX_PATH . 'admin/project-add.php';
require_once BID_PDX_PATH . 'admin/project-edit.php';

require_once BID_PDX_PATH . 'admin/categories.php';
require_once BID_PDX_PATH . 'admin/category-add.php';
require_once BID_PDX_PATH . 'admin/category-edit.php';

require_once BID_PDX_PATH . 'admin/items.php';
require_once BID_PDX_PATH . 'admin/item-add.php';
require_once BID_PDX_PATH . 'admin/item-edit.php';

require_once BID_PDX_PATH . 'admin/leads.php';
require_once BID_PDX_PATH . 'admin/settings.php';

/**
 * Register the Estimator admin menu.
 */
function bid_pdx_register_admin_menu() {

    add_menu_page(
        'Estimator',
        'Estimator',
        'manage_options',
        'bid-pdx',
        'bid_pdx_dashboard_page',
        'dashicons-calculator',
        30
    );

    /*
    |--------------------------------------------------------------------------
    | Visible menu pages
    |--------------------------------------------------------------------------
    */

    add_submenu_page(
        'bid-pdx',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'bid-pdx',
        'bid_pdx_dashboard_page'
    );

    add_submenu_page(
        'bid-pdx',
        'Projects',
        'Projects',
        'manage_options',
        'bid-pdx-projects',
        'bid_pdx_projects_page'
    );

    add_submenu_page(
        'bid-pdx',
        'Add Project',
        'Add Project',
        'manage_options',
        'bid-pdx-project-add',
        'bid_pdx_project_add_page'
    );

    add_submenu_page(
        'bid-pdx',
        'Categories',
        'Categories',
        'manage_options',
        'bid-pdx-categories',
        'bid_pdx_categories_page'
    );

    add_submenu_page(
        'bid-pdx',
        'Add Category',
        'Add Category',
        'manage_options',
        'bid-pdx-category-add',
        'bid_pdx_category_add_page'
    );

    add_submenu_page(
        'bid-pdx',
        'Estimator Options',
        'Estimator Options',
        'manage_options',
        'bid-pdx-items',
        'bid_pdx_items_page'
    );

    add_submenu_page(
        'bid-pdx',
        'Add Estimator Option',
        'Add Estimator Option',
        'manage_options',
        'bid-pdx-item-add',
        'bid_pdx_item_add_page'
    );

    add_submenu_page(
        'bid-pdx',
        'Estimator Leads',
        'Leads',
        'manage_options',
        'bid-pdx-leads',
        'bid_pdx_leads_page'
    );

    add_submenu_page(
        'bid-pdx',
        'Settings',
        'Settings',
        'manage_options',
        'bid-pdx-settings',
        'bid_pdx_settings_page'
    );

    /*
    |--------------------------------------------------------------------------
    | Hidden edit pages
    |--------------------------------------------------------------------------
    |
    | These pages are accessible from Edit links, but they do not appear in the
    | sidebar menu. Do not remove them with remove_submenu_page(), because some
    | WordPress setups block access when a submenu page is removed.
    |
    */

    add_submenu_page(
        null,
        'Edit Project',
        'Edit Project',
        'manage_options',
        'bid-pdx-project-edit',
        'bid_pdx_project_edit_page'
    );

    add_submenu_page(
        null,
        'Edit Category',
        'Edit Category',
        'manage_options',
        'bid-pdx-category-edit',
        'bid_pdx_category_edit_page'
    );

    add_submenu_page(
        null,
        'Edit Estimator Option',
        'Edit Estimator Option',
        'manage_options',
        'bid-pdx-item-edit',
        'bid_pdx_item_edit_page'
    );
}

add_action('admin_menu', 'bid_pdx_register_admin_menu');

/**
 * Dashboard page.
 */
function bid_pdx_dashboard_page() {
    ?>
    <div class="wrap">

        <h1>Estimator Framework</h1>

        <p>
            Manage projects, categories, pricing options, and customer leads from one place.
        </p>

        <hr>

        <h2>Estimator Setup</h2>

        <ol>
            <li><strong>Projects:</strong> Create estimator types such as Kitchen Remodel, Roofing, HVAC, or Solar.</li>
            <li><strong>Categories:</strong> Create sections such as Cabinets, Roofing Material, Electrical, Plumbing, etc.</li>
            <li><strong>Estimator Options:</strong> Add pricing ranges for each option.</li>
            <li><strong>Leads:</strong> Review customer requests submitted through the estimator.</li>
        </ol>

        <h2>Shortcode</h2>

        <p>Place this shortcode on any page:</p>

        <code>[bid_pdx_estimator]</code>

        <h2>Current Status</h2>

        <p>The Estimator Framework is active and connected to the WordPress database.</p>

    </div>
    <?php
}