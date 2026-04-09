<div class="uwsmq-test-mail-form">
    <table class="form-table">
        <tr>
            <th scope="row"><label for="test_to">To email address</label></th>
            <td><input type="text" id="test_to" class="regular-text" value=""></td>
        </tr>
        <tr>
            <th scope="row"><label for="test_cc">Cc email addresses</label></th>
            <td>
                <input type="text" id="test_cc" class="regular-text" value="">
                <p class="description">Multiple addresses can be added separated by comma.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="test_bcc">Bcc email addresses</label></th>
            <td>
                <input type="text" id="test_bcc" class="regular-text" value="">
                <p class="description">Multiple addresses can be added separated by comma.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="test_subject">Subject</label></th>
            <td><input type="text" id="test_subject" class="regular-text" value=""></td>
        </tr>
        <tr>
            <th scope="row"><label for="test_message">Message</label></th>
            <td><textarea id="test_message" rows="10" class="large-text"></textarea></td>
        </tr>
        <tr>
            <th scope="row">Send now?</th>
            <td>
                <label>
                    <input type="checkbox" id="test_direct" value="1">
                    Send directly without waiting for cronjob to process queue
                </label>
            </td>
        </tr>
    </table>
    <p class="submit">
        <button type="button" id="uwsmq-send-test-full" class="button button-primary">Send Test Email</button>
    </p>
</div>
