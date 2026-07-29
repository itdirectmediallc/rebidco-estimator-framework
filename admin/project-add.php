<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once BID_PDX_PATH . 'includes/class-project-manager.php';

function bid_pdx_project_add_page() {

    if (!current_user_can('manage_options')) {
        return;
    }

    $message = '';
    $error   = '';

    if (
        isset($_POST['bid_pdx_project_nonce']) &&
        wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['bid_pdx_project_nonce'])),
            'bid_pdx_add_project'
        )
    ) {
        $name = isset($_POST['project_name'])
            ? sanitize_text_field(wp_unslash($_POST['project_name']))
            : '';

        $slug = isset($_POST['project_slug'])
            ? sanitize_title(wp_unslash($_POST['project_slug']))
            : '';

        if ($slug === '') {
            $slug = sanitize_title($name);
        }

        $description = isset($_POST['project_description'])
            ? sanitize_textarea_field(wp_unslash($_POST['project_description']))
            : '';

        $sort_order = isset($_POST['sort_order'])
            ? intval($_POST['sort_order'])
            : 0;

        $active = isset($_POST['active']) ? 1 : 0;

        if ($name === '') {
            $error = 'Project name is required.';
        } else {
            $created = Bid_PDX_Project_Manager::create([
                'name'        => $name,
                'slug'        => $slug,
                'description' => $description,
                'sort_order'  => $sort_order,
                'active'      => $active,
            ]);

            if ($created) {
                $message = 'Project saved successfully.';
            } else {
                $error = 'The project could not be saved.';
            }
        }
    }

    ?>

    <div class="wrap">

        <h1>Add Project</h1>

        <?php if ($message !== '') : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error !== '') : ?>
            <div class="notice notice-error">
                <p><?php echo esc_html($error); ?></p>
            </div>
        <?php endif; ?>

        <form method="post">

            <?php wp_nonce_field('bid_pdx_add_project', 'bid_pdx_project_nonce'); ?>

            <table class="form-table">

                <tr>
                    <th><label for="project_name">Project Name</label></th>
                    <td>
                        <input
                            type="text"
                            id="project_name"
                            name="project_name"
                            class="regular-text"
                            value="<?php echo esc_attr(isset($_POST['project_name']) ? wp_unslash($_POST['project_name']) : ''); ?>"
                            required
                        >
                    </td>
                </tr>

                <tr>
                    <th><label for="project_slug">Slug</label></th>
                    <td>
                        <input
                            type="text"
                            id="project_slug"
                            name="project_slug"
                            class="regular-text"
                            value="<?php echo esc_attr(isset($_POST['project_slug']) ? wp_unslash($_POST['project_slug']) : ''); ?>"
                        >
                    </td>
                </tr>

                <tr>
                    <th><label for="project_description">Description</label></th>
                    <td>
                        <textarea
                            id="project_description"
                            name="project_description"
                            rows="5"
                            class="large-text"
                        ><?php echo esc_textarea(isset($_POST['project_description']) ? wp_unslash($_POST['project_description']) : ''); ?></textarea>
                    </td>
                </tr>

                <tr>
                    <th><label for="sort_order">Display Order</label></th>
                    <td>
                        <input
                            type="number"
                            id="sort_order"
                            name="sort_order"
                            value="<?php echo esc_attr(isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0); ?>"
                        >
                    </td>
                </tr>

                <tr>
                    <th>Status</th>
                    <td>
                        <label>
                            <input
                                type="checkbox"
                                name="active"
                                value="1"
                                <?php checked(!isset($_POST['bid_pdx_project_nonce']) || isset($_POST['active'])); ?>
                            >

                            Active
                        </label>
                    </td>
                </tr>

            </table>

            <?php submit_button('Save Project'); ?>

        </form>

    </div>

    <?php
}
