<?php
// Automatically mark orders as Completed if they contain dropship products

add_action('woocommerce_payment_complete', 'wd_aero_auto_complete_if_dropship');

function wd_aero_auto_complete_if_dropship($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    // Get selected category IDs from plugin settings
    $selected_categories = get_option('wd_aero_categories', []);
    if (empty($selected_categories)) return;

    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if (!$product instanceof WC_Product) continue;

        // Check if product belongs to any of the selected categories
        if (has_term($selected_categories, 'product_cat', $product->get_id())) {
            if ($order->get_status() !== 'completed') {
                $order->update_status('completed', 'Auto-completed by WD Aero plugin for dropship product.');
            }
            return; // Only need to match one item
        }
    }
}
