=== Privacy My Way ===
Contributors: richard.coffee
Requires at least: 4.7
Tested up to: 5.2.1
Stable tag: 1.5.5
License: MIT

Control the information that your wordpress site is sending to wordpress.org.  View our
[repository](https://github.com/RichardCoffee/privacy-my-way) on github.

== Description ==

This plugin will enable you to finely control some of the information that the WordPress
core code sends back to wordpress.org, including your site's url, the number of users
you have, what plugins are installed and active, and what themes are installed and
active.  It does not filter what PHP and MySQL versions your
server is running, nor the language files installed.

== Installation ==

= Upload =

1.  Download [the latest release](https://github.com/RichardCoffee/privacy-my-way/releases/latest).
2.  Go to the Plugins -> Add New screen and click the Upload tab.
3.  Upload the zipped archive directly.
4.  Go to the Plugins screen and click Activate.
5.  Either click on the Settings link, or go to Dashboard -> Setting -> Privacy My Way, to edit and save the options.

Note:  If an error occurs when attempting to activate the plugin, then manually check your site's plugin directory and
make sure that the plugin directory name does not end with a version number.  If it does, you will need to remove the
version number from the directory name before the plugin can be activated.

= Manual =

This is your best option, although if you have gotten this far you probably don't need these instructions.

1.  Download [the latest release](https://github.com/RichardCoffee/privacy-my-way/releases/latest).
2.  Unzip the master.zip file into a temp directory in your local harddrive.
3.  Using FTP, copy the `privacy-my-way` directory into your site's WordPress plugin directory.  Remove the version number when doing so.
4.  Go to your WordPress Admin Dashboard -> Plugins screen and activate the plugin.
5.  Either click on the Settings link, or go to Dashboard -> Setting -> Privacy My Way, to edit and save the options.

= Upgrades =

Release updates are handled using [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker), so everything should work the WordPress way.

== Frequently Asked Questions ==

See the [GitHib Wiki](https://github.com/RichardCoffee/privacy-my-way/wiki).  Open an issue if you can't find the info you need.

== Changelog ==

= Next Release =
* Enhancement: Allow for blank or missing Plugin/Author URI in description when filtering plugins.

= 1.5.5 =
* Bugfix:  fixed compatibility issue with fluidity-theme admin options page.

= 1.5.4 =
* Bugfix:   added missing case in switch statement.
* Upgrade:  updated base plugin classes.

= 1.5.3 =
* Bugfix:  The minor bugfix in the last release was itself broken.  I panicked about the missing updater, and well...

= 1.5.2 =
* Bugfix:  forced update on the Plugin Update Checker.  Github lost it's files at some point, even though they still showed up in my own repo.
* Bugfix:  update core file classes/Trait/Attributes.php, fixed issue with sanitizing tags.

= 1.5.1 =
* Bugfix:  fixed issue where an array did not need to be passed through the function filtering active plugins.

= 1.5.0 =
* Enhancement:  added option to prevent browser disclosure.
* Enhancement:  added option to prevent location info being sent to wordpress.org.
* Upgrade:  updated base plugin classes.
* Bugfix:  fixed issue with filtering plugins not always working properly.

= 1.4.0 =
* Enhancement:  added options to prevent WordPress automatic updates, intended for developers.
* Enhancement:  added code for 'core_version_check_query_args' filter.
* Upgrade:  updated base plugin classes, added use of base Options class.
* Upgrade:  added filter for site transients.
* Upgrade:  upgraded to version 4.4 of [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker).

= 1.3.2 =
* Bugfix:  fixed new installation crashes.

= 1.3.1 =
* Bugfix:  fixed missing index error.

= 1.3.0 =
* Enhancement:  added pot file, along with en_US.po file.
* Enhancement:  added option for data deletion when deactivating/uninstalling plugin.
* Enhancement:  added option for logging, removed use of flag file.
* Upgrade:  moved files in assets/ to more correct vendor/ directory.
* Upgrade:  upgraded to version 4.1 of [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker).
* Upgrade:  updated base plugin classes.

= 1.2.0 =
* Enhancement:  expanded prefix use to prevent possible function/file name conflicts.  Thanks [nacin](https://nacin.com/2010/05/11/in-wordpress-prefix-everything/)
* Enhancement:  added color to plugin/theme filter lists for active/inactive status.
* Enhancement:  updated Trait classes.
* Logging:  added use of flag file to give better logging control.

= 1.1.1 =
* Enhancement:  updated Plugin and Trait classes.
* Enhancement:  added check for valid logging function.
* Enhancement:  added header field for GitHub Updater.
* Debug:  added more logging, commented out some.
* Fix: corrected variable reference left over from old code.

= 1.1.0 =
* add use of [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker)

= 1.0.0 =
* Initial release
