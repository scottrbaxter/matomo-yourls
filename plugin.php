<?php
/*
Plugin Name: MatomoTracking
Plugin URI: https://github.com/scottrbaxter/matomo-yourls
Description: Track redirections server-side with Matomo without breaking link previews.
Version: 1.0.2
Author: scottrbaxter
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

// Hook our custom function into the 'pre_redirect' event
yourls_add_action( 'pre_redirect', 'matomo_server_side_tracking' );

function matomo_server_side_tracking( $args ) {
    $url = $args[0];

    $matomo_url = ""; // Add your URL from matomo here. Example: analytics.example.de
    $matomo_id = ""; // Add your Tracking ID here

    // Build Matomo Tracking HTTP API URL
    $tracking_api = "https://" . $matomo_url . "/matomo.php?idsite=" . $matomo_id . "&rec=1&url=" . urlencode($url);

    // Forward visitor IP, User-Agent, and Referrer for accurate tracking
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $tracking_api .= "&cip=" . urlencode($_SERVER['REMOTE_ADDR']);
    }
    if (!empty($_SERVER['HTTP_USER_AGENT'])) {
        $tracking_api .= "&ua=" . urlencode($_SERVER['HTTP_USER_AGENT']);
    }
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $tracking_api .= "&urlref=" . urlencode($_SERVER['HTTP_REFERER']);
    }

    // Fire the tracking request silently in the background with a 1-second timeout
    $ctx = stream_context_create(array('http' => array('timeout' => 1)));
    @file_get_contents($tracking_api, false, $ctx);

    // Do NOT die() or output HTML; allow YOURLS to execute the native redirect header.
}
