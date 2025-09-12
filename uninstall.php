<?php
// File: uninstall.php
// Path: PGP-Encryption-for-Contact-Page/uninstall.php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit; // Exit if accessed directly
}

// Delete plugin options on uninstall
delete_option('pgp_ecp_options');
