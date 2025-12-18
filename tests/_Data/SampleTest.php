<?php

declare(strict_types=1);

namespace TeamMatePro\TestsBundle\Tests\_Data;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use TeamMatePro\TestsBundle\PerformanceTrait;
use TeamMatePro\TestsBundle\ServiceTrait;

final class SampleTest extends KernelTestCase
{
    use ServiceTrait;
    use PerformanceTrait;
}