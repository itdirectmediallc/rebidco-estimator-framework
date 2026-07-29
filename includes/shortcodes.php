<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Estimator Framework Shortcode
 *
 * Usage:
 * [bid_pdx_estimator]
 * [bid_pdx_estimator project_id="1"]
 */
function bid_pdx_estimator_shortcode($atts = []) {

    $atts = shortcode_atts(
        [
            'project_id' => 0,
        ],
        $atts,
        'bid_pdx_estimator'
    );

    $engine = new Bid_PDX_Estimator_Engine();

    $project_cards = $engine->get_project_cards();
    $all_projects_estimator_data = $engine->get_all_projects_estimator_data();

    if (!empty($atts['project_id'])) {
        $estimator_data = $engine->get_project_estimator_data(intval($atts['project_id']));
    } else {
        $estimator_data = $engine->get_default_estimator_data();
    }

    ob_start();

    require BID_PDX_PATH . 'templates/estimator.php';

    return ob_get_clean();

}

add_shortcode('bid_pdx_estimator', 'bid_pdx_estimator_shortcode');