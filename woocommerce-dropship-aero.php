<?php
/*
Plugin Name: WooCommerce Dropship Aero
Description: Forward dropshipping orders to Aero Healthcare or other partners.
Version: 1.0.0
Author: Alex Dale
*/

if (!defined('ABSPATH')) exit;

define('WD_AERO_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Load classes
require_once WD_AERO_PLUGIN_DIR . 'includes/class-settings.php';
require_once WD_AERO_PLUGIN_DIR . 'includes/class-email-handler.php';
require_once WD_AERO_PLUGIN_DIR . 'includes/class-webhook-handler.php';

// Hook into WooCommerce order completion
add_action('woocommerce_order_status_completed', ['WD_Aero_Email_Handler', 'handle_completed_order'], 10, 1);
