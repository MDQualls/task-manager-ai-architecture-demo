<?php

declare(strict_types=1);

namespace App\Domain\Task\Repositories;

use App\Domain\Task\DTOs\NewTaskDTO;
use App\Domain\Task\DTOs\TaskDTO;

interface TaskRepositoryInterface
{
    /**
     * Find a single non-deleted task by its primary key.
     */
    public function findById(int $id): ?TaskDTO;

    /**
     * Return all non-deleted tasks ordered by creation date descending.
     *
     * @return TaskDTO[]
     */
    public function findAll(): array;

    /**
     * Persist a new task and return the hydrated DTO.
     */
    public function create(NewTaskDTO $dto): TaskDTO;

    /**
     * Update mutable fields of an existing task and return the refreshed DTO.
     * Returns null if the task does not exist or is already soft-deleted.
     */
    public function update(int $id, NewTaskDTO $dto): ?TaskDTO;

    /**
     * Soft-delete a task by setting is_deleted = true.
     * Returns true when a row was affected, false when not found.
     */
    public function delete(int $id): bool;
}
