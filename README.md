This is beta code.  Not guaranteed to do anything more than take up space on your computer.

# Privacy My Way

Control the information that your wordpress site is sending to wordpress.org

Requires at least: WordPress 4.7 and PHP 5.3.6

Tested up to: WordPress 4.7.3

## Description

This plugin will enable you to finely control all the information that the WordPress core code sends back to wordpress.org, including your site's url,
the number of users you have, what plugins are installed and active, and what themes are installed and active.

## Installation

### Releases

1.  Download [the latest release](https://github.com/RichardCoffee/privacy-my-way/releases/latest).
2.  Copy the `privacy-my-way` directory into your WordPress plugin directory.  Remove the version number when doing so.
3.  Go to your WordPress Dashboard->Plugins screen and activate the plugin.
4.  Either click on the Settings link, or go to Dashboard->Setting->Privacy My Way, to edit and save the options.

Release updates are handled using [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker), so everything should work the WordPress way.
Support for [GitHub Updater](https://github.com/afragen/github-updater) is also present.

### Development

#### SSH

1.  SSH onto your site.
2.  'cd' to your plugin directory.
3.  Clone this repository.
4.  Go to your WordPress Dashboard->Plugins screen and activate the plugin.
5.  Either click on the Settings link, or go to Dashboard->Setting->Privacy My Way, to edit and save the options.
6.  Open an issue on github about what went wrong.

#### FTP

1.  Clone the repository to your computer.
2.  FTP to your site.
3.  Using your FTP client copy the repository into your server's WordPress plugin directory.  You can delete the .git folder on the server, if you need the space...
4.  Go to your WordPress Dashboard->Plugins screen and activate the plugin.
5.  Either click on the Settings link, or go to Dashboard->Settings->Privacy My Way, to edit and save the options.
6.  Open an issue on github about what went wrong.


### Warning

This is beta code.  Use at your own risk.  I do not expect it to break your site, but if it does, then you get to keep the pieces.  Please do not email them to me.

If you are running multisite, please let me know if you have any issues.

## Changelog

### 1.1.2

### 1.1.1
* Enhancement:  updated Plugin and Trait classes
* Enhancement:  added check for valid logging function.
* Enhancement:  added header field for [GitHub Updater](https://github.com/afragen/github-updater).
* Debug:  added run_tests methods, now uses flag file
* Fix:  active theme not being reset properly when filtering themes.
* Fix:  corrected variable reference left over from old code.
* Minor:  changed some comment text


### 1.1.0
* add use of [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker)

### 1.0.0
* Initial release

