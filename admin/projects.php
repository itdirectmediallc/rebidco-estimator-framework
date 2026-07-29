<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Projects List Page
 */
function bid_pdx_projects_page() {

    if (!current_user_can('manage_options')) {
        return;
    }

    $projects = Bid_PDX_Project_Manager::get_all();

    ?>

    <div class="wrap">

        <h1 class="wp-heading-inline">Projects</h1>

        <a href="<?php echo esc_url(admin_url('admin.php?page=bid-pdx-project-add')); ?>" class="page-title-action">
            Add New
        </a>

        <hr class="wp-header-end">

        <?php if (empty($projects)) : ?>

            <div class="notice notice-info">
                <p>
                    No projects found yet.
                    Click <strong>Add New</strong> to create your first project.
                </p>
            </div>

        <?php else : ?>

            <table class="wp-list-table widefat fixed striped">

                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($projects as $project) : ?>

                        <tr>
                            <td>
                                <strong><?php echo esc_html($project->name); ?></strong>
                            </td>

                            <td>
                                <?php echo esc_html($project->slug); ?>
                            </td>

                            <td>
                                <?php if (!empty($project->description)) : ?>
                                    <?php echo esc_html($project->description); ?>
                                <?php else : ?>
                                    <em>No description</em>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo esc_html(intval($project->sort_order)); ?>
                            </td>

                            <td>
                                <?php if (intval($project->active) === 1) : ?>
                                    <span style="color: #008a20; font-weight: 600;">Active</span>
                                <?php else : ?>
                                    <span style="color: #b32d2e; font-weight: 600;">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=bid-pdx-project-edit&project_id=' . intval($project->id))); ?>">
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