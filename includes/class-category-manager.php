<?php

if (!defined('ABSPATH')) {
    exit;
}

class Bid_PDX_Category_Manager {

    /**
     * Get database table name.
     */
    private static function table() {

        global $wpdb;

        return $wpdb->prefix . 'bid_pdx_categories';

    }

    /**
     * Convert condition item IDs to a clean comma-separated list.
     */
    private static function prepare_condition_item_ids($value) {

        if (is_array($value)) {
            $values = $value;
        } else {
            $values = preg_split('/[\s,]+/', (string) $value);
        }

        $item_ids = [];

        foreach ($values as $item_id) {
            $item_id = intval($item_id);

            if ($item_id > 0) {
                $item_ids[] = $item_id;
            }
        }

        return implode(',', array_values(array_unique($item_ids)));

    }

    /**
     * Prepare category data before saving.
     */
    private static function prepare_data($data) {

        $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';

        $slug = isset($data['slug']) && $data['slug'] !== ''
            ? sanitize_title($data['slug'])
            : sanitize_title($name);

        $selection_type = isset($data['selection_type'])
            ? sanitize_text_field($data['selection_type'])
            : 'single';

        if (!in_array($selection_type, ['single', 'multiple'], true)) {
            $selection_type = 'single';
        }

        $condition_match = isset($data['condition_match'])
            ? sanitize_key($data['condition_match'])
            : 'any';

        if (!in_array($condition_match, ['any', 'all'], true)) {
            $condition_match = 'any';
        }

        return [
            'project_id'            => isset($data['project_id']) ? intval($data['project_id']) : 0,
            'name'                  => $name,
            'slug'                  => $slug,
            'description'           => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
            'selection_type'        => $selection_type,
            'condition_category_id' => isset($data['condition_category_id']) ? intval($data['condition_category_id']) : 0,
            'condition_item_ids'    => isset($data['condition_item_ids'])
                ? self::prepare_condition_item_ids($data['condition_item_ids'])
                : '',
            'condition_match'       => $condition_match,
            'sort_order'            => isset($data['sort_order']) ? intval($data['sort_order']) : 0,
            'active'                => isset($data['active']) ? intval($data['active']) : 1,
        ];

    }

    /**
     * Get all categories.
     */
    public static function get_all() {

        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM " . self::table() . "
             ORDER BY sort_order ASC, name ASC"
        );

    }

    /**
     * Get one category by ID.
     */
    public static function get($id) {

        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::table() . "
                 WHERE id = %d",
                intval($id)
            )
        );

    }

    /**
     * Get categories for one project.
     */
    public static function get_by_project($project_id) {

        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . self::table() . "
                 WHERE project_id = %d
                 ORDER BY sort_order ASC, name ASC",
                intval($project_id)
            )
        );

    }

    /**
     * Create a new category.
     */
    public static function create($data) {

        global $wpdb;

        return $wpdb->insert(
            self::table(),
            self::prepare_data($data),
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
                '%d'
            ]
        );

    }

    /**
     * Update existing category.
     */
    public static function update($id, $data) {

        global $wpdb;

        return $wpdb->update(
            self::table(),
            self::prepare_data($data),
            [
                'id' => intval($id)
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
                '%d'
            ],
            [
                '%d'
            ]
        );

    }

    /**
     * Delete category.
     */
    public static function delete($id) {

        global $wpdb;

        return $wpdb->delete(
            self::table(),
            [
                'id' => intval($id)
            ],
            [
                '%d'
            ]
        );

    }

}