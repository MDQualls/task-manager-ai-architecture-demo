<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Task\Actions;

use App\Domain\Task\Actions\DeleteTaskAction;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

class DeleteTaskActionTest extends TestCase
{
    private TaskRepositoryInterface&MockObject $repository;
    private DeleteTaskAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(TaskRepositoryInterface::class);
        $this->action     = new DeleteTaskAction($this->repository, new NullLogger());
    }

    public function test_returns_true_when_task_is_soft_deleted(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with(3)
            ->willReturn(true);

        $result = ($this->action)(3);

        $this->assertTrue($result);
    }

    public function test_returns_false_when_task_not_found(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with(404)
            ->willReturn(false);

        $result = ($this->action)(404);

        $this->assertFalse($result);
    }

    public function test_returns_false_when_task_already_deleted(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with(7)
            ->willReturn(false);

        $result = ($this->action)(7);

        $this->assertFalse($result);
    }

    public function test_it_propagates_repository_exception(): void
    {
        $exception = new RuntimeException('DB connection lost');

        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with(1)
            ->willThrowException($exception);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DB connection lost');

        ($this->action)(1);
    }
}
