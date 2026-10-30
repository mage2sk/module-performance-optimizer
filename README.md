# Magento 2 Performance Optimizer

Panth_PerformanceOptimizer adds a small set of frontend optimizations to a Magento 2 storefront from admin configuration, without edits to theme templates. It does two things: it renders one block in the head of every frontend page (the `head.additional` block) that outputs the `x-cloak` style, and it registers a frontend-area plugin on the layout output that rewrites the page body on the server before the HTML is sent to the browser: it adds missing `width` and `height` attributes to `<img>` tags, adds `loading="lazy"` to `<iframe>` tags and adds `font-display: swap` to `@font-face` rules in inline `<style>` blocks.

It is aimed at store owners and developers working on Core Web Vitals (layout shift and main-thread blocking) on Hyva and Luma storefronts; the package description states it is designed for both. The `x-cloak` style targets Alpine.js, which Hyva uses.

Product page: [kishansavaliya.com/magento-2-performance-optimizer.html](https://kishansavaliya.com/magento-2-performance-optimizer.html)

## Features

- Font display: adds `font-display:swap` to `@font-face` rules in inline `<style>` blocks of the page body that do not set `font-display`.
- Alpine.js cloak style: outputs an inline `<style>` block containing `[x-cloak]{display:none!important}`.
- Server-side image dimensions: a plugin on `Magento\Framework\View\Layout::getOutput()` adds `width` and `height` attributes to `<img>` tags that lack them, reading the size of the image file from `pub/` and caching the result.
- Iframe lazy loading: adds the native `loading="lazy"` attribute to `<iframe>` tags that have no `loading` attribute.
- One master switch plus one switch per optimization; every field can be set at default, website and store view scope.
- Admin menu entry "Performance Optimizer" with a "Configuration" item under the Panth Core admin menu, protected by its own ACL resources.

## Compatibility

| Platform | Versions |
|---|---|
| Magento Open Source | 2.4.4 to 2.4.8 (as published on the product page) |
| Adobe Commerce | 2.4.4 to 2.4.8 (as published on the product page) |
| PHP | 8.1, 8.2, 8.3, 8.4 (`~8.1.0||~8.2.0||~8.3.0||~8.4.0` in `composer.json`) |
| Themes | Hyva and Luma |

Composer constraints on Magento packages: `magento/framework` ^103.0, `magento/module-config` ^101.2, `magento/module-store` ^101.1, `magento/module-backend` ^102.0.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.4 to 2.4.8
- PHP 8.1, 8.2, 8.3 or 8.4
- `mage2kishan/module-core` ^1.0 (module `Panth_Core`); the module is loaded after `Panth_Core`, `Magento_Theme` and `Magento_Catalog`
- `magento/framework`, `magento/module-config`, `magento/module-store`, `magento/module-backend` (installed with Magento)

`composer.json` declares no suggested packages.

## Installation

```bash
composer require mage2kishan/module-performance-optimizer
bin/magento module:enable Panth_Core Panth_PerformanceOptimizer
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

`setup:di:compile` is only needed in production mode. The module ships no files under `view/*/web`, so `setup:static-content:deploy` is not required for this module.

Check the result:

```bash
bin/magento module:status Panth_PerformanceOptimizer
```

## Configuration

Admin path: Stores > Configuration > Panth Extensions > Performance Optimizer. All fields are Yes/No selects unless noted, and all are available at default, website and store view scope. Every field except the master switch is shown only while "Enable Performance Optimizer" is set to Yes.

### General Settings

| Setting | Default | What it does |
|---|---|---|
| Enable Performance Optimizer | Yes | Master switch. When set to No, the frontend block renders nothing and the image dimension plugin returns the page unchanged. |

Config path: `panth_performance/general/enabled`

### Font Optimization

| Setting | Default | What it does |
|---|---|---|
| Add font-display: swap | Yes | Adds `font-display:swap` to `@font-face` rules in inline `<style>` blocks of the page body, see Usage. |

Config path: `panth_performance/font_optimization/font_display_swap`

### Layout Stability (CLS Prevention)

| Setting | Default | What it does |
|---|---|---|
| Add x-cloak Style | Yes | Outputs the inline `[x-cloak]{display:none!important}` style block. |
| Set Image Dimensions | Yes | Enables the server-side plugin that injects `width` and `height` attributes into `<img>` tags. |

Config paths: `panth_performance/layout_stability/x_cloak_style`, `panth_performance/layout_stability/set_image_dimensions`

### Iframe Lazy Loading

| Setting | Default | What it does |
|---|---|---|
| Enable Iframe Lazy Loading | Yes | Adds `loading="lazy"` to `<iframe>` tags on the server, see Usage. |

Config path: `panth_performance/iframe_lazyload/enabled`

Default behaviour: every option ships enabled, so all optimizations are active on all frontend pages as soon as the module is installed. Flush the cache after changing any setting so cached pages pick up the new output.

## Usage

Once enabled there is nothing to call. The block `performance.optimizer` is added to `head.additional` by `view/frontend/layout/default.xml`, so its output appears inside `<head>` of every frontend page. Each optimization is independent and can be switched off on its own.

### Third-party script deferral

Earlier versions printed a script that set `defer` on third-party `<script src>` elements after the page had loaded. That had no effect: a script in the page HTML is fetched and run when the parser reaches it, before any later script can change it, and scripts inserted by JavaScript already load asynchronously. The script was removed. Adding `defer` on the server is not done because inline scripts that depend on a third-party library would then run before it. The two settings (`panth_performance/script_optimization/defer_third_party` and `excluded_domains`) are no longer shown in the admin and saved values are ignored; add `defer` or `async` where the script tag is output instead.

### font-display: swap

The layout plugin looks at inline `<style>` blocks in the page body. Each `@font-face { ... }` rule that does not contain `font-display` gets `font-display:swap;` added as its first descriptor. Rules that already set `font-display` are left as they are. Fonts declared in external CSS files and in `<head>` are not changed; set `font-display` in the theme CSS for those.

### x-cloak style

The block outputs `<style>[x-cloak]{display:none!important}</style>`, inside `<head>`, so it applies before any element carrying the Alpine.js `x-cloak` attribute is parsed and hides it until Alpine removes that attribute.

### Server-side rewriting

- `Plugin\Layout\InjectImageDimensionsPlugin` runs after `Magento\Framework\View\Layout::getOutput()` in the frontend area only (declared in `etc/frontend/di.xml`), so admin pages are not processed. It handles image dimensions, iframe lazy loading and font-display.
- It returns the output unchanged when the master switch is off, when all three settings are off, or when the output contains none of `<img`, `<iframe` and `@font-face`.
- The page is scanned tag by tag in a single pass. Quoted attribute values are read as a whole, so a `>` inside a value does not end the tag, and markup inside attribute values is not touched. Content of `<script>`, `<style>`, `<template>` (including nested templates), `<textarea>`, `<noscript>`, `<title>` and `<xmp>` elements and HTML comments is copied unchanged, except that `@font-face` rules inside `<style>` are handled as described above.

### Image dimensions

- For each `<img>` tag that lacks a `width` or `height` attribute, the `src` attribute is passed to `Service\ImageDimensionRegistry` for the file dimensions. If both are missing, both are added. If only one is present and it is a whole number, the other is calculated from the image aspect ratio. If the present value is not a whole number (for example `auto` or `100%`) the tag is left unchanged. An existing attribute is never added a second time. New attributes are inserted directly after `<img`.
- The registry ignores empty and `data:` sources, strips query strings and fragments, accepts absolute URLs only when their host matches the current store base URL host, requires the remaining path to start with `/`, URL-decodes it, rejects paths containing `.` or `..` segments or NUL bytes, and rewrites `/static/version<digits>/` to `/static/`. The file is then located under `pub/` and measured with `getimagesize()`. Images served from another host or not present on local disk are left unchanged.
- Results, including misses, are memoized per request and stored in the Magento cache under keys prefixed `panth_perf_imgdim_` with the cache tag `panth_perf_imgdim` for 86400 seconds (24 hours).

### Iframe lazy loading

- Each `<iframe>` tag outside the skipped elements that has no `loading` attribute gets `loading="lazy"`, inserted directly after `<iframe`. Iframes that already carry `loading` (for example `loading="eager"`) are left unchanged.
- The browser then delays loading the iframe until it is near the viewport. Iframes in the first viewport are still loaded straight away. Browsers without support for the attribute ignore it and load the iframe normally.

The module registers no cron jobs, console commands, observers, controllers, web API routes or database tables.

## Developer Notes

- Module name: `Panth_PerformanceOptimizer`
- Composer package: `mage2kishan/module-performance-optimizer` (version 1.0.9 in `composer.json`)
- PHP namespace: `Panth\PerformanceOptimizer`
- Module sequence: `Panth_Core`, `Magento_Theme`, `Magento_Catalog`
- Layout: `view/frontend/layout/default.xml` adds block `performance.optimizer` (`Block\PerformanceOptimizer`, template `Panth_PerformanceOptimizer::performance-optimizer.phtml`) to `head.additional`. A theme can override the template at `Panth_PerformanceOptimizer/templates/performance-optimizer.phtml`.
- Plugin: `panth_performance_inject_image_dimensions` (`Plugin\Layout\InjectImageDimensionsPlugin::afterGetOutput`) on `Magento\Framework\View\Layout`, sort order 100, frontend area only.
- `Helper\Data` public methods: `getConfigValue(string $path, ?int $storeId = null)`, `isEnabled()`, `isDeferThirdPartyEnabled()`, `getExcludedDomains()`, `isFontDisplaySwapEnabled()`, `isXCloakStyleEnabled()`, `isSetImageDimensionsEnabled()`, `isIframeLazyLoadEnabled()`. All values are read at store scope, and every feature check also requires the master switch.
- `Block\PerformanceOptimizer` public methods: `getPerformanceHelper()`, `isEnabled()`, `getExcludedDomainsJson()` (JSON with `<`, `>`, `&` and quotes escaped; not used by the shipped template).
- `Service\ImageDimensionRegistry::getDimensions(string $src): ?array` returns `['width' => int, 'height' => int]` or `null`. It can be reused by other code that needs local image sizes.
- `Model\Config\Backend\Enabled` is the backend model of the master switch; it adds no behaviour to `Magento\Framework\App\Config\Value`.
- ACL resources: `Panth_PerformanceOptimizer::performance` ("Performance Optimizer") and `Panth_PerformanceOptimizer::config` ("Configuration"), both under `Panth_Core::panth_extensions`. The configuration section and both menu items use `Panth_PerformanceOptimizer::config`.
- Database: the module has no `etc/db_schema.xml` and creates no tables. Its only stored data are configuration values under `panth_performance/*` in `core_config_data` and image dimension entries in the Magento cache.

## Uninstallation

```bash
bin/magento module:disable Panth_PerformanceOptimizer
composer remove mage2kishan/module-performance-optimizer
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

No database tables are created, so none need removing. Configuration values under `panth_performance/*` remain in `core_config_data` unless deleted manually. Cached image dimension entries (tag `panth_perf_imgdim`) expire after 24 hours or on the cache flush above. `Panth_Core` is left installed because other Panth modules may depend on it.

## Support

- Product page: [kishansavaliya.com/magento-2-performance-optimizer.html](https://kishansavaliya.com/magento-2-performance-optimizer.html)
- Contact form: [kishansavaliya.com/contact](https://kishansavaliya.com/contact)
- Email: kishansavaliyakb@gmail.com
- GitHub issues: [github.com/mage2sk/module-performance-optimizer/issues](https://github.com/mage2sk/module-performance-optimizer/issues)

## Documentation

[USER_GUIDE.md](USER_GUIDE.md) is written for store administrators and covers installation, verifying the module is active, each configuration field, how each optimization works, troubleshooting, and a CLI reference.

## License

Free and open source under the MIT License. See [LICENSE.txt](LICENSE.txt).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Links

- Website: [kishansavaliya.com](https://kishansavaliya.com)
- All extensions: [kishansavaliya.com/magento-extensions.html](https://kishansavaliya.com/magento-extensions.html)
- GitHub: [github.com/mage2sk/module-performance-optimizer](https://github.com/mage2sk/module-performance-optimizer)
- Packagist: [packagist.org/packages/mage2kishan/module-performance-optimizer](https://packagist.org/packages/mage2kishan/module-performance-optimizer)
