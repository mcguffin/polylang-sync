<?php

namespace PolylangSync\Sync;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use PolylangSync\Core;


class ACFTranslate extends Core\Singleton {

	private $core;

	private $translated_acf_fields;

	private $pll_mo = null;

	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		$this->core	= Core\Core::instance();


		add_action( 'init' , array( $this , 'init' ), 9 );

		foreach ( $this->get_supported_fields() as $type ) {
			add_action( "acf/render_field_settings/type={$type}", array( $this, 'render_acf_settings' ) );
		}

		add_action( 'load-post.php', array( $this, 'enqueue_assets' ) );
		add_action( 'load-post-new.php', array( $this, 'enqueue_assets' ) );
	}

	/**
	 *	Enqueue options Assets
	 *
	 *	@action load-post.php
	 *	@action load-post-new.php
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'polylang_sync-admin-post' , $this->core->get_asset_url( '/css/admin/post.css' ), array('acf-input') );
	}

	/**
	 *	@action init
	 */
	public function init() {

		global $wpdb;

		// get top level fields to sync
		$this->translated_acf_fields = [];

		// get all fields
		$all_acf_fields	= get_posts( array(
			'post_type' => 'acf-field',
			'posts_per_page' => -1,
		));

		$default_lang = PLL()->model->get_language( PLL()->options['default_lang'] );
		$field_keys		= array();
		$field_groups	= array();

		// Gather translated fields
		foreach( $all_acf_fields as $post ) {

			$field	= get_field_object( $post->post_name );

			if ( isset( $field['polylang_translate'] ) && $field['polylang_translate'] ) {
				add_filter( "acf/prepare_field/key={$field['key']}", array( $this, 'prepare_field' ) );
				add_filter( "acf/format_value/key={$field['key']}", 'pll__' );
			//	add_filter( "acf/update_value/key={$field['key']}", array( $this, 'update_value'), 10, 3 );

				$this->translated_acf_fields[] = $field;
				$field_keys[ $field['key'] ] = $field;

				if ( ! isset( $field_groups[ $field['key'] ] ) ) {
					 $field_groups[ $field['key'] ]	= $this->get_field_group( $field['parent'] );
				}

			}
		}

		// only insert from default language
		$sql = "SELECT DISTINCT m1.meta_value AS field_key, m2.meta_value AS str
					FROM $wpdb->postmeta AS m1
					INNER JOIN $wpdb->postmeta AS m2
						ON m1.post_id=m2.post_id AND m1.meta_id!=m2.meta_id AND m1.meta_key = CONCAT('_',m2.meta_key)
					INNER JOIN $wpdb->term_relationships AS t
						ON m1.post_id = t.object_id AND t.term_taxonomy_id = %d
					WHERE m1.meta_value in (".implode(',', array_fill(0, count( $field_keys ), '%s')).")";



		$sql = call_user_func_array(
			array( $wpdb, 'prepare' ),
			array_merge( array($sql), array( $default_lang->term_id ), array_keys( $field_keys ) )
		);

		$results = $wpdb->get_results( $sql );

		foreach ( $results as $row ) {
			array_keys( $field_keys );
			$field		 = $field_keys[ $row->field_key ];
			$field_group = $field_groups[ $row->field_key ];

			/* @args $name, $string, $group, $multiline */
			pll_register_string( sanitize_title( 'acf-' . substr( $row->str, 0, 20 ) ), $row->str, $field_group['title'], $field['type'] === 'textarea' );
		}
	}

	/**
	 *	Display translated value of field
	 *
	 *	@filter  "acf/prepare_field/key={$field['key']}"
	 */
	public function prepare_field( $field ) {

		if ( $post = get_post() ) {
			$lang = pll_get_post_language( $post->ID );
			$lang_obj = PLL()->model->get_language( $lang );
			$mo = $this->get_pll_mo( $lang );

			$field_group = $this->get_field_group($field['parent']);
			$admin_url = add_query_arg(array(
				'page'	=> 'mlang_strings',
				'group'	=> rawurlencode($field_group['title'] )
			), admin_url('admin.php') );

			$field['append'] = sprintf('<a title="%s" href="%s">%s %s</a>',
				__('Edit Translation','polylang-sync'),
				$admin_url,
				$lang_obj->flag,
				$mo->translate( $field['value'] )
			);
		}
		$field['wrapper']['class'] .= ' pll-sync-translated';
		return $field;
	}


	/**
	 *	Write field value as translation to pll_mo
	 *
	 *	@filter  "acf/update_value/key={$field['key']}"
	 */
	public function update_value( $value, $post_id, $field ) {

		// only update if no sync in progress
		if ( did_action( 'pll_sync_begin_sync_acf' ) && ! did_action( 'pll_sync_end_sync_acf' ) ) {
			return $value;
		}

		if ( $post = get_post( $post_id ) ) {
			$lang = pll_get_post_language( $post->ID );
			$default_lang = PLL()->options['default_lang'];
			$mo = $this->get_pll_mo( $lang );
			// get default
			$orig_post = pll_get_post( $post_id, $default_lang );
			$orig = get_field( $field['name'], $orig_post );
			error_log('-----------');
			error_log($lang);
			error_log($default_lang);
			error_log($post_id);
			error_log($orig_post);
			error_log($orig);
			error_log($value);
			if ( ! empty( $orig ) && ! empty( $value ) ) {
				$mo->add_entry( $mo->make_entry( $orig, $value ) );
				$mo->export_to_db( PLL()->model->get_language( $lang ) );
				error_log('--- save mo ----');
			}
			// if ( lang is default ): do nothing
			//var_dump()
		}
		return $value;
	}

	/**
	 *	@param string $lang
	 */
	private function get_pll_mo( $lang ) {
		if ( is_null( $this->pll_mo ) ) {
			$this->pll_mo = array();
		}

		if ( ! isset( $this->pll_mo[ $lang ] ) ) {
			$this->pll_mo[ $lang ] = new \PLL_MO();
			$this->pll_mo[ $lang ]->import_from_db( PLL()->model->get_language( $lang ) );
		}
		return $this->pll_mo[ $lang ];
	}

	/**
	 *	Get Supported ACF fields
	 */
	public function get_supported_fields() {
		return apply_filters( 'polylang_acf_sync_supported_fields', array(
			// basic
			'text',
			'textarea',
		));
	}

	/**
	 *	Get field group topmost parent
	 *
	 *	@param Array $field acf field
	 *	@return bool|acf field group
	 */
	private function get_field_group( $parent ) {

		while ( true ) {

			if ( acf_is_field_group_key( $parent ) ) {
				return acf_get_field_group( $parent );
			}
			if ( ! $field = acf_get_field( $parent )) {
				return false;
			}
			$parent = $field['parent'];
		}
		return false;
	}



	/**
	 *	@action acf/render_field_settings/type={$type}
	 */
	public function render_acf_settings( $field ) {

		$post = get_post( $field['ID'] );

		if ( $post ) {

			$instructions = '';

			// show column: todo: allow sortable
			acf_render_field_setting( $field, array(
				'label'			=> __( 'Translatable', 'polylang-sync' ),
				'instructions'	=> '',
				'type'			=> 'true_false',
				'name'			=> 'polylang_translate',
				'message'		=> __( 'Translate this field through Polylang strings', 'polylang-sync' ),
				'width'			=> 50,
			));
		}
	}



}
