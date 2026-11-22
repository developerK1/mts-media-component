# Image processing

## Implemented GD pipeline

The upload service now invokes the GD processing engine after validation. It does not copy the uploaded bytes directly into permanent storage.

For each accepted image, the processor:

1. Checks that the server GD build supports the detected JPEG, PNG, or WebP format.
2. Decodes the image.
3. Applies a pixel-count safety limit before decoding.
4. Re-encodes a sanitised canonical original, stripping metadata not represented by the decoded pixel data.
5. Generates configured thumbnail, medium, and large variants.
6. Preserves transparency for PNG and WebP; JPEG output uses a white background.
7. Avoids upscaling smaller source images.
8. Removes partially written output files if processing fails.

## Default variants

| Variant | Maximum box |
|---|---:|
| thumbnail | 320 × 320 |
| medium | 900 × 900 |
| large | 1600 × 1600 |

The image is fitted inside each box while preserving its aspect ratio. It is not cropped.

## Output format and quality

The canonical image and its variants retain the detected source format:
- JPEG uses configurable JPEG quality.
- PNG uses configurable PNG compression level.
- WebP uses configurable WebP quality.

The defaults are in `config/media.example.php`. The default pixel ceiling is 40 million pixels. Hosting memory limits may require a lower ceiling for constrained shared-hosting environments.

## Storage layout

Files are stored under the configured private root, for example:

```text
private-media-storage/
└── ab/
    ├── <media-uuid>.jpg
    └── <media-uuid>_variants/
        ├── thumbnail.jpg
        ├── medium.jpg
        └── large.jpg
```

The example above is illustrative; the generated prefix and media UUID are created by the component. This directory must be outside the public web root.

## Current boundary

This phase writes processed files and returns their technical metadata to the trusted application backend. Database persistence, public delivery URLs, authorisation integration, and browser gallery rendering are not part of this phase.
