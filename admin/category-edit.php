<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Edit Category Page
 */
function bid_pdx_category_edit_page() {

    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;

    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

    if ($category_id <= 0) {
        ?>
        <div class="wrap">
            <h1>Edit Category</h1>
            <div class="notice notice-error">
                <p>Invalid category.</p>
            </div>
        </div>
        <?php
        return;
    }

    $category = Bid_PDX_Category_Manager::get($category_id);

    if (!$category) {
        ?>
        <div class="wrap">
            <h1>Edit Category</h1>
            <div class="notice notice-error">
                <p>Category not found.</p>
            </div>
        </div>
        <?php
        return;
    }

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
        wp_verify_nonce($_POST['bid_pdx_category_nonce'], 'bid_pdx_update_category_' . $category_id)
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
            $category_id,
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

            $updated = Bid_PDX_Category_Manager::update(
                $category_id,
                [
                    'project_id'     => $project_id,
                    'name'           => $name,
                    'description'           => $description,
                    'selection_type'        => $selection_type,
                    'condition_category_id' => $condition_category_id,
                    'condition_item_ids'    => $condition_item_ids,
                    'condition_match'       => $condition_match,
                    'sort_order'            => $sort_order,
                    'active'                => $active,
                ]
            );

            if ($updated !== false) {
                $message = 'Category updated successfully.';
                $category = Bid_PDX_Category_Manager::get($category_id);
            } else {
                $error = 'The category could not be updated. ' . esc_html($wpdb->last_error);
            }

        }

    }

    $current_selection_type = isset($category->selection_type) && in_array($category->selection_type, ['single', 'multiple'], true)
        ? $category->selection_type
        : 'single';

    ?>

    <div class="wrap">

        <h1>Edit Category</h1>

        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=bid-pdx-categories')); ?>">
                ← Back to Categories
            </a>
        </p>

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

        <form method="post" action="">

            <?php wp_nonce_field('bid_pdx_update_category_' . $category_id, 'bid_pdx_category_nonce'); ?>

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
                                    <?php selected(intval($category->project_id), intval($project->id)); ?>
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
                            value="<?php echo esc_attr($category->name); ?>"
                            required
                        >
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
                        ><?php echo esc_textarea($category->description); ?></textarea>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="selection_type">Selection Type</label>
                    </th>
                    <td>
                        <select name="selection_type" id="selection_type">
                            <option value="single" <?php selected($current_selection_type, 'single'); ?>>
                                Single choice
                            </option>
                            <option value="multiple" <?php selected($current_selection_type, 'multiple'); ?>>
                                Multiple choice
                            </option>
                        </select>

                        <p class="description">
                            Use single choice when the visitor should choose only one option. Use multiple choice when they can select more than one.
                        </p>
                    </td>
                </tr>

                <?php
                $submitted_condition_category_id = isset($_POST['bid_pdx_category_nonce'])
                    ? (isset($_POST['condition_category_id']) ? intval($_POST['condition_category_id']) : 0)
                    : (isset($category->condition_category_id) ? intval($category->condition_category_id) : 0);

                $submitted_condition_item_ids = isset($_POST['bid_pdx_category_nonce'])
                    ? (isset($_POST['condition_item_ids']) ? wp_unslash($_POST['condition_item_ids']) : [])
                    : (isset($category->condition_item_ids) ? $category->condition_item_ids : '');

                $submitted_condition_match = isset($_POST['bid_pdx_category_nonce'])
                    ? (isset($_POST['condition_match']) ? sanitize_key(wp_unslash($_POST['condition_match'])) : 'any')
                    : (isset($category->condition_match) ? sanitize_key($category->condition_match) : 'any');

                bid_pdx_admin_render_condition_fields([
                    'condition_category_id' => $submitted_condition_category_id,
                    'condition_item_ids'    => $submitted_condition_item_ids,
                    'condition_match'       => $submitted_condition_match,
                    'exclude_category_id'   => $category_id,
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
                            value="<?php echo esc_attr($category->sort_order); ?>"
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
                                <?php checked(intval($category->active), 1); ?>
                            >
                            Show this category in the estimator
                        </label>
                    </td>
                </tr>

            </table>

            <?php submit_button('Update Category'); ?>

        </form>

    </div>

    <?php

}