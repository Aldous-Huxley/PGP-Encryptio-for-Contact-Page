<?php
// File: class-pgp-ecp-frontend.php
// Path: PGP-Encryption-for-Contact-Page/includes/class-pgp-ecp-frontend.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PGP_ECP_Frontend {
    private $encryption;

    public function __construct() {
        add_shortcode('pgp_contact_form', [$this, 'render_contact_form']);
        add_shortcode('pgp_public_key', [$this, 'render_public_key']);
        add_action('wp_ajax_pgp_ecp_submit_form', [$this, 'handle_form_submission']);
        add_action('wp_ajax_nopriv_pgp_ecp_submit_form', [$this, 'handle_form_submission']);

        // Load frontend assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_assets() {
        if (has_shortcode(get_post()->post_content, 'pgp_contact_form')) {
            wp_enqueue_script('pgp-ecp-frontend-js', PGP_ECP_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], PGP_ECP_VERSION, true);
            // Use custom admin URL if set, otherwise fall back to default
            $options = get_option('pgp_ecp_options', []);
            $custom_admin_url = !empty($options['pgp_admin_url']) ? trailingslashit($options['pgp_admin_url']) . 'admin-ajax.php' : admin_url('admin-ajax.php');
            $ajax_url = apply_filters('pgp_ecp_ajax_url', $custom_admin_url);
            wp_localize_script('pgp-ecp-frontend-js', 'pgp_ecp_ajax', [
                'ajax_url' => $ajax_url,
                'nonce'    => wp_create_nonce('pgp_ecp_submit'),
            ]);
            wp_enqueue_style('pgp-ecp-frontend-css', PGP_ECP_PLUGIN_URL . 'assets/css/frontend.css', [], PGP_ECP_VERSION);
        }
    }

    /**
     * Render the contact form shortcode
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_contact_form($atts) {
        if (!PGP_ECP_Encryption::is_public_key_valid()) {
            return '<p>PGP encryption is not configured properly. Please contact the site administrator.</p>';
        }

        ob_start();
        ?>
        <form id="pgp-ecp-contact-form" method="post">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="subject">Subject:</label>
            <input type="text" id="subject" name="subject" required>
            
            <label for="message">Message:</label>
            <textarea id="message" name="message" required></textarea>
            
            <input type="submit" value="Send Encrypted Message">
        </form>
        <div id="pgp-ecp-response"></div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the public key shortcode
     * @return string HTML output
     */
    public function render_public_key() {
        $encryption = new PGP_ECP_Encryption();
        $public_key = $encryption->get_public_key();
        
        if (empty($public_key)) {
            return '<p>No public key available.</p>';
        }
        
        return '<pre>' . esc_html($public_key) . '</pre>';
    }

    /**
     * Handle AJAX form submission
     */
    public function handle_form_submission() {
        check_ajax_referer('pgp_ecp_submit', 'nonce');

        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($subject) || empty($message) || !is_email($email)) {
            wp_send_json_error(['message' => 'Invalid form data.']);
        }

        $full_message = "From: $name <$email>\nSubject: $subject\n\n$message";

        $this->encryption = new PGP_ECP_Encryption();
        if (!$this->encryption->import_public_key()) {
            wp_send_json_error(['message' => 'Failed to import PGP key.']);
        }

        $encrypted_message = $this->encryption->encrypt_message($full_message);

        if (!$encrypted_message) {
            wp_send_json_error(['message' => 'Encryption failed.']);
        }

        $options = get_option('pgp_ecp_options', []);
        $recipient = $options['pgp_recipient_email'] ?? get_option('admin_email');

        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        $sent = wp_mail($recipient, 'Encrypted Contact Form Message', $encrypted_message, $headers);

        if ($sent) {
            wp_send_json_success(['message' => 'Message sent successfully!']);
        } else {
            wp_send_json_error(['message' => 'Failed to send email.']);
        }
    }
}