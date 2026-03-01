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

    /** @param list<string> $command */
    protected function runProcess(array $command, OutputInterface $output): int
    {
        $this->executedCommands[] = $command;

        if ($this->cloverXmlContent !== null) {
            $cloverIndex = array_search('--coverage-clover', $command, true);

            if ($cloverIndex !== false && isset($command[$cloverIndex + 1])) {
                $cloverFile = $command[$cloverIndex + 1];
                $dir = dirname($cloverFile);

                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }

                file_put_contents($cloverFile, $this->cloverXmlContent);
            }
        }

        return $this->processExitCode;
    }
}
