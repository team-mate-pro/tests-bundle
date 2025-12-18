<?php

declare(strict_types=1);

namespace TeamMatePro\TestsBundle;

/**
 * Provides performance testing assertions for PHPUnit tests.
 *
 * This trait allows you to measure and assert execution time and memory usage
 * of code blocks. It also supports comparing performance between invocations.
 *
 * @example Time assertions
 * ```php
 * use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
 * use TeamMatePro\TestsBundle\PerformanceTrait;
 *
 * class MyPerformanceTest extends KernelTestCase
 * {
 *     use PerformanceTrait;
 *
 *     public function testResponseTime(): void
 *     {
 *         // Assert that the operation completes in less than 100ms
 *         $this->assertRunsInLessThan(fn() => $this->fetchData(), 100);
 *     }
 *
 *     public function testOptimizationImproved(): void
 *     {
 *         // First call sets the baseline
 *         $this->assertRunsInLessThan(fn() => $this->oldAlgorithm(), 500);
 *
 *         // Assert the new algorithm is faster than the old one
 *         $this->assertRunsFasterThanPreviousInvocation(fn() => $this->newAlgorithm());
 *     }
 * }
 * ```
 *
 * @example Memory assertions
 * ```php
 * use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
 * use TeamMatePro\TestsBundle\PerformanceTrait;
 *
 * class MyMemoryTest extends KernelTestCase
 * {
 *     use PerformanceTrait;
 *
 *     public function testMemoryLimit(): void
 *     {
 *         // Assert that the operation uses less than 10 MB
 *         $this->assertUsesLessMemoryThan(fn() => $this->processLargeDataset(), 10);
 *     }
 *
 *     public function testMemoryOptimization(): void
 *     {
 *         // First call sets the baseline
 *         $this->assertUsesLessMemoryThan(fn() => $this->oldImplementation(), 50);
 *
 *         // Assert the new implementation uses less memory
 *         $this->assertUsesLessMemoryThanPreviousInvocation(fn() => $this->newImplementation());
 *     }
 * }
 * ```
 */
trait PerformanceTrait
{
    private ?float $previousExecutionTimeMs = null;
    private ?float $previousMemoryUsageMb = null;

    /**
     * Asserts that a callback runs in less than the specified time.
     *
     * Measures the execution time of the callback and fails the test
     * if it exceeds the specified threshold. The measured time is stored
     * for comparison with subsequent calls to assertRunsFasterThanPreviousInvocation().
     *
     * @param callable $callback The callback to execute and measure
     * @param int $timeMs Maximum allowed execution time in milliseconds
     */
    protected function assertRunsInLessThan(callable $callback, int $timeMs): void
    {
        $executionTimeMs = $this->measureExecutionTime($callback);

        self::assertLessThan(
            $timeMs,
            $executionTimeMs,
            sprintf('Expected execution time to be less than %d ms, but it took %.2f ms', $timeMs, $executionTimeMs)
        );
    }

    /**
     * Asserts that a callback runs faster than the previous invocation.
     *
     * Compares the execution time of the callback against the previously
     * recorded execution time from assertRunsInLessThan() or a previous
     * call to this method.
     *
     * @param callable $callback The callback to execute and measure
     * @throws \PHPUnit\Framework\AssertionFailedError If no previous execution time is recorded
     */
    protected function assertRunsFasterThanPreviousInvocation(callable $callback): void
    {
        if ($this->previousExecutionTimeMs === null) {
            self::fail('No previous execution time recorded. Run assertRunsInLessThan() or assertRunsFasterThanPreviousInvocation() first.');
        }

        $previousTime = $this->previousExecutionTimeMs;
        $executionTimeMs = $this->measureExecutionTime($callback);

        self::assertLessThan(
            $previousTime,
            $executionTimeMs,
            sprintf(
                'Expected execution time (%.2f ms) to be faster than previous invocation (%.2f ms)',
                $executionTimeMs,
                $previousTime
            )
        );
    }

    /**
     * Asserts that a callback uses less memory than the specified amount.
     *
     * Measures the memory allocated during the callback execution and fails
     * the test if it exceeds the specified threshold. The measured memory is
     * stored for comparison with subsequent calls to assertUsesLessMemoryThanPreviousInvocation().
     *
     * @param callable $callback The callback to execute and measure
     * @param float $memoryMb Maximum allowed memory usage in megabytes (MB)
     */
    protected function assertUsesLessMemoryThan(callable $callback, float $memoryMb): void
    {
        $memoryUsedMb = $this->measureMemoryUsage($callback);

        self::assertLessThan(
            $memoryMb,
            $memoryUsedMb,
            sprintf(
                'Expected memory usage to be less than %.2f MB, but it used %.2f MB',
                $memoryMb,
                $memoryUsedMb
            )
        );
    }

    /**
     * Asserts that a callback uses less memory than the previous invocation.
     *
     * Compares the memory usage of the callback against the previously
     * recorded memory usage from assertUsesLessMemoryThan() or a previous
     * call to this method.
     *
     * @param callable $callback The callback to execute and measure
     * @throws \PHPUnit\Framework\AssertionFailedError If no previous memory usage is recorded
     */
    protected function assertUsesLessMemoryThanPreviousInvocation(callable $callback): void
    {
        if ($this->previousMemoryUsageMb === null) {
            self::fail('No previous memory usage recorded. Run assertUsesLessMemoryThan() or assertUsesLessMemoryThanPreviousInvocation() first.');
        }

        $previousMemory = $this->previousMemoryUsageMb;
        $memoryUsedMb = $this->measureMemoryUsage($callback);

        self::assertLessThan(
            $previousMemory,
            $memoryUsedMb,
            sprintf(
                'Expected memory usage (%.2f MB) to be less than previous invocation (%.2f MB)',
                $memoryUsedMb,
                $previousMemory
            )
        );
    }

    /**
     * Measures the execution time of a callback using high-resolution timer.
     *
     * Uses hrtime() for nanosecond precision, then converts to milliseconds.
     *
     * @param callable $callback The callback to execute
     * @return float Execution time in milliseconds
     */
    private function measureExecutionTime(callable $callback): float
    {
        $startTime = hrtime(true);
        $callback();
        $endTime = hrtime(true);

        $executionTimeMs = ($endTime - $startTime) / 1_000_000;
        $this->previousExecutionTimeMs = $executionTimeMs;

        return $executionTimeMs;
    }

    /**
     * Measures the memory usage of a callback.
     *
     * Triggers garbage collection before measurement to get more accurate results.
     * Measures only the memory allocated during callback execution.
     *
     * @param callable $callback The callback to execute
     * @return float Memory usage in megabytes (MB)
     */
    private function measureMemoryUsage(callable $callback): float
    {
        gc_collect_cycles();
        $startMemory = memory_get_usage();
        $callback();
        $endMemory = memory_get_usage();
        gc_collect_cycles();

        $memoryUsedBytes = max(0, $endMemory - $startMemory);
        $memoryUsedMb = $memoryUsedBytes / (1024 * 1024);
        $this->previousMemoryUsageMb = $memoryUsedMb;

        return $memoryUsedMb;
    }
}
