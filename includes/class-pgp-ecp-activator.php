<?php
// File: class-pgp-ecp-activator.php
// Path: PGP-Encryption-for-Contact-Page/includes/class-pgp-ecp-activator.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PGP_ECP_Activator {
    /**
     * Check if debugging is enabled
     * @return bool
     */
    private function is_debug_enabled() {
        $options = get_option('pgp_ecp_options', []);
        return defined('WP_DEBUG') && WP_DEBUG && isset($options['pgp_enable_debugging']) && $options['pgp_enable_debugging'];
    }

    /**
     * Activate the plugin
     */
    public static function activate() {
        $instance = new self();
        if ($instance->is_debug_enabled()) {
            error_log('PGP_ECP: Activating PGP Encryption for Contact Page');
        }

        // Check for PHP gnupg extension
        if (!extension_loaded('gnupg')) {
            error_log('PGP_ECP: Activation failed - PHP gnupg extension not loaded.');
            wp_die('PGP Encryption for Contact Page requires the PHP gnupg extension. Please install it and try again.');
        }

        // Set default options
        $options = get_option('pgp_ecp_options', []);
        if (empty($options)) {
            $options = [
                'pgp_public_key' => '',
                'pgp_recipient_email' => '',
                'pgp_admin_url' => '',
                'pgp_enable_debugging' => 0,
                'pgp_enable_turnstile' => 0,
                'pgp_turnstile_site_key' => '',
                'pgp_turnstile_secret_key' => ''
            ];
            update_option('pgp_ecp_options', $options);
            if ($instance->is_debug_enabled()) {
                error_log('PGP_ECP: Default options set during activation');
            }
        }

        if ($instance->is_debug_enabled()) {
            error_log('PGP_ECP: Plugin activated successfully');
        }
    }
}