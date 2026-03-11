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
use stdClass;
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
        $this
            ->addOption('failed', null, InputOption::VALUE_NONE, 'Run only previously failed tests')
            ->addOption('coverage', null, InputOption::VALUE_REQUIRED, 'Minimum coverage percentage (1-100)')
            ->addOption('suite', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Test suite(s) to run (e.g. --suite unit --suite integration)')
            ->addOption('group', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Test group(s) to include (e.g. --group fast --group critical)')
            ->addOption('exclude-group', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Test group(s) to exclude (e.g. --exclude-group slow --exclude-group flaky)')
            ->addOption('parallel', null, InputOption::VALUE_REQUIRED, 'Run tests in parallel using N processes (requires paratest)')
            ->addOption('no-warmup', null, InputOption::VALUE_NONE, 'Skip tests:warmup step (useful for unit tests that run in isolation)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $decorated = $output->isDecorated();

        $skipWarmup = $input->getOption('no-warmup');

        if (!$skipWarmup && $this->composerFileReader->hasScript('tests:warmup')) {
            $io->section('Running tests:warmup');

            $warmupCmd = ['composer', 'run', 'tests:warmup'];

            if ($decorated) {
                $warmupCmd[] = '--ansi';
            }

            if ($this->runProcess($warmupCmd, $output) !== 0) {
                $io->error('tests:warmup failed.');

                return Command::FAILURE;
            }
        }

        /** @var string|null $parallelOption */
        $parallelOption = $input->getOption('parallel');
        $parallelProcesses = $parallelOption !== null ? (int) $parallelOption : null;

        if ($parallelProcesses !== null) {
            $paratestBin = $this->projectDir . '/vendor/bin/paratest';

            if (!file_exists($paratestBin)) {
                $io->error([
                    'ParaTest is not installed.',
                    'Install it with: composer require --dev brianium/paratest',
                ]);

                return Command::FAILURE;
            }

            $testCmd = ['php', '-d', 'memory_limit=-1', 'vendor/bin/paratest', '-p', (string) $parallelProcesses];
        } else {
            $testCmd = ['php', '-d', 'memory_limit=-1', 'vendor/bin/phpunit'];
        }

        $configFile = $this->resolvePhpunitConfig();

        if ($configFile !== null) {
            $testCmd[] = '-c';
            $testCmd[] = $configFile;
        }

        if ($decorated) {
            $testCmd[] = $parallelProcesses !== null ? '--colors' : '--colors=always';
        }

        /** @var string|null $coverageOption */
        $coverageOption = $input->getOption('coverage');
        $coverageThreshold = $coverageOption !== null ? (int) $coverageOption : null;

        // Try to read clover path from phpunit config, fallback to temp file
        $cloverFileFromConfig = $configFile !== null ? $this->getCloverOutputPathFromConfig($configFile) : null;
        $cloverFile = $cloverFileFromConfig ?? sys_get_temp_dir() . '/phpunit-coverage-' . uniqid() . '.xml';

        // Only add --coverage-clover if not configured in phpunit.xml
        if ($coverageThreshold !== null && $cloverFileFromConfig === null) {
            $testCmd[] = '--coverage-clover';
            $testCmd[] = $cloverFile;
        }

        /** @var list<string> $suites */
        $suites = $input->getOption('suite');

        if ($suites !== []) {
            $testCmd[] = '--testsuite';
            $testCmd[] = implode(',', $suites);
        }

        /** @var list<string> $groups */
        $groups = $input->getOption('group');

        if ($groups !== []) {
            $testCmd[] = '--group';
            $testCmd[] = implode(',', $groups);
        }

        /** @var list<string> $excludeGroups */
        $excludeGroups = $input->getOption('exclude-group');

        if ($excludeGroups !== []) {
            $testCmd[] = '--exclude-group';
            $testCmd[] = implode(',', $excludeGroups);
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
            $testCmd[] = '--filter';
            $testCmd[] = $filter;

            $io->note(sprintf('Re-running %d previously failed test(s).', count($failedTests)));
        }

        $io->section($parallelProcesses !== null ? sprintf('Running ParaTest (%d processes)', $parallelProcesses) : 'Running PHPUnit');

        $exitCode = $this->runProcess($testCmd, $output);

        if ($exitCode === 0 && $input->getOption('failed')) {
            $this->clearDefects();
        }

        if ($exitCode !== 0) {
            return Command::FAILURE;
        }

        if ($coverageThreshold !== null) {
            $coverage = $this->parseCoverageFromClover($cloverFile);

            if ($coverage === null) {
                $io->error('Could not parse coverage report.');

                return Command::FAILURE;
            }

            $io->newLine();
            $io->text(sprintf('Code coverage: <info>%.2f%%</info>', $coverage));

            if ($coverage < $coverageThreshold) {
                $io->error(sprintf(
                    'Code coverage %.2f%% is below the required %d%% threshold.',
                    $coverage,
                    $coverageThreshold,
                ));

                return Command::FAILURE;
            }

            $io->success(sprintf(
                'Code coverage %.2f%% meets the required %d%% threshold.',
                $coverage,
                $coverageThreshold,
            ));
        }

        return Command::SUCCESS;
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

    /**
     * Reads the clover output path from phpunit.xml configuration.
     * Looks for: <coverage><report><clover outputFile="..."/></report></coverage>
     */
    private function getCloverOutputPathFromConfig(string $configFile): ?string
    {
        $fullPath = $this->projectDir . '/' . $configFile;

        if (!file_exists($fullPath)) {
            return null;
        }

        $xml = @simplexml_load_file($fullPath);

        if ($xml === false) {
            return null;
        }

        $cloverOutputFile = (string) ($xml->coverage->report->clover['outputFile'] ?? '');

        if ($cloverOutputFile === '') {
            return null;
        }

        // Return as absolute path if relative
        if (!str_starts_with($cloverOutputFile, '/')) {
            return $this->projectDir . '/' . $cloverOutputFile;
        }

        return $cloverOutputFile;
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

        $data = json_decode($content, true);

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

    private function clearDefects(): void
    {
        $cacheFile = $this->projectDir . '/.phpunit.cache/test-results';

        if (!file_exists($cacheFile)) {
            return;
        }

        $content = file_get_contents($cacheFile);

        if ($content === false) {
            return;
        }

        $data = json_decode($content, true);

        if (!is_array($data)) {
            return;
        }

        $data['defects'] = new stdClass();
        file_put_contents($cacheFile, json_encode($data));
    }

    private function parseCoverageFromClover(string $cloverFile): ?float
    {
        if (!file_exists($cloverFile)) {
            return null;
        }

        $xml = @simplexml_load_file($cloverFile);

        if ($xml === false) {
            return null;
        }

        $metrics = $xml->project->metrics ?? null;

        if ($metrics === null) {
            return null;
        }

        $statements = (int) ($metrics['statements'] ?? 0);
        $coveredStatements = (int) ($metrics['coveredstatements'] ?? 0);

        if ($statements === 0) {
            return 100.0;
        }

        return ($coveredStatements / $statements) * 100;
    }

    /** @param list<string> $command */
    protected function runProcess(array $command, OutputInterface $output): int
    {
        $env = getenv();

        // Prevent git "dubious ownership" warnings in Docker containers
        $env['GIT_CONFIG_COUNT'] = '1';
        $env['GIT_CONFIG_KEY_0'] = 'safe.directory';
        $env['GIT_CONFIG_VALUE_0'] = '*';

        $process = new Process($command, $this->projectDir, $env);
        $process->setTimeout(null);
        $process->run(static function (string $type, string $buffer) use ($output): void {
            $output->write($buffer);
        });

        return $process->getExitCode() ?? 1;
    }
}
