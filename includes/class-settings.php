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
        register_setting('wd_aero_options', 'wd_aero_categories');
        register_setting('wd_aero_options', 'wd_aero_distributor_number');
        register_setting('wd_aero_options', 'wd_aero_distributor_address');
        register_setting('wd_aero_options', 'wd_aero_partner_email');
    }

    public static function settings_page() {
    ?>
    <div class="wrap">
        <h1>Dropship Aero Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wd_aero_options'); ?>
            <?php do_settings_sections('wd_aero_settings'); ?>
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

                echo '<select name="wd_aero_categories[]" multiple size="5" style="height:auto;">';
                foreach ($categories as $cat) {
                    $is_selected = in_array($cat->term_id, (array)$selected) ? 'selected' : '';
                    echo "<option value='{$cat->term_id}' $is_selected>{$cat->name}</option>";
            }
            echo '</select>';
?>

                        
                        <p class="description">Select one or more categories to trigger dropshipping.</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Partner Email</th>
                    <td>
                        <input type="email" name="wd_aero_partner_email" value="<?php echo esc_attr(get_option('wd_aero_partner_email')); ?>" class="regular-text" />
                        <p class="description">Email address where the dropship order should be sent.</p>
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
    </div>
    <?php
}

}

WD_Aero_Settings::init();
