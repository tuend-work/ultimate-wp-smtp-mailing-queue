<div class="uwsmq-tools-wrap">
    <p><strong>Test Mail:</strong> Test your email settings by sending directly or adding test mail into queue.</p>
    <p><strong>Process Queue:</strong> Start queue processing manually. Your set queue limit will still be obeyed, if set.</p>

    <h3 class="nav-tab-wrapper" style="margin-bottom: 20px;">
        <?php foreach ( $subtabs as $tab => $name ) : ?>
            <a href="?page=ultimate-wp-smtp-mailing-queue&tab=tools&subtab=<?php echo $tab; ?>" class="nav-tab <?php echo $current_subtab === $tab ? 'nav-tab-active' : ''; ?>"><?php echo $name; ?></a>
        <?php endforeach; ?>
    </h3>

    <div class="uwsmq-subtab-content">
        <?php 
        if ( $current_subtab === 'process' ) {
            include UWSMQ_PLUGIN_DIR . 'admin/partials/uwsmq-admin-tools-process.php';
        } else {
            include UWSMQ_PLUGIN_DIR . 'admin/partials/uwsmq-admin-tools-test.php';
        }
        ?>
    </div>
</div>
