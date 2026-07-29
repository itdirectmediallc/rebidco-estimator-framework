<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Return the normalized hostname for the current WordPress site.
 */
function bid_pdx_get_site_hostname() {

    $host = wp_parse_url(home_url('/'), PHP_URL_HOST);

    if (!is_string($host)) {
        return '';
    }

    $host = strtolower(trim($host));

    if (strpos($host, 'www.') === 0) {
        $host = substr($host, 4);
    }

    return $host;
}

/**
 * Return the bundled intro background for the current website.
 */
function bid_pdx_get_default_intro_background_url() {

    $backgrounds = [
        'rebidco.com' => 'rebidco-intro-bg.webp',
    ];

    $host     = bid_pdx_get_site_hostname();
    $filename = isset($backgrounds[$host])
        ? $backgrounds[$host]
        : 'rebidco-intro-bg.webp';

    return ESTIMATOR_FRAMEWORK_URL . 'assets/images/' . $filename;
}

/**
 * Default site-specific estimator branding.
 */
function bid_pdx_get_site_profile_defaults() {

    return [
        'brand_name'           => 'Rebidco',
        'intro_line_1'         => 'Instant',
        'intro_line_2'         => 'quotes nearby',
        'intro_line_3'         => 'for free',
        'service_area'         => '',
        'about_text'           => 'Rebidco helps you compare project costs and connect with trusted professionals who fit your project and budget.',
        'intro_background_url' => bid_pdx_get_default_intro_background_url(),
        'mail_from_name'       => 'Rebidco',
    ];
}

/**
 * Return the saved site profile merged with safe defaults.
 */
function bid_pdx_get_site_profile() {

    $defaults = bid_pdx_get_site_profile_defaults();
    $saved    = get_option('bid_pdx_site_profile', []);

    if (!is_array($saved)) {
        $saved = [];
    }

    $profile = wp_parse_args($saved, $defaults);

    $saved_background_url = isset($saved['intro_background_url'])
        && is_string($saved['intro_background_url'])
        ? esc_url_raw($saved['intro_background_url'])
        : '';

    $saved_background_path = wp_parse_url(
        $saved_background_url,
        PHP_URL_PATH
    );

    $uses_legacy_background = is_string($saved_background_path)
        && basename($saved_background_path) === 'estimator-intro-bg.webp';

    if ($saved_background_url === '' || $uses_legacy_background) {
        $profile['intro_background_url'] = $defaults['intro_background_url'];
    }

    $profile['brand_name']   = sanitize_text_field($profile['brand_name']);
    $profile['intro_line_1'] = sanitize_text_field($profile['intro_line_1']);
    $profile['intro_line_2'] = sanitize_text_field($profile['intro_line_2']);
    $profile['intro_line_3'] = sanitize_text_field($profile['intro_line_3']);
    $profile['service_area'] = sanitize_text_field($profile['service_area']);
    $profile['about_text']   = sanitize_textarea_field($profile['about_text']);

    $profile['intro_background_url'] = esc_url_raw(
        $profile['intro_background_url']
    );

    $profile['mail_from_name'] = sanitize_text_field(
        $profile['mail_from_name']
    );

    if ($profile['brand_name'] === '') {
        $profile['brand_name'] = $defaults['brand_name'];
    }

    if ($profile['intro_background_url'] === '') {
        $profile['intro_background_url'] = $defaults['intro_background_url'];
    }

    if ($profile['mail_from_name'] === '') {
        $profile['mail_from_name'] = $profile['brand_name'];
    }

    return $profile;
}

/**
 * Return one site-profile value.
 */
function bid_pdx_get_site_profile_value($key, $fallback = '') {

    $profile = bid_pdx_get_site_profile();

    return array_key_exists($key, $profile)
        ? $profile[$key]
        : $fallback;
}

/**
 * Return the configured lead notification email.
 */
function bid_pdx_get_lead_notification_email() {

    $email = sanitize_email(
        get_option(
            'bid_pdx_lead_notification_email',
            get_option('admin_email')
        )
    );

    if (!is_email($email)) {
        $email = sanitize_email(get_option('admin_email'));
    }

    return $email;
}