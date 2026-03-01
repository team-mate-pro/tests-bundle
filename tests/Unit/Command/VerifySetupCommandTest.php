<?php

declare(strict_types=1);

namespace TeamMatePro\TestsBundle\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TeamMatePro\TestsBundle\Command\VerifySetupCommand;
use TeamMatePro\TestsBundle\ComposerFileReader;
use TeamMatePro\TestsBundle\TeamMateProTestsBundle;

#[CoversClass(VerifySetupCommand::class)]
#[UsesClass(ComposerFileReader::class)]
#[UsesClass(TeamMateProTestsBundle::class)]
class VerifySetupCommandTest extends KernelTestCase
{
    private const FIXTURES_DIR = __DIR__ . '/../../_Data/SymfonyProject';

    private ?string $tmpDir = null;

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->tmpDir !== null) {
            $this->removeDirectory($this->tmpDir);
            $this->tmpDir = null;
        }
    }

    public function testAllChecksPass(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find('tmp:tests:verify-setup');
        $tester = new CommandTester($command);

        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('correctly configured', $tester->getDisplay());
    }

    public function testMissingComposerJson(): void
    {
        $this->tmpDir = $this->createTmpDir();
        copy(self::FIXTURES_DIR . '/phpunit.xml.dist', $this->tmpDir . '/phpunit.xml.dist');

        $tester = $this->executeWithProjectDir($this->tmpDir);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('not found', $tester->getDisplay());
    }

    public function testInvalidComposerJson(): void
    {
        $this->tmpDir = $this->createTmpDir();
        file_put_contents($this->tmpDir . '/composer.json', 'invalid json{{{');
        copy(self::FIXTURES_DIR . '/phpunit.xml.dist', $this->tmpDir . '/phpunit.xml.dist');

        $tester = $this->executeWithProjectDir($this->tmpDir);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Invalid JSON', $tester->getDisplay());
    }

    public function testMissingWarmupScript(): void
    {
        $this->tmpDir = $this->createTmpDir();
        copy(self::FIXTURES_DIR . '/composer_missing.json', $this->tmpDir . '/composer.json');
        copy(self::FIXTURES_DIR . '/phpunit.xml.dist', $this->tmpDir . '/phpunit.xml.dist');

        $tester = $this->executeWithProjectDir($this->tmpDir);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('tests:warmup', $tester->getDisplay());
    }

    public function testMissingPhpunitXmlDist(): void
    {
        $this->tmpDir = $this->createTmpDir();
        copy(self::FIXTURES_DIR . '/composer.json', $this->tmpDir . '/composer.json');

        $tester = $this->executeWithProjectDir($this->tmpDir);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('phpunit.xml.dist', $tester->getDisplay());
        self::assertStringContainsString('not found', $tester->getDisplay());
    }

    public function testIncompleteSuites(): void
    {
        $this->tmpDir = $this->createTmpDir();
        copy(self::FIXTURES_DIR . '/composer.json', $this->tmpDir . '/composer.json');
        copy(self::FIXTURES_DIR . '/phpunit_incomplete.xml.dist', $this->tmpDir . '/phpunit.xml.dist');

        $tester = $this->executeWithProjectDir($this->tmpDir);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        $display = $tester->getDisplay();
        self::assertStringContainsString('integration', $display);
        self::assertStringContainsString('application', $display);
        self::assertStringContainsString('acceptance', $display);
    }

    public function testMissingCacheDirectory(): void
    {
        $this->tmpDir = $this->createTmpDir();
        copy(self::FIXTURES_DIR . '/composer.json', $this->tmpDir . '/composer.json');
        copy(self::FIXTURES_DIR . '/phpunit_no_cache.xml.dist', $this->tmpDir . '/phpunit.xml.dist');

        $tester = $this->executeWithProjectDir($this->tmpDir);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('cacheDirectory', $tester->getDisplay());
    }

    public function testMultipleErrors(): void
    {
        $this->tmpDir = $this->createTmpDir();
        copy(self::FIXTURES_DIR . '/composer_missing.json', $this->tmpDir . '/composer.json');
        copy(self::FIXTURES_DIR . '/phpunit_incomplete.xml.dist', $this->tmpDir . '/phpunit.xml.dist');

        $tester = $this->executeWithProjectDir($this->tmpDir);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        $display = $tester->getDisplay();
        self::assertStringContainsString('tests:warmup', $display);
        self::assertStringContainsString('integration', $display);
    }

    private function executeWithProjectDir(string $projectDir): CommandTester
    {
        $reader = new ComposerFileReader($projectDir);
        $command = new VerifySetupCommand($projectDir, $reader);
        $tester = new CommandTester($command);
        $tester->execute([]);

        return $tester;
    }

    private function createTmpDir(): string
    {
        $dir = sys_get_temp_dir() . '/tests_bundle_verify_' . uniqid();
        mkdir($dir, 0777, true);

        return $dir;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
