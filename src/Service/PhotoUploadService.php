<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class PhotoUploadService
{
    private string $uploadsDirectory;
    private string $thumbnailsDirectory;
    private SluggerInterface $slugger;

    public function __construct(
        string $uploadsDirectory,
        string $thumbnailsDirectory,
        SluggerInterface $slugger
    ) {
        $this->uploadsDirectory = $uploadsDirectory;
        $this->thumbnailsDirectory = $thumbnailsDirectory;
        $this->slugger = $slugger;
    }

    public function uploadPhoto(UploadedFile $file, string $title): array
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        // Move the file to the uploads directory
        $file->move($this->uploadsDirectory, $fileName);

        // Generate thumbnails
        $thumbnails = $this->generateThumbnails($fileName);

        return [
            'filename' => $fileName,
            'thumbnails' => $thumbnails
        ];
    }

    public function generateThumbnails(string $fileName): array
    {
        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            error_log("GD extension not available. Skipping thumbnail generation.");
            return [];
        }

        $originalPath = $this->uploadsDirectory . '/' . $fileName;
        $thumbnails = [];

        // Define thumbnail sizes
        $sizes = [
            'small' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 600, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 1200]
        ];

        foreach ($sizes as $sizeName => $dimensions) {
            $thumbnailFileName = $sizeName . '_' . $fileName;
            $thumbnailPath = $this->thumbnailsDirectory . '/' . $thumbnailFileName;

            try {
                $this->createThumbnail($originalPath, $thumbnailPath, $dimensions['width'], $dimensions['height']);
                $thumbnails[$sizeName] = $thumbnailFileName;
            } catch (\Exception $e) {
                // Log error but continue with other sizes
                error_log("Failed to generate {$sizeName} thumbnail: " . $e->getMessage());
            }
        }

        return $thumbnails;
    }

    private function createThumbnail(string $sourcePath, string $destPath, int $maxWidth, int $maxHeight): void
    {
        // Get image info
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            throw new \Exception('Invalid image file');
        }

        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // Calculate new dimensions maintaining aspect ratio
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);

        // Create source image resource
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($sourcePath);
                break;
            default:
                throw new \Exception('Unsupported image type: ' . $mimeType);
        }

        if (!$sourceImage) {
            throw new \Exception('Failed to create image resource');
        }

        // Create thumbnail
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize image
        imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        // Save thumbnail
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($thumbnail, $destPath, 85);
                break;
            case 'image/png':
                imagepng($thumbnail, $destPath, 8);
                break;
            case 'image/gif':
                imagegif($thumbnail, $destPath);
                break;
            case 'image/webp':
                imagewebp($thumbnail, $destPath, 85);
                break;
        }

        // Clean up memory
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
    }

    public function deletePhoto(string $fileName, ?array $thumbnails = null): void
    {
        // Delete original file
        $originalPath = $this->uploadsDirectory . '/' . $fileName;
        if (file_exists($originalPath)) {
            unlink($originalPath);
        }

        // Delete thumbnails
        if ($thumbnails) {
            foreach ($thumbnails as $thumbnailFileName) {
                $thumbnailPath = $this->thumbnailsDirectory . '/' . $thumbnailFileName;
                if (file_exists($thumbnailPath)) {
                    unlink($thumbnailPath);
                }
            }
        }
    }

    public function regenerateThumbnailsForPhoto(string $src, string $title): array
    {
        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            error_log("GD extension not available. Cannot regenerate thumbnails.");
            return [];
        }

        $originalPath = $this->uploadsDirectory . '/' . $src;
        $thumbnails = [];

        // Check if original file exists
        if (!file_exists($originalPath)) {
            error_log("Original file not found: {$originalPath}");
            return [];
        }

        // Define thumbnail sizes
        $sizes = [
            'small' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 600, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 1200]
        ];

        foreach ($sizes as $sizeName => $dimensions) {
            $thumbnailFileName = $sizeName . '_' . $src;
            $thumbnailPath = $this->thumbnailsDirectory . '/' . $thumbnailFileName;

            try {
                $this->createThumbnail($originalPath, $thumbnailPath, $dimensions['width'], $dimensions['height']);
                $thumbnails[$sizeName] = $thumbnailFileName;
            } catch (\Exception $e) {
                // Log error but continue with other sizes
                error_log("Failed to regenerate {$sizeName} thumbnail for {$src}: " . $e->getMessage());
            }
        }

        return $thumbnails;
    }

    public function validateImageFile(UploadedFile $file): array
    {
        $errors = [];

        // Check file size (max 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            $errors[] = 'File size must be less than 10MB';
        }

        // Check file type
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            $errors[] = 'Only JPEG, PNG, GIF, and WebP images are allowed';
        }

        return $errors;
    }
}
