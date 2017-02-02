<?php

namespace PolylangSync\Menu;
use PolylangSync\Core;

/**
 *	Provides a Create-Translation-Button for the menu
 */
class Edit extends Core\Singleton {

	private $core;
	
	private $pll_language;

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
		add_action( 'load-nav-menus.php', array( $this, 'load_nav_menus' ) );
		add_action( 'admin_footer-nav-menus.php', array( $this, 'admin_footer' ) );
	}

	/**
	 *	@action load-nav-menus.php
	 */
	function load_nav_menus() {
		if ( current_user_can( 'edit_theme_options' ) && isset( $_GET['action'] ) && $_GET['action'] == 'translate-menu' ) {
			if ( isset( $_GET['id'] ) && ! empty( absint( $_GET['id'] ) ) && 
				isset( $_GET['lang'] ) && term_exists( $_GET['lang'], 'language' ) ) {
				$nav_menu_id		= absint( $_GET['id'] );
				$target_language	= $_GET['lang'];
				if  ( isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'translate-menu-' . $nav_menu_id ) ) {
					// check nonce!!!
					if ( $translated_menu_id = $this->translate_nav_menu( $nav_menu_id, $target_language ) ) {
						$url = add_query_arg( array( 
							'menu'		=> $translated_menu_id,
							'action'	=> 'edit',
						), admin_url( 'nav-menus.php' ) );

						wp_redirect( $url );
					}
					exit();	
				// redirect
				} else {
					exit('Nonce failed');
				}
			}
		}
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
	 *	@param	$nav_menu_id ID of the menu to be translated
	 *	@param	$target_language	Polylang language code
	 *	@return	int ID of the translated nav menu
	 */
	private function translate_nav_menu( $nav_menu_id, $target_language ) {
		
		$menu_map 			= array();

		// setup nav menu
		$nav_menu 			= get_term( $nav_menu_id );
		$new_menu_name 		= $nav_menu->name . sprintf( ' (%s)', $target_language );

		if ( $prev_menu = wp_get_nav_menu_object( $new_menu_name ) ) {
			// delete if previously generated
			wp_delete_nav_menu( $prev_menu->term_id );
		}

		$translated_menu_id = wp_create_nav_menu( $new_menu_name );

		$menu_items 		= wp_get_nav_menu_items( $nav_menu_id );


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
//			$availabe_languages = PLL()->model->get_languages_list();
			?>
				<div id="translate-nav-menu" class="menu-settings">
					<h3><?php _e( 'Translation Tools', 'polylang-menu-translator' ) ?></h3>

					<?php wp_nonce_field( 'translate-menu-' . $nav_menu_selected_id, 'translate-menu[nonce]' ); ?>

					<input type="hidden" name="translate-menu[action]" value="translate-menu" />
					<div class="major-publishing-actions">
						<?php
							wp_dropdown_categories( array(
								'taxonomy'			=> 'language',
								'show_option_none'	=> false,
								'name'				=> 'translate-menu[lang]',
								'value_field'		=> 'slug',
							) );
						?>
						<div class="publishing-action">
							<button class="button-secondary" name="translate-menu[id]" value="<?php echo absint( $nav_menu_selected_id ); ?>"><?php _e( 'Create Translated Menu', 'polylang-menu-translator' ) ?></button>
						</div>
					</div>
					
				</div>
				<script type="text/javascript">
					(function($){
						$('#post-body').append( $('#translate-nav-menu') );

						$(document).on('click','#translate-nav-menu button',function(e) {
							e.preventDefault();
							var data = {};
							$('[name^="translate-menu"]').each(function(i) {
								var key;
								try {
									key = $(this).attr('name').match(/translate-menu\[(\w+)\]/)[1];
									data[ key ] = $(this).val();
								} catch(err){}
							})
							document.location.search = $.param(data);
						});
					})(jQuery);
				</script>
			<?php
		}
	}

}

