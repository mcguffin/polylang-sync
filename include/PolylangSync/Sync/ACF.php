<?php

namespace PolylangSync\Sync;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use PolylangSync\Core;


class ACF extends Core\Singleton {

	private $core;

	private $sync_acf_fields = null;

	private $all_fields = null;

	private $in_sub_field_loop = false;

	/**
	 *	Private constructor
	 */
	protected function __construct() {

		$this->core	= Core\Core::instance();

		add_action( 'init' , array( $this, 'init' ) );

//		add_action( 'save_post' ,  array( &$this , 'save_post' ) , 20 , 3 );
		add_action( 'pll_save_post' ,  array( $this, 'pll_save_post' ) , 20 , 3 );

		foreach ( $this->get_supported_fields() as $type ) {
			add_action( "acf/render_field_settings/type={$type}" , array( $this, 'render_acf_settings' ) );
		}
	}

	/**
	 *	Get Supported ACF fields
	 *
	 *	@return array
	 */
	public function get_supported_fields() {
		return apply_filters( 'polylang_acf_sync_supported_fields', array(
			// basic
			'text',
			'textarea',
			'number',
			'email',
			'url',
			'password',
			'range',

			// Content
			'wysiwyg',
			'oembed',
			'image',
			'file',
			'gallery',

			// Choice
			'select',
			'checkbox',
			'radio',
			'true_false',
			'button_group',

			// relational
			'post_object',
			'page_link',
			'relationship',
			'taxonomy', // will be synced by polylang
			'user',

			// jQuery
			'google_map',
			'date_picker',
			'date_time_picker',
			'time_picker',
			'color_picker',

			// layout
			'repeater',
            'group'
		));
	}

	/**
	 *	Add sync field setting
	 *
	 *	@action acf/render_field_settings/type={$type}
	 */
	public function render_acf_settings( $field ) {

		if ( $this->is_sub_field( $field ) ) {
			return;
		}

		$instructions = '';

		if ( $field['type'] === 'taxonomy' ) {
			/*
			Polylang-Sync AN:

			Polylang-Sync AUS:

			*/
			$instructions = __( 'Enabling this field only makes sense if...', 'polylang-sync' );
		}

		// show column: todo: allow sortable
		acf_render_field_setting( $field, array(
			'label'			=> __( 'Synchronize', 'polylang-sync' ),
			'instructions'	=> '',
			'type'			=> 'true_false',
			'name'			=> 'polylang_sync',
			'message'		=> __( 'Synchronize this field between translations', 'polylang-sync' ),
			'width'			=> 50,
			'ui'			=> true,
		));
	}

	public function all_fields() {
		if ( is_null( $this->all_fields ) ) {
			$this->all_fields = array();
			$groups = acf_get_field_groups();
			foreach ( $groups as $group ) {
				$this->add_sub_fields( acf_get_fields( $group['key'] ) );
			}
		}
		return $this->all_fields;
	}
	private function add_sub_fields( $sub_fields ) {
		foreach ( $sub_fields as $field ) {
			$this->all_fields[] = $field;
			if ( $field['type'] === 'group' ) {
				$this->add_sub_fields($field['sub_fields']);
			} else if ( $field['type'] === 'repeater' ) {
				$this->add_sub_fields($field['sub_fields']);
			} else if ( $field['type'] === 'flexible_content' ) {
				// not supported yet....
				//vaR_dump($field);
			}
		}

	}

	/**
	 *	@action init
	 */
	public function init() {

		// get top level fields to sync
		$this->sync_acf_fields = array();

		$all_acf_fields = $this->all_fields();

		foreach( $all_acf_fields as $field ) {

			if ( isset( $field['polylang_sync'] ) && $field['polylang_sync'] && ! $this->is_sub_field( $field ) ) {
				add_filter( "acf/prepare_field/key={$field['key']}", array( $this, 'prepare_field' ) );
				$this->sync_acf_fields[] = $field;
			}
		}

	}
	public function is_sub_field( $field ) {
		return !$field['parent'];
	}

	/**
	 *	Display translated value of field
	 *
	 *	@filter  "acf/prepare_field/key={$field['key']}"
	 */
	public function prepare_field( $field ) {
		$field['wrapper']['class'] .= ' pll-sync';
		return $field;
	}
	/**
	 *	@action pll_save_post
	 */
	public function pll_save_post( $source_post_id, $source_post, $translation_group ) {
		do_action( 'pll_sync_begin_sync_acf' );
		$this->update_fields( $this->sync_acf_fields, $source_post_id, $translation_group );
		do_action( 'pll_sync_end_sync_acf' );
	}


	/**
	 *	@param	array	$fields				ACF Field objects
	 *	@param	int		$source_post_id		ACF Field objects
	 *	@param	array	$translation_group	PLL translation group
	 */
	private function update_fields( $fields, $source_post_id, $translation_group ) {
		foreach ( $fields as $synced_field ) {

			$field_object = get_field_object( $synced_field['key'], $source_post_id, false, true );

			switch ( $synced_field['type'] ) {
				case 'image':
				case 'file':
					// we need to get a post object for url forrmated uploads!
					if ( $field_object['return_format'] == 'url' ) {
						$media_id	= get_field( $synced_field['key'], $source_post_id, false );
						$media		= get_post( $media_id );
						$field_object['value'] = $media;
					}
					if ( PLL()->options['media_support'] ) {
						$this->update_upload( $field_object, $translation_group );
					} else {
						$this->update_field_value( $field_object, $translation_group );
					}

					break;

				case 'gallery':
					if ( PLL()->options['media_support'] ) {
						$this->update_gallery( $field_object, $translation_group );
					} else {
						$this->update_field_value( $field_object, $translation_group );
					}
					break;

				case 'relationship':
					$this->update_relationship( $field_object, $translation_group );
					break;

				case 'flexible_content':
				case 'repeater':
					$this->update_repeater( $field_object, $translation_group, $source_post_id );
					break;

				case 'taxonomy': // will be synced by polylang
					// if translated, find translate
					break;

				// basic
				case 'text':
				case 'textarea':
				case 'number':
				case 'email':
				case 'url':
				case 'password':
				case 'wysiwyg':
				case 'oembed':
				case 'select':
				case 'checkbox':
				case 'radio':
				case 'true_false':
				case 'user':
				case 'date_picker':
				case 'date_time_picker':
				case 'time_picker':
				case 'color_picker':
				case 'google_map':
				case 'post_object':
				case 'page_link':
				default:
					// just copy over the value
					$this->update_field_value( $field_object, $translation_group );
					break;

			}
		}
	}

	/**
	 *	Called when a repeater field is updated.
	 *
	 *	@param	array 	$field_object		ACF Field object
	 *	@param	array 	$translation_group	PLL Translation group
	 *	@param	int		$source_post_id
	 */
	private function update_repeater( $field_object, $translation_group, $source_post_id ) {
		$values = [];
		if ( have_rows( $field_object['name'] ) ) {
			while ( have_rows( $field_object['name'] ) ) {
				the_row();
				$values[ get_row_index() ] = get_row( false );
			}
		}

		foreach ( $translation_group as $lang_code => $post_id ) {
			$translated_values = array_merge( $values, [] );
			foreach ( $translated_values as $field_key => $value ) {
				foreach ( $field_object['sub_fields'] as $sub_field ) {
					switch ( $sub_field['type'] ) {
						case 'image':
						case 'file':
							if ( PLL()->options['media_support'] ) {
								$value = $this->get_translated_media( $value, $lang_code, [] );
							}
							break;
						case 'relationship':
							$post = get_post( $value );
							if ( pll_is_translated_post_type( $post->post_type ) ) {
								$translated_post = get_translated_post( $post_id, $lang_code );
							}
							if ( $translated_post ) {
								$value = $translated_post->ID;
							}
							break;
						case 'gallery':
							if ( PLL()->options['media_support'] ) {
							//	$value = $this->get_translated_media( $value, $lang_code, [] );
							}

							break;
						case 'taxonomy':
							break;
						case 'text':
						case 'textarea':
							$value = pll_translate_string( $value, $lang_code );
							break;
					}
					// $value = apply_filters( "acf/update_value/type={$sub_field['type']}", $value, $post_id, $sub_field  );
					// $value = apply_filters( "acf/update_value/key={$sub_field['key']}",   $value, $post_id, $sub_field  );
					// $value = apply_filters( "acf/update_value",                           $value, $post_id, $sub_field  );
				}
				$translated_values[ $field_key ] = $value;
			}

			$res = $this->update_field( $field_object['key'], $translated_values, $post_id );
		}

	}

	/**
	 *	Called when any field is updated.
	 *
	 *	@param	array 	$field_object		ACF Field object
	 *	@param	array 	$translation_group	PLL Translation group
	 */
	private function update_field_value( $field_object, $translation_group ) {
		foreach ( $translation_group as $lang_code => $post_id ) {
			$selector = $field_object['key'];
			$this->update_field( $selector, $field_object['value'], $post_id );
		}
	}

	/**
	 *	Called when a relationship field is updated.
	 *
	 *	@param	array 	$field_object		ACF Field object
	 *	@param	array 	$translation_group	PLL Translation group
	 */
	private function update_relationship( $field_object, $translation_group ) {

		$posts				= $field_object['value'];
		$translated_posts	= array();

		foreach ( $translation_group as $lang_code => $post_id ) {
			if ( $posts ) {
				foreach ( $posts as $i => $post ) {
					if ( pll_is_translated_post_type( $post->post_type ) && $translated_post_id = pll_get_post( $post->ID, $lang_code ) ) {
						$translated_posts[$i] = get_post( $translated_post_id );
						unset( $translated_post_id );
					} else {
						$translated_posts[$i] = $post;
					}
				}
				$field_object['value'] = $translated_posts;
			}
			$this->update_field( $field_object['key'], $field_object['value'], $post_id );

		}
	}

	/**
	 *	Called when a file or image field is updated.
	 *
	 *	@param	array 	$field_object		ACF Field object
	 *	@param	array 	$translation_group	PLL Translation group
	 */
	private function update_upload( $field_object, $translation_group ) {

		$media_obj = (object) $field_object['value'];
		$source_lang = pll_get_post_language( $media_obj->ID, 'slug' );
		$media_translation_group = array( $source_lang => $media_obj->ID );

		foreach ( $translation_group as $lang_code => $post_id ) {
			$field_object["value"] = $this->get_translated_media( $field_object["value"], $lang_code, $media_translation_group );
			$this->update_field( $field_object['key'], $field_object['value'], $post_id );
		}

		pll_save_post_translations( $media_translation_group );
	}

	/**
	 *	Get a posts translation
	 *
	 *	@param	int 		$post_id
	 *	@param	string 		$lang_code
	 *	@return	bool|object	WP_Post or false
	 */
	private function get_translated_post( $post_id, $lang_code ) {
		if ( $translated_post_id = pll_get_post( $post_id, $lang_code ) ) {
			return get_post( $translated_post_id );
		}
		return false;
	}

	/**
	 *	@param	WP_Post|array|int 	$media
	 *	@param	string 			$lang_code
	 *	@param	array 			$translation_group
	 *	@return	object	WP_Post
	 */
	private function get_translated_media( $media, $lang_code, &$media_translation_group ) {

		// make sure $media is a WP_Post
		$media = get_post( is_numeric( $media ) ? intval( $media ) : ( is_array($media) ? $media['ID'] : $media->ID ) );

		// found translation?
		if ( $translated_media_id = pll_get_post( $media->ID, $lang_code ) ) {
			if ( $translated_media = get_post( $translated_media_id ) ) {

				$media_translation_group[ $lang_code ] = $translated_media_id;

				return $translated_media;
			}
		}

		$lang = PLL()->model->get_language($lang_code);

		// make new translation
		$post_arr = get_object_vars( $media );
		$post_arr['ID'] = 0;
		$post_arr['comment_count']	= 0;
		$post_arr['post_status']	= $post_arr['post_status'];
		$post_arr['post_parent'] 	= pll_get_post( $media->post_parent, $lang_code );
		$post_arr['post_title'] 	.= sprintf( ' (%s)' , $lang->slug );

		if ( $translated_media_id = wp_insert_post( $post_arr ) ) {

			pll_set_post_language( $translated_media_id, $lang_code );

			$ignore_meta_keys = array( '_edit_lock' , '_edit_last' );

			$meta = get_post_meta( $media->ID );

			foreach ( $meta as $meta_key => $values ) {

				if ( in_array( $meta_key , $ignore_meta_keys ) ) {
					continue;
				}

				foreach ( $values as $value ) {
					update_post_meta( $translated_media_id , $meta_key , maybe_unserialize( $value ) );
				}
			}

			$media_translation_group[ $lang_code ] = $translated_media_id;

			$translated_media = get_post( $translated_media_id );
			pll_save_post_translations( $media_translation_group );

			if ( $translated_media ) {
				return $translated_media;
			}
		}

		// fallback
		return $media;
	}

	/**
	 *	@param	array	$field_object
	 *	@param	array	$translation_group
	 */
	private function update_gallery( $field_object, $translation_group ) {

		$media_translation_groups = array();

		foreach ( $translation_group as $lang_code => $post_id ) {

			$gallery = false;

			if ( $field_object["value"] ) { // array containing the image IDs

				$gallery = array();

				foreach ( $field_object["value"] as $i => $image_id ) {

					$source_lang = pll_get_post_language( $image_id, 'slug' );

					$media_translation_groups[$i] = array( $source_lang => $image_id );

					$gallery[$i] = $this->get_translated_media( $image_id, $lang_code, $media_translation_groups[$i] )->ID;

				}

			}

			$field_object["value"] = $gallery;

			$this->update_field( $field_object['key'], $field_object['value'], $post_id );
		}
	}

	/**
	 *	Wrapper around acf update_field()
	 *
	 *	@param	string	$selector
	 *	@param	mixed	$value
	 *	@param	int		$post_id
	 */
	private function update_field( $selector, $value, $post_id ) {
		return update_field( $selector, $value, $post_id );
	}




}
