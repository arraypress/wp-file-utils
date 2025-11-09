# WP File Utils

A lightweight WordPress library providing essential file operation utilities, MIME type detection, and security validation for file handling.

## Installation

Install via Composer:

```bash
composer require arraypress/wp-file-utils
```

## Requirements

- PHP 7.4 or later
- WordPress 5.0 or later

## Features

- 🔄 **URL/Path Conversion** - Convert between file URLs and local paths
- 📁 **File Operations** - Path manipulation, filename extraction, extension handling
- 🔒 **Security Validation** - Safe filename checking, path sanitization
- 🎯 **MIME Detection** - Comprehensive MIME type detection and categorization
- 🚀 **Optimized Streaming** - Smart chunk size detection for file delivery
- 📦 **Zero Dependencies** - Lightweight with no external dependencies

## Core Classes

### File Class

Essential file operations and path/URL conversions.

```php
use ArrayPress\Utils\File;

// Convert between URLs and paths
$path = File::url_to_path( 'https://site.com/wp-content/uploads/file.pdf' );
$url = File::path_to_url( '/var/www/wp-content/uploads/file.pdf' );

// Check if file is local
if ( File::is_local_file( $url ) ) {
    $size = File::get_size( $path );
}

// Path and filename operations
$extension = File::get_extension( 'document.pdf' );     // 'pdf'
$filename = File::get_filename( '/path/to/file.pdf' );  // 'file'
$basename = File::get_basename( '/path/to/file.pdf' );  // 'file.pdf'
$directory = File::get_directory( '/path/to/file.pdf' ); // '/path/to'

// Change extension
$new_path = File::change_extension( 'image.jpg', 'png' ); // 'image.png'

// Sanitize filename
$safe = File::sanitize_filename( 'My File!!!.pdf', true ); // 'my-file.pdf'

// Join paths safely
$full_path = File::join_path( $upload_dir, '2024', 'documents', 'file.pdf' );
```

### Security Class

File security validation and path sanitization.

```php
use ArrayPress\Utils\Security;

// Check if filename is safe
if ( Security::is_safe_filename( $filename ) ) {
    // No path traversal, null bytes, or dangerous extensions
}

// Check allowed file types
$allowed = ['pdf', 'doc', 'docx'];
if ( Security::is_allowed_file_type( 'document.pdf', $allowed ) ) {
    // File type is permitted
}

// Sanitize file paths
$safe_path = Security::sanitize_path( $user_input );
// Removes dangerous protocols like phar://, php://, etc.
// Removes path traversal attempts (..)
```

### MIME Class

Comprehensive MIME type detection and categorization.

```php
use ArrayPress\Utils\MIME;

// Get MIME type from filename
$mime = MIME::get_type( 'document.pdf' ); // 'application/pdf'

// Get extension from MIME type
$ext = MIME::get_extension_from_type( 'application/pdf' ); // 'pdf'

// Determine delivery behavior
if ( MIME::should_force_download( $mime ) ) {
    // Force download (true for ZIP, DOC, etc.)
} else {
    // Display inline (false for PDF, images, video)
}

// Get optimal chunk size for streaming
$chunk_size = MIME::get_optimal_chunk_size( 'video/mp4' ); // 2097152 (2MB)

// Type checking
MIME::is_media( $mime );      // Audio or video
MIME::is_image( $mime );      // Image files
MIME::is_document( $mime );   // Office docs, PDFs, text
MIME::is_archive( $mime );    // ZIP, RAR, etc.

// Check if suitable for selling as digital product
if ( MIME::is_downloadable_product( $mime ) ) {
    // Suitable for e-commerce
}
```

## Supported MIME Types

The library includes comprehensive mappings for 70+ file types including:

- **Documents**: PDF, Word, Excel, PowerPoint, OpenDocument
- **Media**: MP3, MP4, WebM, AVI, MOV, OGG, WAV
- **Images**: JPEG, PNG, GIF, WebP, SVG, BMP, TIFF
- **Archives**: ZIP, RAR, 7Z, TAR, GZ, BZ2
- **E-books**: EPUB, MOBI, AZW
- **Text**: TXT, CSV, JSON, XML, HTML, CSS, JS
- **Fonts**: TTF, OTF, WOFF, WOFF2
- And many more...

## Optimal Chunk Sizes

The library automatically determines optimal chunk sizes for streaming:

- **Video files**: 2MB chunks for smooth streaming
- **Archives**: 4MB chunks for faster downloads
- **Audio files**: 1MB chunks
- **Images**: 512KB chunks (2MB for large formats like PSD)
- **Documents**: 1MB chunks
- **Default**: 1MB chunks

## Integration Examples

### With Protected Folders

```php
use ArrayPress\Utils\MIME;

class Delivery {
    private function detect_mime_type( string $file_path ): string {
        return MIME::get_type( $file_path );
    }
    
    private function should_force_download( string $mime_type ): bool {
        return MIME::should_force_download( $mime_type );
    }
}
```

### With E-Commerce (SugarCart)

```php
use ArrayPress\Utils\File;
use ArrayPress\Utils\MIME;

// Convert URL to local path for file operations
$local_path = File::url_to_path( $download_url );

if ( $local_path && File::is_readable( $local_path ) ) {
    $file_size = File::get_size( $local_path );
    $mime_type = MIME::get_type( $local_path );
    
    // Store file metadata
    $file_data = [
        'path' => $local_path,
        'size' => $file_size,
        'type' => $mime_type,
        'name' => File::get_basename( $local_path )
    ];
}
```

### File Upload Validation

```php
use ArrayPress\Utils\Security;
use ArrayPress\Utils\MIME;

function validate_upload( $filename, $tmp_path ) {
    // Security checks
    if ( ! Security::is_safe_filename( $filename ) ) {
        return new WP_Error( 'unsafe_filename', 'Filename contains unsafe characters' );
    }
    
    // Check allowed types
    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'png'];
    if ( ! Security::is_allowed_file_type( $filename, $allowed ) ) {
        return new WP_Error( 'invalid_type', 'File type not allowed' );
    }
    
    // Verify MIME type matches extension
    $mime = MIME::get_type( $filename );
    if ( MIME::is_downloadable_product( $mime ) ) {
        // Process as digital product
    }
    
    return true;
}
```

## Why Use This Library?

### What WordPress Doesn't Provide

While WordPress has many file functions, it lacks:
- **URL to path conversion** - No built-in way to convert URLs to local file paths
- **Path to URL conversion** - No reverse conversion function
- **Smart delivery detection** - No logic for inline vs download behavior
- **Optimized chunk sizes** - No MIME-based chunk optimization
- **Comprehensive MIME mappings** - Limited file type coverage

### Benefits Over DIY Solutions

- **Battle-tested** - Used in production e-commerce and file delivery systems
- **Performance optimized** - Smart defaults based on file types
- **Security focused** - Protects against path traversal and dangerous files
- **WordPress integrated** - Falls back to WordPress functions when appropriate
- **Minimal footprint** - Just ~500 lines of focused utility code
- **Well documented** - Comprehensive PHPDoc blocks

## License

GPL-2.0-or-later

## Credits

Created and maintained by [David Sherlock](https://davidsherlock.com) at [ArrayPress](https://arraypress.com).

## Support

For bugs and feature requests, please visit the [GitHub repository](https://github.com/arraypress/wp-file-utils).