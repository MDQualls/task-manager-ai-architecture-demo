<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Task\Actions;

use App\Domain\Task\Actions\GetTaskAction;
use App\Domain\Task\DTOs\TaskDTO;
use App\Domain\Task\Enums\TaskStatus;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

class GetTaskActionTest extends TestCase
{
    private TaskRepositoryInterface&MockObject $repository;
    private GetTaskAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(TaskRepositoryInterface::class);
        $this->action     = new GetTaskAction($this->repository, new NullLogger());
    }

    public function test_returns_task_dto_when_found(): void
    {
        $expected = new TaskDTO(
            id:          10,
            title:       'Fix bug #42',
            description: null,
            status:      TaskStatus::InProgress,
            created_at:  new DateTimeImmutable(),
            is_deleted:  false,
        );

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(10)
            ->willReturn($expected);

        $result = ($this->action)(10);

        $this->assertSame($expected, $result);
    }

    public function test_returns_null_when_task_not_found(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $result = ($this->action)(999);

        $this->assertNull($result);
    }

    public function test_it_propagates_repository_exception(): void
    {
        $exception = new RuntimeException('DB connection lost');

        $this->repository
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willThrowException($exception);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DB connection lost');

        ($this->action)(1);
    }
}
