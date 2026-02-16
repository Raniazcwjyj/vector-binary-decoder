<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\ApiKey;
use App\Models\ConversionTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_api_key(): void
    {
        $this->postJson('/api/v1/tasks', [])->assertStatus(401);
    }

    public function test_can_query_task_status(): void
    {
        $api = ApiKey::query()->create([
            'key_hash' => hash('sha256', 'plain-key'),
            'name' => 'test',
            'is_active' => true,
            'rate_limit_per_min' => 60,
        ]);

        $task = ConversionTask::query()->create([
            'id' => (string) Str::uuid(),
            'api_key_id' => $api->id,
            'status' => TaskStatus::QUEUED->value,
            'request_json' => ['url' => 'https://vectorizer.ai/'],
        ]);

        $this
            ->withHeader('X-API-Key', 'plain-key')
            ->getJson("/api/v1/tasks/{$task->id}")
            ->assertStatus(200)
            ->assertJsonPath('status', TaskStatus::QUEUED->value);
    }
}
