<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
    <h1>Email Monitor</h1>
    
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
                <th scope="col" class="manage-column column-id">ID</th>
                <th scope="col" class="manage-column column-to">To</th>
                <th scope="col" class="manage-column column-subject">Subject</th>
                <th scope="col" class="manage-column column-status">Status</th>
                <th scope="col" class="manage-column column-sent_at">Sent At</th>
                <th scope="col" class="manage-column column-source">Source</th>
                <th scope="col" class="manage-column column-view">View</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $items ) ) : ?>
                <tr>
                    <td colspan="7">No email logs found.</td>
                </tr>
            <?php else : ?>
                <?php foreach ( $items as $item ) : ?>
                    <tr>
                        <td><?php echo esc_html( $item->id ); ?></td>
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
                            <button class="button button-small uwsmq-view-log" 
                                data-id="<?php echo esc_attr( $item->id ); ?>" 
                                data-to="<?php echo esc_attr( $item->to_email ); ?>" 
                                data-subject="<?php echo esc_attr( $item->subject ); ?>" 
                                data-status="<?php echo esc_attr( $item->status ); ?>" 
                                data-sent="<?php echo esc_attr( $item->sent_at ); ?>" 
                                data-source="<?php echo esc_attr( $item->source ); ?>" 
                                data-error="<?php echo esc_attr( $item->error_message ?? '' ); ?>">
                                View
                            </button>
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
</style>
<script type="text/javascript">
jQuery(document).ready(function($){
    $('.uwsmq-view-log').on('click', function(){
        var data = $(this).data();
        var msg = 'ID: ' + data.id + "\n" +
                  'To: ' + data.to + "\n" +
                  'Subject: ' + data.subject + "\n" +
                  'Status: ' + data.status + "\n" +
                  'Sent At: ' + data.sent + "\n" +
                  'Source: ' + data.source + "\n" +
                  'Error Message: ' + (data.error || 'None');
        alert(msg);
    });
});
</script>
