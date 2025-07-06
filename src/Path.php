<?php
/**
 * Path Utility Class
 *
 * Provides utility functions for working with file paths including
 * normalization, directory operations, and path parsing.
 *
 * @package ArrayPress\FileUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\FileUtils;

/**
 * Path Class
 *
 * Path manipulation and normalization utilities including directory
 * operations, path parsing, and relative/absolute conversions.
 */
class Path {

	/**
	 * Normalize a path by resolving . and .. segments.
	 *
	 * @param string $path The path to normalize.
	 *
	 * @return string Normalized path.
	 */
	public static function normalize( string $path ): string {
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
	 * Get parent directory path.
	 *
	 * @param string $path Current path.
	 *
	 * @return string Parent directory path.
	 */
	public static function get_parent( string $path ): string {
		$path       = rtrim( $path, '/' );
		$last_slash = strrpos( $path, '/' );

		if ( $last_slash === false ) {
			return '';
		}

		return substr( $path, 0, $last_slash + 1 );
	}

	/**
	 * Get directory name from path.
	 *
	 * @param string $path Path.
	 *
	 * @return string Directory name.
	 */
	public static function get_dirname( string $path ): string {
		return dirname( $path );
	}

	/**
	 * Get folder name from path.
	 *
	 * @param string $path Path.
	 *
	 * @return string Folder name.
	 */
	public static function get_folder_name( string $path ): string {
		$path  = rtrim( $path, '/' );
		$parts = explode( '/', $path );

		return end( $parts );
	}

	/**
	 * Check if path is a directory (ends with slash).
	 *
	 * @param string $path Path to check.
	 *
	 * @return bool True if path represents a directory.
	 */
	public static function is_directory_path( string $path ): bool {
		return substr( $path, - 1 ) === '/';
	}

	/**
	 * Check if directory exists.
	 *
	 * @param string $path Directory path.
	 *
	 * @return bool True if directory exists.
	 */
	public static function directory_exists( string $path ): bool {
		return is_dir( $path );
	}

	/**
	 * Create directory if it doesn't exist.
	 *
	 * @param string $path        Directory path.
	 * @param int    $permissions Directory permissions.
	 * @param bool   $recursive   Create parent directories.
	 *
	 * @return bool True on success.
	 */
	public static function create_directory( string $path, int $permissions = 0755, bool $recursive = true ): bool {
		if ( self::directory_exists( $path ) ) {
			return true;
		}

		return wp_mkdir_p( $path );
	}

	/**
	 * Get path parts for building breadcrumbs.
	 *
	 * @param string $path Full path.
	 *
	 * @return array Array of path segments with names and full paths.
	 */
	public static function get_parts( string $path ): array {
		$path         = rtrim( $path, '/' );
		$parts        = explode( '/', $path );
		$result       = [];
		$current_path = '';

		foreach ( $parts as $part ) {
			if ( empty( $part ) ) {
				continue;
			}

			$current_path .= $part . '/';
			$result[]     = [
				'name' => $part,
				'path' => $current_path
			];
		}

		return $result;
	}

	/**
	 * Join path segments.
	 *
	 * @param string ...$segments Path segments to join.
	 *
	 * @return string Joined path.
	 */
	public static function join( string ...$segments ): string {
		$path = '';

		foreach ( $segments as $segment ) {
			if ( empty( $segment ) ) {
				continue;
			}

			$segment = trim( $segment, '/' );
			if ( ! empty( $segment ) ) {
				$path = empty( $path ) ? $segment : $path . '/' . $segment;
			}
		}

		return $path;
	}

	/**
	 * Check if file is within a specific directory.
	 *
	 * @param string $file_path File path.
	 * @param string $directory Directory path.
	 *
	 * @return bool True if file is within directory.
	 */
	public static function is_within_directory( string $file_path, string $directory ): bool {
		$real_file_path = realpath( $file_path );
		$real_directory = realpath( $directory );

		if ( $real_file_path === false || $real_directory === false ) {
			return false;
		}

		return strpos( $real_file_path, $real_directory ) === 0;
	}

	/**
	 * Make path relative to base path.
	 *
	 * @param string $path      Full path.
	 * @param string $base_path Base path.
	 *
	 * @return string Relative path.
	 */
	public static function make_relative( string $path, string $base_path ): string {
		$path      = self::normalize( $path );
		$base_path = self::normalize( $base_path );

		if ( strpos( $path, $base_path ) === 0 ) {
			return ltrim( substr( $path, strlen( $base_path ) ), '/' );
		}

		return $path;
	}

	/**
	 * Convert relative path to absolute using base path.
	 *
	 * @param string $relative_path Relative path.
	 * @param string $base_path     Base path.
	 *
	 * @return string Absolute path.
	 */
	public static function to_absolute( string $relative_path, string $base_path ): string {
		if ( self::is_absolute( $relative_path ) ) {
			return $relative_path;
		}

		return self::normalize( $base_path . '/' . $relative_path );
	}

	/**
	 * Check if path is absolute.
	 *
	 * @param string $path Path to check.
	 *
	 * @return bool True if absolute path.
	 */
	public static function is_absolute( string $path ): bool {
		return substr( $path, 0, 1 ) === '/' || preg_match( '/^[a-zA-Z]:/', $path );
	}

	/**
	 * Get file extension from path.
	 *
	 * @param string $path File path.
	 *
	 * @return string File extension.
	 */
	public static function get_extension( string $path ): string {
		return File::get_extension( $path );
	}

	/**
	 * Change file extension in path.
	 *
	 * @param string $path          File path.
	 * @param string $new_extension New extension.
	 *
	 * @return string Path with new extension.
	 */
	public static function change_extension( string $path, string $new_extension ): string {
		$info          = pathinfo( $path );
		$new_extension = ltrim( $new_extension, '.' );

		return $info['dirname'] . '/' . $info['filename'] . '.' . $new_extension;
	}

}