<?php

return [
    'engine_url' => env('VECTOR_DECODER_ENGINE_URL', 'http://127.0.0.1:8001'),
    'engine_internal_token' => env('VECTOR_DECODER_ENGINE_INTERNAL_TOKEN', 'change-me'),
    'storage_disk' => env('VECTOR_DECODER_STORAGE_DISK', 'local'),
    'results_prefix' => env('VECTOR_DECODER_RESULTS_PREFIX', 'vector-decoder/results'),
    'allowed_host' => env('VECTOR_DECODER_ALLOWED_HOST', 'vectorizer.ai'),
    'task_ttl_hours' => (int) env('VECTOR_DECODER_TASK_TTL_HOURS', 24),
    'queue_name' => 'conversions',
    'web_ui_api_key_name' => env('VECTOR_DECODER_WEB_UI_API_KEY_NAME', 'web-ui'),
    'web_ui_upload_max_kb' => (int) env('VECTOR_DECODER_WEB_UI_UPLOAD_MAX_KB', 10240),
    'web_ui_default_headless' => (bool) env('VECTOR_DECODER_WEB_UI_DEFAULT_HEADLESS', false),
    'web_ui_default_max_wait_seconds' => (int) env('VECTOR_DECODER_WEB_UI_DEFAULT_MAX_WAIT_SECONDS', 240),
    'web_ui_default_idle_seconds' => (int) env('VECTOR_DECODER_WEB_UI_DEFAULT_IDLE_SECONDS', 6),
    'billing_enabled' => (bool) env('VECTOR_DECODER_BILLING_ENABLED', true),
    'billing_enforce_web_upload' => (bool) env('VECTOR_DECODER_BILLING_ENFORCE_WEB_UPLOAD', true),
    'billing_credit_cost_per_task' => (int) env('VECTOR_DECODER_BILLING_CREDIT_COST_PER_TASK', 1),
];
