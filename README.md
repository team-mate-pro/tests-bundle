# Tests Bundle

Testing utilities and helpers for Symfony test environment.

## Features

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

    public function testStreamingUsesLessMemory(): void
    {
        // First call sets the baseline (loading all into memory)
        $this->assertUsesLessMemoryThan(
            fn() => $this->importer->importAll($data),
            100
        );

        // Assert streaming version uses less memory
        $this->assertUsesLessMemoryThanPreviousInvocation(
            fn() => $this->importer->importStreaming($data)
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

### run-if-modified.sh - Smart Command Caching

A Bash script that executes commands only when files in a watched directory have been modified since the last successful execution.

**Purpose:**

Optimizes development workflows by skipping expensive operations (like loading database fixtures) when source files haven't changed. Particularly useful in test automation pipelines.

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

**Parameters:**
- `command` (required): The command to execute (wrap in quotes if it contains spaces)
- `/path/to/watch` (required): The directory path to monitor for changes

**Example: Loading Doctrine Fixtures Only When Changed**

```json
{
  "scripts": {
    "tests:warmup:local": [
      "APP_ENV=test_local php bin/console doctrine:migrations:migrate --no-interaction",
      "APP_ENV=test_local php bin/console doctrine:schema:update --force --complete",
      "APP_ENV=test_local ./vendor/team-mate-pro/tests-bundle/tools/run-if-modified.sh \"php bin/console doctrine:fixtures:load --no-interaction --group=test_local --purger=custom_purger\" ./src/DataFixtures"
    ]
  }
}
```

In this example, fixtures only reload when `./src/DataFixtures` files have changed, significantly speeding up test cycles.

**More Examples:**

Running migrations only when migration files change:
```bash
./vendor/team-mate-pro/tests-bundle/tools/run-if-modified.sh \
  "php bin/console doctrine:migrations:migrate --no-interaction" \
  ./migrations
```

Rebuilding assets only when source files change:
```bash
./vendor/team-mate-pro/tests-bundle/tools/run-if-modified.sh \
  "npm run build" \
  ./assets/src
```

**Clearing Cache:**

To force a command to run regardless of modifications:
```bash
# Find your timestamp file
ls -la /tmp/run-if-modified-*

# Delete specific timestamp
rm /tmp/run-if-modified-.-src-DataFixtures.timestamp

# Delete all timestamps (force all cached commands to re-run)
rm /tmp/run-if-modified-*.timestamp
```

**Performance Impact:**

- **Overhead**: < 50ms for directory scanning
- **Benefits**: Saves seconds to minutes on expensive operations
- **Example**: Fixture loading takes ~15 seconds on first run, ~0.05 seconds on subsequent runs (when unchanged)

**Exit Codes:**
- `0`: Success (command executed successfully or skipped due to no changes)
- `1`: Error (missing parameters or command failed)
- Other: Returns the exit code from the executed command

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
php bin/console tmp:tests --suite unit --coverage=90
php bin/console tmp:tests --group fast
php bin/console tmp:tests --group fast --exclude-group flaky
php bin/console tmp:tests --suite integration --group fast --exclude-group flaky
php bin/console tmp:tests --parallel=4
php bin/console tmp:tests --parallel=4 --suite integration
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

All options can be combined. For example, `--suite integration --group fast --exclude-group flaky` runs only the `integration` suite, includes only tests in the `fast` group, and excludes tests in the `flaky` group.

**How `--failed` works:**

PHPUnit 10+ stores test results in `.phpunit.cache/test-results` (JSON). The command reads this file, extracts defect names (stripping data-set suffixes for deduplication), and passes them as a `--filter` to PHPUnit. After a successful re-run, defects are cleared so the next `--failed` invocation reports "No previously failed tests found".

**How `--coverage` works:**

Passes `--coverage-clover` to PHPUnit, then parses the generated Clover XML to calculate line coverage (`coveredstatements / statements * 100`). The result is displayed and compared against the threshold.

**How `--suite` works:**

Passes `--testsuite` to PHPUnit with the suite name(s). When multiple suites are specified, they are joined with commas (e.g. `--testsuite unit,integration`).

**How `--group` and `--exclude-group` work:**

Map directly to PHPUnit's `--group` and `--exclude-group` options. Groups are defined on test classes or methods using the `#[Group]` attribute:

```php
use PHPUnit\Framework\Attributes\Group;

#[Group('fast')]
class PricingServiceTest extends TestCase { /* ... */ }
```

When multiple groups are specified, they are joined with commas (e.g. `--group fast,critical`). This is useful for running subsets of a suite — for example, running only fast integration tests in CI while excluding known flaky ones.

**How `--parallel` works:**

The `--parallel` option uses [ParaTest](https://github.com/paratestphp/paratest) to run tests in multiple processes simultaneously. ParaTest:

1. Scans all test files and distributes them across N processes
2. Runs each process with its own PHPUnit instance
3. Aggregates results from all processes into a single report
4. Merges coverage reports (when using `--coverage`)

**Installing ParaTest:**

ParaTest is not installed by default. Install it as a dev dependency:

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

The optimal number of processes depends on your CPU cores and test characteristics (I/O-bound vs CPU-bound). Start with the number of CPU cores and adjust based on results.

**Combining with other options:**

```bash
# Run integration tests in 4 parallel processes with coverage
php bin/console tmp:tests --parallel=4 --suite integration --coverage=70

# Run fast tests in parallel, excluding flaky ones
php bin/console tmp:tests --parallel=4 --group fast --exclude-group flaky
```

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

## PHPUnit Configuration & Best Practices

### Reference Configuration

The following `phpunit.xml.dist` satisfies all `tmp:tests:verify-setup` checks and follows PHPUnit 10+ best practices:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         cacheDirectory=".phpunit.cache"
         executionOrder="depends,defects"
         requireCoverageMetadata="true"
         beStrictAboutCoverageMetadata="true"
         beStrictAboutOutputDuringTests="true"
         failOnRisky="true"
         failOnWarning="true"
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

### Configuration Explained

| Attribute | Value | Why |
|-----------|-------|-----|
| `bootstrap` | `tests/bootstrap.php` | Load autoloader and set up test environment (env vars, error reporting). |
| `cacheDirectory` | `.phpunit.cache` | Required for `--failed` flag. Stores test results and coverage cache between runs. Add to `.gitignore`. |
| `executionOrder` | `depends,defects` | Run dependent tests in order and prioritize previously failing tests first. |
| `requireCoverageMetadata` | `true` | Every test class must declare `#[CoversClass]` or `#[CoversNothing]`. Prevents accidental untested code from inflating coverage. |
| `beStrictAboutCoverageMetadata` | `true` | Marks tests as risky if they execute code not listed in their coverage attributes. Forces intentional `#[UsesClass]` declarations. |
| `beStrictAboutOutputDuringTests` | `true` | Tests that produce output (echo, print, var_dump) are flagged as risky. Keeps test output clean. |
| `failOnRisky` | `true` | Risky tests (no assertions, output, coverage violations) cause the suite to fail. |
| `failOnWarning` | `true` | PHPUnit warnings (deprecated APIs, configuration issues) cause the suite to fail. |
| `colors` | `true` | Colored terminal output. Overridden by `--colors=always` when running via `tmp:tests`. |

### Recommended Project Structure

```
project/
├── phpunit.xml.dist          # Committed — shared config
├── phpunit.xml               # Git-ignored — local overrides
├── .phpunit.cache/           # Git-ignored — test result cache
├── src/
│   └── ...
└── tests/
    ├── bootstrap.php         # Autoloader + env setup
    ├── Unit/                 # No dependencies, no I/O, no kernel
    │   └── Service/
    │       └── PricingServiceTest.php
    ├── Integration/          # Database, filesystem, external services
    │   └── Repository/
    │       └── UserRepositoryTest.php
    ├── Application/          # HTTP layer, full request/response cycle
    │   └── Controller/
    │       └── LoginControllerTest.php
    └── Acceptance/           # End-to-end, browser, full stack
        └── CheckoutFlowTest.php
```

### Test Suites — What Goes Where

| Suite | Base class | Boots kernel | Uses DB | Speed |
|-------|-----------|-------------|---------|-------|
| `unit` | `TestCase` | No | No | ~ms |
| `integration` | `KernelTestCase` | Yes | Yes | ~100ms |
| `application` | `WebTestCase` | Yes | Yes | ~200ms |
| `acceptance` | `WebTestCase` / Panther | Yes | Yes | ~seconds |

**Unit tests** test a single class in isolation. Dependencies are mocked. No filesystem, no database, no network.

**Integration tests** verify that components work together with real services from the container. Typically test repositories, message handlers, or services that depend on infrastructure.

**Application tests** test HTTP endpoints through Symfony's test client. Assert status codes, response content, redirects, and security.

**Acceptance tests** test complete user flows end-to-end. If using Symfony Panther, they run in a real browser.

### Coverage Metadata Attributes

With `requireCoverageMetadata="true"`, every test class must declare what it covers:

```php
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(PricingService::class)]      // This test covers PricingService
#[UsesClass(Money::class)]                 // PricingService uses Money (not tested here)
#[UsesClass(TaxCalculator::class)]         // PricingService uses TaxCalculator (not tested here)
class PricingServiceTest extends TestCase
{
    // ...
}
```

If a test is not meant to contribute to coverage (e.g. smoke tests):

```php
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
class SmokeTest extends WebTestCase
{
    // ...
}
```

### Bootstrap File

A minimal `tests/bootstrap.php`:

```php
<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/config/bootstrap.php')) {
    require dirname(__DIR__) . '/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}
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

The `tests:warmup` script is automatically executed by `tmp:tests` before running PHPUnit. Use `run-if-modified.sh` for expensive steps that don't need to run every time.

You can also create specialized scripts using groups:

```json
{
    "scripts": {
        "tests": "APP_ENV=test php bin/console tmp:tests",
        "tests:unit": "APP_ENV=test php bin/console tmp:tests --suite unit",
        "tests:integration": "APP_ENV=test php bin/console tmp:tests --suite integration",
        "tests:integration:fast": "APP_ENV=test php bin/console tmp:tests --suite integration --group fast --exclude-group flaky",
        "tests:coverage": "APP_ENV=test php bin/console tmp:tests --coverage=70"
    }
}
```

### .gitignore Entries

```gitignore
# PHPUnit
phpunit.xml
.phpunit.cache/
.coverage/
```

Always commit `phpunit.xml.dist`. Never commit `phpunit.xml` — it is for local overrides only (e.g. running a single suite, disabling coverage strictness during development).

### Coverage Prerequisites

To use `--coverage`, your PHP installation needs a coverage driver:

**PCOV (recommended — fast, low overhead):**

```bash
# Debian/Ubuntu
sudo apt install php-pcov

# Docker (Debian-based)
pecl install pcov && docker-php-ext-enable pcov

# Alpine (Docker)
apk add --no-cache $PHPIZE_DEPS && pecl install pcov && docker-php-ext-enable pcov
```

**Xdebug (slower, but also provides step-debugging and profiling):**

```bash
# Debian/Ubuntu
sudo apt install php-xdebug

# Docker (Debian-based)
pecl install xdebug && docker-php-ext-enable xdebug
```

When using Xdebug, set the mode to `coverage`:

```ini
; php.ini
xdebug.mode=coverage
```

Or via environment variable:

```bash
XDEBUG_MODE=coverage php bin/console tmp:tests --coverage=80
```

**Which driver to choose:**

| | PCOV | Xdebug |
|-|------|--------|
| Speed | ~2x faster | Slower due to instrumentation |
| Debugging | No | Yes (breakpoints, step-through) |
| Profiling | No | Yes (cachegrind output) |
| Recommendation | CI pipelines, daily development | When you also need a debugger |

Do not enable both simultaneously. If Xdebug is installed for debugging, use `XDEBUG_MODE=off` during normal test runs and `XDEBUG_MODE=coverage` only when generating coverage.

### Coverage Targets

Suggested minimum coverage thresholds per suite:

| Suite | Target | Rationale |
|-------|--------|-----------|
| `unit` | 80-90% | Core business logic should be well-covered |
| `integration` | 60-70% | Infrastructure glue code; some paths are hard to test |
| All suites combined | 70-80% | Overall project health metric |

Example CI usage:

```bash
# Enforce 80% on unit tests only
php bin/console tmp:tests --suite unit --coverage=80

# Enforce 70% overall
php bin/console tmp:tests --coverage=70
```

The `<source>` block in `phpunit.xml.dist` controls which directories are included in coverage analysis. Only include `src/` — never include `tests/`, `vendor/`, or `config/`.

## Installation

```bash
composer require team-mate-pro/tests-bundle --dev
```

## Requirements

- PHP >= 8.2
- Symfony >= 7.0

## Development

### Running Tests

```bash
make tests
# or
composer tests:unit
```

### Code Quality

Run all quality checks:
```bash
make check
```

Run checks with auto-fix:
```bash
make check_fast
```

### Docker

Start containers:
```bash
make start
```

Stop containers:
```bash
make stop
```

## License

Proprietary
