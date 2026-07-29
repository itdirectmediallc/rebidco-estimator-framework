<?php

if (!defined('ABSPATH')) {
    exit;
}

function estimator_framework_submit_contractor() {
    if (
        !isset($_POST['nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'bid_pdx_submit_lead')
    ) {
        wp_send_json_error(['message' => 'Security check failed. Please refresh and try again.']);
    }

    $name          = isset($_POST['pro_name']) ? sanitize_text_field(wp_unslash($_POST['pro_name'])) : '';
    $business_name = isset($_POST['pro_business_name']) ? sanitize_text_field(wp_unslash($_POST['pro_business_name'])) : '';
    $email         = isset($_POST['pro_email']) ? sanitize_email(wp_unslash($_POST['pro_email'])) : '';
    $phone         = isset($_POST['pro_phone']) ? sanitize_text_field(wp_unslash($_POST['pro_phone'])) : '';
    $license       = isset($_POST['pro_license']) ? sanitize_text_field(wp_unslash($_POST['pro_license'])) : '';
    $service_areas = isset($_POST['pro_service_areas']) ? sanitize_text_field(wp_unslash($_POST['pro_service_areas'])) : '';
    $services      = isset($_POST['pro_services']) ? sanitize_textarea_field(wp_unslash($_POST['pro_services'])) : '';

    if (empty($name) || empty($business_name) || empty($email) || empty($phone) || empty($services)) {
        wp_send_json_error(['message' => 'Please complete all required fields.']);
    }

    if (!is_email($email)) {
        wp_send_json_error(['message' => 'Please enter a valid email address.']);
    }

    $to = bid_pdx_get_lead_notification_email();

    $subject = 'New Contractor Application - ' . $business_name;

    $message = "New contractor application:\n\n";
    $message .= "Name: {$name}\n";
    $message .= "Business Name: {$business_name}\n";
    $message .= "Email: {$email}\n";
    $message .= "Phone: {$phone}\n";
    $message .= "License Number: {$license}\n";
    $message .= "Service Areas: {$service_areas}\n\n";
    $message .= "Services Provided:\n{$services}\n";

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $name . ' <' . $email . '>',
    ];

    $mail_from_name = bid_pdx_get_site_profile_value(
        'mail_from_name',
        'Estimator'
    );

    $mail_from_name_filter = static function ($from_name) use ($mail_from_name) {
        return $mail_from_name;
    };

    add_filter('wp_mail_from_name', $mail_from_name_filter, 999);

    wp_mail($to, $subject, $message, $headers);

    remove_filter('wp_mail_from_name', $mail_from_name_filter, 999);

    wp_send_json_success(['message' => 'Thank you. Your contractor request has been received.']);
}

add_action('wp_ajax_estimator_framework_submit_contractor', 'estimator_framework_submit_contractor');
add_action('wp_ajax_nopriv_estimator_framework_submit_contractor', 'estimator_framework_submit_contractor');