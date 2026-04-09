<?php

class UWSMQ_Queue {

	public static function add_to_queue( $to, $subject, $message, $headers, $attachments, $log_id = null ) {
		global $wpdb;

		$stored_attachments = UWSMQ_Attachments::store_attachments( $attachments );

		$result = $wpdb->insert(
			$wpdb->prefix . 'uwsmq_queue',
			array(
				'log_id'      => $log_id,
				'to_email'    => is_array( $to ) ? implode( ',', $to ) : $to,
				'subject'     => $subject,
				'message'     => $message,
				'headers'     => is_array( $headers ) ? serialize( $headers ) : $headers,
				'attachments' => maybe_serialize( $stored_attachments ),
				'status'      => 'pending',
				'created_at'  => current_time( 'mysql' ),
			)
		);

		if ( $result ) {
			UWSMQ_Logs::add_log( $to, $subject, 'queue', '', 'queue', '', $headers, $message, current_time( 'mysql' ) );
		}

		return $result;
	}

	public static function get_pending_items( $limit = 10 ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}uwsmq_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT %d",
			$limit
		) );
	}

	public static function update_status( $id, $status, $error = '', $attempts = null ) {
		global $wpdb;

		$data = array( 'status' => $status );
		
		if ( $status === 'sent' ) {
			$data['sent_at'] = current_time( 'mysql' );
		}
		
		if ( ! empty( $error ) ) {
			$data['error_message'] = $error;
		}

		if ( $attempts !== null ) {
			$data['attempts'] = $attempts;
		}

		return $wpdb->update(
			$wpdb->prefix . 'uwsmq_queue',
			$data,
			array( 'id' => $id )
		);
	}

	public static function delete_item( $id ) {
		global $wpdb;
		
		$item = $wpdb->get_row( $wpdb->prepare( "SELECT attachments FROM {$wpdb->prefix}uwsmq_queue WHERE id = %d", $id ) );
		if ( $item ) {
			UWSMQ_Attachments::delete_attachments( $item->attachments );
		}

		return $wpdb->delete( $wpdb->prefix . 'uwsmq_queue', array( 'id' => $id ) );
	}
}
