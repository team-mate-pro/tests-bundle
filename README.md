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
