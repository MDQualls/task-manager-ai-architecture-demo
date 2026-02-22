<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Task\Actions\CreateTaskAction;
use App\Domain\Task\Actions\DeleteTaskAction;
use App\Domain\Task\Actions\GetTaskAction;
use App\Domain\Task\Actions\ListTasksAction;
use App\Domain\Task\Actions\UpdateTaskAction;
use App\Domain\Task\DTOs\NewTaskDTO;
use App\Domain\Task\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTaskRequest;
use App\Http\Requests\Api\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * RESTful API controller for Task CRUD operations.
 *
 * Error-handling contract:
 *   • Domain / infrastructure exceptions thrown by actions are caught here.
 *   • A single private helper logs the full HTTP + exception context once
 *     and returns a generic 500 JSON response so no internal detail leaks.
 *   • Expected "not found" outcomes are handled as 404 before the catch.
 *
 * Routes:
 *   GET    /api/v1/tasks          → index
 *   POST   /api/v1/tasks          → store
 *   GET    /api/v1/tasks/{id}     → show
 *   PUT    /api/v1/tasks/{id}     → update (full replacement)
 *   PATCH  /api/v1/tasks/{id}     → update (partial)
 *   DELETE /api/v1/tasks/{id}     → destroy (soft-delete)
 */
class TaskController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    // Public action methods
    // ──────────────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/tasks
     * Return all non-deleted tasks.
     */
    public function index(Request $request, ListTasksAction $action): AnonymousResourceCollection|JsonResponse
    {
        try {
            $tasks = $action();

            return TaskResource::collection($tasks);
        } catch (Throwable $e) {
            return $this->internalError($request, 'index', $e);
        }
    }

    /**
     * GET /api/v1/tasks/{id}
     * Return a single task by ID.
     */
    public function show(Request $request, int $id, GetTaskAction $action): TaskResource|JsonResponse
    {
        try {
            $task = $action($id);

            if ($task === null) {
                return response()->json(['message' => 'Task not found.'], 404);
            }

            return new TaskResource($task);
        } catch (Throwable $e) {
            return $this->internalError($request, 'show', $e, ['task_id' => $id]);
        }
    }

    /**
     * POST /api/v1/tasks
     * Create a new task.
     */
    public function store(StoreTaskRequest $request, CreateTaskAction $action): JsonResponse
    {
        try {
            $dto = new NewTaskDTO(
                title:       $request->validated('title'),
                description: $request->validated('description'),
                status:      TaskStatus::from($request->validated('status', TaskStatus::Pending->value)),
            );

            $task = $action($dto);

            return (new TaskResource($task))
                ->response()
                ->setStatusCode(201);
        } catch (Throwable $e) {
            return $this->internalError($request, 'store', $e, [
                'input' => $request->validated(),
            ]);
        }
    }

    /**
     * PUT|PATCH /api/v1/tasks/{id}
     * Update an existing task.
     *   PUT   → full replacement (all fields required).
     *   PATCH → partial update (validated fields merged onto existing values).
     */
    public function update(UpdateTaskRequest $request, int $id, UpdateTaskAction $action): TaskResource|JsonResponse
    {
        try {
            $existing = app(GetTaskAction::class)($id);

            if ($existing === null) {
                return response()->json(['message' => 'Task not found.'], 404);
            }

            // For PATCH: fall back to existing values for omitted fields
            $dto = new NewTaskDTO(
                title:       $request->validated('title',       $existing->title),
                description: $request->validated('description', $existing->description),
                status:      TaskStatus::from(
                                 $request->validated('status', $existing->status->value)
                             ),
            );

            $updated = $action($id, $dto);

            if ($updated === null) {
                return response()->json(['message' => 'Task not found.'], 404);
            }

            return new TaskResource($updated);
        } catch (Throwable $e) {
            return $this->internalError($request, 'update', $e, [
                'task_id' => $id,
                'input'   => $request->validated(),
            ]);
        }
    }

    /**
     * DELETE /api/v1/tasks/{id}
     * Soft-delete a task (sets is_deleted = true).
     */
    public function destroy(Request $request, int $id, DeleteTaskAction $action): JsonResponse
    {
        try {
            $deleted = $action($id);

            if (! $deleted) {
                return response()->json(['message' => 'Task not found.'], 404);
            }

            return response()->json(null, 204);
        } catch (Throwable $e) {
            return $this->internalError($request, 'destroy', $e, ['task_id' => $id]);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Log a caught Throwable with full HTTP + exception context, then return
     * a generic 500 response so internal details never reach the client.
     *
     * @param array<string, mixed> $extra Additional context specific to the method
     */
    private function internalError(
        Request   $request,
        string    $method,
        Throwable $e,
        array     $extra = [],
    ): JsonResponse {

        if ($e instanceof AuthenticationException) {
            return response()->json(
                ['message' => 'Unauthenticated.'],
                401,
            );
        }

        Log::error("TaskController::{$method} — unhandled exception", array_merge([
            'exception'  => get_class($e),
            'error'      => $e->getMessage(),
            'caused_by'  => $e->getPrevious()?->getMessage(),
            'http_method'=> $request->method(),
            'url'        => $request->fullUrl(),
            'user_id'    => $request->user()?->id,
        ], $extra));

        return response()->json(
            ['message' => 'An unexpected error occurred. Please try again later.'],
            500,
        );
    }
}
