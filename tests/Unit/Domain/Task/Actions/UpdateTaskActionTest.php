<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Task\Actions;

use App\Domain\Task\Actions\UpdateTaskAction;
use App\Domain\Task\DTOs\NewTaskDTO;
use App\Domain\Task\DTOs\TaskDTO;
use App\Domain\Task\Enums\TaskStatus;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

class UpdateTaskActionTest extends TestCase
{
    private TaskRepositoryInterface&MockObject $repository;
    private UpdateTaskAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(TaskRepositoryInterface::class);
        $this->action     = new UpdateTaskAction($this->repository, new NullLogger());
    }

    public function test_returns_updated_task_dto_on_success(): void
    {
        $dto = new NewTaskDTO('Updated title', 'Updated desc', TaskStatus::Completed);

        $updated = new TaskDTO(
            id:          5,
            title:       'Updated title',
            description: 'Updated desc',
            status:      TaskStatus::Completed,
            created_at:  new DateTimeImmutable(),
            is_deleted:  false,
        );

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with(5, $dto)
            ->willReturn($updated);

        $result = ($this->action)(5, $dto);

        $this->assertSame($updated, $result);
        $this->assertSame(TaskStatus::Completed, $result->status);
    }

    public function test_returns_null_when_task_not_found(): void
    {
        $dto = new NewTaskDTO('Title', null, TaskStatus::Pending);

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with(999, $dto)
            ->willReturn(null);

        $result = ($this->action)(999, $dto);

        $this->assertNull($result);
    }

    public function test_it_propagates_repository_exception(): void
    {
        $dto       = new NewTaskDTO('Title', null, TaskStatus::Pending);
        $exception = new RuntimeException('DB connection lost');

        $this->repository
            ->expects($this->once())
            ->method('update')
            ->with(1, $dto)
            ->willThrowException($exception);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DB connection lost');

        ($this->action)(1, $dto);
    }
}
