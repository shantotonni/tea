<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ConvertImagesToWebp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:convert-webp 
                            {path=public/images : Path to directory relative to base_path} 
                            {--quality=82 : WebP image compression quality (1-100)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recursively converts all JPEG/PNG images in a directory to WebP format for optimal performance.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $relativePath = $this->argument('path');
        $quality = (int) $this->option('quality');
        $targetDir = base_path($relativePath);

        if (!is_dir($targetDir)) {
            $this->error("Directory not found: {$targetDir}");
            return 1;
        }

        $this->info("Scanning directory: {$targetDir}");
        $this->info("Quality setting: {$quality}%");

        $dir = new RecursiveDirectoryIterator($targetDir);
        $iterator = new RecursiveIteratorIterator($dir);
        $totalOld = 0;
        $totalNew = 0;
        $count = 0;

        foreach ($iterator as $file) {
            if ($file->isDir()) continue;
            
            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $filePath = $file->getPathname();
                $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $filePath);
                
                $oldSize = filesize($filePath);
                $totalOld += $oldSize;

                $img = false;
                if ($ext === 'png') {
                    $img = @imagecreatefrompng($filePath);
                } else {
                    $img = @imagecreatefromjpeg($filePath);
                }

                if ($img) {
                    imagepalettetotruecolor($img);
                    imagealphablending($img, true);
                    imagesavealpha($img, true);
                    imagewebp($img, $webpPath, $quality);
                    imagedestroy($img);

                    if (file_exists($webpPath)) {
                        $newSize = filesize($webpPath);
                        $totalNew += $newSize;
                        $count++;
                        $relPath = str_replace($targetDir, '', $filePath);
                        $reduction = round((1 - ($newSize / $oldSize)) * 100, 1);
                        $this->line(sprintf("<info>✓ %s</info>: %s KB -> %s KB (-%s%%)", $relPath, round($oldSize/1024), round($newSize/1024), $reduction));
                    }
                }
            }
        }

        $this->newLine();
        $this->info("============================================");
        $this->info(sprintf("Total Images Converted: %d", $count));
        $this->info(sprintf("Original Total Size:    %.2f MB", $totalOld / 1024 / 1024));
        $this->info(sprintf("Optimized WebP Size:   %.2f MB", $totalNew / 1024 / 1024));
        $this->info(sprintf("Total Data Saved:      %.2f MB (-%.1f%%)", ($totalOld - $totalNew) / 1024 / 1024, $totalOld > 0 ? (1 - $totalNew/$totalOld)*100 : 0));
        $this->info("============================================");

        return 0;
    }
}
