<?php

namespace PolylangSync\Taxonomy;
use PolylangSync\Core;
use PolylangSync\Ajax;


class Taxonomy extends Core\Singleton {

	private $core;

	private $sync;

	/**
	 *	Private constructor
	 */
	protected function __construct() {

		$this->core = Core\Core::instance();
		//*
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'rest_api_init', array( $this, 'admin_init' ) );
		/*/
		add_action( 'init', array( $this, 'admin_init' ) );
		//*/
//		add_action( 'load-edit-tags.php', array( $this, 'enqueue_assets' ) );
	}

	// add settings page
	/**
	 *	@action admin_init
	 */
	public function admin_init() {

		$this->sync = Sync::instance();

	}
	
	/**
	 * Enqueue options Assets
	 */
	function enqueue_assets() {
//		wp_enqueue_style( 'polylang_sync-admin' , $this->core->get_asset_url( '/css/admin.css' ) );
		if ( isset( $_GET[ 'taxonomy' ] ) && pll_is_translated_taxonomy( $_GET[ 'taxonomy' ] ) ) {
			wp_enqueue_script( 'polylang_sync_admin_tags' , $this->core->get_asset_url( 'js/admin/tags.js' ), array( 'jquery-unserialize' ) );
			wp_localize_script('polylang_sync_admin_tags' , 'polylang_sync_admin_tags' , array(
			) );
		}
	}

}