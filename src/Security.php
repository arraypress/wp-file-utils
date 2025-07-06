<?php
/**
 * Security Utility Class
 *
 * Provides security utilities for file operations including
 * path sanitization, protocol filtering, and directory validation.
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
 * File security utilities including path sanitization, protocol
 * filtering, filename validation, and directory safety checks.
 */
class Security {

	/**
	 * Default restricted protocols.
	 *
	 * @var array
	 */
	private static array $restricted_protocols = [
		'phar://',
		'php://',
		'glob://',
		'data://',
		'expect://',
		'zip://',
		'rar://',
		'zlib://'
	];

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

		// Check for protocols
		if ( ! str_contains( $path, '://' ) && ! str_contains( $path, urlencode( '://' ) ) ) {
			return $path;
		}

		// Remove restricted protocols
		foreach ( self::get_protocols() as $protocol ) {
			$pattern = '#^' . preg_quote( $protocol, '#' ) . '#i';
			$path    = preg_replace( $pattern, '', $path );
		}

		return $path;
	}

	/**
	 * Get restricted protocols.
	 *
	 * @return array Restricted protocols.
	 */
	public static function get_protocols(): array {
		return array_merge(
			self::$restricted_protocols,
			array_map( 'urlencode', self::$restricted_protocols )
		);
	}

	/**
	 * Add restricted protocol.
	 *
	 * @param string $protocol Protocol to restrict.
	 *
	 * @return bool Success.
	 */
	public static function add_protocol( string $protocol ): bool {
		if ( empty( $protocol ) ) {
			return false;
		}

		// Ensure protocol ends with ://
		if ( ! str_ends_with( $protocol, '://' ) ) {
			$protocol .= '://';
		}

		if ( ! in_array( $protocol, self::$restricted_protocols, true ) ) {
			self::$restricted_protocols[] = $protocol;
		}

		return true;
	}

	/**
	 * Remove restricted protocol.
	 *
	 * @param string $protocol Protocol to remove.
	 *
	 * @return bool Success.
	 */
	public static function remove_protocol( string $protocol ): bool {
		if ( empty( $protocol ) ) {
			return false;
		}

		// Ensure protocol ends with ://
		if ( ! str_ends_with( $protocol, '://' ) ) {
			$protocol .= '://';
		}

		$key = array_search( $protocol, self::$restricted_protocols, true );
		if ( $key !== false ) {
			unset( self::$restricted_protocols[ $key ] );
			self::$restricted_protocols = array_values( self::$restricted_protocols );

			return true;
		}

		return false;
	}

	/**
	 * Reset protocols to defaults.
	 *
	 * @return bool Success.
	 */
	public static function reset_protocols(): bool {
		self::$restricted_protocols = [
			'phar://',
			'php://',
			'glob://',
			'data://',
			'expect://',
			'zip://',
			'rar://',
			'zlib://'
		];

		return true;
	}

	/**
	 * Validate filename for security issues.
	 *
	 * @param string $filename Filename to validate.
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
		$dangerous_extensions = [ 'php', 'phtml', 'php3', 'php4', 'php5', 'pht', 'phar', 'exe', 'bat', 'cmd', 'scr' ];
		$extension            = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

		if ( in_array( $extension, $dangerous_extensions, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if path is within allowed directory.
	 *
	 * @param string $path          File path to check.
	 * @param string $allowed_dir   Allowed directory.
	 * @param bool   $resolve_paths Whether to resolve real paths.
	 *
	 * @return bool True if path is within allowed directory.
	 */
	public static function is_within_directory( string $path, string $allowed_dir, bool $resolve_paths = true ): bool {
		if ( $resolve_paths ) {
			$real_path = realpath( $path );
			$real_dir  = realpath( $allowed_dir );

			if ( $real_path === false || $real_dir === false ) {
				return false;
			}

			return strpos( $real_path, $real_dir ) === 0;
		}

		$normalized_path = Path::normalize( $path );
		$normalized_dir  = Path::normalize( $allowed_dir );

		return strpos( $normalized_path, $normalized_dir ) === 0;
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

		// Get file parts
		$info      = pathinfo( $filename );
		$name      = $info['filename'] ?? '';
		$extension = $info['extension'] ?? '';

		// Sanitize filename part
		$name = sanitize_file_name( $name );

		// If we have an extension, add it back
		if ( ! empty( $extension ) ) {
			$extension = strtolower( $extension );
			$name      = $name . '.' . $extension;
		}

		return $name;
	}

	/**
	 * Check if file extension is allowed.
	 *
	 * @param string $filename      Filename to check.
	 * @param array  $allowed_types Allowed file types/extensions.
	 *
	 * @return bool True if file extension is allowed.
	 */
	public static function is_allowed_file_type( string $filename, array $allowed_types ): bool {
		if ( empty( $filename ) || empty( $allowed_types ) ) {
			return false;
		}

		$extension = strtolower( File::get_extension( $filename ) );
		$mime_type = File::get_mime_type( $filename );

		// Check if extension is directly allowed
		if ( in_array( $extension, $allowed_types, true ) ) {
			return true;
		}

		// Check if MIME type is allowed
		if ( in_array( $mime_type, $allowed_types, true ) ) {
			return true;
		}

		// Check if general type is allowed
		$general_type = MIME::get_general_type( $mime_type );
		if ( in_array( $general_type, $allowed_types, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Validate upload directory path.
	 *
	 * @param string $upload_path Upload directory path.
	 *
	 * @return bool True if path is valid for uploads.
	 */
	public static function is_valid_upload_path( string $upload_path ): bool {
		if ( empty( $upload_path ) ) {
			return false;
		}

		// Check if path exists and is writable
		if ( ! is_dir( $upload_path ) || ! is_writable( $upload_path ) ) {
			return false;
		}

		// Check if it's within WordPress directory structure
		$wp_upload_dir = wp_upload_dir();
		if ( ! self::is_within_directory( $upload_path, $wp_upload_dir['basedir'] ) ) {
			return false;
		}

		return true;
	}

}