<?php

declare(strict_types=1);

namespace TeamMatePro\TestsBundle;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Provides convenient service access for integration & application tests.
 *
 * This trait is designed to be used with Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
 * to simplify retrieving services from the test container.
 *
 * @example
 * ```php
 * use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
 * use TeamMatePro\TestsBundle\ServiceTrait;
 *
 * class MyIntegrationTest extends KernelTestCase
 * {
 *     use ServiceTrait;
 *
 *     public function testSomething(): void
 *     {
 *         $myService = $this->getService(MyService::class);
 *         // ... test logic
 *     }
 * }
 * ```
 */
trait ServiceTrait
{
    /**
     * Retrieves a service from the test container.
     *
     * @template T of object
     * @param class-string<T> $serviceId The service class name
     * @return T The service instance
     */
    protected static function getService(string $serviceId): object
    {
        $s = self::getContainer()->get($serviceId);

        if (!$s) {
            throw new ServiceNotFoundException($serviceId);
        }

        return $s;
    }
}
