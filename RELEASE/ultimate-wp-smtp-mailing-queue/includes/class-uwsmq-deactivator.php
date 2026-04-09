<?php

class UWSMQ_Deactivator {

	public static function deactivate() {
		wp_clear_scheduled_hook( 'uwsmq_process_queue_cron' );
	}
}
