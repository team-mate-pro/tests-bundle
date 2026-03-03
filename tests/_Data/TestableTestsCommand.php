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

    public ?string $cloverXmlContent = null;

    /** Path to write clover content when using config-based path (no --coverage-clover arg) */
    public ?string $cloverPathFromConfig = null;

    /** @param list<string> $command */
    protected function runProcess(array $command, OutputInterface $output): int
    {
        $this->executedCommands[] = $command;

        if ($this->cloverXmlContent !== null) {
            $cloverIndex = array_search('--coverage-clover', $command, true);

            if ($cloverIndex !== false && isset($command[$cloverIndex + 1])) {
                // Clover path passed via command line
                $cloverFile = $command[$cloverIndex + 1];
                $this->writeCloverFile($cloverFile);
            } elseif ($this->cloverPathFromConfig !== null) {
                // Clover path from phpunit.xml config
                $this->writeCloverFile($this->cloverPathFromConfig);
            }
        }

        return $this->processExitCode;
    }

    private function writeCloverFile(string $cloverFile): void
    {
        $dir = dirname($cloverFile);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($cloverFile, $this->cloverXmlContent);
    }
}
