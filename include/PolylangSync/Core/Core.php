<?php

namespace PolylangSync\Core;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use PolylangSync\Compat;

class Core extends Plugin {

	/**
	 *	Private constructor
	 */
	protected function __construct() {
		add_action( 'plugins_loaded' , array( $this , 'init_compat' ), 0 );

		$args = func_get_args();
		parent::__construct( ...$args );
	}

	/**
	 *	Load Compatibility classes
	 *
	 *  @action plugins_loaded
	 */
	public function init_compat() {

		require_once $this->get_plugin_dir() . '/include/legacy.php';

		if ( class_exists( 'Polylang' ) ) {
			Compat\Polylang::instance();
		}
		if ( class_exists( 'acf' ) && function_exists('acf') && version_compare( acf()->version, '5.0.0', '>=' ) ) {
			Compat\ACF::instance();
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
	 *	@return string URL
	 */
	public function get_asset_url( $asset ) {
		$pi = pathinfo($asset);
		if ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && in_array( $pi['extension'], ['css','js']) ) {
			// add .dev suffix (files with sourcemaps)
			$asset = sprintf('%s/%s.dev.%s', $pi['dirname'], $pi['filename'], $pi['extension'] );
		}
		return plugins_url( $asset, $this->get_plugin_file() );
	}

	/**
	 *	Get asset url for this plugin
	 *
	 *	@param	string	$asset	URL part relative to plugin class
	 *	@return string URL
	 */
	public function get_asset_path( $asset ) {
		$pi = pathinfo($asset);
		if ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && in_array( $pi['extension'], ['css','js']) ) {
			// add .dev suffix (files with sourcemaps)
			$asset = sprintf('%s/%s.dev.%s', $pi['dirname'], $pi['filename'], $pi['extension'] );
		}
		return $this->get_plugin_dir() . '/' . preg_replace( '/^(\/+)/', '', $asset );
		return plugins_url( $asset, $this->get_plugin_file() );
	}

}
