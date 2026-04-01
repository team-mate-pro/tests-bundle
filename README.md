# Tests Bundle

Testing utilities and helpers for Symfony test environment.

## Installation

```bash
composer require team-mate-pro/tests-bundle --dev
```

## Requirements

- PHP >= 8.2
- Symfony >= 6.4

## Features

### Console Commands

#### `tmp:tests` - Test Runner

Runs `tests:warmup` (if defined in `composer.json`) and then executes PHPUnit. Provides colored output, re-run failed tests, and code coverage enforcement.

**Usage:**

```bash
php bin/console tmp:tests
php bin/console tmp:tests --failed
php bin/console tmp:tests --coverage=80
php bin/console tmp:tests --suite unit
php bin/console tmp:tests --suite unit --suite integration
php bin/console tmp:tests --group fast
php bin/console tmp:tests --group fast --exclude-group flaky
php bin/console tmp:tests --parallel=4
php bin/console tmp:tests --no-warmup
```

**Options:**

| Option | Description |
|--------|-------------|
| `--failed` | Re-run only previously failed tests. Once they all pass, the defect list is cleared automatically. |
| `--coverage=N` | Generate code coverage and fail if the line coverage percentage is below `N` (1-100). |
| `--suite=NAME` | Run only the specified test suite(s). Can be repeated to run multiple suites. Maps to PHPUnit's `--testsuite` option. |
| `--group=NAME` | Run only tests in the specified group(s). Can be repeated. Maps to PHPUnit's `--group` option. |
| `--exclude-group=NAME` | Exclude tests in the specified group(s). Can be repeated. Maps to PHPUnit's `--exclude-group` option. |
| `--parallel=N` | Run tests in parallel using N processes. Requires ParaTest (see below). |
| `--no-warmup` | Skip `tests:warmup` step. Useful for unit tests that run in isolation without database setup. |

All options can be combined. For example, `--suite integration --group fast --exclude-group flaky` runs only the `integration` suite, includes only tests in the `fast` group, and excludes tests in the `flaky` group.

**How `--failed` works:**

PHPUnit 10+ stores test results in `.phpunit.cache/test-results` (JSON). The command reads this file, extracts defect names (stripping data-set suffixes for deduplication), and passes them as a `--filter` to PHPUnit. After a successful re-run, defects are cleared so the next `--failed` invocation reports "No previously failed tests found".

**How `--coverage` works:**

Passes `--coverage-clover` to PHPUnit, then parses the generated Clover XML to calculate line coverage (`coveredstatements / statements * 100`). If `<coverage><report><clover outputFile="..."/></report></coverage>` is configured in `phpunit.xml`, uses that path instead of generating a temporary file.

**How `--parallel` works:**

The `--parallel` option uses [ParaTest](https://github.com/paratestphp/paratest) to run tests in multiple processes simultaneously. ParaTest:

1. Scans all test files and distributes them across N processes
2. Runs each process with its own PHPUnit instance
3. Aggregates results from all processes into a single report
4. Merges coverage reports (when using `--coverage`)

**Installing ParaTest:**

```bash
composer require --dev brianium/paratest
```

**Performance comparison:**

| Command | Processes | Time (example) |
|---------|-----------|----------------|
| `tmp:tests` | 1 | ~60s |
| `tmp:tests --parallel=2` | 2 | ~32s |
| `tmp:tests --parallel=4` | 4 | ~18s |
| `tmp:tests --parallel=8` | 8 | ~12s |

#### `tmp:tests:verify-setup` - Setup Verification

Validates that the project is correctly configured for the test runner.

**Usage:**

```bash
php bin/console tmp:tests:verify-setup
```

**Checks performed:**

| Check | Requirement |
|-------|-------------|
| `composer.json` | File exists and contains valid JSON |
| `tests:warmup` script | Defined in `composer.json` scripts |
| `phpunit.xml.dist` | File exists and contains valid XML |
| `cacheDirectory` attribute | Present on `<phpunit>` element (required for `--failed`) |
| Test suites | All four required suites defined: `unit`, `integration`, `application`, `acceptance` |

**Warnings (non-blocking):**

- Missing recommended PHPUnit attributes (`executionOrder`, `failOnRisky`, `failOnWarning`, `beStrictAboutOutputDuringTests`)
- ParaTest not installed (required for `--parallel` option)

### ServiceTrait - Simplified Integration Testing

The `ServiceTrait` provides convenient service access in integration tests, eliminating boilerplate code when retrieving services from the container.

**Key Benefits:**
- Type-safe service retrieval with PHPStan support
- Clean, readable test code
- Automatic validation of service existence
- Designed for `KernelTestCase` integration

**Usage:**

```php
<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use TeamMatePro\TestsBundle\ServiceTrait;

abstract class IntegrationTest extends KernelTestCase
{
    use ServiceTrait;
}
```

**Example Test:**

```php
final class UserRepositoryTest extends IntegrationTest
{
    public function testFindUser(): void
    {
        $repository = $this->getService(UserRepository::class);
        $user = $repository->find(1);

        $this->assertInstanceOf(User::class, $user);
    }
}
```

### PerformanceTrait - Performance Testing Assertions

The `PerformanceTrait` provides assertions for measuring and validating execution time and memory usage in your tests.

**Key Benefits:**
- Assert execution time limits (in milliseconds)
- Assert memory usage limits (in megabytes)
- Compare performance between invocations
- High-precision timing using `hrtime()`

**Usage:**

```php
<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use TeamMatePro\TestsBundle\PerformanceTrait;

abstract class PerformanceTest extends KernelTestCase
{
    use PerformanceTrait;
}
```

**Example - Execution Time Assertions:**

```php
final class ApiPerformanceTest extends PerformanceTest
{
    public function testEndpointResponseTime(): void
    {
        // Assert the operation completes in less than 100ms
        $this->assertRunsInLessThan(
            fn() => $this->client->request('GET', '/api/users'),
            100
        );
    }

    public function testOptimizedQueryIsFaster(): void
    {
        // First call sets the baseline
        $this->assertRunsInLessThan(
            fn() => $this->repository->findAllSlow(),
            500
        );

        // Assert optimized version is faster than the previous call
        $this->assertRunsFasterThanPreviousInvocation(
            fn() => $this->repository->findAllOptimized()
        );
    }
}
```

**Example - Memory Usage Assertions:**

```php
final class MemoryPerformanceTest extends PerformanceTest
{
    public function testDataProcessingMemoryLimit(): void
    {
        // Assert the operation uses less than 50 MB
        $this->assertUsesLessMemoryThan(
            fn() => $this->processor->processLargeDataset(),
            50
        );
    }
}
```

**Available Methods:**

| Method | Description |
|--------|-------------|
| `assertRunsInLessThan(callable, int $ms)` | Assert callback runs in less than N milliseconds |
| `assertRunsFasterThanPreviousInvocation(callable)` | Assert callback is faster than previous measured call |
| `assertUsesLessMemoryThan(callable, float $mb)` | Assert callback uses less than N megabytes |
| `assertUsesLessMemoryThanPreviousInvocation(callable)` | Assert callback uses less memory than previous measured call |

### TestGroup - Centralized Test Group Constants

The `TestGroup` class provides standardized constants for PHPUnit test groups, enabling selective test execution.

**Usage:**

```php
use PHPUnit\Framework\Attributes\Group;
use TeamMatePro\TestsBundle\TestGroup;

#[Group(TestGroup::FAST)]
class PricingServiceTest extends TestCase
{
    // ...
}
```

**Available Groups:**

| Constant | Value | Description |
|----------|-------|-------------|
| `TestGroup::FAST` | `fast` | Quick integration tests that don't require full database seeding |
| `TestGroup::WIP` | `wip` | Work in progress - tests currently being developed |
| `TestGroup::DIRTY` | `dirty` | Tests that modify external state (HTTP calls, files, external services) |
| `TestGroup::FLAKY` | `flaky` | Tests that can fail randomly due to data fixtures or code inconsistency |
| `TestGroup::PERFORMANCE` | `performance` | Performance-related tests |
| `TestGroup::API` | `api` | API-related tests |
| `TestGroup::REPOSITORY` | `repository` | Repository-related tests |

**Example - Running by groups:**

```bash
# Run only fast tests
php bin/console tmp:tests --group fast

# Exclude flaky tests
php bin/console tmp:tests --exclude-group flaky

# Run fast tests, excluding dirty ones
php bin/console tmp:tests --group fast --exclude-group dirty
```

### run-if-modified.sh - Smart Command Caching

A Bash script that executes commands only when files in a watched directory have been modified since the last successful execution.

**Purpose:**

Optimizes development workflows by skipping expensive operations (like loading database fixtures) when source files haven't changed.

**How It Works:**

1. **First Run**: Executes the command and creates a timestamp file in `/tmp/`
2. **Subsequent Runs**:
   - Checks if any files in the watched directory are newer than the timestamp
   - Only executes the command if modifications are detected
   - Updates the timestamp only if the command succeeds

**Usage:**

```bash
./vendor/team-mate-pro/tests-bundle/tools/run-if-modified.sh "command to run" /path/to/watch
```

**Example: Loading Doctrine Fixtures Only When Changed**

```json
{
  "scripts": {
    "tests:warmup": [
      "APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction",
      "./vendor/team-mate-pro/tests-bundle/tools/run-if-modified.sh \"php bin/console doctrine:fixtures:load --no-interaction\" ./src/DataFixtures"
    ]
  }
}
```

**Clearing Cache:**

```bash
# Delete all timestamps (force all cached commands to re-run)
rm /tmp/run-if-modified-*.timestamp
```

## PHPUnit Configuration

### Reference Configuration

The following `phpunit.xml.dist` satisfies all `tmp:tests:verify-setup` checks:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         cacheDirectory=".phpunit.cache"
         executionOrder="depends,defects"
         failOnRisky="true"
         failOnWarning="true"
         beStrictAboutOutputDuringTests="true"
         colors="true">

    <testsuites>
        <testsuite name="unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="application">
            <directory>tests/Application</directory>
        </testsuite>
        <testsuite name="acceptance">
            <directory>tests/Acceptance</directory>
        </testsuite>
    </testsuites>

    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

### Composer Scripts

Recommended `composer.json` scripts section:

```json
{
    "scripts": {
        "tests:warmup": [
            "APP_ENV=test php bin/console cache:warmup",
            "APP_ENV=test php bin/console doctrine:migrations:migrate --no-interaction",
            "APP_ENV=test php bin/console doctrine:fixtures:load --no-interaction"
        ]
    }
}
```

### Coverage Prerequisites

To use `--coverage`, install a coverage driver:

**PCOV (recommended):**

```bash
pecl install pcov && docker-php-ext-enable pcov
```

**Xdebug:**

```bash
pecl install xdebug && docker-php-ext-enable xdebug
# Set XDEBUG_MODE=coverage when running tests
```

## Development

### Running Tests

```bash
make tests
# or
composer tests:unit
```

### Code Quality

```bash
make check      # Run phpcs, phpstan, tests
make fix        # Auto-fix code style
```

### Docker

```bash
make start      # Build and start containers
make stop       # Stop containers
```

## CI/CD

This bundle uses GitLab CI/CD with shared templates from `sh/tmp-infra`:

- **Static analysis**: PHPCS, PHPStan, PHPUnit
- **Auto-publish**: Mirror to GitHub on main branch

## License

MIT
