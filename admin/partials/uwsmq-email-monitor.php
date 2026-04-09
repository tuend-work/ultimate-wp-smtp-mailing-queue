<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h2>Email Monitor</h2>
    <table class="wp-list-table widefat fixed striped table-view-list" id="uwsmq-email-monitor-table">
        <thead>
            <tr>
                <th scope="col" id="id" class="manage-column column-id"><a href="?page=ultimate-wp-smtp-mailing-queue&tab=email-monitor&status=<?php echo esc_attr( $_GET['status'] ?? '' ); ?>">ID</a></th>
                <th scope="col" id="to" class="manage-column column-to"><a href="?page=ultimate-wp-smtp-mailing-queue&tab=email-monitor&status=<?php echo esc_attr( $_GET['status'] ?? '' ); ?>">To</a></th>
                <th scope="col" id="subject" class="manage-column column-subject"><a href="?page=ultimate-wp-smtp-mailing-queue&tab=email-monitor&status=<?php echo esc_attr( $_GET['status'] ?? '' ); ?>">Subject</a></th>
                <th scope="col" id="status" class="manage-column column-status"><a href="?page=ultimate-wp-smtp-mailing-queue&tab=email-monitor&status=<?php echo esc_attr( $_GET['status'] ?? '' ); ?>">Status</a></th>
                <th scope="col" id="sent_at" class="manage-column column-sent_at"><a href="?page=ultimate-wp-smtp-mailing-queue&tab=email-monitor&status=<?php echo esc_attr( $_GET['status'] ?? '' ); ?>">Sent At</a></th>
                <th scope="col" id="source" class="manage-column column-source"><a href="?page=ultimate-wp-smtp-mailing-queue&tab=email-monitor&status=<?php echo esc_attr( $_GET['status'] ?? '' ); ?>">Source</a></th>
                <th scope="col" id="view" class="manage-column column-view">View</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $items as $item ) : ?>
                <tr>
                    <td><?php echo esc_html( $item->id ); ?></td>
                    <td><?php echo esc_html( $item->to_email ); ?></td>
                    <td><?php echo esc_html( $item->subject ); ?></td>
                    <td><?php echo esc_html( $item->status ); ?></td>
                    <td><?php echo esc_html( $item->sent_at ); ?></td>
                    <td><?php echo esc_html( $item->source ); ?></td>
                    <td><button class="button button-primary uwsmq-view-log" data-id="<?php echo esc_attr( $item->id ); ?>" data-to="<?php echo esc_attr( $item->to_email ); ?>" data-subject="<?php echo esc_attr( $item->subject ); ?>" data-status="<?php echo esc_attr( $item->status ); ?>" data-sent="<?php echo esc_attr( $item->sent_at ); ?>" data-source="<?php echo esc_attr( $item->source ); ?>" data-error="<?php echo esc_attr( $item->error_message ?? '' ); ?>">View</button></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
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
