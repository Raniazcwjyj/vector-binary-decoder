<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateApiKeyCommand extends Command
{
    protected $signature = 'vector-decoder:create-api-key {name} {--rate=60}';
    protected $description = 'Create an API key for vector decoder APIs.';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $rate = max(1, (int) $this->option('rate'));
        $plain = (string) Str::uuid() . Str::random(16);

        $model = ApiKey::query()->create([
            'name' => $name,
            'key_hash' => hash('sha256', $plain),
            'is_active' => true,
            'rate_limit_per_min' => $rate,
        ]);

        $this->info("Created API key id={$model->id} name={$model->name}");
        $this->warn("Plain API key (show once): {$plain}");
        return self::SUCCESS;
    }
}
