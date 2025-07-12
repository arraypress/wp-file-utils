<?php
/**
 * Enhanced File Utility Class
 *
 * Provides utility functions for working with files including
 * type detection, size formatting, local/remote file handling,
 * and WordPress-specific optimizations.
 *
 * @package ArrayPress\FileUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\FileUtils;

/**
 * File Class
 *
 * Core operations for working with files including type detection,
 * size formatting, and enhanced local/remote file operations.
 */
class File {

	/**
	 * Get file extension.
	 *
	 * @param string $filename  Filename or path.
	 * @param bool   $lowercase Whether to return lowercase (default true).
	 *
	 * @return string File extension.
	 */
	public static function get_extension( string $filename, bool $lowercase = true ): string {
		if ( empty( $filename ) ) {
			return '';
		}

		$extension = pathinfo( $filename, PATHINFO_EXTENSION );

		return $lowercase ? strtolower( $extension ) : strtoupper( $extension );
	}

	/**
	 * Get filename without extension.
	 *
	 * @param string $filename Filename or path.
	 *
	 * @return string Filename without extension.
	 */
	public static function get_name( string $filename ): string {
		if ( empty( $filename ) ) {
			return '';
		}

		return pathinfo( $filename, PATHINFO_FILENAME );
	}

	/**
	 * Get basename from path.
	 *
	 * @param string $path File path or URL.
	 *
	 * @return string Basename.
	 */
	public static function get_basename( string $path ): string {
		if ( empty( $path ) ) {
			return '';
		}

		// Handle URLs
		if ( str_contains( $path, '://' ) ) {
			$path = parse_url( $path, PHP_URL_PATH ) ?: $path;
		}

		return basename( $path );
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
			return true;
		}

		$upload_dir = wp_upload_dir();
		$site_url   = home_url();

		// Check if it's within uploads directory
		if ( strpos( $file_url, $upload_dir['baseurl'] ) === 0 ) {
			return true;
		}

		// Check if it's within site URL
		if ( strpos( $file_url, $site_url ) === 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Convert local URL to file path.
	 *
	 * @param string $file_url Local file URL.
	 *
	 * @return string|null Local file path or null if not local.
	 */
	public static function url_to_path( string $file_url ): ?string {
		if ( empty( $file_url ) || ! self::is_local_file( $file_url ) ) {
			return null;
		}

		// Already a local path
		if ( ! str_contains( $file_url, '://' ) ) {
			return $file_url;
		}

		$upload_dir = wp_upload_dir();

		// Convert uploads URL to path
		if ( strpos( $file_url, $upload_dir['baseurl'] ) === 0 ) {
			return str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $file_url );
		}

		// Convert site URL to ABSPATH
		$site_url = home_url();
		if ( strpos( $file_url, $site_url ) === 0 ) {
			$relative_path = str_replace( $site_url, '', $file_url );

			return ABSPATH . ltrim( $relative_path, '/' );
		}

		return null;
	}

	/**
	 * Get file size with smart local/remote detection.
	 *
	 * @param string $file_url File URL or path.
	 *
	 * @return int|null File size in bytes or null if not accessible.
	 */
	public static function get_size_from_url( string $file_url ): ?int {
		if ( empty( $file_url ) ) {
			return null;
		}

		// Try local file first for better performance
		if ( self::is_local_file( $file_url ) ) {
			$file_path = self::url_to_path( $file_url );
			if ( $file_path && file_exists( $file_path ) ) {
				$size = filesize( $file_path );

				return $size !== false ? $size : null;
			}
		}

		// Fallback to remote file headers
		$response = wp_remote_head( $file_url, [
			'timeout'    => 10,
			'user-agent' => self::get_user_agent()
		] );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$content_length = wp_remote_retrieve_header( $response, 'content-length' );

		return $content_length ? (int) $content_length : null;
	}

	/**
	 * Check if URL or path is accessible.
	 *
	 * @param string $file_url File URL or path.
	 *
	 * @return bool True if accessible.
	 */
	public static function url_exists( string $file_url ): bool {
		if ( empty( $file_url ) ) {
			return false;
		}

		// Check local file first for better performance
		if ( self::is_local_file( $file_url ) ) {
			$file_path = self::url_to_path( $file_url );

			return $file_path && file_exists( $file_path );
		}

		// Check remote file
		$response = wp_remote_head( $file_url, [
			'timeout'    => 10,
			'user-agent' => self::get_user_agent()
		] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		return $status_code >= 200 && $status_code < 300;
	}

	/**
	 * Check if file/URL is downloadable (accessible and has content).
	 *
	 * @param string $file_url File URL or path.
	 *
	 * @return bool True if downloadable.
	 */
	public static function is_downloadable( string $file_url ): bool {
		if ( ! self::url_exists( $file_url ) ) {
			return false;
		}

		// Local files are downloadable if they exist
		if ( self::is_local_file( $file_url ) ) {
			return true;
		}

		// Check remote file headers for downloadable content
		$response = wp_remote_head( $file_url, [
			'timeout'    => 10,
			'user-agent' => self::get_user_agent()
		] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$content_type        = wp_remote_retrieve_header( $response, 'content-type' );
		$content_disposition = wp_remote_retrieve_header( $response, 'content-disposition' );

		// Check for explicit download header
		if ( strpos( $content_disposition, 'attachment' ) !== false ) {
			return true;
		}

		// Consider it downloadable if it's NOT web content
		if ( empty( $content_type ) ) {
			return false;
		}

		$web_content_types = [
			'text/html',
			'text/xml',
			'application/xml',
			'application/xhtml+xml'
		];

		$content_type = strtolower( trim( explode( ';', $content_type )[0] ) );

		return ! in_array( $content_type, $web_content_types, true );
	}

	/**
	 * Get MIME type from filename.
	 *
	 * @param string $filename Filename.
	 *
	 * @return string MIME type.
	 */
	public static function get_mime_type( string $filename ): string {
		if ( empty( $filename ) ) {
			return 'application/octet-stream';
		}

		$filetype = wp_check_filetype( $filename );

		return $filetype['type'] ?: 'application/octet-stream';
	}

	/**
	 * Get human-readable file type from filename.
	 *
	 * @param string $filename Filename.
	 *
	 * @return string Human-readable file type.
	 */
	public static function get_type( string $filename ): string {
		$mime_type = self::get_mime_type( $filename );

		return MIME::get_description( $mime_type );
	}

	/**
	 * Check if file is an image.
	 *
	 * @param string $filename Filename.
	 *
	 * @return bool True if an image.
	 */
	public static function is_image( string $filename ): bool {
		$mime_type = self::get_mime_type( $filename );

		return strpos( $mime_type, 'image/' ) === 0;
	}

	/**
	 * Check if file is audio.
	 *
	 * @param string $filename Filename.
	 *
	 * @return bool True if audio.
	 */
	public static function is_audio( string $filename ): bool {
		$mime_type = self::get_mime_type( $filename );

		return strpos( $mime_type, 'audio/' ) === 0;
	}

	/**
	 * Check if file is video.
	 *
	 * @param string $filename Filename.
	 *
	 * @return bool True if video.
	 */
	public static function is_video( string $filename ): bool {
		$mime_type = self::get_mime_type( $filename );

		return strpos( $mime_type, 'video/' ) === 0;
	}

	/**
	 * Check if file is a document.
	 *
	 * @param string $filename Filename.
	 *
	 * @return bool True if document.
	 */
	public static function is_document( string $filename ): bool {
		$mime_type = self::get_mime_type( $filename );

		return MIME::is_type( $mime_type, 'document' );
	}

	/**
	 * Check if file is an archive.
	 *
	 * @param string $filename Filename.
	 *
	 * @return bool True if archive.
	 */
	public static function is_archive( string $filename ): bool {
		$mime_type = self::get_mime_type( $filename );

		return MIME::is_type( $mime_type, 'archive' );
	}

	/**
	 * Check if file exists.
	 *
	 * @param string $path File path.
	 *
	 * @return bool True if file exists.
	 */
	public static function exists( string $path ): bool {
		return file_exists( $path ) && is_file( $path );
	}

	/**
	 * Get file size in bytes.
	 *
	 * @param string $path File path.
	 *
	 * @return int|null File size in bytes or null on failure.
	 */
	public static function get_size( string $path ): ?int {
		if ( ! self::exists( $path ) ) {
			return null;
		}

		$size = filesize( $path );

		return $size !== false ? $size : null;
	}

	/**
	 * Format file size for display.
	 *
	 * @param int $bytes    File size in bytes.
	 * @param int $decimals Number of decimal places.
	 *
	 * @return string Formatted file size.
	 */
	public static function format_size( int $bytes, int $decimals = 2 ): string {
		return size_format( $bytes, $decimals );
	}

	/**
	 * Get file contents.
	 *
	 * @param string $path File path.
	 *
	 * @return string|null File contents or null on failure.
	 */
	public static function get_contents( string $path ): ?string {
		if ( ! self::exists( $path ) ) {
			return null;
		}

		$contents = file_get_contents( $path );

		return $contents !== false ? $contents : null;
	}

	/**
	 * Put contents to file.
	 *
	 * @param string $path     File path.
	 * @param string $contents Contents to write.
	 *
	 * @return bool True on success.
	 */
	public static function put_contents( string $path, string $contents ): bool {
		return file_put_contents( $path, $contents ) !== false;
	}

	/**
	 * Copy a file.
	 *
	 * @param string $source      Source file path.
	 * @param string $destination Destination file path.
	 *
	 * @return bool True on success.
	 */
	public static function copy( string $source, string $destination ): bool {
		if ( ! self::exists( $source ) ) {
			return false;
		}

		return copy( $source, $destination );
	}

	/**
	 * Move a file.
	 *
	 * @param string $source      Source file path.
	 * @param string $destination Destination file path.
	 *
	 * @return bool True on success.
	 */
	public static function move( string $source, string $destination ): bool {
		if ( ! self::exists( $source ) ) {
			return false;
		}

		return rename( $source, $destination );
	}

	/**
	 * Delete a file.
	 *
	 * @param string $path File path.
	 *
	 * @return bool True on success.
	 */
	public static function delete( string $path ): bool {
		if ( ! self::exists( $path ) ) {
			return false;
		}

		return unlink( $path );
	}

	/**
	 * Check if file is readable.
	 *
	 * @param string $path File path.
	 *
	 * @return bool True if readable.
	 */
	public static function is_readable( string $path ): bool {
		return is_readable( $path );
	}

	/**
	 * Check if file is writable.
	 *
	 * @param string $path File path.
	 *
	 * @return bool True if writable.
	 */
	public static function is_writable( string $path ): bool {
		return is_writable( $path );
	}

	/**
	 * Get file modification time.
	 *
	 * @param string $path File path.
	 *
	 * @return int|null Last modified timestamp or null on failure.
	 */
	public static function get_modified_time( string $path ): ?int {
		if ( ! self::exists( $path ) ) {
			return null;
		}

		$time = filemtime( $path );

		return $time !== false ? $time : null;
	}

	/**
	 * Get file category (simpler version of get_type).
	 *
	 * @param string $filename Filename.
	 *
	 * @return string File category (image, video, audio, document, archive, other).
	 */
	public static function get_category( string $filename ): string {
		$mime_type = self::get_mime_type( $filename );

		if ( strpos( $mime_type, 'image/' ) === 0 ) {
			return 'image';
		}

		if ( strpos( $mime_type, 'video/' ) === 0 ) {
			return 'video';
		}

		if ( strpos( $mime_type, 'audio/' ) === 0 ) {
			return 'audio';
		}

		// Document types
		$document_types = [
			'application/pdf',
			'application/msword',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/vnd.ms-excel',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/vnd.ms-powerpoint',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'text/plain',
			'text/csv',
			'application/rtf'
		];

		if ( in_array( $mime_type, $document_types, true ) ) {
			return 'document';
		}

		// Archive types
		$archive_types = [
			'application/zip',
			'application/x-rar-compressed',
			'application/x-tar',
			'application/gzip',
			'application/x-7z-compressed'
		];

		if ( in_array( $mime_type, $archive_types, true ) ) {
			return 'archive';
		}

		return 'other';
	}

	/**
	 * Check if file type is allowed by WordPress.
	 *
	 * @param string $filename Filename.
	 *
	 * @return bool True if file type is allowed.
	 */
	public static function is_allowed_type( string $filename ): bool {
		$filetype = wp_check_filetype( $filename );

		return ! empty( $filetype['type'] ) && ! empty( $filetype['ext'] );
	}

	/**
	 * Get user agent string for HTTP requests.
	 *
	 * @return string User agent string.
	 */
	private static function get_user_agent(): string {
		$site_name = get_bloginfo( 'name' );
		$site_url  = home_url();

		return "WordPress/{$site_name} (+{$site_url})";
	}

}