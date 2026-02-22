<?php

declare(strict_types=1);

namespace App\Domain\Task\Actions;

use App\Domain\Task\Repositories\TaskRepositoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class DeleteTaskAction
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository,
        private readonly LoggerInterface         $logger,
    ) {}

    /**
     * Soft-deletes the task (sets is_deleted = true).
     *
     * Returns true when a row was affected, false when the task was not found
     * or is already deleted. Throws on infrastructure failures.
     *
     * @throws Throwable
     */
    public function __invoke(int $id): bool
    {
        try {
            return $this->repository->delete($id);
        } catch (Throwable $e) {
            $this->logger->error('DeleteTaskAction — failed to delete task', [
                'task_id'   => $id,
                'exception' => get_class($e),
                'error'     => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
