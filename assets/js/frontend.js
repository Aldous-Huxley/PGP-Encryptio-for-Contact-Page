/* File: frontend.js */
/* Path: PGP-Encryption-for-Contact-Page/assets/js/frontend.js */

jQuery(document).ready(function($) {
    // Log script initialization
    console.log('PGP_ECP: frontend.js initialized, targeting #pgp-ecp-contact-form');

    // Ensure form exists before binding
    if ($('#pgp-ecp-contact-form').length === 0) {
        console.error('PGP_ECP: Form #pgp-ecp-contact-form not found on page');
        return;
    }

    // Use delegated event binding to handle dynamic content
    $(document).on('submit', '#pgp-ecp-contact-form', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $responseDiv = $('#pgp-ecp-response');
        $responseDiv.html(''); // Clear previous messages

        // Log form submission attempt
        console.log('PGP_ECP: Form submission triggered', {
            ajax_url: pgp_ecp_ajax.ajax_url,
            nonce: pgp_ecp_ajax.nonce,
            form_data: $form.serialize()
        });

        // Verify AJAX URL and nonce
        if (!pgp_ecp_ajax.ajax_url || !pgp_ecp_ajax.nonce) {
            console.error('PGP_ECP: AJAX configuration missing', pgp_ecp_ajax);
            $responseDiv.html('<p class="error">Form configuration error. Please contact the site administrator.</p>');
            return;
        }

        $.ajax({
            url: pgp_ecp_ajax.ajax_url,
            type: 'POST',
            data: $form.serialize() + '&action=pgp_ecp_submit_form&nonce=' + pgp_ecp_ajax.nonce,
            dataType: 'json',
            beforeSend: function() {
                console.log('PGP_ECP: Sending AJAX request to ' + pgp_ecp_ajax.ajax_url);
                $responseDiv.html('<p>Sending message...</p>');
            },
            success: function(response) {
                console.log('PGP_ECP: AJAX response received', response);
                if (response.success) {
                    $responseDiv.html('<p class="success">' + response.data.message + '</p>');
                    $form[0].reset();
                } else {
                    console.error('PGP_ECP: Server error - ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                    $responseDiv.html('<p class="error">' + (response.data && response.data.message ? response.data.message : 'An error occurred. Please try again.') + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('PGP_ECP: AJAX request failed', {
                    status: status,
                    error: error,
                    statusCode: xhr.status,
                    responseText: xhr.responseText
                });
                var errorMessage = 'An error occurred. Please try again.';
                if (xhr.status === 403) {
                    errorMessage = 'Security check failed. Please refresh and try again.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please check server logs or contact support.';
                } else if (xhr.responseText) {
                    try {
                        var parsed = JSON.parse(xhr.responseText);
                        if (parsed.data && parsed.data.message) {
                            errorMessage = parsed.data.message;
                        }
                    } catch (e) {
                        errorMessage = 'Unexpected response: ' + xhr.responseText;
                    }
                }
                $responseDiv.html('<p class="error">' + errorMessage + '</p>');
            }
        });
    });
});