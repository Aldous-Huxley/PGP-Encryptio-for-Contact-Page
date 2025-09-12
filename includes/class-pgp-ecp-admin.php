<?php
// File: class-pgp-ecp-admin.php
// Path: PGP-Encryption-for-Contact-Page/includes/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PGP_ECP_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Add the settings page to the admin menu
     */
    public function add_settings_page() {
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
        register_setting('pgp_ecp_options_group', 'pgp_ecp_options', [$this, 'sanitize_options']);

        add_settings_section(
            'pgp_ecp_main_section',
            'Main Settings',
            function() {
                echo '<p>Configure the settings below to enable PGP encryption for your contact form.</p>';
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
    }

    /**
     * Sanitize and validate options
     * @param array $input Input data
     * @return array Sanitized data
     */
    public function sanitize_options($input) {
        $sanitized = [];
        $sanitized['pgp_public_key'] = sanitize_textarea_field($input['pgp_public_key'] ?? '');
        $sanitized['pgp_recipient_email'] = sanitize_email($input['pgp_recipient_email'] ?? '');
        $sanitized['pgp_admin_url'] = esc_url_raw($input['pgp_admin_url'] ?? '');

        // Validate public key
        if (!empty($sanitized['pgp_public_key'])) {
            if (strpos($sanitized['pgp_public_key'], 'BEGIN PGP PUBLIC KEY BLOCK') === false) {
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
                    error_log('PGP Key Validation Error: ' . $e->getMessage());
                    $sanitized['pgp_public_key'] = '';
                }
            }
        } else {
            add_settings_error('pgp_ecp_options', 'pgp_key_empty', 'PGP public key is required for encryption.', 'warning');
        }

        // Validate email
        if (!empty($sanitized['pgp_recipient_email']) && !is_email($sanitized['pgp_recipient_email'])) {
            add_settings_error('pgp_ecp_options', 'pgp_email_invalid', 'Invalid recipient email address.', 'error');
            $sanitized['pgp_recipient_email'] = '';
        }

        // Validate admin URL
        if (!empty($sanitized['pgp_admin_url']) && !filter_var($sanitized['pgp_admin_url'], FILTER_VALIDATE_URL)) {
            add_settings_error('pgp_ecp_options', 'pgp_admin_url_invalid', 'Invalid admin URL. Use a full URL (e.g., https://example.com/custom-admin).', 'error');
            $sanitized['pgp_admin_url'] = '';
        }

        return $sanitized;
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>PGP Encryption for Contact Page Settings</h1>
            <form method="post" action="options.php">
                <?php
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
        $options = get_option('pgp_ecp_options', []);
        $value = $options['pgp_public_key'] ?? '';
        ?>
        <textarea name="pgp_ecp_options[pgp_public_key]" rows="10" cols="50"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">Paste your armored PGP public key here (e.g., starts with "-----BEGIN PGP PUBLIC KEY BLOCK-----"). Generate one using <code>gpg --gen-key</code> and export with <code>gpg --armor --export your-email@domain.com</code>.</p>
        <?php
    }

    /**
     * Render the recipient email input field
     */
    public function render_recipient_email_field() {
        $options = get_option('pgp_ecp_options', []);
        $value = $options['pgp_recipient_email'] ?? '';
        ?>
        <input type="email" name="pgp_ecp_options[pgp_recipient_email]" value="<?php echo esc_attr($value); ?>" />
        <p class="description">The email address where encrypted messages will be sent (e.g., your-email@domain.com).</p>
        <?php
    }

    /**
     * Render the custom admin URL input field
     */
    public function render_admin_url_field() {
        $options = get_option('pgp_ecp_options', []);
        $value = $options['pgp_admin_url'] ?? '';
        ?>
        <input type="url" name="pgp_ecp_options[pgp_admin_url]" value="<?php echo esc_attr($value); ?>" style="width: 100%; max-width: 400px;" />
        <p class="description">Enter the custom admin URL if your WordPress admin panel is not at /wp-admin (e.g., https://example.com/custom-admin). Leave blank to use the default admin URL.</p>
        <?php
    }
}
?>
