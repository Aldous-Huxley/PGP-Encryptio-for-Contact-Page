<?php
// File: pgp-encryption-for-contact-page.php
// Path: PGP-Encryption-for-Contact-Page/pgp-encryption-for-contact-page.php
/**
 * Plugin Name: PGP Encryption for Contact Page
 * Plugin URI: https://blockstreamtechnologies.llc/pgp-encryption-for-wordpress-contact-pages/
 * Version: 2.0.0
 * Author: Robert Stanghellini
 * Author URI: https://blockstreamtechnologies.llc
 * License: MIT
 * Description: Adds PGP encryption to WordPress contact forms, allowing secure email communication with public key encryption. <strong>Like this plugin? Please <a href="https://www.paypal.com/donate/?business=CAA8EG3Z7MDHL&no_recurring=0&item_name=Wordpress+plugin+-+PGP+Encryption+for+Contact+Page.&currency_code=USD" title="Send a donation to the developer of WP PGP Encrypted Emails">donate</a>. &hearts; Thank you!</strong>
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Check if debugging is enabled
 * @return bool
 */
function pgp_ecp_is_debug_enabled() {
    $options = get_option('pgp_ecp_options', []);
    return defined('WP_DEBUG') && WP_DEBUG && isset($options['pgp_enable_debugging']) && $options['pgp_enable_debugging'];
}

// Define plugin constants
define('PGP_ECP_VERSION', '2.0.0');
define('PGP_ECP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PGP_ECP_PLUGIN_URL', plugin_dir_url(__FILE__));

if (pgp_ecp_is_debug_enabled()) {
    error_log('PGP_ECP: Plugin initialized');
}

// Load dependencies
require_once PGP_ECP_PLUGIN_DIR . 'includes/class-pgp-ecp-activator.php';
require_once PGP_ECP_PLUGIN_DIR . 'includes/class-pgp-ecp-deactivator.php';
require_once PGP_ECP_PLUGIN_DIR . 'includes/class-pgp-ecp-admin.php';
require_once PGP_ECP_PLUGIN_DIR . 'includes/class-pgp-ecp-frontend.php';
require_once PGP_ECP_PLUGIN_DIR . 'includes/class-pgp-ecp-encryption.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, ['PGP_ECP_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['PGP_ECP_Deactivator', 'deactivate']);

// Initialize admin and frontend
function pgp_ecp_init() {
    if (pgp_ecp_is_debug_enabled()) {
        error_log('PGP_ECP: Initializing admin and frontend');
    }

    // Check PHP version for gnupg
    if (!extension_loaded('gnupg')) {
        error_log('PGP_ECP: PHP gnupg extension not loaded.');
        add_action('admin_notices', function() {
            ?>
            <div class="error">
                <p><?php _e('PGP Encryption for Contact Page requires the PHP gnupg extension. Please install it to use this plugin.', 'pgp-encryption'); ?></p>
            </div>
            <?php
        });
        return;
    }

    // Initialize admin
    if (is_admin()) {
        $admin = new PGP_ECP_Admin();
    }

    // Initialize frontend
    $frontend = new PGP_ECP_Frontend();
}

add_action('plugins_loaded', 'pgp_ecp_init');