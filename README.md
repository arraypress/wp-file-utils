# WordPress File Utils

Comprehensive file operations, path manipulation, MIME type handling, and secure filesystem operations for WordPress. Clean APIs that integrate seamlessly with WordPress core functions.

## Installation

```bash
composer require arraypress/wp-file-utils
```

## Usage

### File Operations

```php
use ArrayPress\FileUtils\File;

// File info
$extension = File::get_extension( 'document.pdf' ); // pdf
$basename  = File::get_basename( '/path/to/file.jpg' ); // file.jpg
$name      = File::get_name( 'photo.png' ); // photo
$category  = File::get_category( 'video.mp4' ); // video
$type      = File::get_type( 'document.docx' ); // Word Document

// File type checking
$is_image    = File::is_image( 'photo.jpg' );
$is_document = File::is_document( 'report.pdf' );
$is_allowed  = File::is_allowed_type( 'file.txt' );

// File operations
$exists    = File::exists( '/path/to/file.txt' );
$size      = File::get_size( '/path/to/file.txt' ); // bytes
$formatted = File::format_size( 1024 ); // 1 KB
$content   = File::get_contents( '/path/to/file.txt' );
File::put_contents( '/path/to/file.txt', 'content' );

// File management
File::copy( '/source.txt', '/destination.txt' );
File::move( '/old/path.txt', '/new/path.txt' );
File::delete( '/path/to/file.txt' );
```

### Path Manipulation

```php
use ArrayPress\FileUtils\Path;

// Path normalization
$clean  = Path::normalize( '/path/../to/./file' ); // /to/file
$parent = Path::get_parent( '/path/to/file' ); // /path/to/
$joined = Path::join( 'path', 'to', 'file' ); // path/to/file

// Directory operations
Path::create_directory( '/new/directory' );
$exists = Path::directory_exists( '/path' );
$is_dir = Path::is_directory_path( '/path/' );

// Path analysis
$parts       = Path::get_parts( '/path/to/file' );
$relative    = Path::make_relative( '/full/path', '/full' );
$absolute    = Path::to_absolute( 'relative/path', '/base' );
$is_absolute = Path::is_absolute( '/absolute/path' );

// Path utilities
$within  = Path::is_within_directory( '/file/path', '/allowed/dir' );
$new_ext = Path::change_extension( '/file.txt', 'md' ); // /file.md
```

### MIME Type Handling

```php
use ArrayPress\FileUtils\MIME;

// MIME conversion
$mime = MIME::get_type_from_extension( 'jpg' ); // image/jpeg
$ext  = MIME::get_extension_from_type( 'image/png' ); // png

// Type checking
$is_image    = MIME::is_type( 'image/jpeg', 'image' );
$is_document = MIME::is_type( 'application/pdf', 'document' );
$is_allowed  = MIME::is_allowed( 'image/png', [ 'image', 'document' ] );

// Descriptions
$description = MIME::get_description( 'application/pdf' ); // PDF Document
$category    = MIME::get_general_type( 'image/jpeg' ); // image

// WordPress integration
$allowed_types = MIME::get_allowed_types(); // WordPress allowed MIME types
$allowed_exts  = MIME::get_allowed_extensions(); // WordPress allowed extensions
$additional    = MIME::get_additional_types(); // Modern formats (WebP, AVIF, etc.)
```

### Security & Validation

```php
use ArrayPress\FileUtils\Security;

// Path sanitization
$safe_path     = Security::sanitize_path( 'dangerous://path' );
$safe_filename = Security::sanitize_filename( 'bad<file>.txt' );

// Filename validation
$is_safe    = Security::is_safe_filename( 'document.pdf' );
$is_allowed = Security::is_allowed_file_type( 'file.jpg', [ 'image' ] );

// Directory validation
$within_dir   = Security::is_within_directory( '/file', '/allowed' );
$valid_upload = Security::is_valid_upload_path( '/uploads/dir' );

// Protocol management
Security::add_protocol( 'dangerous' );
$protocols = Security::get_protocols();
Security::reset_protocols();
```

### WordPress Filesystem Integration

```php
use ArrayPress\FileUtils\FileSystem;

// WordPress filesystem
FileSystem::init(); // Initialize WP filesystem
$fs        = FileSystem::get_filesystem(); // Get WP_Filesystem instance
$is_direct = FileSystem::is_direct(); // Check if using direct filesystem

// Smart file operations (auto-detects filesystem type)
$content = FileSystem::get_contents( '/path/to/file' );
FileSystem::put_contents( '/path/to/file', 'content' );
$exists = FileSystem::exists( '/path/to/file' );
$size   = FileSystem::get_size( '/path/to/file' );

// File operations
FileSystem::copy( '/source', '/destination' );
FileSystem::move( '/old/path', '/new/path' );
FileSystem::delete( '/path/to/file' );

// Directory operations
FileSystem::mkdir( '/new/directory' );
$is_dir = FileSystem::is_dir( '/path' );

// WordPress helpers
$uploaded     = FileSystem::maybe_move_from_uploads( 'file.txt', '/new/path' );
$uploads_info = FileSystem::get_uploads_info();
$in_uploads   = FileSystem::is_in_uploads( '/path/to/file' );

// Advanced operations
$lines  = FileSystem::get_file_array( '/path/to/file.txt' );
$handle = FileSystem::fopen( '/path/to/file', 'r' );
FileSystem::symlink( '/target', '/link' );
```

## Common Use Cases

**File upload processing:**
```php
$filename = $_FILES['upload']['name'];
$tmp_path = $_FILES['upload']['tmp_name'];

// Validate file
if ( ! File::is_allowed_type( $filename ) ) {
	die( 'File type not allowed' );
}

if ( ! Security::is_safe_filename( $filename ) ) {
	$filename = Security::sanitize_filename( $filename );
}

// Process upload
$upload_dir  = FileSystem::get_uploads_info()['basedir'];
$destination = Path::join( $upload_dir, 'custom', $filename );
FileSystem::move( $tmp_path, $destination );
```

**Directory organization:**
```php
$files = glob( '/uploads/*' );

foreach ( $files as $file ) {
	$category     = File::get_category( $file );
	$category_dir = Path::join( '/uploads', $category );

	Path::create_directory( $category_dir );

	$new_path = Path::join( $category_dir, File::get_basename( $file ) );
	File::move( $file, $new_path );
}
```

**File analysis:**
```php
$file_info = [
	'name'      => File::get_name( $filepath ),
	'extension' => File::get_extension( $filepath ),
	'category'  => File::get_category( $filepath ),
	'type'      => File::get_type( $filepath ),
	'size'      => File::format_size( File::get_size( $filepath ) ),
	'mime'      => File::get_mime_type( $filepath ),
	'is_image'  => File::is_image( $filepath ),
	'is_safe'   => Security::is_safe_filename( $filepath )
];
```

## All Classes

- **File** - Core file operations, type detection, basic I/O
- **Path** - Path manipulation, normalization, directory operations
- **MIME** - MIME type handling, WordPress integration
- **Security** - Path sanitization, filename validation, protocol filtering
- **FileSystem** - WordPress filesystem integration, smart operations

## Requirements

- PHP 7.4+
- WordPress 5.0+

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-2.0-or-later License.

## Support

- [Documentation](https://github.com/arraypress/wp-file-utils)
- [Issue Tracker](https://github.com/arraypress/wp-file-utils/issues)