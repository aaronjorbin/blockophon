=== Blockophon ===
Contributors:      aaronjorbin
Tags:              block, colophon, typography, theme, plugins
Requires at least: 7.0
Tested up to:      7.0
Stable tag:        0.1.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

A block that automatically generates a colophon for your WordPress site.

== Description ==

A colophon is a brief note — traditionally placed at the end of a book — describing the tools, typefaces, and materials used to make it. Blockophon brings that tradition to WordPress.

Add the Blockophon block to any page or post and it will automatically compose a colophon from your site's live data: the active theme, the fonts and colors defined in your theme.json, and the plugins you have running. Every section is optional, so you can show as much or as little as you like.

**What it shows:**

* **Theme** — the active theme name and author, with a note if it is a child theme and a summary of any template or global-style customizations you have made.
* **Typography** — the heading and body fonts declared in your theme's global styles.
* **Color palette** — interactive swatches for every color in your theme palette; click a swatch to reveal its hex value.
* **Plugins** — a count of active plugins, mu-plugins, and drop-ins, with an expandable list of plugin names and versions.

**AI-generated prose (WordPress 7.0+)**

When a WordPress AI connector is configured, you can enable the "Generate text with AI" toggle in the block inspector. Blockophon will send your site's colophon data to the AI client and replace the structured output with a short, conversational paragraph or two. The generated text is cached and only regenerated when your theme, plugins, or global styles change.

**Cache**

Blockophon caches its data in a non-autoloaded option so repeated page loads are fast. The cache is automatically cleared whenever you switch themes, activate or deactivate a plugin, save a template or template part, update global styles, or run a plugin or theme upgrade.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/blockophon` directory, or install the plugin through the WordPress Plugins screen directly.
1. Activate the plugin through the Plugins screen.
1. Add the **Blockophon** block to any page or post using the block inserter.
1. Use the block settings panel to choose which sections to display.

== Frequently Asked Questions ==

= Does this plugin require WordPress 7.0? =

Yes. The AI text-generation feature relies on `wp_ai_client_prompt()`, which ships in WordPress 7.0. The rest of the block works on any version that supports the Block API v3 (WordPress 6.6+), but the minimum declared version is 7.0.

= Where does the colophon data come from? =

Everything is read live from your WordPress installation: `wp_get_theme()` for theme data, `get_plugins()` for plugin lists, `wp_get_global_settings()` and `wp_get_global_styles()` for typography and color palette, and `get_block_templates()` for template customizations.

= How do I enable AI-generated prose? =

Configure a WordPress AI connector in your site settings (Settings → AI, available in WordPress 7.0+), then select the Blockophon block and toggle "Generate text with AI" in the block inspector sidebar.

= Can I show only some sections? =

Yes. Each section — theme, typography, colors, and plugins — has its own toggle in the block inspector. You can enable or disable any combination.

= How is the cache cleared? =

Automatically, on switch_theme, activated_plugin, deactivated_plugin, save_post_wp_template, save_post_wp_template_part, save_post_wp_global_styles, deleted_post (for template types), and after plugin or theme upgrades via upgrader_process_complete.

== Screenshots ==

1. The Blockophon block displayed on the front end, showing theme, typography, color swatches, and the plugin list.
2. The block inspector panel with toggles for each section and the AI text option.

== Changelog ==

= 0.1.0 =
* Initial release.

== Credits ==

The plugin header image uses the following typefaces:

* [Joost](https://fonts.adobe.com/fonts/joost)
* [Fat Frank](https://fonts.adobe.com/fonts/fatfrank)
* [MuseoModerno](https://fonts.google.com/specimen/MuseoModerno)
* [OFL Sorts Mill Goudy](https://fonts.google.com/specimen/Sorts+Mill+Goudy)
* [Kathleen](https://davemart.in/2023/12/18/kathleen-regular-font/)
* [TT Modernoir](https://fonts.adobe.com/fonts/tt-modernoir)

Header image colors are drawn from Marc Chagall's *The Praying Jew* (1923), held in the collection of the Art Institute of Chicago: https://www.artic.edu/artworks/23700/the-praying-jew
