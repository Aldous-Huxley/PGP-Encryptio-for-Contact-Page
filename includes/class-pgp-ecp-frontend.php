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
        $post = get_post();
        if ($post && has_shortcode($post->post_content, 'pgp_contact_form')) {
            wp_enqueue_script('pgp-ecp-frontend-js', PGP_ECP_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], PGP_ECP_VERSION, true);
            // Use custom admin URL if valid, otherwise fall back to default
            $options = get_option('pgp_ecp_options', []);
            $custom_admin_url = !empty($options['pgp_admin_url']) ? trailingslashit($options['pgp_admin_url']) . 'admin-ajax.php' : admin_url('admin-ajax.php');
            // Validate custom admin URL
            $ajax_url = $this->validate_ajax_url($custom_admin_url);
            wp_localize_script('pgp-ecp-frontend-js', 'pgp_ecp_ajax', [
                'ajax_url' => $ajax_url,
                'nonce'    => wp_create_nonce('pgp_ecp_submit'),
            ]);
            wp_enqueue_style('pgp-ecp-frontend-css', PGP_ECP_PLUGIN_URL . 'assets/css/frontend.css', [], PGP_ECP_VERSION);
        }
    }

    /**
     * Validate the AJAX URL and fall back to default if invalid
     * @param string $custom_url The custom admin URL
     * @return string Valid AJAX URL
     */
    private function validate_ajax_url($custom_url) {
        $default_url = admin_url('admin-ajax.php');
        // Test custom URL with a HEAD request
        $response = wp_remote_head($custom_url, ['timeout' => 5]);
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            error_log('PGP_ECP: Custom AJAX URL validated: ' . $custom_url);
            return $custom_url;
        } else {
            $error = is_wp_error($response) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code($response);
            error_log('PGP_ECP: Custom AJAX URL failed (' . $error . '), falling back to default: ' . $default_url);
            add_action('admin_notices', function() use ($custom_url, $error) {
                echo '<div class="notice notice-error"><p>PGP Encryption: Custom AJAX URL (' . esc_html($custom_url) . ') is invalid (' . esc_html($error) . '). Using default URL. Please check settings.</p></div>';
            });
            return $default_url;
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
        // Log request start
        error_log('PGP_ECP: Starting handle_form_submission');

        // Check nonce
        if (!check_ajax_referer('pgp_ecp_submit', 'nonce', false)) {
            error_log('PGP_ECP: Nonce verification failed.');
            wp_send_json_error(['message' => 'Security check failed. Please refresh and try again.']);
        }
        error_log('PGP_ECP: Nonce verified successfully');

        // Validate form data
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($subject) || empty($message) || !is_email($email)) {
            error_log('PGP_ECP: Invalid form data. Name: ' . (empty($name) ? 'empty' : 'set') . ', Email: ' . (empty($email) ? 'empty' : 'set') . ', Subject: ' . (empty($subject) ? 'empty' : 'set') . ', Message: ' . (empty($message) ? 'empty' : 'set') . ', Valid Email: ' . (is_email($email) ? 'yes' : 'no'));
            wp_send_json_error(['message' => 'Invalid form data. Please fill all fields correctly.']);
        }
        error_log('PGP_ECP: Form data validated successfully');

        $full_message = "From: $name <$email>\nSubject: $subject\n\n$message";

        // Initialize encryption
        $this->encryption = new PGP_ECP_Encryption();
        if (!$this->encryption->import_public_key()) {
            error_log('PGP_ECP: Failed to import PGP key.');
            wp_send_json_error(['message' => 'Failed to import PGP key. Check server logs for details.']);
        }
        error_log('PGP_ECP: PGP key imported successfully');

        // Encrypt message
        $encrypted_message = $this->encryption->encrypt_message($full_message);
        if (!$encrypted_message) {
            error_log('PGP_ECP: Encryption failed.');
            wp_send_json_error(['message' => 'Encryption failed. Check server logs for details.']);
        }
        error_log('PGP_ECP: Message encrypted successfully');

        // Send email
        $options = get_option('pgp_ecp_options', []);
        $recipient = $options['pgp_recipient_email'] ?? get_option('admin_email');
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        error_log('PGP_ECP: Attempting to send email to ' . $recipient);
        $sent = wp_mail($recipient, 'Encrypted Contact Form Message', $encrypted_message, $headers);

        if ($sent) {
            error_log('PGP_ECP: Email sent successfully');
            wp_send_json_success(['message' => 'Message sent successfully!']);
        } else {
            error_log('PGP_ECP: Failed to send email to ' . $recipient);
            wp_send_json_error(['message' => 'Failed to send email. Check email configuration or server logs.']);
        }
    }
}