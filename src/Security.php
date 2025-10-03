<?php
/**
 * File Security Utility Class
 *
 * Provides security utilities for file operations including path sanitization,
 * filename validation, and protocol filtering.
 *
 * @package ArrayPress\FileUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\FileUtils;

/**
 * Security Class
 *
 * File security utilities for safe file operations.
 */
class Security {

	/**
	 * Sanitize file path by removing dangerous protocols.
	 *
	 * @param string $path File path to sanitize.
	 *
	 * @return string Sanitized path.
	 */
	public static function sanitize_path( string $path ): string {
		if ( empty( $path ) ) {
			return '';
		}

		// Remove dangerous protocols
		$protocols = [
			'phar://',
			'php://',
			'glob://',
			'data://',
			'expect://',
			'zip://',
			'rar://',
			'zlib://'
		];

		foreach ( $protocols as $protocol ) {
			if ( stripos( $path, $protocol ) === 0 ) {
				$path = substr( $path, strlen( $protocol ) );
			}
			// Also check URL-encoded versions
			$encoded = urlencode( $protocol );
			if ( stripos( $path, $encoded ) === 0 ) {
				$path = substr( $path, strlen( $encoded ) );
			}
		}

		// Normalize path separators
		$path = str_replace( '\\', '/', $path );

		// Remove double dots
		while ( strpos( $path, '../' ) !== false ) {
			$path = str_replace( '../', '', $path );
		}

		return $path;
	}

	/**
	 * Sanitize filename for safe storage.
	 *
	 * @param string $filename Original filename.
	 *
	 * @return string Sanitized filename.
	 */
	public static function sanitize_filename( string $filename ): string {
		if ( empty( $filename ) ) {
			return '';
		}

		// Use WordPress function
		return sanitize_file_name( $filename );
	}

	/**
	 * Check if filename is safe.
	 *
	 * @param string $filename Filename to check.
	 *
	 * @return bool True if filename is safe.
	 */
	public static function is_safe_filename( string $filename ): bool {
		if ( empty( $filename ) ) {
			return false;
		}

		// Check for dangerous characters
		$dangerous_chars = [ '..', '/', '\\', ':', '*', '?', '"', '<', '>', '|', "\0" ];
		foreach ( $dangerous_chars as $char ) {
			if ( strpos( $filename, $char ) !== false ) {
				return false;
			}
		}

		// Check for dangerous extensions
		$dangerous_extensions = [
			'php',
			'phtml',
			'php3',
			'php4',
			'php5',
			'php7',
			'php8',
			'pht',
			'phar',
			'exe',
			'bat',
			'cmd',
			'com',
			'scr',
			'vbs',
			'js',
			'jsp',
			'asp',
			'aspx',
			'cgi',
			'sh'
		];

		$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

		return ! in_array( $extension, $dangerous_extensions, true );
	}

	/**
	 * Check if file type is allowed.
	 *
	 * @param string $filename      Filename to check.
	 * @param array  $allowed_types Array of allowed extensions or MIME types.
	 *
	 * @return bool True if file type is allowed.
	 */
	public static function is_allowed_file_type( string $filename, array $allowed_types ): bool {
		if ( empty( $filename ) || empty( $allowed_types ) ) {
			return false;
		}

		$extension = strtolower( File::get_extension( $filename ) );
		$mime_type = MIME::get_type( $filename );

		// Check extension
		if ( in_array( $extension, $allowed_types, true ) ) {
			return true;
		}

		// Check MIME type
		if ( in_array( $mime_type, $allowed_types, true ) ) {
			return true;
		}

		// Check MIME category
		$category = MIME::get_category( $mime_type );

		return in_array( $category, $allowed_types, true );
	}

	/**
	 * Validate upload directory path.
	 *
	 * @param string $upload_path Upload directory path.
	 *
	 * @return bool True if path is valid for uploads.
	 */
	public static function validate_upload_path( string $upload_path ): bool {
		if ( empty( $upload_path ) ) {
			return false;
		}

		// Check if path exists and is writable
		if ( ! is_dir( $upload_path ) || ! is_writable( $upload_path ) ) {
			return false;
		}

		// Check if it's within WordPress uploads directory
		$wp_upload_dir = wp_upload_dir();

		return File::is_within_directory( $upload_path, $wp_upload_dir['basedir'] );
	}

	/**
	 * Generate safe filename with optional uniqueness.
	 *
	 * @param string $filename    Original filename.
	 * @param string $directory   Directory to check for uniqueness.
	 * @param bool   $make_unique Whether to ensure filename is unique.
	 *
	 * @return string Safe filename.
	 */
	public static function generate_safe_filename( string $filename, string $directory = '', bool $make_unique = false ): string {
		// Sanitize first
		$safe_name = self::sanitize_filename( $filename );

		if ( ! $make_unique || empty( $directory ) ) {
			return $safe_name;
		}

		// Ensure unique filename
		$info      = pathinfo( $safe_name );
		$name      = $info['filename'] ?? 'file';
		$extension = isset( $info['extension'] ) ? '.' . $info['extension'] : '';
		$counter   = 1;

		while ( file_exists( trailingslashit( $directory ) . $safe_name ) ) {
			$safe_name = $name . '-' . $counter . $extension;
			$counter ++;
		}

		return $safe_name;
	}

}