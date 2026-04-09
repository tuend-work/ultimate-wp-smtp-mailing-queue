jQuery(document).ready(function($) {
    
    // Test SMTP Modal
    $('#uwsmq-test-btn').on('click', function() {
        $('#uwsmq-test-modal').css('display', 'flex');
    });

    $('#uwsmq-cancel-test').on('click', function() {
        $('#uwsmq-test-modal').hide();
    });

    $('#uwsmq-send-test, #uwsmq-send-test-full').on('click', function() {
        const isFull = $(this).attr('id') === 'uwsmq-send-test-full';
        const email = isFull ? $('#test_to').val() : $('#uwsmq-test-email').val();
        
        if (!email) {
            alert('Please enter a recipient email.');
            return;
        }

        const btn = $(this);
        const originalText = btn.text();
        btn.prop('disabled', true).text('Sending...');

        const data = {
            action: 'uwsmq_test_smtp',
            nonce: uwsmq_ajax.nonce,
            test_email: email
        };

        if (isFull) {
            data.test_cc = $('#test_cc').val();
            data.test_bcc = $('#test_bcc').val();
            data.test_subject = $('#test_subject').val();
            data.test_message = $('#test_message').val();
            data.test_direct = $('#test_direct').is(':checked');
        }

        $.ajax({
            url: uwsmq_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    if (!isFull) $('#uwsmq-test-modal').hide();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('An error occurred.');
            },
            complete: function() {
                btn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Process Queue Now (Handle both locations)
    $('#uwsmq-process-now, #uwsmq-process-now-btn').on('click', function() {
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
