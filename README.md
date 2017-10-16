# Maintenance Mode

[WordPress][1] plugin that offers maintenance mode customizable in [theme customizer][2].

__This plugin is still under development and is not finished yet.__

## Donations

If your like this plugin and you want to be maintained and improved more frequently consider donation:

[![Make donation](https://www.paypalobjects.com/webstatic/paypalme/images/pp_logo_small.png "PayPal.Me, your link to getting paid")][3]

## Description

Main features:

* Maintenance mode for your site
* Settings are available through traditional options page or __theme customizer__
* Czech and English localization

## Screenshots

...

## Installation

Here are few steps to install the plugin:

1. Download archive from the [Releases][6] page in plugin's repository
2. Unpack the archvie into your `/wp-content/plugins/` directory
3. Activate the plugin through the __Plugins__ menu in WordPress

## TODO

* [ ] Finish the plugin:
  - [x] ~~After activating plugin has to create new page "Maintenance mode"~~
    - [ ] __FIXME__: When "Maintenance mode" page exists but is in _Trash_ we should re-publish it
  - [ ] This page will be set to `WP_Query` in our hook to `pre_get_posts` action
  - [ ] __Maintenance mode have to work inside the Theme Customizer!__
  - [ ] After deactivating should be "Maintenance mode" page moved to _Trash_ (with user confirmation)
* [ ] Finish both readme files
* [x] ~~Enable localization~~
* [ ] Finish Czech and English localization
* [ ] Create some screenshots
* [ ] Release on [GitHub][4]
* [ ] Blog/shop posts on [home page][5]

In future investigate how to support __WPMU__.

[1]:https://wordpress.org/
[2]:https://codex.wordpress.org/Theme_Customization_API
[3]:https://www.paypal.me/ondrejd
[4]:https://github.com/ondrejd/odwp-maintenance_mode
[5]:https://ondrejd.com/
[6]:https://github.com/ondrejd/odwp-maintenance_mode/releases
