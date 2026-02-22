<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Task\Actions\CreateTaskAction;
use App\Domain\Task\Actions\DeleteTaskAction;
use App\Domain\Task\Actions\GetTaskAction;
use App\Domain\Task\Actions\ListTasksAction;
use App\Domain\Task\Actions\UpdateTaskAction;
use App\Domain\Task\Factories\TaskDTOFactory;
use App\Domain\Task\Repositories\TaskRepository;
use App\Domain\Task\Repositories\TaskRepositoryInterface;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class TaskServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Factory — shared singleton; stateless, safe to reuse.
        // LoggerInterface is resolved automatically from the container
        // (bound by Laravel's core LogServiceProvider as an alias of 'log').
        $this->app->singleton(TaskDTOFactory::class);

        // Repository interface → concrete Query Builder implementation.
        $this->app->singleton(
            TaskRepositoryInterface::class,
            fn($app) => new TaskRepository(
                factory: $app->make(TaskDTOFactory::class),
                logger:  $app->make(LoggerInterface::class),
            ),
        );

        // Actions — resolved fresh per request via the container.
        // Constructor dependencies (TaskRepositoryInterface + LoggerInterface)
        // are auto-wired by the container; no explicit factories required.
        $this->app->bind(CreateTaskAction::class);
        $this->app->bind(GetTaskAction::class);
        $this->app->bind(ListTasksAction::class);
        $this->app->bind(UpdateTaskAction::class);
        $this->app->bind(DeleteTaskAction::class);
    }

    public function boot(): void
    {
        //
    }
}
