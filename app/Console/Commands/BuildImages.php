<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Turns originals in resources/images/source into responsive AVIF + WebP files in public/img and
 * writes public/img/manifest.json (sizes per image) for the <x-picture> component.
 * Part of the performance budget: nothing ships as a raw JPEG or PNG.
 */
class BuildImages extends Command
{
    protected $signature = 'images:build {--force : Rebuild even if outputs are newer than the source}';

    protected $description = 'Generate responsive AVIF/WebP images from resources/images/source';

    private const WIDTHS = [480, 800, 1200, 1600];

    private function resize(\GdImage $img, int $w, int $h, int $width): \GdImage
    {
        $height = (int) round($h * $width / $w);
        $out = imagecreatetruecolor($width, $height);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
        imagecopyresampled($out, $img, 0, 0, 0, 0, $width, $height, $w, $h);

        return $out;
    }

    public function handle(): int
    {
        $src = resource_path('images/source');
        $dst = public_path('img');
        File::ensureDirectoryExists($dst);

        $manifest = [];
        $totalBytes = 0;

        foreach (File::files($src) as $file) {
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                continue;
            }

            $name = $file->getFilenameWithoutExtension();
            // Detect the real format from content: some source files have a wrong extension.
            $img = @imagecreatefromstring(file_get_contents($file->getPathname()));
            if (! $img) {
                $this->error("Cannot read {$file->getFilename()}");

                return self::FAILURE;
            }
            imagepalettetotruecolor($img);
            imagealphablending($img, false);
            imagesavealpha($img, true);

            $w = imagesx($img);
            $h = imagesy($img);
            $widths = array_values(array_unique(array_merge(
                array_filter(self::WIDTHS, fn ($x) => $x < $w),
                [$w]
            )));
            sort($widths);

            foreach ($widths as $width) {
                $scaled = $width === $w ? $img : $this->resize($img, $w, $h, $width);

                $avif = "{$dst}/{$name}-{$width}.avif";
                $webp = "{$dst}/{$name}-{$width}.webp";
                imageavif($scaled, $avif, 52, 6);
                imagewebp($scaled, $webp, 74);
                $totalBytes += filesize($avif) + filesize($webp);

                if ($scaled !== $img) {
                    imagedestroy($scaled);
                }
            }
            imagedestroy($img);

            $manifest[$name] = ['w' => $w, 'h' => $h, 'widths' => $widths];
            $this->line(sprintf('  %-22s %4d x %-4d  widths: %s', $name, $w, $h, implode(', ', $widths)));
        }

        File::put("{$dst}/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info(sprintf('Built %d images, %.0f KB total.', count($manifest), $totalBytes / 1024));

        return self::SUCCESS;
    }
}
