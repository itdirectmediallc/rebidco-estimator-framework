<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add Category Page
 */
function bid_pdx_category_add_page() {

    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;

    $message = '';
    $error   = '';

    $projects = Bid_PDX_Project_Manager::get_all();

    $active_projects = [];

    foreach ($projects as $project) {
        if (intval($project->active) === 1) {
            $active_projects[] = $project;
        }
    }

    if (
        isset($_POST['bid_pdx_category_nonce']) &&
        wp_verify_nonce($_POST['bid_pdx_category_nonce'], 'bid_pdx_save_category')
    ) {

        $project_id     = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $name           = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $description    = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';
        $selection_type        = isset($_POST['selection_type']) ? sanitize_text_field(wp_unslash($_POST['selection_type'])) : 'single';
        $condition_category_id = isset($_POST['condition_category_id']) ? intval($_POST['condition_category_id']) : 0;
        $condition_item_ids    = isset($_POST['condition_item_ids'])
            ? bid_pdx_admin_normalize_condition_item_ids(wp_unslash($_POST['condition_item_ids']))
            : [];
        $condition_match       = isset($_POST['condition_match']) ? sanitize_key(wp_unslash($_POST['condition_match'])) : 'any';
        $sort_order            = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
        $active                = isset($_POST['active']) ? 1 : 0;

        if (!in_array($selection_type, ['single', 'multiple'], true)) {
            $selection_type = 'single';
        }

        if (!in_array($condition_match, ['any', 'all'], true)) {
            $condition_match = 'any';
        }

        if ($condition_category_id <= 0) {
            $condition_category_id = 0;
            $condition_item_ids    = [];
            $condition_match       = 'any';
        }

        $condition_error = bid_pdx_admin_validate_condition_rule(
            $project_id,
            0,
            $sort_order,
            $condition_category_id,
            $condition_item_ids
        );

        if ($project_id <= 0) {
            $error = 'Please select a project.';
        } elseif ($name === '') {
            $error = 'Please enter a category name.';
        } elseif ($condition_error !== '') {
            $error = $condition_error;
        } else {

            $created = Bid_PDX_Category_Manager::create([
                'project_id'     => $project_id,
                'name'           => $name,
                'description'           => $description,
                'selection_type'        => $selection_type,
                'condition_category_id' => $condition_category_id,
                'condition_item_ids'    => $condition_item_ids,
                'condition_match'       => $condition_match,
                'sort_order'            => $sort_order,
                'active'                => $active,
            ]);

            if ($created) {
                $message = 'Category added successfully.';
                $_POST = [];
            } else {
                $error = 'The category could not be saved. ' . esc_html($wpdb->last_error);
            }

        }

    }

    ?>

    <div class="wrap">

        <h1>Add Category</h1>

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

        <?php if (empty($active_projects)) : ?>

            <div class="notice notice-warning">
                <p>You need to create at least one active project before adding categories.</p>
            </div>

        <?php else : ?>

            <form method="post" action="">

                <?php wp_nonce_field('bid_pdx_save_category', 'bid_pdx_category_nonce'); ?>

                <table class="form-table" role="presentation">

                    <tr>
                        <th scope="row">
                            <label for="project_id">Project</label>
                        </th>
                        <td>
                            <select name="project_id" id="project_id" required>
                                <option value="">Select project</option>

                                <?php foreach ($active_projects as $project) : ?>
                                    <option
                                        value="<?php echo esc_attr($project->id); ?>"
                                        <?php selected(isset($_POST['project_id']) ? intval($_POST['project_id']) : 0, intval($project->id)); ?>
                                    >
                                        <?php echo esc_html($project->name); ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="name">Category Name</label>
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
                                Example: Cabinets, Countertops, Electrical, Plumbing.
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
                            <label for="selection_type">Selection Type</label>
                        </th>
                        <td>
                            <select name="selection_type" id="selection_type">
                                <option value="single" <?php selected(isset($_POST['selection_type']) ? $_POST['selection_type'] : 'single', 'single'); ?>>
                                    Single choice
                                </option>
                                <option value="multiple" <?php selected(isset($_POST['selection_type']) ? $_POST['selection_type'] : '', 'multiple'); ?>>
                                    Multiple choice
                                </option>
                            </select>

                            <p class="description">
                                Use single choice when the visitor should choose only one option. Use multiple choice when they can select more than one.
                            </p>
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
                                    <?php checked(!isset($_POST['bid_pdx_category_nonce']) || isset($_POST['active'])); ?>
                                >
                                Show this category in the estimator
                            </label>
                        </td>
                    </tr>

                </table>

                <?php submit_button('Add Category'); ?>

            </form>

        <?php endif; ?>

    </div>

    <?php

}