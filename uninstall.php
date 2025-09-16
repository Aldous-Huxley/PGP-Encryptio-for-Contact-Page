<?php
// File: uninstall.php
// Path: PGP-Encryption-for-Contact-Page/uninstall.php

if (!defined('WP_UNINSTALL_PLUGIN')) {
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

if (pgp_ecp_is_debug_enabled()) {
    error_log('PGP_ECP: Starting plugin uninstall');
}

// Remove plugin options
$deleted = delete_option('pgp_ecp_options');
if (!$deleted) {
    error_log('PGP_ECP: Failed to delete plugin options.');
}

if (pgp_ecp_is_debug_enabled()) {
    error_log('PGP_ECP: Plugin options deleted successfully');
}

// Remove transients (if any)
delete_transient('pgp_ecp_temp_data');

if (pgp_ecp_is_debug_enabled()) {
    error_log('PGP_ECP: Plugin uninstall completed');
}