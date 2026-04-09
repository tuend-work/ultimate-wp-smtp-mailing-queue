jQuery(document).ready(function($) {
    
    // Test SMTP Modal
    $('#uwsmq-test-btn').on('click', function() {
        $('#uwsmq-test-modal').css('display', 'flex');
    });

    $('#uwsmq-cancel-test').on('click', function() {
        $('#uwsmq-test-modal').hide();
    });

    $('#uwsmq-send-test').on('click', function() {
        const email = $('#uwsmq-test-email').val();
        if (!email) {
            alert('Please enter a recipient email.');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).text('Sending...');

        $.ajax({
            url: uwsmq_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'uwsmq_test_smtp',
                nonce: uwsmq_ajax.nonce,
                test_email: email
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#uwsmq-test-modal').hide();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('An error occurred while sending test email.');
            },
            complete: function() {
                btn.prop('disabled', false).text('Send Test');
            }
        });
    });

    // Process Queue Now
    $('#uwsmq-process-now').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Processing...');

        $.ajax({
            url: uwsmq_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'uwsmq_process_queue',
                nonce: uwsmq_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('An error occurred while processing queue.');
            },
            complete: function() {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-controls-play"></span> Process Queue Now');
            }
        });
    });

    // Delete item
    $('.uwsmq-delete-btn').on('click', function() {
        if (!confirm('Are you sure you want to delete this item?')) return;

        const id = $(this).data('id');
        const row = $('#uwsmq-item-' + id);

        $.ajax({
            url: uwsmq_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'uwsmq_delete_item',
                nonce: uwsmq_ajax.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut();
                }
            }
        });
    });
});
