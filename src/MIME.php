<?php
/**
 * MIME Type Utility Class
 *
 * Provides MIME type detection, categorization, and WordPress integration
 * for file type handling and validation.
 *
 * @package ArrayPress\FileUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\FileUtils;

/**
 * MIME Class
 *
 * MIME type detection and categorization utilities.
 */
class MIME {

	/**
	 * Get MIME type from filename.
	 *
	 * @param string $filename Filename to check.
	 *
	 * @return string MIME type or 'application/octet-stream' if unknown.
	 */
	public static function get_type( string $filename ): string {
		if ( empty( $filename ) ) {
			return 'application/octet-stream';
		}

		$filetype = wp_check_filetype( $filename );

		return $filetype['type'] ?: 'application/octet-stream';
	}

	/**
	 * Get MIME type from file extension.
	 *
	 * @param string $extension File extension without dot.
	 *
	 * @return string|null MIME type or null if not found.
	 */
	public static function get_type_from_extension( string $extension ): ?string {
		$mime_types = wp_get_mime_types();
		$extension  = strtolower( ltrim( $extension, '.' ) );

		foreach ( $mime_types as $exts => $mime ) {
			if ( preg_match( '!^(' . $exts . ')$!i', $extension ) ) {
				return $mime;
			}
		}

		return null;
	}

	/**
	 * Get file extension from MIME type.
	 *
	 * @param string $mime_type MIME type.
	 *
	 * @return string|null File extension or null if not found.
	 */
	public static function get_extension_from_type( string $mime_type ): ?string {
		$mime_types = array_flip( wp_get_mime_types() );

		if ( isset( $mime_types[ $mime_type ] ) ) {
			$extensions = explode( '|', $mime_types[ $mime_type ] );

			return $extensions[0] ?? null;
		}

		return null;
	}

	/**
	 * Check if MIME type belongs to a specific category.
	 *
	 * @param string $mime_type MIME type to check.
	 * @param string $category  Category: 'image', 'audio', 'video', 'document', 'archive', 'code'.
	 *
	 * @return bool True if MIME type is in category.
	 */
	public static function is_type( string $mime_type, string $category ): bool {
		switch ( $category ) {
			case 'image':
				return str_starts_with( $mime_type, 'image/' );

			case 'audio':
				return str_starts_with( $mime_type, 'audio/' );

			case 'video':
				return str_starts_with( $mime_type, 'video/' );

			case 'document':
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
					'text/rtf'
				];

				return in_array( $mime_type, $document_types, true );

			case 'archive':
				$archive_types = [
					'application/zip',
					'application/x-rar-compressed',
					'application/x-7z-compressed',
					'application/x-tar',
					'application/gzip'
				];

				return in_array( $mime_type, $archive_types, true );

			case 'code':
				$code_types = [
					'text/html',
					'text/css',
					'application/javascript',
					'application/json',
					'application/xml',
					'text/xml'
				];

				return in_array( $mime_type, $code_types, true );

			default:
				return false;
		}
	}

	/**
	 * Get human-readable description of MIME type.
	 *
	 * @param string $mime_type MIME type.
	 *
	 * @return string Human-readable description.
	 */
	public static function get_description( string $mime_type ): string {
		$descriptions = [
			// Images
			'image/jpeg'                                                                => 'JPEG Image',
			'image/png'                                                                 => 'PNG Image',
			'image/gif'                                                                 => 'GIF Image',
			'image/webp'                                                                => 'WebP Image',
			'image/svg+xml'                                                             => 'SVG Image',

			// Audio
			'audio/mpeg'                                                                => 'MP3 Audio',
			'audio/wav'                                                                 => 'WAV Audio',
			'audio/ogg'                                                                 => 'OGG Audio',

			// Video
			'video/mp4'                                                                 => 'MP4 Video',
			'video/quicktime'                                                           => 'QuickTime Video',
			'video/webm'                                                                => 'WebM Video',

			// Documents
			'application/pdf'                                                           => 'PDF Document',
			'application/msword'                                                        => 'Word Document',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'Word Document',
			'application/vnd.ms-excel'                                                  => 'Excel Spreadsheet',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'Excel Spreadsheet',
			'application/vnd.ms-powerpoint'                                             => 'PowerPoint Presentation',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PowerPoint Presentation',

			// Text
			'text/plain'                                                                => 'Text File',
			'text/html'                                                                 => 'HTML Document',
			'text/css'                                                                  => 'CSS Stylesheet',
			'text/csv'                                                                  => 'CSV File',

			// Archives
			'application/zip'                                                           => 'ZIP Archive',
			'application/x-rar-compressed'                                              => 'RAR Archive',
			'application/x-7z-compressed'                                               => '7-Zip Archive',

			// Code
			'application/javascript'                                                    => 'JavaScript File',
			'application/json'                                                          => 'JSON File',
			'application/xml'                                                           => 'XML File'
		];

		return $descriptions[ $mime_type ] ?? 'Unknown File Type';
	}

	/**
	 * Get general category of MIME type.
	 *
	 * @param string $mime_type MIME type.
	 *
	 * @return string Category: 'image', 'audio', 'video', 'document', 'archive', 'code', 'text', or 'other'.
	 */
	public static function get_category( string $mime_type ): string {
		$categories = [ 'image', 'audio', 'video', 'document', 'archive', 'code' ];

		foreach ( $categories as $category ) {
			if ( self::is_type( $mime_type, $category ) ) {
				return $category;
			}
		}

		if ( str_starts_with( $mime_type, 'text/' ) ) {
			return 'text';
		}

		return 'other';
	}

	/**
	 * Check if MIME type is allowed.
	 *
	 * @param string $mime_type     MIME type to check.
	 * @param array  $allowed_types Array of allowed MIME types or categories.
	 *
	 * @return bool True if allowed.
	 */
	public static function is_allowed( string $mime_type, array $allowed_types ): bool {
		// Check exact MIME type match
		if ( in_array( $mime_type, $allowed_types, true ) ) {
			return true;
		}

		// Check category match
		$category = self::get_category( $mime_type );

		return in_array( $category, $allowed_types, true );
	}

	/**
	 * Get WordPress allowed MIME types.
	 *
	 * @return array Allowed MIME types.
	 */
	public static function get_allowed_types(): array {
		return get_allowed_mime_types();
	}

	/**
	 * Check if MIME type is allowed by WordPress.
	 *
	 * @param string $mime_type MIME type to check.
	 *
	 * @return bool True if allowed.
	 */
	public static function is_wordpress_allowed( string $mime_type ): bool {
		$allowed = self::get_allowed_types();

		return in_array( $mime_type, $allowed, true );
	}

}