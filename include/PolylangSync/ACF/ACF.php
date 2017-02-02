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

		foreach ( $this->get_supported_fields() as $type ) {
			add_action( "acf/render_field_settings/type={$type}" , array( $this , 'render_acf_sync_settings' ) );
		}

		$this->sync = Sync::instance();
	}

	/**
	 *	Get Supported AF fields
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

			// relational
			'repeater',
		));
	}

	/**
	 *	@action acf/render_field_settings/type={$type}
	 */
	public function render_acf_sync_settings( $field ) {
		$post = get_post( $field['ID'] );
		if ( $post ) {

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
			));
		}		
	}

	/**
	 *	Return whether a field is part of a repeater field.
	 *
	 *	@param $field	object	ACF Field Post object
	 *
	 *	@return	bool
	 */
	private function is_repeater_child( $post ) {
		return get_post_type($post->post_parent) === 'acf-field';
	}

}