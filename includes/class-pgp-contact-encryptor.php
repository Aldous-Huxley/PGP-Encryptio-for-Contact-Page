<?php
// File: class-pgp-contact-encryptor.php
// Path: PGP-Encryption-for-Contact-Page/includes/class-pgp-contact-encryptor.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class PGP_Contact_Encryptor {
    /**
     * Encrypt a message using a PGP public key.
     *
     * @param string $message The message to encrypt.
     * @param string $public_key The PGP public key.
     * @return string|WP_Error The encrypted message or WP_Error on failure.
     */
    public static function encrypt_message($message, $public_key) {
        try {
            // Ensure OpenPGP library is available
            if (!class_exists('\OpenPGP')) {
                require_once PGP_CONTACT_PLUGIN_DIR . 'vendor/autoload.php';
            }

            // Validate public key
            if (empty($public_key)) {
                return new WP_Error('pgp_error', __('No public key provided.', 'pgp-encryption-for-contact-page'));
            }

            // Parse the public key
            $key = \OpenPGP_PublicKeyPacket::parse($public_key);
            if (!$key) {
                return new WP_Error('pgp_error', __('Invalid public key.', 'pgp-encryption-for-contact-page'));
            }

            // Create a new message
            $data = new \OpenPGP_LiteralDataPacket($message, ['format' => 'utf8']);
            $encrypted = \OpenPGP_Crypt_Symmetric::encrypt([$key], new \OpenPGP_Message([$data]));

            // Convert to armored ASCII
            $armored = \OpenPGP::enarmor($encrypted->to_bytes(), 'PGP MESSAGE');

            return $armored;
        } catch (Exception $e) {
            return new WP_Error('pgp_error', __('Encryption failed: ', 'pgp-encryption-for-contact-page') . $e->getMessage());
        }
    }

    /**
     * Validate a PGP public key.
     *
     * @param string $public_key The PGP public key to validate.
     * @return bool True if valid, false otherwise.
     */
    public static function is_valid_public_key($public_key) {
        try {
            $key = \OpenPGP_PublicKeyPacket::parse($public_key);
            return !empty($key);
        } catch (Exception $e) {
            return false;
        }
    }
}
