This is beta code.  Not guaranteed to do anything more than take up space on your computer.


# Privacy My Way

Control the information that your wordpress site is sending to wordpress.org

Requires at least: WordPress 4.7 and PHP 5.3.6

Tested up to: WordPress 4.7.3

## Description

This plugin will enable you to finely control all the information that the WordPress core code sends back to wordpress.org, including your site's url,
the number of users you have, what plugins are installed and active, and what themes are installed and active.

## Installation

Please use [the latest release](https://github.com/RichardCoffee/privacy-my-way/releases/latest).  Contributions are welcome - fork, fix and send pull
requests against the `development` branch please.

### Releases

#### Upload

1.  Download [the latest release](https://github.com/RichardCoffee/privacy-my-way/releases/latest).
2.  Go to __Plugins -> Add New__ screen and click the __Upload__ tab.
3.  Upload the zipped archive directly.
4.  Go to __Dashboard -> Plugins__ screen and activate the plugin.
5.  Either click on the __Settings__ link, or go to __Dashboard -> Setting -> Privacy My Way__, to edit and save the options.

#### Manual

1.  Download [the latest release](https://github.com/RichardCoffee/privacy-my-way/releases/latest).
2.  Copy the `privacy-my-way` directory into your WordPress plugin directory.  Remove the version number when doing so.
3.  Go to __Dashboard -> Plugins__ screen and activate the plugin.
4.  Either click on the __Settings__ link, or go to __Dashboard -> Setting -> Privacy My Way__, to edit and save the options.

Release updates are handled using [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker), so everything should work the WordPress way.
Support for [GitHub Updater](https://github.com/afragen/github-updater) is also present.

### Development

#### SSH

1.  SSH onto your site.
2.  'cd' to your plugin directory.
3.  Clone this repository.
4.  Go to __Dashboard -> Plugins__ screen and activate the plugin.
5.  Either click on the Settings link, or go to __Dashboard -> Setting -> Privacy My Way__, to edit and save the options.
6.  Open an issue on github about what went wrong.

#### FTP

1.  Clone the repository to your computer.
2.  FTP to your site.
3.  Using your FTP client copy the repository into your server's WordPress plugin directory.  You can delete the .git folder on the server, if you need the space...
4.  Go to __Dashboard -> Plugins__ screen and activate the plugin.
5.  Either click on the Settings link, or go to __Dashboard -> Settings -> Privacy My Way__, to edit and save the options.
6.  Open an issue on github about what went wrong.


### Warning

This is beta code.  Use at your own risk.  I do not expect it to break your site, but if it does, then you get to keep the pieces.  Please do not email them to me.

## FAQ

See the [GitHib Wiki](https://github.com/RichardCoffee/privacy-my-way/wiki).

### Contributions

Contributions are welcome - fork, fix and send pull requests against the `development` branch please.

## Changelog

### 1.3.0 - not yet released
* Enhancement:  added pot file, with en_US.po file.
* Enhancement:  added data deletion option.6
* Upgrade:  moved files in assets/ to more correct vendors/
* Bugfix:  added check for multisite when deactivating/uninstalling

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

