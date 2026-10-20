<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Tests;

use Bedriox\PluginTools\Core\PluginPlatform;
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
        file_put_contents($path, '{"schema":2,"schema":2}');
        $this->expectException(PackageException::class);
        new ManifestValidator()->validate($path);
    }

    #[DataProvider('supportedApiConstraints')]
    public function testAcceptsBedrioxApi04Constraints(string $api): void
    {
        $path = $this->manifestWithApi($api);

        self::assertSame('PluginTools', new ManifestValidator()->validate($path));
    }

    /** @return iterable<string, array{string}> */
    public static function supportedApiConstraints(): iterable
    {
        foreach (['0.4', '0.4.0', '^0.4', '^0.4.0', '~0.4', '~0.4.0'] as $api) {
            yield $api => [$api];
        }
    }

    public function testRejectsUnsupportedApiConstraint(): void
    {
        $path = $this->manifestWithApi('^0.2');

        $this->expectException(PackageException::class);
        $this->expectExceptionMessage('unsupported bedriox API ^0.2');
        new ManifestValidator()->validate($path);
    }

    public function testManifestWithoutPlatformDefaultsToBedriox(): void
    {
        $path = $this->manifestWith(static function (array &$manifest): void {
            unset($manifest['platform']);
        });

        self::assertSame(PluginPlatform::BEDRIOX, new ManifestValidator()->read($path)->platform);
        self::assertSame(PluginPlatform::BEDRIOX, new ManifestValidator(PluginPlatform::BEDRIOX)->read($path)->platform);
    }

    public function testGatewayProfileRequiresExplicitPlatform(): void
    {
        $path = $this->manifestWith(static function (array &$manifest): void {
            unset($manifest['platform']);
        });

        $this->expectException(PackageException::class);
        $this->expectExceptionMessage('missing the required platform field');
        new ManifestValidator(PluginPlatform::GATEWAY)->read($path);
    }

    #[DataProvider('unknownPlatforms')]
    public function testRejectsUnknownOrIncorrectlyCasedPlatform(mixed $platform): void
    {
        $path = $this->manifestWith(static function (array &$manifest) use ($platform): void {
            $manifest['platform'] = $platform;
        });

        $this->expectException(PackageException::class);
        $this->expectExceptionMessage('platform must be exactly "bedriox" or "gateway"');
        new ManifestValidator()->validate($path);
    }

    /** @return iterable<string, array{mixed}> */
    public static function unknownPlatforms(): iterable
    {
        yield 'incorrect case' => ['Gateway'];
        yield 'misspelling' => ['gateaway'];
        yield 'unknown' => ['velocity'];
        yield 'non-string' => [7];
    }

    public function testGatewayProfileAcceptsGatewayApiAndRejectsBedrioxHost(): void
    {
        $path = $this->manifestWith(static function (array &$manifest): void {
            $manifest['platform'] = 'gateway';
            $manifest['api'] = '^0.2';
        });

        $manifest = new ManifestValidator(PluginPlatform::GATEWAY)->read($path);
        self::assertSame(PluginPlatform::GATEWAY, $manifest->platform);

        $this->expectException(PackageException::class);
        $this->expectExceptionMessage('this host accepts only "bedriox"');
        new ManifestValidator(PluginPlatform::BEDRIOX)->read($path);
    }

    public function testGatewayRejectsWorldReadyLoadPhase(): void
    {
        $path = $this->manifestWith(static function (array &$manifest): void {
            $manifest['platform'] = 'gateway';
            $manifest['api'] = '^0.2';
            $manifest['load'] = 'WORLD_READY';
        });

        $this->expectException(PackageException::class);
        $this->expectExceptionMessage('invalid gateway load phase');
        new ManifestValidator()->read($path);
    }

    private function manifestWithApi(string $api): string
    {
        return $this->manifestWith(static function (array &$manifest) use ($api): void {
            $manifest['api'] = $api;
        });
    }

    /** @param callable(array<string, mixed>&): void $change */
    private function manifestWith(callable $change): string
    {
        $manifest = file_get_contents(__DIR__ . '/../plugin.json');
        self::assertIsString($manifest);
        $decoded = $this->decodeObject($manifest);
        $change($decoded);
        $path = tempnam(sys_get_temp_dir(), 'plugin-manifest-');
        self::assertIsString($path);
        file_put_contents($path, json_encode($decoded, JSON_THROW_ON_ERROR));

        return $path;
    }

    /** @return array<string, mixed> */
    private function decodeObject(string $json): array
    {
        $decoded = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        $object = [];
        foreach ($decoded as $key => $value) {
            self::assertIsString($key);
            $object[$key] = $value;
        }

        return $object;
    }
}
