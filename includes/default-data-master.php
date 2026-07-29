<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Install the conservative master estimator defaults.
 *
 * This installer runs once for version 2.1.58. It replaces categories/options
 * for the supported default project slugs so old draft data does not duplicate.
 * Customer leads are not deleted.
 */
function estimator_framework_maybe_install_master_defaults_2_1_58() {

    if (!is_admin()) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    if (get_option('estimator_framework_master_defaults_2_1_58') === 'installed') {
        return;
    }

    $installed = estimator_framework_install_master_defaults_2_1_58();

    if ($installed) {
        update_option('estimator_framework_master_defaults_2_1_58', 'installed');
    }
}
add_action('admin_init', 'estimator_framework_maybe_install_master_defaults_2_1_58');

/**
 * Install master default project/category/option data from CSV.
 */
function estimator_framework_install_master_defaults_2_1_58() {

    global $wpdb;

    $csv_path = ESTIMATOR_FRAMEWORK_PATH . 'includes/default-data-master.csv';

    if (!file_exists($csv_path) || !is_readable($csv_path)) {
        return false;
    }

    $rows = estimator_framework_read_master_defaults_csv_2_1_58($csv_path);

    if (empty($rows)) {
        return false;
    }

    $projects_table   = $wpdb->prefix . 'bid_pdx_projects';
    $categories_table = $wpdb->prefix . 'bid_pdx_categories';
    $items_table      = $wpdb->prefix . 'bid_pdx_items';

    $projects = [];

    foreach ($rows as $row) {
        $project_slug = sanitize_title($row['project_slug']);

        if ($project_slug === '') {
            continue;
        }

        if (!isset($projects[$project_slug])) {
            $projects[$project_slug] = [
                'name'       => sanitize_text_field($row['project_name']),
                'slug'       => $project_slug,
                'categories' => [],
            ];
        }

        $category_slug = sanitize_title($row['category_name']);

        if ($category_slug === '') {
            continue;
        }

        if (!isset($projects[$project_slug]['categories'][$category_slug])) {
            $projects[$project_slug]['categories'][$category_slug] = [
                'name'                    => sanitize_text_field($row['category_name']),
                'slug'                    => $category_slug,
                'selection_type'          => sanitize_key($row['selection_type']) === 'multiple' ? 'multiple' : 'single',
                'condition_category_slug' => sanitize_title(
                    isset($row['category_condition_category_slug'])
                        ? $row['category_condition_category_slug']
                        : ''
                ),
                'condition_option_slugs'  => estimator_framework_prepare_condition_option_slugs_2_1_58(
                    isset($row['category_condition_option_slugs'])
                        ? $row['category_condition_option_slugs']
                        : ''
                ),
                'condition_match'         => estimator_framework_prepare_condition_match_2_1_58(
                    isset($row['category_condition_match'])
                        ? $row['category_condition_match']
                        : 'any'
                ),
                'sort_order'              => intval($row['category_sort']),
                'options'                 => [],
            ];
        }

        $option_name = sanitize_text_field($row['option_name']);
        $option_slug = sanitize_title($option_name);

        if ($option_slug === '') {
            continue;
        }

        $projects[$project_slug]['categories'][$category_slug]['options'][] = [
            'name'                    => $option_name,
            'slug'                    => $option_slug,
            'description'             => sanitize_textarea_field($row['option_description']),
            'price_min'               => max(0, floatval($row['price_min'])),
            'price_max'               => max(0, floatval($row['price_max'])),
            'condition_category_slug' => sanitize_title(
                isset($row['option_condition_category_slug'])
                    ? $row['option_condition_category_slug']
                    : ''
            ),
            'condition_option_slugs'  => estimator_framework_prepare_condition_option_slugs_2_1_58(
                isset($row['option_condition_option_slugs'])
                    ? $row['option_condition_option_slugs']
                    : ''
            ),
            'condition_match'         => estimator_framework_prepare_condition_match_2_1_58(
                isset($row['option_condition_match'])
                    ? $row['option_condition_match']
                    : 'any'
            ),
            'sort_order'              => intval($row['option_sort']),
            'active'                  => intval($row['active']) === 1 ? 1 : 0,
        ];
    }

    if (empty($projects)) {
        return false;
    }

    $project_sort_order = 0;

    foreach ($projects as $project) {
        $project_sort_order++;

        $project_id = intval(
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$projects_table} WHERE slug = %s LIMIT 1",
                    $project['slug']
                )
            )
        );

        if ($project_id > 0) {
            $wpdb->update(
                $projects_table,
                [
                    'name'        => $project['name'],
                    'slug'        => $project['slug'],
                    'description' => 'Get an instant quote',
                    'sort_order'  => $project_sort_order,
                    'active'      => 1,
                ],
                [
                    'id' => $project_id,
                ],
                [
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                ],
                [
                    '%d',
                ]
            );
        } else {
            $created = $wpdb->insert(
                $projects_table,
                [
                    'name'        => $project['name'],
                    'slug'        => $project['slug'],
                    'description' => 'Get an instant quote',
                    'sort_order'  => $project_sort_order,
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
                continue;
            }

            $project_id = intval($wpdb->insert_id);
        }

        if ($project_id <= 0) {
            continue;
        }

        estimator_framework_reset_project_categories_2_1_58(
            $project_id,
            $categories_table,
            $items_table
        );

        $category_ids_by_slug       = [];
        $item_ids_by_category_slug  = [];

        foreach ($project['categories'] as $category) {

            $category_condition = estimator_framework_resolve_default_condition_2_1_58(
                $category['condition_category_slug'],
                $category['condition_option_slugs'],
                $category_ids_by_slug,
                $item_ids_by_category_slug
            );

            if ($category_condition === null) {
                return false;
            }

            $category_created = $wpdb->insert(
                $categories_table,
                [
                    'project_id'            => $project_id,
                    'name'                  => $category['name'],
                    'slug'                  => $category['slug'],
                    'description'           => '',
                    'selection_type'        => $category['selection_type'],
                    'condition_category_id' => $category_condition['category_id'],
                    'condition_item_ids'    => $category_condition['item_ids'],
                    'condition_match'       => $category['condition_match'],
                    'sort_order'            => $category['sort_order'],
                    'active'                => 1,
                ],
                [
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                ]
            );

            if (!$category_created) {
                return false;
            }

            $category_id = intval($wpdb->insert_id);

            $category_ids_by_slug[$category['slug']] = $category_id;
            $item_ids_by_category_slug[$category['slug']] = [];

            foreach ($category['options'] as $option) {

                $option_condition = estimator_framework_resolve_default_condition_2_1_58(
                    $option['condition_category_slug'],
                    $option['condition_option_slugs'],
                    $category_ids_by_slug,
                    $item_ids_by_category_slug
                );

                if ($option_condition === null) {
                    return false;
                }

                $item_created = $wpdb->insert(
                    $items_table,
                    [
                        'category_id'           => $category_id,
                        'name'                  => $option['name'],
                        'slug'                  => $option['slug'],
                        'description'           => $option['description'],
                        'unit'                  => '',
                        'price_min'             => $option['price_min'],
                        'price_max'             => $option['price_max'],
                        'condition_category_id' => $option_condition['category_id'],
                        'condition_item_ids'    => $option_condition['item_ids'],
                        'condition_match'       => $option['condition_match'],
                        'sort_order'            => $option['sort_order'],
                        'active'                => $option['active'],
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
                        '%s',
                        '%s',
                        '%d',
                        '%d',
                    ]
                );

                if (!$item_created) {
                    return false;
                }

                $item_ids_by_category_slug[$category['slug']][$option['slug']] =
                    intval($wpdb->insert_id);
            }
        }
    }
    return true;
}

/**
 * Normalize pipe- or comma-separated condition option slugs.
 */
function estimator_framework_prepare_condition_option_slugs_2_1_58($value) {

    $values = is_array($value)
        ? $value
        : preg_split('/[|,\s]+/', (string) $value);

    $slugs = [];

    foreach ($values as $slug) {
        $slug = is_scalar($slug)
            ? sanitize_title((string) $slug)
            : '';

        if ($slug !== '') {
            $slugs[] = $slug;
        }
    }

    return array_values(array_unique($slugs));
}

/**
 * Return a supported default-data condition matching mode.
 */
function estimator_framework_prepare_condition_match_2_1_58($value) {

    $value = sanitize_key($value);

    return in_array($value, ['any', 'all'], true)
        ? $value
        : 'any';
}

/**
 * Resolve stable CSV condition slugs to the newly inserted database IDs.
 */
function estimator_framework_resolve_default_condition_2_1_58(
    $category_slug,
    $option_slugs,
    $category_ids_by_slug,
    $item_ids_by_category_slug
) {

    $category_slug = sanitize_title($category_slug);
    $option_slugs  = estimator_framework_prepare_condition_option_slugs_2_1_58(
        $option_slugs
    );

    if ($category_slug === '' && empty($option_slugs)) {
        return [
            'category_id' => 0,
            'item_ids'    => '',
        ];
    }

    if (
        $category_slug === '' ||
        empty($option_slugs) ||
        !isset($category_ids_by_slug[$category_slug]) ||
        !isset($item_ids_by_category_slug[$category_slug])
    ) {
        return null;
    }

    $item_ids = [];

    foreach ($option_slugs as $option_slug) {
        if (!isset($item_ids_by_category_slug[$category_slug][$option_slug])) {
            return null;
        }

        $item_ids[] = intval(
            $item_ids_by_category_slug[$category_slug][$option_slug]
        );
    }

    return [
        'category_id' => intval($category_ids_by_slug[$category_slug]),
        'item_ids'    => implode(',', array_values(array_unique($item_ids))),
    ];
}

/**
 * Read the master defaults CSV.
 */
function estimator_framework_read_master_defaults_csv_2_1_58($csv_path) {

    $handle = fopen($csv_path, 'r');

    if (!$handle) {
        return [];
    }

    $headers = fgetcsv($handle);

    if (empty($headers)) {
        fclose($handle);
        return [];
    }

    $headers = array_map('sanitize_key', $headers);
    $rows    = [];

    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) !== count($headers)) {
            continue;
        }

        $row = array_combine($headers, $data);

        if (empty($row['project_slug']) || empty($row['category_name']) || empty($row['option_name'])) {
            continue;
        }

        $rows[] = $row;
    }

    fclose($handle);

    return $rows;
}

/**
 * Remove old category and option rows for one default project.
 */
function estimator_framework_reset_project_categories_2_1_58($project_id, $categories_table, $items_table) {

    global $wpdb;

    $category_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT id FROM {$categories_table} WHERE project_id = %d",
            $project_id
        )
    );

    if (!empty($category_ids)) {
        $category_ids = array_map('intval', $category_ids);
        $placeholders = implode(',', array_fill(0, count($category_ids), '%d'));

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$items_table} WHERE category_id IN ({$placeholders})",
                $category_ids
            )
        );
    }

    $wpdb->delete(
        $categories_table,
        [
            'project_id' => intval($project_id),
        ],
        [
            '%d',
        ]
    );
}
