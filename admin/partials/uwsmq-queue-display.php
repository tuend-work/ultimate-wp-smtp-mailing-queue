<div class="uwsmq-queue-wrap">
    <h3>Supervisors</h3>
    <ul class="subsubsub">
        <li><a href="?page=ultimate-wp-smtp-mailing-queue&tab=queue&status=pending" class="<?php echo $status_filter === 'pending' ? 'current' : ''; ?>">Pending</a> |</li>
        <li><a href="?page=ultimate-wp-smtp-mailing-queue&tab=queue&status=sent" class="<?php echo $status_filter === 'sent' ? 'current' : ''; ?>">Sent</a> |</li>
        <li><a href="?page=ultimate-wp-smtp-mailing-queue&tab=queue&status=failed" class="<?php echo $status_filter === 'failed' ? 'current' : ''; ?>">Errors</a></li>
    </ul>

    <div class="tablenav top">
        <div class="alignleft actions">
            <button id="uwsmq-process-now" class="button button-secondary">Process Queue Now</button>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox"></td>
                <th scope="col" class="manage-column column-primary">Recipient</th>
                <th scope="col" class="manage-column">Subject</th>
                <th scope="col" class="manage-column">Attempts</th>
                <th scope="col" class="manage-column">Created</th>
                <th scope="col" class="manage-column">Sent</th>
                <th scope="col" class="manage-column">Status</th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if ( empty( $items ) ) : ?>
                <tr><td colspan="7" style="text-align:center;">No items found.</td></tr>
            <?php else : ?>
                <?php foreach ( $items as $item ) : ?>
                    <tr id="uwsmq-item-<?php echo $item->id; ?>">
                        <th scope="row" class="check-column"><input type="checkbox" name="item[]" value="<?php echo $item->id; ?>"></th>
                        <td class="column-primary">
                            <strong><?php echo esc_html( $item->to_email ); ?></strong>
                            <div class="row-actions">
                                <span class="delete"><a href="#" class="uwsmq-delete-btn" data-id="<?php echo $item->id; ?>" style="color:red;">Delete</a></span>
                            </div>
                        </td>
                        <td><?php echo esc_html( $item->subject ); ?></td>
                        <td><?php echo $item->attempts; ?></td>
                        <td><?php echo date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->created_at) ); ?></td>
                        <td><?php echo $item->sent_at ? date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->sent_at) ) : '-'; ?></td>
                        <td><span class="uwsmq-status-badge uwsmq-status-<?php echo $item->status; ?>"><?php echo ucfirst($item->status); ?></span></td>
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
        font-weight: bold;
        text-transform: uppercase;
    }
    .uwsmq-status-pending { background: #fff8e5; color: #856404; border: 1px solid #ffeeba; }
    .uwsmq-status-sent { background: #ecfaf0; color: #155724; border: 1px solid #c3e6cb; }
    .uwsmq-status-failed { background: #fdf2f2; color: #721c24; border: 1px solid #f5c6cb; }
</style>
