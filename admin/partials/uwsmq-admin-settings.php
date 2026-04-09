<div class="uwsmq-settings-wrap">
    <form method="post" action="">
        <?php wp_nonce_field( 'uwsmq_save_settings_action', 'uwsmq_save_settings_nonce' ); ?>
        
        <h3>SMTP Configuration</h3>
        <p>Enter your SMTP server details to route all WordPress emails through this account.</p>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="smtp_host">SMTP Host</label></th>
                <td>
                    <input name="smtp_host" type="text" id="smtp_host" value="<?php echo esc_attr( $settings['smtp_host'] ); ?>" class="regular-text" placeholder="smtp.example.com">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtp_port">SMTP Port</label></th>
                <td>
                    <input name="smtp_port" type="text" id="smtp_port" value="<?php echo esc_attr( $settings['smtp_port'] ); ?>" class="small-text" placeholder="587">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtp_secure">Encryption</label></th>
                <td>
                    <select name="smtp_secure" id="smtp_secure">
                        <option value="tls" <?php selected( $settings['smtp_secure'], 'tls' ); ?>>TLS</option>
                        <option value="ssl" <?php selected( $settings['smtp_secure'], 'ssl' ); ?>>SSL</option>
                        <option value="" <?php selected( $settings['smtp_secure'], '' ); ?>>None</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtp_auth">Authentication</label></th>
                <td>
                    <input name="smtp_auth" type="checkbox" id="smtp_auth" value="yes" <?php checked( $settings['smtp_auth'], 'yes' ); ?>>
                    <label for="smtp_auth">Use SMTP authentication</label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtp_user">Username</label></th>
                <td>
                    <input name="smtp_user" type="text" id="smtp_user" value="<?php echo esc_attr( $settings['smtp_user'] ); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="smtp_pass">Password</label></th>
                <td>
                    <input name="smtp_pass" type="password" id="smtp_pass" value="<?php echo esc_attr( $settings['smtp_pass'] ); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="from_email">From Email</label></th>
                <td>
                    <input name="from_email" type="email" id="from_email" value="<?php echo esc_attr( $settings['from_email'] ); ?>" class="regular-text">
                    <p class="description">Email address that emails will appear to come from.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="from_name">From Name</label></th>
                <td>
                    <input name="from_name" type="text" id="from_name" value="<?php echo esc_attr( $settings['from_name'] ); ?>" class="regular-text">
                    <p class="description">Name that emails will appear to come from.</p>
                </td>
            </tr>
        </table>

        <div class="uwsmq-submit-wrap" style="margin-top: 20px;">
            <?php submit_button( 'Save Settings', 'primary', 'uwsmq_save_settings' ); ?>
        </div>
    </form>
</div>
