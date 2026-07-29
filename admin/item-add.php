<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add Estimator Option Page
 */
function bid_pdx_item_add_page() {

    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;

    $message = '';
    $error   = '';

    $projects_table   = $wpdb->prefix . 'bid_pdx_projects';
    $categories_table = $wpdb->prefix . 'bid_pdx_categories';

    $categories = $wpdb->get_results(
        "SELECT c.id,
            c.name AS category_name,
            p.name AS project_name
         FROM {$categories_table} c
         INNER JOIN {$projects_table} p ON p.id = c.project_id
         WHERE c.active = 1
         AND p.active = 1
         ORDER BY p.sort_order ASC, p.name ASC, c.sort_order ASC, c.name ASC"
    );

    if (
        isset($_POST['bid_pdx_item_nonce']) &&
        wp_verify_nonce($_POST['bid_pdx_item_nonce'], 'bid_pdx_save_item')
    ) {

        $category_id           = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $name                  = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $description           = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';
        $unit                  = isset($_POST['unit']) ? sanitize_text_field(wp_unslash($_POST['unit'])) : '';
        $price_min             = isset($_POST['price_min']) ? floatval($_POST['price_min']) : 0;
        $price_max             = isset($_POST['price_max']) ? floatval($_POST['price_max']) : 0;
        $condition_category_id = isset($_POST['condition_category_id']) ? intval($_POST['condition_category_id']) : 0;
        $condition_item_ids    = isset($_POST['condition_item_ids'])
            ? bid_pdx_admin_normalize_condition_item_ids(wp_unslash($_POST['condition_item_ids']))
            : [];
        $condition_match       = isset($_POST['condition_match']) ? sanitize_key(wp_unslash($_POST['condition_match'])) : 'any';
        $sort_order            = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
        $active                = isset($_POST['active']) ? 1 : 0;

        if (!in_array($condition_match, ['any', 'all'], true)) {
            $condition_match = 'any';
        }

        if ($condition_category_id <= 0) {
            $condition_category_id = 0;
            $condition_item_ids    = [];
            $condition_match       = 'any';
        }

        $category_context = bid_pdx_admin_get_category_context($category_id);

        $target_project_id = $category_context
            ? intval($category_context->project_id)
            : 0;

        $target_sort_order = $category_context
            ? intval($category_context->sort_order)
            : 0;

        $condition_error = bid_pdx_admin_validate_condition_rule(
            $target_project_id,
            $category_id,
            $target_sort_order,
            $condition_category_id,
            $condition_item_ids
        );

        if ($category_id <= 0 || !$category_context) {
            $error = 'Please select a valid category.';
        } elseif ($name === '') {
            $error = 'Please enter an option name.';
        } elseif ($price_min < 0 || $price_max < 0) {
            $error = 'Prices cannot be negative.';
        } elseif ($price_max < $price_min) {
            $error = 'Max price must be greater than or equal to min price.';
        } elseif ($condition_error !== '') {
            $error = $condition_error;
        } else {

            $created = Bid_PDX_Item_Manager::create([
                'category_id'  => $category_id,
                'name'         => $name,
                'description'  => $description,
                'unit'                  => $unit,
                'price_min'             => $price_min,
                'price_max'             => $price_max,
                'condition_category_id' => $condition_category_id,
                'condition_item_ids'    => $condition_item_ids,
                'condition_match'       => $condition_match,
                'sort_order'            => $sort_order,
                'active'                => $active,
            ]);

            if ($created) {
                $message = 'Estimator option added successfully.';

                $_POST = [];
            } else {
                $error = 'The estimator option could not be saved. ' . esc_html($wpdb->last_error);
            }

        }

    }

    ?>

    <div class="wrap">

        <h1>Add Estimator Option</h1>

        <?php if ($message) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error) : ?>
            <div class="notice notice-error">
                <p><?php echo esc_html($error); ?></p>
            </div>
        <?php endif; ?>

        <?php if (empty($categories)) : ?>

            <div class="notice notice-warning">
                <p>
                    You need to create at least one active project and one active category before adding estimator options.
                </p>
            </div>

        <?php else : ?>

            <form method="post" action="">

                <?php wp_nonce_field('bid_pdx_save_item', 'bid_pdx_item_nonce'); ?>

                <table class="form-table" role="presentation">

                    <tr>
                        <th scope="row">
                            <label for="category_id">Category</label>
                        </th>
                        <td>
                            <select name="category_id" id="category_id" required>
                                <option value="">Select category</option>

                                <?php foreach ($categories as $category) : ?>
                                    <option
                                        value="<?php echo esc_attr($category->id); ?>"
                                        <?php selected(isset($_POST['category_id']) ? intval($_POST['category_id']) : 0, intval($category->id)); ?>
                                    >
                                        <?php echo esc_html($category->project_name . ' → ' . $category->category_name); ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>

                            <p class="description">
                                The option belongs to a category. The project is inherited automatically.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="name">Option Name</label>
                        </th>
                        <td>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="regular-text"
                                value="<?php echo esc_attr(isset($_POST['name']) ? $_POST['name'] : ''); ?>"
                                required
                            >

                            <p class="description">
                                Example: Basic Cabinets, Quartz Countertops, Luxury Vinyl Flooring.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="description">Description</label>
                        </th>
                        <td>
                            <textarea
                                name="description"
                                id="description"
                                rows="4"
                                class="large-text"
                            ><?php echo esc_textarea(isset($_POST['description']) ? $_POST['description'] : ''); ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="unit">Unit</label>
                        </th>
                        <td>
                            <input
                                type="text"
                                name="unit"
                                id="unit"
                                class="regular-text"
                                value="<?php echo esc_attr(isset($_POST['unit']) ? $_POST['unit'] : ''); ?>"
                            >

                            <p class="description">
                                Optional. Example: each, sq ft, allowance, project.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="price_min">Minimum Price</label>
                        </th>
                        <td>
                            <input
                                type="number"
                                name="price_min"
                                id="price_min"
                                step="0.01"
                                min="0"
                                class="regular-text"
                                value="<?php echo esc_attr(isset($_POST['price_min']) ? $_POST['price_min'] : ''); ?>"
                                required
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="price_max">Maximum Price</label>
                        </th>
                        <td>
                            <input
                                type="number"
                                name="price_max"
                                id="price_max"
                                step="0.01"
                                min="0"
                                class="regular-text"
                                value="<?php echo esc_attr(isset($_POST['price_max']) ? $_POST['price_max'] : ''); ?>"
                                required
                            >
                        </td>
                    </tr>

                    <?php
                    bid_pdx_admin_render_condition_fields([
                        'condition_category_id' => isset($_POST['condition_category_id'])
                            ? intval($_POST['condition_category_id'])
                            : 0,
                        'condition_item_ids' => isset($_POST['condition_item_ids'])
                            ? wp_unslash($_POST['condition_item_ids'])
                            : [],
                        'condition_match' => isset($_POST['condition_match'])
                            ? sanitize_key(wp_unslash($_POST['condition_match']))
                            : 'any',
                        'exclude_category_id' => isset($_POST['category_id'])
                            ? intval($_POST['category_id'])
                            : 0,
                    ]);
                    ?>

                    <tr>
                        <th scope="row">
                            <label for="sort_order">Display Order</label>
                        </th>
                        <td>
                            <input
                                type="number"
                                name="sort_order"
                                id="sort_order"
                                class="small-text"
                                value="<?php echo esc_attr(isset($_POST['sort_order']) ? $_POST['sort_order'] : 0); ?>"
                            >
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">Active</th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="active"
                                    value="1"
                                    <?php checked(!isset($_POST['bid_pdx_item_nonce']) || isset($_POST['active'])); ?>
                                >
                                Show this option in the estimator
                            </label>
                        </td>
                    </tr>

                </table>

                <?php submit_button('Add Estimator Option'); ?>

            </form>

        <?php endif; ?>

    </div>

    <?php

}
