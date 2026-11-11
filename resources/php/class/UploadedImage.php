<?php

declare(strict_types=1);

final class UploadedImage
{
    public static function clientName(string $name): ?string
    {
        $fileName = basename($name);

        return preg_match('/^[A-Za-z0-9._-]+\.(jpg|jpeg|png|gif|webp)$/i', $fileName) === 1
            ? $fileName
            : null;
    }

    /**
     * @param array<string, mixed> $file
     */
    public static function store(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null)) {
            throw new InvalidArgumentException('Image upload failed');
        }
        if (($file['size'] ?? 0) > 2_000_000) {
            throw new InvalidArgumentException('Image is too large');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        if (!is_string($mime) || !isset($extensions[$mime])) {
            throw new InvalidArgumentException('Unsupported image type');
        }

        $directory = dirname(__DIR__, 2) . '/image/spare';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Upload directory unavailable');
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $fileName)) {
            throw new InvalidArgumentException('Failed to store image');
        }

        return $fileName;
    }
}
