# Bedriox PluginTools

PluginTools is the development companion for Bedriox plugins. Installing
`PluginTools.phar` enables source-folder plugins and adds the recommended
in-server packaging workflow. Bedriox itself remains a PHAR-only loader;
PluginTools owns source discovery, validation, loading, and packaging.

## Develop from source

Put PluginTools and each development project directly under the server's
`plugins/` directory:

```text
plugins/
|-- PluginTools.phar
`-- MyPlugin/
    |-- plugin.json
    |-- src/
    `-- resources/
```

PluginTools activates source loading automatically when it starts. It applies
the same manifest, API-version, dependency, namespace, lifecycle, command, and
event rules used for packaged plugins. Projects are inspected before any of
their PHP code executes. Unsafe paths, symbolic links, duplicate names, missing
entry points, and projects outside the configured limits are rejected.

PHP classes cannot be unloaded safely. Restart Bedriox after changing source
code or adding and removing source plugins.

## Build from the server console

The primary packaging command is:

```text
makeplugin MyPlugin
```

`MyPlugin` must be the name of a source plugin discovered under `plugins/`.
Filesystem paths are not accepted. The command writes:

```text
plugin_data/PluginTools/MyPlugin.phar
plugin_data/PluginTools/MyPlugin.phar.sha256
```

An existing build is preserved by default. Replace it explicitly with:

```text
makeplugin MyPlugin --overwrite
```

Packaging uses a bounded child PHP process, so archive creation does not block
the authoritative simulation. The new archive and checksum are prepared before
an existing build is replaced. If packaging fails, the previous build remains
available.

## Standalone CLI

For CI, automation, or packaging while Bedriox is offline, use the same
packager through the standalone command:

```shell
php -d phar.readonly=0 bin/plugin-tools path/to/plugin dist/MyPlugin.phar
```

The packaged tool is directly executable too:

```shell
php -d phar.readonly=0 PluginTools.phar path/to/plugin dist/MyPlugin.phar
```

Add `--overwrite` to either command to replace an existing archive. The console
command is the normal development workflow; the CLI is an alternative for
automation and offline builds.

`phar.readonly=0` is needed only by the isolated build process. Running Bedriox
or loading a packaged plugin does not require it. PluginTools validates the
schema-1 manifest, rejects links and path escapes, excludes development and
secret files, sorts archive paths, signs the PHAR with SHA-256, and writes a
SHA-256 sidecar. PHP does not expose a portable PHAR entry-timestamp setter, so
PluginTools does not claim byte-for-byte reproducibility where PHP supplies
archive timestamps.

PluginTools is intended for development servers. Remove it from a production
installation when source loading and in-server packaging are not required.

Licensed under [GPL-3.0-only](LICENSE). See [SECURITY.md](SECURITY.md) before
reporting vulnerabilities. Project website: [bedriox.com](https://bedriox.com).
