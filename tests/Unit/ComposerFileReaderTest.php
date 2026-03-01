<?php

declare(strict_types=1);

namespace TeamMatePro\TestsBundle\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use TeamMatePro\TestsBundle\ComposerFileReader;

#[CoversClass(ComposerFileReader::class)]
class ComposerFileReaderTest extends KernelTestCase
{
    public function testServiceIsWiredAndReadsValidComposerJson(): void
    {
        self::bootKernel();
        $reader = self::getContainer()->get(ComposerFileReader::class);

        self::assertInstanceOf(ComposerFileReader::class, $reader);
        self::assertTrue($reader->exists());
        self::assertTrue($reader->isValid());
        self::assertTrue($reader->hasScript('tests:warmup'));
    }

    public function testHasScriptReturnsFalseForMissingScript(): void
    {
        self::bootKernel();
        $reader = self::getContainer()->get(ComposerFileReader::class);
        assert($reader instanceof ComposerFileReader);

        self::assertFalse($reader->hasScript('nonexistent'));
    }

    public function testExistsReturnsFalseWhenFileIsMissing(): void
    {
        $reader = new ComposerFileReader(sys_get_temp_dir() . '/nonexistent_' . uniqid());

        self::assertFalse($reader->exists());
    }

    public function testIsValidReturnsFalseForInvalidJson(): void
    {
        $tmpDir = sys_get_temp_dir() . '/tests_bundle_' . uniqid();
        mkdir($tmpDir);
        file_put_contents($tmpDir . '/composer.json', 'invalid json{{{');

        try {
            $reader = new ComposerFileReader($tmpDir);

            self::assertTrue($reader->exists());
            self::assertFalse($reader->isValid());
        } finally {
            unlink($tmpDir . '/composer.json');
            rmdir($tmpDir);
        }
    }

    public function testHasScriptReturnsFalseWhenFileDoesNotExist(): void
    {
        $reader = new ComposerFileReader(sys_get_temp_dir() . '/nonexistent_' . uniqid());

        self::assertFalse($reader->hasScript('tests:warmup'));
    }
}
