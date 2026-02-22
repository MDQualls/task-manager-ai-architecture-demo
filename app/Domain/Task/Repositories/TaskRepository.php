<?php

declare(strict_types=1);

namespace App\Domain\Task\Repositories;

use App\Domain\Task\DTOs\NewTaskDTO;
use App\Domain\Task\DTOs\TaskDTO;
use App\Domain\Task\Exceptions\TaskHydrationException;
use App\Domain\Task\Exceptions\TaskPersistenceException;
use App\Domain\Task\Factories\TaskDTOFactory;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Concrete implementation using Laravel's Query Builder.
 * No Eloquent models are used — all reads return DTO objects.
 *
 * Error-handling contract:
 *  - TaskHydrationException from the factory is re-thrown as-is (already logged there).
 *  - All other Throwables are logged here with full DB context and wrapped in
 *    TaskPersistenceException so callers receive a typed domain signal.
 */
final class TaskRepository implements TaskRepositoryInterface
{
    private const TABLE = 'tasks';

    public function __construct(
        private readonly TaskDTOFactory  $factory,
        private readonly LoggerInterface $logger,
    ) {}

    public function findById(int $id): ?TaskDTO
    {
        try {
            $row = DB::table(self::TABLE)
                ->where('id', $id)
                ->where('is_deleted', false)
                ->first();

            return $row !== null ? $this->factory->fromDatabaseRow($row) : null;
        } catch (TaskHydrationException $e) {
            throw $e; // already logged in the factory — propagate unchanged
        } catch (Throwable $e) {
            $this->logger->error('TaskRepository::findById — database operation failed', [
                'task_id'   => $id,
                'table'     => self::TABLE,
                'error'     => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            throw TaskPersistenceException::fromThrowable('findById', $e, $id);
        }
    }

    /**
     * @return TaskDTO[]
     * @throws TaskPersistenceException
     */
    public function findAll(): array
    {
        try {
            $rows = DB::table(self::TABLE)
                ->where('is_deleted', false)
                ->orderByDesc('created_at')
                ->get()
                ->all(); // returns plain stdClass[]

            return $this->factory->fromDatabaseRows($rows);
        } catch (TaskHydrationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error('TaskRepository::findAll — database operation failed', [
                'table'     => self::TABLE,
                'error'     => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            throw TaskPersistenceException::fromThrowable('findAll', $e);
        }
    }

    public function create(NewTaskDTO $dto): TaskDTO
    {
        try {
            $now = now()->toDateTimeString();

            $id = DB::table(self::TABLE)->insertGetId([
                'title'       => $dto->title,
                'description' => $dto->description,
                'status'      => $dto->status->value,
                'is_deleted'  => false,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $task = $this->findById($id);

            if ($task === null) {
                throw new RuntimeException(
                    "Insert succeeded but task could not be re-fetched (id={$id})."
                );
            }

            return $task;
        } catch (TaskHydrationException | TaskPersistenceException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error('TaskRepository::create — database operation failed', [
                'table'     => self::TABLE,
                'title'     => $dto->title,
                'status'    => $dto->status->value,
                'error'     => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            throw TaskPersistenceException::fromThrowable('create', $e);
        }
    }

    public function update(int $id, NewTaskDTO $dto): ?TaskDTO
    {
        try {
            $affected = DB::table(self::TABLE)
                ->where('id', $id)
                ->where('is_deleted', false)
                ->update([
                    'title'       => $dto->title,
                    'description' => $dto->description,
                    'status'      => $dto->status->value,
                    'updated_at'  => now()->toDateTimeString(),
                ]);

            if ($affected === 0) {
                return null;
            }

            return $this->findById($id);
        } catch (TaskHydrationException | TaskPersistenceException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error('TaskRepository::update — database operation failed', [
                'task_id'   => $id,
                'table'     => self::TABLE,
                'title'     => $dto->title,
                'status'    => $dto->status->value,
                'error'     => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            throw TaskPersistenceException::fromThrowable('update', $e, $id);
        }
    }

    public function delete(int $id): bool
    {
        try {
            $affected = DB::table(self::TABLE)
                ->where('id', $id)
                ->where('is_deleted', false)
                ->update([
                    'is_deleted' => true,
                    'updated_at' => now()->toDateTimeString(),
                ]);

            return $affected > 0;
        } catch (Throwable $e) {
            $this->logger->error('TaskRepository::delete — database operation failed', [
                'task_id'   => $id,
                'table'     => self::TABLE,
                'error'     => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            throw TaskPersistenceException::fromThrowable('delete', $e, $id);
        }
    }
}
