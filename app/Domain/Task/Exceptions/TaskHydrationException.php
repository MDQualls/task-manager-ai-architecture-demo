<?php

declare(strict_types=1);

namespace App\Domain\Task\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown when a raw database row cannot be mapped to a TaskDTO.
 *
 * Wraps the original Throwable and captures the raw data that triggered
 * the failure so it is available in logs without re-querying the database.
 */
final class TaskHydrationException extends RuntimeException
{
    /**
     * @param object|array<string,mixed>|null $rawData  The row/array that could not be hydrated
     */
    public static function fromThrowable(
        Throwable            $previous,
        object|array|null    $rawData = null,
    ): self {
        $hint = $rawData !== null
            ? ' | raw_data: ' . json_encode((array) $rawData, JSON_UNESCAPED_UNICODE)
            : '';

        return new self(
            message:  "Failed to hydrate TaskDTO: {$previous->getMessage()}{$hint}",
            code:     0,
            previous: $previous,
        );
    }
}
