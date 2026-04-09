<div class="uwsmq-settings-wrap">
    <div class="uwsmq-cron-info" style="margin-bottom: 20px; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
        <div style="line-height: 26px;">
            <strong><?php esc_html_e( 'Cron Status:', 'ultimate-wp-smtp-mailing-queue' ); ?></strong> 
            <span class="uwsmq-cron-time" style="color: #2271b1;"><?php echo esc_html( $cron_status ); ?></span>
            <button id="uwsmq-refresh-cron" class="button" title="<?php esc_attr_e( 'Reschedule Cron', 'ultimate-wp-smtp-mailing-queue' ); ?>" style="padding: 0 5px; margin-left: 5px; height: 26px; line-height: 24px;">🔄</button>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#uwsmq-refresh-cron').on('click', function(e){
            e.preventDefault();
            var btn = $(this);
            btn.prop('disabled', true).text('...');
            $.post(uwsmq_ajax.ajax_url, {
                action: 'uwsmq_refresh_cron',
                nonce: uwsmq_ajax.nonce
            }, function(response) {
                location.reload();
            });
        });
    });
    </script>

    <form method="post" action="">
        <?php wp_nonce_field( 'uwsmq_save_settings_action', 'uwsmq_save_settings_nonce' ); ?>
        
        <h3><?php esc_html_e( 'Queue & CRON Settings', 'ultimate-wp-smtp-mailing-queue' ); ?></h3>
        <p><?php esc_html_e( 'Configure how emails are queued and processed by the system.', 'ultimate-wp-smtp-mailing-queue' ); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="enable_queue"><?php esc_html_e( 'Enable Queue', 'ultimate-wp-smtp-mailing-queue' ); ?></label></th>
                <td>
                    <input name="enable_queue" type="checkbox" id="enable_queue" value="yes" <?php checked( $settings['enable_queue'], 'yes' ); ?>>
                    <label for="enable_queue"><?php esc_html_e( 'Defer email sending to background queue', 'ultimate-wp-smtp-mailing-queue' ); ?></label>
                    <p class="description"><?php esc_html_e( 'When enabled, WordPress will not send emails immediately. Instead, they will be saved to the database and sent in chunks via WP-Cron.', 'ultimate-wp-smtp-mailing-queue' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="batch_size"><?php esc_html_e( 'Batch Size', 'ultimate-wp-smtp-mailing-queue' ); ?></label></th>
                <td>
                    <input name="batch_size" type="number" id="batch_size" value="<?php echo esc_attr( $settings['batch_size'] ); ?>" class="small-text">
                    <p class="description"><?php esc_html_e( 'Number of emails to send in each interval.', 'ultimate-wp-smtp-mailing-queue' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="log_limit"><?php esc_html_e( 'Log Limit', 'ultimate-wp-smtp-mailing-queue' ); ?></label></th>
                <td>
                    <input name="log_limit" type="number" id="log_limit" value="<?php echo esc_attr( $settings['log_limit'] ); ?>" class="small-text" min="0">
                    <p class="description"><?php esc_html_e( 'Maximum number of log entries to retain. Older logs will be deleted automatically.', 'ultimate-wp-smtp-mailing-queue' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="secret_key"><?php esc_html_e( 'Secret Key', 'ultimate-wp-smtp-mailing-queue' ); ?></label></th>
                <td>
                    <input name="secret_key" type="text" id="secret_key" value="<?php echo esc_attr( $settings['secret_key'] ); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e( 'This key is needed to start processing the queue manually or via cronjob.', 'ultimate-wp-smtp-mailing-queue' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Use External Cron?', 'ultimate-wp-smtp-mailing-queue' ); ?></th>
                <td>
                    <label>
                        <input name="dont_use_wpcron" type="checkbox" id="dont_use_wpcron" value="yes" <?php checked( $settings['dont_use_wpcron'], 'yes' ); ?>>
                        <?php esc_html_e( 'Setup this cronjob to VPS / Hosting if WP_CRON is disabled.', 'ultimate-wp-smtp-mailing-queue' ); ?>
                    </label>
                    <?php 
                    $cron_url = add_query_arg( array(
                        'smqProcessQueue' => '',
                        'key' => $settings['secret_key']
                    ), home_url( '/' ) );
                    ?>
                    <p class="description"><code>* * * * * wget -q -O - "<?php echo esc_url( $cron_url ); ?>" >/dev/null 2>&1</code></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="interval"><?php esc_html_e( 'Cron Interval (seconds)', 'ultimate-wp-smtp-mailing-queue' ); ?></label></th>
                <td>
                    <input name="interval" type="number" id="interval" value="<?php echo esc_attr( $settings['interval'] ); ?>" class="small-text" min="60">
                    <p class="description"><?php esc_html_e( 'The interval in seconds to process the queue when using wp_cron. Default is 300 (5 minutes).', 'ultimate-wp-smtp-mailing-queue' ); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button( __( 'Save Advanced Settings', 'ultimate-wp-smtp-mailing-queue' ), 'primary', 'uwsmq_save_settings' ); ?>
    </form>
</div>
