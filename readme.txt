=== Privacy My Way ===
Contributors: richard.coffee
Requires at least: 4.7
Tested up to: 4.7.3
Stable Tag: 1.1.1
License: MIT

Control the information that your wordpress site is sending to wordpress.org

== Description ==

This plugin will enable you to finely control all the information that the WordPress
core code sends back to wordpress.org, including your site's url, the number of users
you have, what plugins are installed and active, and what themes are installed and
active.

== Installation ==

This plugin is not available from the WordPress repository, and must be installed manually.


1.  Download [the latest release](https://github.com/RichardCoffee/privacy-my-way/releases/latest).
2.  Copy the `privacy-my-way` directory into your WordPress plugin directory.  Remove the version number when doing so.
3.  Go to your WordPress Dashboard->Plugins screen and activate the plugin.
4.  Either click on the Settings link, or go to Dashboard->Setting->Privacy My Way, to edit and save the options.

Release updates are handled using [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker), so everything should work the WordPress way.

== Changelog ==

= 1.2.0 =
* Enhancement:  expanded prefix use to prevent possible function/file name conflicts.  Thanks [nacin](https://nacin.com/2010/05/11/in-wordpress-prefix-everything/)
* Enhancement:  added color to plugin/theme filter lists for active/inactive status.
* Enhancement:  updated Trait classes.
* Logging:  added use of flag file to give better logging control.
* Minor:  better integration with my personal theme option framework.

= 1.1.1 =
* Enhancement:  updated Plugin and Trait classes.
* Enhancement:  added check for valid logging function.
* Enhancement:  added header field for GitHub Updater.
* Debug:  added more logging, commented out some.
* Fix: corrected variable reference left over from old code.
* Minor:  changed some comment text.


= 1.1.0 =
* add use of [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker)

= 1.0.0 =
* Initial release
