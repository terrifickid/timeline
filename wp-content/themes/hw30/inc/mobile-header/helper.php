<?php

if ( ! function_exists( 'laurits_load_page_mobile_header' ) ) {
	/**
	 * Function which loads page template module
	 */
	function laurits_load_page_mobile_header() {
		// Include mobile header template
		echo apply_filters( 'laurits_filter_mobile_header_template', laurits_get_template_part( 'mobile-header', 'templates/mobile-header' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	add_action( 'laurits_action_page_header_template', 'laurits_load_page_mobile_header' );
}

if ( ! function_exists( 'laurits_register_mobile_navigation_menus' ) ) {
	/**
	 * Function which registers navigation menus
	 */
	function laurits_register_mobile_navigation_menus() {
		$navigation_menus = apply_filters( 'laurits_filter_register_mobile_navigation_menus', array( 'mobile-navigation' => esc_html__( 'Mobile Navigation', 'laurits' ) ) );

		if ( ! empty( $navigation_menus ) ) {
			register_nav_menus( $navigation_menus );
		}
	}

	add_action( 'laurits_action_after_include_modules', 'laurits_register_mobile_navigation_menus' );
}
