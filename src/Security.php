<?php
/**
 * File Security Utility Class
 *
 * Basic file security validation for SugarCart file handling.
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
 * Security Class
 *
 * File security utilities for safe file operations.
 */
class Security {

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

		// Check for path traversal attempts
		if ( str_contains( $filename, '..' ) || str_contains( $filename, '/' ) || str_contains( $filename, '\\' ) ) {
			return false;
		}

		// Check for null bytes
		if ( str_contains( $filename, "\0" ) ) {
			return false;
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
	 * @param array  $allowed_types Array of allowed extensions (without dots).
	 *
	 * @return bool True if file type is allowed.
	 */
	public static function is_allowed_file_type( string $filename, array $allowed_types ): bool {
		if ( empty( $filename ) || empty( $allowed_types ) ) {
			return false;
		}

		$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

		// Normalize allowed types (remove dots, lowercase)
		$allowed_types = array_map( function ( $type ) {
			return strtolower( ltrim( $type, '.' ) );
		}, $allowed_types );

		return in_array( $extension, $allowed_types, true );
	}

	/**
	 * Sanitize a file path.
	 *
	 * Removes dangerous protocols and path traversal attempts.
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
		}

		// Remove path traversal attempts
		$path = str_replace( '..', '', $path );

		// Normalize slashes
		$path = str_replace( '\\', '/', $path );

		// Remove double slashes (except after protocol)
		$path = preg_replace( '#(?<!:)//+#', '/', $path );

		return $path;
	}

}