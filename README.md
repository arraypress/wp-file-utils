# WordPress File Utils

Streamlined file operations for WordPress focusing on URL/path conversion, MIME type detection, and secure file delivery.

## Installation

```bash
composer require arraypress/wp-file-utils
```

## Usage

### File Operations

```php
use ArrayPress\FileUtils\File;

// URL and path conversion
$path = File::url_to_path( 'https://site.com/wp-content/uploads/file.pdf' );
$url = File::path_to_url( '/var/www/wp-content/uploads/file.pdf' );
$is_local = File::is_local_file( 'https://site.com/file.pdf' );

// File information
$extension = File::get_extension( 'document.pdf' ); // 'pdf'
$basename = File::get_basename( '/path/to/file.jpg' ); // 'file.jpg'
$path = File::normalize_path( '/path/../to/./file' ); // '/to/file'

// Remote file operations
$size = File::get_size_from_url( 'https://example.com/file.zip' );
$exists = File::url_exists( 'https://example.com/file.pdf' );

// Directory checks
$within = File::is_within_directory( '/file/path', '/allowed/dir' );
$in_uploads = File::is_in_uploads( '/path/to/file' );
```

### MIME Type Handling

```php
use ArrayPress\FileUtils\MIME;

// Type detection
$mime = MIME::get_type( 'document.pdf' ); // 'application/pdf'
$mime = MIME::get_type_from_extension( 'jpg' ); // 'image/jpeg'
$ext = MIME::get_extension_from_type( 'image/png' ); // 'png'

// Category checking
$category = MIME::get_category( 'application/pdf' ); // 'document'
$is_image = MIME::is_type( 'image/jpeg', 'image' ); // true
$is_allowed = MIME::is_allowed( 'image/png', ['image', 'document'] );
```

### Security

```php
use ArrayPress\FileUtils\Security;

// Path sanitization
$safe = Security::sanitize_path( 'phar://dangerous/path' );
$is_safe = Security::is_safe_filename( 'document.pdf' );

// File type validation
$allowed = Security::is_allowed_file_type( 'file.jpg', ['image', 'pdf'] );
$valid_upload = Security::validate_upload_path( '/uploads/dir' );

// Safe filename generation
$unique = Security::generate_safe_filename( 'file.txt', '/uploads', true );
```

### File Delivery

```php
use ArrayPress\FileUtils\Delivery;

// Stream a file with resume support
Delivery::stream( '/path/to/file.pdf', [
    'filename' => 'download.pdf',
    'force_download' => true,
    'enable_range' => true
] );

// Check server capabilities
if ( Delivery::supports_xsendfile() ) {
    Delivery::stream( $file, ['enable_xsendfile' => true] );
}

// Set download headers manually
Delivery::set_download_headers( 'report.pdf', 'application/pdf' );

// Parse range requests
$range = Delivery::parse_range_header( filesize( $file ) );
```

## Common Use Cases

**Convert CDN URL to local path:**
```php
$cdn_url = 'https://cdn.example.com/wp-content/uploads/2024/01/file.pdf';
if ( File::is_local_file( $cdn_url ) ) {
    $local_path = File::url_to_path( $cdn_url );
    $file_size = filesize( $local_path );
}
```

**Validate uploaded file:**
```php
$filename = $_FILES['upload']['name'];

if ( ! Security::is_safe_filename( $filename ) ) {
    wp_die( 'Invalid filename' );
}

if ( ! Security::is_allowed_file_type( $filename, ['image', 'pdf'] ) ) {
    wp_die( 'File type not allowed' );
}
```

**Serve protected download:**
```php
// Verify user has access
if ( ! current_user_can( 'download_files' ) ) {
    wp_die( 'Access denied' );
}

// Stream file with resume support
Delivery::stream( $protected_file, [
    'filename' => 'purchased-content.zip',
    'force_download' => true,
    'enable_range' => true,
    'enable_xsendfile' => true
] );
```

## Classes

- **File** - URL/path conversion, local file detection
- **MIME** - MIME type detection and categorization
- **Security** - Path sanitization and filename validation
- **Delivery** - Secure file streaming with range support

## Requirements

- PHP 7.4+
- WordPress 5.0+

## License

GPL-2.0-or-later