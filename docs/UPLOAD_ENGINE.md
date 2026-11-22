# Upload engine usage

The upload engine is a backend component. The consuming application must authenticate the request, validate its CSRF token, and check any application-specific permissions before invoking it.

## Single upload

```php
<?php

require_once '/path/to/mts-media-component/bootstrap.php';

$config = mts_media_config('/path/to/app/config/media.php');
$uploader = new \\MTS\\Media\\Upload\\MediaUploadService($config);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['image'])) {
    http_response_code(400);
    exit('Invalid upload request.');
}

$result = $uploader->upload($_FILES['image']);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_THROW_ON_ERROR);
```

## Multiple uploads

The HTML form must use `enctype="multipart/form-data"` and a multiple file input named `images[]`.

```php
$results = $uploader->uploadMany($_FILES['images']);
echo json_encode($results, JSON_THROW_ON_ERROR);
```

## Response shape

Successful uploads return a `media` object containing a UUID media ID, sanitised original filename, detected MIME type, extension, byte size, dimensions, SHA-256 checksum, and internal relative storage key.

Failed uploads return `success: false` and a safe error message. The service intentionally does not return absolute filesystem paths or exception traces.

## Important current limitation

This phase stores the validated original file in private storage. It does not yet write metadata to MySQL, re-encode or resize the image, generate variants, or provide a delivery URL. Those are later phases. The storage key is currently returned to the trusted backend caller only; do not expose it in browser output.
