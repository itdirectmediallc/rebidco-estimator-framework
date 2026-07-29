<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle frontend lead form submission.
 */
function bid_pdx_submit_lead() {

    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'bid_pdx_submit_lead')
    ) {
        wp_send_json_error([
            'message' => 'Security check failed. Please refresh the page and try again.',
        ]);
    }

    $started_at = isset($_POST['form_started_at']) ? intval($_POST['form_started_at']) : 0;
    $honeypot   = isset($_POST['company_website']) ? sanitize_text_field(wp_unslash($_POST['company_website'])) : '';

    if ($honeypot !== '') {
        wp_send_json_error([
            'message' => 'Your request could not be submitted. Please try again.',
        ]);
    }

    if ($started_at <= 0 || (time() - $started_at) < 4) {
        wp_send_json_error([
            'message' => 'Please wait a moment before submitting the form.',
        ]);
    }

    $customer_name          = isset($_POST['customer_name']) ? sanitize_text_field(wp_unslash($_POST['customer_name'])) : '';
    $customer_email         = isset($_POST['customer_email']) ? sanitize_email(wp_unslash($_POST['customer_email'])) : '';
    $customer_email_confirm = isset($_POST['customer_email_confirm']) ? sanitize_email(wp_unslash($_POST['customer_email_confirm'])) : '';
    $customer_phone         = isset($_POST['customer_phone']) ? sanitize_text_field(wp_unslash($_POST['customer_phone'])) : '';
    $zip_code               = isset($_POST['zip_code']) ? sanitize_text_field(wp_unslash($_POST['zip_code'])) : '';
    $message                = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';

    $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;

    $selected_item_ids = [];

    if (isset($_POST['selected_item_ids'])) {
        $raw_item_ids = json_decode(wp_unslash($_POST['selected_item_ids']), true);

        if (is_array($raw_item_ids)) {
            foreach ($raw_item_ids as $item_id) {
                $item_id = intval($item_id);

                if ($item_id > 0) {
                    $selected_item_ids[] = $item_id;
                }
            }
        }
    }

    $selected_item_ids = array_values(array_unique($selected_item_ids));

    if ($customer_name === '') {
        wp_send_json_error([
            'message' => 'Please enter your name.',
        ]);
    }

    if ($customer_email === '' || !is_email($customer_email)) {
        wp_send_json_error([
            'message' => 'Please enter a valid email address.',
        ]);
    }

    if ($customer_email_confirm === '' || strtolower($customer_email) !== strtolower($customer_email_confirm)) {
        wp_send_json_error([
            'message' => 'Please make sure both email fields match.',
        ]);
    }

    if ($customer_phone === '') {
        wp_send_json_error([
            'message' => 'Please enter your phone number.',
        ]);
    }

    if ($zip_code === '') {
        wp_send_json_error([
            'message' => 'Please enter your project address.',
        ]);
    }

    if ($project_id <= 0) {
        wp_send_json_error([
            'message' => 'Please select a valid project.',
        ]);
    }

    if (empty($selected_item_ids)) {
        wp_send_json_error([
            'message' => 'Please select at least one estimator option.',
        ]);
    }

    $engine       = new Bid_PDX_Estimator_Engine();
    $project_data = $engine->get_project_estimator_data($project_id);

    if (empty($project_data) || empty($project_data['project']) || empty($project_data['categories'])) {
        wp_send_json_error([
            'message' => 'This estimator is not available. Please refresh the page and try again.',
        ]);
    }

    $project_name = sanitize_text_field($project_data['project']['name']);

    $selection_state = $engine->evaluate_selection(
        $project_data,
        $selected_item_ids
    );

    $requested_item_ids = isset($selection_state['requested_item_ids'])
        && is_array($selection_state['requested_item_ids'])
        ? array_map('intval', $selection_state['requested_item_ids'])
        : [];

    $valid_selected_item_ids = isset($selection_state['valid_selected_item_ids'])
        && is_array($selection_state['valid_selected_item_ids'])
        ? array_map('intval', $selection_state['valid_selected_item_ids'])
        : [];

    if (!empty(array_diff($requested_item_ids, $valid_selected_item_ids))) {
        wp_send_json_error([
            'message' => 'One of your selected options is not available for the chosen project route. Please review your selections.',
        ]);
    }

    $visible_category_ids = isset($selection_state['visible_category_ids'])
        && is_array($selection_state['visible_category_ids'])
        ? array_map('intval', $selection_state['visible_category_ids'])
        : [];

    $visible_category_lookup = array_fill_keys($visible_category_ids, true);

    $selected_by_category = isset($selection_state['selected_by_category'])
        && is_array($selection_state['selected_by_category'])
        ? $selection_state['selected_by_category']
        : [];

    foreach ($project_data['categories'] as $category) {

        $category_id = isset($category['id'])
            ? intval($category['id'])
            : 0;

        if (
            $category_id <= 0 ||
            !isset($visible_category_lookup[$category_id]) ||
            !isset($category['selection_type']) ||
            $category['selection_type'] !== 'single'
        ) {
            continue;
        }

        $selected_count = isset($selected_by_category[$category_id])
            && is_array($selected_by_category[$category_id])
            ? count($selected_by_category[$category_id])
            : 0;

        if ($selected_count !== 1) {
            wp_send_json_error([
                'message' => 'Please complete all required estimator steps.',
            ]);
        }
    }

    $selected_item_ids = array_values(array_unique($valid_selected_item_ids));

    if (empty($selected_item_ids)) {
        wp_send_json_error([
            'message' => 'Please select at least one estimator option.',
        ]);
    }

    $estimate = $engine->calculate_estimate($selected_item_ids);

    $estimate_min     = isset($estimate['min']) ? floatval($estimate['min']) : 0;
    $estimate_max     = isset($estimate['max']) ? floatval($estimate['max']) : 0;
    $selected_options = [];

    if (!empty($estimate['items']) && is_array($estimate['items'])) {
        foreach ($estimate['items'] as $item) {
            if (!empty($item['name'])) {
                $selected_options[] = sanitize_text_field($item['name']);
            }
        }
    }

    if (empty($selected_options)) {
        wp_send_json_error([
            'message' => 'Please select at least one estimator option.',
        ]);
    }

    $lead_id = Bid_PDX_Lead_Manager::create([
        'project_id'       => $project_id,
        'project_name'     => $project_name,
        'customer_name'    => $customer_name,
        'customer_email'   => $customer_email,
        'customer_phone'   => $customer_phone,
        'zip_code'         => $zip_code,
        'estimate_min'     => $estimate_min,
        'estimate_max'     => $estimate_max,
        'selected_options' => $selected_options,
        'message'          => $message,
        'status'           => 'new',
    ]);

    if (!$lead_id) {
        wp_send_json_error([
            'message' => 'Your request could not be saved. Please try again.',
        ]);
    }

    bid_pdx_send_lead_notification($lead_id);

    wp_send_json_success([
        'message' => 'Thank you. Your request has been received.',
        'lead_id' => $lead_id,
    ]);

}

add_action('wp_ajax_bid_pdx_submit_lead', 'bid_pdx_submit_lead');
add_action('wp_ajax_nopriv_bid_pdx_submit_lead', 'bid_pdx_submit_lead');

/**
 * Send a simple admin email notification for a new lead.
 */
function bid_pdx_send_lead_notification($lead_id) {

    $lead = Bid_PDX_Lead_Manager::get($lead_id);

    if (!$lead) {
        return;
    }

    $selected_options = json_decode($lead->selected_options, true);

    if (!is_array($selected_options)) {
        $selected_options = [];
    }

    $to = bid_pdx_get_lead_notification_email();

    if (empty($to) || !is_email($to)) {
        $to = get_option('admin_email');
    }

    $subject = 'New Estimator Lead';

    $body  = "A new estimator lead was submitted.\n\n";
    $body .= "Project: " . $lead->project_name . "\n";
    $body .= "Estimated Budget: $" . number_format(floatval($lead->estimate_min)) . " - $" . number_format(floatval($lead->estimate_max)) . "\n\n";
    $body .= "Customer Name: " . $lead->customer_name . "\n";
    $body .= "Email: " . $lead->customer_email . "\n";
    $body .= "Phone: " . $lead->customer_phone . "\n";
    $body .= "Project Address: " . $lead->zip_code . "\n\n";

    $body .= "Selected Options:\n";

    foreach ($selected_options as $option) {
        $body .= "- " . sanitize_text_field($option) . "\n";
    }

    if (!empty($lead->message)) {
        $body .= "\nMessage:\n" . $lead->message . "\n";
    }

    $headers = [];

    $reply_to_name  = str_replace(["\r", "\n"], '', sanitize_text_field($lead->customer_name));
    $reply_to_email = sanitize_email($lead->customer_email);

    if (!empty($reply_to_email) && is_email($reply_to_email)) {
        $headers[] = 'Reply-To: ' . $reply_to_name . ' <' . $reply_to_email . '>';
    }

    $mail_from_name = bid_pdx_get_site_profile_value(
        'mail_from_name',
        'Estimator'
    );

    $mail_from_name_filter = static function ($from_name) use ($mail_from_name) {
        return $mail_from_name;
    };

    add_filter('wp_mail_from_name', $mail_from_name_filter, 999);

    wp_mail($to, $subject, $body, $headers);

    remove_filter('wp_mail_from_name', $mail_from_name_filter, 999);

}
