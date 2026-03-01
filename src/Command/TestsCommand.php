<?php

declare(strict_types=1);

namespace TeamMatePro\TestsBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use TeamMatePro\TestsBundle\ComposerFileReader;

#[AsCommand(
    name: 'tmp:tests',
    description: 'Run test warmup and execute PHPUnit tests',
)]
class TestsCommand extends Command
{
    public function __construct(
        private readonly string $projectDir,
        private readonly ComposerFileReader $composerFileReader,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('failed', null, InputOption::VALUE_NONE, 'Run only previously failed tests');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->composerFileReader->hasScript('tests:warmup')) {
            $io->section('Running tests:warmup');

            if ($this->runProcess(['composer', 'run', 'tests:warmup'], $output) !== 0) {
                $io->error('tests:warmup failed.');

                return Command::FAILURE;
            }
        }

        $phpunitCmd = ['php', 'vendor/bin/phpunit'];
        $configFile = $this->resolvePhpunitConfig();

        if ($configFile !== null) {
            $phpunitCmd[] = '-c';
            $phpunitCmd[] = $configFile;
        }

        if ($input->getOption('failed')) {
            $failedTests = $this->getFailedTests();

            if ($failedTests === []) {
                $io->success('No previously failed tests found.');

                return Command::SUCCESS;
            }

            $filter = implode('|', array_map(
                static fn(string $test): string => preg_quote($test, '/'),
                $failedTests,
            ));
            $phpunitCmd[] = '--filter';
            $phpunitCmd[] = $filter;

            $io->note(sprintf('Re-running %d previously failed test(s).', count($failedTests)));
        }

        $io->section('Running PHPUnit');

        $exitCode = $this->runProcess($phpunitCmd, $output);

        return $exitCode === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function resolvePhpunitConfig(): ?string
    {
        if (file_exists($this->projectDir . '/phpunit.xml')) {
            return 'phpunit.xml';
        }

        if (file_exists($this->projectDir . '/phpunit.xml.dist')) {
            return 'phpunit.xml.dist';
        }

        return null;
    }

    /** @return list<string> */
    private function getFailedTests(): array
    {
        $cacheFile = $this->projectDir . '/.phpunit.cache/test-results';

        if (!file_exists($cacheFile)) {
            return [];
        }

        $content = file_get_contents($cacheFile);

        if ($content === false) {
            return [];
        }

        $data = @unserialize($content);

        if (!is_array($data) || !isset($data['defects']) || !is_array($data['defects'])) {
            return [];
        }

        $tests = [];

        /** @var array-key $testId */
        foreach (array_keys($data['defects']) as $testId) {
            $cleaned = preg_replace('/ with data set .+$/', '', (string) $testId);

            if (is_string($cleaned)) {
                $tests[] = $cleaned;
            }
        }

        return array_values(array_unique($tests));
    }

    /** @param list<string> $command */
    protected function runProcess(array $command, OutputInterface $output): int
    {
        $process = new Process($command, $this->projectDir);
        $process->setTimeout(null);
        $process->run(static function (string $type, string $buffer) use ($output): void {
            $output->write($buffer);
        });

        return $process->getExitCode() ?? 1;
    }
}
