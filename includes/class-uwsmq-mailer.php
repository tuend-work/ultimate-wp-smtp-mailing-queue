<?php

class UWSMQ_Mailer {

	private static $instance = null;
	private $is_processing = false;
	private $force_direct = false;

	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function force_direct_send( $force = true ) {
		$this->force_direct = $force;
	}

	public function get_force_direct() {
		return $this->force_direct;
	}

	public function pre_wp_mail_filter( $return, $atts ) {
		$settings = get_option( 'uwsmq_settings' );
		$enable_queue = isset( $settings['enable_queue'] ) && $settings['enable_queue'] === 'yes';

		if ( $this->is_processing || $this->force_direct || ! $enable_queue ) {
			return null;
		}

		// EXTRACT DATA
		$to          = isset( $atts['to'] ) ? $atts['to'] : '';
		$subject     = isset( $atts['subject'] ) ? $atts['subject'] : '';
		$message     = isset( $atts['message'] ) ? $atts['message'] : '';
		$headers     = isset( $atts['headers'] ) ? $atts['headers'] : '';
		$attachments = isset( $atts['attachments'] ) ? $atts['attachments'] : array();

		// Add to queue
		UWSMQ_Queue::add_to_queue( $to, $subject, $message, $headers, $attachments );
		
		return true;
	}

	public function handle_wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
		$atts = array(
			'to'          => $to,
			'subject'     => $subject,
			'message'     => $message,
			'headers'     => $headers,
			'attachments' => $attachments
		);
		
		// If the filter returns true, it handled the queuing
		if ( $this->pre_wp_mail_filter( null, $atts ) === true ) {
			return true;
		}
		
		// Otherwise, go direct
		return $this->original_wp_mail( $to, $subject, $message, $headers, $attachments );
	}

	private function original_wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
		// This uses the built-in PHPMailer via the phpmailer_init hook which we already set up.
		require_once ABSPATH . WPINC . '/pluggable.php';
		// Note: Since we are in the wp_mail override, we might need to manually trigger the PHPMailer flow
		// if we were trying to avoid calling wp_mail again. But actually, since we set $is_processing = true,
		// calling the global wp_mail (which would be us) is not ideal if it's already defined.
		// However, WordPress's pluggable.php uses `if ( ! function_exists( 'wp_mail' ) )`.
		// If we are here, we ARE wp_mail. So we need to use PHPMailer directly.
		return $this->send_with_phpmailer( $to, $subject, $message, $headers, $attachments );
	}

	public function init_smtp( $phpmailer ) {
		$settings = get_option( 'uwsmq_settings' );

		if ( empty( $settings['smtp_host'] ) ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host       = $settings['smtp_host'];
		$phpmailer->SMTPAuth   = ( $settings['smtp_auth'] === 'yes' );
		$phpmailer->Port       = $settings['smtp_port'];
		$phpmailer->Username   = $settings['smtp_user'];
		$phpmailer->Password   = $settings['smtp_pass'];
		$phpmailer->SMTPSecure = $settings['smtp_secure'];
		$phpmailer->From       = $settings['from_email'];
		$phpmailer->FromName   = $settings['from_name'];
	}

	public function process_queue() {
		if ( $this->is_processing ) {
			return;
		}

		$this->is_processing = true;
		$settings = get_option( 'uwsmq_settings' );
		$batch_size = isset( $settings['batch_size'] ) ? (int)$settings['batch_size'] : 10;

		$items = UWSMQ_Queue::get_pending_items( $batch_size );

		foreach ( $items as $item ) {
			$this->process_single_item( $item );
		}

		$this->is_processing = false;
	}

	public function process_bulk_items( $ids ) {
		if ( $this->is_processing || empty( $ids ) ) {
			return;
		}

		$this->is_processing = true;
		global $wpdb;
		
		$ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$query = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}uwsmq_queue WHERE id IN ($ids_placeholder)", $ids );
		$items = $wpdb->get_results( $query );

		foreach ( $items as $item ) {
			$this->process_single_item( $item );
		}

		$this->is_processing = false;
	}

	private function process_single_item( $item ) {
		UWSMQ_Queue::update_status( $item->id, 'sending' );
		
		$headers = maybe_unserialize( $item->headers );
		$attachments = maybe_unserialize( $item->attachments );

		$result = $this->send_with_phpmailer( 
			$item->to_email, 
			$item->subject, 
			$item->message, 
			$headers, 
			$attachments 
		);

		if ( $result ) {
			UWSMQ_Queue::update_status( $item->id, 'sent', '', $item->attempts + 1 );
			UWSMQ_Logs::add_log( $item->to_email, $item->subject, 'sent', '', 'queue', '', $headers, $item->message, $item->created_at );
		} else {
			global $phpmailer_error;
			UWSMQ_Queue::update_status( $item->id, 'failed', $phpmailer_error, $item->attempts + 1 );
			UWSMQ_Logs::add_log( $item->to_email, $item->subject, 'failed', $phpmailer_error, 'queue', '', $headers, $item->message, $item->created_at );
		}
	}

	private function send_with_phpmailer( $to, $subject, $message, $headers = '', $attachments = array() ) {
		global $phpmailer;

		if ( ! ( $phpmailer instanceof PHPMailer ) ) {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
			require_once ABSPATH . WPINC . '/class-smtp.php';
			$phpmailer = new PHPMailer( true );
		}

		// Reset PHPMailer
		$phpmailer->clearAllRecipients();
		$phpmailer->clearAttachments();
		$phpmailer->clearCustomHeaders();
		$phpmailer->clearReplyTos();

		// Set Character Encoding
		$phpmailer->CharSet = 'UTF-8';

		// Apply SMTP settings
		$this->init_smtp( $phpmailer );

		try {
			// To
			if ( is_array( $to ) ) {
				foreach ( $to as $recipient ) {
					$phpmailer->addAddress( $recipient );
				}
			} else {
				$recipients = explode( ',', $to );
				foreach ( $recipients as $recipient ) {
					$phpmailer->addAddress( trim( $recipient ) );
				}
			}

			// Subject & Body
			$phpmailer->Subject = $subject;
			$phpmailer->Body    = $message;
			
			// Detect Content-Type
			$content_type = 'text/plain';
			if ( ! empty( $headers ) ) {
				$header_lines = is_array( $headers ) ? $headers : explode( "\n", str_replace( "\r\n", "\n", $headers ) );
				foreach ( $header_lines as $header ) {
					if ( stripos( $header, 'Content-Type:' ) !== false && stripos( $header, 'text/html' ) !== false ) {
						$content_type = 'text/html';
						break;
					}
				}
			}
			$phpmailer->isHTML( $content_type === 'text/html' );

			// Headers
			if ( ! empty( $headers ) ) {
				if ( is_string( $headers ) ) {
					$headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
				}
				foreach ( (array) $headers as $header ) {
					if ( strpos( $header, ':' ) !== false ) {
						list( $name, $value ) = explode( ':', $header, 2 );
						$phpmailer->addCustomHeader( trim( $name ), trim( $value ) );
					}
				}
			}

			// Attachments
			if ( ! empty( $attachments ) ) {
				foreach ( (array) $attachments as $attachment ) {
					if ( file_exists( $attachment ) ) {
						$phpmailer->addAttachment( $attachment );
					}
				}
			}

			$result = $phpmailer->send();
			if ( ! $result ) {
				global $phpmailer_error;
				$phpmailer_error = $phpmailer->ErrorInfo;
			}
			return $result;

		} catch ( Exception $e ) {
			global $phpmailer_error;
			$phpmailer_error = $e->getMessage();
			if ( ! $this->is_processing ) {
				UWSMQ_Logs::add_log( $to, $subject, 'failed', $phpmailer_error, 'direct', '', $headers, $message );
			}
			return false;
		}
	}

}
