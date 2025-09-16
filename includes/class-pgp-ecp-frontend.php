<?php
// File: class-pgp-ecp-frontend.php
// Path: PGP-Encryption-for-Contact-Page/includes/class-pgp-ecp-frontend.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PGP_ECP_Frontend {
    private $encryption;

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
            error_log('PGP_ECP: PGP_ECP_Frontend constructor called');
        }
        add_shortcode('pgp_contact_form', [$this, 'render_contact_form']);
        add_action('wp_ajax_pgp_ecp_submit_form', [$this, 'handle_form_submission']);
        add_action('wp_ajax_nopriv_pgp_ecp_submit_form', [$this, 'handle_form_submission']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: Enqueuing assets for pgp_contact_form shortcode');
        }
        wp_enqueue_style('pgp-ecp-frontend', PGP_ECP_PLUGIN_URL . 'assets/css/frontend.css', [], PGP_ECP_VERSION);

        $options = get_option('pgp_ecp_options', []);
        $custom_admin_url = !empty($options['pgp_admin_url']) ? trailingslashit($options['pgp_admin_url']) . 'admin-ajax.php' : admin_url('admin-ajax.php');
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: Attempting custom AJAX URL: ' . $custom_admin_url);
        }

        $response = wp_remote_head($custom_admin_url, ['timeout' => 5]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $custom_admin_url = admin_url('admin-ajax.php');
            if ($this->is_debug_enabled()) {
                error_log('PGP_ECP: Using default AJAX URL: ' . $custom_admin_url . ' (custom URL empty or invalid: ' . ($options['pgp_admin_url'] ?? '') . ')');
            }
        }

        wp_enqueue_script('pgp-ecp-frontend', PGP_ECP_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], PGP_ECP_VERSION, true);
        wp_localize_script('pgp-ecp-frontend', 'pgp_ecp_vars', [
            'ajax_url' => $custom_admin_url,
            'nonce' => wp_create_nonce('pgp_ecp_submit_form'),
            'debug_enabled' => $this->is_debug_enabled()
        ]);

        if (!empty($options['pgp_turnstile_site_key']) && !empty($options['pgp_enable_turnstile'])) {
            wp_enqueue_script('cf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [], null, true);
            if ($this->is_debug_enabled()) {
                error_log('PGP_ECP: Enqueued cf-turnstile script');
            }
        }
    }

    /**
     * Render the contact form shortcode
     */
    public function render_contact_form() {
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: Rendering contact form');
        }
        $options = get_option('pgp_ecp_options', []);
        if (empty($options['pgp_public_key'])) {
            error_log('PGP_ECP: PGP encryption not configured properly');
            return '<p>Error: PGP encryption is not configured properly. Please set a valid PGP public key in the admin settings.</p>';
        }

        $turnstile_site_key = !empty($options['pgp_enable_turnstile']) ? $options['pgp_turnstile_site_key'] : '';
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: Turnstile site key: ' . ($turnstile_site_key ? 'set' : 'empty'));
        }

        ob_start();
        ?>
        <form id="pgp-ecp-contact-form" method="post" action="">
            <label for="pgp-ecp-name">Name:</label>
            <input type="text" id="pgp-ecp-name" name="pgp_ecp_name" required>
            <label for="pgp-ecp-email">Email:</label>
            <input type="email" id="pgp-ecp-email" name="pgp_ecp_email" required>
            <label for="pgp-ecp-subject">Subject:</label>
            <input type="text" id="pgp-ecp-subject" name="pgp_ecp_subject" required>
            <label for="pgp-ecp-message">Message:</label>
            <textarea id="pgp-ecp-message" name="pgp_ecp_message" required></textarea>
            <?php if (!empty($turnstile_site_key)) { ?>
                <div class="cf-turnstile" data-sitekey="<?php echo esc_attr($turnstile_site_key); ?>"></div>
                <?php
                if ($this->is_debug_enabled()) {
                    error_log('PGP_ECP: Turnstile widget included in form');
                }
            } else {
                if ($this->is_debug_enabled()) {
                    error_log('PGP_ECP: Turnstile widget skipped, no site key');
                }
            }
            ?>
            <input type="hidden" name="action" value="pgp_ecp_submit_form">
            <?php wp_nonce_field('pgp_ecp_submit_form', 'pgp_ecp_nonce'); ?>
            <button type="submit">Submit</button>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle form submission
     */
    public function handle_form_submission() {
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: Starting handle_form_submission');
        }

        // Verify nonce
        if (!isset($_POST['pgp_ecp_nonce']) || !wp_verify_nonce($_POST['pgp_ecp_nonce'], 'pgp_ecp_submit_form')) {
            error_log('PGP_ECP: Nonce verification failed.');
            wp_send_json_error(['message' => 'Nonce verification failed.']);
        }

        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: Nonce verified successfully');
        }

        $options = get_option('pgp_ecp_options', []);
        $turnstile_site_key = !empty($options['pgp_enable_turnstile']) ? $options['pgp_turnstile_site_key'] : '';
        $turnstile_secret_key = !empty($options['pgp_enable_turnstile']) ? $options['pgp_turnstile_secret_key'] : '';

        // Verify Turnstile token if enabled
        if (!empty($turnstile_site_key) && !empty($turnstile_secret_key)) {
            $turnstile_token = sanitize_text_field($_POST['cf-turnstile-response'] ?? '');
            if ($this->is_debug_enabled()) {
                error_log('PGP_ECP: Turnstile token received: ' . ($turnstile_token ? 'set' : 'empty'));
            }

            if (empty($turnstile_token)) {
                error_log('PGP_ECP: Cloudflare Turnstile token missing.');
                wp_send_json_error(['message' => 'CAPTCHA verification failed. Please complete the CAPTCHA.']);
            }

            // Verify Turnstile token
            $response = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'body' => [
                    'secret' => $turnstile_secret_key,
                    'response' => $turnstile_token
                ]
            ]);

            if (is_wp_error($response)) {
                error_log('PGP_ECP: Turnstile verification failed: ' . $response->get_error_message());
                wp_send_json_error(['message' => 'CAPTCHA verification failed.']);
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!$body['success']) {
                error_log('PGP_ECP: Turnstile verification failed: ' . implode(', ', $body['error-codes'] ?? ['Unknown error']));
                wp_send_json_error(['message' => 'CAPTCHA verification failed.']);
            }

            if ($this->is_debug_enabled()) {
                error_log('PGP_ECP: Turnstile token verified successfully');
            }
        }

        // Validate form data
        $name = sanitize_text_field($_POST['pgp_ecp_name'] ?? '');
        $email = sanitize_email($_POST['pgp_ecp_email'] ?? '');
        $subject = sanitize_text_field($_POST['pgp_ecp_subject'] ?? '');
        $message = sanitize_textarea_field($_POST['pgp_ecp_message'] ?? '');

        if (empty($name) || empty($email) || empty($subject) || empty($message) || !is_email($email)) {
            error_log('PGP_ECP: Invalid form data.');
            wp_send_json_error(['message' => 'Invalid form data.']);
        }

        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: Form data validated successfully');
        }

        // Initialize encryption once
        if (!$this->encryption) {
            $this->encryption = new PGP_ECP_Encryption();
            if ($this->is_debug_enabled()) {
                error_log('PGP_ECP: Encryption instance created');
            }
        }

        // Import public key
        if (!$this->encryption->import_public_key()) {
            error_log('PGP_ECP: Failed to import PGP key.');
            wp_send_json_error(['message' => 'Failed to import PGP key. Check server logs for details.']);
        }

        // Encrypt message
        $full_message = "Name: $name\nEmail: $email\nSubject: $subject\nMessage: $message";
        $encrypted_message = $this->encryption->encrypt_message($full_message);

        if (!$encrypted_message) {
            error_log('PGP_ECP: Message encryption failed.');
            wp_send_json_error(['message' => 'Message encryption failed.']);
        }

        // Send email
        $recipient_email = $options['pgp_recipient_email'] ?? '';
        if (empty($recipient_email)) {
            error_log('PGP_ECP: Recipient email not set.');
            wp_send_json_error(['message' => 'Recipient email not configured.']);
        }

        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: Attempting to send email to ' . $recipient_email);
        }

        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        $sent = wp_mail($recipient_email, 'New Encrypted Contact Form Submission', $encrypted_message, $headers);

        if (!$sent) {
            error_log('PGP_ECP: Email sending failed.');
            wp_send_json_error(['message' => 'Failed to send email.']);
        }

        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: Email sent successfully');
        }

        wp_send_json_success(['message' => 'Message sent successfully!']);
    }
}