<?php

class WD_Aero_Email_Handler {

    public static function handle_completed_order($order_id) {
        error_log("[WD_AERO] handle_completed_order triggered for Order #$order_id");
        error_log("[WD_AERO] Entered handle_completed_order() for Order ID: $order_id");


        $order = wc_get_order($order_id);
        if (!$order) {
            error_log("[WD_AERO] Failed to get order.");
            return;
        }
        // Check if email already sent
        if (get_post_meta($order_id, '_wd_aero_dropship_sent_at', true)) {
            error_log("[WD_AERO] Dropship email already sent for order #$order_id. Skipping.");
            return;
}

        $categories = get_option('wd_aero_categories', []);
        $partner_email = get_option('wd_aero_partner_email');

        error_log("[WD_AERO] Categories set: " . print_r($categories, true));
        error_log("[WD_AERO] Partner email set: $partner_email");

        if (!$partner_email || empty($categories)) {
            error_log("[WD_AERO] Missing partner email or categories. Aborting.");
            return;
        }

        $dropship_items = [];

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) {
                error_log("[WD_AERO] Missing product for item.");
                continue;
            }

            $product_id = $product->get_id();
            if (has_term($categories, 'product_cat', $product_id)) {
                error_log("[WD_AERO] Product ID $product_id is in a dropship category.");
                $dropship_items[] = $item;
            } else {
                error_log("[WD_AERO] Product ID $product_id is NOT in dropship categories.");
            }
        }

        if (empty($dropship_items)) {
            error_log("[WD_AERO] No dropship items found in order.");
            return;
        }

        error_log("[WD_AERO] Preparing to send dropship email...");

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
                Registered office and training centre: Unit One, Main Road, Stratford St Andrew, Suffolk, IP17 1LF<br>
                Telephone: 01728 554225 | Email: info@daceducation.co.uk
                </p>
                <p style="font-size: 0.9em; color: #777;">
                Registered Company No. 00504340 | VAT number: 145077275<br>
                © Steroplast Healthcare Limited 2025
                </p>
            ';
        }

        // Replace placeholders
        $template = str_replace('{order_id}', $order_id, $template);
        $template = str_replace('{customer_name}', $order->get_formatted_billing_full_name(), $template);
        $template = str_replace('{delivery_address}', nl2br($order->get_formatted_billing_address()), $template);
        $template = str_replace('{distributor_number}', esc_html(get_option('wd_aero_distributor_number')), $template);
        $template = str_replace('{distributor_address}', nl2br(esc_html(get_option('wd_aero_distributor_address'))), $template);

        // Build the product table
        $product_rows = '';
        foreach ($dropship_items as $item) {
            $product = $item->get_product();
            if (!$product) continue;
            $distributor_price = get_post_meta($product->get_id(), '_distributor_price', true);

            $product_rows .= '<tr>
                <td>' . esc_html($item->get_name()) . '</td>
                <td>' . esc_html($product->get_sku()) . '</td>
                <td>' . esc_html($item->get_quantity()) . '</td>
                <td>' . esc_html(wp_strip_all_tags(wc_price($distributor_price))) . '</td>
            </tr>';
        }

        $product_table = '
        <div style="max-width: 794px; ">
            <table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse; width:100%;">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Product Code</th>
                        <th>Qty</th>
                        <th>Dist. Price</th>
                    </tr>
                </thead>
                <tbody>' . $product_rows . '</tbody>
            </table>
        </div>';

        $template = str_replace('{product_table}', $product_table, $template);

        // Send the email
        error_log("[WD_AERO] Sending email to: $partner_email");
        error_log("[WD_AERO] Final email content:\n" . $template);

        $sent = wp_mail(
            $partner_email,
            "New Dropship Order #{$order_id}",
            $template,
            ['Content-Type: text/html; charset=UTF-8']
        );

        if ($sent) {
            error_log("[WD_AERO] Email sent successfully.");
        } else {
            error_log("[WD_AERO] wp_mail failed.");
        }

        error_log("[WD_AERO] Subject: New Dropship Order #{$order_id}");
        error_log("[WD_AERO] Partner Email: $partner_email");
        error_log("[WD_AERO] Retrieved Partner Email: " . get_option('wd_aero_partner_email'));



        // Store log
        update_post_meta($order_id, '_wd_aero_dropship_email', $template);
        update_post_meta($order_id, '_wd_aero_dropship_sent_at', current_time('mysql'));

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'wd_aero_dropship_log',
            [
                'order_id' => $order->get_id(),
                'email_content' => $template,
            ]
        );
    }
}
