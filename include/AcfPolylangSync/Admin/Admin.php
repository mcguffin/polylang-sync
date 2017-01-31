<?php

namespace AcfPolylangSync\Admin;
use AcfPolylangSync\Core;


class Admin extends Core\Singleton {

	private $core;

	/**
	 *	Private constructor
	 */
	protected function __construct() {

		$this->core = Core\Core::instance();

		add_action( 'admin_init', array( $this , 'admin_init' ) );
	}


	/**
	 * Admin init
	 */
	function admin_init() {
	}

	/**
	 * Enqueue options Assets
	 */
	function enqueue_assets() {
		wp_enqueue_style( 'acf_polylang_sync-admin' , $this->core->get_asset_url( '/css/admin.css' ) );

		wp_enqueue_script( 'acf_polylang_sync-admin' , $this->core->get_asset_url( 'js/admin.js' ) );
		wp_localize_script('acf_polylang_sync-admin' , 'acf_polylang_sync_admin' , array(
		) );
	}

}

