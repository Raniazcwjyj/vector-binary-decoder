<?php

namespace App\Console\Commands;

use App\Models\ConversionTask;
use App\Services\TaskResultService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CleanupExpiredTasksCommand extends Command
{
    protected $signature = 'vector-decoder:cleanup-expired';
    protected $description = 'Cleanup expired task results and old records.';

    public function handle(): int
    {
        $hours = (int) config('vector_decoder.task_ttl_hours', 24);
        $deadline = now()->subHours($hours);
        $disk = (string) config('vector_decoder.storage_disk', 'local');

        $tasks = ConversionTask::query()
            ->whereNotNull('finished_at')
            ->where('finished_at', '<', $deadline)
            ->get();

        $count = 0;
        DB::transaction(function () use ($tasks, $disk, &$count) {
            foreach ($tasks as $task) {
                if ($task->result_path && Storage::disk($disk)->exists($task->result_path)) {
                    Storage::disk($disk)->delete($task->result_path);
                }
                $task->events()->delete();
                $task->delete();
                $count++;
            }
        });

        $this->info("Cleanup done. deleted_tasks={$count}");
        return self::SUCCESS;
    }
}
