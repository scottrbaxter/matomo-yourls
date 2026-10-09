<?php
/*
Plugin Name: MatomoSmartTracking
Plugin URI: https://github.com/scottrbaxter/matomo-yourls
Description: Instant HTTP 301 redirects with prioritized bot bypass for Discord and social scrapers.
Version: 2.0.0
Author: scottrbaxter
*/

if( !defined( 'YOURLS_ABSPATH' ) ) die();

yourls_add_action( 'pre_redirect', 'matomo_smart_tracking' );

function matomo_smart_tracking( $args ) {
    $destination_url = $args[0];
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

    // Immediate exit for any known preview bot or crawler to guarantee instant 301 responses
    $bots = ['discordbot', 'facebookexternalhit', 'twitterbot', 'linkedinbot', 'whatsapp', 'telegrambot', 'slackbot', 'applebot', 'embedly', 'pinterestbot', 'vkshare', 'preview', 'bot', 'crawler', 'spider'];

    foreach ($bots as $bot) {
        if ($ua !== '' && stripos($ua, $bot) !== false) {
            return; // Zero latency, zero cURL overhead for bots
        }
    }

    $matomo_url = "matomo.example.com"; // Replace with your matomo hosted domain
    $matomo_id = 1; // Replace with your Matomo Website ID
    $token_auth = ""; // Replace with your Matomo Superuser Token

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $short_link_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $visitor_id = substr(md5((isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '') . $ua), 0, 16);

    $params = array(
        'idsite'      => $matomo_id,
        'rec'         => 1,
        'apiv'        => 1,
        'send_image'  => 0,
        'url'         => $short_link_url,
        'token_auth'  => $token_auth,
        'action_name' => "Redirect: " . $destination_url,
        '_id'         => $visitor_id,
        'cdt'         => gmdate('Y-m-d H:i:s')
    );

    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $params['cip'] = $_SERVER['REMOTE_ADDR'];
    }
    if (!empty($ua)) {
        $params['ua'] = $ua;
    }

    $tracking_endpoint = "https://" . $matomo_url . "/matomo.php";

    $ch = curl_init($tracking_endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 100);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_exec($ch);
    curl_close($ch);
}
