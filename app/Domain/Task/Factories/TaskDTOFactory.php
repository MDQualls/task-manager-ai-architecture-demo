<?php

declare(strict_types=1);

namespace App\Domain\Task\Factories;

use App\Domain\Task\DTOs\TaskDTO;
use App\Domain\Task\Enums\TaskStatus;
use App\Domain\Task\Exceptions\TaskHydrationException;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Hydrates TaskDTO instances from raw database rows.
 * Keeps mapping logic in one place, away from the repository.
 *
 * Accepts a PSR-3 LoggerInterface so the class remains framework-agnostic
 * and fully unit-testable without bootstrapping the Laravel application
 * (pass Psr\Log\NullLogger in unit tests).
 */
final class TaskDTOFactory
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Build a TaskDTO from a stdClass row returned by the Query Builder.
     *
     * @throws TaskHydrationException
     */
    public function fromDatabaseRow(object $row): TaskDTO
    {
        try {
            return new TaskDTO(
                id:          (int) $row->id,
                title:       $row->title,
                description: $row->description ?? null,
                status:      TaskStatus::from($row->status),
                created_at:  new DateTimeImmutable($row->created_at),
                is_deleted:  (bool) $row->is_deleted,
            );
        } catch (Throwable $e) {
            $this->logger->error('TaskDTOFactory::fromDatabaseRow — hydration failed', [
                'error'   => $e->getMessage(),
                'raw_row' => (array) $row,
            ]);

            throw TaskHydrationException::fromThrowable($e, $row);
        }
    }

    /**
     * Build a TaskDTO from a plain associative array (useful in tests / seeders).
     *
     * @param  array<string, mixed> $data
     * @throws TaskHydrationException
     */
    public function fromArray(array $data): TaskDTO
    {
        try {
            return $this->fromDatabaseRow((object) $data);
        } catch (TaskHydrationException $e) {
            throw $e; // already wrapped and logged by fromDatabaseRow — pass through
        } catch (Throwable $e) {
            $this->logger->error('TaskDTOFactory::fromArray — hydration failed', [
                'error'    => $e->getMessage(),
                'raw_data' => $data,
            ]);

            throw TaskHydrationException::fromThrowable($e, $data);
        }
    }

    /**
     * Map a collection of raw rows to TaskDTO instances.
     * Fails fast on the first unmappable row.
     *
     * @param  object[] $rows
     * @return TaskDTO[]
     * @throws TaskHydrationException
     */
    public function fromDatabaseRows(array $rows): array
    {
        try {
            return array_map(
                fn(object $row): TaskDTO => $this->fromDatabaseRow($row),
                $rows,
            );
        } catch (TaskHydrationException $e) {
            throw $e; // individual row failure already logged — pass through
        } catch (Throwable $e) {
            $this->logger->error('TaskDTOFactory::fromDatabaseRows — collection hydration failed', [
                'error'     => $e->getMessage(),
                'row_count' => count($rows),
            ]);

            throw TaskHydrationException::fromThrowable($e);
        }
    }
}
