# File Upload

File uploads and filesystem operations are handled through `App\Utils\FS`.
It is a small static helper that ships with the framework and wraps PHP's filesystem functions: moving uploaded files, creating and removing directories, and cleaning up files.

## Uploading a File

The framework's `App\Core\File` class builds on `FS` for the common case: it validates the extension, the upload size and the error status, creates the destination directory and moves the file.
Use it through the [request](/request) object.

```php
$file = Request::file('photo');

if ($file->isValid()) {
    $filename = $file->upload(APP_ROOT . '/uploads');
}
```

`upload()` returns the generated filename on success, or `false` on failure.
`Request::all()` also includes uploaded files under the `_files` key.

### Custom Extension and Size

Override the defaults (jpg, jpeg, png, pdf, doc, docx, zip, xls, xlsx, csv, ppt, pptx and a 5 MB limit).

```php
$file = Request::file('photo');

$file
    ->setAllowedFileTypes(['svg', 'webp'])
    ->setMaxUploadSize(10);

if ($file->isValid()) {
    $filename = $file->upload(APP_ROOT . '/uploads');
}
```

## Using FS Directly

When you need more control, call `FS` methods directly.

```php
use App\Utils\FS;

// Move a file yourself
$isUploaded = FS::upload($file['tmp_name'], $destination);

// Create a folder only if it does not exist
FS::makeDirectory(APP_ROOT . '/uploads/avatars');

// Check the extension before deciding what to do
$extension = FS::getFileExtension($file['name']);

if ($extension !== 'zip') {
    FS::upload($file['tmp_name'], $destination);
}

// Check whether a file is already there
if (FS::exists($destination)) {
    // replace it
}
```

## Cleaning Up

```php
// Remove a single file
FS::removeFile(APP_ROOT . '/uploads/old.txt');

// Empty a directory but keep the directory itself
FS::removeFilesInDirectory(APP_ROOT . '/uploads/tmp');

// Remove a directory and everything inside it
FS::removeDirectory(APP_ROOT . '/uploads/tmp');
```

## A Complete Controller

A controller that accepts a file upload, validates it and stores it.

```php
<?php
namespace App\Controllers;

use App\Core\Request;
use App\Utils\FS;

class UploadController extends Controller
{
    public function store(): void
    {
        $file = Request::file('photo');

        if (!$file->isValid()) {
            // invalid file, show an error
            return;
        }

        $filename = $file->upload(APP_ROOT . '/uploads');

        if (!$filename) {
            // the upload failed, show an error
            return;
        }

        // save $filename in the database
    }

    public function destroy($filename): void
    {
        FS::removeFile(APP_ROOT . '/uploads/' . $filename);
    }
}
```

## Notes

- Uploads live in a folder of your choice, for example `APP_ROOT . '/uploads'`.
- `makeDirectory()` creates nested directories recursively.
- All `FS` methods are static, so there is no instance to configure.