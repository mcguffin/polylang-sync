<?php

namespace PolylangSync\Compat;

use PolylangSync\ACF;
use PolylangSync\Core;

class ACF extends Core\Singleton {

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
