<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loggly connection class.
 *
 * @package wsal
 * @subpackage external-db
 * @since 4.3.0
 */
class WSAL_Ext_Mirrors_LogglyConnection extends WSAL_Ext_AbstractConnection {

	public static function get_type() {
		return 'loggly';
	}

	public static function get_name() {
		return __( 'Loggly', 'wp-security-audit-log' );
	}

	public static function get_config_definition() {
		return [
			'desc'   => __( 'General mirror connection description.', 'wp-security-audit-log' ),
			'fields' => [
				'token' => [
					'label'    => __( 'Loggly token', 'wp-security-audit-log' ),
					'type'     => 'text',
					'required' => true,
					'desc'     => sprintf(
						esc_html__( 'The Loggly token required here is the "Customer token" and you can get it from the following URL: %s', 'wp-security-audit-log' ),
						'https://[your_subdomain].loggly.com/tokens'
					)
				]
			]
		];
	}

	protected static function add_extra_requirements( $checker ) {
		$checker->requirePhpExtensions( [ 'curl' ] );

		return $checker;
	}

	public function get_monolog_handler() {

		$token = array_key_exists( 'token', $this->connection ) ? $this->connection['token'] : '';
		if ( empty( $token ) ) {
			throw new Exception( 'Loggly token is missing.' );
		}

		return new \WSAL_Vendor\Monolog\Handler\LogglyHandler( $token );
	}

}
