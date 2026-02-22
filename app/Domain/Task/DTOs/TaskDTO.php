<?php

declare(strict_types=1);

namespace App\Domain\Task\DTOs;

use App\Domain\Task\Enums\TaskStatus;
use DateTimeImmutable;

/**
 * Full Task representation returned from the domain layer.
 * Includes all persisted properties — read-only by design.
 */
final class TaskDTO
{
    public function __construct(
        public readonly int               $id,
        public readonly string            $title,
        public readonly ?string           $description,
        public readonly TaskStatus        $status,
        public readonly DateTimeImmutable $created_at,
        public readonly bool              $is_deleted,
    ) {}
}
