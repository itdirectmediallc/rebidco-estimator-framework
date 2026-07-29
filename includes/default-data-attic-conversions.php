<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Install default categories and options for Attic Conversions.
 *
 * This is safe to run more than once. It only creates missing categories/options.
 */
function estimator_framework_maybe_install_attic_conversions_defaults() {

    if (!is_admin()) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    if (get_option('estimator_framework_attic_conversions_defaults_2_1_27') === 'installed') {
        return;
    }

    $installed = estimator_framework_install_attic_conversions_defaults();

    if ($installed) {
        update_option('estimator_framework_attic_conversions_defaults_2_1_27', 'installed');
    }
}
add_action('admin_init', 'estimator_framework_maybe_install_attic_conversions_defaults');

/**
 * Create Attic Conversions default data.
 */
function estimator_framework_install_attic_conversions_defaults() {

    global $wpdb;

    $projects_table   = $wpdb->prefix . 'bid_pdx_projects';
    $categories_table = $wpdb->prefix . 'bid_pdx_categories';
    $items_table      = $wpdb->prefix . 'bid_pdx_items';

    $project_slug = 'attic-conversions';

    $project_id = intval(
        $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$projects_table} WHERE slug = %s LIMIT 1",
                $project_slug
            )
        )
    );

    if ($project_id <= 0) {
        $created = $wpdb->insert(
            $projects_table,
            [
                'name'        => 'Attic Conversions',
                'slug'        => $project_slug,
                'description' => 'Convert unfinished attic space into usable living space.',
                'sort_order'  => 10,
                'active'      => 1,
            ],
            [
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
            ]
        );

        if (!$created) {
            return false;
        }

        $project_id = intval($wpdb->insert_id);
    }

    if ($project_id <= 0) {
        return false;
    }

    $categories = [
        [
            'name' => 'Attic Size',
            'description' => 'Approximate usable attic space to convert.',
            'selection_type' => 'single',
            'sort_order' => 1,
            'options' => [
                ['Small attic conversion', 'Small bonus room, office, or compact bedroom area.', 15000, 28000, 1],
                ['Medium attic conversion', 'Typical bedroom, office, or flexible living area.', 28000, 50000, 2],
                ['Large attic conversion', 'Large finished attic, multi-use room, or suite-ready space.', 50000, 85000, 3],
            ],
        ],
        [
            'name' => 'Existing Conditions',
            'description' => 'Current attic condition before remodeling.',
            'selection_type' => 'single',
            'sort_order' => 2,
            'options' => [
                ['Good existing headroom', 'Attic already has workable height and simple layout.', 0, 5000, 1],
                ['Limited headroom adjustments', 'Some framing, ceiling, or layout adjustments needed.', 8000, 25000, 2],
                ['Major structural constraints', 'Low ceiling, complicated roof framing, or major code upgrades.', 25000, 65000, 3],
            ],
        ],
        [
            'name' => 'Conversion Type',
            'description' => 'Main use for the finished attic.',
            'selection_type' => 'single',
            'sort_order' => 3,
            'options' => [
                ['Bonus room or office', 'Finished room without bathroom or major plumbing.', 12000, 30000, 1],
                ['Bedroom conversion', 'Finished bedroom-style space with closet-ready layout.', 22000, 50000, 2],
                ['Primary suite layout', 'Bedroom suite layout prepared for larger finishes and possible bath.', 45000, 95000, 3],
            ],
        ],
        [
            'name' => 'Access and Stairs',
            'description' => 'How the attic will be accessed.',
            'selection_type' => 'single',
            'sort_order' => 4,
            'options' => [
                ['Keep existing stairs', 'Existing stair access remains mostly unchanged.', 0, 5000, 1],
                ['New code-compliant stairs', 'Build new permanent stairs to the attic.', 12000, 35000, 2],
                ['Reconfigure stair opening', 'Move or expand stair opening and adjust surrounding framing.', 25000, 60000, 3],
            ],
        ],
        [
            'name' => 'Structure and Roof Changes',
            'description' => 'Optional structural or roofline upgrades.',
            'selection_type' => 'multiple',
            'sort_order' => 5,
            'options' => [
                ['Floor joist reinforcement', 'Strengthen attic floor system for living-space use.', 12000, 45000, 1],
                ['Add dormer', 'Add dormer to increase headroom and usable space.', 35000, 90000, 2],
                ['Major roofline modification', 'More complex roof framing or roofline changes.', 65000, 140000, 3],
                ['Seismic or framing upgrades', 'Additional framing improvements for older homes.', 8000, 30000, 4],
            ],
        ],
        [
            'name' => 'Insulation and Drywall',
            'description' => 'Thermal envelope and wall/ceiling finish level.',
            'selection_type' => 'single',
            'sort_order' => 6,
            'options' => [
                ['Standard insulation and drywall', 'Basic insulation, drywall, texture, and paint-ready finish.', 8000, 18000, 1],
                ['Upgraded energy package', 'Improved insulation, air sealing, and ventilation prep.', 14000, 30000, 2],
                ['Premium comfort package', 'Higher-performance insulation, drywall detail, and sound control.', 25000, 50000, 3],
            ],
        ],
        [
            'name' => 'Electrical and Lighting',
            'description' => 'Electrical scope for the attic conversion.',
            'selection_type' => 'single',
            'sort_order' => 7,
            'options' => [
                ['Basic electrical updates', 'Basic outlets, switches, and overhead lighting.', 2500, 7000, 1],
                ['Standard room wiring', 'New wiring, recessed lights, outlets, and smoke/CO updates.', 7000, 16000, 2],
                ['Full electrical upgrade', 'Larger wiring scope, panel coordination, or multiple circuits.', 16000, 35000, 3],
            ],
        ],
        [
            'name' => 'Heating Cooling and Ventilation',
            'description' => 'Comfort system for the finished attic.',
            'selection_type' => 'single',
            'sort_order' => 8,
            'options' => [
                ['Extend existing HVAC', 'Tie attic space into existing heating/cooling system.', 3500, 12000, 1],
                ['Add mini-split system', 'Install dedicated ductless heating and cooling.', 8000, 18000, 2],
                ['Full HVAC and ventilation upgrades', 'Expanded HVAC work, ventilation, and moisture-control upgrades.', 18000, 40000, 3],
            ],
        ],
        [
            'name' => 'Windows Egress and Natural Light',
            'description' => 'Optional daylight, emergency exit, and ventilation upgrades.',
            'selection_type' => 'multiple',
            'sort_order' => 9,
            'options' => [
                ['Egress window', 'Add or upgrade required emergency escape opening.', 5000, 15000, 1],
                ['Skylight or roof window', 'Add skylight or roof window for natural light.', 4000, 12000, 2],
                ['Multiple windows or larger openings', 'Add several windows or enlarge openings.', 12000, 35000, 3],
            ],
        ],
        [
            'name' => 'Bathroom and Plumbing',
            'description' => 'Optional bathroom or plumbing work.',
            'selection_type' => 'multiple',
            'sort_order' => 10,
            'options' => [
                ['Plumbing rough-in only', 'Run plumbing lines for future bathroom or wet bar.', 10000, 25000, 1],
                ['Half bathroom', 'Add toilet and sink in attic space.', 25000, 55000, 2],
                ['Full bathroom', 'Add shower/tub, toilet, vanity, and full plumbing scope.', 45000, 95000, 3],
            ],
        ],
        [
            'name' => 'Interior Finishes',
            'description' => 'Finished look and materials.',
            'selection_type' => 'single',
            'sort_order' => 11,
            'options' => [
                ['Basic finish level', 'Basic trim, paint, flooring, and simple fixtures.', 8000, 18000, 1],
                ['Mid-range finish level', 'Better flooring, trim, doors, paint, and finish details.', 18000, 40000, 2],
                ['Premium finish level', 'Custom trim, premium flooring, built-ins, and higher-end details.', 40000, 85000, 3],
            ],
        ],
        [
            'name' => 'Permits Design and Engineering',
            'description' => 'Planning, permit, and professional design needs.',
            'selection_type' => 'single',
            'sort_order' => 12,
            'options' => [
                ['Basic permit support', 'Basic permit preparation for simple conversion.', 2500, 8000, 1],
                ['Design and permit package', 'Design drawings, permit support, and contractor planning.', 8000, 20000, 2],
                ['Engineering-heavy package', 'Structural engineering, complex plans, and advanced permit support.', 18000, 40000, 3],
            ],
        ],
        [
            'name' => 'Storage and Built-Ins',
            'description' => 'Optional custom storage and built-in features.',
            'selection_type' => 'multiple',
            'sort_order' => 13,
            'options' => [
                ['Knee-wall storage', 'Build low-wall storage into attic knee walls.', 3000, 10000, 1],
                ['Custom closets', 'Add custom closet storage or wardrobe area.', 5000, 18000, 2],
                ['Built-in desk or shelving', 'Add built-in office, shelving, or media storage.', 4000, 20000, 3],
            ],
        ],
    ];

    foreach ($categories as $category) {
        $category_slug = sanitize_title($category['name']);

        $category_id = intval(
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$categories_table} WHERE project_id = %d AND slug = %s LIMIT 1",
                    $project_id,
                    $category_slug
                )
            )
        );

        if ($category_id <= 0) {
            $created = $wpdb->insert(
                $categories_table,
                [
                    'project_id'     => $project_id,
                    'name'           => sanitize_text_field($category['name']),
                    'slug'           => $category_slug,
                    'description'    => sanitize_textarea_field($category['description']),
                    'selection_type' => sanitize_key($category['selection_type']),
                    'sort_order'     => intval($category['sort_order']),
                    'active'         => 1,
                ],
                [
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                ]
            );

            if (!$created) {
                continue;
            }

            $category_id = intval($wpdb->insert_id);
        }

        if ($category_id <= 0) {
            continue;
        }

        foreach ($category['options'] as $option) {
            $option_slug = sanitize_title($option[0]);

            $option_id = intval(
                $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id FROM {$items_table} WHERE category_id = %d AND slug = %s LIMIT 1",
                        $category_id,
                        $option_slug
                    )
                )
            );

            if ($option_id > 0) {
                continue;
            }

            $wpdb->insert(
                $items_table,
                [
                    'category_id' => $category_id,
                    'name'        => sanitize_text_field($option[0]),
                    'slug'        => $option_slug,
                    'description' => sanitize_textarea_field($option[1]),
                    'unit'        => '',
                    'price_min'   => floatval($option[2]),
                    'price_max'   => floatval($option[3]),
                    'sort_order'  => intval($option[4]),
                    'active'      => 1,
                ],
                [
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%f',
                    '%f',
                    '%d',
                    '%d',
                ]
            );
        }
    }

    return true;
}
