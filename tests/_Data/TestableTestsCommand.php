<?php

declare(strict_types=1);

namespace TeamMatePro\TestsBundle\Tests\_Data;

use Symfony\Component\Console\Output\OutputInterface;
use TeamMatePro\TestsBundle\Command\TestsCommand;

class TestableTestsCommand extends TestsCommand
{
    /** @var list<list<string>> */
    public array $executedCommands = [];

    public int $processExitCode = 0;

    /** @param list<string> $command */
    protected function runProcess(array $command, OutputInterface $output): int
    {
        $this->executedCommands[] = $command;

        return $this->processExitCode;
    }
}
