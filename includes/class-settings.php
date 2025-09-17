<?php

class WD_Aero_Settings {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function add_menu() {
        add_options_page(
            'Dropship Steroplast Settings',
            'Dropship Steroplast',
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
            echo '<pre style="background: #fff; padding: 20px; border: 1px solid #ccc; white-space: pre-wrap;">' . $log->email_content . '</pre>';
        } else {
            echo '<p>Email not found.</p>';
        }

        echo '<p><a href="' . esc_url(admin_url('options-general.php?page=wd_aero_settings&tab=dropship_orders')) . '">&larr; Back to Dropship Orders</a></p>';
        echo '</div>';
        return;
    }
    if (isset($_GET['view_email'], $_GET['_wpnonce'])) {
        $log_id = absint($_GET['view_email']);
        if (!wp_verify_nonce($_GET['_wpnonce'], 'wd_aero_view_email_' . $log_id)) {
            wp_die(esc_html__('Nonce verification failed.', 'wd-aero'));
        }
        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wd_aero_dropship_log WHERE id = %d", $log_id));

        echo '<div class="wrap">';
        echo '<h1>Email Sent</h1>';
        if ($log) {
            echo '<p><strong>Order:</strong> #' . esc_html($log->order_id) . '</p>';
            echo '<p><strong>Status:</strong> ' . esc_html($log->status ?: 'sent') . '</p>';
            echo '<p><strong>To:</strong> ' . esc_html($log->to_email) . '</p>';
            echo '<p><strong>Subject:</strong> ' . esc_html($log->subject) . '</p>';
            echo '<div style="background:#fff; padding: 16px; border:1px solid #ccd0d4; max-width: 900px; overflow:auto;">' . wp_kses_post($log->email_content) . '</div>';
            if (!empty($log->error)) {
                echo '<p><strong>Error:</strong> ' . esc_html($log->error) . '</p>';
            }
        } else {
            echo '<p>' . esc_html__('Email not found.', 'wd-aero') . '</p>';
        }
        $back = esc_url(admin_url('options-general.php?page=wd_aero_settings&tab=dropship_orders'));
        echo '<p><a class="button" href="' . $back . '">&larr; ' . esc_html__('Back to Dropship Orders', 'wd-aero') . '</a></p>';
        echo '</div>';
        return;
    }
    // Normal settings tabs UI
    ?>
    <div class="wrap">
        <h1>Dropship Steroplast</h1>
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
   echo 'Use placeholders like: <code>{order_id}</code>, <code>{customer_name}</code>, <code>{customer_email}</code>, <code>{delivery_address}</code>, <code>{product_table}</code>, <code>{distributor_number}</code>, <code>{distributor_address}</code>';
    echo '</p>';

    submit_button('Save Email Template');
    echo '</form>';

    // Dummy substitution for preview
    $preview_template = strtr($raw_template, [
        '{order_id}' => '2449',
        '{customer_name}' => 'John Doe',
        '{customer_email}'     => 'john@example.com',
        '{delivery_address}' => "John Doe<br>15 Some Road<br>Somewhere<br>Town<br>SUFFOLK<br>IP44 3TH<br>United Kingdom (UK)",
        '{product_table}' => '
            <table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse; width: 794px;">
                <thead>
                    <tr>
                        <th>Product</th><th>Product Code</th><th>Qty</th><th>Distributor Unit Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Premier Children First Aid Kit</td><td>8170</td><td>1</td><td>£23.30</td></tr>
                    <tr><td>SAM Splint 36 Standard Roll</td><td>1381</td><td>1</td><td>£10.50</td></tr>
                </tbody>
            </table>
        ',
        '{distributor_number}' => '12345678',
        '{distributor_address}' => 'Unit One, Main Road, Stratford St Andrew, Suffolk, IP17 1LF'
    ]);

    // Output preview below the editor
    echo '<h3>Preview Mode (With some pretend data):</h3>';
    echo '<div style="border:1px solid #ccc; padding: 10px; background: #fff; margin-top:20px;">';
    echo wp_kses_post($preview_template);
    echo '</div>';
}




   private static function render_dropship_orders_tab() {
    if ( ! current_user_can('manage_options') ) {
        wp_die( esc_html__('You do not have permission to view this page.', 'wd-aero') );
    }

    global $wpdb;

    // --- Restore (Untrash) handler ---
    if ( isset($_GET['wd_aero_untrash'], $_GET['_wpnonce']) ) {
        $oid = absint($_GET['wd_aero_untrash']);
        if ( wp_verify_nonce($_GET['_wpnonce'], 'wd_aero_untrash_' . $oid) && current_user_can('edit_shop_orders') ) {
            $restored = false;

            if ( function_exists('wp_untrash_post') && 'trash' === get_post_status($oid) ) {
                $res = wp_untrash_post($oid);
                $restored = (false !== $res);
            }

            if ( ! $restored && function_exists('wc_get_order') ) {
                $order = wc_get_order($oid);
                if ( $order ) {
                    $prev = get_post_meta($oid, '_wp_trash_meta_status', true);
                    $new  = $prev ? $prev : 'pending';
                    if ( method_exists($order, 'set_status') ) {
                        $order->set_status($new);
                        $order->save();
                        $restored = true;
                    }
                }
            }

            if ( $restored ) {
                echo '<div class="notice notice-success"><p>' . sprintf( esc_html__( 'Order #%d restored.', 'wd-aero' ), $oid ) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . sprintf( esc_html__( 'Could not restore order #%d. You can also restore it from WooCommerce → Orders → Trash.', 'wd-aero' ), $oid ) . '</p></div>';
            }
        }
    }

    // --- Fetch logs + post_status ---
    $table = $wpdb->prefix . 'wd_aero_dropship_log';
    $sql   = "
        SELECT l.*, p.post_status
        FROM {$table} l
        LEFT JOIN {$wpdb->posts} p ON p.ID = l.order_id
        ORDER BY l.sent_at DESC, l.id DESC
        LIMIT 100
    ";
    $logs = $wpdb->get_results( $sql );

    // --- Render table ---
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__( 'Order', 'wd-aero' )   . '</th>';
    echo '<th>' . esc_html__( 'Customer', 'wd-aero' )   . '</th>';
    echo '<th>' . esc_html__( 'Status', 'wd-aero' )  . '</th>';
    echo '<th>' . esc_html__( 'To', 'wd-aero' )      . '</th>';
    echo '<th>' . esc_html__( 'Subject', 'wd-aero' ) . '</th>';
    echo '<th>' . esc_html__( 'Date', 'wd-aero' )    . '</th>';
    echo '<th>' . esc_html__( 'Actions', 'wd-aero' ) . '</th>';
    echo '</tr></thead><tbody>';

    if ( $logs ) {
        foreach ( $logs as $log ) {
            $wp_status   = $log->post_status;
            $exists      = ( null !== $wp_status );
            $is_trashed  = ( 'trash' === $wp_status );

            if ( ! $is_trashed && function_exists('wc_get_order') ) {
                $o = wc_get_order( $log->order_id );
                if ( $o && method_exists($o, 'get_status') && 'trash' === $o->get_status() ) {
                    $is_trashed = true;
                }
            }

            // Order label
            $order_cell = '#' . intval( $log->order_id );
            if ( $is_trashed ) {
                $order_cell .= ' <span class="dashicons dashicons-trash" title="Trashed" style="color:#a00"></span>';
            } elseif ( ! $exists ) {
                $order_cell .= ' <span class="dashicons dashicons-dismiss" title="Deleted" style="color:#777"></span>';
            }

            // Customer name
            $customer_name = '—';
            if ( $exists && function_exists('wc_get_order') ) {
                $order = wc_get_order( $log->order_id );
                if ( $order ) {
                    $customer_name = $order->get_formatted_billing_full_name();
                    if ( ! $customer_name ) {
                        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                    }
                    $customer_name = trim($customer_name) ?: '—';
                }
            }

            // Log status
            $log_status = ( isset($log->status) && $log->status !== '' )
                ? $log->status
                : ( empty($log->error) ? 'sent' : 'failed' );

            // Actions
            $view_order_url = ( $exists && ! $is_trashed )
                ? admin_url( 'post.php?post=' . absint( $log->order_id ) . '&action=edit' )
                : '';

            $view_email_url = wp_nonce_url(
                admin_url( 'options-general.php?page=wd_aero_settings&tab=dropship_orders&view_email=' . absint( $log->id ) ),
                'wd_aero_view_email_' . absint( $log->id )
            );

            $restore_url = $is_trashed
                ? wp_nonce_url(
                    admin_url( 'options-general.php?page=wd_aero_settings&tab=dropship_orders&wd_aero_untrash=' . absint( $log->order_id ) ),
                    'wd_aero_untrash_' . absint( $log->order_id )
                  )
                : '';

            echo '<tr>';
            echo '<td>' . wp_kses_post( $order_cell ) . '</td>';
            echo '<td>' . esc_html( $customer_name ) . '</td>';
            echo '<td>' . esc_html( $log_status ) . '</td>';
            echo '<td>' . esc_html( $log->to_email ?? '' ) . '</td>';
            echo '<td>' . esc_html( $log->subject  ?? '' ) . '</td>';
            echo '<td>' . esc_html( $log->sent_at  ?? '' ) . '</td>';
            echo '<td>';

            if ( $exists && ! $is_trashed ) {
                echo '<a class="button" href="' . esc_url( $view_order_url ) . '">' . esc_html__( 'View Order', 'wd-aero' ) . '</a> ';
            } elseif ( $is_trashed && current_user_can('edit_shop_orders') ) {
                echo '<a class="button" href="' . esc_url( $restore_url ) . '">' . esc_html__( 'Restore Order', 'wd-aero' ) . '</a> ';
            } else {
                echo '<span class="button disabled" aria-disabled="true" style="opacity:.6;cursor:not-allowed;">' . esc_html__( 'Order Missing', 'wd-aero' ) . '</span> ';
            }

            echo '<a class="button" href="' . esc_url( $view_email_url ) . '">' . esc_html__( 'View Email', 'wd-aero' ) . '</a>';

            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="7">' . esc_html__( 'No logs found.', 'wd-aero' ) . '</td></tr>';
    }

    echo '</tbody></table>';
}

    private static function render_email_viewer_tab($order_id) {
        $email_body = get_post_meta($order_id, '_wd_aero_dropship_email', true);
        echo '<h2>Email Sent for Order #' . $order_id . '</h2>';
        echo '<pre style="background:#fff; padding:1em; border:1px solid #ccc; max-width:900px;">' . esc_html($email_body) . '</pre>';
        echo '<p><a href="?page=wd_aero_settings&tab=dropship_orders" class="button">Back to Dropship Orders</a></p>';
    }
}

WD_Aero_Settings::init();