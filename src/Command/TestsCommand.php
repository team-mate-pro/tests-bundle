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
            ->addOption('exclude-group', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Test group(s) to exclude (e.g. --exclude-group slow --exclude-group flaky)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $decorated = $output->isDecorated();

        if ($this->composerFileReader->hasScript('tests:warmup')) {
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

        $phpunitCmd = ['php', '-d', 'memory_limit=-1', 'vendor/bin/phpunit'];
        $configFile = $this->resolvePhpunitConfig();

        if ($configFile !== null) {
            $phpunitCmd[] = '-c';
            $phpunitCmd[] = $configFile;
        }

        if ($decorated) {
            $phpunitCmd[] = '--colors=always';
        }

        /** @var string|null $coverageOption */
        $coverageOption = $input->getOption('coverage');
        $coverageThreshold = $coverageOption !== null ? (int) $coverageOption : null;
        $cloverFile = sys_get_temp_dir() . '/phpunit-coverage-' . uniqid() . '.xml';

        if ($coverageThreshold !== null) {
            $phpunitCmd[] = '--coverage-clover';
            $phpunitCmd[] = $cloverFile;
        }

        /** @var list<string> $suites */
        $suites = $input->getOption('suite');

        if ($suites !== []) {
            $phpunitCmd[] = '--testsuite';
            $phpunitCmd[] = implode(',', $suites);
        }

        /** @var list<string> $groups */
        $groups = $input->getOption('group');

        if ($groups !== []) {
            $phpunitCmd[] = '--group';
            $phpunitCmd[] = implode(',', $groups);
        }

        /** @var list<string> $excludeGroups */
        $excludeGroups = $input->getOption('exclude-group');

        if ($excludeGroups !== []) {
            $phpunitCmd[] = '--exclude-group';
            $phpunitCmd[] = implode(',', $excludeGroups);
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
        $process = new Process($command, $this->projectDir);
        $process->setTimeout(null);
        $process->run(static function (string $type, string $buffer) use ($output): void {
            $output->write($buffer);
        });

        return $process->getExitCode() ?? 1;
    }
}
