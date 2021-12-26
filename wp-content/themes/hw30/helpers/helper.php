<?php

if ( ! function_exists( 'laurits_is_installed' ) ) {
	/**
	 * Function that checks if forward plugin installed
	 *
	 * @param string $plugin - plugin name
	 *
	 * @return bool
	 */
	function laurits_is_installed( $plugin ) {

		switch ( $plugin ) {
			case 'framework':
				return class_exists( 'QodeFramework' );
			case 'core':
				return class_exists( 'LauritsCore' );
			case 'woocommerce':
				return class_exists( 'WooCommerce' );
			case 'gutenberg-page':
				$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : array();

				return method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor();
			case 'gutenberg-editor':
				return class_exists( 'WP_Block_Type' );
			default:
				return false;
		}
	}
}

if ( ! function_exists( 'laurits_include_theme_is_installed' ) ) {
	/**
	 * Function that set case is installed element for framework functionality
	 *
	 * @param bool $installed
	 * @param string $plugin - plugin name
	 *
	 * @return bool
	 */
	function laurits_include_theme_is_installed( $installed, $plugin ) {

		if ( 'theme' === $plugin ) {
			return class_exists( 'Laurits_Handler' );
		}

		return $installed;
	}

	add_filter( 'qode_framework_filter_is_plugin_installed', 'laurits_include_theme_is_installed', 10, 2 );
}

if ( ! function_exists( 'laurits_template_part' ) ) {
	/**
	 * Function that echo module template part.
	 *
	 * @param string $module name of the module from inc folder
	 * @param string $template full path of the template to load
	 * @param string $slug
	 * @param array $params array of parameters to pass to template
	 */
	function laurits_template_part( $module, $template, $slug = '', $params = array() ) {
		echo laurits_get_template_part( $module, $template, $slug, $params ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'laurits_get_template_part' ) ) {
	/**
	 * Function that load module template part.
	 *
	 * @param string $module name of the module from inc folder
	 * @param string $template full path of the template to load
	 * @param string $slug
	 * @param array $params array of parameters to pass to template
	 *
	 * @return string - string containing html of template
	 */
	function laurits_get_template_part( $module, $template, $slug = '', $params = array() ) {
		//HTML Content from template
		$html          = '';
		$template_path = LAURITS_INC_ROOT_DIR . '/' . $module;

		$temp = $template_path . '/' . $template;
		if ( is_array( $params ) && count( $params ) ) {
			extract( $params ); // @codingStandardsIgnoreLine
		}

		$template = '';

		if ( ! empty( $temp ) ) {
			if ( ! empty( $slug ) ) {
				$template = "{$temp}-{$slug}.php";

				if ( ! file_exists( $template ) ) {
					$template = $temp . '.php';
				}
			} else {
				$template = $temp . '.php';
			}
		}

		if ( $template ) {
			ob_start();
			include( $template ); // phpcs:ignore WPThemeReview.CoreFunctionality.FileInclude.FileIncludeFound
			$html = ob_get_clean();
		}

		return $html;
	}
}

if ( ! function_exists( 'laurits_get_page_id' ) ) {
	/**
	 * Function that returns current page id
	 * Additional conditional is to check if current page is any wp archive page (archive, category, tag, date etc.) and returns -1
	 *
	 * @return int
	 */
	function laurits_get_page_id() {
		$page_id = get_queried_object_id();

		if ( laurits_is_wp_template() ) {
			$page_id = - 1;
		}

		return apply_filters( 'laurits_filter_page_id', $page_id );
	}
}

if ( ! function_exists( 'laurits_is_wp_template' ) ) {
	/**
	 * Function that checks if current page default wp page
	 *
	 * @return bool
	 */
	function laurits_is_wp_template() {
		return is_archive() || is_search() || is_404() || ( is_front_page() && is_home() );
	}
}

if ( ! function_exists( 'laurits_get_ajax_status' ) ) {
	/**
	 * Function that return status from ajax functions
	 *
	 * @param string $status - success or error
	 * @param string $message - ajax message value
	 * @param string|array $data - returned value
	 * @param string $redirect - url address
	 */
	function laurits_get_ajax_status( $status, $message, $data = null, $redirect = '' ) {
		$response = array(
			'status'   => esc_attr( $status ),
			'message'  => esc_html( $message ),
			'data'     => $data,
			'redirect' => ! empty( $redirect ) ? esc_url( $redirect ) : '',
		);

		$output = json_encode( $response );

		exit( $output ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'laurits_get_button_element' ) ) {
	/**
	 * Function that returns button with provided params
	 *
	 * @param array $params - array of parameters
	 *
	 * @return string - string representing button html
	 */
	function laurits_get_button_element( $params ) {
		if ( class_exists( 'LauritsCore_Button_Shortcode' ) ) {
			return LauritsCore_Button_Shortcode::call_shortcode( $params );
		} else {
			$link   = isset( $params['link'] ) ? $params['link'] : '#';
			$target = isset( $params['target'] ) ? $params['target'] : '_self';
			$text   = isset( $params['text'] ) ? $params['text'] : '';

			return '<a itemprop="url" class="qodef-theme-button" href="' . esc_url( $link ) . '" target="' . esc_attr( $target ) . '">' . esc_html( $text ) . '</a>';
		}
	}
}

if ( ! function_exists( 'laurits_render_button_element' ) ) {
	/**
	 * Function that render button with provided params
	 *
	 * @param array $params - array of parameters
	 */
	function laurits_render_button_element( $params ) {
		echo laurits_get_button_element( $params ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'laurits_class_attribute' ) ) {
	/**
	 * Function that render class attribute
	 *
	 * @param string|array $class
	 */
	function laurits_class_attribute( $class ) {
		echo laurits_get_class_attribute( $class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'laurits_get_class_attribute' ) ) {
	/**
	 * Function that return class attribute
	 *
	 * @param string|array $class
	 *
	 * @return string|mixed
	 */
	function laurits_get_class_attribute( $class ) {
		return laurits_is_installed( 'framework' ) ? qode_framework_get_class_attribute( $class ) : '';
	}
}

if ( ! function_exists( 'laurits_get_post_value_through_levels' ) ) {
	/**
	 * Function that returns meta value if exists
	 *
	 * @param string $name name of option
	 * @param int $post_id id of
	 *
	 * @return string value of option
	 */
	function laurits_get_post_value_through_levels( $name, $post_id = null ) {
		return laurits_is_installed( 'framework' ) && laurits_is_installed( 'core' ) ? laurits_core_get_post_value_through_levels( $name, $post_id ) : '';
	}
}

if ( ! function_exists( 'laurits_get_space_value' ) ) {
	/**
	 * Function that returns spacing value based on selected option
	 *
	 * @param string $text_value - textual value of spacing
	 *
	 * @return int
	 */
	function laurits_get_space_value( $text_value ) {
		return laurits_is_installed( 'core' ) ? laurits_core_get_space_value( $text_value ) : 0;
	}
}

if ( ! function_exists( 'laurits_wp_kses_html' ) ) {
	/**
	 * Function that does escaping of specific html.
	 * It uses wp_kses function with predefined attributes array.
	 *
	 * @param string $type - type of html element
	 * @param string $content - string to escape
	 *
	 * @return string escaped output
	 * @see wp_kses()
	 *
	 */
	function laurits_wp_kses_html( $type, $content ) {
		return laurits_is_installed( 'framework' ) ? qode_framework_wp_kses_html( $type, $content ) : $content;
	}
}

if ( ! function_exists( 'laurits_render_svg_icon' ) ) {
	/**
	 * Function that print svg html icon
	 *
	 * @param string $name - icon name
	 * @param string $class_name - custom html tag class name
	 */
	function laurits_render_svg_icon( $name, $class_name = '' ) {
		echo laurits_get_svg_icon( $name, $class_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'laurits_get_svg_icon' ) ) {
	/**
	 * Returns svg html
	 *
	 * @param string $name - icon name
	 * @param string $class_name - custom html tag class name
	 *
	 * @return string - string containing svg html
	 */
	function laurits_get_svg_icon( $name, $class_name = '' ) {
		$html  = '';
		$class = isset( $class_name ) && ! empty( $class_name ) ? 'class="' . esc_attr( $class_name ) . '"' : '';

		switch ( $name ) {
			case 'menu':
				$html = '<svg ' . $class . ' xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="69.247px" height="69.246px" viewBox="0 0 69.247 69.246" enable-background="new 0 0 69.247 69.246" xml:space="preserve"><path opacity="0.4" fill="none" stroke="#241C10" stroke-miterlimit="10" d="M68.123,34.803c0,18.5-14.998,33.501-33.498,33.501 c-18.504,0-33.502-15.001-33.502-33.501c0-18.499,14.998-33.499,33.502-33.499C53.125,1.304,68.123,16.304,68.123,34.803z"/><g><path fill="#241C10" d="M22.775,38.185H21.55v-3.612l0.114-3.853l-3.028,7.465h-0.933l-3.021-7.459l0.114,3.847v3.612h-1.225 v-9.243h1.054h0.533l3.015,7.553l3.015-7.553h1.587V38.185z"/><path fill="#241C10" d="M32.749,37.194v0.99h-4.882h-0.99v-9.243h1.232h4.576v0.997h-4.576v2.977h3.992v0.99h-3.992v3.289H32.749z" /><path fill="#241C10" d="M42.088,38.185l-4.64-7.148v7.148h-1.232v-9.243h1.232l4.658,7.167v-7.167h1.219v9.243H42.088z"/><path fill="#241C10" d="M53.953,35.194c0,0.668-0.151,1.238-0.454,1.707c-0.303,0.471-0.709,0.822-1.219,1.057 c-0.51,0.236-1.072,0.354-1.686,0.354c-0.644,0-1.216-0.117-1.717-0.354c-0.502-0.234-0.897-0.586-1.188-1.053 c-0.29-0.469-0.435-1.037-0.435-1.711v-6.252h1.219v6.252c0,0.703,0.189,1.232,0.568,1.59s0.896,0.537,1.552,0.537 c0.66,0,1.179-0.18,1.556-0.537c0.376-0.357,0.564-0.887,0.564-1.59v-6.252h1.238V35.194z"/></g></svg>';
				break;
			case 'search':
				$html = '<svg ' . $class . ' xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="13px" height="13px" viewBox="-1 -1 12 12" enable-background="new 0 0 11 11" xml:space="preserve"><g transform="translate(-1550 -19)"><g transform="translate(1550 19)"> <circle fill="none" stroke="#000000" cx="4.479" cy="4.479" r="3.979"></circle></g><line fill="none" stroke="#000000" x1="1557" y1="26" x2="1561" y2="30"></line></g></svg>';
				break;
			case 'star':
				$html = '<svg ' . $class . ' xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="15px" height="14px" viewBox="0 0 15 14" enable-background="new 0 0 15 14" xml:space="preserve"><polygon fill="none" points="10.181,11.367 9.36,8.969 9.117,8.258 9.732,7.826 11.831,6.353 8.538,6.353 8.313,5.666 7.5,3.2 7.5,9.452 8.082,9.868 "/><g><polygon fill="none" points="4.819,11.367 5.64,8.969 5.883,8.258 5.268,7.826 3.169,6.353 6.462,6.353 6.687,5.666 7.5,3.2 7.5,9.452 6.918,9.868"/><path fill="#3D3D3D" d="M15,5.353H9.261L7.5,0L5.739,5.353H0l4.697,3.294L2.866,14L7.5,10.683L12.134,14l-1.831-5.353L15,5.353z M8.082,9.868L7.5,9.452L6.918,9.868l-2.099,1.499L5.64,8.969l0.243-0.711L5.268,7.826L3.169,6.353h3.293l0.226-0.687L7.5,3.2 l0.813,2.466l0.226,0.687h3.293L9.732,7.826L9.117,8.258L9.36,8.969l0.821,2.398L8.082,9.868z"/></g></svg>';
				break;
			case 'menu-arrow-right':
				$html = '<svg ' . $class . ' xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="8.173px" height="14.778px" viewBox="0 0 8.173 14.778" enable-background="new 0 0 8.173 14.778" xml:space="preserve"><polyline fill="none" stroke="#3D3D3D" stroke-width="1.1" stroke-miterlimit="10" points="0.395,0.395 7.395,7.166 0.395,14.395 "/></svg>';
				break;
			case 'slider-arrow-left':
				$html = '<svg ' . $class . ' xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="55.803" height="38" viewBox="0 0 55.803 38"><g transform="translate(55.803 38.378) rotate(180)"><path d="M0,0,19.012,19.012,0,38.024" transform="translate(36.084 0)"/><line y2="54.964" transform="translate(0 19.024) rotate(-90)"/></g></svg>';
				break;
			case 'slider-arrow-right':
				$html = '<svg ' . $class . ' xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="55.803" height="38" viewBox="0 0 55.803 38"><g transform="translate(0 0.354)"><path d="M-166.756,620.12l19.012,19.012-19.012,19.012" transform="translate(202.84 -620.12)"/><line y2="54.964" transform="translate(0 19.024) rotate(-90)"/></g></svg>';
				break;
			case 'pagination-arrow-left':
				$html = '<svg ' . $class . ' xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="18.7px" height="12.7px" viewBox="0 0 18.7 12.7" style="enable-background:new 0 0 18.7 12.7;" xml:space="preserve"><g transform="translate(-1576.5 -477.146)"><path class="st0" d="M1583.4,489.5l-6.2-6l6.2-6"/><line class="st0" x1="1595.2" y1="483.5" x2="1577.3" y2="483.5"/></g></svg>';
				break;
			case 'pagination-arrow-right':
				$html = '<svg ' . $class . ' xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="18.72px" height="12.719px" viewBox="0 0 18.72 12.719" enable-background="new 0 0 18.72 12.719" xml:space="preserve"><g transform="translate(-1576.5 -477.146)"><path d="M1588.288,477.506l6.212,6l-6.212,6"/><line x1="1576.5" y1="483.508" x2="1594.455" y2="483.508"/></g></svg>';
				break;
			case 'close':
				$html = '<svg ' . $class . ' xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="32" height="32" viewBox="0 0 32 32"><g><path d="M 10.050,23.95c 0.39,0.39, 1.024,0.39, 1.414,0L 17,18.414l 5.536,5.536c 0.39,0.39, 1.024,0.39, 1.414,0 c 0.39-0.39, 0.39-1.024,0-1.414L 18.414,17l 5.536-5.536c 0.39-0.39, 0.39-1.024,0-1.414c-0.39-0.39-1.024-0.39-1.414,0 L 17,15.586L 11.464,10.050c-0.39-0.39-1.024-0.39-1.414,0c-0.39,0.39-0.39,1.024,0,1.414L 15.586,17l-5.536,5.536 C 9.66,22.926, 9.66,23.56, 10.050,23.95z"></path></g></svg>';
				break;
			case 'spinner':
				$html = '<svg ' . $class . ' xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512"><path d="M304 48c0 26.51-21.49 48-48 48s-48-21.49-48-48 21.49-48 48-48 48 21.49 48 48zm-48 368c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.49-48-48-48zm208-208c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.49-48-48-48zM96 256c0-26.51-21.49-48-48-48S0 229.49 0 256s21.49 48 48 48 48-21.49 48-48zm12.922 99.078c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48c0-26.509-21.491-48-48-48zm294.156 0c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48c0-26.509-21.49-48-48-48zM108.922 60.922c-26.51 0-48 21.49-48 48s21.49 48 48 48 48-21.49 48-48-21.491-48-48-48z"></path></svg>';
				break;
			case 'link':
				$html = '<svg ' . $class . ' xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="32.06999969482422" height="33.58000183105469" viewBox="0 0 32.06999969482422 33.58000183105469"><g><path d="M 7.54,15.77c 1.278,1.278, 3.158,1.726, 4.868,1.216L 2.96,7.54C 2.652,7.232, 2.49,6.786, 2.49,6.254 c0-0.88, 0.46-2.004, 1.070-2.614c 0.8-0.8, 2.97-1.686, 3.98-0.682l 9.446,9.448c 0.138-0.462, 0.208-0.942, 0.208-1.422 c0-1.304-0.506-2.526-1.424-3.446L 9.364,1.134C 7.44-0.79, 3.616-0.068, 1.734,1.814C 0.642,2.906-0.036,4.598-0.036,6.23 c0,1.268, 0.416,2.382, 1.17,3.136L 7.54,15.77zM 24.46,16.23c-1.278-1.278-3.158-1.726-4.868-1.216l 9.448,9.448c 0.308,0.308, 0.47,0.752, 0.47,1.286 c0,0.88-0.46,2.004-1.070,2.614c-0.8,0.8-2.97,1.686-3.98,0.682L 15.014,19.594c-0.138,0.462-0.208,0.942-0.208,1.422 c0,1.304, 0.506,2.526, 1.424,3.446l 6.404,6.404c 1.924,1.924, 5.748,1.202, 7.63-0.68c 1.092-1.092, 1.77-2.784, 1.77-4.416 c0-1.268-0.416-2.382-1.17-3.136L 24.46,16.23zM 9.164,9.162C 8.908,9.416, 8.768,9.756, 8.768,10.116s 0.14,0.698, 0.394,0.952l 11.768,11.77 c 0.526,0.524, 1.38,0.524, 1.906,0c 0.256-0.254, 0.394-0.594, 0.394-0.954s-0.14-0.698-0.394-0.952L 11.068,9.162 C 10.544,8.638, 9.688,8.638, 9.164,9.162z"></path></g></svg>';
				break;
			case 'quote':
				$html = '<svg ' . $class . ' xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="80" height="73" viewBox="0 0 80 73"><path d="M.5.5V37H18.9A18.4,18.4,0,0,1,.5,55V72.5A35.829,35.829,0,0,0,36.3,37V.5Z"/><path d="M43.7.5V37H62.1A18.4,18.4,0,0,1,43.7,55V72.5A35.829,35.829,0,0,0,79.5,37V.5Z"/></svg>';
				break;
			case 'calendar':
				$html = '<svg ' . $class . ' xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="16" height="14" viewBox="0 0 16 14"><g><rect width="16" height="14"/><rect x="0.5" y="0.5" width="15" height="13"/></g><rect width="2" height="2" transform="translate(3 4)"/><rect width="2" height="2" transform="translate(3 8)"/><rect width="2" height="2" transform="translate(7 4)"/><rect width="2" height="2" transform="translate(7 8)"/><rect width="2" height="2" transform="translate(11 4)"/><rect width="2" height="2" transform="translate(11 8)"/></svg>';
				break;
		}

		// remove white spaces from loaded svg markup
		$html = preg_replace( '~>\s+<~', '><', $html );
		$html = trim( $html );

		return apply_filters( 'laurits_filter_svg_icon', $html );
	}
}
