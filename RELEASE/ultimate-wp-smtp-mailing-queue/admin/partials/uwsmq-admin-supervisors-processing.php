<div class="uwsmq-processing-info">
    <p>Authorized delay to process through wp-cron: <strong>30 seconds</strong></p>
    <p>Maximum delay reached while processing through wp-cron (last 24h): <strong><?php echo (int)get_transient('uwsmq_max_delay'); ?> seconds</strong></p>
    
    <p class="description">If this maximum delay is too high, please reduce the queue limit.</p>

    <hr>

    <h3>Internal WP-Cron Status</h3>
    <?php if ( wp_next_scheduled( 'uwsmq_process_queue_cron' ) ) : ?>
        <p>Next scheduled processing: <strong><?php echo date_i18n( get_option('date_format') . ' ' . get_option('time_format'), wp_next_scheduled( 'uwsmq_process_queue_cron' ) ); ?></strong></p>
    <?php else : ?>
        <p style="color:red;">Cron event not scheduled! Please re-save settings.</p>
    <?php endif; ?>
</div>
