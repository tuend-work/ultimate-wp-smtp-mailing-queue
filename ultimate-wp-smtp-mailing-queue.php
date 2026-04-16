<?php
/**
 * Plugin Name: Ultimate WP SMTP Mailing Queue
 * Plugin URI: https://example.com/ultimate-wp-smtp-mailing-queue
 * Description: A professional SMTP mailing queue plugin for WordPress. Reliable delivery, batch processing, and premium admin UI.
 * Version: 2.2.0
 * Author: Nguyễn Đức Tuệ
 * Author URI: https://antigravity.google
 * License: GPL2
 * Text Domain: ultimate-wp-smtp-mailing-queue
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'UWSMQ_VERSION', '2.2.0' );
define( 'UWSMQ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UWSMQ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'UWSMQ_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Activation / Deactivation hooks
 */
function activate_ultimate_wp_smtp_mailing_queue() {
	require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-activator.php';
	UWSMQ_Activator::activate();
	set_transient( 'uwsmq_activation_redirect', true, 30 );
}
function deactivate_ultimate_wp_smtp_mailing_queue() {
	require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-deactivator.php';
	UWSMQ_Deactivator::deactivate();
}
register_activation_hook( __FILE__, 'activate_ultimate_wp_smtp_mailing_queue' );
register_deactivation_hook( __FILE__, 'deactivate_ultimate_wp_smtp_mailing_queue' );

/**
 * Bootstrap: load dependencies & register SMTP/mail hooks as early as possible.
 * Uses plugins_loaded priority 1 so we run before most other plugins.
 */
add_action( 'plugins_loaded', 'uwsmq_bootstrap', 1 );
function uwsmq_bootstrap() {
	// Load all required classes
	require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-attachments.php';
	require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-logs.php';
	require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-mailer.php';
	require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-core.php';

	// Register mail-related hooks immediately (before any other plugin can conflict)
	$mailer = UWSMQ_Mailer::get_instance();
	add_filter( 'pre_wp_mail',    array( $mailer, 'pre_wp_mail_filter' ), 1, 2 );
	add_action( 'phpmailer_init', array( $mailer, 'init_smtp' ) );
	add_action( 'wp_mail_failed', array( $mailer, 'log_wp_mail_failed' ) );

	// Boot the rest of the plugin (admin UI, cron, etc.)
	$core = new UWSMQ_Core();
	$core->run();
}

/**
 * Text domain
 */
add_action( 'plugins_loaded', 'uwsmq_load_textdomain', 2 );
function uwsmq_load_textdomain() {
	load_plugin_textdomain( 'ultimate-wp-smtp-mailing-queue', false, dirname( UWSMQ_BASENAME ) . '/languages' );
}

/**
 * Settings link on plugins page
 */
add_filter( 'plugin_action_links_' . UWSMQ_BASENAME, 'uwsmq_plugin_action_links' );
function uwsmq_plugin_action_links( $links ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=ultimate-wp-smtp-mailing-queue' ) . '">' . __( 'Settings', 'ultimate-wp-smtp-mailing-queue' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

/**
 * Redirect to settings after activation
 */
add_action( 'admin_init', 'uwsmq_activation_redirect' );
function uwsmq_activation_redirect() {
	if ( get_transient( 'uwsmq_activation_redirect' ) ) {
		delete_transient( 'uwsmq_activation_redirect' );
		if ( ! isset( $_GET['activate-multi'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ultimate-wp-smtp-mailing-queue' ) );
			exit;
		}
	}
}
