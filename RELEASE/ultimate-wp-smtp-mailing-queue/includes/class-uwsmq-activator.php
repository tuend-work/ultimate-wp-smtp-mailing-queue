<?php

class UWSMQ_Activator {

	public static function activate() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'uwsmq_queue';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			to_email text NOT NULL,
			subject text NOT NULL,
			message longtext NOT NULL,
			headers text DEFAULT '' NOT NULL,
			attachments text DEFAULT '' NOT NULL,
			status varchar(20) DEFAULT 'pending' NOT NULL,
			attempts int(11) DEFAULT 0 NOT NULL,
			error_message text DEFAULT '' NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			sent_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Set default options
		if ( ! get_option( 'uwsmq_settings' ) ) {
			update_option( 'uwsmq_settings', array(
				'smtp_host'     => '',
				'smtp_port'     => '587',
				'smtp_auth'     => 'yes',
				'smtp_user'     => '',
				'smtp_pass'     => '',
				'smtp_secure'   => 'tls',
				'from_email'    => get_option( 'admin_email' ),
				'from_name'     => get_bloginfo( 'name' ),
				'enable_queue'  => 'yes',
				'batch_size'    => '10',
				'interval'      => '300', // 5 minutes
			) );
		}

		// Schedule CRON
		if ( ! wp_next_scheduled( 'uwsmq_process_queue_cron' ) ) {
			wp_schedule_event( time(), 'uwsmq_interval', 'uwsmq_process_queue_cron' );
		}
	}
}
