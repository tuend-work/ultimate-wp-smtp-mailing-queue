<div class="uwsmq-settings-wrap">
    <form method="post" action="">
        <?php wp_nonce_field( 'uwsmq_save_settings_action', 'uwsmq_save_settings_nonce' ); ?>
        
        <h3>Queue & CRON Settings</h3>
        <p>Configure how emails are queued and processed by the system.</p>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="enable_queue">Enable Queue</label></th>
                <td>
                    <input name="enable_queue" type="checkbox" id="enable_queue" value="yes" <?php checked( $settings['enable_queue'], 'yes' ); ?>>
                    <label for="enable_queue">Defer email sending to background queue</label>
                    <p class="description">When enabled, WordPress will not send emails immediately. Instead, they will be saved to the database and sent in chunks via WP-Cron.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="batch_size">Batch Size</label></th>
                <td>
                    <input name="batch_size" type="number" id="batch_size" value="<?php echo esc_attr( $settings['batch_size'] ); ?>" class="small-text">
                    <p class="description">Number of emails to send in each interval.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="interval">Process Interval</label></th>
                <td>
                    <input name="interval" type="number" id="interval" value="<?php echo esc_attr( $settings['interval'] ); ?>" class="regular-text">
                    <p class="description">Interval in seconds between queue processing. (Standard is 300 for 5 minutes).</p>
                </td>
            </tr>
            <tr>
                <th scope="row">CRON Trigger Link</th>
                <td>
                    <code><?php echo site_url('/?uwsmq_process=1'); ?></code>
                    <p class="description">You can call this URL to trigger queue processing externally (e.g., via a system CRON job).</p>
                </td>
            </tr>
        </table>

        <?php submit_button( 'Save Advanced Settings', 'primary', 'uwsmq_save_settings' ); ?>
    </form>
</div>
