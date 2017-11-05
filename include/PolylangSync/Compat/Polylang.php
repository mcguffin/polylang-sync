<?php

namespace PolylangSync\Compat;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use PolylangSync\Core;
use PolylangSync\Sync;

class Polylang extends Core\Singleton {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {
		add_action('pll_init', array( $this, 'pll_init') );
	}

	/**
	 *	@action pll_init
	 */
	public function pll_init( $polylang ) {
		Sync\Menu::instance();
		Sync\Taxonomy::instance();
	}

}
