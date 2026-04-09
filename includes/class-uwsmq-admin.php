<?php

class UWSMQ_Admin {

	private $current_tab;
	private $current_subtab;

	public function __construct() {
		$this->current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'settings';
		$this->current_subtab = isset( $_GET['subtab'] ) ? sanitize_text_field( $_GET['subtab'] ) : '';
		
		// Set default subtabs
		if ( empty( $this->current_subtab ) ) {
			if ( $this->current_tab === 'test-email' ) {
				$this->current_subtab = 'test';
			} elseif ( $this->current_tab === 'supervisors' ) {
				$this->current_subtab = 'processing';
			}
		}
	}

	public function add_plugin_admin_menu() {
		add_menu_page(
			'SMTP Mailing Queue',
			'SMTP Queue',
			'manage_options',
			'ultimate-wp-smtp-mailing-queue',
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-email-alt',
			80
		);

		add_submenu_page(
			'ultimate-wp-smtp-mailing-queue',
			'Settings',
			'Settings',
			'manage_options',
			'ultimate-wp-smtp-mailing-queue',
			array( $this, 'display_plugin_admin_page' )
		);

		add_submenu_page(
			'ultimate-wp-smtp-mailing-queue',
			'Email Monitor',
			'Email Monitor',
			'manage_options',
			'uwsmq-email-monitor',
			array( $this, 'display_email_monitor_page' )
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
			'settings'    => 'SMTP Settings',
			'advanced'    => 'Advanced Settings',
			'test-email'  => 'Test Email',
			'email-monitor' => 'Email Monitor'
		);

		if ( isset( $_POST['uwsmq_save_settings'] ) && check_admin_referer( 'uwsmq_save_settings_action', 'uwsmq_save_settings_nonce' ) ) {
			$this->save_settings();
			echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
		}

		$settings = get_option( 'uwsmq_settings' );
		
		echo '<div class="wrap">';
		echo '<h1>SMTP Mailing Queue</h1>';
		
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab => $name ) {
			$class = ( $tab == $this->current_tab ) ? 'nav-tab-active' : '';
			echo "<a href='?page=ultimate-wp-smtp-mailing-queue&tab=$tab' class='nav-tab $class'>$name</a>";
		}
		echo '</h2>';

		echo '<div class="uwsmq-tab-content" style="margin-top: 20px;">';
		switch ( $this->current_tab ) {
			case 'advanced':
				include UWSMQ_PLUGIN_DIR . 'admin/partials/uwsmq-admin-advanced.php';
				break;
			case 'test-email':
				$this->display_tools_page();
				break;
			case 'email-monitor':
				$next_cron = wp_next_scheduled( 'uwsmq_process_queue_cron' );
				$settings = get_option( 'uwsmq_settings' );
				$dont_use_cron = isset( $settings['dont_use_wpcron'] ) && $settings['dont_use_wpcron'] === 'yes';

				// Auto-fix: if not using external cron, and next cron is more than 5 mins in the past, or missing
				if ( ! $dont_use_cron ) {
					if ( ! $next_cron || $next_cron < time() - 300 ) {
						wp_clear_scheduled_hook( 'uwsmq_process_queue_cron' );
						wp_schedule_event( time(), 'uwsmq_interval', 'uwsmq_process_queue_cron' );
						$next_cron = wp_next_scheduled( 'uwsmq_process_queue_cron' );
					}
					$cron_status = $next_cron ? date_i18n( 'Y-m-d H:i:s', $next_cron + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) : 'Not scheduled';
				} else {
					$last_ext = get_option( 'uwsmq_last_external_run' );
					$cron_status = $last_ext ? 'External Cron Active (Last run: ' . date_i18n( 'Y-m-d H:i:s', $last_ext + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) . ')' : 'External Cron Managed (Waiting for first run)';
				}
				$this->display_email_monitor_page( $cron_status );
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
		$old_settings = get_option( 'uwsmq_settings' );
		$new_settings = $old_settings;

		if ( $this->current_tab === 'settings' ) {
			$new_settings['smtp_host']   = sanitize_text_field( $_POST['smtp_host'] );
			$new_settings['smtp_port']   = sanitize_text_field( $_POST['smtp_port'] );
			$new_settings['smtp_auth']   = isset( $_POST['smtp_auth'] ) ? 'yes' : 'no';
			$new_settings['smtp_user']   = sanitize_text_field( $_POST['smtp_user'] );
			$new_settings['smtp_pass']   = sanitize_text_field( $_POST['smtp_pass'] );
			$new_settings['smtp_secure'] = sanitize_text_field( $_POST['smtp_secure'] );
			$new_settings['from_email']  = sanitize_email( $_POST['from_email'] );
			$new_settings['from_name']   = sanitize_text_field( $_POST['from_name'] );
		} elseif ( $this->current_tab === 'advanced' ) {
			$new_settings['enable_queue']   = isset( $_POST['enable_queue'] ) ? 'yes' : 'no';
			$new_settings['dont_use_wpcron'] = isset( $_POST['dont_use_wpcron'] ) ? 'yes' : 'no';
			$new_settings['batch_size']     = (int)$_POST['batch_size'];
			$new_settings['interval']       = (int)$_POST['interval'];
			$new_settings['secret_key']     = sanitize_text_field( $_POST['secret_key'] );
			$new_settings['log_limit']      = isset( $_POST['log_limit'] ) ? (int)$_POST['log_limit'] : 1000;
			
			update_option( 'uwsmq_settings', $new_settings );

			if ( $old_settings['interval'] != $new_settings['interval'] || $old_settings['dont_use_wpcron'] != $new_settings['dont_use_wpcron'] ) {
				wp_clear_scheduled_hook( 'uwsmq_process_queue_cron' );
				if ( $new_settings['dont_use_wpcron'] !== 'yes' ) {
					wp_schedule_event( time(), 'uwsmq_interval', 'uwsmq_process_queue_cron' );
				}
			}
		} else {
			update_option( 'uwsmq_settings', $new_settings );
		}
	}

	private function display_tools_page() {
		$subtabs = array(
			'test'    => 'Test Mail'
		);
		$current_subtab = $this->current_subtab;
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
		$sql .= " ORDER BY id DESC";
		
		$items = $wpdb->get_results( $sql );

		include UWSMQ_PLUGIN_DIR . 'admin/partials/uwsmq-email-monitor.php';
	}

	public function ajax_test_smtp() {
		check_ajax_referer( 'uwsmq_admin_nonce', 'nonce' );
		
		$to = sanitize_text_field( $_POST['test_email'] );
		$cc = sanitize_text_field( $_POST['test_cc'] );
		$bcc = sanitize_text_field( $_POST['test_bcc'] );
		$subject = sanitize_text_field( $_POST['test_subject'] );
		$message = wp_kses_post( $_POST['test_message'] );
		$direct_raw = $_POST['test_direct'];
		$direct = ( $direct_raw === 'true' || $direct_raw === '1' || $direct_raw === 1 || $direct_raw === true );

		$headers = array();
		if ( ! empty( $cc ) ) $headers[] = 'Cc: ' . $cc;
		if ( ! empty( $bcc ) ) $headers[] = 'Bcc: ' . $bcc;
		$headers[] = 'Content-Type: text/html; charset=UTF-8';

		$mailer = UWSMQ_Mailer::get_instance();
		
		if ( $direct ) {
			$mailer->force_direct_send( true );
		}

		$result = wp_mail( $to, $subject, $message, $headers );
		
		if ( $direct ) {
			$mailer->force_direct_send( false );
		}



		global $phpmailer_error;
		if ( $result ) {
			// Only log here if it was a direct send. If it was queued, UWSMQ_Queue::add_to_queue already logged it.
			if ( $direct ) {
				UWSMQ_Logs::add_log( $to, $subject, 'sent', '', 'direct', '', $headers, $message );
			}
			wp_send_json_success( array( 'message' => 'Email sent/queued successfully!' ) );
		} else {
			$error_msg = ! empty( $phpmailer_error ) ? $phpmailer_error : 'Failed to send email.';
			if ( $direct ) {
				UWSMQ_Logs::add_log( $to, $subject, 'failed', $error_msg, 'direct', '', $headers, $message );
			}
			wp_send_json_error( array( 'message' => $error_msg ) );
		}
	}

	public function ajax_process_queue() {
		check_ajax_referer( 'uwsmq_admin_nonce', 'nonce' );
		$mailer = UWSMQ_Mailer::get_instance();
		$mailer->process_queue();
		wp_send_json_success( array( 'message' => 'Queue processed successfully.' ) );
	}

	public function ajax_delete_item() {
		check_ajax_referer( 'uwsmq_admin_nonce', 'nonce' );
		$id = (int)$_POST['id'];
		UWSMQ_Queue::delete_item( $id );
		wp_send_json_success();
	}

	public function ajax_delete_log() {
		check_ajax_referer( 'uwsmq_admin_nonce', 'nonce' );
		$id = (int)$_POST['id'];
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'uwsmq_logs', array( 'id' => $id ) );
		wp_send_json_success();
	}

	public function ajax_bulk_action() {
		check_ajax_referer( 'uwsmq_admin_nonce', 'nonce' );
		$ids = isset( $_POST['ids'] ) ? array_map( 'intval', $_POST['ids'] ) : array();
		$action = sanitize_text_field( $_POST['bulk_action'] );

		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => 'No items selected.' ) );
		}

		global $wpdb;

		if ( $action === 'bulk-delete' ) {
			foreach ( $ids as $id ) {
				$wpdb->delete( $wpdb->prefix . 'uwsmq_logs', array( 'id' => $id ) );
				$wpdb->delete( $wpdb->prefix . 'uwsmq_queue', array( 'log_id' => $id ) );
			}
		} elseif ( $action === 'bulk-send' ) {
			$mailer = UWSMQ_Mailer::get_instance();
			$ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$qids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}uwsmq_queue WHERE log_id IN ($ids_placeholder)", $ids ) );
			if ( ! empty( $qids ) ) {
				$mailer->process_bulk_items( $qids );
			}
		}

		wp_send_json_success();
	}

	public function ajax_refresh_cron() {
		check_ajax_referer( 'uwsmq_admin_nonce', 'nonce' );
		wp_clear_scheduled_hook( 'uwsmq_process_queue_cron' );
		wp_schedule_event( time(), 'uwsmq_interval', 'uwsmq_process_queue_cron' );
		wp_send_json_success();
	}

	public function ajax_send_now() {
		check_ajax_referer( 'uwsmq_admin_nonce', 'nonce' );
		$log_id = (int)$_POST['id'];
		
		global $wpdb;
		$queue_item = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}uwsmq_queue WHERE log_id = %d", $log_id ) );
		
		$mailer = UWSMQ_Mailer::get_instance();
		if ( $queue_item ) {
			$mailer->process_bulk_items( array( $queue_item->id ) );
			wp_send_json_success();
		} else {
			// If not found in queue by log_id, it might be an old item or already sent
			wp_send_json_error( array( 'message' => 'Queue item not found or already processed.' ) );
		}
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
