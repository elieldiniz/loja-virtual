<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageUploader
{
    /**
     * Reduz a imagem para no máximo $maxWidth px de largura, converte para WebP e grava no disco.
     * Se o arquivo não puder ser processado, grava o original.
     *
     * @return string caminho gravado no disco
     */
    public static function storeResized(UploadedFile|TemporaryUploadedFile $file, string $directory, int $maxWidth, string $disk): string
    {
        $contents = (string) file_get_contents($file->getRealPath());
        $image = function_exists('imagecreatefromstring') ? @imagecreatefromstring($contents) : false;

        if ($image === false || ! function_exists('imagewebp')) {
            return $file->store($directory, $disk);
        }

        if (imagesx($image) > $maxWidth) {
            $image = imagescale($image, $maxWidth, -1, IMG_BICUBIC) ?: $image;
        }

        ob_start();
        imagewebp($image, null, 82);
        $webp = (string) ob_get_clean();

        $path = trim($directory, '/').'/'.Str::random(24).'.webp';
        Storage::disk($disk)->put($path, $webp, 'public');

        return $path;
    }
}
