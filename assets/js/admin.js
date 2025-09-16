// File: admin.js
// Path: PGP-Encryption-for-Contact-Page/assets/js/admin.js

jQuery(document).ready(function() {
    console.log('PGP_ECP: Admin JavaScript loaded');
    
    var turnstileCheckbox = jQuery("#pgp_enable_turnstile");
    var siteKeyField = jQuery("#pgp_turnstile_site_key");
    var secretKeyField = jQuery("#pgp_turnstile_secret_key");

    if (turnstileCheckbox.length === 0) {
        console.log('PGP_ECP: Error - turnstileCheckbox not found');
    }
    if (siteKeyField.length === 0) {
        console.log('PGP_ECP: Error - siteKeyField not found');
    }
    if (secretKeyField.length === 0) {
        console.log('PGP_ECP: Error - secretKeyField not found');
    }

    function toggleTurnstileFields() {
        console.log('PGP_ECP: toggleTurnstileFields called, checkbox checked: ' + turnstileCheckbox.is(":checked"));
        if (turnstileCheckbox.is(":checked")) {
            siteKeyField.prop("disabled", false);
            secretKeyField.prop("disabled", false);
        } else {
            siteKeyField.prop("disabled", true);
            secretKeyField.prop("disabled", true);
        }
    }

    toggleTurnstileFields();
    turnstileCheckbox.on("change", function() {
        console.log('PGP_ECP: Checkbox changed');
        toggleTurnstileFields();
    });
});