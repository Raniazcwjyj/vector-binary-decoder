<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Throwable;

class TaskResultService
{
    public function saveSvg(string $taskId, string $svg): array
    {
        $disk = (string) config('vector_decoder.storage_disk', 'local');
        $prefix = trim((string) config('vector_decoder.results_prefix', 'vector-decoder/results'), '/');
        $path = "{$prefix}/{$taskId}/output.svg";

        $ok = Storage::disk($disk)->put($path, $svg);
        if ($ok !== true || !Storage::disk($disk)->exists($path)) {
            throw new \RuntimeException("Failed to persist SVG to disk={$disk}, path={$path}");
        }

        return [
            'disk' => $disk,
            'path' => $path,
            'size' => strlen($svg),
        ];
    }

    public function getSvg(string $path): ?string
    {
        $disk = (string) config('vector_decoder.storage_disk', 'local');
        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        try {
            if (Storage::disk($disk)->exists($normalized)) {
                return Storage::disk($disk)->get($normalized);
            }
        } catch (Throwable) {
            // Fall through to direct file reads when Storage disk checks fail.
        }

        $candidates = [
            storage_path('app/private/' . $normalized),
            storage_path('app/' . $normalized),
            $path,
        ];

        foreach ($candidates as $file) {
            if (is_file($file) && is_readable($file)) {
                $content = @file_get_contents($file);
                if ($content !== false) {
                    return $content;
                }
            }
        }

        return null;
    }
}
