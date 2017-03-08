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


		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'rest_api_init', array( $this, 'admin_init' ) );

	}

	// add settings page
	/**
	 *	@action admin_init
	 */
	public function admin_init() {

		if ( function_exists( 'PLL' ) ) {
			$this->sync = Sync::instance();
		}

	}

}