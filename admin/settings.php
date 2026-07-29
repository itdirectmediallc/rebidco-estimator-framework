<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Page
 */
function bid_pdx_settings_page() {

    if (!current_user_can('manage_options')) {
        return;
    }

    $message = '';
    $error   = '';

    $profile            = bid_pdx_get_site_profile();
    $notification_email = bid_pdx_get_lead_notification_email();

    if (
        isset($_SERVER['REQUEST_METHOD']) &&
        $_SERVER['REQUEST_METHOD'] === 'POST'
    ) {
        check_admin_referer(
            'bid_pdx_save_settings',
            'bid_pdx_settings_nonce'
        );

        $new_email = isset($_POST['lead_notification_email'])
            ? sanitize_email(wp_unslash($_POST['lead_notification_email']))
            : '';

        $new_profile = [
            'brand_name' => isset($_POST['brand_name'])
                ? sanitize_text_field(wp_unslash($_POST['brand_name']))
                : '',

            'intro_line_1' => isset($_POST['intro_line_1'])
                ? sanitize_text_field(wp_unslash($_POST['intro_line_1']))
                : '',

            'intro_line_2' => isset($_POST['intro_line_2'])
                ? sanitize_text_field(wp_unslash($_POST['intro_line_2']))
                : '',

            'intro_line_3' => isset($_POST['intro_line_3'])
                ? sanitize_text_field(wp_unslash($_POST['intro_line_3']))
                : '',

            'service_area' => isset($_POST['service_area'])
                ? sanitize_text_field(wp_unslash($_POST['service_area']))
                : '',

            'about_text' => isset($_POST['about_text'])
                ? sanitize_textarea_field(wp_unslash($_POST['about_text']))
                : '',

            'intro_background_url' => isset($_POST['intro_background_url'])
                ? esc_url_raw(wp_unslash($_POST['intro_background_url']))
                : '',

            'mail_from_name' => isset($_POST['mail_from_name'])
                ? sanitize_text_field(wp_unslash($_POST['mail_from_name']))
                : '',
        ];

        if ($new_email === '' || !is_email($new_email)) {
            $error = 'Please enter a valid lead notification email address.';
        } elseif ($new_profile['brand_name'] === '') {
            $error = 'Please enter a brand name.';
        } else {
            if ($new_profile['mail_from_name'] === '') {
                $new_profile['mail_from_name'] = $new_profile['brand_name'];
            }

            update_option(
                'bid_pdx_lead_notification_email',
                $new_email
            );

            update_option(
                'bid_pdx_site_profile',
                $new_profile
            );

            $profile            = bid_pdx_get_site_profile();
            $notification_email = bid_pdx_get_lead_notification_email();
            $message            = 'Settings saved successfully.';
        }
    }

    ?>

    <div class="wrap">

        <h1>Estimator Site Profile</h1>

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

            <?php
            wp_nonce_field(
                'bid_pdx_save_settings',
                'bid_pdx_settings_nonce'
            );
            ?>

            <h2>Branding</h2>

            <table class="form-table" role="presentation">

                <tr>
                    <th scope="row">
                        <label for="brand_name">Brand Name</label>
                    </th>
                    <td>
                        <input
                            type="text"
                            name="brand_name"
                            id="brand_name"
                            class="regular-text"
                            value="<?php echo esc_attr($profile['brand_name']); ?>"
                            required
                        >
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="intro_line_1">Intro Line 1</label>
                    </th>
                    <td>
                        <input
                            type="text"
                            name="intro_line_1"
                            id="intro_line_1"
                            class="regular-text"
                            value="<?php echo esc_attr($profile['intro_line_1']); ?>"
                        >
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="intro_line_2">Intro Line 2</label>
                    </th>
                    <td>
                        <input
                            type="text"
                            name="intro_line_2"
                            id="intro_line_2"
                            class="regular-text"
                            value="<?php echo esc_attr($profile['intro_line_2']); ?>"
                        >
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="intro_line_3">Intro Line 3</label>
                    </th>
                    <td>
                        <input
                            type="text"
                            name="intro_line_3"
                            id="intro_line_3"
                            class="regular-text"
                            value="<?php echo esc_attr($profile['intro_line_3']); ?>"
                        >
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="service_area">Service Area Text</label>
                    </th>
                    <td>
                        <input
                            type="text"
                            name="service_area"
                            id="service_area"
                            class="regular-text"
                            value="<?php echo esc_attr($profile['service_area']); ?>"
                        >

                        <p class="description">
                            Leave blank when no service-area line should appear.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="about_text">About Text</label>
                    </th>
                    <td>
                        <textarea
                            name="about_text"
                            id="about_text"
                            class="large-text"
                            rows="4"
                        ><?php echo esc_textarea($profile['about_text']); ?></textarea>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="intro_background_url">Intro Background Image URL</label>
                    </th>
                    <td>
                        <input
                            type="url"
                            name="intro_background_url"
                            id="intro_background_url"
                            class="large-text"
                            value="<?php echo esc_attr($profile['intro_background_url']); ?>"
                        >

                        <p class="description">
                            Use a WordPress Media Library image URL or the bundled default image.
                        </p>
                    </td>
                </tr>

            </table>

            <h2>Email</h2>

            <table class="form-table" role="presentation">

                <tr>
                    <th scope="row">
                        <label for="lead_notification_email">Lead Notification Email</label>
                    </th>
                    <td>
                        <input
                            type="email"
                            name="lead_notification_email"
                            id="lead_notification_email"
                            class="regular-text"
                            value="<?php echo esc_attr($notification_email); ?>"
                            required
                        >

                        <p class="description">
                            New estimator leads and contractor applications are sent here.
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="mail_from_name">Email Sender Name</label>
                    </th>
                    <td>
                        <input
                            type="text"
                            name="mail_from_name"
                            id="mail_from_name"
                            class="regular-text"
                            value="<?php echo esc_attr($profile['mail_from_name']); ?>"
                        >

                        <p class="description">
                            Example: Rebidco.
                        </p>
                    </td>
                </tr>

            </table>

            <?php submit_button('Save Site Profile'); ?>

        </form>

    </div>

    <?php
}