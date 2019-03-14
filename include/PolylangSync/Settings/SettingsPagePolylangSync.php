<?php

namespace PolylangSync\Settings;

if ( ! defined('ABSPATH') ) {
	die('FU!');
}

use PolylangSync\Ajax;
use PolylangSync\Sync;


class SettingsPagePolylangSync extends Settings {

	private $optionset = 'polylang_sync';

	private $ajax_sync_handler = null;


	/**
	 *	@inheritdoc
	 */
	protected function __construct() {

		add_option( 'polylang_sync_taxonomies' , array() , '' , false );

		add_option( 'polylang_sync_term_meta' , array() , '' , false );

		add_option( 'polylang_sync_strings_capability', 'manage_options', '' , false );

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		$this->ajax_sync_handler = Ajax\Ajax::register_action( 'sync-taxonomy', array(
			'capability'	=> 'manage_options',
			'callback'		=> array( $this, 'ajax_sync_taxonomies' ),
		) );

		parent::__construct();

	}

	public function ajax_sync_taxonomies( $params ) {
		$sync = Sync\Taxonomy::instance();
		$taxonomies = get_option( 'polylang_sync_taxonomies' );

		foreach ( $taxonomies as $taxonomy ) {
			$sync->synchronize( $taxonomy );
		}
		return array( 'success' => true );
	}

	/**
	 *	Add Settings page
	 *
	 *	@action admin_menu
	 */
	public function admin_menu() {

		if ( function_exists( 'PLL' ) ) {

			add_options_page( __('Sync Settings' , 'polylang-sync' ),__('Sync Settings' , 'polylang-sync'), 'manage_options', $this->optionset, array( $this, 'settings_page' ) );

		}

	}

	/**
	 *	Render Settings page
	 */
	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		?>
		<div class="wrap">
			<h2><?php _e('Synchronization Settings', 'polylang-sync') ?></h2>

			<form action="options.php" method="post">
				<?php
				settings_fields(  $this->optionset );
				do_settings_sections( $this->optionset );
				submit_button( __('Save Settings' , 'polylang-sync' ) );
				?>
			</form>
		</div><?php
	}




	/**
	 *	Setup options.
	 *
	 *	@action admin_init
	 */
	public function register_settings() {

		$settings_section = 'polylang_sync_settings';

		// more settings go here ...
		register_setting( $this->optionset , 'polylang_sync_taxonomies', array( $this , 'sanitize_taxonomies' ) );
		register_setting( $this->optionset , 'polylang_sync_term_meta', array( $this , 'sanitize_checkbox' ) );
		register_setting( $this->optionset , 'polylang_sync_strings_capability', array( $this , 'sanitize_strings_cap' ) );

		add_settings_section( $settings_section, __( 'Taxonomies',  'polylang-sync' ), array( $this, 'sync_taxonomies_description' ), $this->optionset );

		// ... and here
		add_settings_field(
			'polylang_sync_taxonomies',
			__( 'Synchronize Taxonomies',  'polylang-sync' ),
			array( $this, 'setting_sync_taxonomies_ui' ),
			$this->optionset,
			$settings_section
		);

		add_settings_field(
			'polylang_sync_term_meta',
			__( 'Term Metadata',  'polylang-sync' ),
			array( $this, 'checkbox_ui' ),
			$this->optionset,
			$settings_section,
			array( 'polylang_sync_term_meta', __( 'Also syncronize Term Metadata', 'polylang-sync' ) )
		);

		add_settings_field(
			'polylang_sync_taxonomies_now',
			'',
			array( $this, 'setting_sync_taxonomies_now' ),
			$this->optionset,
			$settings_section
		);


		add_settings_field(
			'polylang_sync_strings_capability',
			__( 'Strings Editing Capability', 'polylang-sync' ),
			array( $this, 'setting_select_cap' ),
			$this->optionset,
			$settings_section
		);



	}

	/**
	 * Print some documentation for the optionset
	 */
	public function sync_taxonomies_description() {
		?>
		<div class="inside">
			<p><?php _e( 'Choose some Taxonomies to be syncronized to keep the Terms and their hierarchy in sync.' , 'polylang-sync' ); ?></p>
		</div>
		<?php
	}

	/**
	 *	add_settings_field callback
	 */
	public function setting_select_cap() {
		$setting_name	= 'polylang_sync_strings_capability';
		$setting 		= get_option( $setting_name );

		$caps = array(
			'manage_options' => __('Manage Options (Default)','polylang-sync'),
			'publish_pages' => __('Publish Pages','polylang-sync'),
			'publish_posts' => __('Publish Posts','polylang-sync'),
		);
		?>
		<select name="<?php echo $setting_name ?>">
			<?php
				foreach( $caps as $cap => $label ) {
					printf( '<option value="%s" %s>%s</option>', $cap, selected($cap,$setting,false),$label );

				}
			?>
		</select>
		<?php

	}


	public function setting_sync_taxonomies_now() {

		$send_data = array(
			'action'	=> $this->ajax_sync_handler->action,
			'nonce'		=> $this->ajax_sync_handler->nonce,
		);

		?>
			<button id="do-sync-button" class="button-secondary">
				<?php _e( 'Synchronize now!', 'polylang-sync') ?>
			</button>
			<span class="spinner"></span>
			<script type="text/javascript">
				(function($){
					$('#do-sync-button').on('click', function(e) {
						var $spinner = $(this).next('.spinner').css( 'visibility','visible' );
						e.preventDefault();
						$.post( '<?php echo admin_url( 'admin-ajax.php' ) ?>', <?php echo json_encode( $send_data ) ?>, function() {
							$spinner.css( 'visibility','hidden' );
						} );

					});
				})(jQuery);
			</script>
		<?php
	}

	/**
	 * Output Theme selectbox
	 */
	public function setting_sync_taxonomies_ui( ) {
		$setting_name	= 'polylang_sync_taxonomies';
		$setting 		= get_option( $setting_name );
		$taxonomies		= get_taxonomies( array( 'public' => true ), 'objects' );

		foreach ( $taxonomies as $taxonomy ) {
			if ( \pll_is_translated_taxonomy( $taxonomy->name ) ) {
	//
				?><label>
					<input type="checkbox" <?php checked( isset( $setting[$taxonomy->name] ), true, true ); ?> name="<?php echo $setting_name ?>[<?php esc_attr_e( $taxonomy->name ) ?>]" value="<?php esc_attr_e( $taxonomy->name ) ?>" />
					<?php echo $taxonomy->label ?>
				</label><?php
			}
		}
	}

	public function sanitize_strings_cap( $value ) {
		if ( in_array( $value, array(
			'manage_options',
			'publish_pages',
			'publish_posts' ) ) ) {
			return $value;
		}
		return 'manage_options';
	}

	/**
	 * Sanitize value of setting_1
	 *
	 * @return string sanitized value
	 */
	public function sanitize_taxonomies( $value ) {
		// do sanitation here!
		$return = array();
		foreach ( (array) $value as $tax ) {
			if ( pll_is_translated_taxonomy( $tax ) ) {
				$return[$tax] = $tax;
			}
		}
		return $return;
	}

}
