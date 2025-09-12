<?php
/**
 * Plugin Name: PGP Encryption for Contact Page
 * Plugin URI: https://blockstreamtechnologies.llc/pgp-encryption-for-wordpress-contact-pages/
 * Version: 1.0.0
 * Author: Robert Stanghellini
 * Author URI: https://blockstreamtechnologies.llc
 * License: GPL-2.0
 * Description: Adds PGP encryption to WordPress contact forms, allowing secure email communication with public key encryption. <strong>Like this plugin? Please <a href="https://www.paypal.com/donate/?business=CAA8EG3Z7MDHL&no_recurring=0&item_name=Wordpress+plugin+-+PGP+Encryption+for+Contact+Page.&currency_code=USD" title="Send a donation to the developer of WP PGP Encrypted Emails">donate</a>. &hearts; Thank you!</strong>
 * Donate
 * License: GPL-2.0
 * Text Domain: pgp-encryption-for-contact-page
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('PGP_ECP_VERSION', '1.0.0');
define('PGP_ECP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PGP_ECP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check server requirements
function pgp_ecp_check_requirements() {
    if (!extension_loaded('gnupg')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>PGP Encryption for Contact Page requires the GnuPG PHP extension. Please contact your hosting provider to enable it.</p></div>';
        });
        error_log('PGP Encryption Error: GnuPG PHP extension is not enabled.');
        return false;
    }
    return true;
}

// Initialize plugin
function pgp_ecp_init() {
    if (!pgp_ecp_check_requirements()) {
        return;
    }

    // Load classes
    require_once PGP_ECP_PLUGIN_DIR . 'includes/class-pgp-ecp-encryption.php';
    require_once PGP_ECP_PLUGIN_DIR . 'includes/class-pgp-ecp-admin.php';
    require_once PGP_ECP_PLUGIN_DIR . 'includes/class-pgp-ecp-frontend.php';

    // Instantiate classes
    new PGP_ECP_Admin();
    new PGP_ECP_Frontend();

    // Add admin notice for missing configuration
    add_action('admin_notices', function() {
        if (!PGP_ECP_Encryption::is_public_key_valid()) {
            echo '<div class="notice notice-warning"><p>PGP Encryption for Contact Page: Please configure a valid PGP public key in Settings > PGP Encryption to enable the contact form.</p></div>';
        }
    });
}

// Set default options on activation
function pgp_ecp_activate() {
    $options = get_option('pgp_ecp_options', []);
    if (empty($options)) {
        update_option('pgp_ecp_options', [
            'pgp_public_key' => '',
            'pgp_recipient_email' => get_option('admin_email'),
            'pgp_admin_url' => ''
        ]);
    }
}

// Register activation hook
register_activation_hook(__FILE__, 'pgp_ecp_activate');

// Initialize the plugin
add_action('plugins_loaded', 'pgp_ecp_init');