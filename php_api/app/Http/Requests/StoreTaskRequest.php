<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048'],
            'width' => ['nullable', 'integer', 'min:1', 'max:4096'],
            'height' => ['nullable', 'integer', 'min:1', 'max:4096'],
            'channel' => ['nullable', 'string', 'max:20'],
            'headless' => ['nullable', 'boolean'],
            'max_wait_seconds' => ['nullable', 'integer', 'min:1', 'max:300'],
            'idle_seconds' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $url = (string) $this->input('url');
            if (!preg_match('/^https:\\/\\/([a-z0-9-]+\\.)*vectorizer\\.ai(\\/.*)?$/i', $url)) {
                $validator->errors()->add('url', 'URL must match https://*.vectorizer.ai/*');
            }
        });
    }
}
