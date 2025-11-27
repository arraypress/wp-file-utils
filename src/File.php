<?php
/**
 * File Utility Class
 *
 * Core file operations for SugarCart digital file handling.
 * Focuses on URL/path conversion and basic file operations.
 *
 * @package     ArrayPress\Utils
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\FileUtils;

/**
 * File Class
 *
 * Essential file operations for e-commerce file handling.
 */
class File {

	/**
	 * Convert local URL to file path.
	 *
	 * This is the MAIN thing WordPress doesn't provide that we need!
	 *
	 * @param string $file_url Local file URL.
	 *
	 * @return string|null Local file path or null if not local/convertible.
	 */
	public static function url_to_path( string $file_url ): ?string {
		if ( empty( $file_url ) ) {
			return null;
		}

		// Already a local path
		if ( ! str_contains( $file_url, '://' ) ) {
			return file_exists( $file_url ) ? $file_url : null;
		}

		$upload_dir = wp_upload_dir();

		// Convert uploads URL to path (most common case)
		if ( str_starts_with( $file_url, $upload_dir['baseurl'] ) ) {
			return str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $file_url );
		}

		// Convert site URL to ABSPATH
		$site_url = home_url();
		if ( str_starts_with( $file_url, $site_url ) ) {
			$relative_path = str_replace( $site_url, '', $file_url );
			$full_path     = ABSPATH . ltrim( $relative_path, '/' );

			return file_exists( $full_path ) ? $full_path : null;
		}

		// Try content URL
		$content_url = content_url();
		if ( str_starts_with( $file_url, $content_url ) ) {
			$relative_path = str_replace( $content_url, '', $file_url );
			$full_path     = WP_CONTENT_DIR . ltrim( $relative_path, '/' );

			return file_exists( $full_path ) ? $full_path : null;
		}

		return null;
	}

	/**
	 * Convert local file path to URL.
	 *
	 * @param string $file_path Local file path.
	 *
	 * @return string|null File URL or null if not convertible.
	 */
	public static function path_to_url( string $file_path ): ?string {
		if ( empty( $file_path ) ) {
			return null;
		}

		$upload_dir = wp_upload_dir();

		// Convert uploads path to URL (most common)
		if ( str_starts_with( $file_path, $upload_dir['basedir'] ) ) {
			return str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $file_path );
		}

		// Convert WP_CONTENT_DIR to URL
		if ( str_starts_with( $file_path, WP_CONTENT_DIR ) ) {
			return str_replace( WP_CONTENT_DIR, content_url(), $file_path );
		}

		// Convert ABSPATH to site URL
		if ( str_starts_with( $file_path, ABSPATH ) ) {
			$relative_path = str_replace( ABSPATH, '', $file_path );

			return home_url( $relative_path );
		}

		return null;
	}

	/**
	 * Check if URL/path points to a local file.
	 *
	 * @param string $file_url File URL or path.
	 *
	 * @return bool True if file is local.
	 */
	public static function is_local_file( string $file_url ): bool {
		if ( empty( $file_url ) ) {
			return false;
		}

		// Already a local path
		if ( ! str_contains( $file_url, '://' ) ) {
			return file_exists( $file_url );
		}

		// Check if URL is within site
		$site_host = parse_url( home_url(), PHP_URL_HOST );
		$file_host = parse_url( $file_url, PHP_URL_HOST );

		return $site_host === $file_host;
	}

	/**
	 * Get file extension.
	 *
	 * @param string $filename Filename or path.
	 *
	 * @return string File extension without dot, lowercase.
	 */
	public static function get_extension( string $filename ): string {
		if ( empty( $filename ) ) {
			return '';
		}

		return strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
	}

	/**
	 * Get filename without extension.
	 *
	 * @param string $path File path or filename.
	 *
	 * @return string Filename without extension.
	 */
	public static function get_filename( string $path ): string {
		if ( empty( $path ) ) {
			return '';
		}

		return pathinfo( $path, PATHINFO_FILENAME );
	}

	/**
	 * Get basename (filename with extension).
	 *
	 * @param string $path File path.
	 *
	 * @return string Basename of the file.
	 */
	public static function get_basename( string $path ): string {
		if ( empty( $path ) ) {
			return '';
		}

		return basename( $path );
	}

	/**
	 * Get directory from path.
	 *
	 * @param string $path File path.
	 *
	 * @return string Directory path.
	 */
	public static function get_directory( string $path ): string {
		if ( empty( $path ) ) {
			return '';
		}

		return dirname( $path );
	}

	/**
	 * Change file extension.
	 *
	 * @param string $filename      Filename or path.
	 * @param string $new_extension New extension (without dot).
	 *
	 * @return string Filename with new extension.
	 */
	public static function change_extension( string $filename, string $new_extension ): string {
		if ( empty( $filename ) ) {
			return '';
		}

		$info          = pathinfo( $filename );
		$new_extension = ltrim( $new_extension, '.' );

		// Handle paths vs just filenames
		if ( ! empty( $info['dirname'] ) && $info['dirname'] !== '.' ) {
			return $info['dirname'] . '/' . $info['filename'] . '.' . $new_extension;
		}

		return $info['filename'] . '.' . $new_extension;
	}

	/**
	 * Sanitize filename for safe file operations.
	 *
	 * @param string $filename   Filename to sanitize.
	 * @param bool   $lower_case Whether to lowercase the filename.
	 *
	 * @return string Sanitized filename.
	 */
	public static function sanitize_filename( string $filename, bool $lower_case = false ): string {
		if ( empty( $filename ) ) {
			return '';
		}

		// Use WordPress sanitization
		$filename = sanitize_file_name( $filename );

		if ( $lower_case ) {
			$filename = strtolower( $filename );
		}

		return $filename;
	}

	/**
	 * Get file size in bytes.
	 *
	 * @param string $path File path.
	 *
	 * @return int|null File size in bytes or null on failure.
	 */
	public static function get_size( string $path ): ?int {
		if ( ! file_exists( $path ) || ! is_file( $path ) ) {
			return null;
		}

		$size = filesize( $path );

		return $size !== false ? $size : null;
	}

	/**
	 * Check if file exists and is readable.
	 *
	 * @param string $path File path.
	 *
	 * @return bool True if file exists and is readable.
	 */
	public static function is_readable( string $path ): bool {
		return file_exists( $path ) && is_file( $path ) && is_readable( $path );
	}

	/**
	 * Join path parts safely.
	 *
	 * @param string ...$parts Path parts to join.
	 *
	 * @return string Joined path.
	 */
	public static function join_path( string ...$parts ): string {
		$parts = array_map( 'untrailingslashit', $parts );
		$parts = array_filter( $parts, 'strlen' );

		return implode( '/', $parts );
	}

	/**
	 * Normalize a file path.
	 *
	 * @param string $path Path to normalize.
	 *
	 * @return string Normalized path.
	 */
	public static function normalize_path( string $path ): string {
		// Convert backslashes to forward slashes
		$path = str_replace( '\\', '/', $path );

		// Remove double slashes (except for protocol://)
		$path = preg_replace( '#(?<!:)//+#', '/', $path );

		return $path;
	}

	/**
	 * Generate a clean filename with a new name but preserved extension.
	 *
	 * @param string $new_name      New name for the file (without extension).
	 * @param string $original_path Original file path or filename.
	 * @param bool   $lower_case    Whether to lowercase the filename.
	 *
	 * @return string Sanitized filename with original extension.
	 */
	public static function rename_preserve_extension( string $new_name, string $original_path, bool $lower_case = false ): string {
		$extension = self::get_extension( $original_path );
		$safe_name = sanitize_file_name( $new_name );

		if ( $lower_case ) {
			$safe_name = strtolower( $safe_name );
		}

		return $extension ? $safe_name . '.' . $extension : $safe_name;
	}

}