<?php

namespace PolylangSync\Admin;
use PolylangSync\Core;
use PolylangSync\ACF;
use PolylangSync\Menu;


class Admin extends Core\Singleton {

	private $core;

	/**
	 *	Private constructor
	 */
	protected function __construct() {

		$this->core = Core\Core::instance();

		add_action( 'admin_init' , array( $this, 'admin_init' ) );
		add_action( 'after_setup_theme' , array( $this , 'setup' ) );
	}

	/**
	 * @action admin_notices
	 */
	function print_acf_free_notice() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php
				printf(
					_x( 'The Polylang-Sync Plugin plugin requires <a href="%2$s">Polylang</a> to be installed and activated.',
						'1: ACF Pro URL, 2: Polylang URL',
						'acf-quick-edit-fields'
					),
					'http://www.advancedcustomfields.com/pro/',
					'https://wordpress.org/plugins-wp/polylang/'
				);
			?></p>
		</div>
		<?php
	}


	/**
	 * Admin init
	 */
	function setup() {
		if ( function_exists( 'PLL' ) ) {

			$this->menu = Menu\Menu::instance();

			if ( class_exists( 'acf' ) && function_exists( 'acf_get_field_groups' ) ) {
				//$this->acf		= ACF\ACF::instance();
			}

		} else if ( class_exists( 'acf' ) && current_user_can( 'activate_plugins' ) ) {
		//	add_action( 'admin_notices', array( $this, 'print_acf_free_notice' ) );
		}
	}


	/**
	 * @action admin_init
	 */
	function admin_init() {
	}

	/**
	 * Enqueue options Assets
	 */
	function enqueue_assets() {
		wp_enqueue_style( 'polylang_sync-admin' , $this->core->get_asset_url( '/css/admin.css' ) );

		wp_enqueue_script( 'polylang_sync-admin' , $this->core->get_asset_url( 'js/admin.js' ) );
		wp_localize_script('polylang_sync-admin' , 'polylang_sync_admin' , array(
		) );
	}

}
