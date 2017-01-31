<?php

namespace PolylangSync\ACF;
use PolylangSync\Core;


class Sync extends Core\Singleton {

	private $core;

	private $sync_scf_fields;

	private $in_sub_field_loop = false;

	/**
	 *	Private constructor
	 */
	protected function __construct() {

		$this->core	= Core\Core::instance();
		$this->acf	= ACF::instance();

		add_action( 'init' , array( &$this , 'init' ) );

//		add_action( 'save_post' ,  array( &$this , 'save_post' ) , 20 , 3 );
		add_action( 'pll_save_post' ,  array( &$this , 'pll_save_post' ) , 20 , 3 );

	}

	/**
	 *	@action init
	 */
	public function init() {

		// get top level fields to sync
		$this->sync_scf_fields = array();

		$all_acf_fields = get_posts(array(
			'post_type' => 'acf-field',
			'posts_per_page' => -1,
		));

		foreach( $all_acf_fields as $post ) {
			if ( ! $this->is_repeater_child( $post ) ) {

				$field	= get_field_object( $post->post_name );

				if ( isset( $field['polylang_sync'] ) && $field['polylang_sync'] ) {
					$this->sync_scf_fields[] = $field;
				}
			}
		}
		
	}


	/**
	 *	@action pll_save_post
	 */
	public function pll_save_post( $source_post_id, $source_post, $translation_group ) {
		$this->update_fields( $this->sync_scf_fields, $source_post_id, $translation_group );
		exit();
	}
	
	
	private function update_fields( $fields, $source_post_id, $translation_group ) {
		foreach ( $fields as $synced_field ) {
		
			if ( $this->in_sub_field_loop ) {
				$field_object = get_sub_field_object( $synced_field['key'] );
			} else {
				$field_object = get_field_object( $synced_field['key'], $source_post_id );
			}

			switch ( $synced_field['type'] ) {
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
					// just copy over the value
					$this->update_field_value( $field_object, $translation_group );
					break;

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

				case 'post_object':
				case 'page_link':
					$this->update_field_value( $field_object, $translation_group );
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
			}
		}
	}
	
	private function update_repeater( $field_object, $translation_group, $source_post_id ) {

		$sync_repeater_fields = array();

		$this->in_sub_field_loop = true;

		while ( have_rows( $field_object['key'] ) ) {
			the_row();
			foreach ( $field_object['sub_fields'] as $sub_field ) {
				if ( isset( $sub_field['polylang_sync'] ) && $sub_field['polylang_sync'] ) {
					$sync_repeater_fields[] = $sub_field;
				}
			}
			if ( ! empty( $sync_repeater_fields ) ) {
				$this->update_fields( $sync_repeater_fields, $source_post_id, $translation_group );
			}
		}

		$this->in_sub_field_loop = false;
		
	}

	private function update_field_value( $field_object, $translation_group ) {
		foreach ( $translation_group as $lang_code => $post_id ) {
			$this->update_field( $field_object['key'], $field_object['value'], $post_id );
		}
	}


	private function update_relationship( $field_object, $translation_group ) {

		$posts				= $field_object['value'];

		$translated_posts	= array();

		foreach ( $translation_group as $lang_code => $post_id ) {
			foreach ( $posts as $i => $post ) {
				if ( pll_is_translated_post_type( $post->post_type ) && $translated_post_id = pll_get_post( $post->ID, $lang_code ) ) {
					$translated_posts[$i] = get_post( $translated_post_id );
					unset( $translated_post_id );
				} else {
					$translated_posts[$i] = $post;
				}
			}
			$field_object['value'] = $translated_posts;

			$this->update_field( $field_object['key'], $field_object['value'], $post_id );

		}
	}
	
	
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
	 *	@return	object	WP_Post
	 */
	private function get_translated_media( $media, $lang_code, &$translation_group ) {

		$media = get_post( is_array($media) ? $media['ID'] : $media->ID );
		

		// found translation?
		if ( $translated_media_id = pll_get_post( $media->ID, $lang_code ) ) {
			if ( $translated_media = get_post( $translated_media_id ) ) {
			
				$translation_group[ $lang_code ] = $translated_media_id;

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
				if ( in_array( $meta_key , $ignore_meta_keys ) )
					continue;
				foreach ( $values as $value ) {
					update_post_meta( $translated_media_id , $meta_key , maybe_unserialize( $value ) );
				}
			}
			
			$translation_group[ $lang_code ] = $translated_media_id;

			$translated_media = get_post( $translated_media_id );

			if ( $translated_media ) {
				return $translated_media;
			}
		}

		// fallback
		return $media;
	}

	private function update_gallery( $field_object, $translation_group ) {

		$media_translation_groups = array();

		foreach ( $translation_group as $lang_code => $post_id ) {

			$gallery = false;

			if ( $field_object["value"] ) {

				$gallery = array();

				foreach ( $field_object["value"] as $i => $image ) {

					$media_obj = (object) $image;

					$source_lang = pll_get_post_language( $media_obj->ID, 'slug' );
		
					$media_translation_groups[$i] = array( $source_lang => $media_obj->ID );

					$gallery[$i] = $this->get_translated_media( $image, $lang_code, $media_translation_groups[$i] );

				}

			}

			$field_object["value"] = $gallery;

			$this->update_field( $field_object['key'], $field_object['value'], $post_id );
		}
		
		foreach ( $media_translation_groups as $translation_group ) {
			pll_save_post_translations( $translation_group );
		}
	}

	private function update_field( $key, $value, $post_id ) {
		if ( $this->in_sub_field_loop ) {
			update_sub_field( $key, $value, $post_id );
		} else {
			update_field( $key, $value, $post_id );
		}
	}



	/**
	 *	Return whether a field is part of a repeater field.
	 *
	 *	@param $field	object	ACF Field Post object
	 *
	 *	@return	bool
	 */
	function is_repeater_child( $field ) {
		return get_post_type($field->post_parent) === 'acf-field';
	}

}