<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Tests;

use Bedriox\PluginTools\Packaging\ManifestValidator;
use Bedriox\PluginTools\Packaging\PackageException;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('supportedApiConstraints')]
    public function testAcceptsBedrioxApi03Constraints(string $api): void
    {
        $path = $this->manifestWithApi($api);

        self::assertSame('PluginTools', new ManifestValidator()->validate($path));
    }

    /** @return iterable<string, array{string}> */
    public static function supportedApiConstraints(): iterable
    {
        foreach (['0.3', '0.3.0', '^0.3', '^0.3.0', '~0.3', '~0.3.0'] as $api) {
            yield $api => [$api];
        }
    }

    public function testRejectsUnsupportedApiConstraint(): void
    {
        $path = $this->manifestWithApi('^0.2');

        $this->expectException(PackageException::class);
        $this->expectExceptionMessage('unsupported API ^0.2');
        new ManifestValidator()->validate($path);
    }

    private function manifestWithApi(string $api): string
    {
        $manifest = file_get_contents(__DIR__ . '/../plugin.json');
        self::assertIsString($manifest);
        $decoded = json_decode($manifest, true, 16, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        $decoded['api'] = $api;
        $path = tempnam(sys_get_temp_dir(), 'plugin-manifest-');
        self::assertIsString($path);
        file_put_contents($path, json_encode($decoded, JSON_THROW_ON_ERROR));

        return $path;
    }
}
