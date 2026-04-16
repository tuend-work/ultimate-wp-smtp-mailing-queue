<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UWSMQ_Mailer {

	private static $instance = null;
	private $is_processing = false;
	private $force_direct = false;

	public static function get_instance() {
		if ( self::$instance === null ) {
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

		$to          = isset( $atts['to'] ) ? $atts['to'] : '';
		$subject     = isset( $atts['subject'] ) ? $atts['subject'] : '';
		$message     = isset( $atts['message'] ) ? $atts['message'] : '';
		$headers     = isset( $atts['headers'] ) ? $atts['headers'] : '';
		$attachments = isset( $atts['attachments'] ) ? $atts['attachments'] : array();

		$stored_attachments = UWSMQ_Attachments::store_attachments( $attachments );

		UWSMQ_Logs::add_log( $to, $subject, 'queue', '', 'queue', '', $headers, $message, current_time( 'mysql' ), $stored_attachments, 0 );
		
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
		
		if ( $this->pre_wp_mail_filter( null, $atts ) === true ) {
			return true;
		}
		
	public function original_wp_mail( $to, $subject, $message, $headers = '', $attachments = array(), $debug = false ) {
		require_once ABSPATH . WPINC . '/pluggable.php';
		return $this->send_with_phpmailer( $to, $subject, $message, $headers, $attachments, $debug );
	}

	public function init_smtp( $phpmailer ) {
		$settings = get_option( 'uwsmq_settings' );

		if ( empty( $settings['smtp_host'] ) ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host       = $settings['smtp_host'];
		$phpmailer->SMTPAuth   = ( isset( $settings['smtp_auth'] ) && $settings['smtp_auth'] === 'yes' );
		$phpmailer->Port       = isset( $settings['smtp_port'] ) ? $settings['smtp_port'] : 587;
		$phpmailer->Username   = isset( $settings['smtp_user'] ) ? $settings['smtp_user'] : '';
		$phpmailer->Password   = isset( $settings['smtp_pass'] ) ? $settings['smtp_pass'] : '';
		$phpmailer->SMTPSecure = isset( $settings['smtp_secure'] ) ? $settings['smtp_secure'] : 'tls';
		$phpmailer->From       = isset( $settings['from_email'] ) ? $settings['from_email'] : '';
		$phpmailer->FromName   = isset( $settings['from_name'] ) ? $settings['from_name'] : '';
	}

	public function process_queue() {
		if ( $this->is_processing ) {
			return;
		}

		$this->is_processing = true;
		global $wpdb;

		$settings = get_option( 'uwsmq_settings' );
		$batch_size = isset( $settings['batch_size'] ) ? (int) $settings['batch_size'] : 10;

		$table_name = $wpdb->prefix . 'uwsmq_logs';
		$items = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE status = 'queue' OR status = 'failed' ORDER BY id ASC LIMIT %d",
			$batch_size
		) );

		foreach ( $items as $item ) {
			$this->process_single_item( $item );
		}

		// Rotate logs after batch processing
		UWSMQ_Logs::rotate_logs();

		$this->is_processing = false;
	}

	public function process_bulk_items( $ids ) {
		if ( $this->is_processing || empty( $ids ) ) {
			return;
		}

		$this->is_processing = true;
		global $wpdb;
		
		$ids = array_map( 'absint', $ids );
		$ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$table_name = $wpdb->prefix . 'uwsmq_logs';
		$query = $wpdb->prepare( "SELECT * FROM $table_name WHERE id IN ($ids_placeholder)", $ids );
		$items = $wpdb->get_results( $query );

		foreach ( $items as $item ) {
			$this->process_single_item( $item );
		}

		$this->is_processing = false;
	}

	private function process_single_item( $item ) {
		UWSMQ_Logs::update_log_status( $item->id, 'processing' );
		
		$headers     = maybe_unserialize( $item->headers );
		$attachments = maybe_unserialize( $item->attachments );

		$result = $this->send_with_phpmailer( 
			$item->to_email, 
			$item->subject, 
			$item->message, 
			$headers, 
			$attachments 
		);

		if ( $result ) {
			UWSMQ_Logs::update_log_status( $item->id, 'sent', '', null, true );
		} else {
			global $phpmailer_error;
			$error = ! empty( $phpmailer_error ) ? $phpmailer_error : 'Unknown error';
			UWSMQ_Logs::update_log_status( $item->id, 'failed', $error, null, true );
		}
	}

	private $debug_output = '';

	public function get_debug_output() {
		return $this->debug_output;
	}

	public function send_with_phpmailer( $to, $subject, $message, $headers = '', $attachments = array(), $debug = false ) {
		global $phpmailer;

		// WordPress 5.5+ uses namespaced PHPMailer
		if ( file_exists( ABSPATH . WPINC . '/PHPMailer/PHPMailer.php' ) ) {
			// WordPress 5.5+
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
			if ( ! ( $phpmailer instanceof PHPMailer\PHPMailer\PHPMailer ) ) {
				$phpmailer = new PHPMailer\PHPMailer\PHPMailer( true );
			}
		} else {
			// WordPress < 5.5 (legacy)
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
			require_once ABSPATH . WPINC . '/class-smtp.php';
			if ( ! ( $phpmailer instanceof PHPMailer ) ) {
				$phpmailer = new PHPMailer( true );
			}
		}

		// Reset PHPMailer
		$phpmailer->clearAllRecipients();
		$phpmailer->clearAttachments();
		$phpmailer->clearCustomHeaders();
		$phpmailer->clearReplyTos();

		$phpmailer->CharSet = 'UTF-8';

		// Apply SMTP settings
		$this->init_smtp( $phpmailer );

		// Debugging setup
		if ( $debug ) {
			$this->debug_output = '';
			$phpmailer->SMTPDebug = 2; // Client + Server messages
			$phpmailer->Debugoutput = function( $str, $level ) {
				$this->debug_output .= $str . "\n";
			};
		} else {
			$phpmailer->SMTPDebug = 0;
		}

		try {
			// To
			if ( is_array( $to ) ) {
				foreach ( $to as $recipient ) {
					$phpmailer->addAddress( trim( $recipient ) );
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
