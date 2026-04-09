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

		$table_logs = $wpdb->prefix . 'uwsmq_logs';
		$sql_logs = "CREATE TABLE $table_logs (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			from_email varchar(100) DEFAULT '' NOT NULL,
			to_email text NOT NULL,
			subject text NOT NULL,
			message longtext DEFAULT '' NOT NULL,
			headers longtext DEFAULT '' NOT NULL,
			status varchar(20) NOT NULL,
			error_message text DEFAULT '' NOT NULL,
			queued_at datetime DEFAULT NULL,
			sent_at datetime DEFAULT NULL,
			source varchar(20) DEFAULT 'direct' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql_logs );

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
				'secret_key'    => wp_generate_password( 16, false ),
				'dont_use_wpcron' => 'no',
				'log_limit'     => '1000',
			) );
		}

		// Schedule CRON
		if ( ! wp_next_scheduled( 'uwsmq_process_queue_cron' ) ) {
			wp_schedule_event( time(), 'uwsmq_interval', 'uwsmq_process_queue_cron' );
		}
		if ( ! wp_next_scheduled( 'uwsmq_maintenance_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'uwsmq_maintenance_cron' );
		}
	}
}
