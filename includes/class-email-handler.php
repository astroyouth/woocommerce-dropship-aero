<?php

class WD_Aero_Email_Handler {

    public static function handle_completed_order($order_id) {
        $order = wc_get_order($order_id);
        $categories = get_option('wd_aero_categories', []);
        $partner_email = get_option('wd_aero_partner_email');

        if (!$partner_email || empty($categories)) return;

        $dropship_items = [];

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;

            if (has_term($categories, 'product_cat', $product->get_id())) {
                $dropship_items[] = $item;
            }
        }

        if (empty($dropship_items)) return;

        $email_body = "<h2>New Dropship Order</h2>";
        $email_body .= "<p><strong>Order #{$order_id}</strong></p>";
        $email_body .= "<p><strong>Customer:</strong> " . $order->get_formatted_billing_full_name() . "<br>";
        $email_body .= $order->get_billing_address_1() . ", " . $order->get_billing_city() . " " . $order->get_billing_postcode() . "</p>";
        $email_body .= "<ul>";

        foreach ($dropship_items as $item) {
            $email_body .= "<li>{$item->get_name()} x {$item->get_quantity()}</li>";
        }

        $email_body .= "</ul>";
        $email_body .= "<p><strong>Distributor Number:</strong> " . esc_html(get_option('wd_aero_distributor_number')) . "</p>";
        $email_body .= "<p><strong>Distributor Address:</strong><br>" . nl2br(esc_html(get_option('wd_aero_distributor_address'))) . "</p>";

        wp_mail($partner_email, "New Dropship Order #{$order_id}", $email_body, ['Content-Type: text/html; charset=UTF-8']);
    }
}
