<p align="center">
  <img src="assets/plugintools.webp" width="192" height="192" alt="PluginTools voxel hammer, code brackets, and cube icon">
</p>

<h1 align="center">Bedriox PluginTools</h1>

<p align="center">
  Develop source plugins locally, validate their structure, and package release-ready PHAR files.
</p>

PluginTools is the shared development companion for Bedriox server and Gateway
plugins. Its common core owns source discovery, schema validation, resources,
and secure packaging. Thin host adapters admit source plugins through public
host APIs; neither Bedriox nor Gateway scans source trees itself.

Releases provide one `PluginTools.phar` and its SHA-256 checksum. Install that
same PHAR under `plugins/` in either Bedriox or Gateway. The archive carries
bounded manifests for both hosts, and each host selects only its own adapter.

## Develop from source

Put PluginTools and each development project directly under the host's
`plugins/` directory:

```text
plugins/
|-- PluginTools.phar
`-- MyPlugin/
    |-- plugin.json
    |-- src/
    `-- resources/
```

Both hosts also give plugins writable data under
`plugin_data/<PluginName>/`. Plugins never write into their source tree or
PHAR.

PluginTools activates source loading automatically when it starts. It applies
the same manifest, API-version, dependency, namespace, lifecycle, command, and
event rules used for packaged plugins. Projects are inspected before any of
their PHP code executes. Unsafe paths, symbolic links, duplicate names, missing
entry points, and projects outside the configured limits are rejected. A safe
directory label and rejection reason are written to the host console.

Source plugins may omit `schema` and `platform`. PluginTools supplies the
current schema and infers the active Bedriox or Gateway host. Explicit values
remain case-sensitive, and misspellings such as `gateaway`, unknown values,
schema-1 manifests, and wrong-host plugins are rejected. Packaged PHARs always
contain the resolved schema and platform so they remain independently valid.

PluginTools supports Bedriox API 0.4 constraints and Gateway API 0.2
constraints. Bedriox accepts `STARTUP` and `WORLD_READY`; Gateway accepts
`STARTUP`. Validation selects these rules from the declared platform.

Source projects may include a `resources/` directory. PluginTools admits its
bounded contents and gives each host the same read-only resource view provided
to packaged plugins. Plugins can then copy defaults into their data directory
whether they are loaded from source or a PHAR.

PHP classes cannot be unloaded safely. Restart the host after changing source
code or adding and removing source plugins.

## Build from the Bedriox console

The Bedriox host adapter provides:

```text
makeplugin MyPlugin
```

`MyPlugin` must be the name of a source plugin discovered under `plugins/`.
Filesystem paths are not accepted. Discovered project names are exposed to the
Bedrock command UI through a live, PluginTools-owned command enum. The command
writes:

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

Gateway source loading is supported by the same PluginTools artifact. Gateway does not
yet expose a console dispatcher suitable for filesystem build commands, so its
player command registry is deliberately not used for packaging. Use the shared
standalone CLI for Gateway projects until that console API is available.

## Standalone CLI

For CI, automation, Gateway projects, or offline packaging, use the same
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
schema-2 host manifest, rejects links and path escapes, excludes development and
secret files, sorts archive paths, signs the PHAR with SHA-256, and writes a
SHA-256 sidecar. PHP does not expose a portable PHAR entry-timestamp setter, so
PluginTools does not claim byte-for-byte reproducibility where PHP supplies
archive timestamps.

PluginTools is intended for development servers. Remove it from a production
installation when source loading and in-server packaging are not required.

## Build the release artifact

Repository maintainers build the shared artifact from the same source tree:

```shell
composer build:phar
```

This writes `build/PluginTools.phar` and
`build/PluginTools.phar.sha256`. The root manifest selects the Bedriox adapter;
the bounded manifest under `platforms/gateway/` selects the Gateway adapter.
Their package identity must match, while API, entry point, dependencies, and
load phase may remain host-specific. The standalone CLI uses the same audited
core without loading either host adapter.

Licensed under [GPL-3.0-only](LICENSE). See [SECURITY.md](SECURITY.md) before
reporting vulnerabilities. Project website: [bedriox.com](https://bedriox.com).
