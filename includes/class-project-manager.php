<?php

if (!defined('ABSPATH')) {
    exit;
}

class Bid_PDX_Project_Manager {

    /**
     * Get database table name.
     */
    private static function table() {

        global $wpdb;

        return $wpdb->prefix . 'bid_pdx_projects';

    }

    /**
     * Get all projects.
     */
    public static function get_all() {

        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM " . self::table() . "
             ORDER BY sort_order ASC, name ASC"
        );

    }

    /**
     * Get one project by ID.
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
     * Create a new project.
     */
    public static function create($data) {

        global $wpdb;

        return $wpdb->insert(
            self::table(),
            [
                'name'        => sanitize_text_field($data['name']),
                'slug'        => sanitize_title($data['slug']),
                'description' => sanitize_textarea_field($data['description']),
                'sort_order'  => intval($data['sort_order']),
                'active'      => intval($data['active']),
            ],
            [
                '%s',
                '%s',
                '%s',
                '%d',
                '%d'
            ]
        );

    }

    /**
     * Update existing project.
     */
    public static function update($id, $data) {

        global $wpdb;

        return $wpdb->update(
            self::table(),
            [
                'name'        => sanitize_text_field($data['name']),
                'slug'        => sanitize_title($data['slug']),
                'description' => sanitize_textarea_field($data['description']),
                'sort_order'  => intval($data['sort_order']),
                'active'      => intval($data['active']),
            ],
            [
                'id' => intval($id)
            ],
            [
                '%s',
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
     * Delete project.
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