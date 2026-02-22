<?php

declare(strict_types=1);

namespace App\Domain\Task\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown when a database / persistence operation on the tasks table fails.
 *
 * Wraps the original infrastructure Throwable so callers can inspect the
 * full exception chain while receiving a typed, domain-level signal.
 */
final class TaskPersistenceException extends RuntimeException
{
    /**
     * Build a contextual exception from an infrastructure-level Throwable.
     *
     * @param string    $operation  e.g. "findById", "create", "update", "delete"
     * @param int|null  $taskId     The task ID involved, when applicable
     * @param Throwable $previous   The original exception that caused this failure
     */
    public static function fromThrowable(
        string    $operation,
        Throwable $previous,
        ?int      $taskId = null,
    ): self {
        $context = $taskId !== null ? " (task_id={$taskId})" : '';

        return new self(
            message:  "Task persistence failed during [{$operation}]{$context}: {$previous->getMessage()}",
            code:     0,
            previous: $previous,
        );
    }
}
