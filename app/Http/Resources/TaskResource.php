<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Task\DTOs\TaskDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaskDTO
 */
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var TaskDTO $task */
        $task = $this->resource;

        return [
            'id'          => $task->id,
            'title'       => $task->title,
            'description' => $task->description,
            'status'      => $task->status->value,
            'status_label'=> $task->status->label(),
            'is_deleted'  => $task->is_deleted,
            'created_at'  => $task->created_at->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
