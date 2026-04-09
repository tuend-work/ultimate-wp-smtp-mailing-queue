<?php
/**
 * Plugin Name: Ultimate WP SMTP Mailing Queue
 * Plugin URI: https://example.com/ultimate-wp-smtp-mailing-queue
 * Description: A professional SMTP mailing queue plugin for WordPress. Reliable delivery, batch processing, and premium admin UI.
 * Version: 1.0.9
 * Author: Antigravity
 * Author URI: https://antigravity.google
 * License: GPL2
 * Text Domain: ultimate-wp-smtp-mailing-queue
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'UWSMQ_VERSION', '1.0.9' );
define( 'UWSMQ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UWSMQ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'UWSMQ_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-uwsmq-activator.php
 */
function activate_ultimate_wp_smtp_mailing_queue() {
	require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-activator.php';
	UWSMQ_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-uwsmq-deactivator.php
 */
function deactivate_ultimate_wp_smtp_mailing_queue() {
	require_once UWSMQ_PLUGIN_DIR . 'includes/class-uwsmq-deactivator.php';
	UWSMQ_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ultimate_wp_smtp_mailing_queue' );
register_deactivation_hook( __FILE__, 'deactivate_ultimate_wp_smtp_mailing_queue' );

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
