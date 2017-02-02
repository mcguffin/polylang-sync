<?php

namespace PolylangSync\Taxonomy;
use PolylangSync\Core;


class Sync extends Core\Singleton {

	private $core;

	private $sync_taxonomies;
	
	private $unhook_created	= false;
	private $unhook_delete	= false;


	/**
	 *	Private constructor
	 */
	protected function __construct() {

		$this->core	= Core\Core::instance();

		$sync_taxonomies = get_option( 'polylang_sync_taxonomies' );

		foreach ( (array) $sync_taxonomies as $taxonomy ) {
			if ( pll_is_translated_taxonomy( $taxonomy ) ) {
				add_action( "created_{$taxonomy}", array( $this, 'created_term' ), 10, 2 );
				add_action( "edited_{$taxonomy}", array( $this, 'edited_term' ), 10, 2 );
				/*
				add_action( "delete_{$taxonomy}", array( $this, 'delete_term' ), 10, 4 );
				/*/
				//*/
			}
		}

		add_action( 'pre_delete_term', array( $this, 'pre_delete_term' ), 10, 2 );

		if ( get_option( 'polylang_sync_term_meta' ) ) {
			add_action( 'deleted_term_meta', array( $this, 'delete_meta' ), 10, 4 );
			add_action( 'added_term_meta', array( $this, 'added_meta' ), 10, 4 );
		}
	}
	
	/**
	 *	Synchronize taxonomy
	 */
	public function synchronize( $taxonomy ) {
		if ( pll_is_translated_taxonomy( $taxonomy ) ) {
			$this->unhook_created	= true;
			$terms = get_terms( array(
				'taxonomy'		=> $taxonomy,
				'hide_empty'	=> false,
			) );

			$languages	= $this->core->get_pll_languages();

			foreach ( $terms as $term ) {
				$term_lang = pll_get_term_language( $term->term_id );
				$term_translation_group = array( $term_lang => $term->term_id );

				foreach ( $languages as $lang ) {
					if ( $lang == $term_lang ) {
						continue;
					}
					if ( ! $translated_term_id = pll_get_term( $term->term_id, $lang ) ) {
						$translated_term_id = $this->create_term_translation( $term, $lang, $term_translation_group );
					}
					$translated_term	= get_term( $translated_term_id );
					$term_translation_group[ $lang ] = $translated_term->term_id;
				}
				pll_save_term_translations( $term_translation_group );
			}
			$this->unhook_created	= false;
		}
	}

	/**
	 *	@action added_{$meta_type}_meta
	 */
	public function added_meta( $mid, $object_id, $meta_key, $_meta_value ) {
		$taxonomy = get_term( $object_id )->taxonomy;
		if ( pll_is_translated_taxonomy( $taxonomy ) ) {
		}
	}

	/**
	 *	@action deleted_{$meta_type}_meta
	 */
	public function delete_meta( $meta_ids, $object_id, $meta_key, $_meta_value ) {
		$taxonomy = get_term( $object_id )->taxonomy;
		if ( pll_is_translated_taxonomy( $taxonomy ) ) {

		}
	}

	/**
	 *	@action created_{$taxonomy}
	 */
	public function created_term( $term_id, $tt_id ) {
		// prevent infinite recursion
		if ( $this->unhook_created ) {
			return;
		}
		$this->unhook_created = true;
		
		// create term translations		
		$term		= get_term( $term_id );
		$taxonomy	= get_taxonomy( $term->taxonomy );
		$languages	= $this->core->get_pll_languages();
		$term_lang	= pll_get_term_language( $term_id );

		$term_translation_group = array( 
			$term_lang	=> $term_id,
		);
		foreach ( $languages as $lang ) {
			if ( $lang == $term_lang ) {
				continue;
			}

			if ( ! $taxonomy->hierarchical || ! $term->parent ) {
				if ( ! $translated_term = pll_get_term( $term_id, $lang ) ) {
					$translated_term_id = $this->create_term_translation( $term, $lang, $term_translation_group );
				}
			} else {
				// create parent ... later!
			}
		}
		pll_save_term_translations( $term_translation_group );
		$this->unhook_created = false;
	}

	private function create_term_translation( $term, $lang_code, &$term_trnaslation_group ) {
		$translated_term_arr = wp_insert_term( 
			sprintf( '%s (%s)', $term->name, $lang_code ), 
			$term->taxonomy, 
			array( 'description' => $term->description ) 
		);
		if ( ! is_wp_error( $translated_term_arr ) ) {
			pll_set_term_language( $translated_term_arr[ 'term_id' ], $lang_code );
			$term_translation_group[ $lang_code ] = $translated_term_arr[ 'term_id' ];
		}
		return $translated_term_arr[ 'term_id' ];
	}

	/**
	 *	@action edited_{$taxonomy}
	 */
	public function edited_term( $term_id, $tt_id ) {
		// if parent changed term translations
	}

	/**
	 *	@action delete_{$taxonomy}
	 */
	public function pre_delete_term( $term_id, $taxonomy ) {

		if ( ! pll_is_translated_taxonomy( $taxonomy ) ) {
			return;
		}

		// prevent infinite recursion
		if ( $this->unhook_delete ) {
			return;
		}

		$this->unhook_delete = true;

		
		// delete all translated terms
		$languages	= $this->core->get_pll_languages();
		$term_lang	= pll_get_term_language( $term_id );

		foreach ( $languages as $lang ) {
			if ( $lang == $term_lang ) {
				continue;
			}

			if ( $translated_term_id = pll_get_term( $term_id, $lang ) ) {
				wp_delete_term( $translated_term_id, $taxonomy );
			}
			
		}

		$this->unhook_delete = false;
	}
	
}