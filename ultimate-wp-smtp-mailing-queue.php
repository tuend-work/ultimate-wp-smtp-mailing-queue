<?php
/**
 * Plugin Name: Ultimate WP SMTP Mailing Queue
 * Plugin URI: https://example.com/ultimate-wp-smtp-mailing-queue
 * Description: A professional SMTP mailing queue plugin for WordPress. Reliable delivery, batch processing, and premium admin UI.
 * Version: 2.0.2
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
define( 'UWSMQ_VERSION', '2.0.3' );
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

function run_ultimate_wp_smtp_mailing_queue() {
	$plugin = new UWSMQ_Core();
	$plugin->run();
}

run_ultimate_wp_smtp_mailing_queue();

// Override wp_mail if enabled
if ( ! function_exists( 'wp_mail' ) ) {
	function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
		$mailer = UWSMQ_Mailer::get_instance();
		return $mailer->handle_wp_mail( $to, $subject, $message, $headers, $attachments );
	}
}

