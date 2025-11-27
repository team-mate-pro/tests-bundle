<?php

declare(strict_types=1);

namespace TeamMatePro\TestsBundle\Exception;

use RuntimeException;

/**
 * Should be thrown when there is no possibility to arrange a test.
 */
class TestArrangeException extends RuntimeException
{
}
