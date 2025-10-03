<?php
/**
 * File Delivery Utility Class
 *
 * Handles secure file delivery with support for streaming, range requests,
 * and X-Sendfile/X-Accel-Redirect for optimal performance.
 *
 * @package ArrayPress\FileUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\FileUtils;

/**
 * Delivery Class
 *
 * Secure file delivery with streaming and server optimization support.
 */
class Delivery {

	/**
	 * Stream a file to the browser with proper headers.
	 *
	 * @param string $file_path        Path to the file to stream.
	 * @param array  $options          {
	 *                                 Optional delivery options.
	 *
	 * @type string  $filename         Filename for download (default: basename of file).
	 * @type string  $mime_type        MIME type (default: auto-detect).
	 * @type bool    $force_download   Force download instead of inline (default: true).
	 * @type int     $chunk_size       Chunk size in bytes (default: 1MB).
	 * @type bool    $enable_range     Enable range request support (default: true).
	 * @type bool    $enable_xsendfile Try to use X-Sendfile if available (default: false).
	 *                                 }
	 *
	 * @return void Outputs file and exits.
	 */
	public static function stream( string $file_path, array $options = [] ): void {
		// Verify file exists and is readable
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			wp_die(
				__( 'File not found or not readable.', 'arraypress' ),
				__( 'Download Error', 'arraypress' ),
				[ 'response' => 404 ]
			);
		}

		$defaults = [
			'filename'         => basename( $file_path ),
			'mime_type'        => null,
			'force_download'   => true,
			'chunk_size'       => 1048576, // 1MB
			'enable_range'     => true,
			'enable_xsendfile' => false
		];

		$options = array_merge( $defaults, $options );

		// Setup environment
		self::setup_environment( $file_path );

		// Try X-Sendfile if enabled and available
		if ( $options['enable_xsendfile'] && self::supports_xsendfile() ) {
			self::deliver_via_xsendfile( $file_path, $options );
			exit;
		}

		// Stream file normally
		self::stream_file( $file_path, $options );
		exit;
	}

	/**
	 * Check if server supports X-Sendfile or X-Accel-Redirect.
	 *
	 * @return bool True if X-Sendfile is supported.
	 */
	public static function supports_xsendfile(): bool {
		// Check Apache mod_xsendfile
		if ( function_exists( 'apache_get_modules' ) ) {
			$modules = apache_get_modules();
			if ( in_array( 'mod_xsendfile', $modules, true ) ) {
				return true;
			}
		}

		// Check for Nginx
		$server = $_SERVER['SERVER_SOFTWARE'] ?? '';
		if ( stripos( $server, 'nginx' ) !== false ) {
			// Nginx support requires configuration, check filter
			return apply_filters( 'arraypress_file_nginx_xsendfile', false );
		}

		return false;
	}

	/**
	 * Set secure download headers.
	 *
	 * @param string $filename  Filename for download.
	 * @param string $mime_type MIME type or null to auto-detect.
	 * @param bool   $inline    Whether to display inline instead of download.
	 *
	 * @return void
	 */
	public static function set_download_headers( string $filename, ?string $mime_type = null, bool $inline = false ): void {
		// Prevent caching
		nocache_headers();

		// Security headers
		header( 'X-Robots-Tag: noindex, nofollow', true );
		header( 'X-Content-Type-Options: nosniff' );

		// Set MIME type
		if ( empty( $mime_type ) ) {
			$mime_type = MIME::get_type( $filename );
		}

		// Force download for potentially dangerous types
		$dangerous_types = [
			'text/html',
			'text/javascript',
			'application/javascript',
			'application/x-javascript'
		];

		if ( in_array( strtolower( $mime_type ), $dangerous_types, true ) ) {
			$mime_type = 'application/octet-stream';
			$inline    = false;
		}

		header( 'Content-Type: ' . $mime_type );

		// File transfer headers
		header( 'Content-Description: File Transfer' );
		header( 'Content-Transfer-Encoding: binary' );

		// Set disposition
		$disposition = $inline ? 'inline' : 'attachment';

		// Sanitize filename for header
		$safe_filename = preg_replace( '/[^a-zA-Z0-9._-]/', '_', $filename );

		// Use RFC 5987 for international characters
		if ( $safe_filename !== $filename ) {
			header( sprintf(
				'Content-Disposition: %s; filename="%s"; filename*=UTF-8\'\'%s',
				$disposition,
				$safe_filename,
				rawurlencode( $filename )
			) );
		} else {
			header( sprintf( 'Content-Disposition: %s; filename="%s"', $disposition, $safe_filename ) );
		}
	}

	/**
	 * Parse HTTP range header.
	 *
	 * @param int $file_size Total file size in bytes.
	 *
	 * @return array|null Array with 'start' and 'end' positions or null if no valid range.
	 */
	public static function parse_range_header( int $file_size ): ?array {
		if ( ! isset( $_SERVER['HTTP_RANGE'] ) ) {
			return null;
		}

		$range = $_SERVER['HTTP_RANGE'];

		// Parse bytes range
		if ( ! preg_match( '/^bytes=(\d*)-(\d*)$/', $range, $matches ) ) {
			return null;
		}

		$start = $matches[1] !== '' ? (int) $matches[1] : 0;
		$end   = $matches[2] !== '' ? (int) $matches[2] : $file_size - 1;

		// Validate range
		if ( $start > $end || $start >= $file_size || $end >= $file_size ) {
			header( 'HTTP/1.1 416 Range Not Satisfiable' );
			header( 'Content-Range: bytes */' . $file_size );

			return null;
		}

		return [ 'start' => $start, 'end' => $end ];
	}

	/**
	 * Setup delivery environment.
	 *
	 * @param string $file_path Path to file being delivered.
	 *
	 * @return void
	 */
	private static function setup_environment( string $file_path ): void {
		// Clean output buffers
		while ( ob_get_level() > 0 ) {
			@ob_end_clean();
		}

		// Prevent timeouts for large files
		@set_time_limit( 0 );

		// Increase memory limit for large files
		$file_size = filesize( $file_path );
		if ( $file_size > 100 * 1024 * 1024 ) { // 100MB
			@ini_set( 'memory_limit', '256M' );
		}

		// Disable compression
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', 1 );
		}
		@ini_set( 'zlib.output_compression', 'Off' );
	}

	/**
	 * Deliver file via X-Sendfile or X-Accel-Redirect.
	 *
	 * @param string $file_path File path.
	 * @param array  $options   Delivery options.
	 *
	 * @return void
	 */
	private static function deliver_via_xsendfile( string $file_path, array $options ): void {
		// Set headers
		self::set_download_headers(
			$options['filename'],
			$options['mime_type'],
			! $options['force_download']
		);

		// Check server type
		$server = $_SERVER['SERVER_SOFTWARE'] ?? '';

		if ( stripos( $server, 'nginx' ) !== false ) {
			// Nginx uses X-Accel-Redirect with internal location
			$internal_path = apply_filters(
				'arraypress_file_nginx_internal_path',
				'/protected/',
				$file_path
			);
			header( 'X-Accel-Redirect: ' . $internal_path . basename( $file_path ) );
		} else {
			// Apache uses X-Sendfile with full path
			header( 'X-Sendfile: ' . $file_path );
		}
	}

	/**
	 * Stream file with optional range support.
	 *
	 * @param string $file_path File path.
	 * @param array  $options   Delivery options.
	 *
	 * @return void
	 */
	private static function stream_file( string $file_path, array $options ): void {
		$file_size = filesize( $file_path );

		// Set download headers
		self::set_download_headers(
			$options['filename'],
			$options['mime_type'],
			! $options['force_download']
		);

		// Handle range requests
		$range = null;
		if ( $options['enable_range'] ) {
			$range = self::parse_range_header( $file_size );
		}

		if ( $range !== null ) {
			// Partial content
			header( 'HTTP/1.1 206 Partial Content' );
			header( 'Accept-Ranges: bytes' );
			header( sprintf(
				'Content-Range: bytes %d-%d/%d',
				$range['start'],
				$range['end'],
				$file_size
			) );
			header( 'Content-Length: ' . ( $range['end'] - $range['start'] + 1 ) );

			self::read_file_chunked( $file_path, $range['start'], $range['end'], $options['chunk_size'] );
		} else {
			// Full content
			header( 'Accept-Ranges: ' . ( $options['enable_range'] ? 'bytes' : 'none' ) );
			header( 'Content-Length: ' . $file_size );

			self::read_file_chunked( $file_path, 0, $file_size - 1, $options['chunk_size'] );
		}
	}

	/**
	 * Read and output file in chunks.
	 *
	 * @param string $file_path  File path.
	 * @param int    $start      Start byte position.
	 * @param int    $end        End byte position.
	 * @param int    $chunk_size Chunk size in bytes.
	 *
	 * @return void
	 */
	private static function read_file_chunked( string $file_path, int $start, int $end, int $chunk_size ): void {
		$handle = @fopen( $file_path, 'rb' );

		if ( ! $handle ) {
			wp_die(
				__( 'Cannot open file for reading.', 'arraypress' ),
				__( 'Download Error', 'arraypress' ),
				[ 'response' => 500 ]
			);
		}

		// Seek to start position
		if ( $start > 0 ) {
			fseek( $handle, $start );
		}

		$bytes_sent    = 0;
		$bytes_to_send = $end - $start + 1;

		while ( ! feof( $handle ) && $bytes_sent < $bytes_to_send && connection_status() === CONNECTION_NORMAL ) {
			// Calculate chunk size for this iteration
			$chunk = min( $chunk_size, $bytes_to_send - $bytes_sent );

			// Read and output chunk
			$buffer = fread( $handle, $chunk );
			if ( $buffer === false ) {
				break;
			}

			echo $buffer;
			$bytes_sent += strlen( $buffer );

			// Flush periodically
			if ( $bytes_sent % ( 10 * 1024 * 1024 ) === 0 ) { // Every 10MB
				if ( ob_get_level() > 0 ) {
					@ob_flush();
				}
				@flush();
			}
		}

		fclose( $handle );

		// Final flush
		if ( ob_get_level() > 0 ) {
			@ob_flush();
		}
		@flush();
	}

}