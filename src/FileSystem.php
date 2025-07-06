<?php
/**
 * FileSystem Utility Class
 *
 * Provides WordPress filesystem integration and advanced file operations
 * with proper handling of different filesystem types and security.
 *
 * @package ArrayPress\FileUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\FileUtils;

use WP_Filesystem_Base;
use WP_Filesystem_Direct;

/**
 * FileSystem Class
 *
 * WordPress filesystem integration providing compatibility with
 * different filesystem types and WordPress-aware file operations.
 */
class FileSystem {

	/**
	 * Initialize WordPress filesystem.
	 *
	 * @return bool True on success.
	 */
	public static function init(): bool {
		global $wp_filesystem;

		if ( ! empty( $wp_filesystem ) ) {
			return true;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		return WP_Filesystem();
	}

	/**
	 * Get WordPress filesystem instance.
	 *
	 * @return WP_Filesystem_Base|null Filesystem instance or null on failure.
	 */
	public static function get_filesystem(): ?WP_Filesystem_Base {
		global $wp_filesystem;

		if ( ! empty( $wp_filesystem ) ) {
			return $wp_filesystem;
		}

		if ( self::init() ) {
			return $wp_filesystem;
		}

		return null;
	}

	/**
	 * Check if using direct filesystem.
	 *
	 * @return bool True if using direct filesystem.
	 */
	public static function is_direct(): bool {
		$fs = self::get_filesystem();

		return $fs instanceof WP_Filesystem_Direct;
	}

	/**
	 * Get file contents using WordPress filesystem.
	 *
	 * @param string $path File path.
	 *
	 * @return string|null File contents or null on failure.
	 */
	public static function get_contents( string $path ): ?string {
		$path = Security::sanitize_path( $path );

		if ( self::is_direct() ) {
			return file_get_contents( $path ) ?: null;
		}

		$fs = self::get_filesystem();
		if ( ! $fs ) {
			return null;
		}

		$contents = $fs->get_contents( $path );

		return $contents !== false ? $contents : null;
	}

	/**
	 * Put contents to file using WordPress filesystem.
	 *
	 * @param string $path     File path.
	 * @param string $contents Contents to write.
	 *
	 * @return bool True on success.
	 */
	public static function put_contents( string $path, string $contents ): bool {
		$path = Security::sanitize_path( $path );

		if ( self::is_direct() ) {
			return file_put_contents( $path, $contents ) !== false;
		}

		$fs = self::get_filesystem();
		if ( ! $fs ) {
			return false;
		}

		return $fs->put_contents( $path, $contents );
	}

	/**
	 * Check if file exists using WordPress filesystem.
	 *
	 * @param string $path File path.
	 *
	 * @return bool True if file exists.
	 */
	public static function exists( string $path ): bool {
		$path = Security::sanitize_path( $path );

		if ( self::is_direct() ) {
			return file_exists( $path );
		}

		$fs = self::get_filesystem();
		if ( ! $fs ) {
			return false;
		}

		return $fs->exists( $path );
	}

	/**
	 * Get file size using WordPress filesystem.
	 *
	 * @param string $path File path.
	 *
	 * @return int|null File size in bytes or null on failure.
	 */
	public static function get_size( string $path ): ?int {
		$path = Security::sanitize_path( $path );

		if ( self::is_direct() ) {
			$size = filesize( $path );

			return $size !== false ? $size : null;
		}

		$fs = self::get_filesystem();
		if ( ! $fs ) {
			return null;
		}

		$size = $fs->size( $path );

		return $size !== false ? $size : null;
	}

	/**
	 * Get file modification time using WordPress filesystem.
	 *
	 * @param string $path File path.
	 *
	 * @return int|null Modification time or null on failure.
	 */
	public static function get_mtime( string $path ): ?int {
		$path = Security::sanitize_path( $path );

		if ( self::is_direct() ) {
			$time = filemtime( $path );

			return $time !== false ? $time : null;
		}

		$fs = self::get_filesystem();
		if ( ! $fs ) {
			return null;
		}

		$time = $fs->mtime( $path );

		return $time !== false ? $time : null;
	}

	/**
	 * Get file as array using WordPress filesystem.
	 *
	 * @param string $path File path.
	 *
	 * @return array|null File lines as array or null on failure.
	 */
	public static function get_file_array( string $path ): ?array {
		$path = Security::sanitize_path( $path );

		if ( self::is_direct() ) {
			$lines = file( $path );

			return $lines !== false ? $lines : null;
		}

		$fs = self::get_filesystem();
		if ( ! $fs ) {
			return null;
		}

		$lines = $fs->get_contents_array( $path );

		return $lines !== false ? $lines : null;
	}

	/**
	 * Copy file using WordPress filesystem.
	 *
	 * @param string $source      Source file path.
	 * @param string $destination Destination file path.
	 *
	 * @return bool True on success.
	 */
	public static function copy( string $source, string $destination ): bool {
		$source      = Security::sanitize_path( $source );
		$destination = Security::sanitize_path( $destination );

		if ( self::is_direct() ) {
			return copy( $source, $destination );
		}

		$fs = self::get_filesystem();
		if ( ! $fs ) {
			return false;
		}

		return $fs->copy( $source, $destination );
	}

	/**
	 * Move file using WordPress filesystem.
	 *
	 * @param string $source      Source file path.
	 * @param string $destination Destination file path.
	 *
	 * @return bool True on success.
	 */
	public static function move( string $source, string $destination ): bool {
		$source      = Security::sanitize_path( $source );
		$destination = Security::sanitize_path( $destination );

		if ( self::is_direct() ) {
			return rename( $source, $destination );
		}

		$fs = self::get_filesystem();
		if ( ! $fs ) {
			return false;
		}

		return $fs->move( $source, $destination );
	}

	/**
	 * Delete file using WordPress filesystem.
	 *
	 * @param string $path File path.
	 *
	 * @return bool True on success.
	 */
	public static function delete( string $path ): bool {
		$path = Security::sanitize_path( $path );

		if ( self::is_direct() ) {
			return unlink( $path );
		}

		$fs = self::get_filesystem();
		if ( ! $fs ) {
			return false;
		}

		return $fs->delete( $path );
	}

	/**
	 * Create directory using WordPress filesystem.
	 *
	 * @param string $path        Directory path.
	 * @param int    $permissions Directory permissions.
	 *
	 * @return bool True on success.
	 */
	public static function mkdir( string $path, int $permissions = 0755 ): bool {
		$path = Security::sanitize_path( $path );

		if ( self::is_direct() ) {
			return wp_mkdir_p( $path );
		}

		$fs = self::get_filesystem();
		if ( ! $fs ) {
			return false;
		}

		return $fs->mkdir( $path, $permissions );
	}

	/**
	 * Check if directory exists using WordPress filesystem.
	 *
	 * @param string $path Directory path.
	 *
	 * @return bool True if directory exists.
	 */
	public static function is_dir( string $path ): bool {
		$path = Security::sanitize_path( $path );

		if ( self::is_direct() ) {
			return is_dir( $path );
		}

		$fs = self::get_filesystem();
		if ( ! $fs ) {
			return false;
		}

		return $fs->is_dir( $path );
	}

	/**
	 * Open file handle.
	 *
	 * @param string $path File path.
	 * @param string $mode File mode.
	 *
	 * @return resource|null File handle or null on failure.
	 */
	public static function fopen( string $path, string $mode ) {
		$path   = Security::sanitize_path( $path );
		$handle = @fopen( $path, $mode );

		return $handle !== false ? $handle : null;
	}

	/**
	 * Create symbolic link.
	 *
	 * @param string $target Target path.
	 * @param string $link   Link path.
	 *
	 * @return bool True on success.
	 */
	public static function symlink( string $target, string $link ): bool {
		$target = Security::sanitize_path( realpath( $target ) ?: $target );
		$link   = Security::sanitize_path( $link );

		return @symlink( $target, $link );
	}

	/**
	 * Maybe move file from uploads directory.
	 *
	 * @param string $filename    Original filename in uploads.
	 * @param string $destination New file location.
	 *
	 * @return bool True if file was moved.
	 */
	public static function maybe_move_from_uploads( string $filename, string $destination ): bool {
		$uploads = wp_upload_dir();
		$source  = trailingslashit( $uploads['basedir'] ) . $filename;

		if ( ! self::exists( $source ) ) {
			return false;
		}

		return self::move( $source, $destination );
	}

	/**
	 * Get WordPress uploads directory info.
	 *
	 * @return array WordPress uploads directory info.
	 */
	public static function get_uploads_info(): array {
		return wp_upload_dir();
	}

	/**
	 * Check if path is within WordPress uploads directory.
	 *
	 * @param string $path Path to check.
	 *
	 * @return bool True if within uploads directory.
	 */
	public static function is_in_uploads( string $path ): bool {
		$uploads = self::get_uploads_info();

		return Security::is_within_directory( $path, $uploads['basedir'] );
	}

}