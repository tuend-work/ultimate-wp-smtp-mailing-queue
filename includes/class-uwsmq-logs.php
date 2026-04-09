<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UWSMQ_Logs {

	public static function rotate_logs() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'uwsmq_logs';
		$settings = get_option( 'uwsmq_settings' );
		$limit = isset( $settings['log_limit'] ) ? (int) $settings['log_limit'] : 1000;

		if ( $limit <= 0 ) {
			return;
		}

		// Only count completed logs (sent/failed), never delete pending queue items
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status IN ('sent', 'failed')" );

		if ( $count > $limit ) {
			$to_delete = $count - $limit;
			$wpdb->query( $wpdb->prepare( 
				"DELETE FROM $table_name WHERE status IN ('sent', 'failed') ORDER BY id ASC LIMIT %d", 
				$to_delete 
			) );
		}
	}

	public static function add_log( $to, $subject, $status, $error = '', $source = 'direct', $from = '', $headers = '', $message = '', $queued_at = null, $attachments = array(), $attempts = 0 ) {
		global $wpdb;
		
		if ( empty( $from ) ) {
			$settings = get_option( 'uwsmq_settings' );
			$from = ! empty( $settings['smtp_user'] ) ? $settings['smtp_user'] : ( isset( $settings['from_email'] ) ? $settings['from_email'] : '' );
		}

		$result = $wpdb->insert(
			$wpdb->prefix . 'uwsmq_logs',
			array(
				'from_email'    => $from,
				'to_email'      => is_array( $to ) ? implode( ',', $to ) : $to,
				'subject'       => $subject,
				'message'       => $message,
				'headers'       => is_array( $headers ) ? serialize( $headers ) : $headers,
				'attachments'   => maybe_serialize( $attachments ),
				'status'        => $status,
				'attempts'      => $attempts,
				'error_message' => $error,
				'queued_at'     => $queued_at,
				'sent_at'       => ( $status === 'sent' ) ? current_time( 'mysql' ) : null,
				'source'        => $source
			)
		);

		if ( ! $result ) {
			error_log( 'UWSMQ Log Insert Error: ' . $wpdb->last_error );
			return false;
		}

		return $wpdb->insert_id;
	}

	public static function update_log_status( $id, $status, $error = '', $sent_at = null, $increment_attempts = false ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'uwsmq_logs';
		$data = array( 'status' => $status );
		
		if ( ! empty( $error ) ) {
			$data['error_message'] = $error;
		}

		if ( $sent_at ) {
			$data['sent_at'] = $sent_at;
		} elseif ( $status === 'sent' ) {
			$data['sent_at'] = current_time( 'mysql' );
		}

		$sql = "UPDATE $table_name SET status = %s";
		$params = array( $status );

		if ( ! empty( $error ) ) {
			$sql .= ", error_message = %s";
			$params[] = $error;
		}

		if ( isset( $data['sent_at'] ) ) {
			$sql .= ", sent_at = %s";
			$params[] = $data['sent_at'];
		}

		if ( $increment_attempts ) {
			$sql .= ", attempts = attempts + 1";
		}

		$sql .= " WHERE id = %d";
		$params[] = $id;

		return $wpdb->query( $wpdb->prepare( $sql, $params ) );
	}
}
