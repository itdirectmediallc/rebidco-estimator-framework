<?php

if (!defined('ABSPATH')) {
    exit;
}

class Bid_PDX_Lead_Manager {

    /**
     * Get database table name.
     */
    private static function table() {

        global $wpdb;

        return $wpdb->prefix . 'bid_pdx_leads';

    }

    /**
     * Prepare lead data before saving.
     */
    private static function prepare_data($data) {

        $selected_options = [];

        if (isset($data['selected_options']) && is_array($data['selected_options'])) {
            $selected_options = $data['selected_options'];
        }

        return [
            'project_id'       => isset($data['project_id']) ? intval($data['project_id']) : 0,
            'project_name'     => isset($data['project_name']) ? sanitize_text_field($data['project_name']) : '',
            'customer_name'    => isset($data['customer_name']) ? sanitize_text_field($data['customer_name']) : '',
            'customer_email'   => isset($data['customer_email']) ? sanitize_email($data['customer_email']) : '',
            'customer_phone'   => isset($data['customer_phone']) ? sanitize_text_field($data['customer_phone']) : '',
            'zip_code'         => isset($data['zip_code']) ? sanitize_text_field($data['zip_code']) : '',
            'estimate_min'     => isset($data['estimate_min']) ? floatval($data['estimate_min']) : 0,
            'estimate_max'     => isset($data['estimate_max']) ? floatval($data['estimate_max']) : 0,
            'selected_options' => wp_json_encode($selected_options),
            'message'          => isset($data['message']) ? sanitize_textarea_field($data['message']) : '',
            'status'           => isset($data['status']) ? sanitize_text_field($data['status']) : 'new',
        ];

    }

    /**
     * Create a new lead.
     */
    public static function create($data) {

        global $wpdb;

        $created = $wpdb->insert(
            self::table(),
            self::prepare_data($data),
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%f',
                '%f',
                '%s',
                '%s',
                '%s'
            ]
        );

        if (!$created) {
            return false;
        }

        return intval($wpdb->insert_id);

    }

    /**
     * Get all leads.
     */
    public static function get_all($limit = 100) {

        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . self::table() . "
                 ORDER BY created_at DESC
                 LIMIT %d",
                intval($limit)
            )
        );

    }

    /**
     * Get one lead by ID.
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
     * Update lead status.
     */
    public static function update_status($id, $status) {

        global $wpdb;

        return $wpdb->update(
            self::table(),
            [
                'status' => sanitize_text_field($status),
            ],
            [
                'id' => intval($id),
            ],
            [
                '%s',
            ],
            [
                '%d',
            ]
        );

    }

    /**
     * Delete lead.
     */
    public static function delete($id) {

        global $wpdb;

        return $wpdb->delete(
            self::table(),
            [
                'id' => intval($id),
            ],
            [
                '%d',
            ]
        );

    }

}