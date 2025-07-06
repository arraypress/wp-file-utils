<?php
/**
 * File Utility Class
 *
 * Provides utility functions for working with files including
 * type detection, size formatting, and basic file operations.
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
 * size formatting, and basic file operations.
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

}