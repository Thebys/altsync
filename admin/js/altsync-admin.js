(function( $ ) {
	'use strict';

	$(function() {

        // Use event delegation for the button, as it might be added dynamically (e.g., in media modal)
        $(document).on('click', '.altsync-sync-button', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $spinner = $button.next('.altsync-status.spinner');
            var $messageContainer = $button.siblings('.altsync-message'); // Find the message container relative to the button
            var attachmentId = $button.data('attachment-id');

            // Prevent multiple clicks
            if ($button.is('.disabled') || $button.prop('disabled')) {
                return;
            }

            // Simple confirmation (optional, can be removed or made more sophisticated)
            // if (!confirm(altsync_ajax.sync_confirm_text)) {
            //     return;
            // }

            // Disable button, show spinner, clear previous messages
            $button.prop('disabled', true).addClass('disabled');
            $spinner.css({'display': 'inline-block', 'visibility': 'visible'}); // Make sure spinner is visible
            $messageContainer.text('').removeClass('notice-success notice-warning notice-error');
            var originalButtonText = $button.text();
            $button.text(altsync_ajax.syncing_text);

            // Perform AJAX request
            $.ajax({
                url: altsync_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'altsync_sync_alt_text',
                    nonce: altsync_ajax.nonce,
                    attachment_id: attachmentId
                },
                success: function(response) {
                    if (response.success) {
                        var message = response.data.message || altsync_ajax.sync_no_updates; // Use specific message or default no-update message
                        var count = response.data.count || 0;
                        if (count > 0) {
                            // Inject count into the success message if placeholder exists
                            message = altsync_ajax.sync_success_text.replace('%d', count);
                        }
                        $messageContainer.text(message).addClass('notice-success').css('display', 'block'); // Show message
                    } else {
                        var errorMessage = response.data.message || altsync_ajax.sync_error_text;
                        $messageContainer.text(errorMessage).addClass('notice-error').css('display', 'block'); // Show error message
                         console.error('AltSync Error:', response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $messageContainer.text(altsync_ajax.sync_error_text).addClass('notice-error').css('display', 'block');
                    console.error('AltSync AJAX Error:', textStatus, errorThrown, jqXHR.responseText);
                },
                complete: function() {
                    // Re-enable button, hide spinner
                    $button.prop('disabled', false).removeClass('disabled');
                    $spinner.css('display', 'none');
                    $button.text(originalButtonText); // Restore original button text

                    // Optional: Hide the message after a few seconds
                    setTimeout(function() {
                        $messageContainer.fadeOut(function() {
                             $(this).text('').removeClass('notice-success notice-warning notice-error').css('display', '' );
                        });
                    }, 7000); // Hide after 7 seconds
                }
            });
        });

        // Potential: Add observer for media modal if needed for dynamic button binding

	});

})( jQuery ); 