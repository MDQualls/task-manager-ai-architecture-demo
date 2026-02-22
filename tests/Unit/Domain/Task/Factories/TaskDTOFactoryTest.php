<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Task\Factories;

use App\Domain\Task\DTOs\TaskDTO;
use App\Domain\Task\Enums\TaskStatus;
use App\Domain\Task\Exceptions\TaskHydrationException;
use App\Domain\Task\Factories\TaskDTOFactory;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class TaskDTOFactoryTest extends TestCase
{
    private TaskDTOFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new TaskDTOFactory(new NullLogger());
    }

    public function test_from_database_row_maps_all_fields(): void
    {
        $row = (object) [
            'id'          => 42,
            'title'       => 'Write tests',
            'description' => 'Cover the factory.',
            'status'      => 'pending',
            'created_at'  => '2026-02-21 10:00:00',
            'is_deleted'  => false,
        ];

        $dto = $this->factory->fromDatabaseRow($row);

        $this->assertInstanceOf(TaskDTO::class, $dto);
        $this->assertSame(42, $dto->id);
        $this->assertSame('Write tests', $dto->title);
        $this->assertSame('Cover the factory.', $dto->description);
        $this->assertSame(TaskStatus::Pending, $dto->status);
        $this->assertInstanceOf(DateTimeImmutable::class, $dto->created_at);
        $this->assertFalse($dto->is_deleted);
    }

    public function test_from_database_row_handles_null_description(): void
    {
        $row = (object) [
            'id'          => 1,
            'title'       => 'No description',
            'description' => null,
            'status'      => 'in_progress',
            'created_at'  => '2026-02-21 12:00:00',
            'is_deleted'  => false,
        ];

        $dto = $this->factory->fromDatabaseRow($row);

        $this->assertNull($dto->description);
        $this->assertSame(TaskStatus::InProgress, $dto->status);
    }

    public function test_from_database_row_maps_completed_status(): void
    {
        $row = (object) [
            'id'          => 5,
            'title'       => 'Done',
            'description' => null,
            'status'      => 'completed',
            'created_at'  => '2026-02-21 08:00:00',
            'is_deleted'  => false,
        ];

        $dto = $this->factory->fromDatabaseRow($row);

        $this->assertSame(TaskStatus::Completed, $dto->status);
    }

    public function test_from_database_row_maps_is_deleted_true(): void
    {
        $row = (object) [
            'id'          => 99,
            'title'       => 'Deleted task',
            'description' => null,
            'status'      => 'pending',
            'created_at'  => '2026-02-20 00:00:00',
            'is_deleted'  => true,
        ];

        $dto = $this->factory->fromDatabaseRow($row);

        $this->assertTrue($dto->is_deleted);
    }

    public function test_from_database_row_throws_hydration_exception_for_invalid_status(): void
    {
        $row = (object) [
            'id'          => 1,
            'title'       => 'Bad status',
            'description' => null,
            'status'      => 'invalid_status',
            'created_at'  => '2026-02-21 10:00:00',
            'is_deleted'  => false,
        ];

        $this->expectException(TaskHydrationException::class);

        $this->factory->fromDatabaseRow($row);
    }

    public function test_from_array_builds_dto_from_associative_array(): void
    {
        $data = [
            'id'          => 7,
            'title'       => 'Array test',
            'description' => 'via array',
            'status'      => 'pending',
            'created_at'  => '2026-02-21 09:00:00',
            'is_deleted'  => false,
        ];

        $dto = $this->factory->fromArray($data);

        $this->assertSame(7, $dto->id);
        $this->assertSame('Array test', $dto->title);
    }

    public function test_from_array_throws_hydration_exception_for_invalid_status(): void
    {
        $data = [
            'id'          => 1,
            'title'       => 'Bad status',
            'description' => null,
            'status'      => 'bogus_status',
            'created_at'  => '2026-02-21 10:00:00',
            'is_deleted'  => false,
        ];

        $this->expectException(TaskHydrationException::class);

        $this->factory->fromArray($data);
    }

    public function test_from_database_rows_maps_collection(): void
    {
        $rows = [
            (object) [
                'id' => 1, 'title' => 'Task A', 'description' => null,
                'status' => 'pending', 'created_at' => '2026-02-21 10:00:00', 'is_deleted' => false,
            ],
            (object) [
                'id' => 2, 'title' => 'Task B', 'description' => 'desc',
                'status' => 'completed', 'created_at' => '2026-02-21 11:00:00', 'is_deleted' => false,
            ],
        ];

        $dtos = $this->factory->fromDatabaseRows($rows);

        $this->assertCount(2, $dtos);
        $this->assertContainsOnlyInstancesOf(TaskDTO::class, $dtos);
        $this->assertSame(1, $dtos[0]->id);
        $this->assertSame(2, $dtos[1]->id);
    }

    public function test_from_database_rows_returns_empty_array_for_no_rows(): void
    {
        $dtos = $this->factory->fromDatabaseRows([]);

        $this->assertSame([], $dtos);
    }
}
