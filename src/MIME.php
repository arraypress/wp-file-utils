<?php
/**
 * MIME Type Utility Class
 *
 * Comprehensive MIME type detection and categorization for file handling.
 * Used by both Protected Folders and SugarCart for consistent MIME operations.
 *
 * @package     ArrayPress\Utils
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Utils;

/**
 * MIME Class
 *
 * MIME type detection and categorization utilities.
 */
class MIME {

	/**
	 * Common MIME type mappings.
	 *
	 * @var array
	 */
	private static array $mime_types = [
		// Documents
		'pdf'    => 'application/pdf',
		'doc'    => 'application/msword',
		'docx'   => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xls'    => 'application/vnd.ms-excel',
		'xlsx'   => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'ppt'    => 'application/vnd.ms-powerpoint',
		'pptx'   => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'odt'    => 'application/vnd.oasis.opendocument.text',
		'ods'    => 'application/vnd.oasis.opendocument.spreadsheet',
		'odp'    => 'application/vnd.oasis.opendocument.presentation',

		// Media
		'mp3'    => 'audio/mpeg',
		'ogg'    => 'audio/ogg',
		'wav'    => 'audio/wav',
		'm4a'    => 'audio/x-m4a',
		'flac'   => 'audio/flac',
		'mp4'    => 'video/mp4',
		'webm'   => 'video/webm',
		'avi'    => 'video/x-msvideo',
		'mov'    => 'video/quicktime',
		'wmv'    => 'video/x-ms-wmv',
		'mkv'    => 'video/x-matroska',

		// Images
		'jpg'    => 'image/jpeg',
		'jpeg'   => 'image/jpeg',
		'png'    => 'image/png',
		'gif'    => 'image/gif',
		'webp'   => 'image/webp',
		'svg'    => 'image/svg+xml',
		'ico'    => 'image/x-icon',
		'bmp'    => 'image/bmp',
		'tiff'   => 'image/tiff',
		'tif'    => 'image/tiff',

		// Archives
		'zip'    => 'application/zip',
		'rar'    => 'application/x-rar-compressed',
		'7z'     => 'application/x-7z-compressed',
		'tar'    => 'application/x-tar',
		'gz'     => 'application/gzip',
		'bz2'    => 'application/x-bzip2',

		// E-books
		'epub'   => 'application/epub+zip',
		'mobi'   => 'application/x-mobipocket-ebook',
		'azw'    => 'application/vnd.amazon.ebook',
		'azw3'   => 'application/vnd.amazon.ebook',

		// Text
		'txt'    => 'text/plain',
		'csv'    => 'text/csv',
		'json'   => 'application/json',
		'xml'    => 'application/xml',
		'html'   => 'text/html',
		'css'    => 'text/css',
		'js'     => 'application/javascript',
		'rtf'    => 'application/rtf',

		// Fonts
		'ttf'    => 'font/ttf',
		'otf'    => 'font/otf',
		'woff'   => 'font/woff',
		'woff2'  => 'font/woff2',
		'eot'    => 'application/vnd.ms-fontobject',

		// Applications
		'exe'    => 'application/x-msdownload',
		'dmg'    => 'application/x-apple-diskimage',
		'apk'    => 'application/vnd.android.package-archive',
		'deb'    => 'application/x-deb',
		'rpm'    => 'application/x-rpm',

		// Data
		'sql'    => 'application/sql',
		'db'     => 'application/x-sqlite3',
		'psd'    => 'image/vnd.adobe.photoshop',
		'ai'     => 'application/illustrator',
		'sketch' => 'application/x-sketch',
	];

	/**
	 * Get MIME type from filename or path.
	 *
	 * @param string $filename Filename or path to check.
	 *
	 * @return string MIME type or 'application/octet-stream' if unknown.
	 */
	public static function get_type( string $filename ): string {
		if ( empty( $filename ) ) {
			return 'application/octet-stream';
		}

		$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

		// Check our comprehensive list first
		if ( isset( self::$mime_types[ $extension ] ) ) {
			return self::$mime_types[ $extension ];
		}

		// Fall back to WordPress detection
		$filetype = wp_check_filetype( $filename );

		return $filetype['type'] ?: 'application/octet-stream';
	}

	/**
	 * Get file extension from MIME type.
	 *
	 * @param string $mime_type MIME type.
	 *
	 * @return string|null File extension or null if not found.
	 */
	public static function get_extension_from_type( string $mime_type ): ?string {
		if ( empty( $mime_type ) ) {
			return null;
		}

		// Search our mappings (this is now comprehensive)
		$extension = array_search( $mime_type, self::$mime_types, true );
		if ( $extension !== false ) {
			return $extension;
		}

		// Fall back to WordPress
		$mime_types = array_flip( wp_get_mime_types() );
		if ( isset( $mime_types[ $mime_type ] ) ) {
			$extensions = explode( '|', $mime_types[ $mime_type ] );

			return $extensions[0] ?? null;
		}

		return null;
	}

	/**
	 * Determine if file should be downloaded or displayed inline.
	 *
	 * @param string $mime_type MIME type.
	 *
	 * @return bool True to force download, false to display inline.
	 */
	public static function should_force_download( string $mime_type ): bool {
		// Images should display inline
		if ( str_starts_with( $mime_type, 'image/' ) ) {
			return false;
		}

		// Video/audio should stream inline (HTML5 players)
		if ( str_starts_with( $mime_type, 'video/' ) || str_starts_with( $mime_type, 'audio/' ) ) {
			return false;
		}

		// These specific types make sense to view inline
		$inline_types = [
			'application/pdf',  // PDFs can be viewed in browser
			'text/plain',       // Text files
			'text/csv',         // CSV can be displayed
			'text/html',        // HTML pages
			'text/css',         // Stylesheets
			'application/json', // JSON data
			'application/xml',  // XML data
		];

		if ( in_array( $mime_type, $inline_types, true ) ) {
			return false;
		}

		// Everything else downloads (safer default)
		// This includes: ZIP, DOC, DOCX, XLS, executables, unknown types
		return true;
	}

	/**
	 * Get optimal chunk size for streaming based on MIME type.
	 *
	 * @param string $mime_type MIME type.
	 *
	 * @return int Chunk size in bytes.
	 */
	public static function get_optimal_chunk_size( string $mime_type ): int {
		// Video files need larger chunks for smooth streaming
		if ( str_starts_with( $mime_type, 'video/' ) ) {
			return 2097152; // 2MB
		}

		// Archives and large files benefit from larger chunks
		$large_chunk_types = [
			'application/zip',
			'application/x-rar-compressed',
			'application/x-7z-compressed',
			'application/x-tar',
			'application/gzip',
			'application/x-apple-diskimage', // DMG files
		];

		if ( in_array( $mime_type, $large_chunk_types, true ) ) {
			return 4194304; // 4MB
		}

		// Audio files
		if ( str_starts_with( $mime_type, 'audio/' ) ) {
			return 1048576; // 1MB
		}

		// Images can use smaller chunks
		if ( str_starts_with( $mime_type, 'image/' ) ) {
			// Large images (PSDs, etc.) need bigger chunks
			if ( $mime_type === 'image/vnd.adobe.photoshop' ) {
				return 2097152; // 2MB
			}

			return 524288; // 512KB for regular images
		}

		// PDFs and documents
		$document_types = [
			'application/pdf',
			'application/msword',
			'application/vnd.openxmlformats-officedocument',
		];

		foreach ( $document_types as $type ) {
			if ( str_starts_with( $mime_type, $type ) ) {
				return 1048576; // 1MB
			}
		}

		// Default for everything else
		return 1048576; // 1MB
	}

	/**
	 * Check if MIME type is a media file (audio/video).
	 *
	 * @param string $mime_type MIME type.
	 *
	 * @return bool True if media file.
	 */
	public static function is_media( string $mime_type ): bool {
		return str_starts_with( $mime_type, 'audio/' ) || str_starts_with( $mime_type, 'video/' );
	}

	/**
	 * Check if MIME type is an image.
	 *
	 * @param string $mime_type MIME type.
	 *
	 * @return bool True if image.
	 */
	public static function is_image( string $mime_type ): bool {
		return str_starts_with( $mime_type, 'image/' );
	}

	/**
	 * Check if MIME type is a document.
	 *
	 * @param string $mime_type MIME type.
	 *
	 * @return bool True if document.
	 */
	public static function is_document( string $mime_type ): bool {
		$document_types = [
			'application/pdf',
			'application/msword',
			'application/vnd.ms-excel',
			'application/vnd.ms-powerpoint',
			'application/vnd.openxmlformats',
			'application/vnd.oasis.opendocument',
			'text/plain',
			'text/csv',
			'text/rtf',
			'application/rtf',
		];

		foreach ( $document_types as $type ) {
			if ( str_starts_with( $mime_type, $type ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if MIME type is an archive.
	 *
	 * @param string $mime_type MIME type.
	 *
	 * @return bool True if archive.
	 */
	public static function is_archive( string $mime_type ): bool {
		$archive_types = [
			'application/zip',
			'application/x-rar-compressed',
			'application/x-7z-compressed',
			'application/x-tar',
			'application/gzip',
			'application/x-bzip2',
		];

		return in_array( $mime_type, $archive_types, true );
	}

	/**
	 * Check if MIME type represents a downloadable digital product.
	 *
	 * For e-commerce, helps determine if a file should be sold as a download.
	 *
	 * @param string $mime_type MIME type to check.
	 *
	 * @return bool True if typically a downloadable digital product.
	 */
	public static function is_downloadable_product( string $mime_type ): bool {
		// These are typically sold as digital downloads
		return self::is_document( $mime_type )
		       || self::is_media( $mime_type )
		       || self::is_image( $mime_type )
		       || self::is_archive( $mime_type )
		       || str_contains( $mime_type, 'epub' )
		       || str_contains( $mime_type, 'ebook' )
		       || str_contains( $mime_type, 'font' );
	}

	/**
	 * Get all registered MIME types.
	 *
	 * @return array Array of extension => mime_type pairs.
	 */
	public static function get_all_types(): array {
		return self::$mime_types;
	}

}