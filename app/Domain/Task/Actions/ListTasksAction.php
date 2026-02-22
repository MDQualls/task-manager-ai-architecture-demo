<?php

declare(strict_types=1);

namespace App\Domain\Task\Actions;

use App\Domain\Task\DTOs\TaskDTO;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class ListTasksAction
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository,
        private readonly LoggerInterface         $logger,
    ) {}

    /**
     * @return TaskDTO[]
     * @throws Throwable
     */
    public function __invoke(): array
    {
        try {
            return $this->repository->findAll();
        } catch (Throwable $e) {
            $this->logger->error('ListTasksAction — failed to retrieve task list', [
                'exception' => get_class($e),
                'error'     => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
