<?php

declare(strict_types=1);

namespace TeamMatePro\TestsBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class TeamMateProTestsBundle extends AbstractBundle
{
    /** @param array<string, mixed> $config */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services()
            ->defaults()
                ->autowire()
                ->autoconfigure()
                ->bind('$projectDir', '%kernel.project_dir%');

        $services->set(ComposerFileReader::class);
        $services->load('TeamMatePro\\TestsBundle\\Command\\', __DIR__ . '/Command/');
    }
}
