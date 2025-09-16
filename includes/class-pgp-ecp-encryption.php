<?php
// File: class-pgp-ecp-encryption.php
// Path: PGP-Encryption-for-Contact-Page/includes/class-pgp-ecp-encryption.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PGP_ECP_Encryption {
    private $gpg;
    private $public_key;
    private $fingerprint;

    /**
     * Check if debugging is enabled
     * @return bool
     */
    private function is_debug_enabled() {
        $options = get_option('pgp_ecp_options', []);
        return defined('WP_DEBUG') && WP_DEBUG && isset($options['pgp_enable_debugging']) && $options['pgp_enable_debugging'];
    }

    public function __construct() {
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: PGP_ECP_Encryption constructor called');
        }
        $this->gpg = new gnupg();
        $options = get_option('pgp_ecp_options', []);
        $this->public_key = $options['pgp_public_key'] ?? '';
        $this->fingerprint = null; // Initialize fingerprint
    }

    /**
     * Import the PGP public key
     * @return bool
     */
    public function import_public_key() {
        if (empty($this->public_key)) {
            error_log('PGP_ECP: No PGP public key set.');
            return false;
        }

        // Skip if already imported
        if ($this->fingerprint !== null) {
            if ($this->is_debug_enabled()) {
                error_log('PGP_ECP: PGP key already imported, skipping');
            }
            return true;
        }

        try {
            $import = $this->gpg->import($this->public_key);
            if (!$import || empty($import['fingerprint'])) {
                error_log('PGP_ECP: Failed to import PGP public key: Invalid key or import error.');
                return false;
            }

            $this->fingerprint = $import['fingerprint'];
            if ($this->is_debug_enabled()) {
                error_log('PGP_ECP: PGP key imported successfully, fingerprint: ' . $this->fingerprint);
            }
            return true;
        } catch (Exception $e) {
            error_log('PGP_ECP: PGP key import failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Encrypt the message
     * @param string $message
     * @return string|bool
     */
    public function encrypt_message($message) {
        try {
            if (!$this->fingerprint) {
                error_log('PGP_ECP: No valid PGP key fingerprint available.');
                return false;
            }

            $this->gpg->addencryptkey($this->fingerprint);
            $encrypted = $this->gpg->encrypt($message);

            if (!$encrypted) {
                error_log('PGP_ECP: Message encryption failed: No encrypted data returned.');
                return false;
            }

            if ($this->is_debug_enabled()) {
                error_log('PGP_ECP: Message encrypted successfully');
            }
            return $encrypted;
        } catch (Exception $e) {
            error_log('PGP_ECP: Message encryption failed: ' . $e->getMessage());
            return false;
        }
    }
}