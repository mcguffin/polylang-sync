<?php

namespace PolylangSync\Menu;
use PolylangSync\Core;

/**
 *	Provides a Create-Translation-Button for the menu
 */
class Menu extends Core\Singleton {

	private $core;
	
	private $pll_language;
	
	private $unhook_delete_term = false;

	/**
	 *	Private constructor
	 */
	protected function __construct() {

		$this->core = Core\Core::instance();

		add_action( 'admin_init', array( $this , 'admin_init' ) );
	}

	/**
	 *	Admin init
	 *
	 *	@action admin_init
	 */
	function admin_init() {

		add_action( 'admin_footer-nav-menus.php', array( $this, 'admin_footer' ) );

		add_action( 'load-nav-menus.php', array( $this, 'enqueue_assets' ) );

		add_action( 'wp_update_nav_menu', array( $this, 'wp_update_nav_menu' ) );

		add_action( 'pre_delete_term', array( $this, 'pre_delete_term' ), 10, 2 );
	}

	/**
	 *	dd sync option to menu admin
	 *
	 *	@action admin_footer-nav-menus.php
	 */
	function admin_footer() {
		global $nav_menu_selected_id, $add_new_screen, $locations_screen;

		if ( ! $locations_screen && ! $add_new_screen && ! empty( $nav_menu_selected_id ) ) {
			$availabe_languages = pll_the_languages(array(
				'raw'			=> 1,
				'hide_if_empty'	=> 0,
				'echo'			=> 0,
			));
			$selected_menu = absint( $nav_menu_selected_id );
			$sync_menu = get_term_meta( $nav_menu_selected_id, 'polylang_sync_menu', true );
//			$availabe_languages = PLL()->model->get_languages_list();
			?>
				<div id="translate-nav-menu" class="menu-settings">
					<h3><?php _e( 'Polylang Sync', 'polylang-menu-translator' ) ?></h3>
					

					<fieldset class="menu-settings-group sync-menu">
						<legend class="menu-settings-group-name howto"><?php _e( 'Syncronize', 'polylang-sync' ) ?></legend>
						<div class="menu-settings-input checkbox-input">
							<input type="hidden" name="polylang-sync-menu" value="0">
							<input type="checkbox" <?php checked( $sync_menu, true, true ); ?> name="polylang-sync-menu" id="polylang-sync-menu" value="1">
							<label for="polylang-sync-menu"><?php _e( 'Keep this menu synchronized between translations', 'polylang-sync' ) ?></label>
						</div>
					</fieldset>

				</div>
			<?php
		}
	}

	/**
	 * Enqueue options Assets
	 */
	function enqueue_assets() {

		$asset_id	= 'polylang_sync_nav_menus';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$js_src		= 'js/admin/nav-menus.js';
		} else {
			$js_src		= 'js/admin/nav-menus.min.js';
		}

		wp_enqueue_script( $asset_id , $this->core->get_asset_url( $js_src ), array('jquery') );
		wp_localize_script( $asset_id , 'polylang_sync_nav_menus' , array(
		) );
	}
	/**
	 *	Update menu metadata
	 *
	 *	@action pre_delete_term
	 */
	function pre_delete_term( $term_id, $taxonomy ) {

		if ( $taxonomy !== 'nav_menu' || $this->unhook_delete_term ) {
			return;
		}

		$nav_menu_id	= $term_id;

		$this->unhook_delete_term = true;

		if ( isset( $_REQUEST[ 'polylang-sync-menu' ] ) ) {
			// save sync setting accross menus
			$is_synced	= boolval( $_REQUEST[ 'polylang-sync-menu' ] );
		} else {
			$is_synced	= boolval( get_term_meta( $nav_menu_id, 'polylang_sync_menu', true ) );
		}

		if ( $is_synced ) {

			$translation_group = $this->get_menu_translation_group( $nav_menu_id );

			foreach ( $translation_group as $lang_code => $translated_menu_id ) {
				wp_delete_nav_menu( $translated_menu_id );
			}

		}

		$this->unhook_delete_term = false;
	}

	/**
	 *	Update menu metadata
	 *
	 *	@action wp_update_nav_menu
	 */
	function wp_update_nav_menu( $nav_menu_id ) {
		
		// update menu settings
		if ( isset( $_REQUEST[ 'polylang-sync-menu' ] ) ) {
			// save sync setting accross menus
			$do_sync = boolval( $_REQUEST[ 'polylang-sync-menu' ] );
			
			if ( ! $do_sync ) {
				$this->set_nav_menu_sync( $nav_menu_id, false );
			}

		} else {
			// get current sync setting
			$do_sync = boolval( get_term_meta( $nav_menu_id, 'polylang_sync_menu', true ) );
		}

		if ( $do_sync ) {

			$this->sync_nav_menu( $nav_menu_id );

			$this->set_nav_menu_sync( $nav_menu_id, $do_sync );

		}

	}


	//
	// logic starts here
	// 


	/**
	 *	Sync a menu accross translations
	 *
	 *	@param int $nav_menu_id
	 */
	private function set_nav_menu_sync( $nav_menu_id, $is_synced ) {

		$is_synced = boolval( $is_synced );

		$menu_translation_group = $this->get_menu_translation_group( $nav_menu_id );

		foreach ( $menu_translation_group as $lang_code => $translated_menu_id ) {
			update_term_meta( $translated_menu_id, 'polylang_sync_menu', $is_synced );
		}

	}

	/**
	 *	Sync a menu accross translations
	 *
	 *	@param int $nav_menu_id
	 */
	private function sync_nav_menu( $nav_menu_id ) {
		// perform sync

		if ( ! $nav_menu = get_term( $nav_menu_id ) ) {
			return;
		}

		$menu_translation_group = $this->get_menu_translation_group( $nav_menu_id );
		$languages				= $this->core->get_pll_languages();
		$menu_language			= $this->get_menu_language( $nav_menu );


		//
		//	Aggregate Menu translation group
		//
		foreach ( $languages as $lang_code ) {
			if ( $lang_code == $menu_language ) {
				continue;
			}

			if ( isset( $menu_translation_group[ $lang_code ] ) ) {
				// make sure menu exists
				if ( ! $prev_menu = wp_get_nav_menu_object( $menu_translation_group[ $lang_code ] ) ) {
					unset( $menu_translation_group[ $lang_code ] );
				}
			}
			if ( ! isset( $menu_translation_group[ $lang_code ] ) ) {
				// create nav menu
				$new_menu_name 		= $nav_menu->name . sprintf( ' (%s)', $lang_code );
				if ( $prev_menu = wp_get_nav_menu_object( $new_menu_name ) ) {
					// delete if previously generated
					$translated_menu_id = $prev_menu->term_id;
				} else {
					$translated_menu_id = wp_create_nav_menu( $new_menu_name );
//					update_term_meta( $translated_menu_id, 'polylang_sync_menu', true );
				}
				$menu_translation_group[ $lang_code ] = $translated_menu_id;
			}
		}


		$this->update_menu_translation_group( $nav_menu_id, $menu_translation_group );


		//
		//	Menu Items
		//
		$menu_items 	= wp_get_nav_menu_items( $nav_menu->term_id );


		// setup Polylang
		$pll			= PLL();
		$filters		= new \PLL_Frontend_Filters_Links( $pll );

		add_filter( 'locale', array( $this, 'locale' ) );

		load_default_textdomain();

		foreach ( $menu_items as $menu_item ) {

			$menu_item_translation_group = get_post_meta( $menu_item->ID, 'polylang_sync_translation_group', true );

			if ( ! $menu_item_translation_group ) {
				$menu_item_translation_group = array(
					$menu_language	=> $menu_item->ID,
				);
			}
			foreach ( $languages as $lang_code ) {
				$this->pll_language	= $pll->model->get_language( $lang_code );
				$pll->filter_lang	= $this->pll_language;
				$pll->set_current_language();

				if ( $lang_code == $menu_language ) {
					continue;
				}

				// try to get menu item translation
				if ( $translated_menu_item = $this->get_translated_menu_item( $menu_item, $lang_code ) ) {
					// do something...?
					$this->update_menu_item( $menu_item, $lang_code, $translated_menu_item, $menu_translation_group[ $lang_code ] );
				} else {
					$translated_menu_item_id = $this->translate_menu_item( $menu_item, $lang_code, $menu_translation_group[ $lang_code ] );
					$translated_menu_item = get_post( $translated_menu_item_id );
				}

				$menu_item_translation_group[ $lang_code ] = $translated_menu_item->ID;

			}

			$this->update_menu_item_translation_group( $menu_item->ID, $menu_item_translation_group );

		}
		remove_filter( 'locale', array( $this, 'locale' ) );
	}
	
	
	private function get_translated_menu_item( $menu_item, $lang_code ) {

		if ( $translation_group = get_post_meta( $menu_item->ID, 'polylang_sync_translation_group', true ) ) {
			if ( isset( $translation_group[ $lang_code ] ) ) {
				if ( $post = get_post( $translation_group[ $lang_code ] ) ) {
					return wp_setup_nav_menu_item( $post );
				}
			}
		}
		return false;
	}
	
	private function update_menu_item( $menu_item, $lang_code, $translated_menu_item, $translated_menu_id ) {
		if ( $parent_menu_item = get_post( $menu_item->menu_item_parent ) ) {
			if ( $new_parent = $this->get_translated_menu_item( $parent_menu_item, $lang_code ) ) {
				$new_parent_id = $new_parent->ID;
			}
		}
		if ( ! isset( $new_parent_id ) ) {
			$new_parent_id = 0;
		};
		$new_menu_item_data = array(
			'menu-item-object-id'	=> $translated_menu_item->object_id,
			'menu-item-object' 		=> $translated_menu_item->object,
			'menu-item-type' 		=> $translated_menu_item->type,
			'menu-item-title' 		=> $translated_menu_item->title,
			'menu-item-description'	=> $translated_menu_item->description,
			'menu-item-attr-title'	=> $translated_menu_item->attr_title,

			'menu-item-position' 	=> $menu_item->menu_order,
			'menu-item-target'		=> $menu_item->target,
			'menu-item-parent-id' 	=> $new_parent_id,
			'menu-item-xfn'			=> $menu_item->xfn,
			'menu-item-classes'		=> trim( implode( ' ', $menu_item->classes ) ),

			'menu-item-status'		=> 'publish',
		);
		return wp_update_nav_menu_item( $translated_menu_id, $translated_menu_item->ID, $new_menu_item_data );	
	}
	
	/**
	 *	@param	$menu_item	WP_Post 
	 *	@param	$target_language	String
	 *	@param	$translated_menu_id	int	Menu ID
	 *
	 *	@return object WP_Post
	 */
	private function translate_menu_item( $menu_item, $target_language, $translated_menu_id ) {

		$new_menu_item_data = array(
			'menu-item-object-id'	=> $menu_item->object_id,
			'menu-item-object' 		=> $menu_item->object,
			'menu-item-parent-id' 	=> $menu_item->menu_item_parent,
			'menu-item-position' 	=> $menu_item->menu_order,
			'menu-item-type' 		=> $menu_item->type,
			'menu-item-title' 		=> $menu_item->title,
			'menu-item-description'	=> $menu_item->description,
			'menu-item-attr-title'	=> $menu_item->attr_title,
			'menu-item-target'		=> $menu_item->target,
			'menu-item-classes'		=> trim( implode( ' ', $menu_item->classes ) ),
			'menu-item-xfn'			=> $menu_item->xfn,
			'menu-item-status'		=> 'publish',
		);

		switch( $menu_item->type ) {
			case 'post_type':

				if ( pll_is_translated_post_type( $menu_item->object ) ) {

					$post				= get_post( $menu_item->object_id );

					$translated_post_id	= pll_get_post( $menu_item->object_id, $target_language );

					$translated_post	= get_post( $translated_post_id );

					if ( $translated_post ) {
						$new_menu_item_data['menu-item-object-id'] = absint( $translated_post_id );
						if ( $menu_item->title == $post->post_title ) {
							$new_menu_item_data['menu-item-title'] = $translated_post->post_title;
						}
					}
				}
				break;

			case 'custom':
				$new_menu_item_data['menu-item-url'] = $menu_item->url;
				break;

			case 'post_type_archive':
//				var_dump($menu_item);
/*
				if ( pll_is_translated_post_type( $menu_item->object ) ) {
					// humm.
					$new_url = PLL()->links_model->add_language_to_link( $link, $target_language );
				}
*/
				break;

			case 'taxonomy':
				if ( pll_is_translated_taxonomy( $menu_item->object ) ) {

					$term				= get_term( $menu_item->object_id );

					$translated_term_id	= pll_get_term( $menu_item->object_id, $target_language );

					if ( $translated_term_id ) {
						$translated_term	= get_term( $translated_term_id );
						$new_menu_item_data['menu-item-object-id'] = absint( $translated_term_id );
						if ( $menu_item->title == $term->name ) {
							$new_menu_item_data['menu-item-title'] = $translated_term->name;
						}
					}
				} else if ( $menu_item->object == 'post_format' ) {
					$pf_strings_trans	= get_post_format_strings();
					$term				= get_term( $menu_item->object_id );
					$post_format		= str_replace('post-format-', '', $term->slug );
					if ( $menu_item->title == $pf_strings_orig[ $post_format ] ) {
						$new_menu_item_data['menu-item-title'] = $pf_strings_trans[ $post_format ];
					}
				}
				break;
		}

		if ( $menu_item->menu_item_parent && isset( $menu_map[ absint( $menu_item->menu_item_parent ) ] ) ) {
			$new_menu_item_data[ 'menu-item-parent-id' ] = $menu_map[ absint( $menu_item->menu_item_parent ) ];
		}

		return wp_update_nav_menu_item( $translated_menu_id, $menu_item->id, $new_menu_item_data );	
	}

	/**
	 *	@filter locale
	 */
	public function locale( $locale ) {
		if ( ! is_null( $this->pll_language ) ) {
			return $this->pll_language->locale;
		}
		return $locale;
	}


	/**
	 *	Translate a nav Menu
	 *
	 *	@param	$nav_menu_id ID of the menu to be translated
	 *	@param	$target_language	Polylang language code
	 *
	 *	@return	int ID of the translated nav menu
	 */
	private function translate_nav_menu( $nav_menu, $target_language ) {
		
		$menu_map 			= array();

		// setup nav menu
//		$menu_translations = get_term_meta( $menu_id, 'pll_sync_translation_group' );
		$menu_translation_group = $this->get_menu_translation_group( $nav_menu_id );

		if ( ! isset( $menu_translation_group[ $target_language ] ) ) {
			// create nav menu
			$new_menu_name 		= $nav_menu->name . sprintf( ' (%s)', $target_language );
			if ( $prev_menu = wp_get_nav_menu_object( $new_menu_name ) ) {
				// delete if previously generated
				wp_delete_nav_menu( $prev_menu->term_id );
			}
			$translated_menu_id = wp_create_nav_menu( $new_menu_name );
		} else {
			$translated_menu_id = $menu_translation_group[ $target_language ];
		}

		update_term_meta( $translated_menu_id, 'polylang_sync_menu', true );

		$menu_items 		= wp_get_nav_menu_items( $nav_menu->term_id );


		// setup Polylang
		$pll				= PLL();
		$this->pll_language	= $pll->model->get_language( $target_language );
		$pll->filter_lang	= $this->pll_language;

		$pll->set_current_language();

		$filters			= new \PLL_Frontend_Filters_Links( $pll );
		$pf_strings_orig	= get_post_format_strings();

		add_filter( 'locale', array( $this, 'locale' ) );

		load_default_textdomain();

		$pf_strings_trans	= get_post_format_strings();

		foreach ( $menu_items as $menu_item ) {

			$new_menu_item_data = array(
				'menu-item-object-id'	=> $menu_item->object_id,
				'menu-item-object' 		=> $menu_item->object,
				'menu-item-parent-id' 	=> $menu_item->menu_item_parent,
				'menu-item-position' 	=> $menu_item->menu_order,
				'menu-item-type' 		=> $menu_item->type,
				'menu-item-title' 		=> $menu_item->title,
				'menu-item-description'	=> $menu_item->description,
				'menu-item-attr-title'	=> $menu_item->attr_title,
				'menu-item-target'		=> $menu_item->target,
				'menu-item-classes'		=> trim( implode( ' ', $menu_item->classes ) ),
				'menu-item-xfn'			=> $menu_item->xfn,
				'menu-item-status'		=> 'publish',
			);

			switch( $menu_item->type ) {
				case 'post_type':

					if ( pll_is_translated_post_type( $menu_item->object ) ) {

						$post				= get_post( $menu_item->object_id );

						$translated_post_id	= pll_get_post( $menu_item->object_id, $target_language );

						$translated_post	= get_post( $translated_post_id );

						if ( $translated_post ) {
							$new_menu_item_data['menu-item-object-id'] = absint( $translated_post_id );
							if ( $menu_item->title == $post->post_title ) {
								$new_menu_item_data['menu-item-title'] = $translated_post->post_title;
							}
						}
					}
					break;

				case 'custom':
					$new_menu_item_data['menu-item-url'] = $menu_item->url;
					break;

				case 'post_type_archive':
/*
					if ( pll_is_translated_post_type( $menu_item->object ) ) {
						// humm.
						$new_url = PLL()->links_model->add_language_to_link( $link, $target_language );
					}
*/
					break;

				case 'taxonomy':
					if ( pll_is_translated_taxonomy( $menu_item->object ) ) {

						$term				= get_term( $menu_item->object_id );

						$translated_term_id	= pll_get_term( $menu_item->object_id, $target_language );

						if ( $translated_term_id ) {
							$translated_term	= get_term( $translated_term_id );
							$new_menu_item_data['menu-item-object-id'] = absint( $translated_term_id );
							if ( $menu_item->title == $term->name ) {
								$new_menu_item_data['menu-item-title'] = $translated_term->name;
							}
						}
					} else if ( $menu_item->object == 'post_format' ) {
						$term			= get_term( $menu_item->object_id );
						$post_format	= str_replace('post-format-', '', $term->slug );
						if ( $menu_item->title == $pf_strings_orig[ $post_format ] ) {
							$new_menu_item_data['menu-item-title'] = $pf_strings_trans[ $post_format ];
						}
					}
					break;
			}

			if ( $menu_item->menu_item_parent && isset( $menu_map[ absint( $menu_item->menu_item_parent ) ] ) ) {
				$new_menu_item_data[ 'menu-item-parent-id' ] = $menu_map[ absint( $menu_item->menu_item_parent ) ];
			}

			$menu_map[ absint( $menu_item->ID ) ] = wp_update_nav_menu_item( $translated_menu_id, 0, $new_menu_item_data );

		}
		return $translated_menu_id;
	}

	/**
	 *	Get menu Translation group. 
	 *	First tries to get it from term_meta
	 *	Then tries to get
	 *
	 *	@param	int	$nav_menu_id
	 *
	 *	@return array	menu translations array( 'de' => 123, 'en' => 456, ... )
	 */
	private function get_menu_translation_group( $nav_menu_id ) {

		$meta_key_name = 'polylang_sync_translation_group';

		if ( $menu_translation_group = get_term_meta( $nav_menu_id, $meta_key_name, true ) ) {
			return $menu_translation_group;
		}

		if ( ! $menu_translation_group ) {

			$menu_translation_group = array();

			foreach ( PLL()->options['nav_menus'] as $theme_slug => $pll_menus ) {

				foreach ( $pll_menus as $menu_position => $menu_translations ) {

					if ( in_array( $nav_menu_id, $menu_translations ) ) {

						return $menu_translations;

					}

				}

			}

		}
		return false;
	}

	private function update_menu_item_translation_group( $menu_item_id, $menu_item_translation_group ) {

		$meta_key_name = 'polylang_sync_translation_group';

		foreach ( $menu_item_translation_group as $lang_code => $translated_menu_item_id ) {
			update_post_meta( $translated_menu_item_id, $meta_key_name, $menu_item_translation_group );
		}
		
	}

	private function update_menu_translation_group( $nav_menu_id, $menu_translation_group ) {

		$meta_key_name = 'polylang_sync_translation_group';


		// update translations
		foreach ( $menu_translation_group as $lang_code => $menu_id ) {

			update_term_meta( $menu_id, $meta_key_name, $menu_translation_group );

		}

		// assign generated menus to theme locations
		$pll_menu_locations	= array();

		$menu_locations		= $this->get_menu_locations( $nav_menu_id );

		foreach ( $menu_locations as $location ) {
			foreach ( $menu_translation_group as $lang_code => $menu_id ) {
				$lang = PLL()->model->get_language( $lang_code );
				$pll_menu_locations[ PLL()->nav_menu->combine_location( $location, $lang ) ] = $menu_id;
				update_term_meta( $menu_id, $meta_key_name, $menu_translation_group );
			}
		}

		if ( count( $pll_menu_locations ) ) {
			PLL()->nav_menu->update_nav_menu_locations( $pll_menu_locations );
		}

		return true;
	}


	/**
	 *	Get menu Language.
	 *	Returns language code of first translatable menu item
	 *
	 *	@param	int	$nav_menu_id
	 *
	 *	@return array	menu locations
	 */
	private function get_menu_locations( $nav_menu_id ) {
		$locations = get_theme_mod('nav_menu_locations');
		$found_locations = array();
		foreach ( $locations as $location => $menu_id ) {
			if ( $menu_id == $nav_menu_id ) {
				$found_locations[] = $location;
			}
		}
		return $found_locations;
	}
	
	/**
	 *	Get menu Language.
	 *	Returns language code of first translatable menu item
	 *
	 *	@param	WP_Term	$nav_menu
	 *
	 *	@return bool|string	language code
	 */
	private function get_menu_language( $nav_menu ) {
		// look if polylang knows the language
		foreach ( PLL()->options['nav_menus'] as $theme_slug => $pll_menus ) {
			foreach ( $pll_menus as $menu_position => $menu_translations ) {
				foreach ( $menu_translations as $lang_code => $nav_menu_id ) {
					if ( $nav_menu_id == $nav_menu->term_id ) {
						return $lang_code;
					}
				}
			}
		}

		// try to get menu language from translation group
		// ...

		// fallback: look if there is a translated menu item
		$menu_items = wp_get_nav_menu_items( $nav_menu->term_id );

		foreach ( $menu_items as $menu_item ) {
			switch( $menu_item->type ) {
				case 'post_type':
					if ( pll_is_translated_post_type( $menu_item->object ) ) {
						return pll_get_post_language( $menu_item->object_id );
					}
					break;
				case 'taxonomy':
					if ( pll_is_translated_taxonomy( $menu_item->object ) ) {
						return pll_get_post_language( $menu_item->object_id );
					}
					break;
			}
		}

		// last resort: we don't know
		return false;
	}





}

