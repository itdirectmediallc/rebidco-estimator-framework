<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Make CSV fields safer for Excel.
 */
function bid_pdx_csv_safe_cell($value) {

    $value = wp_strip_all_tags((string) $value);
    $value = str_replace(["\r", "\n"], ' ', $value);
    $value = trim($value);

    if ($value !== '' && preg_match('/^[=+\-@\t]/', $value)) {
        $value = "'" . $value;
    }

    return $value;
}

/**
 * Export leads to CSV before the admin page outputs HTML.
 */
function bid_pdx_export_leads_csv() {

    if (
        !isset($_GET['page']) ||
        $_GET['page'] !== 'bid-pdx-leads' ||
        !isset($_GET['bid_pdx_action']) ||
        $_GET['bid_pdx_action'] !== 'export_csv'
    ) {
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to export leads.');
    }

    check_admin_referer('bid_pdx_export_leads_csv');

    $leads = Bid_PDX_Lead_Manager::get_all(10000);

    $filename = 'bid-pdx-leads-' . gmdate('Y-m-d') . '.csv';

    nocache_headers();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');

    echo "\xEF\xBB\xBF";

    fputcsv($output, [
        'Date',
        'Status',
        'Project',
        'Estimate Min',
        'Estimate Max',
        'Customer Name',
        'Email',
        'Phone',
        'ZIP',
        'Selected Options',
        'Message',
    ]);

    foreach ($leads as $lead) {

        $selected_options = json_decode($lead->selected_options, true);

        if (!is_array($selected_options)) {
            $selected_options = [];
        }

        $selected_options = array_map('bid_pdx_csv_safe_cell', $selected_options);

        $status = in_array($lead->status, ['new', 'contacted', 'closed'], true)
            ? $lead->status
            : 'new';

        fputcsv($output, [
            bid_pdx_csv_safe_cell(date_i18n('M j, Y g:i A', strtotime($lead->created_at))),
            bid_pdx_csv_safe_cell(ucfirst($status)),
            bid_pdx_csv_safe_cell($lead->project_name),
            number_format(floatval($lead->estimate_min), 2, '.', ''),
            number_format(floatval($lead->estimate_max), 2, '.', ''),
            bid_pdx_csv_safe_cell($lead->customer_name),
            bid_pdx_csv_safe_cell($lead->customer_email),
            bid_pdx_csv_safe_cell($lead->customer_phone),
            bid_pdx_csv_safe_cell($lead->zip_code),
            bid_pdx_csv_safe_cell(implode('; ', $selected_options)),
            bid_pdx_csv_safe_cell($lead->message),
        ]);

    }

    fclose($output);
    exit;
}

add_action('admin_init', 'bid_pdx_export_leads_csv');

/**
 * Leads List Page
 */
function bid_pdx_leads_page() {

    if (!current_user_can('manage_options')) {
        return;
    }

    $message = '';
    $error   = '';

    /*
    |--------------------------------------------------------------------------
    | Delete Lead
    |--------------------------------------------------------------------------
    */

    if (
        isset($_GET['bid_pdx_action']) &&
        $_GET['bid_pdx_action'] === 'delete' &&
        isset($_GET['lead_id'])
    ) {

        $lead_id = intval($_GET['lead_id']);

        if ($lead_id > 0) {

            check_admin_referer('bid_pdx_delete_lead_' . $lead_id);

            $deleted = Bid_PDX_Lead_Manager::delete($lead_id);

            if ($deleted) {
                $message = 'Lead deleted successfully.';
            } else {
                $error = 'Lead could not be deleted.';
            }

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Update Lead Status
    |--------------------------------------------------------------------------
    */

    if (
        isset($_GET['bid_pdx_action']) &&
        $_GET['bid_pdx_action'] === 'status' &&
        isset($_GET['lead_id']) &&
        isset($_GET['status'])
    ) {

        $lead_id = intval($_GET['lead_id']);
        $status  = sanitize_text_field(wp_unslash($_GET['status']));

        $allowed_statuses = ['new', 'contacted', 'closed'];

        if ($lead_id > 0 && in_array($status, $allowed_statuses, true)) {

            check_admin_referer('bid_pdx_update_lead_status_' . $lead_id);

            $updated = Bid_PDX_Lead_Manager::update_status($lead_id, $status);

            if ($updated !== false) {
                $message = 'Lead status updated successfully.';
            } else {
                $error = 'Lead status could not be updated.';
            }

        }

    }

    $leads = Bid_PDX_Lead_Manager::get_all(200);

    $export_url = wp_nonce_url(
        admin_url('admin.php?page=bid-pdx-leads&bid_pdx_action=export_csv'),
        'bid_pdx_export_leads_csv'
    );

    ?>

    <div class="wrap">

        <h1 class="wp-heading-inline">Estimator Leads</h1>

        <a href="<?php echo esc_url($export_url); ?>" class="page-title-action">
            Export CSV
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

        <?php if (empty($leads)) : ?>

            <div class="notice notice-info">
                <p>No estimator leads found yet.</p>
            </div>

        <?php else : ?>

            <table class="wp-list-table widefat fixed striped">

                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Project</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>ZIP</th>
                        <th>Estimate</th>
                        <th>Selected Options</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($leads as $lead) : ?>

                        <?php
                        $selected_options = json_decode($lead->selected_options, true);

                        if (!is_array($selected_options)) {
                            $selected_options = [];
                        }

                        $current_status = in_array($lead->status, ['new', 'contacted', 'closed'], true)
                            ? $lead->status
                            : 'new';

                        $new_url = wp_nonce_url(
                            admin_url('admin.php?page=bid-pdx-leads&bid_pdx_action=status&status=new&lead_id=' . intval($lead->id)),
                            'bid_pdx_update_lead_status_' . intval($lead->id)
                        );

                        $contacted_url = wp_nonce_url(
                            admin_url('admin.php?page=bid-pdx-leads&bid_pdx_action=status&status=contacted&lead_id=' . intval($lead->id)),
                            'bid_pdx_update_lead_status_' . intval($lead->id)
                        );

                        $closed_url = wp_nonce_url(
                            admin_url('admin.php?page=bid-pdx-leads&bid_pdx_action=status&status=closed&lead_id=' . intval($lead->id)),
                            'bid_pdx_update_lead_status_' . intval($lead->id)
                        );

                        $delete_url = wp_nonce_url(
                            admin_url('admin.php?page=bid-pdx-leads&bid_pdx_action=delete&lead_id=' . intval($lead->id)),
                            'bid_pdx_delete_lead_' . intval($lead->id)
                        );
                        ?>

                        <tr>

                            <td>
                                <?php echo esc_html(date_i18n('M j, Y g:i A', strtotime($lead->created_at))); ?>
                            </td>

                            <td>
                                <?php echo esc_html($lead->project_name); ?>
                            </td>

                            <td>
                                <strong><?php echo esc_html($lead->customer_name); ?></strong>
                            </td>

                            <td>
                                <div>
                                    <a href="mailto:<?php echo esc_attr($lead->customer_email); ?>">
                                        <?php echo esc_html($lead->customer_email); ?>
                                    </a>
                                </div>

                                <div>
                                    <?php echo esc_html($lead->customer_phone); ?>
                                </div>
                            </td>

                            <td>
                                <?php echo esc_html($lead->zip_code); ?>
                            </td>

                            <td>
                                $<?php echo esc_html(number_format_i18n(floatval($lead->estimate_min))); ?>
                                –
                                $<?php echo esc_html(number_format_i18n(floatval($lead->estimate_max))); ?>
                            </td>

                            <td>
                                <?php if (empty($selected_options)) : ?>

                                    <em>No options recorded</em>

                                <?php else : ?>

                                    <ul style="margin: 0; padding-left: 18px;">

                                        <?php foreach ($selected_options as $option) : ?>
                                            <li><?php echo esc_html($option); ?></li>
                                        <?php endforeach; ?>

                                    </ul>

                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (!empty($lead->message)) : ?>
                                    <?php echo esc_html($lead->message); ?>
                                <?php else : ?>
                                    <em>No message</em>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($current_status === 'new') : ?>
                                    <span style="color: #008a20; font-weight: 600;">New</span>
                                <?php elseif ($current_status === 'contacted') : ?>
                                    <span style="color: #996800; font-weight: 600;">Contacted</span>
                                <?php else : ?>
                                    <span style="color: #50575e; font-weight: 600;">Closed</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($current_status !== 'new') : ?>
                                    <a href="<?php echo esc_url($new_url); ?>">Mark New</a>
                                    |
                                <?php endif; ?>

                                <?php if ($current_status !== 'contacted') : ?>
                                    <a href="<?php echo esc_url($contacted_url); ?>">Contacted</a>
                                    |
                                <?php endif; ?>

                                <?php if ($current_status !== 'closed') : ?>
                                    <a href="<?php echo esc_url($closed_url); ?>">Closed</a>
                                    |
                                <?php endif; ?>

                                <a
                                    href="<?php echo esc_url($delete_url); ?>"
                                    style="color: #b32d2e;"
                                    onclick="return confirm('Are you sure you want to delete this lead?');"
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