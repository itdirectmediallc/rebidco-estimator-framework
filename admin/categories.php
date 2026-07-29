<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Categories List Page
 */
function bid_pdx_categories_page() {

    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;

    $projects_table   = $wpdb->prefix . 'bid_pdx_projects';
    $categories_table = $wpdb->prefix . 'bid_pdx_categories';

    $categories = $wpdb->get_results(
        "SELECT
            c.id,
            c.name,
            c.description,
            c.selection_type,
            c.sort_order,
            c.active,
            p.name AS project_name
         FROM {$categories_table} c
         INNER JOIN {$projects_table} p ON p.id = c.project_id
         ORDER BY
            p.sort_order ASC,
            p.name ASC,
            c.sort_order ASC,
            c.name ASC"
    );

    ?>

    <div class="wrap">

        <h1 class="wp-heading-inline">Categories</h1>

        <a href="<?php echo esc_url(admin_url('admin.php?page=bid-pdx-category-add')); ?>" class="page-title-action">
            Add New
        </a>

        <hr class="wp-header-end">

        <?php if (empty($categories)) : ?>

            <div class="notice notice-info">
                <p>
                    No categories found yet.
                    Click <strong>Add New</strong> to create your first category.
                </p>
            </div>

        <?php else : ?>

            <table class="wp-list-table widefat fixed striped">

                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Category</th>
                        <th>Selection Type</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($categories as $category) : ?>

                        <?php
                        $selection_type = isset($category->selection_type) && in_array($category->selection_type, ['single', 'multiple'], true)
                            ? $category->selection_type
                            : 'single';
                        ?>

                        <tr>
                            <td>
                                <?php echo esc_html($category->project_name); ?>
                            </td>

                            <td>
                                <strong><?php echo esc_html($category->name); ?></strong>

                                <?php if (!empty($category->description)) : ?>
                                    <div style="color: #646970; margin-top: 4px;">
                                        <?php echo esc_html($category->description); ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo esc_html($selection_type === 'multiple' ? 'Multiple choice' : 'Single choice'); ?>
                            </td>

                            <td>
                                <?php echo esc_html(intval($category->sort_order)); ?>
                            </td>

                            <td>
                                <?php if (intval($category->active) === 1) : ?>
                                    <span style="color: #008a20; font-weight: 600;">Active</span>
                                <?php else : ?>
                                    <span style="color: #b32d2e; font-weight: 600;">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=bid-pdx-category-edit&category_id=' . intval($category->id))); ?>">
                                    Edit
                                </a>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </div>

    <?php

}