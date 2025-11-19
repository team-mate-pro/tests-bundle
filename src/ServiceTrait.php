<?php

declare(strict_types=1);

namespace TeamMatePro\TestsBundle;

use Psr\Container\ContainerInterface;
use RuntimeException;

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
 *         $myService = $this->service(MyService::class);
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
    protected function service(string $serviceId): object
    {
        $container = $this->getTestContainer();

        if (!$container->has($serviceId)) {
            throw new RuntimeException(
                sprintf('Service "%s" not found in container', $serviceId)
            );
        }

        $service = $container->get($serviceId);

        if (!$service instanceof $serviceId) {
            throw new RuntimeException(
                sprintf(
                    'Service "%s" is not an instance of "%s"',
                    get_class($service),
                    $serviceId
                )
            );
        }

        return $service;
    }

    /**
     * Gets the test container.
     *
     * This method relies on KernelTestCase::getContainer() being available.
     */
    private function getTestContainer(): ContainerInterface
    {
        if (!method_exists($this, 'getContainer')) {
            throw new RuntimeException(
                'ServiceTrait requires getContainer() method. ' .
                'Make sure your test class extends Symfony\Bundle\FrameworkBundle\Test\KernelTestCase'
            );
        }

        return $this->getContainer();
    }
}
