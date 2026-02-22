<?php

declare(strict_types=1);

namespace App\Domain\Task\Actions;

use App\Domain\Task\DTOs\NewTaskDTO;
use App\Domain\Task\DTOs\TaskDTO;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class CreateTaskAction
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository,
        private readonly LoggerInterface         $logger,
    ) {}

    /**
     * @throws Throwable — callers (e.g. the controller) are responsible for
     *                     the final catch and HTTP response.
     */
    public function __invoke(NewTaskDTO $dto): TaskDTO
    {
        try {
            return $this->repository->create($dto);
        } catch (Throwable $e) {
            $this->logger->error('CreateTaskAction — failed to create task', [
                'title'     => $dto->title,
                'status'    => $dto->status->value,
                'exception' => get_class($e),
                'error'     => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
