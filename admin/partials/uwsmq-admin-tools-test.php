<div class="uwsmq-test-mail-form">
    <table class="form-table">
        <tr>
            <th scope="row"><label for="test_to"><?php esc_html_e( 'To email address', 'ultimate-wp-smtp-mailing-queue' ); ?></label></th>
            <td><input type="text" id="test_to" class="regular-text" value=""></td>
        </tr>
        <tr>
            <th scope="row"><label for="test_cc"><?php esc_html_e( 'Cc email addresses', 'ultimate-wp-smtp-mailing-queue' ); ?></label></th>
            <td>
                <input type="text" id="test_cc" class="regular-text" value="">
                <p class="description"><?php esc_html_e( 'Multiple addresses can be added separated by comma.', 'ultimate-wp-smtp-mailing-queue' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="test_bcc"><?php esc_html_e( 'Bcc email addresses', 'ultimate-wp-smtp-mailing-queue' ); ?></label></th>
            <td>
                <input type="text" id="test_bcc" class="regular-text" value="">
                <p class="description"><?php esc_html_e( 'Multiple addresses can be added separated by comma.', 'ultimate-wp-smtp-mailing-queue' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="test_subject"><?php esc_html_e( 'Subject', 'ultimate-wp-smtp-mailing-queue' ); ?></label></th>
            <td><input type="text" id="test_subject" class="regular-text" value=""></td>
        </tr>
        <tr>
            <th scope="row"><label for="test_message"><?php esc_html_e( 'Message', 'ultimate-wp-smtp-mailing-queue' ); ?></label></th>
            <td><textarea id="test_message" rows="10" class="large-text"></textarea></td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Send now?', 'ultimate-wp-smtp-mailing-queue' ); ?></th>
            <td>
                <label>
                    <input type="checkbox" id="test_direct" value="1">
                    <?php esc_html_e( 'Send directly without waiting for cronjob to process queue', 'ultimate-wp-smtp-mailing-queue' ); ?>
                </label>
            </td>
        </tr>
    </table>
    <p class="submit">
        <button type="button" id="uwsmq-send-test-full" class="button button-primary"><?php esc_html_e( 'Send Test Email', 'ultimate-wp-smtp-mailing-queue' ); ?></button>
    </p>
</div>
