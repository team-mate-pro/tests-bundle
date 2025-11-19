<?php

declare(strict_types=1);

namespace TeamMatePro\TestsBundle;

/**
 * Centralized PHPUnit test group constants.
 *
 * Test groups allow selective test execution via PHPUnit's --group and --exclude-group options.
 * Use these constants in #[Group(TestGroup::CONSTANT_NAME)] attributes on test classes or methods.
 *
 * Examples:
 * - Run only fast tests: vendor/bin/phpunit --group fast
 * - Run all except dirty tests: vendor/bin/phpunit --exclude-group dirty
 * - Run specific module tests: vendor/bin/phpunit --group finances
 * - Run multiple groups: vendor/bin/phpunit --group fast --group player
 */
class TestGroup
{
    /**
     * FAST - Quick integration tests that don't require full database seeding.
     *
     * Used for: Integration tests that use minimal test data (fake UUIDs, no fixtures).
     * Execution time: < 1 second per test
     *
     * Run with: vendor/bin/phpunit --group fast
     * Composer shortcut: composer tests:integration:fast
     */
    public const FAST = 'fast';

    /**
     * WIP - Work In Progress tests.
     *
     * Used for: Tests currently being developed or debugged.
     * Purpose: Isolate incomplete tests from main test suite
     */
    public const WIP = 'wip';

    /**
     * DIRTY - Tests that modify external state or resources.
     *
     * Used for: Tests that make HTTP calls, modify files, or interact with external services.
     * Examples: HTTP repository tests, external API integrations
     * Warning: May have side effects outside the test database
     *
     * Run with: vendor/bin/phpunit --exclude-group dirty
     */
    public const DIRTY = 'dirty';

    public const PERFORMANCE = 'performance';

    public const API = 'api';

    public const REPOSITORY = 'repository';

    /**
     * FLAKY - a test that can fails randomly (probably due data fixtures randomization or code inconsistency)
     */
    public const FLAKY = 'flaky';
}
