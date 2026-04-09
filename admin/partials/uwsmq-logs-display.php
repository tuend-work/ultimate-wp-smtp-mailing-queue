<div class="uwsmq-logs-wrap">
    <p>Success and failure logs for all emails sent through this plugin.</p>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-primary">Recipient</th>
                <th scope="col" class="manage-column">Subject</th>
                <th scope="col" class="manage-column">Status</th>
                <th scope="col" class="manage-column">Source</th>
                <th scope="col" class="manage-column">Sent At</th>
                <th scope="col" class="manage-column">Error Message</th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if ( empty( $items ) ) : ?>
                <tr><td colspan="6" style="text-align:center;">No logs found.</td></tr>
            <?php else : ?>
                <?php foreach ( $items as $item ) : ?>
                    <tr>
                        <td class="column-primary"><strong><?php echo esc_html( $item->to_email ); ?></strong></td>
                        <td><?php echo esc_html( $item->subject ); ?></td>
                        <td>
                            <span class="uwsmq-status-badge uwsmq-status-<?php echo $item->status === 'sent' ? 'sent' : 'failed'; ?>">
                                <?php echo ucfirst($item->status); ?>
                            </span>
                        </td>
                        <td><span class="uwsmq-badge"><?php echo ucfirst($item->source); ?></span></td>
                        <td><?php echo date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->sent_at) ); ?></td>
                        <td style="color:red; font-size:11px;"><?php echo esc_html( $item->error_message ); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
