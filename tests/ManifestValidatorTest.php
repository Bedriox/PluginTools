<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Tests;

use Bedriox\PluginTools\Packaging\ManifestValidator;
use Bedriox\PluginTools\Packaging\PackageException;
use PHPUnit\Framework\TestCase;

final class ManifestValidatorTest extends TestCase
{
    public function testRepositoryManifestMatchesSchema(): void
    {
        self::assertSame('PluginTools', new ManifestValidator()->validate(__DIR__ . '/../plugin.json'));
    }

    public function testRejectsDuplicateObjectKeys(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'plugin-manifest-');
        self::assertIsString($path);
        file_put_contents($path, '{"schema":1,"schema":1}');
        $this->expectException(PackageException::class);
        new ManifestValidator()->validate($path);
    }
}
