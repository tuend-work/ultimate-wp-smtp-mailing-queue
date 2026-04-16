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
 * Load plugin text domain for translations.
 */
function uwsmq_load_textdomain() {
	load_plugin_textdomain( 'ultimate-wp-smtp-mailing-queue', false, dirname( UWSMQ_BASENAME ) . '/languages' );
}
add_action( 'plugins_loaded', 'uwsmq_load_textdomain' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-uwsmq-activator.php
 */
function activate_ultimate_wp_smtp_mailing_queue() {
	require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-activator.php';
	UWSMQ_Activator::activate();
	// Set flag to redirect to settings after activation
	set_transient( 'uwsmq_activation_redirect', true, 30 );
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_ultimate_wp_smtp_mailing_queue() {
	require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-deactivator.php';
	UWSMQ_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ultimate_wp_smtp_mailing_queue' );
register_deactivation_hook( __FILE__, 'deactivate_ultimate_wp_smtp_mailing_queue' );

/**
 * Add "Settings" link on the Plugins list page.
 */
function uwsmq_plugin_action_links( $links ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=ultimate-wp-smtp-mailing-queue' ) . '">' . __( 'Settings', 'ultimate-wp-smtp-mailing-queue' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . UWSMQ_BASENAME, 'uwsmq_plugin_action_links' );

/**
 * Redirect to settings page after activation.
 */
function uwsmq_activation_redirect() {
	if ( get_transient( 'uwsmq_activation_redirect' ) ) {
		delete_transient( 'uwsmq_activation_redirect' );
		if ( ! isset( $_GET['activate-multi'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ultimate-wp-smtp-mailing-queue' ) );
			exit;
		}
	}
}
add_action( 'admin_init', 'uwsmq_activation_redirect' );

/**
 * Core loader class.
 */
require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-core.php';

/**
 * Bootstrap: runs at plugins_loaded priority 5.
 * Loads dependencies and registers mail hooks BEFORE CF7/WooCommerce (which run at default priority 10).
 */
function uwsmq_bootstrap() {
	// Load all required classes
	require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-attachments.php';
	require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-logs.php';
	require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-mailer.php';

	// Register SMTP/mail hooks directly here — guaranteed to run before most plugins
	$mailer = UWSMQ_Mailer::get_instance();
	add_filter( 'pre_wp_mail',    array( $mailer, 'pre_wp_mail_filter' ), 1, 2 );
	add_action( 'phpmailer_init', array( $mailer, 'init_smtp' ) );
	add_action( 'wp_mail_failed', array( $mailer, 'log_wp_mail_failed' ) );

	// Log that bootstrap ran (for debugging)
	UWSMQ_Mailer::static_flog( 'BOOTSTRAP: Plugin loaded. Mail hooks registered. request_uri=' . ( $_SERVER['REQUEST_URI'] ?? 'n/a' ) . ' | action=' . ( $_REQUEST['action'] ?? 'n/a' ) );

	// Boot admin UI, cron, etc.
	$core = new UWSMQ_Core();
	$core->run();
}
add_action( 'plugins_loaded', 'uwsmq_bootstrap', 5 );
