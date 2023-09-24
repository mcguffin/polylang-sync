Polylang Sync
=============

Filling the gaps that Polylang leaves.

Keep [ACF-Pro](https://www.advancedcustomfields.com) Fields in Sync on your
[Polylang](http://polylang.wordpress.com)-Powered multilingual WordPress-Site.

 - Requires PHP 5.6+, [ACF 5+](https://www.advancedcustomfields.com/pro) and [Polylang](http://polylang.wordpress.com)
 - Developed and Tested With WP 4.7.2 - 6.3, Polylang 2.1 - 3.4, ACF Pro 5.5 - 6.2

Development Status
------------------

This plugin is still under heavy development. Please do not expect it to be stable and harmless in any way. If you encounter an Issue please don't hesitate to report it in the issues tab.


Features:
---------
  - WordPress
  	- Sync Terms
  	- Sync Menus
  - ACF
  	- Synchronize almost every ACF-Field between translations
  	- Textfield and Textareas can be translated by Polylang string translation
  	- If a translation exists for a relational field (like Images, Post objects, ...), the plugin will link the translated item instead.
  	- If Media Support enabled in Polylang the plugin will auto-create media translations if they don't exist.


Installation
------------

### Production (Stand-Alone)
 - Head over to [releases](../../releases)
 - Download 'polylang-sync.zip'
 - Upload and activate it like any other WordPress plugin
 - AutoUpdate will run as long as the plugin is active

### Development

In your plugin directory:

```shell
git clone git@github.com:mcguffin/polylang-sync.git
cd polylang-sync
npm install
npm run dev
```
