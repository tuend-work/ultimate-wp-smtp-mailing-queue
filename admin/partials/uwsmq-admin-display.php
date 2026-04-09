<div class="uwsmq-admin-wrap">
    <div class="uwsmq-header">
        <h1>Ultimate WP SMTP Mailing Queue</h1>
        <div class="uwsmq-actions">
            <button id="uwsmq-test-btn" class="uwsmq-btn uwsmq-btn-outline">
                <span class="dashicons dashicons-email"></span> Test SMTP
            </button>
        </div>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field( 'uwsmq_save_settings_action', 'uwsmq_save_settings_nonce' ); ?>
        
        <div class="uwsmq-grid">
            <div class="uwsmq-card">
                <h2><span class="dashicons dashicons-admin-settings"></span> SMTP Configuration</h2>
                
                <div class="uwsmq-form-group">
                    <label>SMTP Host</label>
                    <input type="text" name="smtp_host" value="<?php echo esc_attr( $settings['smtp_host'] ); ?>" placeholder="smtp.gmail.com">
                </div>

                <div class="uwsmq-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="uwsmq-form-group">
                        <label>SMTP Port</label>
                        <input type="text" name="smtp_port" value="<?php echo esc_attr( $settings['smtp_port'] ); ?>" placeholder="587">
                    </div>
                    <div class="uwsmq-form-group">
                        <label>Encryption</label>
                        <select name="smtp_secure">
                            <option value="tls" <?php selected( $settings['smtp_secure'], 'tls' ); ?>>TLS</option>
                            <option value="ssl" <?php selected( $settings['smtp_secure'], 'ssl' ); ?>>SSL</option>
                            <option value="" <?php selected( $settings['smtp_secure'], '' ); ?>>None</option>
                        </select>
                    </div>
                </div>

                <div class="uwsmq-form-group">
                    <label>Authentication</label>
                    <label class="uwsmq-switch">
                        <input type="checkbox" name="smtp_auth" <?php checked( $settings['smtp_auth'], 'yes' ); ?>>
                        <span class="uwsmq-slider"></span>
                    </label>
                </div>

                <div class="uwsmq-form-group">
                    <label>Username</label>
                    <input type="text" name="smtp_user" value="<?php echo esc_attr( $settings['smtp_user'] ); ?>">
                </div>

                <div class="uwsmq-form-group">
                    <label>Password</label>
                    <input type="password" name="smtp_pass" value="<?php echo esc_attr( $settings['smtp_pass'] ); ?>">
                </div>
            </div>

            <div class="uwsmq-card">
                <h2><span class="dashicons dashicons-id"></span> Sender Information</h2>
                
                <div class="uwsmq-form-group">
                    <label>From Email</label>
                    <input type="text" name="from_email" value="<?php echo esc_attr( $settings['from_email'] ); ?>">
                </div>

                <div class="uwsmq-form-group">
                    <label>From Name</label>
                    <input type="text" name="from_name" value="<?php echo esc_attr( $settings['from_name'] ); ?>">
                </div>

                <hr style="border: 0; border-top: 1px solid var(--uwsmq-border); margin: 30px 0;">

                <h2><span class="dashicons dashicons-clock"></span> Queue Settings</h2>
                
                <div class="uwsmq-form-group">
                    <label>Enable Mailing Queue</label>
                    <label class="uwsmq-switch">
                        <input type="checkbox" name="enable_queue" <?php checked( $settings['enable_queue'], 'yes' ); ?>>
                        <span class="uwsmq-slider"></span>
                    </label>
                    <p class="description">If enabled, emails will be stored in database and sent in batches.</p>
                </div>

                <div class="uwsmq-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="uwsmq-form-group">
                        <label>Batch Size</label>
                        <input type="number" name="batch_size" value="<?php echo esc_attr( $settings['batch_size'] ); ?>">
                    </div>
                    <div class="uwsmq-form-group">
                        <label>Interval (Seconds)</label>
                        <input type="number" name="interval" value="<?php echo esc_attr( $settings['interval'] ); ?>">
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align: right; margin-top: 20px;">
            <button type="submit" name="uwsmq_save_settings" class="uwsmq-btn uwsmq-btn-primary">
                <span class="dashicons dashicons-saved"></span> Save Settings
            </button>
        </div>
    </form>
</div>

<!-- Test SMTP Modal (Simple implementation) -->
<div id="uwsmq-test-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999; align-items:center; justify-content:center;">
    <div class="uwsmq-card" style="width:400px; background:white;">
        <h2>Test SMTP Delivery</h2>
        <div class="uwsmq-form-group">
            <label>Recipient Email</label>
            <input type="text" id="uwsmq-test-email" placeholder="you@example.com">
        </div>
        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button id="uwsmq-cancel-test" class="uwsmq-btn uwsmq-btn-outline">Cancel</button>
            <button id="uwsmq-send-test" class="uwsmq-btn uwsmq-btn-primary">Send Test</button>
        </div>
    </div>
</div>
