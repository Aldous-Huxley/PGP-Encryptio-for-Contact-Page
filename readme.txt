=== PGP Encryption for Contact Page ===
Contributors: Robert Stanghellini
Author URI: https://blockstreamtechnologies.llc
Tags: pgp, encryption, contact form, security, email
Requires at least: Wordpress 6.0
Tested up to Wordpress: 6.8.2
Requires PHP: 8.2
Tested up to: 8.4
Stable tag: 1.0.0
License: Apache License 2.0
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds PGP encryption to a built-in WordPress contact form, allowing secure email communication with public key encryption.

== Description ==

This plugin enables PGP encryption for a built in contact form on your WordPress site, ensuring that messages sent via the form are encrypted using your public key before being emailed to you. It's ideal for maintaining privacy and security in user communications.

Key Features:
* Simple shortcode-based contact form: `[pgp_contact_form]`
* Tested with Contact Form 7 (Unsupported)
* Display your public key: `[pgp_public_key]`
* Admin settings for configuring your PGP public key and recipient email
* AJAX-powered form submission for seamless user experience
* Responsive, styled form that integrates with your theme
* Uses the secure GnuPG PHP extension for encryption (requires server support)

Once installed, configure the plugin in Settings > PGP Encryption, then add the shortcodes to any page or post.

== Installation ==

1. Upload the `PGP-Encryption-for-Contact-Page` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Settings > PGP Encryption and paste your armored PGP public key (starting with "BEGIN PGP PUBLIC KEY BLOCK") and set the recipient email.
4. Add the shortcode `[pgp_contact_form]` to a page or post to display the encrypted contact form.
5. Optionally, add `[pgp_public_key]` to show your public key for users.

Note: Your server must have the GnuPG PHP extension enabled. Check with your host if encryption fails.

== Frequently Asked Questions ==

= What are the server requirements for this plugin to run?

This plugin requires the following PHP extension to be enabled on your webserver: gnupg, json, mbstring, openssl.

= How do I impliment this contact form? 1. Setup the plugin in your Wordpress admin panel. 2. Use the shortcode: [pgp_contact_form] to embed a responsive, styled form on any page or post.

= Does this plugin work with any contact form plugin? =

Yes, but unsupported. This is a standalone contact form. It worked with Contact Form 7 providing encryption for a sent form on Contact 7. For other custom integration, advanced users can extend the `PGP_ECP_Frontend` class.

= What if the GnuPG extension is not available? =

The plugin will log errors and disable encryption. Contact your hosting provider to enable the `gnupg` PHP extension.

= How do I get a PGP public key? =

Generate one using tools like GnuPG (gpg --gen-key) or online services like Protonmail, then export it in armored format, paste your public key in the plugins admin page in Wordpress.

= Is the form secure? =

Yes, it uses WordPress nonces, sanitization, and PGP encryption. Always keep WordPress updated.

== Screenshots ==

1. Admin settings page for key configuration.
2. Frontend contact form in action.

== Changelog ==

= 1.0.0 =
* Initial release with full PGP encryption support for contact forms.

== Upgrade Notice ==

= 1.0.0 =
Initial version. No upgrades needed.
