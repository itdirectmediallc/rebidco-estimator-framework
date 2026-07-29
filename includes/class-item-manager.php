<?php

if (!defined('ABSPATH')) {
    exit;
}

class Bid_PDX_Item_Manager {

    /**
     * Get database table name.
     */
    private static function table() {

        global $wpdb;

        return $wpdb->prefix . 'bid_pdx_items';

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
     * Prepare item data before saving.
     */
    private static function prepare_data($data) {

        $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';

        $slug = isset($data['slug']) && $data['slug'] !== ''
            ? sanitize_title($data['slug'])
            : sanitize_title($name);

        $condition_match = isset($data['condition_match'])
            ? sanitize_key($data['condition_match'])
            : 'any';

        if (!in_array($condition_match, ['any', 'all'], true)) {
            $condition_match = 'any';
        }

        return [
            'category_id'           => isset($data['category_id']) ? intval($data['category_id']) : 0,
            'name'                  => $name,
            'slug'                  => $slug,
            'description'           => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
            'unit'                  => isset($data['unit']) ? sanitize_text_field($data['unit']) : '',
            'price_min'             => isset($data['price_min']) ? floatval($data['price_min']) : 0,
            'price_max'             => isset($data['price_max']) ? floatval($data['price_max']) : 0,
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
     * Get all items.
     */
    public static function get_all() {

        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM " . self::table() . "
             ORDER BY sort_order ASC, name ASC"
        );

    }

    /**
     * Get all active items.
     */
    public static function get_active() {

        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM " . self::table() . "
             WHERE active = 1
             ORDER BY sort_order ASC, name ASC"
        );

    }

    /**
     * Get one item by ID.
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
     * Get items for one category.
     */
    public static function get_by_category($category_id) {

        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . self::table() . "
                 WHERE category_id = %d
                 ORDER BY sort_order ASC, name ASC",
                intval($category_id)
            )
        );

    }

    /**
     * Get active items for one category.
     */
    public static function get_active_by_category($category_id) {

        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . self::table() . "
                 WHERE category_id = %d
                 AND active = 1
                 ORDER BY sort_order ASC, name ASC",
                intval($category_id)
            )
        );

    }

    /**
     * Create a new item.
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
                '%f',
                '%f',
                '%d',
                '%s',
                '%s',
                '%d',
                '%d'
            ]
        );

    }

    /**
     * Update existing item.
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
                '%f',
                '%f',
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
     * Delete item.
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