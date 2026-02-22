<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Domain\Task\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // PUT  → all fields required (full replacement)
        // PATCH → only provided fields validated (partial update)
        $required = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'title'       => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status'      => [$required, new Enum(TaskStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'A task title is required.',
            'title.max'      => 'The title may not exceed 255 characters.',
            'status.required'=> 'A status is required.',
            'status.Illuminate\Validation\Rules\Enum' => 'Status must be one of: ' . implode(', ', TaskStatus::values()) . '.',
        ];
    }
}
