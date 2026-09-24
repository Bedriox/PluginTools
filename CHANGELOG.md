# Changelog

All notable PluginTools changes are recorded here.

## Unreleased

### Added

- Migrate `makeplugin` to the typed command API and advertise discovered source-project names through a plugin-owned soft enum.
- Add bounded manifest validation and deterministic PHAR input selection.
- Add strong embedded SHA-256 signatures and SHA-256 sidecar generation.
- Add the development-only `PluginTools` Bedriox plugin scaffold and standalone
  packaging command.
- Add bounded source-project discovery and namespace-restricted loading support.
- Add the `makeplugin <PluginName> [--overwrite]` console packaging workflow,
  with the standalone CLI retained for automation and offline builds.
