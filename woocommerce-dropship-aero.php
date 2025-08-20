<?php

/*
Plugin Name: WooCommerce Dropship Steroplast
Description: Forward dropshipping orders to Aero Healthcare or other partners.
Version: 1.1.1
Author: Alex Dale
*/
if (!isset($GLOBALS['wd_aero_raw_body'])) {
    $GLOBALS['wd_aero_raw_body'] = file_get_contents('php://input');
}

if (!defined('ABSPATH')) exit;

define('WD_AERO_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Load classes
require_once WD_AERO_PLUGIN_DIR . 'includes/class-settings.php';
require_once WD_AERO_PLUGIN_DIR . 'includes/class-email-handler.php';
require_once WD_AERO_PLUGIN_DIR . 'includes/class-auto-complete.php';
require_once WD_AERO_PLUGIN_DIR . 'includes/class-distributor-price.php';

// adding plugin updater
// functions at plugin load
require_once __DIR__ . '/includes/puc/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;


add_action('plugins_loaded', function () {
    // 2) Point to the EXACT GitHub repo URL (no .git at the end)
    //    Replace OWNER and REPO with your actual names.
    $repoUrl = 'https://github.com/astroyouth/woocommerce-dropship-aero.git';

    // 3) Tell PUC where your main plugin file is AND give it a unique slug.
    //    If this code is in the main plugin file itself, you can use __FILE__.
    //    Otherwise, use an absolute path to that file.
    $main_file = __FILE__; // <-- use this ONLY if this code sits in the main plugin file.
    // If this code is in another file, do this instead:
    // $main_file = WP_PLUGIN_DIR . '/woocommerce-dropship-aero/woocommerce-dropship-aero.php';

    $updateChecker = PucFactory::buildUpdateChecker(
        $repoUrl,
        $main_file,
        'woocommerce-dropship-aero' // your plugin slug (any unique string)
    );

    // 4) Track the branch you actually use (most repos use "main" now)
    $updateChecker->setBranch('main');

    // 5) If the GitHub repo is PRIVATE, add a read-only token:
    // $updateChecker->setAuthentication('ghp_your_readonly_token');

    // 6) (Testing) Force a check when you open wp-admin (remove later).
    add_action('admin_init', function() use ($updateChecker) {
        $updateChecker->checkForUpdates();
    });
});


error_log("[WD_AERO] Plugin loaded and main file executed.");

// Initialize settings UI
WD_Aero_Settings::init();


// Hook into WooCommerce order completion
add_action('woocommerce_order_status_changed', function($order_id, $from_status, $to_status) {
    if ($to_status === 'completed') {
        error_log("[WD_AERO] order_status_changed detected for Order #$order_id from $from_status to $to_status");
        WD_Aero_Email_Handler::handle_completed_order($order_id);
    }
}, 10, 3);

register_activation_hook(__FILE__, 'wd_aero_create_dropship_log_table');

function wd_aero_create_dropship_log_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wd_aero_dropship_log';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id BIGINT(20) NOT NULL,
        to_email VARCHAR(255) NULL,
        subject VARCHAR(255) NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'sent', /* sent|failed */
        email_content LONGTEXT NOT NULL,
        error TEXT NULL,
        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY order_id (order_id),
        KEY status (status),
        KEY sent_at (sent_at)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

add_filter('allowed_options', function($allowed) {
    if (!isset($allowed['wd_aero_options'])) {
        $allowed['wd_aero_options'] = [
            'wd_aero_categories',
            'wd_aero_distributor_number',
            'wd_aero_distributor_address',
            'wd_aero_partner_email',
            'wd_aero_email_template'
        ];
    }
    return $allowed;
});
