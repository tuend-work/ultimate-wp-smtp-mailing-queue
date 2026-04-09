<div class="uwsmq-supervisors-wrap">
    <p><strong>Processing:</strong> Show some informations about processing.</p>
    <p><strong>List Queue:</strong> Show all mails in mailing queue.</p>
    <p><strong>Sending Errors:</strong> Emails that couldn't be sent.</p>
    <p><strong>Sent:</strong> Emails that have been sent.</p>

    <h3 class="nav-tab-wrapper" style="margin-bottom: 20px;">
        <?php foreach ( $subtabs as $tab => $name ) : ?>
            <a href="?page=ultimate-wp-smtp-mailing-queue&tab=supervisors&subtab=<?php echo $tab; ?>" class="nav-tab <?php echo $current_subtab === $tab ? 'nav-tab-active' : ''; ?>"><?php echo $name; ?></a>
        <?php endforeach; ?>
    </h3>

    <div class="uwsmq-subtab-content">
        <?php 
        if ( $current_subtab === 'processing' ) {
            include UWSMQ_PLUGIN_DIR . 'admin/partials/uwsmq-admin-supervisors-processing.php';
        } else {
            // Re-using the queue display logic for List, Errors, and Sent
            include UWSMQ_PLUGIN_DIR . 'admin/partials/uwsmq-queue-display.php';
        }
        ?>
    </div>
</div>
