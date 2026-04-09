<?php

class UWSMQ_Admin {

	private $current_tab;

	public function __construct() {
		$this->current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'settings';
	}

	public function add_plugin_admin_menu() {
		add_options_page(
			'Ultimate SMTP Mailing Queue',
			'Ultimate SMTP Queue',
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
			'settings' => 'SMTP Settings',
			'advanced' => 'Advanced Settings',
			'queue'    => 'Queue Management'
		);

		if ( isset( $_POST['uwsmq_save_settings'] ) && check_admin_referer( 'uwsmq_save_settings_action', 'uwsmq_save_settings_nonce' ) ) {
			$this->save_settings();
			echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
		}

		$settings = get_option( 'uwsmq_settings' );
		
		echo '<div class="wrap">';
		echo '<h1>Ultimate WP SMTP Mailing Queue</h1>';
		
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
			case 'queue':
				$this->display_queue_page();
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
			$new_settings['enable_queue'] = isset( $_POST['enable_queue'] ) ? 'yes' : 'no';
			$new_settings['batch_size']   = (int)$_POST['batch_size'];
			$new_settings['interval']     = (int)$_POST['interval'];
			
			// Reschedule CRON if interval changed
			if ( $old_settings['interval'] != $new_settings['interval'] ) {
				wp_clear_scheduled_hook( 'uwsmq_process_queue_cron' );
				wp_schedule_event( time(), 'uwsmq_interval', 'uwsmq_process_queue_cron' );
			}
		}

		update_option( 'uwsmq_settings', $new_settings );
	}

	public function display_queue_page() {
		global $wpdb;
		$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'pending';
		$table_name = $wpdb->prefix . 'uwsmq_queue';
		
		$items = $wpdb->get_results( $wpdb->prepare( 
			"SELECT * FROM $table_name WHERE status = %s ORDER BY created_at DESC LIMIT 100", 
			$status_filter 
		) );
		
		include UWSMQ_PLUGIN_DIR . 'admin/partials/uwsmq-queue-display.php';
	}

	public function ajax_test_smtp() {
		check_ajax_referer( 'uwsmq_admin_nonce', 'nonce' );
		
		$to = sanitize_email( $_POST['test_email'] );
		if ( ! is_email( $to ) ) {
			wp_send_json_error( array( 'message' => 'Invalid email address.' ) );
		}

		$mailer = UWSMQ_Mailer::get_instance();
		$result = $mailer->handle_wp_mail( $to, 'Ultimate SMTP Test Email', 'This is a test email.', '', array() );

		if ( $result ) {
			if ( get_option( 'uwsmq_settings' )['enable_queue'] === 'yes' ) {
				$mailer->process_queue();
			}
			wp_send_json_success( array( 'message' => 'Test email sent successfully!' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to send test email.' ) );
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
}
