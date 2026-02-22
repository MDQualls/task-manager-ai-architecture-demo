<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Task\Actions;

use App\Domain\Task\Actions\ListTasksAction;
use App\Domain\Task\DTOs\TaskDTO;
use App\Domain\Task\Enums\TaskStatus;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

class ListTasksActionTest extends TestCase
{
    private TaskRepositoryInterface&MockObject $repository;
    private ListTasksAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(TaskRepositoryInterface::class);
        $this->action     = new ListTasksAction($this->repository, new NullLogger());
    }

    public function test_returns_array_of_task_dtos(): void
    {
        $tasks = [
            new TaskDTO(1, 'Task A', null, TaskStatus::Pending,    new DateTimeImmutable(), false),
            new TaskDTO(2, 'Task B', 'desc', TaskStatus::Completed, new DateTimeImmutable(), false),
        ];

        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($tasks);

        $result = ($this->action)();

        $this->assertSame($tasks, $result);
        $this->assertCount(2, $result);
    }

    public function test_returns_empty_array_when_no_tasks(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $result = ($this->action)();

        $this->assertSame([], $result);
    }

    public function test_it_propagates_repository_exception(): void
    {
        $exception = new RuntimeException('DB connection lost');

        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->willThrowException($exception);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DB connection lost');

        ($this->action)();
    }
}
