# Changelog

All notable PluginTools changes are recorded here.

## Unreleased

### Added

- Treat an omitted schema 2 platform as `bedriox` for existing plugin
  compatibility while requiring Gateway plugins to declare `platform: gateway`;
  unknown, incorrectly cased, and wrong-host values receive clear rejections.
- Add platform profiles for Bedriox API 0.4 and Gateway API 0.2, including
  host-specific load-phase validation.
- Split host integration from the shared source discovery, class loading,
  resource, manifest, and packaging core.
- Add thin Bedriox and Gateway host adapters and a host-artifact builder for
  `PluginTools-Bedriox.phar` and `PluginTools-Gateway.phar`.
- Use the same `plugins/` and `plugin_data/` conventions on both hosts.
- Target Bedriox plugin API 0.4 and reject source manifests that request an
  unsupported API constraint.
- Provide bounded source-project resources through the same plugin data API
  used by packaged plugins.
- Migrate `makeplugin` to the typed command API and advertise discovered source-project names through a plugin-owned soft enum.
- Add bounded manifest validation and deterministic PHAR input selection.
- Add strong embedded SHA-256 signatures and SHA-256 sidecar generation.
- Add the development-only `PluginTools` Bedriox plugin scaffold and standalone
  packaging command.
- Add bounded source-project discovery and namespace-restricted loading support.
- Add the `makeplugin <PluginName> [--overwrite]` console packaging workflow,
  with the standalone CLI retained for automation and offline builds.
