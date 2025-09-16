<?php
// File: class-pgp-ecp-deactivator.php
// Path: PGP-Encryption-for-Contact-Page/includes/class-pgp-ecp-deactivator.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PGP_ECP_Deactivator {
    /**
     * Check if debugging is enabled
     * @return bool
     */
    private function is_debug_enabled() {
        $options = get_option('pgp_ecp_options', []);
        return defined('WP_DEBUG') && WP_DEBUG && isset($options['pgp_enable_debugging']) && $options['pgp_enable_debugging'];
    }

    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        $instance = new self();
        if ($instance->is_debug_enabled()) {
            error_log('PGP_ECP: Deactivating PGP Encryption for Contact Page');
        }

        // Perform deactivation tasks (e.g., clean up temporary data)
        delete_transient('pgp_ecp_temp_data');

        if ($instance->is_debug_enabled()) {
            error_log('PGP_ECP: Plugin deactivated successfully');
        }
    }
}