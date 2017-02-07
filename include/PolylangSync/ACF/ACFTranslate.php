<?php

namespace PolylangSync\ACF;
use PolylangSync\Core;


class ACFTranslate extends Core\Singleton {

	private $core;

	private $translated_acf_fields;
	
	private $ppl_mo;

	/**
	 *	Private constructor
	 */
	protected function __construct() {

		$this->core	= Core\Core::instance();

		$this->ppl_mo = array();

		add_action( 'init' , array( &$this , 'init' ), 9 );

		foreach ( $this->get_supported_fields() as $type ) {
			add_action( "acf/render_field_settings/type={$type}",		array( $this , 'render_acf_settings' ) );
		}
		add_action( 'load-post.php', array( &$this, 'enqueue_assets' ) );
		add_action( 'load-post-new.php', array( &$this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue options Assets
	 */
	function enqueue_assets() {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$css_src = '/css/admin/post.css';
		} else {
			$css_src = '/css/admin/post.min.css';
		}
		wp_enqueue_style( 'polylang_sync-admin-post' , $this->core->get_asset_url( $css_src ), array('acf-input') );
	}

	/**
	 *	@action init
	 */
	public function init() {

		global $wpdb;

		// get top level fields to sync
		$this->translated_acf_fields = [];

		$all_acf_fields	= get_posts( array(
			'post_type' => 'acf-field',
			'posts_per_page' => -1,
		));

		$field_keys		= array();
		$field_groups	= array();

		foreach( $all_acf_fields as $post ) {

			$field	= get_field_object( $post->post_name );

			if ( isset( $field['polylang_translate'] ) && $field['polylang_translate'] ) {
				add_filter( "acf/prepare_field/key={$field['key']}", array( $this, 'prepare_field' ) );
				add_filter( "acf/format_value/key={$field['key']}", 'pll__' );
				$this->translated_acf_fields[] = $field;
				$field_keys[ $field['key'] ] = $field;
				
				if ( ! isset( $field_groups[ $field['key'] ] ) ) {
					 $field_groups[ $field['key'] ]	= $this->get_field_group( $field );
				}
				
			}
		}
		
		$sql = "SELECT m1.post_id AS post_id, m1.meta_value AS field_key, m2.meta_value AS str 
					FROM $wpdb->postmeta AS m1 
					INNER JOIN $wpdb->postmeta AS m2 
						ON m1.post_id=m2.post_id AND m1.meta_id!=m2.meta_id AND m1.meta_key=CONCAT('_',m2.meta_key) 
					WHERE m1.meta_value in (".implode(',', array_fill(0, count( $field_keys ), '%s')).")";

		$sql = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array($sql), array_keys( $field_keys ) ) );


		foreach ( $wpdb->get_results( $sql ) as $row ) {
			array_keys( $field_keys );
			$field		 = $field_keys[ $row->field_key ];
			$field_group = $field_groups[ $row->field_key ];
			
			pll_register_string( sanitize_title( 'acf-' . substr( $row->str, 0, 20 ) ), $row->str, $field_group['title'], $field['type'] == 'textarea' );
		}
	}


	public function prepare_field( $field ) {

		if ( $post = get_post() ) {
			$lang = pll_get_post_language( $post->ID );
			if ( ! isset( $this->ppl_mo[ $lang ] ) ) {
				$this->ppl_mo[ $lang ] = new \PLL_MO();
				$this->ppl_mo[ $lang ]->import_from_db( PLL()->model->get_language( $lang ) );
			}
			$field['append'] = ' =&gt; ' . $this->ppl_mo[ $lang ]->translate( $field['value'] );
		}
		return $field;
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


	private function get_field_group( $field ) {
		$post = get_post($field['ID']);
		while ( $post && $post->post_parent && $post->post_type != 'acf-field-group' ) {
			$post = get_post( $post->post_parent );
		}
		if ( $post ) {
			return acf_get_field_group( $post->ID );
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