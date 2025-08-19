<?php

class WD_Aero_Email_Handler {

    public static function handle_completed_order($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    // Skip if already sent
    if (get_post_meta($order_id, '_wd_aero_dropship_sent_at', true)) {
        return;
    }

    $categories    = (array) get_option('wd_aero_categories', []);
    $partner_email = trim((string) get_option('wd_aero_partner_email'));
    if (!$partner_email || empty($categories)) {
        return;
    }

    // Collect items in the configured categories
    $dropship_items = [];
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if (!$product) continue;
        if (has_term($categories, 'product_cat', $product->get_id())) {
            $dropship_items[] = $item;
        }
    }
    if (!$dropship_items) return;

    // Editable template (keeps your option, falls back to a sensible default)
    $template = get_option('wd_aero_email_template', '');
    if (!$template) {
        $template = '
            <h2>Purchase Order #{order_id}</h2>
            <p><strong>Customer:</strong> {customer_name}</p>
            <p><strong>Delivery Address:</strong><br>{delivery_address}</p>
            <h3>Items to Fulfil</h3>
            {product_table}
            <p><strong>Distributor Number:</strong> {distributor_number}</p>
            <p><strong>Distributor Address:</strong><br>{distributor_address}</p>
            <hr>
            <p>
            DAC Education LTD, Company number 12639015<br>
            Unit One, Main Road, Stratford St Andrew, Suffolk, IP17 1LF<br>
            Telephone: 01728 554225 | Email: info@daceducation.co.uk
            </p>
            <p style="font-size: 0.9em; color: #777;">
            Registered Company No. 00504340 | VAT number: 145077275<br>
            © Steroplast Healthcare Limited 2025
            </p>
        ';
    }

    // Prefer shipping address; fallback to billing; format nicely
    $addr = $order->get_address('shipping');
    if (empty(array_filter((array)$addr))) {
        $addr = $order->get_address('billing');
    }
    $delivery_address_html = WC()->countries->get_formatted_address($addr);

    // Build items table (Product | Product Code | Qty | Dist. Price)
    $rows = '';
    foreach ($dropship_items as $item) {
        $product = $item->get_product();
        if (!$product) continue;
        $sku = $product->get_sku();
        $qty = (int) $item->get_quantity();
        $dist_price = get_post_meta($product->get_id(), '_distributor_price', true);
        $rows .= '<tr>'
            . '<td>' . esc_html($item->get_name()) . '</td>'
            . '<td>' . esc_html($sku) . '</td>'
            . '<td>' . esc_html($qty) . '</td>'
            . '<td>' . esc_html(wp_strip_all_tags(wc_price($dist_price))) . '</td>'
            . '</tr>';
    }
    $product_table = '
        <table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse; width:100%; max-width:794px;">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Product Code</th>
                    <th>Qty</th>
                    <th>Dist. Price</th>
                </tr>
            </thead>
            <tbody>' . $rows . '</tbody>
        </table>';

    // Replace placeholders
    $replacements = [
        '{order_id}'            => (string) $order->get_order_number(),
        '{customer_name}'       => esc_html($order->get_formatted_billing_full_name()),
        '{delivery_address}'    => $delivery_address_html,
        '{product_table}'       => $product_table,
        '{distributor_number}'  => esc_html((string) get_option('wd_aero_distributor_number')),
        '{distributor_address}' => nl2br(esc_html((string) get_option('wd_aero_distributor_address'))),
    ];
    $body_html = strtr($template, $replacements);

    // Compose and send
    $subject = 'New Dropship Order #' . $order->get_order_number();
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    // Optional guard (useful on staging): add_filter('wd_aero_allow_send', '__return_false');
    $status = 'sent';
    $error  = '';

    if (!apply_filters('wd_aero_allow_send', true, $order)) {
        $status = 'failed';
        $error  = 'Sending blocked by wd_aero_allow_send filter.';
        $sent   = false;
    } else {
        $sent = wp_mail($partner_email, $subject, $body_html, $headers);
        if (!$sent) {
            $status = 'failed';
            $error  = 'wp_mail returned false';
        }
    }

    // Always store last email + timestamp (if sent)
    update_post_meta($order_id, '_wd_aero_dropship_email', $body_html);
    if ($status === 'sent') {
        update_post_meta($order_id, '_wd_aero_dropship_sent_at', current_time('mysql'));
    }

    // Persist detailed log row
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'wd_aero_dropship_log',
        [
            'order_id'      => $order->get_id(),
            'to_email'      => $partner_email,
            'subject'       => $subject,
            'status'        => $status,
            'email_content' => $body_html,
            'error'         => $error,
        ]
    );
}

}
