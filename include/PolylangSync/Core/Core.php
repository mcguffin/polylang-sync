<?php

namespace PolylangSync\Core;

use PolylangSync\Compat;

class Core extends Plugin {

	/**
	 *	Private constructor
	 */
	protected function __construct() {
		add_action( 'plugins_loaded' , array( $this , 'load_textdomain' ) );
		add_action( 'plugins_loaded' , array( $this , 'init_compat' ), 0 );

		register_activation_hook( POLYLANG_SYNC_FILE, array( __CLASS__ , 'activate' ) );
		register_deactivation_hook( POLYLANG_SYNC_FILE, array( __CLASS__ , 'deactivate' ) );
		register_uninstall_hook( POLYLANG_SYNC_FILE, array( __CLASS__ , 'uninstall' ) );

		parent::__construct();
	}


	/**
	 *	Load text domain
	 *
	 *  @action plugins_loaded
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'polylang-sync' , false, POLYLANG_SYNC_DIRECTORY . '/languages/' );
	}

	/**
	 *	Load Compatibility classes
	 *
	 *  @action plugins_loaded
	 */
	public function init_compat() {
		if ( class_exists( 'Polylang' ) ) {
			Compat\Polylang::instance();
		}
		if ( class_exists( 'acf' ) && function_exists('acf') && version_compare( acf()->version, '5.0.0', '>=' ) ) {
			Compat\ACFCompat::instance();
		}
	}


	/**
	 *	Get installed Polylang languages
	 *
	 *  @return array	language slugs
	 */
	public function get_pll_languages() {
		$langs	= array();
		$terms	= get_terms( array(
			'taxonomy'		=> 'language',
			'hide_empty'	=> false,
		) );
		foreach ( $terms as $term ) {
			$langs[] = $term->slug;
		}
		return $langs;
	}

	/**
	 *	Get asset url for this plugin
	 *
	 *	@param	string	$asset	URL part relative to plugin class
	 *	@return string asset URL
	 */
	public function get_asset_url( $asset ) {
		return plugins_url( $asset, POLYLANG_SYNC_FILE );
	}


	/**
	 *	Fired on plugin activation
	 */
	public static function activate() {
	}

	/**
	 *	Fired on plugin deactivation
	 */
	public static function deactivate() {
	}

	/**
	 *	Fired on plugin deinstallation
	 */
	public static function uninstall() {
	}

}
