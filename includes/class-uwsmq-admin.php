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
		// Add Parent Menu "Ultimate WP" if it does not exist
		add_menu_page(
			'Ultimate WP Dashboard',
			'Ultimate WP',
			'manage_options',
			'ultimate-wp',
			'ultimate_wp_render_dashboard', // Call global function
			'dashicons-superhero',
			2.1
		);

		// Add Submenu for Dashboard
		add_submenu_page(
			'ultimate-wp',
			'Ultimate WP Dashboard',
			'Dashboard',
			'manage_options',
			'ultimate-wp',
			'ultimate_wp_render_dashboard'
		);

		// Add Submenu for SMTP Queue settings
		add_submenu_page(
			'ultimate-wp',
			__( 'SMTP Mailing Queue Settings', 'ultimate-wp-smtp-mailing-queue' ),
			__( 'SMTP Queue', 'ultimate-wp-smtp-mailing-queue' ),
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
			$new_settings['smtp_secure'] = sanitize_text_field( trim( wp_unslash( $_POST['smtp_secure'] ?? 'tls' ) ) );
			$new_settings['from_email']  = sanitize_email( trim( wp_unslash( $_POST['from_email'] ?? '' ) ) );
			$new_settings['from_name']   = sanitize_text_field( trim( wp_unslash( $_POST['from_name'] ?? '' ) ) );
		} elseif ( $this->current_tab === 'advanced' ) {
			$new_settings['enable_queue']    = isset( $_POST['enable_queue'] ) ? 'yes' : 'no';
			$new_settings['dont_use_wpcron'] = isset( $_POST['dont_use_wpcron'] ) ? 'yes' : 'no';
			$new_settings['batch_size']      = isset( $_POST['batch_size'] ) ? absint( $_POST['batch_size'] ) : 10;
			$new_settings['interval']        = isset( $_POST['interval'] ) ? absint( $_POST['interval'] ) : 300;
			$new_settings['secret_key']      = sanitize_text_field( trim( wp_unslash( $_POST['secret_key'] ?? '' ) ) );
			$new_settings['log_limit']       = isset( $_POST['log_limit'] ) ? absint( $_POST['log_limit'] ) : 1000;
			$new_settings['debug_mode']      = isset( $_POST['debug_mode'] ) ? 'yes' : 'no';

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
		
		if ( $direct ) {
			// Gửi trực tiếp và lấy debug transcript
			$result = $mailer->send_with_phpmailer( $to, $subject, $message, $headers, array(), true );
			
			global $phpmailer_error;
			if ( $result ) {
				UWSMQ_Logs::add_log( $to, $subject, 'sent', '', 'direct', '', $headers, $message );
				wp_send_json_success( array( 'message' => __( 'Email sent successfully (Direct)!', 'ultimate-wp-smtp-mailing-queue' ) ) );
			} else {
				$debug_transcript = $mailer->get_debug_output();
				$error_msg = ! empty( $phpmailer_error ) ? $phpmailer_error : __( 'Failed to send email.', 'ultimate-wp-smtp-mailing-queue' );
				
				if ( ! empty( $debug_transcript ) ) {
					$error_msg .= "\n\n--- SMTP DEBUG TRANSCRIPT ---\n" . $debug_transcript;
				}
				UWSMQ_Logs::add_log( $to, $subject, 'failed', $error_msg, 'direct', '', $headers, $message );
				wp_send_json_error( array( 'message' => $error_msg ) );
			}
		} else {
			$settings = get_option( 'uwsmq_settings' );
			$enable_queue = isset( $settings['enable_queue'] ) && $settings['enable_queue'] === 'yes';

			if ( ! $enable_queue ) {
				wp_send_json_error( array( 'message' => __( 'Email queueing is currently DISABLED. Please enable it in the "Advanced Settings" tab first or use "Send Direct".', 'ultimate-wp-smtp-mailing-queue' ) ) );
			}

			// Kiểm tra bảng Database có tồn tại không
			global $wpdb;
			$table_name = $wpdb->prefix . 'uwsmq_logs';
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
				// Nếu không thấy bảng, cố gắng tạo lại ngay lập tức
				require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-activator.php';
				UWSMQ_Activator::activate();
				
				// Kiểm tra lại lần nữa
				if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
					wp_send_json_error( array( 'message' => __( 'System Error: Database table is missing and could not be created automatically. Table name: ', 'ultimate-wp-smtp-mailing-queue' ) . $table_name . ' | DB Error: ' . $wpdb->last_error ) );
				}
			}

			// Ép buộc nạp vào hàng đợi (Queue) thông qua Mailer của plugin
			$atts = array(
				'to'          => $to,
				'subject'     => $subject,
				'message'     => $message,
				'headers'     => $headers,
				'attachments' => array()
			);
			$result = $mailer->pre_wp_mail_filter( null, $atts );
			
			if ( $result === true ) {
				wp_send_json_success( array( 'message' => __( 'Email has been added to the queue successfully (Direct Queue).', 'ultimate-wp-smtp-mailing-queue' ) ) );
			} else {
				$db_error = $wpdb->last_error;
				$msg = __( 'Failed to add email to queue.', 'ultimate-wp-smtp-mailing-queue' );
				if ( ! empty( $db_error ) ) {
					$msg .= ' DB Error: ' . $db_error;
				} else {
					$msg .= ' Please check if your database tables are created correctly and your PHP error log.';
				}
				wp_send_json_error( array( 'message' => $msg ) );
			}
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

/**
 * Global function to render the unified Ultimate WP Ecosystem Dashboard page.
 * Wrapped in function_exists to prevent conflicts if multiple ecosystem plugins define it.
 */
if ( ! function_exists( 'ultimate_wp_render_dashboard' ) ) {
    function ultimate_wp_render_dashboard() {
        global $_wp_admin_css_colors;
        $color_scheme = get_user_option( 'admin_color' );
        if ( empty( $color_scheme ) ) {
            $color_scheme = 'fresh';
        }

        $primary_color = '#6366f1';
        $primary_dark = '#4f46e5';
        $header_bg_start = '#1d2327';
        $header_bg_end = '#2c3338';

        if ( ! empty( $_wp_admin_css_colors ) && isset( $_wp_admin_css_colors[ $color_scheme ] ) ) {
            $colors = $_wp_admin_css_colors[ $color_scheme ]->colors;
            if ( isset( $colors[0] ) ) {
                $header_bg_start = $colors[0];
            }
            if ( isset( $colors[1] ) ) {
                $header_bg_end = $colors[1];
            }
            if ( isset( $colors[2] ) ) {
                $primary_color = $colors[2];
            }
            if ( isset( $colors[3] ) ) {
                $primary_dark = $colors[3];
            } else if ( isset( $colors[2] ) ) {
                $primary_dark = $colors[2];
            }
        }

        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        $ecosystem_plugins = array(
            'ultimate-wp-booster' => array(
                'name'         => 'Ultimate WP Booster',
                'description'  => 'Tối ưu hóa tốc độ tải trang toàn diện, dọn dẹp và tối ưu hóa cơ sở dữ liệu, nén ảnh, gộp và nén CSS/JS, tích hợp Redis Cache.',
                'path'         => 'ultimate-wp-booster/ultimate-wp-booster.php',
                'settings_url' => admin_url( 'options-general.php?page=ultimate-wp-booster' ),
            ),
            'ultimate-wp-flatsome' => array(
                'name'         => 'Ultimate WP Flatsome',
                'description'  => 'Mở rộng khả năng thiết kế của Flatsome. Cho phép sử dụng UX Builder kéo thả layout trực tiếp cho taxonomy và single page của custom post types.',
                'path'         => 'ultimate-wp-flatsome/ultimate-wp-flatsome.php',
                'settings_url' => admin_url( 'admin.php?page=ultimate-wp-flatsome' ),
            ),
            'ultimate-wp-smtp-queue' => array(
                'name'         => 'Ultimate WP SMTP Queue',
                'description'  => 'Cấu hình gửi email qua giao thức SMTP chuyên nghiệp kết hợp hệ thống hàng đợi gửi ngầm chạy nền (Queue) hiệu năng cao, giảm tải máy chủ.',
                'path'         => 'ultimate-wp-smtp-queue/ultimate-wp-smtp-queue.php',
                'settings_url' => admin_url( 'options-general.php?page=ultimate-wp-smtp-queue' ),
            ),
        );
        ?>
        <style>
            :root {
                --uwp-primary: <?php echo esc_attr( $primary_color ); ?>;
                --uwp-primary-dark: <?php echo esc_attr( $primary_dark ); ?>;
                --uwp-success: #10b981;
                --uwp-warning: #f59e0b;
                --uwp-danger: #ef4444;
                --uwp-bg: #f8fafc;
                --uwp-card-bg: #ffffff;
                --uwp-text: #1e293b;
                --uwp-text-muted: #64748b;
                --uwp-border: #e2e8f0;
            }

            .uwp-dashboard-wrap {
                margin: 20px 20px 0 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                color: var(--uwp-text);
            }

            /* Header Section */
            .uwp-header {
                background: linear-gradient(135deg, <?php echo esc_attr( $header_bg_start ); ?>, <?php echo esc_attr( $header_bg_end ); ?>);
                color: #ffffff;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                margin-bottom: 30px;
                position: relative;
                overflow: hidden;
            }

            .uwp-header::after {
                content: '';
                position: absolute;
                top: -50%;
                right: -20%;
                width: 300px;
                height: 300px;
                background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
                border-radius: 50%;
            }

            .uwp-header h1 {
                margin: 0 0 10px 0;
                font-size: 2.2rem;
                font-weight: 700;
                letter-spacing: -0.5px;
                color: #ffffff;
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .uwp-header h1 span {
                background: linear-gradient(to right, #a5b4fc, #818cf8);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            .uwp-header p {
                margin: 0;
                font-size: 1.1rem;
                color: #e2e8f0;
                max-width: 600px;
                line-height: 1.6;
            }

            /* Grid Layout */
            .uwp-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 24px;
                margin-bottom: 40px;
            }

            /* Card Style */
            .uwp-card {
                background: var(--uwp-card-bg);
                border: 1px solid var(--uwp-border);
                border-radius: 12px;
                padding: 30px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                position: relative;
            }

            .uwp-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 12px 20px rgba(0, 0, 0, 0.05);
                border-color: var(--uwp-primary);
            }

            .uwp-card-title {
                font-size: 1.4rem;
                font-weight: 600;
                margin: 0 0 15px 0;
                color: var(--uwp-text);
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .uwp-card-desc {
                font-size: 0.95rem;
                color: var(--uwp-text-muted);
                line-height: 1.6;
                margin-bottom: 25px;
                flex-grow: 1;
            }

            /* Badges */
            .uwp-status {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 0.8rem;
                font-weight: 600;
                padding: 4px 12px;
                border-radius: 9999px;
            }

            .uwp-status-active {
                background-color: #d1fae5;
                color: #065f46;
            }

            .uwp-status-inactive {
                background-color: #fef3c7;
                color: #92400e;
            }

            .uwp-status-notinstalled {
                background-color: #f1f5f9;
                color: #475569;
            }

            .uwp-status-dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background-color: currentColor;
            }

            /* Buttons */
            .uwp-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                font-weight: 500;
                font-size: 0.9rem;
                padding: 10px 20px;
                border-radius: 8px;
                text-decoration: none;
                transition: all 0.2s ease;
                cursor: pointer;
                border: none;
                width: 100%;
                text-align: center;
            }

            .uwp-btn-primary {
                background-color: var(--uwp-primary);
                color: #ffffff;
            }

            .uwp-btn-primary:hover {
                background-color: var(--uwp-primary-dark);
                color: #ffffff;
            }

            .uwp-btn-secondary {
                background-color: #f1f5f9;
                color: #0f172a;
            }

            .uwp-btn-secondary:hover {
                background-color: #e2e8f0;
                color: #0f172a;
            }

            .uwp-btn-disabled {
                background-color: #f8fafc;
                color: #94a3b8;
                cursor: not-allowed;
                border: 1px dashed var(--uwp-border);
            }

            /* Info Box */
            .uwp-info-box {
                background-color: #f8fafc;
                border: 1px solid var(--uwp-border);
                border-radius: 12px;
                padding: 30px;
                margin-top: 40px;
            }

            .uwp-info-box h3 {
                margin-top: 0;
                font-size: 1.2rem;
                font-weight: 600;
            }

            .uwp-info-box p {
                color: var(--uwp-text-muted);
                line-height: 1.6;
                margin-bottom: 0;
            }
        </style>

        <div class="uwp-dashboard-wrap">
            <!-- Header -->
            <div class="uwp-header">
                <h1><span>Ultimate WP</span> Ecosystem</h1>
                <p>Hệ sinh thái các plugin tối ưu hóa và mở rộng tính năng chuyên nghiệp dành cho WordPress và theme Flatsome của bạn.</p>
            </div>

            <!-- Grid Plugins -->
            <div class="uwp-grid">
                <?php
                foreach ( $ecosystem_plugins as $slug => $data ) {
                    $is_installed = file_exists( WP_PLUGIN_DIR . '/' . $data['path'] );
                    $is_active = $is_installed && is_plugin_active( $data['path'] );

                    if ( $slug === 'ultimate-wp-flatsome' ) {
                        $settings_url = admin_url( 'admin.php?page=ultimate-wp-flatsome' );
                    } else if ( $slug === 'ultimate-wp-booster' && $is_active ) {
                        // Check if booster is updated to submenus
                        $settings_url = admin_url( 'options-general.php?page=ultimate-wp-booster' );
                    } else {
                        $settings_url = $data['settings_url'];
                    }
                    ?>
                    <div class="uwp-card">
                        <div>
                            <div class="uwp-card-title">
                                <?php echo esc_html( $data['name'] ); ?>
                                <?php if ( $is_active ) : ?>
                                    <span class="uwp-status uwp-status-active">
                                        <span class="uwp-status-dot"></span> Đang hoạt động
                                    </span>
                                <?php elseif ( $is_installed ) : ?>
                                    <span class="uwp-status uwp-status-inactive">
                                        <span class="uwp-status-dot"></span> Chưa kích hoạt
                                    </span>
                                <?php else : ?>
                                    <span class="uwp-status uwp-status-notinstalled">
                                        Chưa cài đặt
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="uwp-card-desc">
                                <?php echo esc_html( $data['description'] ); ?>
                            </div>
                        </div>

                        <div class="uwp-card-actions">
                            <?php if ( $is_active ) : ?>
                                <a href="<?php echo esc_url( $settings_url ); ?>" class="uwp-btn uwp-btn-primary">
                                    <span class="dashicons dashicons-admin-settings" style="font-size:17px; line-height:22px; margin-right:4px;"></span> Cấu hình ngay
                                </a>
                            <?php elseif ( $is_installed ) : ?>
                                <?php
                                $activate_url = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $data['path'] ), 'activate-plugin_' . $data['path'] );
                                ?>
                                <a href="<?php echo esc_url( $activate_url ); ?>" class="uwp-btn uwp-btn-secondary" style="background-color: #fef3c7; color: #d97706;">
                                    <span class="dashicons dashicons-admin-plugins" style="font-size:17px; line-height:22px; margin-right:4px;"></span> Kích hoạt Plugin
                                </a>
                            <?php else : ?>
                                <button class="uwp-btn uwp-btn-disabled" disabled>
                                    Chưa cài đặt Plugin
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>

            <!-- Ecosystem Info -->
            <div class="uwp-info-box">
                <h3>Về hệ sinh thái Ultimate WP Plugins</h3>
                <p>Hệ sinh thái Ultimate WP được xây dựng với mục tiêu mang lại hiệu năng cao nhất, giao diện trực quan thân thiện và khả năng tương thích tuyệt vời cho các website chạy mã nguồn WordPress và Flatsome. Toàn bộ các plugin đều được tối ưu hóa sâu ở mức mã nguồn để đảm bảo tốc độ tải trang nhanh nhất.</p>
            </div>
        </div>
        <?php
    }
}
