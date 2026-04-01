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
    private const REQUIRED_SUITES = ['unit', 'integration', 'application'];

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

        /** @var list<array{string, string}> $warnings */
        $warnings = [];

        $this->verifyComposerScripts($errors);
        $this->verifyPhpunitConfig($errors, $warnings);
        $this->verifyOptionalDependencies($warnings);

        if ($warnings !== []) {
            $io->warning(sprintf('Found %d warning(s) in test setup:', count($warnings)));
            $io->table(['Check', 'Warning'], $warnings);
        }

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

    /**
     * @param list<array{string, string}> $errors
     * @param list<array{string, string}> $warnings
     */
    private function verifyPhpunitConfig(array &$errors, array &$warnings): void
    {
        $phpunitPath = $this->resolvePhpunitConfigPath();

        if ($phpunitPath === null) {
            $errors[] = ['phpunit.xml[.dist]', 'Neither phpunit.xml nor phpunit.xml.dist found in project root'];

            return;
        }

        $configFileName = basename($phpunitPath);

        $xml = @simplexml_load_file($phpunitPath);

        if ($xml === false) {
            $errors[] = [$configFileName, 'Invalid XML'];

            return;
        }

        // Required attributes
        $cacheDirectory = (string) ($xml['cacheDirectory'] ?? '');

        if ($cacheDirectory === '') {
            $errors[] = [$configFileName, 'Missing "cacheDirectory" attribute (required for --failed support)'];
        }

        // Recommended attributes
        $this->checkRecommendedAttribute($xml, $configFileName, 'executionOrder', 'depends,defects', $warnings);
        $this->checkRecommendedBoolAttribute($xml, $configFileName, 'failOnRisky', $warnings);
        $this->checkRecommendedBoolAttribute($xml, $configFileName, 'failOnWarning', $warnings);
        $this->checkRecommendedBoolAttribute($xml, $configFileName, 'beStrictAboutOutputDuringTests', $warnings);

        // Required test suites
        $suites = [];

        foreach ($xml->testsuites->testsuite ?? [] as $testsuite) {
            $name = (string) ($testsuite['name'] ?? '');

            if ($name !== '') {
                $suites[] = $name;
            }
        }

        foreach (self::REQUIRED_SUITES as $required) {
            if (!in_array($required, $suites, true)) {
                $errors[] = [$configFileName, sprintf('Missing "%s" test suite', $required)];
            }
        }
    }

    /** @param list<array{string, string}> $warnings */
    private function checkRecommendedAttribute(
        \SimpleXMLElement $xml,
        string $configFileName,
        string $attribute,
        string $expectedValue,
        array &$warnings,
    ): void {
        $value = (string) ($xml[$attribute] ?? '');

        if ($value !== $expectedValue) {
            $warnings[] = [$configFileName, sprintf('Missing or incorrect %s="%s" (recommended)', $attribute, $expectedValue)];
        }
    }

    /** @param list<array{string, string}> $warnings */
    private function checkRecommendedBoolAttribute(
        \SimpleXMLElement $xml,
        string $configFileName,
        string $attribute,
        array &$warnings,
    ): void {
        $value = (string) ($xml[$attribute] ?? '');

        if ($value !== 'true') {
            $warnings[] = [$configFileName, sprintf('Missing or incorrect %s="true" (recommended)', $attribute)];
        }
    }

    /** @param list<array{string, string}> $warnings */
    private function verifyOptionalDependencies(array &$warnings): void
    {
        $paratestBin = $this->projectDir . '/vendor/bin/paratest';

        if (!file_exists($paratestBin)) {
            $warnings[] = ['paratest', 'Not installed. Install with: composer require --dev brianium/paratest (required for --parallel option)'];
        }
    }

    /**
     * Resolves PHPUnit config path following PHPUnit convention:
     * phpunit.xml takes precedence over phpunit.xml.dist
     */
    private function resolvePhpunitConfigPath(): ?string
    {
        $phpunitXml = $this->projectDir . '/phpunit.xml';

        if (file_exists($phpunitXml)) {
            return $phpunitXml;
        }

        $phpunitXmlDist = $this->projectDir . '/phpunit.xml.dist';

        if (file_exists($phpunitXmlDist)) {
            return $phpunitXmlDist;
        }

        return null;
    }
}
