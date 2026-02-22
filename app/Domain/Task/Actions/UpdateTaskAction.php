<?php

declare(strict_types=1);

namespace App\Domain\Task\Actions;

use App\Domain\Task\DTOs\NewTaskDTO;
use App\Domain\Task\DTOs\TaskDTO;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class UpdateTaskAction
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository,
        private readonly LoggerInterface         $logger,
    ) {}

    /**
     * Returns null when the task does not exist or is soft-deleted.
     * Throws on infrastructure failures.
     *
     * @throws Throwable
     */
    public function __invoke(int $id, NewTaskDTO $dto): ?TaskDTO
    {
        try {
            return $this->repository->update($id, $dto);
        } catch (Throwable $e) {
            $this->logger->error('UpdateTaskAction — failed to update task', [
                'task_id'   => $id,
                'title'     => $dto->title,
                'status'    => $dto->status->value,
                'exception' => get_class($e),
                'error'     => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
