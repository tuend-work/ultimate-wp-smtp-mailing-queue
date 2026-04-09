<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UWSMQ_Core {

	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->plugin_name = 'ultimate-wp-smtp-mailing-queue';
		$this->version = UWSMQ_VERSION;

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies() {
		require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-attachments.php';
		require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-logs.php';
		require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-mailer.php';
		require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-admin.php';
		require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-updater.php';
	}

	private function define_admin_hooks() {
		new UWSMQ_Updater();
		$admin = new UWSMQ_Admin();
		add_action( 'admin_menu', array( $admin, 'add_plugin_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_uwsmq_test_smtp', array( $admin, 'ajax_test_smtp' ) );
		add_action( 'wp_ajax_uwsmq_delete_log', array( $admin, 'ajax_delete_log' ) );
		add_action( 'wp_ajax_uwsmq_bulk_action', array( $admin, 'ajax_bulk_action' ) );
		add_action( 'wp_ajax_uwsmq_refresh_cron', array( $admin, 'ajax_refresh_cron' ) );
		add_action( 'wp_ajax_uwsmq_send_now', array( $admin, 'ajax_send_now' ) );
		add_action( 'wp_ajax_uwsmq_process_queue', array( $admin, 'ajax_process_queue' ) );
		add_action( 'wp_ajax_uwsmq_delete_item', array( $admin, 'ajax_delete_item' ) );
	}

	private function define_public_hooks() {
		add_action( 'init', array( $this, 'heartbeat' ) );
		add_action( 'init', array( $this, 'check_external_process' ) );
		add_filter( 'cron_schedules', array( $this, 'add_cron_intervals' ) );
		add_action( 'uwsmq_process_queue_cron', array( $this, 'process_queue' ) );
		add_action( 'uwsmq_maintenance_cron', array( 'UWSMQ_Logs', 'rotate_logs' ) );
		
		// Mailer hooks
		$mailer = UWSMQ_Mailer::get_instance();
		add_filter( 'pre_wp_mail', array( $mailer, 'pre_wp_mail_filter' ), 10, 2 );
		add_action( 'phpmailer_init', array( $mailer, 'init_smtp' ) );
	}

	public function heartbeat() {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		$settings = get_option( 'uwsmq_settings' );
		$interval = isset( $settings['interval'] ) ? (int) $settings['interval'] : 300;
		$last_run = get_transient( 'uwsmq_last_heartbeat_run' );

		if ( false === $last_run ) {
			$this->process_queue();
			set_transient( 'uwsmq_last_heartbeat_run', time(), $interval );
		}
	}

	public function add_cron_intervals( $schedules ) {
		$settings = get_option( 'uwsmq_settings' );
		$interval = isset( $settings['interval'] ) ? (int) $settings['interval'] : 300;
		
		$schedules['uwsmq_interval'] = array(
			'interval' => $interval,
			'display'  => esc_html__( 'UWSMQ Interval', 'ultimate-wp-smtp-mailing-queue' ),
		);
		return $schedules;
	}

	public function process_queue() {
		$mailer = UWSMQ_Mailer::get_instance();
		$mailer->process_queue();
	}

	public function check_external_process() {
		if ( ! isset( $_GET['smqProcessQueue'] ) ) {
			return;
		}

		$settings = get_option( 'uwsmq_settings' );
		$key = isset( $settings['secret_key'] ) ? $settings['secret_key'] : '';
		$provided_key = isset( $_GET['key'] ) ? $_GET['key'] : '';
		
		if ( empty( $key ) || empty( $provided_key ) || ! hash_equals( $key, $provided_key ) ) {
			header( 'HTTP/1.0 403 Forbidden' );
			wp_die( 'Invalid process key.' );
		}

		// Prevent caching
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );

		update_option( 'uwsmq_last_external_run', time() );

		$this->process_queue();
		echo 'UWSMQ Queue processed at ' . esc_html( current_time( 'mysql' ) );
		exit;
	}

	public function run() {
		if ( is_admin() ) {
			$db_version = get_option( 'uwsmq_db_version', '0' );
			if ( version_compare( $db_version, UWSMQ_VERSION, '<' ) ) {
				require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-activator.php';
				UWSMQ_Activator::activate();
				update_option( 'uwsmq_db_version', UWSMQ_VERSION );
			}
		}
	}
}
