
# Privacy My Way #


       Contributors: richardcoffee
               Tags: admin, updates, plugins, themes, core
         Stable tag: 1.8.2
  Requires at least: 4.7.0
       Tested up to: 5.4.2
            License: MIT


Control the information that your wordpress site is sending to wordpress.org

## Description

This plugin will enable you to finely control some of the information that the WordPress core code sends back to wordpress.org, including your site's url,
the number of users you have, what plugins are installed and active, and what themes are installed and active.  It can also obscure what PHP version and
database software version are being run on your server.

The number of users you have, the PHP version, and database software version are sent to wordpress.org via URL parameters.  This means that, not only is it
all sent unencrypted, but it is also probably in the log files of every server between you and wordpress.  This means that neither you nor wordpress has any
control over what is done with that information.  Take back that control with this plugin.


## Installation

Please use [the latest release](https://github.com/RichardCoffee/privacy-my-way/releases/latest).

When using the Upload option, be aware that Github includes the version number as part of the directory name inside the zip file.  You will
need FTP or SSH access to get around that.  Once you have the plugin actually installed, then upgrades via the admin dashboard will work just fine.
If anyone knows how to get github not to add the version number to the tarball's internal directory, please drop me a note.

### Manual Installation

This is your best option, although if you have gotten this far you probably don't need these instructions.

1.  Download [the latest release](https://github.com/RichardCoffee/privacy-my-way/releases/latest).
2.  Upload the master.zip file to your site via the Upload Plugin button on the __Plugins -> Add New__ admin page,
    or if using ftp, unzip the file into a temp directory and copy the `privacy-my-way` directory into your WordPress
    plugin directory.  Remove the version number when doing so.
3.  Go to the __Plugins__ screen and activate the plugin.
4.  Either click on the __Settings__ link on the Plugins screen, or go to __Settings -> Privacy My Way__, to edit and save the options.

### Upgrades

Release updates are handled using [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker), so everything should work the WordPress way.

## Warning

I no longer consider this beta code and I do not expect it to break your site, but if it does, then you get to keep the pieces.

## FAQ

See the [GitHib Wiki](https://github.com/RichardCoffee/privacy-my-way/wiki).  Open an issue if you can't find the info you need.  Please.

### Contributions

Contributions are welcome - fork, fix and send pull requests against the `development` branch please.

## Changelog

### 1.8.1
* Certify:  Tested with WordPress 5.4.1
* Upgrade:  Updated core files.

### 1.8.0
* Enhancement:  add options to obscure the php and database versions.
* Upgrade:  Updated core files.
* Bugfix:   Added a check for a transient object property.
* Minor:    Removed reference to WP constant.
* Minor:    Removed obsolete css.

### 1.7.5
* Bugfix:  Version information didn't get updated properly for the 1.7.4 release.

### 1.7.4
* Upgrade:  Bumped tested WP version to 5.4
* Upgrade:  Updated core file.

### 1.7.3
* Bugfix:  Blocking all plugin updates was also blocking core updates.

### 1.7.2
* Upgrade:  updated core files.
* Bugfix:  Bug in core file made 'Setting' link disappear.
* Bugfix:  Added an error handler for when blocking all reports of plugins.
* Bugfix:  More property object checks added.
* Minor:  Remove a superfluous method.

### 1.7.1
* Bugfix:  Now passes script localization information properly.

### 1.7.0
* Enhancement:  Rewrote plugin classes to bring them in sync with core files.
* Upgrade:  updated core files, which included some bug fixes.
* Upgrade:  updated WP tested version.
* Bugfix:  CSS fix for admin form.
* Bugfix:  Some option filters were being run twice.
* Bugfix:  Now passes the 'network_id' to get_user_count().
* Bugfix:  Now checking for object property in transient filter.
* Minor:  More work on documentation.

### 1.6.1
* Upgrade:  updated core files.

### 1.6.0
* Enhancement:  Allow for blank or missing Plugin/Author URI in description when filtering plugins.
* Upgrade:  upgraded to version 4.8 of [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker).
* Upgrade:  updated core files.
* Bugfix:  Prevent a recursion issue with user count.
* Minor:  Replace isset calls with array_key_exists.

### 1.5.5
* Bugfix:  fixed compatibility issue with fluidity-theme admin options page.

### 1.5.4
* Bugfix:   added missing case in switch statement.
* Upgrade:  update base plugin classes.

### 1.5.3
* Bugfix:  The minor bugfix in the last release was itself broken.  I panicked about the missing updater, and well...

### 1.5.2
* Bugfix:  forced update on the Plugin Update Checker.  Github lost it's files at some point, even though they still showed up in my own repo.
* Bugfix:  updated a core plugin file classes/Trait/Attributes.

### 1.5.1
* Bugfix: fixed issue where an array did not need to be passed through the function filtering active plugins.

### 1.5.0
* Enhancement:  added option to prevent browser disclosure.
* Enhancement:  added option to prevent location info being sent to wordpress.org.
* Upgrade:  update base plugin classes.
* Bugfix:  fixed issue with filtering plugins not always working properly.

### 1.4.0
* Enhancement:  added options to prevent WordPress automatic updates, intended for developers.
* Enhancement:  added code for 'core_version_check_query_args' filter.
* Upgrade:  updated base plugin classes, added use of base Options class.
* Upgrade:  added filter for site transients.
* Upgrade:  upgraded to version 4.4 of [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker).

### 1.3.2
* Bugfix:  fixed new installation crashes.

### 1.3.1
* Bugfix:  fixed missing index error.

### 1.3.0
* Enhancement:  added pot file, along with en_US.po file.
* Enhancement:  added option for data deletion when deactivating/uninstalling plugin.
* Enhancement:  added option for logging, removed use of flag file.
* Upgrade:  moved files in assets/ to more correct vendor/ directory.
* Upgrade:  upgraded to version 4.1 of [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker).
* Upgrade:  updated base plugin classes.

### 1.2.0
* Enhancement:  expanded prefix use to prevent possible function/file name conflicts.  Thanks [nacin](https://nacin.com/2010/05/11/in-wordpress-prefix-everything/)
* Enhancement:  added color to plugin/theme filter lists for active/inactive status.
* Enhancement:  updated Trait classes.
* Logging:  added use of flag file to give better logging control.

### 1.1.1
* Enhancement:  updated Plugin and Trait classes.
* Enhancement:  added check for valid logging function.
* Enhancement:  added header field for [GitHub Updater](https://github.com/afragen/github-updater).
* Debug:  added run_tests methods, now uses flag file.
* Fix:  active theme not being reset properly when filtering themes.
* Fix:  corrected variable reference left over from old code.
* Minor:  changed some comment text.

### 1.1.0
* add use of [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker)

### 1.0.0
* Initial release

