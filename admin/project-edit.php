<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Edit Project Page
 */
function bid_pdx_project_edit_page() {

    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;

    $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

    if ($project_id <= 0) {
        ?>
        <div class="wrap">
            <h1>Edit Project</h1>
            <div class="notice notice-error">
                <p>Invalid project.</p>
            </div>
        </div>
        <?php
        return;
    }

    $project = Bid_PDX_Project_Manager::get($project_id);

    if (!$project) {
        ?>
        <div class="wrap">
            <h1>Edit Project</h1>
            <div class="notice notice-error">
                <p>Project not found.</p>
            </div>
        </div>
        <?php
        return;
    }

    $message = '';
    $error   = '';

    if (
        isset($_POST['bid_pdx_project_nonce']) &&
        wp_verify_nonce($_POST['bid_pdx_project_nonce'], 'bid_pdx_update_project_' . $project_id)
    ) {

        $name        = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $slug        = isset($_POST['slug']) ? sanitize_title(wp_unslash($_POST['slug'])) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';
        $sort_order  = isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0;
        $active      = isset($_POST['active']) ? 1 : 0;

        if ($name === '') {
            $error = 'Please enter a project name.';
        } else {

            if ($slug === '') {
                $slug = sanitize_title($name);
            }

            $updated = Bid_PDX_Project_Manager::update(
                $project_id,
                [
                    'name'        => $name,
                    'slug'        => $slug,
                    'description' => $description,
                    'sort_order'  => $sort_order,
                    'active'      => $active,
                ]
            );

            if ($updated !== false) {
                $message = 'Project updated successfully.';
                $project = Bid_PDX_Project_Manager::get($project_id);
            } else {
                $error = 'The project could not be updated. ' . esc_html($wpdb->last_error);
            }

        }

    }

    ?>

    <div class="wrap">

        <h1>Edit Project</h1>

        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=bid-pdx-projects')); ?>">
                ← Back to Projects
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

            <?php wp_nonce_field('bid_pdx_update_project_' . $project_id, 'bid_pdx_project_nonce'); ?>

            <table class="form-table" role="presentation">

                <tr>
                    <th scope="row">
                        <label for="name">Project Name</label>
                    </th>
                    <td>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="regular-text"
                            value="<?php echo esc_attr($project->name); ?>"
                            required
                        >
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="slug">Slug</label>
                    </th>
                    <td>
                        <input
                            type="text"
                            name="slug"
                            id="slug"
                            class="regular-text"
                            value="<?php echo esc_attr($project->slug); ?>"
                        >

                        <p class="description">
                            Leave as-is unless you intentionally want to change the internal project slug.
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
                        ><?php echo esc_textarea($project->description); ?></textarea>
                    </td>
                </tr>

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
                            value="<?php echo esc_attr($project->sort_order); ?>"
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
                                <?php checked(intval($project->active), 1); ?>
                            >
                            Show this project in the estimator
                        </label>
                    </td>
                </tr>

            </table>

            <?php submit_button('Update Project'); ?>

        </form>

    </div>

    <?php

}