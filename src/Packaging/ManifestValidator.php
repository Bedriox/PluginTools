<?php

declare(strict_types=1);

namespace Bedriox\PluginTools\Packaging;

use JsonException;

final class ManifestValidator
{
    private const int MAX_BYTES = 32768;
    private const array FIELDS = ['schema', 'name', 'version', 'api', 'main', 'namespace', 'authors', 'dependencies', 'softDependencies', 'load'];

    public function validate(string $path): string
    {
        return $this->read($path)->name;
    }

    public function read(string $path): PluginManifest
    {
        if (!is_file($path) || is_link($path) || ($size = filesize($path)) === false || $size > self::MAX_BYTES) {
            throw new PackageException('plugin.json must be a regular file no larger than 32768 bytes.');
        }
        $json = file_get_contents($path);
        if ($json === false) {
            throw new PackageException('plugin.json could not be read.');
        }
        preg_match_all('/"((?:\\\\.|[^"\\\\])*)"\s*:/', $json, $matches);
        $keys = [];
        foreach ($matches[0] as $encodedKey) {
            $encodedKey = substr($encodedKey, 0, (int) strrpos($encodedKey, ':'));
            $key = json_decode(trim($encodedKey), true);
            if (!is_string($key) || isset($keys[$key])) {
                throw new PackageException('plugin.json contains a duplicate object key.');
            }
            $keys[$key] = true;
        }
        try {
            $data = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new PackageException('plugin.json is not valid JSON.', 0, $e);
        }
        if (!is_array($data) || array_is_list($data) || array_diff(array_keys($data), self::FIELDS) !== [] || array_diff(self::FIELDS, array_keys($data)) !== []) {
            throw new PackageException('plugin.json fields do not match schema 1.');
        }
        if ($data['schema'] !== 1 || !is_string($data['name']) || preg_match('/^[A-Z][A-Za-z0-9_]{0,63}$/D', $data['name']) !== 1) {
            throw new PackageException('plugin.json has an invalid schema or name.');
        }
        foreach (['version' => '/^(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)(?:-[0-9A-Za-z.-]+)?$/D', 'api' => '/^[~^]?(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)(?:\.(?:0|[1-9]\d*))?$/D', 'main' => '/^[A-Z][A-Za-z0-9_]*(?:\\\\[A-Z][A-Za-z0-9_]*)*$/D', 'namespace' => '/^[A-Z][A-Za-z0-9_]*(?:\\\\[A-Z][A-Za-z0-9_]*)*$/D'] as $field => $pattern) {
            if (!is_string($data[$field]) || strlen($data[$field]) > 255 || preg_match($pattern, $data[$field]) !== 1) {
                throw new PackageException("plugin.json has an invalid {$field}.");
            }
        }
        if ($data['main'] !== $data['namespace'] && !str_starts_with($data['main'], $data['namespace'] . '\\')) {
            throw new PackageException('The entry point is outside the declared namespace.');
        }
        foreach (['authors', 'dependencies', 'softDependencies'] as $field) {
            if (!is_array($data[$field]) || !array_is_list($data[$field]) || count($data[$field]) > 64) {
                throw new PackageException("plugin.json has an invalid {$field} list.");
            }
            $seen = [];
            $pattern = $field === 'authors' ? '/^[^\x00-\x1f\x7f]{1,80}$/D' : '/^[A-Z][A-Za-z0-9_]{0,63}$/D';
            foreach ($data[$field] as $entry) {
                if (!is_string($entry) || preg_match($pattern, $entry) !== 1 || isset($seen[strtolower($entry)])) {
                    throw new PackageException("plugin.json has an invalid or duplicate {$field} entry.");
                }
                $seen[strtolower($entry)] = true;
            }
        }
        if (in_array($data['name'], [...$data['dependencies'], ...$data['softDependencies']], true) || array_intersect($data['dependencies'], $data['softDependencies']) !== []) {
            throw new PackageException('plugin.json has conflicting dependencies.');
        }
        if (!in_array($data['load'], ['STARTUP', 'WORLD_READY'], true)) {
            throw new PackageException('plugin.json has an invalid load phase.');
        }
        /** @var list<string> $authors */
        $authors = $data['authors'];
        /** @var list<string> $dependencies */
        $dependencies = $data['dependencies'];
        /** @var list<string> $softDependencies */
        $softDependencies = $data['softDependencies'];

        return new PluginManifest(
            $data['schema'],
            $data['name'],
            $data['version'],
            $data['api'],
            $data['main'],
            $data['namespace'],
            $authors,
            $dependencies,
            $softDependencies,
            $data['load'],
        );
    }
}
