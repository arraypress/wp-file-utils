<?php
/**
 * File Utility Class
 *
 * Core file operations with WordPress integration including local/remote handling,
 * URL to path conversion, and filesystem operations.
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
 * Core file and path operations with WordPress integration.
 */
class File {

	/**
	 * Get file extension.
	 *
	 * @param string $filename  Filename or path.
	 * @param bool   $lowercase Whether to return lowercase (default true).
	 *
	 * @return string File extension without dot.
	 */
	public static function get_extension( string $filename, bool $lowercase = true ): string {
		if ( empty( $filename ) ) {
			return '';
		}

		$extension = pathinfo( $filename, PATHINFO_EXTENSION );

		return $lowercase ? strtolower( $extension ) : strtoupper( $extension );
	}

	/**
	 * Get basename from path or URL.
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
	 * Normalize a path by resolving . and .. segments.
	 *
	 * @param string $path The path to normalize.
	 *
	 * @return string Normalized path.
	 */
	public static function normalize_path( string $path ): string {
		if ( empty( $path ) ) {
			return '';
		}

		// Convert backslashes to forward slashes
		$path = str_replace( '\\', '/', $path );

		// Preserve leading slash
		$leading_slash = substr( $path, 0, 1 ) === '/' ? '/' : '';

		// Preserve trailing slash
		$trailing_slash = substr( $path, - 1 ) === '/' ? '/' : '';

		// Split path into segments
		$segments = explode( '/', trim( $path, '/' ) );
		$result   = [];

		foreach ( $segments as $segment ) {
			if ( $segment === '.' || $segment === '' ) {
				continue;
			} elseif ( $segment === '..' ) {
				if ( ! empty( $result ) ) {
					array_pop( $result );
				}
			} else {
				$result[] = $segment;
			}
		}

		return $leading_slash . implode( '/', $result ) . $trailing_slash;
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

		// Convert uploads path to URL
		if ( strpos( $file_path, $upload_dir['basedir'] ) === 0 ) {
			return str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $file_path );
		}

		// Convert ABSPATH to site URL
		if ( strpos( $file_path, ABSPATH ) === 0 ) {
			$relative_path = str_replace( ABSPATH, '', $file_path );

			return home_url( $relative_path );
		}

		return null;
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
	 * Get file size from URL with smart local/remote detection.
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
	 * Check if URL exists and is accessible.
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
	 * Check if path is within a specific directory.
	 *
	 * @param string $path      Path to check.
	 * @param string $directory Directory to check against.
	 *
	 * @return bool True if path is within directory.
	 */
	public static function is_within_directory( string $path, string $directory ): bool {
		$real_path = realpath( $path );
		$real_dir  = realpath( $directory );

		if ( $real_path === false || $real_dir === false ) {
			return false;
		}

		return strpos( $real_path, $real_dir ) === 0;
	}

	/**
	 * Check if path is within WordPress uploads directory.
	 *
	 * @param string $path Path to check.
	 *
	 * @return bool True if within uploads directory.
	 */
	public static function is_in_uploads( string $path ): bool {
		$upload_dir = wp_upload_dir();

		return self::is_within_directory( $path, $upload_dir['basedir'] );
	}

	/**
	 * Get WordPress uploads directory info.
	 *
	 * @return array WordPress uploads directory info.
	 */
	public static function get_upload_dir(): array {
		return wp_upload_dir();
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