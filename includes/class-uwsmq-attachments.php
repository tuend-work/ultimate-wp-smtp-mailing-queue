<?php

class UWSMQ_Attachments {

	public static function store_attachments( $attachments ) {
		if ( empty( $attachments ) ) {
			return array();
		}

		$stored = array();
		$upload_dir = wp_upload_dir();
		$base_dir = $upload_dir['basedir'] . '/uwsmq-attachments';

		if ( ! file_exists( $base_dir ) ) {
			wp_mkdir_p( $base_dir );
			// Add htaccess for security
			file_put_contents( $base_dir . '/.htaccess', 'DENY FROM ALL' );
		}

		foreach ( (array)$attachments as $attachment ) {
			if ( ! file_exists( $attachment ) ) {
				continue;
			}

			$filename = basename( $attachment );
			$sub_dir = $base_dir . '/' . uniqid();
			wp_mkdir_p( $sub_dir );
			
			$new_path = $sub_dir . '/' . $filename;
			if ( copy( $attachment, $new_path ) ) {
				$stored[] = $new_path;
			}
		}

		return $stored;
	}

	public static function delete_attachments( $attachments ) {
		$attachments = maybe_unserialize( $attachments );
		if ( empty( $attachments ) ) {
			return;
		}

		foreach ( (array)$attachments as $path ) {
			if ( file_exists( $path ) ) {
				unlink( $path );
				// Try to delete parent dir if empty
				$dir = dirname( $path );
				$files = glob( $dir . '/*' );
				if ( empty( $files ) ) {
					rmdir( $dir );
				}
			}
		}
	}
}
