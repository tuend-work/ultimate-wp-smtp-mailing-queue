<?php

class UWSMQ_Core {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->plugin_name = 'ultimate-wp-smtp-mailing-queue';
		$this->version = '1.2.1';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies() {
		require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-attachments.php';
		require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-logs.php';
		require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-queue.php';
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
		add_action( 'wp_ajax_uwsmq_process_queue', array( $admin, 'ajax_process_queue' ) );
		add_action( 'wp_ajax_uwsmq_delete_item', array( $admin, 'ajax_delete_item' ) );
	}

	private function define_public_hooks() {
		add_action( 'init', array( $this, 'check_external_process' ) );
		add_filter( 'cron_schedules', array( $this, 'add_cron_intervals' ) );
		add_action( 'uwsmq_process_queue_cron', array( $this, 'process_queue' ) );
		add_action( 'uwsmq_maintenance_cron', array( 'UWSMQ_Logs', 'rotate_logs' ) );
		
		// Mailer hooks
		$mailer = UWSMQ_Mailer::get_instance();
		add_filter( 'pre_wp_mail', array( $mailer, 'pre_wp_mail_filter' ), 10, 2 );
		add_action( 'phpmailer_init', array( $mailer, 'init_smtp' ) );
	}

	public function add_cron_intervals( $schedules ) {
		$settings = get_option( 'uwsmq_settings' );
		$interval = isset( $settings['interval'] ) ? (int)$settings['interval'] : 300;
		
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
		if ( isset( $_GET['smqProcessQueue'] ) ) {
			$settings = get_option( 'uwsmq_settings' );
			$key = isset( $settings['secret_key'] ) ? $settings['secret_key'] : '';
			
			if ( empty( $key ) || ! isset( $_GET['key'] ) || $_GET['key'] !== $key ) {
				wp_die( 'Invalid process key.' );
			}

			$this->process_queue();
			echo 'Queue processed.';
			exit;
		}
	}

	public function run() {
		// Ensure database tables exist
		if ( is_admin() ) {
			require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-activator.php';
			UWSMQ_Activator::activate();
		}
	}
}
