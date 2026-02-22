<?php

declare(strict_types=1);

namespace App\Domain\Task\Actions;

use App\Domain\Task\DTOs\TaskDTO;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class GetTaskAction
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository,
        private readonly LoggerInterface         $logger,
    ) {}

    /**
     * Returns null when the task does not exist or is soft-deleted.
     * Throws on infrastructure failures (DB / hydration errors).
     *
     * @throws Throwable
     */
    public function __invoke(int $id): ?TaskDTO
    {
        try {
            return $this->repository->findById($id);
        } catch (Throwable $e) {
            $this->logger->error('GetTaskAction — failed to retrieve task', [
                'task_id'   => $id,
                'exception' => get_class($e),
                'error'     => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
