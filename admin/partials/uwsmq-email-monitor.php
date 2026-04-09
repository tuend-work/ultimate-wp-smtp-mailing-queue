<div class="wrap" id="uwsmq-monitor-wrap">
    <h1>Email Monitor</h1>
    
    <div class="uwsmq-monitor-meta" style="margin-bottom: 20px; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <strong>Next Cron Schedule:</strong> 
            <span class="uwsmq-cron-time" style="color: #2271b1;"><?php echo esc_html( $cron_status ); ?></span>
        </div>
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

    <table class="wp-list-table widefat fixed striped table-view-list" id="uwsmq-email-monitor-table">
        <thead>
            <tr>
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
                    <td colspan="8">No email logs found.</td>
                </tr>
            <?php else : ?>
                <?php foreach ( $items as $item ) : ?>
                    <tr>
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
                                    data-sent="<?php echo esc_attr( $item->sent_at ); ?>" 
                                    data-source="<?php echo esc_attr( $item->source ); ?>" 
                                    data-headers="<?php echo esc_attr( $item->headers ); ?>"
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
        var headers = data.headers;
        try {
            // Check if it's serialized or string
            if (headers && (headers.indexOf('a:') === 0 || headers.indexOf('s:') === 0)) {
                // For simplicity, we just show the raw string for now or a note
                // In a real app we might want to unserialize in JS
            }
        } catch(e) {}

        var msg = '--- EMAIL DETAILS ---\n' +
                  'ID: ' + data.id + "\n" +
                  'From: ' + data.from + "\n" +
                  'To: ' + data.to + "\n" +
                  'Subject: ' + data.subject + "\n" +
                  'Status: ' + data.status + "\n" +
                  'Sent At: ' + data.sent + "\n" +
                  'Source: ' + data.source + "\n\n" +
                  '--- HEADERS ---\n' + (data.headers || 'None') + "\n\n" +
                  '--- ERROR MESSAGE ---\n' + (data.error || 'None');
        alert(msg);
    });

    // Delete Log
    $('.uwsmq-delete-log').on('click', function(e){
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this log?')) return;
        var id = $(this).data('id');
        var row = $(this).closest('tr');
        $.post(uwsmq_ajax.ajax_url, {
            action: 'uwsmq_delete_log', // We'll add this handler
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

