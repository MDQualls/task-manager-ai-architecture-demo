<?php

declare(strict_types=1);

namespace App\Domain\Task\DTOs;

use App\Domain\Task\Enums\TaskStatus;

/**
 * Data required for inserting a new task record into the database.
 * Excludes auto-generated fields: id, created_at, is_deleted.
 */
final class NewTaskDTO
{
    public function __construct(
        public readonly string     $title,
        public readonly ?string    $description,
        public readonly TaskStatus $status,
    ) {}
}
