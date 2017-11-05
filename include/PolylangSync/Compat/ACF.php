<?php

namespace PolylangSync\Compat;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use PolylangSync\Core;
use PolylangSync\Sync\ACF as ACFSync;
use PolylangSync\Sync\ACFTranslate;

class ACF extends Core\Singleton {

	/**
	 *	Private constructor
	 */
	protected function __construct() {
		ACFSync::instance();
		ACFTranslate::instance();
	}

}
