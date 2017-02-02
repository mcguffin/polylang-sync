<?php

namespace PolylangSync\Core;

class Core extends Singleton {

	/**
	 *	Private constructor
	 */
	protected function __construct() {
		add_action( 'plugins_loaded' , array( $this , 'load_textdomain' ) );
		add_action( 'init' , array( $this , 'init' ) );
		add_action( 'init' , array( $this , 'register_assets' ) );
		add_action( 'wp_enqueue_scripts' , array( $this , 'wp_enqueue_style' ) );

		register_activation_hook( POLYLANG_SYNC_FILE, array( __CLASS__ , 'activate' ) );
		register_deactivation_hook( POLYLANG_SYNC_FILE, array( __CLASS__ , 'deactivate' ) );
		register_uninstall_hook( POLYLANG_SYNC_FILE, array( __CLASS__ , 'uninstall' ) );
		
		parent::__construct();
	}

	/**
	 *	Load frontend styles and scripts
	 *
	 *	@action wp_enqueue_scripts
	 */
	public function wp_enqueue_style() {
	}


	/**
	 *	Register Assets
	 *
	 *	@action init
	 */
	public function register_assets() {
		wp_register_script( 'jquery-unserialize', $this->get_asset_url( 'js/jquery.unserialize.js' ) );
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
	 *	Init hook.
	 * 
	 *  @action init
	 */
	public function init() {
	}

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
	 *	@return wp_enqueue_editor
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
