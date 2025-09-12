<?php
// File: class-pgp-ecp-encryption.php
// Path: PGP-Encryption-for-Contact-Page/includes/class-pgp-ecp-encryption.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PGP_ECP_Encryption {
    private $gpg;
    private $public_key;

    public function __construct() {
        // Check if gnupg extension is available
        if (!extension_loaded('gnupg')) {
            error_log('PGP Encryption Error: GnuPG PHP extension is not enabled.');
            throw new Exception('GnuPG PHP extension is required for PGP encryption.');
        }
        $this->gpg = new gnupg();
        $this->gpg->seterrormode(GNUPG_ERROR_EXCEPTION);
    }

    /**
     * Import the public key from options
     * @return bool Success status
     */
    public function import_public_key() {
        $options = get_option('pgp_ecp_options', []);
        $public_key = sanitize_textarea_field($options['pgp_public_key'] ?? '');

        if (empty($public_key)) {
            error_log('PGP Key Import Error: No public key provided.');
            return false;
        }

        try {
            $import_result = $this->gpg->import($public_key);
            if ($import_result && !empty($import_result['fingerprint'])) {
                $this->public_key = $import_result['fingerprint'];
                return true;
            } else {
                error_log('PGP Key Import Error: Invalid key format or no fingerprint. Import result: ' . print_r($import_result, true));
                return false;
            }
        } catch (Exception $e) {
            error_log('PGP Key Import Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Encrypt a message using the imported public key
     * @param string $message The plaintext message to encrypt
     * @return string|false Encrypted message or false on failure
     */
    public function encrypt_message($message) {
        if (!$this->public_key || empty($message)) {
            error_log('PGP Encryption Error: ' . (!$this->public_key ? 'No valid public key set.' : 'Empty message provided.'));
            return false;
        }

        try {
            // Clear any previous keys
            $this->gpg->clearencryptkeys();
            // Add the encryption key
            $this->gpg->addencryptkey($this->public_key);
            // Encrypt the message
            $encrypted = $this->gpg->encrypt($message);
            if ($encrypted === false) {
                error_log('PGP Encryption Error: Encryption failed with no output.');
                return false;
            }
            return $encrypted;
        } catch (Exception $e) {
            error_log('PGP Encryption Exception: ' . $e->getMessage() . ' in encrypt_message');
            return false;
        }
    }

    /**
     * Get the armored public key for display or export
     * @return string The armored public key
     */
    public function get_public_key() {
        $options = get_option('pgp_ecp_options', []);
        return sanitize_textarea_field($options['pgp_public_key'] ?? '');
    }

    /**
     * Validate if the public key is properly set
     * @return bool
     */
    public static function is_public_key_valid() {
        $options = get_option('pgp_ecp_options', []);
        $public_key = sanitize_textarea_field($options['pgp_public_key'] ?? '');
        if (empty($public_key) || strpos($public_key, 'BEGIN PGP PUBLIC KEY BLOCK') === false) {
            error_log('PGP Key Validation Error: ' . (empty($public_key) ? 'No public key set.' : 'Key does not contain BEGIN PGP PUBLIC KEY BLOCK.'));
            return false;
        }
        return true;
    }
}