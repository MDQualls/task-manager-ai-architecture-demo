# AI-Assisted Task Manager API + React UI

## Summary

This is a relatively simple project, however it represents a production-structured, 
AI-assisted full-stack application built with Laravel 12 and React.

The backend enforces Domain-Driven Design (DDD), layered architecture,
PSR-3 logging, structured exception handling, and automated testing. The
frontend integrates with a fully authenticated REST API.

This repository showcases:

-   AI-augmented development using structured prompt engineering
-   Explicit architectural boundaries (DTOs, Repositories, Actions)
-   Query Builder over Active Record for domain separation
-   Layered logging strategy
-   Docker-based development (Laravel Sail)
-   Unit + integration testing

The AI tooling accelerated scaffolding --- architectural design, review,
and refinement were performed deliberately to align with production
standards.

------------------------------------------------------------------------

## Architecture Overview

### Domain Layer

-   `TaskDTO` and `NewTaskDTO`
-   `TaskStatus` enum
-   Repository interface + implementation (Query Builder)
-   Invokable Action classes (Create, Read, Update, Delete)
-   Custom domain exceptions
-   DTO Factory with hydration safeguards

### Application Layer

-   Orchestrates domain actions
-   Enforces input validation through Request objects

### Infrastructure Layer

-   MySQL 8.4 (Dockerized)
-   Redis (queue/cache ready)
-   PSR-3 logging implementation
-   No Eloquent ORM --- explicit persistence boundaries

### HTTP Layer

-   RESTful `TaskController`
-   Laravel Sanctum authentication
-   Structured JSON responses
-   Centralized exception rendering for APIs

------------------------------------------------------------------------

## Technology Stack

-   PHP 8.5
-   Laravel 12
-   Laravel Sanctum
-   MySQL 8.4
-   Redis
-   Docker (Laravel Sail)
-   React
-   PHPUnit
-   PSR-3 Logging

------------------------------------------------------------------------

## Key Engineering Decisions

### Explicit DTO Boundaries

Data Transfer Objects define strict layer boundaries and prevent
implicit model leakage.

### Query Builder Instead of Eloquent

Persistence logic remains isolated from the domain model, maintaining
architectural clarity.

### Invokable Action Pattern

Each use case is encapsulated within a single-responsibility class to
improve readability and testability.

### Layered Logging Strategy

Each layer logs contextual diagnostic information without duplicating
concerns:

-   Factory: Hydration failures
-   Repository: Database operation failures
-   Actions: Domain input context
-   Controller: HTTP-level request context

### Exception Handling Strategy

-   All layers catch `Throwable`
-   Domain-specific exceptions wrap lower-level failures
-   Controllers return standardized 500 responses
-   Global JSON rendering for API routes

------------------------------------------------------------------------

## Testing Strategy

-   Unit tests for:
    -   Actions
    -   DTO Factory
-   Integration test for Repository (via `RefreshDatabase`)
-   Real MySQL container used for integration-level validation

------------------------------------------------------------------------

## Getting Started

### 1. Clone Repository

``` bash
git clone https://github.com/your-username/ai-assisted-task-manager.git
cd ai-assisted-task-manager
```

### 2. Install Dependencies

``` bash
composer install
npm install
```

### 3. Configure Environment

``` bash
cp .env.example .env
php artisan key:generate
```

Update database credentials if needed (default Sail config works out of
the box).

### 4. Start Laravel Sail

``` bash
./vendor/bin/sail up -d
```

If using WSL:

``` bash
wsl
./vendor/bin/sail up -d
```

### 5. Run Migrations

``` bash
./vendor/bin/sail artisan migrate
```

### 6. Run Tests

``` bash
./vendor/bin/sail artisan test
```

### 7. Start Frontend (if applicable)

``` bashSS
npm run dev
```

The API will be available at:

    http://localhost:8080/api/v1/tasks

The Web UI will be available at:

    http://localhost:8080/app

------------------------------------------------------------------------

## AI-Assisted Development Workflow

This project was scaffolded using structured prompts with Claude Code.

Workflow included:

1.  Generating Laravel project structure
2.  Refactoring to DDD architecture
3.  Enforcing repository and action patterns
4.  Implementing layered logging and exception handling
5.  Writing unit and integration tests
6.  Hardening Docker configuration

All generated code was reviewed, refactored, and validated to ensure
architectural integrity.

This repository demonstrates how AI can accelerate development while
maintaining engineering discipline.

------------------------------------------------------------------------

## Why This Project Matters

This repository serves as:

-   A portfolio-ready backend architecture example
-   Proof of AI-augmented engineering capability
-   A template for micro-SaaS foundations
-   A demonstration of production-minded Laravel design

------------------------------------------------------------------------


## License

Provided for demonstration and educational purposes.
