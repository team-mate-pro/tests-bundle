<?php

declare(strict_types=1);

namespace TeamMatePro\TestsBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TeamMatePro\TestsBundle\ComposerFileReader;

#[AsCommand(
    name: 'tmp:tests:verify-setup',
    description: 'Verify that the project test setup is correctly configured',
)]
class VerifySetupCommand extends Command
{
    private const REQUIRED_SUITES = ['unit', 'integration', 'application', 'acceptance'];

    public function __construct(
        private readonly string $projectDir,
        private readonly ComposerFileReader $composerFileReader,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var list<array{string, string}> $errors */
        $errors = [];

        $this->verifyComposerScripts($errors);
        $this->verifyPhpunitConfig($errors);

        if ($errors !== []) {
            $io->error(sprintf('Found %d problem(s) in test setup:', count($errors)));
            $io->table(['Check', 'Problem'], $errors);

            return Command::FAILURE;
        }

        $io->success('Test setup is correctly configured.');

        return Command::SUCCESS;
    }

    /** @param list<array{string, string}> $errors */
    private function verifyComposerScripts(array &$errors): void
    {
        if (!$this->composerFileReader->exists()) {
            $errors[] = ['composer.json', 'File not found in project root'];

            return;
        }

        if (!$this->composerFileReader->isValid()) {
            $errors[] = ['composer.json', 'Invalid JSON or unable to read file'];

            return;
        }

        if (!$this->composerFileReader->hasScript('tests:warmup')) {
            $errors[] = ['composer.json', 'Missing "tests:warmup" script'];
        }
    }

    /** @param list<array{string, string}> $errors */
    private function verifyPhpunitConfig(array &$errors): void
    {
        $phpunitPath = $this->projectDir . '/phpunit.xml.dist';

        if (!file_exists($phpunitPath)) {
            $errors[] = ['phpunit.xml.dist', 'File not found in project root'];

            return;
        }

        $xml = @simplexml_load_file($phpunitPath);

        if ($xml === false) {
            $errors[] = ['phpunit.xml.dist', 'Invalid XML'];

            return;
        }

        $cacheDirectory = (string) ($xml['cacheDirectory'] ?? '');

        if ($cacheDirectory === '') {
            $errors[] = ['phpunit.xml.dist', 'Missing "cacheDirectory" attribute (required for --failed support)'];
        }

        $suites = [];

        foreach ($xml->testsuites->testsuite ?? [] as $testsuite) {
            $name = (string) ($testsuite['name'] ?? '');

            if ($name !== '') {
                $suites[] = $name;
            }
        }

        foreach (self::REQUIRED_SUITES as $required) {
            if (!in_array($required, $suites, true)) {
                $errors[] = ['phpunit.xml.dist', sprintf('Missing "%s" test suite', $required)];
            }
        }
    }
}
