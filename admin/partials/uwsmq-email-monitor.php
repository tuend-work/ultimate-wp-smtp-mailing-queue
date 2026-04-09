<div class="wrap" id="uwsmq-monitor-wrap">
    <h1>Email Monitor</h1>
    
    <div class="uwsmq-monitor-actions" style="margin-top: 10px;   margin-bottom: 10px;">
        <div>
            <button id="uwsmq-process-all-queue" class="button button-primary">Process All Queue</button>
            <span id="uwsmq-process-spinner" class="spinner" style="float: none;"></span>
        </div>
    </div>

    <?php
    $current_status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'all';
    $statuses = array(
        'all'        => 'All',
        'sent'       => 'Sent',
        'failed'     => 'Error',
        'queue'      => 'Queue',
        'processing' => 'Processing'
    );
    ?>
    
    <ul class="subsubsub">
        <?php foreach ( $statuses as $slug => $label ) : 
            $class = ( $current_status === $slug ) ? 'current' : '';
            $url = add_query_arg( array( 'tab' => 'email-monitor', 'status' => $slug ), admin_url( 'admin.php?page=ultimate-wp-smtp-mailing-queue' ) );
            ?>
            <li class="<?php echo $slug; ?>">
                <a href="<?php echo esc_url( $url ); ?>" class="<?php echo $class; ?>"><?php echo $label; ?></a> 
                <?php if ( $slug !== 'processing' ) echo '|'; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <select name="action" id="bulk-action-selector-top">
                <option value="-1">Bulk actions</option>
                <option value="bulk-send">Send selected</option>
                <option value="bulk-delete">Delete selected</option>
            </select>
            <button type="button" id="doaction" class="button action">Apply</button>
            <span id="uwsmq-bulk-spinner" class="spinner" style="float: none;"></span>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped table-view-list" id="uwsmq-email-monitor-table">
        <thead>
            <tr>
                <td id="cb" class="manage-column column-cb check-column">
                    <input id="cb-select-all-1" type="checkbox">
                </td>
                <th scope="col" class="manage-column column-id" style="width: 50px;">ID</th>
                <th scope="col" class="manage-column column-from">From</th>
                <th scope="col" class="manage-column column-to">To</th>
                <th scope="col" class="manage-column column-subject">Subject</th>
                <th scope="col" class="manage-column column-status" style="width: 100px;">Status</th>
                <th scope="col" class="manage-column column-sent_at">Sent At</th>
                <th scope="col" class="manage-column column-source">Source</th>
                <th scope="col" class="manage-column column-actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $items ) ) : ?>
                <tr>
                    <td colspan="9">No email logs found.</td>
                </tr>
            <?php else : ?>
                <?php foreach ( $items as $item ) : ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="log_ids[]" value="<?php echo esc_attr( $item->id ); ?>">
                        </th>
                        <td><?php echo esc_html( $item->id ); ?></td>
                        <td><?php echo esc_html( $item->from_email ); ?></td>
                        <td><?php echo esc_html( $item->to_email ); ?></td>
                        <td><?php echo esc_html( $item->subject ); ?></td>
                        <td>
                            <?php 
                            $status_class = 'uwsmq-status-' . esc_attr( $item->status );
                            echo '<span class="uwsmq-status-badge ' . $status_class . '">' . esc_html( ucfirst( $item->status ) ) . '</span>';
                            ?>
                        </td>
                        <td><?php echo esc_html( $item->sent_at ); ?></td>
                        <td><?php echo esc_html( $item->source ); ?></td>
                        <td>
                            <div class="uwsmq-actions">
                                <a href="#" class="uwsmq-view-log" 
                                    data-id="<?php echo esc_attr( $item->id ); ?>" 
                                    data-from="<?php echo esc_attr( $item->from_email ); ?>" 
                                    data-to="<?php echo esc_attr( $item->to_email ); ?>" 
                                    data-subject="<?php echo esc_attr( $item->subject ); ?>" 
                                    data-status="<?php echo esc_attr( $item->status ); ?>" 
                                    data-queued="<?php echo esc_attr( $item->queued_at ); ?>"
                                    data-sent="<?php echo esc_attr( $item->sent_at ); ?>" 
                                    data-source="<?php echo esc_attr( $item->source ); ?>" 
                                    data-headers="<?php echo esc_attr( $item->headers ); ?>"
                                    data-message="<?php echo esc_attr( $item->message ); ?>"
                                    data-error="<?php echo esc_attr( $item->error_message ?? '' ); ?>">View</a> |
                                <?php if ( $item->status === 'queue' || $item->status === 'failed' ) : ?>
                                    <a href="#" class="uwsmq-send-now" data-id="<?php echo esc_attr( $item->id ); ?>">Send</a> |
                                <?php endif; ?>
                                <a href="#" class="uwsmq-delete-log" data-id="<?php echo esc_attr( $item->id ); ?>" style="color: #a00;">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.tablenav { margin-bottom: 5px; }
.uwsmq-status-badge {
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}
.uwsmq-status-sent { background: #d4edda; color: #155724; }
.uwsmq-status-failed { background: #f8d7da; color: #721c24; }
.uwsmq-status-queue { background: #fff3cd; color: #856404; }
.uwsmq-status-processing { background: #cce5ff; color: #004085; }
.uwsmq-actions a { text-decoration: none; cursor: pointer; }
</style>
<script type="text/javascript">
jQuery(document).ready(function($){
    // View Log
    $('.uwsmq-view-log').on('click', function(e){
        e.preventDefault();
        var data = $(this).data();
        
        var delayMsg = 'N/A';
        if (data.queued && data.sent && data.status === 'sent') {
            var qDate = new Date(data.queued);
            var sDate = new Date(data.sent);
            var diff = Math.abs(sDate - qDate) / 1000; // seconds
            if (diff < 60) {
                delayMsg = diff + ' seconds';
            } else {
                delayMsg = Math.floor(diff / 60) + ' minutes ' + (diff % 60) + ' seconds';
            }
        }

        var msg = '--- EMAIL DETAILS ---\n' +
                  'ID: ' + data.id + "\n" +
                  'From: ' + data.from + "\n" +
                  'To: ' + data.to + "\n" +
                  'Subject: ' + data.subject + "\n" +
                  'Status: ' + data.status + "\n" +
                  'Queued At: ' + (data.queued || 'N/A') + "\n" +
                  'Sent At: ' + (data.sent || 'N/A') + "\n" +
                  'Delivery Delay: ' + delayMsg + "\n" +
                  'Source: ' + data.source + "\n\n" +
                  '--- MESSAGE CONTENT ---\n' + (data.message || 'None') + "\n\n" +
                  '--- HEADERS ---\n' + (data.headers || 'None') + "\n\n" +
                  '--- ERROR MESSAGE ---\n' + (data.error || 'None');
        alert(msg);
    });

    // Select All
    $('#cb-select-all-1').on('change', function(){
        $('input[name="log_ids[]"]').prop('checked', $(this).prop('checked'));
    });

    // Send Now (Individual)
    $(document).on('click', '.uwsmq-send-now', function(e){
        e.preventDefault();
        var btn = $(this);
        var id = btn.data('id');
        btn.text('Sending...');
        
        $.post(uwsmq_ajax.ajax_url, {
            action: 'uwsmq_send_now',
            nonce: uwsmq_ajax.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
                btn.text('Send');
            }
        });
    });

    // Refresh Cron
    $('#uwsmq-refresh-cron').on('click', function(){
        var btn = $(this);
        btn.prop('disabled', true).text('...');
        $.post(uwsmq_ajax.ajax_url, {
            action: 'uwsmq_refresh_cron',
            nonce: uwsmq_ajax.nonce
        }, function(response) {
            location.reload();
        });
    });

    // Bulk Actions
    $('#doaction').on('click', function(){
        var action = $('#bulk-action-selector-top').val();
        if (action === '-1') return;
        
        var selectedids = [];
        $('input[name="log_ids[]"]:checked').each(function(){
            selectedids.push($(this).val());
        });
        
        if (selectedids.length === 0) {
            alert('Please select at least one item.');
            return;
        }
        
        if (!confirm('Are you sure you want to perform this bulk action?')) return;
        
        var spinner = $('#uwsmq-bulk-spinner');
        spinner.addClass('is-active');
        $(this).prop('disabled', true);
        
        $.post(uwsmq_ajax.ajax_url, {
            action: 'uwsmq_bulk_action',
            nonce: uwsmq_ajax.nonce,
            bulk_action: action,
            ids: selectedids
        }, function(response) {
            spinner.removeClass('is-active');
            $('#doaction').prop('disabled', false);
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });

    // Delete Log
    $('.uwsmq-delete-log').on('click', function(e){
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this log?')) return;
        var id = $(this).data('id');
        var row = $(this).closest('tr');
        $.post(uwsmq_ajax.ajax_url, {
            action: 'uwsmq_delete_log',
            nonce: uwsmq_ajax.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                row.fadeOut();
            }
        });
    });

    // Process All Queue
    $('#uwsmq-process-all-queue').on('click', function(){
        var btn = $(this);
        var spinner = $('#uwsmq-process-spinner');
        btn.prop('disabled', true);
        spinner.addClass('is-active');

        function processBatch() {
            $.post(uwsmq_ajax.ajax_url, {
                action: 'uwsmq_process_queue',
                nonce: uwsmq_ajax.nonce
            }, function(response) {
                if (response.success) {
                    // Check if we should continue? 
                    // For now, let's just refresh after one batch or show success
                    alert('Batch processed successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                    btn.prop('disabled', false);
                    spinner.removeClass('is-active');
                }
            });
        }
        processBatch();
    });
});
</script>

