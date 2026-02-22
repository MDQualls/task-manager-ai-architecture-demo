<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Domain\Task\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status'      => ['nullable', new Enum(TaskStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'A task title is required.',
            'title.max'      => 'The title may not exceed 255 characters.',
            'status.Illuminate\Validation\Rules\Enum' => 'Status must be one of: ' . implode(', ', TaskStatus::values()) . '.',
        ];
    }
}
