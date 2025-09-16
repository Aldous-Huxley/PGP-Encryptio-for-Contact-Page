<?php
// File: class-pgp-ecp-admin.php
// Path: PGP-Encryption-for-Contact-Page/includes/class-pgp-ecp-admin.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PGP_ECP_Admin {
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
            error_log('PGP_ECP: PGP_ECP_Admin constructor called');
        }
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        // Enqueue script for enabling/disabling Turnstile fields
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Enqueue admin scripts for Turnstile checkbox functionality
     */
    public function enqueue_admin_scripts($hook) {
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: Enqueuing admin JavaScript for hook: ' . $hook . ', URL: ' . PGP_ECP_PLUGIN_URL . 'assets/js/admin.js');
        }
        if ($hook !== 'settings_page_pgp-ecp-settings') {
            if ($this->is_debug_enabled()) {
                error_log('PGP_ECP: Skipping admin JavaScript enqueue, wrong hook: ' . $hook);
            }
            return;
        }
        wp_enqueue_script('jquery');
        wp_enqueue_script('pgp-ecp-admin-js', PGP_ECP_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], PGP_ECP_VERSION, true);
    }

    /**
     * Add the settings page to the admin menu
     */
    public function add_settings_page() {
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: add_settings_page called');
        }
        add_options_page(
            'PGP Encryption Settings',
            'PGP Encryption',
            'manage_options',
            'pgp-ecp-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings, sections, and fields
     */
    public function register_settings() {
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: register_settings called');
        }
        register_setting('pgp_ecp_options_group', 'pgp_ecp_options', [$this, 'sanitize_options']);

        add_settings_section(
            'pgp_ecp_main_section',
            'Main Settings',
            function() {
                if ($this->is_debug_enabled()) {
                    error_log('PGP_ECP: Main Settings section rendered');
                }
                echo '<p>Configure the settings below to enable PGP encryption and Cloudflare Turnstile for your contact form.</p>';
            },
            'pgp-ecp-settings'
        );

        add_settings_field(
            'pgp_public_key',
            'PGP Public Key',
            [$this, 'render_public_key_field'],
            'pgp-ecp-settings',
            'pgp_ecp_main_section'
        );

        add_settings_field(
            'pgp_recipient_email',
            'Recipient Email',
            [$this, 'render_recipient_email_field'],
            'pgp-ecp-settings',
            'pgp_ecp_main_section'
        );

        add_settings_field(
            'pgp_admin_url',
            'Custom Admin URL',
            [$this, 'render_admin_url_field'],
            'pgp-ecp-settings',
            'pgp_ecp_main_section'
        );

        add_settings_field(
            'pgp_enable_debugging',
            'Enable Debugging Logs',
            [$this, 'render_enable_debugging_field'],
            'pgp-ecp-settings',
            'pgp_ecp_main_section'
        );

        add_settings_field(
            'pgp_enable_turnstile',
            'Enable Cloudflare Turnstile',
            [$this, 'render_enable_turnstile_field'],
            'pgp-ecp-settings',
            'pgp_ecp_main_section'
        );

        add_settings_field(
            'pgp_turnstile_site_key',
            'Cloudflare Turnstile Site Key',
            [$this, 'render_turnstile_site_key_field'],
            'pgp-ecp-settings',
            'pgp_ecp_main_section'
        );

        add_settings_field(
            'pgp_turnstile_secret_key',
            'Cloudflare Turnstile Secret Key',
            [$this, 'render_turnstile_secret_key_field'],
            'pgp-ecp-settings',
            'pgp_ecp_main_section'
        );
    }

    /**
     * Sanitize and validate options
     * @param array $input Input data
     * @return array Sanitized data
     */
    public function sanitize_options($input) {
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: sanitize_options called');
        }

        // Skip validation unless explicitly saving settings form
        if (empty($_POST) || !isset($_POST['option_page']) || $_POST['option_page'] !== 'pgp_ecp_options_group' || !isset($_POST['action']) || $_POST['action'] !== 'update' || !isset($_POST['pgp_ecp_options'])) {
            if ($this->is_debug_enabled()) {
                error_log('PGP_ECP: Not a form submission, skipping validation and returning existing options');
            }
            return get_option('pgp_ecp_options', []);
        }

        // Initialize sanitized array
        $sanitized = [];
        $sanitized['pgp_public_key'] = sanitize_textarea_field($input['pgp_public_key'] ?? '');
        $sanitized['pgp_recipient_email'] = sanitize_email($input['pgp_recipient_email'] ?? '');
        $sanitized['pgp_admin_url'] = esc_url_raw($input['pgp_admin_url'] ?? '');
        $sanitized['pgp_enable_debugging'] = isset($input['pgp_enable_debugging']) ? 1 : 0;
        $sanitized['pgp_enable_turnstile'] = isset($input['pgp_enable_turnstile']) ? 1 : 0;
        $sanitized['pgp_turnstile_site_key'] = sanitize_text_field($input['pgp_turnstile_site_key'] ?? '');
        $sanitized['pgp_turnstile_secret_key'] = sanitize_text_field($input['pgp_turnstile_secret_key'] ?? '');

        // Log key lengths and first 5 chars (obfuscated)
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: Turnstile site key length: ' . strlen($sanitized['pgp_turnstile_site_key']) . ', first 5 chars: ' . substr($sanitized['pgp_turnstile_site_key'], 0, 5));
            error_log('PGP_ECP: Turnstile secret key length: ' . strlen($sanitized['pgp_turnstile_secret_key']) . ', first 5 chars: ' . substr($sanitized['pgp_turnstile_secret_key'], 0, 5));
            error_log('PGP_ECP: Enable Turnstile: ' . ($sanitized['pgp_enable_turnstile'] ? 'enabled' : 'disabled'));
            error_log('PGP_ECP: Enable Debugging: ' . ($sanitized['pgp_enable_debugging'] ? 'enabled' : 'disabled'));
        }

        // Clear Turnstile keys if disabled
        if (!$sanitized['pgp_enable_turnstile']) {
            $sanitized['pgp_turnstile_site_key'] = '';
            $sanitized['pgp_turnstile_secret_key'] = '';
            if ($this->is_debug_enabled()) {
                error_log('PGP_ECP: Turnstile disabled, clearing site and secret keys');
            }
        }

        // Validate public key
        if (empty($sanitized['pgp_public_key'])) {
            add_settings_error('pgp_ecp_options', 'pgp_key_empty', 'PGP public key is required for form encryption. Please paste a valid key starting with "-----BEGIN PGP PUBLIC KEY BLOCK-----".', 'warning');
        } elseif (strpos($sanitized['pgp_public_key'], 'BEGIN PGP PUBLIC KEY BLOCK') === false) {
            add_settings_error('pgp_ecp_options', 'pgp_key_invalid', 'Invalid PGP public key format. It must start with "-----BEGIN PGP PUBLIC KEY BLOCK-----".', 'error');
            $sanitized['pgp_public_key'] = '';
        } else {
            // Test key import
            try {
                $gpg = new gnupg();
                $import = $gpg->import($sanitized['pgp_public_key']);
                if (!$import || empty($import['fingerprint'])) {
                    add_settings_error('pgp_ecp_options', 'pgp_key_invalid', 'The PGP public key could not be imported. Ensure it is a valid armored key.', 'error');
                    $sanitized['pgp_public_key'] = '';
                }
            } catch (Exception $e) {
                add_settings_error('pgp_ecp_options', 'pgp_key_invalid', 'PGP key import failed: ' . esc_html($e->getMessage()), 'error');
                error_log('PGP_ECP: PGP key import failed: ' . $e->getMessage());
                $sanitized['pgp_public_key'] = '';
            }
        }

        // Validate email
        if (!empty($sanitized['pgp_recipient_email']) && !is_email($sanitized['pgp_recipient_email'])) {
            add_settings_error('pgp_ecp_options', 'pgp_email_invalid', 'Invalid recipient email address.', 'error');
            $sanitized['pgp_recipient_email'] = '';
        }

        // Validate admin URL
        if (!empty($sanitized['pgp_admin_url'])) {
            if (!filter_var($sanitized['pgp_admin_url'], FILTER_VALIDATE_URL)) {
                add_settings_error('pgp_ecp_options', 'pgp_admin_url_invalid', 'Invalid admin URL. Use a valid URL (e.g., https://example.com/custom-admin) or leave blank to use the default WordPress admin URL.', 'error');
                $sanitized['pgp_admin_url'] = '';
            } else {
                // Test custom URL with a HEAD request
                $custom_admin_url = trailingslashit($sanitized['pgp_admin_url']) . 'admin-ajax.php';
                $response = wp_remote_head($custom_admin_url, ['timeout' => 5]);
                if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                    $error = is_wp_error($response) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code($response);
                    add_settings_error('pgp_ecp_options', 'pgp_admin_url_invalid', 'Custom admin URL (' . esc_html($custom_admin_url) . ') is invalid (' . esc_html($error) . '). Cleared to use default WordPress admin URL.', 'error');
                    error_log('PGP_ECP: Custom admin URL invalid during settings save (' . $error . '): ' . $custom_admin_url);
                    $sanitized['pgp_admin_url'] = '';
                }
            }
        }

        // Validate Turnstile keys only if enabled
        if ($sanitized['pgp_enable_turnstile']) {
            if (!empty($sanitized['pgp_turnstile_site_key']) && !preg_match('/^[0-9a-zA-Z._-]+$/', $sanitized['pgp_turnstile_site_key'])) {
                add_settings_error('pgp_ecp_options', 'pgp_turnstile_site_key_invalid', 'Invalid Cloudflare Turnstile site key. It should contain only alphanumeric characters, dots, hyphens, or underscores.', 'error');
                $sanitized['pgp_turnstile_site_key'] = '';
            }

            if (!empty($sanitized['pgp_turnstile_secret_key']) && !preg_match('/^[0-9a-zA-Z._-]+$/', $sanitized['pgp_turnstile_secret_key'])) {
                add_settings_error('pgp_ecp_options', 'pgp_turnstile_secret_key_invalid', 'Invalid Cloudflare Turnstile secret key. It should contain only alphanumeric characters, dots, hyphens, or underscores.', 'error');
                $sanitized['pgp_turnstile_secret_key'] = '';
            }
        }

        return $sanitized;
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: render_settings_page called');
        }
        ?>
        <div class="wrap">
            <h1>PGP Encryption for Contact Page Settings</h1>
            <form method="post" action="options.php">
                <?php
                if ($this->is_debug_enabled()) {
                    error_log('PGP_ECP: Rendering settings fields for pgp-ecp-settings');
                }
                settings_fields('pgp_ecp_options_group');
                do_settings_sections('pgp-ecp-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the public key textarea field
     */
    public function render_public_key_field() {
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: render_public_key_field called');
        }
        $options = get_option('pgp_ecp_options', []);
        $value = esc_textarea($options['pgp_public_key'] ?? '');
        ?>
        <textarea name="pgp_ecp_options[pgp_public_key]" rows="14" cols="62" style="width: 100%; max-width: 540px;"><?php echo $value; ?></textarea>
        <p class="description">Paste your armored PGP public key here (e.g., starts with "-----BEGIN PGP PUBLIC KEY BLOCK-----"). Generate one using <code>gpg --gen-key</code> and export with <code>gpg --armor --export your-email@domain.com</code>. Required for form encryption.</p>
        <?php
    }

    /**
     * Render the recipient email input field
     */
    public function render_recipient_email_field() {
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: render_recipient_email_field called');
        }
        $options = get_option('pgp_ecp_options', []);
        $value = esc_attr($options['pgp_recipient_email'] ?? '');
        ?>
        <input type="email" name="pgp_ecp_options[pgp_recipient_email]" value="<?php echo $value; ?>" />
        <p class="description">The email address where encrypted messages will be sent (e.g., your-email@domain.com).</p>
        <?php
    }

    /**
     * Render the custom admin URL input field
     */
    public function render_admin_url_field() {
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: render_admin_url_field called');
        }
        $options = get_option('pgp_ecp_options', []);
        $value = esc_attr($options['pgp_admin_url'] ?? '');
        ?>
        <input type="url" name="pgp_ecp_options[pgp_admin_url]" value="<?php echo $value; ?>" style="width: 100%; max-width: 400px;" />
        <p class="description">Enter a custom admin URL if your WordPress admin panel is not at /wp-admin (e.g., https://example.com/custom-admin). Leave blank to use the default WordPress admin URL. Invalid URLs (e.g., causing 404 errors) will be cleared automatically on save.</p>
        <?php
    }

    /**
     * Render the enable debugging logs checkbox field
     */
    public function render_enable_debugging_field() {
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: render_enable_debugging_field called');
        }
        $options = get_option('pgp_ecp_options', []);
        $checked = isset($options['pgp_enable_debugging']) && $options['pgp_enable_debugging'] ? 'checked' : '';
        ?>
        <input type="checkbox" id="pgp_enable_debugging" name="pgp_ecp_options[pgp_enable_debugging]" value="1" <?php echo $checked; ?> />
        <label for="pgp_enable_debugging">Enable Debugging Logs</label>
        <p class="description">Check to enable detailed debug logging to wp-content/debug.log (requires WP_DEBUG and WP_DEBUG_LOG enabled in wp-config.php). Uncheck for production to disable logs and improve performance.</p>
        <?php
    }

    /**
     * Render the enable Cloudflare Turnstile checkbox field
     */
    public function render_enable_turnstile_field() {
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: render_enable_turnstile_field called');
        }
        $options = get_option('pgp_ecp_options', []);
        $checked = isset($options['pgp_enable_turnstile']) && $options['pgp_enable_turnstile'] ? 'checked' : '';
        ?>
        <input type="checkbox" id="pgp_enable_turnstile" name="pgp_ecp_options[pgp_enable_turnstile]" value="1" <?php echo $checked; ?> />
        <label for="pgp_enable_turnstile">Enable Cloudflare Turnstile CAPTCHA for the contact form</label>
        <p class="description">Check to enable Turnstile CAPTCHA. Uncheck to disable CAPTCHA and allow form submissions without verification.</p>
        <script>
            jQuery(document).ready(function() {
                console.log('PGP_ECP: Inline turnstile checkbox script loaded');
                var turnstileCheckbox = jQuery("#pgp_enable_turnstile");
                var siteKeyField = jQuery("#pgp_turnstile_site_key");
                var secretKeyField = jQuery("#pgp_turnstile_secret_key");
                console.log('PGP_ECP: Checkbox initial state: ' + turnstileCheckbox.is(":checked"));
                turnstileCheckbox.on("change", function() {
                    console.log('PGP_ECP: Checkbox changed to: ' + turnstileCheckbox.is(":checked"));
                    if (turnstileCheckbox.is(":checked")) {
                        siteKeyField.prop("disabled", false);
                        secretKeyField.prop("disabled", false);
                    } else {
                        siteKeyField.prop("disabled", true);
                        secretKeyField.prop("disabled", true);
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Render the Cloudflare Turnstile site key input field
     */
    public function render_turnstile_site_key_field() {
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: render_turnstile_site_key_field called');
        }
        $options = get_option('pgp_ecp_options', []);
        $value = esc_attr($options['pgp_turnstile_site_key'] ?? '');
        $disabled = empty($options['pgp_enable_turnstile']) ? 'disabled' : '';
        ?>
        <input type="text" id="pgp_turnstile_site_key" name="pgp_ecp_options[pgp_turnstile_site_key]" value="<?php echo $value; ?>" style="width: 100%; max-width: 400px;" <?php echo $disabled; ?> />
        <p class="description">Enter your Cloudflare Turnstile site key (obtain from <a href="https://dash.cloudflare.com/turnstile" target="_blank">Cloudflare Dashboard</a>). Typically 40 characters starting with '0x4'.</p>
        <?php
    }

    /**
     * Render the Cloudflare Turnstile secret key input field
     */
    public function render_turnstile_secret_key_field() {
        if ($this->is_debug_enabled()) {
            error_log('PGP_ECP: render_turnstile_secret_key_field called');
        }
        $options = get_option('pgp_ecp_options', []);
        $value = esc_attr($options['pgp_turnstile_secret_key'] ?? '');
        $disabled = empty($options['pgp_enable_turnstile']) ? 'disabled' : '';
        ?>
        <input type="text" id="pgp_turnstile_secret_key" name="pgp_ecp_options[pgp_turnstile_secret_key]" value="<?php echo $value; ?>" style="width: 100%; max-width: 400px;" <?php echo $disabled; ?> />
        <p class="description">Enter your Cloudflare Turnstile secret key (obtain from <a href="https://dash.cloudflare.com/turnstile" target="_blank">Cloudflare Dashboard</a>). Typically 40 characters starting with '1x' or '2x'. Keep this secure.</p>
        <?php
    }
}