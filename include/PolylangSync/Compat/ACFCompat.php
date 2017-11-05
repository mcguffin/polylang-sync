<?php

namespace PolylangSync\Compat;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use PolylangSync\ACF;
use PolylangSync\Core;

class ACFCompat extends Core\Singleton {

	private $core;

	/**
	 *	Private constructor
	 */
	protected function __construct() {

		$this->core = Core\Core::instance();

		$this->sync 		= ACF\Sync::instance();
		$this->translate	= ACF\Translate::instance();
	}

}
