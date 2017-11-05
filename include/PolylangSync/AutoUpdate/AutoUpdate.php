<?php

namespace PolylangSync\AutoUpdate;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}


use PolylangSync\Core;

abstract class AutoUpdate extends Core\Singleton {

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'pre_set_transient' ), 10, 3 );

		add_filter( 'upgrader_source_selection', array( $this, 'source_selection' ), 10, 4 );

	}

	/**
	 *	Will make sure that the downloaded directory name and our plugins dirname are the same.
	 *	@filter upgrader_source_selection
	 */
	public function source_selection( $source, $remote_source, $wp_upgrader, $hook_extra ) {
		if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === plugin_basename( POLYLANG_SYNC_FILE ) ) {
			// $source: filepath
			// $remote_source download dir
			$source_dirname = pathinfo( $source, PATHINFO_FILENAME);
			$plugin_dirname = pathinfo( $hook_extra['plugin'], PATHINFO_DIRNAME );

			if ( $source_dirname !== $plugin_dirname ) {
				$new_source = $remote_source . '/' . $plugin_dirname;
				rename( $source, $new_source );
				$source = $new_source;
			}

		}
		return $source;
	}

	/**
	 *	@action admin_init
	 */
	public function admin_init() {

		//$this->pre_set_transient( get_site_transient('update_plugins') );
	}

	/**
	 *	Preprocess download.
	 *	Should return false if nothing shall happen
	 *
	 *	@filter upgrader_pre_download
	 */
	public function preprocess_download( $return, $package, $wp_upgrader ) {
		return $return;
	}

	/**
	 *	@filter	pre_set_site_transient_update_plugins
	 */
	public function pre_set_transient( $transient ) {

		if ( ! is_object( $transient ) || ! isset( $transient->response ) ) {
			return $transient;
		}

		// get own version
		if ( $release_info = $this->get_release_info() ) {
			$plugin 		= plugin_basename( POLYLANG_SYNC_FILE );
			$slug			= basename( POLYLANG_SYNC_DIRECTORY );
			$plugin_info	= get_plugin_data( POLYLANG_SYNC_FILE );

			if ( version_compare( $release_info['version'], $plugin_info['Version'] , '>' ) ) {
				$transient->response[ $plugin ] = (object) array(
					'id'			=> $release_info['id'],
					'slug'			=> $slug,
					'plugin'		=> $plugin,
					'new_version'	=> $release_info['version'],
					'url'			=> $plugin_info['PluginURI'],
					'package'		=> $release_info['download_url'],
					'icons'			=> array(),
					'banners'		=> array(),
					'banners_rtl'	=> array(),
					//'tested'		=> '',
					'compatibility'	=> array(),
				);
				if ( isset( $transient->no_update ) && isset( $transient->no_update[$plugin] ) ) {
					unset( $transient->no_update[$plugin] );
				}
			}
		}

		return $transient;
	}

	/**
	 *	Should return info for current release
	 *
	 *	@return array(
	 *		'id'			=> '...'
	 *		'version'		=> '...'
	 *		'download_url'	=> 'https://...'
	 *	)
	 */
	abstract function get_release_info();


}
