<?php

add_action('rest_api_init', function () {
    register_rest_route('wd-aero/v1', '/airwallex-webhook', [
        'methods' => 'POST',
        'callback' => 'wd_aero_handle_airwallex_webhook',
        'permission_callback' => '__return_true',
    ]);
});

function wd_aero_handle_airwallex_webhook(WP_REST_Request $request) {
    error_log('[WD_AERO] Webhook function hit.');

    $body = $request->get_json_params();
    error_log('[WD_AERO] Body: ' . print_r($body, true));

    // Validate event type
    if (!isset($body['name']) || $body['name'] !== 'payment_intent.succeeded') {
        error_log('[WD_AERO] Invalid event type or missing "name".');
        return new WP_REST_Response(['message' => 'Invalid event'], 400);
    }

    // Extract WooCommerce order ID
    $payment_intent = $body['data']['object'] ?? [];
    error_log('[WD_AERO] Payment intent: ' . print_r($payment_intent, true));

   $metadata = $payment_intent['metadata'] ?? [];
    error_log('[WD_AERO] Metadata: ' . print_r($metadata, true));

    $order_id = $metadata['wp_order_id'] ?? null;
    if (!$order_id) {
    error_log('[WD_AERO] No wp_order_id found in metadata.');
    return new WP_REST_Response(['message' => 'Order ID missing'], 400);
}

    if (!$order_id) {
        error_log('[WD_AERO] No wp_order_id found in metadata.');
        return new WP_REST_Response(['message' => 'Order ID missing'], 400);
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        error_log("[WD_AERO] Order #$order_id not found.");
        return new WP_REST_Response(['message' => 'Order not found'], 404);
    }

    // Check for dropship product categories
    $dropship_categories = get_option('wd_aero_categories', []);
    error_log('[WD_AERO] Drop categories: ' . print_r($dropship_categories, true));

    $has_dropship_item = false;
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if (!$product) {
            error_log('[WD_AERO] Item has no product.');
            continue;
        }
error_log('[WD_AERO] Raw option: ' . get_option('wd_aero_categories'));

        if (has_term($dropship_categories, 'product_cat', $product->get_id())) {
            $has_dropship_item = true;
            error_log('[WD_AERO] Found dropship product: ' . $product->get_name());
            break;
        }
    }

    if ($has_dropship_item && $order->get_status() !== 'completed') {
        $order->update_status('completed', 'Auto-completed via Airwallex webhook.');
        error_log("[WD_AERO] Order #$order_id marked as completed.");
    } else {
        error_log("[WD_AERO] Order #$order_id not updated — either no dropship item or already completed.");
    }

    return new WP_REST_Response(['message' => 'Webhook processed.'], 200);
}
