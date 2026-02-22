<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Task\Actions;

use App\Domain\Task\Actions\CreateTaskAction;
use App\Domain\Task\DTOs\NewTaskDTO;
use App\Domain\Task\DTOs\TaskDTO;
use App\Domain\Task\Enums\TaskStatus;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

class CreateTaskActionTest extends TestCase
{
    private TaskRepositoryInterface&MockObject $repository;
    private CreateTaskAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(TaskRepositoryInterface::class);
        $this->action     = new CreateTaskAction($this->repository, new NullLogger());
    }

    public function test_it_delegates_to_repository_and_returns_task_dto(): void
    {
        $dto = new NewTaskDTO(
            title:       'Build feature X',
            description: 'Details here.',
            status:      TaskStatus::Pending,
        );

        $expected = new TaskDTO(
            id:          1,
            title:       'Build feature X',
            description: 'Details here.',
            status:      TaskStatus::Pending,
            created_at:  new DateTimeImmutable(),
            is_deleted:  false,
        );

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with($dto)
            ->willReturn($expected);

        $result = ($this->action)($dto);

        $this->assertSame($expected, $result);
    }

    public function test_it_passes_dto_with_null_description(): void
    {
        $dto = new NewTaskDTO('Title only', null, TaskStatus::Pending);

        $expected = new TaskDTO(1, 'Title only', null, TaskStatus::Pending, new DateTimeImmutable(), false);

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with($dto)
            ->willReturn($expected);

        $result = ($this->action)($dto);

        $this->assertNull($result->description);
    }

    public function test_it_propagates_repository_exception(): void
    {
        $dto       = new NewTaskDTO('Title', null, TaskStatus::Pending);
        $exception = new RuntimeException('DB connection lost');

        $this->repository
            ->expects($this->once())
            ->method('create')
            ->with($dto)
            ->willThrowException($exception);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DB connection lost');

        ($this->action)($dto);
    }
}
