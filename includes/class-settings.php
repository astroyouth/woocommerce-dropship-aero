<?php

class WD_Aero_Settings {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function add_menu() {
        add_options_page(
            'Dropship Aero Settings',
            'Dropship Aero',
            'manage_options',
            'wd_aero_settings',
            [__CLASS__, 'settings_page']
        );
    }

    public static function register_settings() {
        register_setting('wd_aero_settings_group', 'wd_aero_categories');
        register_setting('wd_aero_settings_group', 'wd_aero_distributor_number');
        register_setting('wd_aero_settings_group', 'wd_aero_distributor_address');
        register_setting('wd_aero_settings_group', 'wd_aero_partner_email');

        register_setting('wd_aero_template_group', 'wd_aero_email_template');

    }

public static function settings_page() {
    $active_tab = $_GET['tab'] ?? 'settings';

    // Special view for individual email
    if (isset($_GET['view_email'])) {
        global $wpdb;
        $log_id = intval($_GET['view_email']);
        $log = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}wd_aero_dropship_log WHERE id = $log_id");

        echo '<div class="wrap">';
        echo '<h1>Email Sent for Order #' . esc_html($log->order_id) . '</h1>';

        if ($log) {
            echo '<pre style="background: #fff; padding: 20px; border: 1px solid #ccc; white-space: pre-wrap;">' . esc_html($log->email_content) . '</pre>';
        } else {
            echo '<p>Email not found.</p>';
        }

        echo '<p><a href="' . esc_url(admin_url('options-general.php?page=wd_aero_settings&tab=dropship_orders')) . '">&larr; Back to Dropship Orders</a></p>';
        echo '</div>';
        return;
    }

    // Normal settings tabs UI
    ?>
    <div class="wrap">
        <h1>Dropship Aero</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=wd_aero_settings&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
            <a href="?page=wd_aero_settings&tab=email_template" class="nav-tab <?php echo $active_tab == 'email_template' ? 'nav-tab-active' : ''; ?>">Email Template</a>
            <a href="?page=wd_aero_settings&tab=dropship_orders" class="nav-tab <?php echo $active_tab == 'dropship_orders' ? 'nav-tab-active' : ''; ?>">Dropship Orders</a>
        </h2>

        <?php
        if ($active_tab === 'settings') {
            self::render_settings_tab();
        } elseif ($active_tab === 'email_template') {
            self::render_email_template_tab();
        } elseif ($active_tab === 'dropship_orders') {
            self::render_dropship_orders_tab();
        }
        ?>
    </div>
    <?php
}


private static function render_settings_tab() {
    ?>
    <form method="post" action="options.php">
        <?php settings_fields('wd_aero_settings_group'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Dropship Categories</th>
                <td>
                    <?php
                    $selected = get_option('wd_aero_categories', []);
                    $categories = get_terms([
                        'taxonomy' => 'product_cat',
                        'hide_empty' => false
                    ]);
                    ?>
                    <select name="wd_aero_categories[]" multiple style="height: 120px;">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected(in_array($cat->term_id, (array)$selected)); ?>>
                                <?php echo esc_html($cat->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">Select one or more categories to trigger dropshipping.</p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Partner Email</th>
                <td>
                    <input type="email" name="wd_aero_partner_email" value="<?php echo esc_attr(get_option('wd_aero_partner_email')); ?>" class="regular-text" />
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Distributor Number</th>
                <td>
                    <input type="text" name="wd_aero_distributor_number" value="<?php echo esc_attr(get_option('wd_aero_distributor_number')); ?>" class="regular-text" />
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Distributor Address</th>
                <td>
                    <textarea name="wd_aero_distributor_address" rows="4" class="large-text"><?php echo esc_textarea(get_option('wd_aero_distributor_address')); ?></textarea>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
    <?php
}

private static function render_email_template_tab() {
    // Load saved template with placeholders
    $raw_template = get_option('wd_aero_email_template', '');

    // Form to edit the template
    echo '<form method="post" action="options.php">';
    settings_fields('wd_aero_template_group');

    echo '<h3>Editable Template (use placeholders):</h3>';

    wp_editor(
        $raw_template,
        'wd_aero_email_template_editor',
        [
            'textarea_name' => 'wd_aero_email_template',
            'textarea_rows' => 20,
            'media_buttons' => false,
            'teeny' => false,
            'quicktags' => true,
        ]
    );

    echo '<p class="description">';
    echo 'Use placeholders like: <code>{order_id}</code>, <code>{customer_name}</code>, <code>{delivery_address}</code>, <code>{product_table}</code>, <code>{distributor_number}</code>, <code>{distributor_address}</code>';
    echo '</p>';

    submit_button('Save Email Template');
    echo '</form>';

    // Dummy substitution for preview
    $preview_template = strtr($raw_template, [
        '{order_id}' => '2449',
        '{customer_name}' => 'John Doe',
        '{delivery_address}' => "John Doe<br>15 Some Road<br>Somewhere<br>Town<br>SUFFOLK<br>IP44 3TH<br>United Kingdom (UK)",
        '{product_table}' => '
            <table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse; width: 794px;">
                <thead>
                    <tr>
                        <th>Product</th><th>Product Code</th><th>Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Premier Children First Aid Kit</td><td>8170</td><td>1</td></tr>
                    <tr><td>SAM Splint 36 Standard Roll</td><td>1381</td><td>1</td></tr>
                </tbody>
            </table>
        ',
        '{distributor_number}' => '12345678',
        '{distributor_address}' => 'Unit One, Main Road, Stratford St Andrew, Suffolk, IP17 1LF'
    ]);

    // Output preview below the editor
    echo '<h3>Preview Mode (live values):</h3>';
    echo '<div style="border:1px solid #ccc; padding: 10px; background: #fff; margin-top:20px;">';
    echo wp_kses_post($preview_template);
    echo '</div>';
}




    private static function render_dropship_orders_tab() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wd_aero_dropship_log';
        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY sent_at DESC LIMIT 50");

        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Customer Name</th>
                    <th>Customer Email</th>
                    <th>Postcode</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)) : ?>
                    <?php foreach ($logs as $log): 
                        $order = wc_get_order($log->order_id);
                        $customer_name = $order ? $order->get_formatted_billing_full_name() : '—';
                        $customer_email = $order ? $order->get_billing_email() : '—';
                        $postcode = $order ? $order->get_shipping_postcode() : '—';
                    ?>
                        <tr>
                            <td><?php echo esc_html($log->order_id); ?></td>
                            <td><?php echo esc_html(date('Y-m-d H:i', strtotime($log->sent_at))); ?></td>
                            <td><?php echo esc_html($customer_name); ?></td>
                            <td><?php echo esc_html($customer_email); ?></td>
                            <td><?php echo esc_html($postcode); ?></td>
                            <td>
                                <select onchange="if (this.value) window.open(this.value, '_blank');">
                                    <option value="">Select Action</option>
                                    <option value="<?php echo admin_url('post.php?post=' . intval($log->order_id) . '&action=edit'); ?>">View Order</option>
                                    <option value="<?php echo esc_url(admin_url('admin.php?page=wd_aero_settings&view_email=' . intval($log->id))); ?>">View Email</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No dropship orders found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }



    private static function render_email_viewer_tab($order_id) {
        $email_body = get_post_meta($order_id, '_wd_aero_dropship_email', true);
        echo '<h2>Email Sent for Order #' . $order_id . '</h2>';
        echo '<pre style="background:#fff; padding:1em; border:1px solid #ccc; max-width:900px;">' . esc_html($email_body) . '</pre>';
        echo '<p><a href="?page=wd_aero_settings&tab=dropship_orders" class="button">Back to Dropship Orders</a></p>';
    }
}

WD_Aero_Settings::init();