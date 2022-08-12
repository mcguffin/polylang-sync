<?php

if ( ! function_exists( 'acf_is_sub_field' ) ) {
	
	function acf_is_sub_field( $field ) {
		
		// local field uses a field instead of ID
		if( acf_is_field_key($field['parent']) ) {
			
			return true;
			
		}
		
		
		// attempt to load parent field
		if( acf_get_field($field['parent']) ) {
			
			return true;
			
		}
		
		
		// return
		return false;
		
	}
}