<?php

declare(strict_types=1);

namespace TeamMatePro\TestsBundle\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use TeamMatePro\TestsBundle\Command\TestsCommand;
use TeamMatePro\TestsBundle\ComposerFileReader;
use TeamMatePro\TestsBundle\Tests\_Data\TestableTestsCommand;

#[CoversClass(TestsCommand::class)]
class TestsCommandTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/tests_bundle_cmd_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpDir);
    }

    public function testFailedWithNoCacheReturnsSuccess(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        $tester = new CommandTester($command);

        $tester->execute(['--failed' => true]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertStringContainsString('No previously failed tests found', $tester->getDisplay());
        self::assertSame([], $command->executedCommands);
    }

    public function testFailedWithDefectsBuildsCorrectFilter(): void
    {
        $cacheDir = $this->tmpDir . '/.phpunit.cache';
        mkdir($cacheDir, 0777, true);
        file_put_contents($cacheDir . '/test-results', serialize([
            'defects' => [
                'App\\Tests\\FooTest::testBar' => 1,
                'App\\Tests\\BazTest::testQux' => 1,
            ],
        ]));

        $command = $this->createTestableCommand(hasWarmup: false);
        $tester = new CommandTester($command);

        $tester->execute(['--failed' => true]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertNotEmpty($command->executedCommands);

        $phpunitCmd = $command->executedCommands[count($command->executedCommands) - 1];
        $filterIndex = array_search('--filter', $phpunitCmd, true);
        self::assertIsInt($filterIndex);

        $filter = $phpunitCmd[$filterIndex + 1];
        self::assertStringContainsString('FooTest', $filter);
        self::assertStringContainsString('testBar', $filter);
        self::assertStringContainsString('BazTest', $filter);
        self::assertStringContainsString('testQux', $filter);
    }

    public function testFailedStripsDataSetSuffix(): void
    {
        $cacheDir = $this->tmpDir . '/.phpunit.cache';
        mkdir($cacheDir, 0777, true);
        file_put_contents($cacheDir . '/test-results', serialize([
            'defects' => [
                'App\\Tests\\FooTest::testBar with data set #0' => 1,
                'App\\Tests\\FooTest::testBar with data set #1' => 1,
            ],
        ]));

        $command = $this->createTestableCommand(hasWarmup: false);
        $tester = new CommandTester($command);

        $tester->execute(['--failed' => true]);

        self::assertSame(0, $tester->getStatusCode());

        $phpunitCmd = $command->executedCommands[count($command->executedCommands) - 1];
        $filterIndex = array_search('--filter', $phpunitCmd, true);
        self::assertIsInt($filterIndex);

        $filter = $phpunitCmd[$filterIndex + 1];
        self::assertSame(1, substr_count($filter, 'FooTest'));
    }

    public function testConfigResolutionPrefersPhpunitXml(): void
    {
        file_put_contents($this->tmpDir . '/phpunit.xml', '<phpunit/>');
        file_put_contents($this->tmpDir . '/phpunit.xml.dist', '<phpunit/>');

        $command = $this->createTestableCommand(hasWarmup: false);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $phpunitCmd = $command->executedCommands[count($command->executedCommands) - 1];
        $configIndex = array_search('-c', $phpunitCmd, true);
        self::assertIsInt($configIndex);
        self::assertSame('phpunit.xml', $phpunitCmd[$configIndex + 1]);
    }

    public function testConfigResolutionFallsBackToPhpunitXmlDist(): void
    {
        file_put_contents($this->tmpDir . '/phpunit.xml.dist', '<phpunit/>');

        $command = $this->createTestableCommand(hasWarmup: false);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $phpunitCmd = $command->executedCommands[count($command->executedCommands) - 1];
        $configIndex = array_search('-c', $phpunitCmd, true);
        self::assertIsInt($configIndex);
        self::assertSame('phpunit.xml.dist', $phpunitCmd[$configIndex + 1]);
    }

    public function testWarmupRunsWhenScriptExists(): void
    {
        $command = $this->createTestableCommand(hasWarmup: true);
        $tester = new CommandTester($command);

        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertCount(2, $command->executedCommands);
        self::assertSame(['composer', 'run', 'tests:warmup'], $command->executedCommands[0]);
    }

    public function testWarmupSkippedWhenScriptAbsent(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        $tester = new CommandTester($command);

        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertCount(1, $command->executedCommands);
        self::assertSame('php', $command->executedCommands[0][0]);
    }

    private function createTestableCommand(bool $hasWarmup): TestableTestsCommand
    {
        if ($hasWarmup) {
            file_put_contents($this->tmpDir . '/composer.json', json_encode([
                'scripts' => ['tests:warmup' => 'echo warmup'],
            ]));
        } else {
            file_put_contents($this->tmpDir . '/composer.json', json_encode([
                'scripts' => [],
            ]));
        }

        $reader = new ComposerFileReader($this->tmpDir);

        return new TestableTestsCommand($this->tmpDir, $reader);
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
