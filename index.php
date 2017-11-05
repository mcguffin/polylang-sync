<?php

/*
Plugin Name: Polylang Sync
Plugin URI: https://github.com/mcguffin/polylang-sync
Description: Keep Menus, ACF-Fields and more in Sync on your Polylang-Powered multilingual WordPress-Site.
Author: Jörn Lund
Version: 0.0.2
Author URI: https://github.com/mcguffin/
License: GPL3

Text Domain: polylang-sync
Domain Path: /languages/
*/

/*  Copyright 2017  Jörn Lund

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
Plugin was generated by WP Plugin Scaffold
https://github.com/mcguffin/wp-plugin-scaffold
Command line args were: `"ACF Polylang Sync" admin+css+js settings_page+css+js git`
*/

namespace PolylangSync;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


define( 'POLYLANG_SYNC_FILE', __FILE__ );
define( 'POLYLANG_SYNC_DIRECTORY', plugin_dir_path(__FILE__) );

require_once POLYLANG_SYNC_DIRECTORY . 'include/vendor/autoload.php';

Core\Core::instance();

Taxonomy\Taxonomy::instance();

if ( is_admin() || defined( 'DOING_AJAX' ) ) {

	// don't WP-Update actual repos!
	if ( ! file_exists( ACFQUICKEDIT_DIRECTORY . '/.git/' ) ) {
		AutoUpdate\AutoUpdateGithub::instance();
	}

//	Admin\Admin::instance();

	Settings\SettingsPagePolylangSync::instance();

}
