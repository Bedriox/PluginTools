# Bedriox PluginTools

PluginTools is a development-only Bedriox plugin and standalone CLI for building bounded `PluginTools.phar`-style plugin archives. It validates the same schema-1 `plugin.json` shape used by Bedriox, rejects links and path escapes, excludes development and secret files, sorts archive paths, and writes a SHA-256 sidecar.

```shell
php -d phar.readonly=0 bin/plugin-tools path/to/plugin dist/MyPlugin.phar
```

The packaged tool is also directly executable, so developers may keep only the
artifact:

```shell
php -d phar.readonly=0 PluginTools.phar path/to/plugin dist/MyPlugin.phar
```

`phar.readonly=0` is needed only while building. Running Bedriox or a packaged plugin does not require it. PHP's PHAR API does not provide a portable entry-timestamp setter; PluginTools makes entry selection, ordering, content, stub, and signature deterministic but does not claim byte-for-byte reproducibility where the runtime supplies archive timestamps.

When installed under a server's `plugins/` directory, PluginTools currently has no in-server console command because Bedriox has not published that API. Its plugin entry point reports the standalone command through the stable plugin logger. The same PHAR can be executed outside the server to package plugins today.

Licensed under [GPL-3.0-only](LICENSE). See [SECURITY.md](SECURITY.md) before reporting vulnerabilities. Project website: [bedriox.com](https://bedriox.com).
