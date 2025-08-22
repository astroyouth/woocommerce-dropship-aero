<?php
// File: includes/class-distributor-price.php

class WD_Aero_Distributor_Price {
    public static function init() {
        add_action('woocommerce_product_options_general_product_data', [__CLASS__, 'add_custom_price_field']);
        add_action('woocommerce_process_product_meta', [__CLASS__, 'save_custom_price_field']);
    }

    // Add the custom field to the Product Data > General tab
    public static function add_custom_price_field() {
        woocommerce_wp_text_input([
            'id' => '_distributor_price',
            'label' => __('Distributor Price', 'woocommerce'),
            'desc_tip' => true,
            'description' => __('Only used for dropship email. Never shown to customers.'),
            'type' => 'number',
            'custom_attributes' => [
                'step' => '0.01',
                'min' => '0',
            ],
        ]);
    }

    // Save the custom field value when the product is saved
    public static function save_custom_price_field($post_id) {
        if (isset($_POST['_distributor_price'])) {
            update_post_meta($post_id, '_distributor_price', sanitize_text_field($_POST['_distributor_price']));
        }
    }
}

WD_Aero_Distributor_Price::init();
