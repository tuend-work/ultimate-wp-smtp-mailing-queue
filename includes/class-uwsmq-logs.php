<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UWSMQ_Logs {

	public static function rotate_logs() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'uwsmq_logs';
		$settings = get_option( 'uwsmq_settings' );
		$limit = isset( $settings['log_limit'] ) ? (int)$settings['log_limit'] : 1000;

		if ( $limit <= 0 ) {
			return;
		}

		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

		if ( $count > $limit ) {
			$to_delete = $count - $limit;
			$wpdb->query( $wpdb->prepare( 
				"DELETE FROM $table_name ORDER BY sent_at ASC LIMIT %d", 
				$to_delete 
			) );
		}
	}

	public static function add_log( $to, $subject, $status, $error = '', $source = 'direct' ) {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'uwsmq_logs',
			array(
				'to_email'    => is_array( $to ) ? implode( ',', $to ) : $to,
				'subject'     => $subject,
				'status'      => $status,
				'error_message' => $error,
				'sent_at'     => current_time( 'mysql' ),
				'source'      => $source
			)
		);
	}
}
