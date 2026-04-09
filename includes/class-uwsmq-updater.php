<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UWSMQ_Updater {

	public function __construct() {
		add_action( 'admin_notices', array( $this, 'render_update_button' ) );
		add_action( 'admin_init', array( $this, 'handle_update' ) );
		add_action( 'admin_notices', array( $this, 'update_success_notice' ) );
	}

	public function render_update_button() {
		$screen = get_current_screen();
		// The page slug for add_options_page is 'settings_page_ultimate-wp-smtp-mailing-queue'
		if ( 'settings_page_ultimate-wp-smtp-mailing-queue' !== $screen->id ) {
			return;
		}

		$update_url = wp_nonce_url( admin_url( 'options-general.php?page=ultimate-wp-smtp-mailing-queue&uwsmq_action=update' ), 'uwsmq_update_nonce' );
		?>
		<div class="notice notice-warning is-dismissible" style="border-left-color: #6366f1; display: flex; align-items: center; justify-content: space-between; padding: 10px 20px;">
			<div style="font-weight: 600;">
				🚀 Ultimate WP SMTP Mailing Queue Auto-Updater
			</div>
			<div>
				<a href="<?php echo esc_url( $update_url ); ?>" class="button button-primary" style="background: #6366f1; border-color: #6366f1;">
					<span class="dashicons dashicons-cloud-download" style="margin-top: 4px;"></span> 
					AUTO-UPDATE FROM GITHUB NOW
				</a>
			</div>
		</div>
		<?php
	}

	public function handle_update() {
		if ( ! isset( $_GET['uwsmq_action'] ) || 'update' !== $_GET['uwsmq_action'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'uwsmq_update_nonce' ) ) {
			wp_die( 'Unauthorized action.' );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		
		$github_zip_url = 'https://github.com/tuend-work/ultimate-wp-smtp-mailing-queue/archive/refs/heads/main.zip';
		$temp_file = download_url( $github_zip_url );

		if ( is_wp_error( $temp_file ) ) {
			wp_die( 'Download failed: ' . $temp_file->get_error_message() );
		}

		// Initialize Filesystem
		WP_Filesystem();
		global $wp_filesystem;

		$destination = UWSMQ_PLUGIN_DIR;
		$temp_dir = UWSMQ_PLUGIN_DIR . 'temp_update/';
		
		// Ensure temp dir exists and is clean
		$wp_filesystem->delete( $temp_dir, true );
		$wp_filesystem->mkdir( $temp_dir );

		// Unzip to temp folder
		$unzipped = unzip_file( $temp_file, $temp_dir );
		unlink( $temp_file );

		if ( is_wp_error( $unzipped ) ) {
			$wp_filesystem->delete( $temp_dir, true );
			wp_die( 'Unzip failed: ' . $unzipped->get_error_message() );
		}

		// GitHub zips have a subfolder like 'ultimate-wp-smtp-mailing-queue-main'
		$contents = $wp_filesystem->dirlist( $temp_dir );
		if ( ! empty( $contents ) ) {
			$inner_dir_name = key( $contents );
			$inner_dir_path = $temp_dir . $inner_dir_name . '/';
			
			// Move contents from inner dir to our plugin root
			copy_dir( $inner_dir_path, $destination );
		}

		// Cleanup
		$wp_filesystem->delete( $temp_dir, true );

		// Redirect back with success flag
		wp_safe_redirect( admin_url( 'options-general.php?page=ultimate-wp-smtp-mailing-queue&uwsmq_updated=1' ) );
		exit;
	}

	public function update_success_notice() {
		if ( isset( $_GET['uwsmq_updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>Plugin updated successfully from GitHub!</p></div>';
		}
	}
}
