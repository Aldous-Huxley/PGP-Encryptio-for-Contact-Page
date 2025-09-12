<?php
// File: class-pgp-contact-admin.php
// Path: PGP-Encryption-for-Contact-Page/includes/class-pgp-contact-admin.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class PGP_Contact_Admin {
    public static function init() {
        $instance = new self();
        add_action('admin_menu', [$instance, 'add_settings_page']);
        add_action('admin_init', [$instance, 'register_settings']);
    }

    public function add_settings_page() {
        add_options_page(
            __('PGP Encryption Settings', 'pgp-encryption-for-contact-page'),
                         __('PGP Encryption', 'pgp-encryption-for-contact-page'),
                         'manage_options',
                         'pgp-contact-settings',
                         [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting(
            'pgp_contact_settings_group',
            'pgp_contact_public_key',
            [
                'sanitize_callback' => [$this, 'sanitize_public_key'],
                'default' => ''
            ]
        );

        add_settings_section(
            'pgp_contact_main_section',
            __('PGP Encryption Settings', 'pgp-encryption-for-contact-page'),
                             null,
                             'pgp-contact-settings'
        );

        add_settings_field(
            'pgp_contact_public_key',
            __('PGP Public Key', 'pgp-encryption-for-contact-page'),
                           [$this, 'render_public_key_field'],
                           'pgp-contact-settings',
                           'pgp_contact_main_section'
        );
    }

    public function sanitize_public_key($input) {
        $input = trim($input);
        if (!empty($input) && !PGP_Contact_Encryptor::is_valid_public_key($input)) {
            add_settings_error(
                'pgp_contact_public_key',
                'invalid_pgp_key',
                __('The provided PGP public key is invalid.', 'pgp-encryption-for-contact-page'),
                               'error'
            );
            return '';
        }
        return $input;
    }

    public function render_public_key_field() {
        $public_key = get_option('pgp_contact_public_key', '');
        ?>
        <textarea name="pgp_contact_public_key" rows="10" cols="50" class="large-text"><?php echo esc_textarea($public_key); ?></textarea>
        <p class="description"><?php esc_html_e('Enter your PGP public key to encrypt contact form emails.', 'pgp-encryption-for-contact-page'); ?></p>
        <?php
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
        <h1><?php esc_html_e('PGP Encryption for Contact Page', 'pgp-encryption-for-contact-page'); ?></h1>
        <form method="post" action="options.php">
        <?php
        settings_fields('pgp_contact_settings_group');
        do_settings_sections('pgp-contact-settings');
        submit_button();
        ?>
        </form>
        </div>
        <?php
    }
}
