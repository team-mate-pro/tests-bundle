<?php

declare(strict_types=1);

namespace TeamMatePro\TestsBundle\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use TeamMatePro\TestsBundle\Command\TestsCommand;
use TeamMatePro\TestsBundle\ComposerFileReader;
use TeamMatePro\TestsBundle\Tests\_Data\TestableTestsCommand;

#[CoversClass(TestsCommand::class)]
#[UsesClass(ComposerFileReader::class)]
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
        file_put_contents($cacheDir . '/test-results', json_encode([
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
        file_put_contents($cacheDir . '/test-results', json_encode([
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

    public function testCoveragePassesWhenAboveThreshold(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        $command->cloverXmlContent = $this->buildCloverXml(80, 100);
        $tester = new CommandTester($command);

        $tester->execute(['--coverage' => '70']);

        self::assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        self::assertStringContainsString('80.00%', $display);
        self::assertStringContainsString('meets the required', $display);
    }

    public function testCoverageFailsWhenBelowThreshold(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        $command->cloverXmlContent = $this->buildCloverXml(50, 100);
        $tester = new CommandTester($command);

        $tester->execute(['--coverage' => '80']);

        self::assertSame(1, $tester->getStatusCode());
        $display = $tester->getDisplay();
        self::assertStringContainsString('50.00%', $display);
        self::assertStringContainsString('below the required', $display);
    }

    public function testCoverageAddsCloverArgToPhpunit(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        $command->cloverXmlContent = $this->buildCloverXml(100, 100);
        $tester = new CommandTester($command);

        $tester->execute(['--coverage' => '50']);

        $phpunitCmd = $command->executedCommands[count($command->executedCommands) - 1];
        $cloverIndex = array_search('--coverage-clover', $phpunitCmd, true);
        self::assertIsInt($cloverIndex);
        self::assertStringContainsString('phpunit-coverage-', $phpunitCmd[$cloverIndex + 1]);
    }

    public function testCoverageNotCheckedWithoutOption(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        $tester = new CommandTester($command);

        $tester->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        $phpunitCmd = $command->executedCommands[count($command->executedCommands) - 1];
        self::assertNotContains('--coverage-clover', $phpunitCmd);
    }

    public function testCoverageFailsWhenCloverFileMissing(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        // Don't set cloverXmlContent so no file is created
        $tester = new CommandTester($command);

        $tester->execute(['--coverage' => '80']);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('Could not parse coverage report', $tester->getDisplay());
    }

    public function testSuitePassesSingleSuiteToPhpunit(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        $tester = new CommandTester($command);

        $tester->execute(['--suite' => ['unit']]);

        self::assertSame(0, $tester->getStatusCode());
        $phpunitCmd = $command->executedCommands[count($command->executedCommands) - 1];
        $suiteIndex = array_search('--testsuite', $phpunitCmd, true);
        self::assertIsInt($suiteIndex);
        self::assertSame('unit', $phpunitCmd[$suiteIndex + 1]);
    }

    public function testSuitePassesMultipleSuitesToPhpunit(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        $tester = new CommandTester($command);

        $tester->execute(['--suite' => ['unit', 'integration']]);

        self::assertSame(0, $tester->getStatusCode());
        $phpunitCmd = $command->executedCommands[count($command->executedCommands) - 1];
        $suiteIndex = array_search('--testsuite', $phpunitCmd, true);
        self::assertIsInt($suiteIndex);
        self::assertSame('unit,integration', $phpunitCmd[$suiteIndex + 1]);
    }

    public function testSuiteNotPassedWithoutOption(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $phpunitCmd = $command->executedCommands[count($command->executedCommands) - 1];
        self::assertNotContains('--testsuite', $phpunitCmd);
    }

    public function testSuiteWithCoverage(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        $command->cloverXmlContent = $this->buildCloverXml(90, 100);
        $tester = new CommandTester($command);

        $tester->execute(['--suite' => ['unit'], '--coverage' => '80']);

        self::assertSame(0, $tester->getStatusCode());
        $phpunitCmd = $command->executedCommands[count($command->executedCommands) - 1];
        self::assertContains('--testsuite', $phpunitCmd);
        self::assertContains('--coverage-clover', $phpunitCmd);
        self::assertStringContainsString('90.00%', $tester->getDisplay());
    }

    public function testGroupPassesSingleGroupToPhpunit(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        $tester = new CommandTester($command);

        $tester->execute(['--group' => ['fast']]);

        self::assertSame(0, $tester->getStatusCode());
        $phpunitCmd = $command->executedCommands[count($command->executedCommands) - 1];
        $groupIndex = array_search('--group', $phpunitCmd, true);
        self::assertIsInt($groupIndex);
        self::assertSame('fast', $phpunitCmd[$groupIndex + 1]);
    }

    public function testGroupPassesMultipleGroupsToPhpunit(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        $tester = new CommandTester($command);

        $tester->execute(['--group' => ['fast', 'critical']]);

        self::assertSame(0, $tester->getStatusCode());
        $phpunitCmd = $command->executedCommands[count($command->executedCommands) - 1];
        $groupIndex = array_search('--group', $phpunitCmd, true);
        self::assertIsInt($groupIndex);
        self::assertSame('fast,critical', $phpunitCmd[$groupIndex + 1]);
    }

    public function testGroupNotPassedWithoutOption(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $phpunitCmd = $command->executedCommands[count($command->executedCommands) - 1];
        self::assertNotContains('--group', $phpunitCmd);
    }

    public function testExcludeGroupPassesSingleGroupToPhpunit(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        $tester = new CommandTester($command);

        $tester->execute(['--exclude-group' => ['flaky']]);

        self::assertSame(0, $tester->getStatusCode());
        $phpunitCmd = $command->executedCommands[count($command->executedCommands) - 1];
        $excludeIndex = array_search('--exclude-group', $phpunitCmd, true);
        self::assertIsInt($excludeIndex);
        self::assertSame('flaky', $phpunitCmd[$excludeIndex + 1]);
    }

    public function testExcludeGroupPassesMultipleGroupsToPhpunit(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        $tester = new CommandTester($command);

        $tester->execute(['--exclude-group' => ['flaky', 'slow']]);

        self::assertSame(0, $tester->getStatusCode());
        $phpunitCmd = $command->executedCommands[count($command->executedCommands) - 1];
        $excludeIndex = array_search('--exclude-group', $phpunitCmd, true);
        self::assertIsInt($excludeIndex);
        self::assertSame('flaky,slow', $phpunitCmd[$excludeIndex + 1]);
    }

    public function testExcludeGroupNotPassedWithoutOption(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $phpunitCmd = $command->executedCommands[count($command->executedCommands) - 1];
        self::assertNotContains('--exclude-group', $phpunitCmd);
    }

    public function testGroupAndExcludeGroupCombined(): void
    {
        $command = $this->createTestableCommand(hasWarmup: false);
        $tester = new CommandTester($command);

        $tester->execute([
            '--suite' => ['integration'],
            '--group' => ['fast'],
            '--exclude-group' => ['flaky'],
        ]);

        self::assertSame(0, $tester->getStatusCode());
        $phpunitCmd = $command->executedCommands[count($command->executedCommands) - 1];

        $suiteIndex = array_search('--testsuite', $phpunitCmd, true);
        self::assertIsInt($suiteIndex);
        self::assertSame('integration', $phpunitCmd[$suiteIndex + 1]);

        $groupIndex = array_search('--group', $phpunitCmd, true);
        self::assertIsInt($groupIndex);
        self::assertSame('fast', $phpunitCmd[$groupIndex + 1]);

        $excludeIndex = array_search('--exclude-group', $phpunitCmd, true);
        self::assertIsInt($excludeIndex);
        self::assertSame('flaky', $phpunitCmd[$excludeIndex + 1]);
    }

    private function buildCloverXml(int $coveredStatements, int $totalStatements): string
    {
        return <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <coverage generated="1234567890">
                <project timestamp="1234567890">
                    <metrics statements="{$totalStatements}" coveredstatements="{$coveredStatements}" />
                </project>
            </coverage>
            XML;
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
