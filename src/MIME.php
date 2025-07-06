<?php
/**
 * MIME Utility Class
 *
 * Provides utility functions for working with MIME types including
 * type detection, categorization, and human-readable descriptions.
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
 * MIME type utilities for detection, categorization, and WordPress
 * integration including extension mapping and type validation.
 */
class MIME {

	/**
	 * Get MIME type from file extension.
	 *
	 * @param string $extension File extension.
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

		return $mime_types[ $mime_type ] ?? null;
	}

	/**
	 * Check if MIME type is in a specific category.
	 *
	 * @param string $mime_type MIME type.
	 * @param string $category  Category to check.
	 *
	 * @return bool True if MIME type is in category.
	 */
	public static function is_type( string $mime_type, string $category ): bool {
		$categories = [
			'image'    => [ 'image/' ],
			'audio'    => [ 'audio/' ],
			'video'    => [ 'video/' ],
			'document' => [
				'application/pdf',
				'application/msword',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'application/vnd.ms-excel',
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'application/vnd.ms-powerpoint',
				'application/vnd.openxmlformats-officedocument.presentationml.presentation',
				'text/plain',
				'text/rtf'
			],
			'archive'  => [
				'application/zip',
				'application/x-rar-compressed',
				'application/x-7z-compressed',
				'application/x-tar',
				'application/gzip'
			],
			'code'     => [
				'text/html',
				'text/css',
				'application/javascript',
				'application/json',
				'application/xml',
				'text/xml'
			]
		];

		if ( ! isset( $categories[ $category ] ) ) {
			return false;
		}

		foreach ( $categories[ $category ] as $prefix ) {
			if ( str_starts_with( $mime_type, $prefix ) ) {
				return true;
			}
		}

		return false;
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
			'image/bmp'                                                                 => 'BMP Image',
			'image/tiff'                                                                => 'TIFF Image',

			// Audio
			'audio/mpeg'                                                                => 'MP3 Audio',
			'audio/wav'                                                                 => 'WAV Audio',
			'audio/ogg'                                                                 => 'OGG Audio',
			'audio/mp4'                                                                 => 'M4A Audio',
			'audio/flac'                                                                => 'FLAC Audio',

			// Video
			'video/mp4'                                                                 => 'MP4 Video',
			'video/quicktime'                                                           => 'QuickTime Video',
			'video/webm'                                                                => 'WebM Video',
			'video/avi'                                                                 => 'AVI Video',
			'video/x-msvideo'                                                           => 'AVI Video',

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
			'text/rtf'                                                                  => 'Rich Text Document',

			// Archives
			'application/zip'                                                           => 'ZIP Archive',
			'application/x-rar-compressed'                                              => 'RAR Archive',
			'application/x-7z-compressed'                                               => '7-Zip Archive',
			'application/x-tar'                                                         => 'TAR Archive',
			'application/gzip'                                                          => 'GZip Archive',

			// Code
			'application/javascript'                                                    => 'JavaScript File',
			'application/json'                                                          => 'JSON File',
			'application/xml'                                                           => 'XML File',
			'text/xml'                                                                  => 'XML File'
		];

		return $descriptions[ $mime_type ] ?? 'Unknown File Type';
	}

	/**
	 * Get general category of MIME type.
	 *
	 * @param string $mime_type MIME type.
	 *
	 * @return string General category.
	 */
	public static function get_general_type( string $mime_type ): string {
		$types = [ 'image', 'audio', 'video', 'document', 'archive', 'code' ];

		foreach ( $types as $type ) {
			if ( self::is_type( $mime_type, $type ) ) {
				return $type;
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
	 * @param string $mime_type     MIME type.
	 * @param array  $allowed_types Allowed MIME types or categories.
	 *
	 * @return bool True if allowed.
	 */
	public static function is_allowed( string $mime_type, array $allowed_types ): bool {
		// Check exact MIME type match
		if ( in_array( $mime_type, $allowed_types, true ) ) {
			return true;
		}

		// Check category match
		$general_type = self::get_general_type( $mime_type );

		return in_array( $general_type, $allowed_types, true );
	}

	/**
	 * Get common MIME types by category.
	 *
	 * @return array Common MIME types grouped by category.
	 */
	public static function get_common_types(): array {
		return [
			'image'    => [
				'image/jpeg',
				'image/png',
				'image/gif',
				'image/webp',
				'image/svg+xml'
			],
			'audio'    => [
				'audio/mpeg',
				'audio/wav',
				'audio/ogg',
				'audio/mp4'
			],
			'video'    => [
				'video/mp4',
				'video/quicktime',
				'video/webm',
				'video/avi'
			],
			'document' => [
				'application/pdf',
				'application/msword',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'text/plain'
			],
			'archive'  => [
				'application/zip',
				'application/x-rar-compressed',
				'application/x-7z-compressed'
			],
			'code'     => [
				'text/html',
				'text/css',
				'application/javascript',
				'application/json'
			]
		];
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
	 * Get allowed file extensions from WordPress MIME types.
	 *
	 * @return array Array of allowed extensions.
	 */
	public static function get_allowed_extensions(): array {
		$mime_types = self::get_allowed_types();
		$extensions = [];

		foreach ( $mime_types as $ext => $mime ) {
			// Handle multiple extensions (e.g., "jpg|jpeg|jpe")
			$ext_parts = explode( '|', $ext );
			foreach ( $ext_parts as $extension ) {
				$extensions[] = strtolower( trim( $extension ) );
			}
		}

		return array_unique( $extensions );
	}

	/**
	 * Get additional MIME types for modern file formats.
	 *
	 * @return array Additional MIME types.
	 */
	public static function get_additional_types(): array {
		return [
			'webp' => 'image/webp',
			'avif' => 'image/avif',
			'heic' => 'image/heic',
			'webm' => 'video/webm',
			'flac' => 'audio/flac',
			'opus' => 'audio/opus',
			'7z'   => 'application/x-7z-compressed',
			'epub' => 'application/epub+zip',
			'mobi' => 'application/x-mobipocket-ebook'
		];
	}

}