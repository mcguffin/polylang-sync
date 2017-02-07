<?php

namespace PolylangSync\ACF;
use PolylangSync\Core;

class ACF extends Core\Singleton {
	private $core;

	/**
	 *	Private constructor
	 */
	protected function __construct() {

		$this->core = Core\Core::instance();

		$this->sync 		= Sync::instance();
		$this->translate	= ACFTranslate::instance();
	}




}