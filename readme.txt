=== PGP Encryption for Contact Page ===
Contributors: Robert Stanghellini
Author URI: https://blockstreamtechnologies.llc
Tags: pgp, encryption, contact form, security, email, turnstile, captcha
Requires at least: 6.0
Tested up to: 6.8.2
Requires PHP: 8.2
Tested up to: 8.4
Stable tag: 2.0.0
License: MIT License
License URI: https://opensource.org/licenses/MIT

Adds PGP encryption and optional Cloudflare Turnstile CAPTCHA to a built-in WordPress contact form, ensuring secure and spam-protected email communication.

== Description ==
This plugin enables PGP encryption for a built-in contact form on your WordPress site, ensuring that messages are encrypted using your public key before being emailed. It now includes optional Cloudflare Turnstile CAPTCHA to prevent spam, along with debugging controls for troubleshooting. Ideal for maintaining privacy and security in user communications.

**Key Features:**
* Simple shortcode-based contact form: `[pgp_contact_form]`
* Display your public key: `[pgp_public_key]`
* Admin settings for configuring PGP public key, recipient email, and optional Cloudflare Turnstile
* Enable/disable Turnstile CAPTCHA via a checkbox in Settings > PGP Encryption
* Enable/disable detailed debugging logs (requires `WP_DEBUG` and `WP_DEBUG_LOG` enabled in `wp-config.php`)
* Optional custom admin URL for WordPress admin login obfuscation
* AJAX-powered form submission for seamless user experience
* Responsive, styled form that integrates with your theme
* Uses the secure GnuPG PHP extension for encryption (requires server support)

Once installed, configure the plugin in **Settings > PGP Encryption**, then add the shortcodes to any page or post.

== Installation ==
1. Upload the `PGP-Encryption-for-Contact-Page` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings > PGP Encryption** and:
   - Paste your armored PGP public key (starting with "-----BEGIN PGP PUBLIC KEY BLOCK-----").
   - Set the recipient email.
   - Optionally enable Cloudflare Turnstile and enter your Site Key and Secret Key (obtain from [Cloudflare Dashboard](https://dash.cloudflare.com/turnstile)).
   - Optionally enable debugging logs for troubleshooting (requires `WP_DEBUG` and `WP_DEBUG_LOG` in `wp-config.php`).
4. Add the shortcode `[pgp_contact_form]` to a page or post to display the encrypted contact form.
5. Optionally, add `[pgp_public_key]` to show your public key for users.

**Note:** Your server must have the GnuPG PHP extension enabled. Check with your host if encryption fails.

== Frequently Asked Questions ==

= What are the server requirements for this plugin to run? =
This plugin requires the following PHP extensions to be enabled on your webserver: `gnupg`, `json`, `mbstring`, `openssl`. Additionally, for debugging, `WP_DEBUG` and `WP_DEBUG_LOG` must be enabled in `wp-config.php`.

= How do I implement this contact form? =
1. Configure the plugin in **Settings > PGP Encryption** in your WordPress admin panel.
2. Use the shortcode `[pgp_contact_form]` to embed a responsive, styled form on any page or post.

= How do I enable Cloudflare Turnstile CAPTCHA? =
1. Go to **Settings > PGP Encryption**.
2. Check the **Enable Cloudflare Turnstile** checkbox.
3. Enter your Site Key and Secret Key from the [Cloudflare Dashboard](https://dash.cloudflare.com/turnstile).
4. Save settings. The CAPTCHA will appear on the `[pgp_contact_form]` shortcode.

= Does this plugin work with other contact form plugins? =
Maybe, but unsupported. This is a standalone contact form. It has been tested with Contact Form 7 for encryption but is not guaranteed to work with other plugins. Advanced users can extend the `PGP_ECP_Frontend` class for custom integrations.

= What if the GnuPG extension is not available? =
The plugin will log errors and disable encryption. Contact your hosting provider to enable the `gnupg` PHP extension.

= How do I get a PGP public key? =
Generate one using tools like GnuPG (`gpg --gen-key`) or services like ProtonMail, then export it in armored format (`gpg --armor --export your-email@domain.com`) and paste it into the plugin’s admin settings.

= How do I enable debugging logs? =
1. Ensure `define('WP_DEBUG', true);` and `define('WP_DEBUG_LOG', true);` are set in `wp-config.php`.
2. Go to **Settings > PGP Encryption** and check **Enable Debugging Logs**.
3. Logs will appear in `wp-content/debug.log` and the browser console for frontend actions.

= Is the form secure? =
Yes, it uses WordPress nonces, sanitization, PGP encryption, and optional Cloudflare Turnstile CAPTCHA. Always keep WordPress and plugins updated for maximum security.

== Screenshots ==
1. Admin settings page for configuring PGP key, Turnstile, and debugging.
2. Frontend contact form with optional Cloudflare Turnstile CAPTCHA.

== Changelog ==
= 2.0.0 =
* Added Cloudflare Turnstile CAPTCHA integration with enable/disable checkbox.
* Added debugging control with **Enable Debugging Logs** checkbox.
* Updated version to 2.0.0.
* Improved form submission reliability and error handling.

= 1.0.0 =
* Initial release with PGP encryption support for contact forms.

== Upgrade Notice ==
= 2.0.0 =
This update adds Cloudflare Turnstile CAPTCHA and debugging controls. Configure these in **Settings > PGP Encryption**. Ensure your server supports the GnuPG PHP extension and obtain Turnstile keys from the Cloudflare Dashboard if enabling CAPTCHA.

= 1.0.0 =
Initial version. No upgrades needed.