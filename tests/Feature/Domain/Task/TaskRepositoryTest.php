<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Task;

use App\Domain\Task\DTOs\NewTaskDTO;
use App\Domain\Task\DTOs\TaskDTO;
use App\Domain\Task\Enums\TaskStatus;
use App\Domain\Task\Factories\TaskDTOFactory;
use App\Domain\Task\Repositories\TaskRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Psr\Log\NullLogger;
use Tests\TestCase;

/**
 * Integration tests for TaskRepository — requires a real database.
 * Run via: ./vendor/bin/sail artisan test --filter=TaskRepositoryTest
 */
class TaskRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TaskRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TaskRepository(
            factory: new TaskDTOFactory(new NullLogger()),
            logger:  new NullLogger(),
        );
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function insertTask(array $overrides = []): int
    {
        $defaults = [
            'title'       => 'Test Task',
            'description' => 'A description',
            'status'      => TaskStatus::Pending->value,
            'is_deleted'  => false,
            'created_at'  => now()->toDateTimeString(),
            'updated_at'  => now()->toDateTimeString(),
        ];

        return DB::table('tasks')->insertGetId(array_merge($defaults, $overrides));
    }

    // ── findById ─────────────────────────────────────────────────────────

    public function test_find_by_id_returns_dto_for_existing_task(): void
    {
        $id = $this->insertTask(['title' => 'Find me']);

        $result = $this->repository->findById($id);

        $this->assertInstanceOf(TaskDTO::class, $result);
        $this->assertSame($id, $result->id);
        $this->assertSame('Find me', $result->title);
    }

    public function test_find_by_id_returns_null_for_missing_task(): void
    {
        $result = $this->repository->findById(99999);

        $this->assertNull($result);
    }

    public function test_find_by_id_returns_null_for_soft_deleted_task(): void
    {
        $id = $this->insertTask(['is_deleted' => true]);

        $result = $this->repository->findById($id);

        $this->assertNull($result);
    }

    // ── findAll ──────────────────────────────────────────────────────────

    public function test_find_all_returns_only_non_deleted_tasks(): void
    {
        $this->insertTask(['title' => 'Active 1']);
        $this->insertTask(['title' => 'Active 2']);
        $this->insertTask(['title' => 'Deleted', 'is_deleted' => true]);

        $results = $this->repository->findAll();

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(TaskDTO::class, $results);
        $titles = array_map(fn($t) => $t->title, $results);
        $this->assertNotContains('Deleted', $titles);
    }

    public function test_find_all_returns_empty_array_when_no_tasks(): void
    {
        $results = $this->repository->findAll();

        $this->assertSame([], $results);
    }

    // ── create ───────────────────────────────────────────────────────────

    public function test_create_persists_task_and_returns_dto(): void
    {
        $dto = new NewTaskDTO('New Task', 'Some description', TaskStatus::Pending);

        $result = $this->repository->create($dto);

        $this->assertInstanceOf(TaskDTO::class, $result);
        $this->assertGreaterThan(0, $result->id);
        $this->assertSame('New Task', $result->title);
        $this->assertSame('Some description', $result->description);
        $this->assertSame(TaskStatus::Pending, $result->status);
        $this->assertFalse($result->is_deleted);

        $this->assertDatabaseHas('tasks', ['id' => $result->id, 'title' => 'New Task']);
    }

    public function test_create_with_null_description(): void
    {
        $dto    = new NewTaskDTO('No Desc', null, TaskStatus::InProgress);
        $result = $this->repository->create($dto);

        $this->assertNull($result->description);
        $this->assertSame(TaskStatus::InProgress, $result->status);
    }

    // ── update ───────────────────────────────────────────────────────────

    public function test_update_modifies_task_and_returns_refreshed_dto(): void
    {
        $id  = $this->insertTask(['title' => 'Old Title', 'status' => 'pending']);
        $dto = new NewTaskDTO('New Title', 'New desc', TaskStatus::Completed);

        $result = $this->repository->update($id, $dto);

        $this->assertInstanceOf(TaskDTO::class, $result);
        $this->assertSame($id, $result->id);
        $this->assertSame('New Title', $result->title);
        $this->assertSame(TaskStatus::Completed, $result->status);

        $this->assertDatabaseHas('tasks', ['id' => $id, 'title' => 'New Title', 'status' => 'completed']);
    }

    public function test_update_returns_null_for_nonexistent_task(): void
    {
        $dto    = new NewTaskDTO('X', null, TaskStatus::Pending);
        $result = $this->repository->update(99999, $dto);

        $this->assertNull($result);
    }

    public function test_update_returns_null_for_soft_deleted_task(): void
    {
        $id  = $this->insertTask(['is_deleted' => true]);
        $dto = new NewTaskDTO('Try update', null, TaskStatus::Pending);

        $result = $this->repository->update($id, $dto);

        $this->assertNull($result);
    }

    // ── delete ───────────────────────────────────────────────────────────

    public function test_delete_soft_deletes_task_and_returns_true(): void
    {
        $id = $this->insertTask();

        $result = $this->repository->delete($id);

        $this->assertTrue($result);
        $this->assertDatabaseHas('tasks', ['id' => $id, 'is_deleted' => true]);
    }

    public function test_delete_returns_false_for_nonexistent_task(): void
    {
        $result = $this->repository->delete(99999);

        $this->assertFalse($result);
    }

    public function test_delete_returns_false_for_already_deleted_task(): void
    {
        $id = $this->insertTask(['is_deleted' => true]);

        $result = $this->repository->delete($id);

        $this->assertFalse($result);
    }

    public function test_deleted_task_excluded_from_find_all(): void
    {
        $id = $this->insertTask(['title' => 'Will be gone']);
        $this->repository->delete($id);

        $results = $this->repository->findAll();
        $ids     = array_map(fn($t) => $t->id, $results);

        $this->assertNotContains($id, $ids);
    }
}
