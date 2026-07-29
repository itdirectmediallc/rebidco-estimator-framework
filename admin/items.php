<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Estimator Options List Page
 */
function bid_pdx_items_page() {

    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;

    $message = '';
    $error   = '';

    /*
    |--------------------------------------------------------------------------
    | Delete Estimator Option
    |--------------------------------------------------------------------------
    */

    if (
        isset($_GET['bid_pdx_action']) &&
        $_GET['bid_pdx_action'] === 'delete' &&
        isset($_GET['item_id'])
    ) {

        $item_id = intval($_GET['item_id']);

        if ($item_id > 0) {

            check_admin_referer('bid_pdx_delete_item_' . $item_id);

            $deleted = Bid_PDX_Item_Manager::delete($item_id);

            if ($deleted) {
                $message = 'Estimator option deleted successfully.';
            } else {
                $error = 'Estimator option could not be deleted.';
            }

        }

    }

    $projects_table   = $wpdb->prefix . 'bid_pdx_projects';
    $categories_table = $wpdb->prefix . 'bid_pdx_categories';
    $items_table      = $wpdb->prefix . 'bid_pdx_items';

    $items = $wpdb->get_results(
        "SELECT
            i.id,
            i.name,
            i.unit,
            i.price_min,
            i.price_max,
            i.sort_order,
            i.active,
            c.name AS category_name,
            p.name AS project_name
         FROM {$items_table} i
         INNER JOIN {$categories_table} c ON c.id = i.category_id
         INNER JOIN {$projects_table} p ON p.id = c.project_id
         ORDER BY
            p.sort_order ASC,
            p.name ASC,
            c.sort_order ASC,
            c.name ASC,
            i.sort_order ASC,
            i.name ASC"
    );

    ?>

    <div class="wrap">

        <h1 class="wp-heading-inline">Estimator Options</h1>

        <a href="<?php echo esc_url(admin_url('admin.php?page=bid-pdx-item-add')); ?>" class="page-title-action">
            Add New
        </a>

        <hr class="wp-header-end">

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

        <?php if (empty($items)) : ?>

            <div class="notice notice-info">
                <p>
                    No estimator options found yet.
                    Click <strong>Add New</strong> to create your first estimator option.
                </p>
            </div>

        <?php else : ?>

            <table class="wp-list-table widefat fixed striped">

                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Category</th>
                        <th>Option</th>
                        <th>Unit</th>
                        <th>Price Range</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($items as $item) : ?>

                        <?php
                        $edit_url = admin_url('admin.php?page=bid-pdx-item-edit&item_id=' . intval($item->id));

                        $delete_url = wp_nonce_url(
                            admin_url('admin.php?page=bid-pdx-items&bid_pdx_action=delete&item_id=' . intval($item->id)),
                            'bid_pdx_delete_item_' . intval($item->id)
                        );
                        ?>

                        <tr>
                            <td>
                                <?php echo esc_html($item->project_name); ?>
                            </td>

                            <td>
                                <?php echo esc_html($item->category_name); ?>
                            </td>

                            <td>
                                <strong><?php echo esc_html($item->name); ?></strong>
                            </td>

                            <td>
                                <?php echo esc_html($item->unit); ?>
                            </td>

                            <td>
                                $<?php echo esc_html(number_format_i18n(floatval($item->price_min))); ?>
                                –
                                $<?php echo esc_html(number_format_i18n(floatval($item->price_max))); ?>
                            </td>

                            <td>
                                <?php echo esc_html(intval($item->sort_order)); ?>
                            </td>

                            <td>
                                <?php if (intval($item->active) === 1) : ?>
                                    <span style="color: #008a20; font-weight: 600;">Active</span>
                                <?php else : ?>
                                    <span style="color: #b32d2e; font-weight: 600;">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a href="<?php echo esc_url($edit_url); ?>">
                                    Edit
                                </a>

                                |

                                <a
                                    href="<?php echo esc_url($delete_url); ?>"
                                    style="color: #b32d2e;"
                                    onclick="return confirm('Are you sure you want to delete this estimator option?');"
                                >
                                    Delete
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