# PluginTools contributor guide

This repository owns Bedriox development source loading, the `makeplugin` console workflow, and the standalone packaging CLI. Bedriox admits definitions through its public API but must never scan source projects itself. Keep source loading and packaging independent of server internals and use only released public plugin APIs. Never invent pending APIs.

All filesystem input is hostile. Resolve canonical paths, reject links and escapes, bound counts and sizes before reading, use sorted archive paths, exclude credentials and build inputs, and never overwrite a source project. Package output must include a SHA-256 sidecar. `phar.readonly=0` is a build-only requirement.

The console command is the primary user workflow. It accepts a discovered plugin name, never an arbitrary path, writes under PluginTools' own data folder, preserves existing output unless `--overwrite` is explicit, and must not block the simulation thread. Keep the standalone CLI as an automation and offline-build alternative using the same packager.

Target PHP 8.4 through PHP 8.x with strict types. Do not commit `vendor/`, PHARs, secrets, logs, player data, Minecraft assets, or packet captures. Update tests and README with behavior. Run `composer validate --strict`, `composer audit --locked`, and `composer check`; inspect the complete diff before committing.

Original work is GPL-3.0-only. Keep commits focused and natural without personal email addresses or identity trailers.
