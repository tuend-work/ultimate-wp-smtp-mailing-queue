<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UWSMQ_Admin {

	protected $current_tab;
	protected $current_subtab;

	public function __construct() {
		$this->current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'settings';
		$this->current_subtab = isset( $_GET['subtab'] ) ? sanitize_text_field( $_GET['subtab'] ) : 'test';

		add_action( 'admin_init', array( $this, 'maybe_save_settings' ) );
	}

	public function maybe_save_settings() {
		if ( isset( $_POST['uwsmq_save_settings'] ) ) {
			$this->save_settings();
		}
	}

	public function add_plugin_admin_menu() {
		add_menu_page(
			__( 'SMTP Mailing Queue', 'ultimate-wp-smtp-mailing-queue' ),
			__( 'SMTP Queue', 'ultimate-wp-smtp-mailing-queue' ),
			'manage_options',
			'ultimate-wp-smtp-mailing-queue',
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-email-alt',
			80
		);

		add_submenu_page(
			'ultimate-wp-smtp-mailing-queue',
			__( 'Settings', 'ultimate-wp-smtp-mailing-queue' ),
			__( 'Settings', 'ultimate-wp-smtp-mailing-queue' ),
			'manage_options',
			'ultimate-wp-smtp-mailing-queue',
			array( $this, 'display_plugin_admin_page' )
		);
	}

	public function enqueue_styles() {
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'ultimate-wp-smtp-mailing-queue' ) {
			wp_enqueue_style( 'uwsmq-admin-css', UWSMQ_PLUGIN_URL . 'admin/css/uwsmq-admin.css', array(), UWSMQ_VERSION, 'all' );
		}
	}

	public function enqueue_scripts() {
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'ultimate-wp-smtp-mailing-queue' ) {
			wp_enqueue_script( 'uwsmq-admin-js', UWSMQ_PLUGIN_URL . 'admin/js/uwsmq-admin.js', array( 'jquery' ), UWSMQ_VERSION, false );
			wp_localize_script( 'uwsmq-admin-js', 'uwsmq_ajax', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'uwsmq_admin_nonce' )
			) );
		}
	}

	public function display_plugin_admin_page() {
		$tabs = array(
			'settings'      => __( 'SMTP Settings', 'ultimate-wp-smtp-mailing-queue' ),
			'advanced'      => __( 'Advanced Settings', 'ultimate-wp-smtp-mailing-queue' ),
			'test-email'    => __( 'Test Email', 'ultimate-wp-smtp-mailing-queue' ),
			'email-monitor' => __( 'Email Queue Monitor', 'ultimate-wp-smtp-mailing-queue' )
		);

		$settings = get_option( 'uwsmq_settings' );
		
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'SMTP Mailing Queue', 'ultimate-wp-smtp-mailing-queue' ) . '</h1>';
		
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab => $name ) {
			$class = ( $tab == $this->current_tab ) ? 'nav-tab-active' : '';
			echo "<a href='?page=ultimate-wp-smtp-mailing-queue&tab=$tab' class='nav-tab $class'>$name</a>";
		}
		echo '</h2>';

		echo '<div class="uwsmq-tab-content" style="margin-top: 20px;">';

		// Pre-calculate cron status
		$next_cron     = wp_next_scheduled( 'uwsmq_process_queue_cron' );
		$dont_use_cron = isset( $settings['dont_use_wpcron'] ) && $settings['dont_use_wpcron'] === 'yes';

		if ( ! $dont_use_cron ) {
			if ( ! $next_cron || $next_cron < time() - 300 ) {
				wp_clear_scheduled_hook( 'uwsmq_process_queue_cron' );
				wp_schedule_event( time(), 'uwsmq_interval', 'uwsmq_process_queue_cron' );
				$next_cron = wp_next_scheduled( 'uwsmq_process_queue_cron' );
			}
			$cron_status = $next_cron ? date_i18n( 'Y-m-d H:i:s', $next_cron + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) : 'Not scheduled';
		} else {
			$last_ext = get_option( 'uwsmq_last_external_run' );
			$cron_status = $last_ext
				? 'External Cron Active (Last run: ' . date_i18n( 'Y-m-d H:i:s', $last_ext + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) . ')'
				: 'External Cron Managed (Waiting for first run)';
		}

		switch ( $this->current_tab ) {
			case 'email-monitor':
				$this->display_email_monitor_page( $cron_status );
				break;
			case 'test-email':
				$this->display_tools_page();
				break;
			case 'advanced':
				include UWSMQ_PLUGIN_DIR . 'admin/partials/uwsmq-admin-advanced.php';
				break;
			case 'settings':
			default:
				include UWSMQ_PLUGIN_DIR . 'admin/partials/uwsmq-admin-settings.php';
				break;
		}
		
		echo '</div>';
		echo '</div>';
	}

	private function save_settings() {
		// Nonce verification (CSRF protection)
		if ( ! isset( $_POST['uwsmq_save_settings_nonce'] ) ||
		     ! wp_verify_nonce( $_POST['uwsmq_save_settings_nonce'], 'uwsmq_save_settings_action' ) ) {
			return;
		}

		// Capability check
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$old_settings = get_option( 'uwsmq_settings' );
		$new_settings = $old_settings;

		if ( $this->current_tab === 'settings' ) {
			$new_settings['smtp_host']   = sanitize_text_field( trim( wp_unslash( $_POST['smtp_host'] ?? '' ) ) );
			$new_settings['smtp_port']   = sanitize_text_field( trim( wp_unslash( $_POST['smtp_port'] ?? '587' ) ) );
			$new_settings['smtp_auth']   = isset( $_POST['smtp_auth'] ) ? 'yes' : 'no';
			$new_settings['smtp_user']   = sanitize_text_field( trim( wp_unslash( $_POST['smtp_user'] ?? '' ) ) );
			$new_settings['smtp_pass']   = trim( wp_unslash( $_POST['smtp_pass'] ?? '' ) );
			$new_settings['smtp_secure'] = sanitize_text_field( wp_unslash( $_POST['smtp_secure'] ?? 'tls' ) );
			$new_settings['from_email']  = sanitize_email( trim( wp_unslash( $_POST['from_email'] ?? '' ) ) );
			$new_settings['from_name']   = sanitize_text_field( trim( wp_unslash( $_POST['from_name'] ?? '' ) ) );
		} elseif ( $this->current_tab === 'advanced' ) {
			$new_settings['enable_queue']    = isset( $_POST['enable_queue'] ) ? 'yes' : 'no';
			$new_settings['dont_use_wpcron'] = isset( $_POST['dont_use_wpcron'] ) ? 'yes' : 'no';
			$new_settings['batch_size']      = isset( $_POST['batch_size'] ) ? absint( $_POST['batch_size'] ) : 10;
			$new_settings['interval']        = isset( $_POST['interval'] ) ? absint( $_POST['interval'] ) : 300;
			$new_settings['secret_key']      = sanitize_text_field( wp_unslash( $_POST['secret_key'] ?? '' ) );
			$new_settings['log_limit']       = isset( $_POST['log_limit'] ) ? absint( $_POST['log_limit'] ) : 1000;

			update_option( 'uwsmq_settings', $new_settings );

			if ( $old_settings['interval'] != $new_settings['interval'] || $old_settings['dont_use_wpcron'] != $new_settings['dont_use_wpcron'] ) {
				wp_clear_scheduled_hook( 'uwsmq_process_queue_cron' );
				if ( $new_settings['dont_use_wpcron'] !== 'yes' ) {
					wp_schedule_event( time(), 'uwsmq_interval', 'uwsmq_process_queue_cron' );
				}
			}
		}

		update_option( 'uwsmq_settings', $new_settings );

		// PRG Pattern: Redirect back to prevent resubmission & "headers already sent"
		$redirect_url = add_query_arg(
			array( 'page' => 'ultimate-wp-smtp-mailing-queue', 'tab' => $this->current_tab, 'settings-updated' => 'true' ),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redirect_url );
		exit;
	}

	private function display_tools_page() {
		include UWSMQ_PLUGIN_DIR . 'admin/partials/uwsmq-admin-tools.php';
	}

	private function display_email_monitor_page( $cron_status = '' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'uwsmq_logs';
		$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
		
		$sql = "SELECT * FROM $table_name";
		if ( ! empty( $status_filter ) && $status_filter !== 'all' ) {
			$sql .= $wpdb->prepare( " WHERE status = %s", $status_filter );
		}
		$sql .= " ORDER BY id DESC LIMIT 200";
		
		$items = $wpdb->get_results( $sql );

		include UWSMQ_PLUGIN_DIR . 'admin/partials/uwsmq-email-monitor.php';
	}

	public function ajax_test_smtp() {
		check_ajax_referer( 'uwsmq_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'ultimate-wp-smtp-mailing-queue' ) ) );
		}
		
		$to      = sanitize_text_field( wp_unslash( $_POST['test_email'] ?? '' ) );
		$cc      = sanitize_text_field( wp_unslash( $_POST['test_cc'] ?? '' ) );
		$bcc     = sanitize_text_field( wp_unslash( $_POST['test_bcc'] ?? '' ) );
		$subject = sanitize_text_field( wp_unslash( $_POST['test_subject'] ?? '' ) );
		$message = wp_kses_post( wp_unslash( $_POST['test_message'] ?? '' ) );
		$direct  = isset( $_POST['test_direct'] ) && ( $_POST['test_direct'] === 'true' || $_POST['test_direct'] === '1' );

		$headers = array();
		if ( ! empty( $cc ) )  $headers[] = 'Cc: ' . $cc;
		if ( ! empty( $bcc ) ) $headers[] = 'Bcc: ' . $bcc;
		$headers[] = 'Content-Type: text/html; charset=UTF-8';

		$mailer = UWSMQ_Mailer::get_instance();
		
		// Luôn sử dụng send_with_phpmailer trực tiếp khi test để có debug transcript
		$result = $mailer->send_with_phpmailer( $to, $subject, $message, $headers, array(), true );
		
		global $phpmailer_error;
		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Email sent successfully!', 'ultimate-wp-smtp-mailing-queue' ) ) );
		} else {
			$debug_transcript = $mailer->get_debug_output();
			$error_msg = ! empty( $phpmailer_error ) ? $phpmailer_error : __( 'Failed to send email.', 'ultimate-wp-smtp-mailing-queue' );
			
			if ( ! empty( $debug_transcript ) ) {
				$error_msg .= "\n\n--- SMTP DEBUG TRANSCRIPT ---\n" . $debug_transcript;
			}
			
			wp_send_json_error( array( 'message' => $error_msg ) );
		}
	}

	public function ajax_process_queue() {
		check_ajax_referer( 'uwsmq_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}
		$mailer = UWSMQ_Mailer::get_instance();
		$mailer->process_queue();
		wp_send_json_success( array( 'message' => __( 'Queue processed successfully.', 'ultimate-wp-smtp-mailing-queue' ) ) );
	}

	public function ajax_delete_log() {
		check_ajax_referer( 'uwsmq_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( $id > 0 ) {
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'uwsmq_logs', array( 'id' => $id ), array( '%d' ) );
		}
		wp_send_json_success();
	}

	// Alias for backwards compatibility
	public function ajax_delete_item() {
		$this->ajax_delete_log();
	}

	public function ajax_bulk_action() {
		check_ajax_referer( 'uwsmq_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}

		$ids    = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : array();
		$action = isset( $_POST['bulk_action'] ) ? sanitize_text_field( $_POST['bulk_action'] ) : '';

		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No items selected.', 'ultimate-wp-smtp-mailing-queue' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'uwsmq_logs';

		if ( $action === 'bulk-delete' ) {
			$ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE id IN ($ids_placeholder)", $ids ) );
		} elseif ( $action === 'bulk-send' ) {
			$mailer = UWSMQ_Mailer::get_instance();
			$mailer->process_bulk_items( $ids );
		}

		wp_send_json_success();
	}

	public function ajax_refresh_cron() {
		check_ajax_referer( 'uwsmq_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}
		wp_clear_scheduled_hook( 'uwsmq_process_queue_cron' );
		wp_schedule_event( time(), 'uwsmq_interval', 'uwsmq_process_queue_cron' );
		wp_send_json_success();
	}

	public function ajax_send_now() {
		check_ajax_referer( 'uwsmq_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( $id > 0 ) {
			$mailer = UWSMQ_Mailer::get_instance();
			$mailer->process_bulk_items( array( $id ) );
		}
		wp_send_json_success();
	}

	public function display_logs_page() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'uwsmq_logs';
		$items = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC LIMIT 100" );
		
		echo '<div class="wrap">';
		echo '<h1>Email Logs</h1>';
		include UWSMQ_PLUGIN_DIR . 'admin/partials/uwsmq-logs-display.php';
		echo '</div>';
	}
}
