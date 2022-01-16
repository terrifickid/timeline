<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Syslog connection class.
 *
 * @package wsal
 * @subpackage external-db
 * @since 4.3.0
 */
class WSAL_Ext_Mirrors_SyslogConnection extends WSAL_Ext_AbstractConnection {

	public static function get_type() {
		return 'syslog';
	}

	public static function get_name() {
		return __( 'Syslog Server', 'wp-security-audit-log' );
	}

	public static function get_config_definition() {
		return [
			'desc'   => __( 'General mirror connection description.', 'wp-security-audit-log' ),
			'fields' => [
				'destination' => [
					'label'    => __( 'Syslog Location', 'wp-security-audit-log' ),
					'type'     => 'radio',
					'required' => true,
					'options'  => [
						'local'  => [
							'label' => __( 'Write to local syslog file', 'wp-security-audit-log' ),
						],
						'remote' => [
							'label'     => __( 'Send messages to remote syslog server', 'wp-security-audit-log' ),
							'subfields' => [
								'host' => [
									'label'      => __( 'IP Address / Hostname', 'wp-security-audit-log' ),
									'type'       => 'text',
									'required'   => true,
									'validation' => 'ipAddress',
									'error'      => __( 'Invalid Invalid IP/Hostname', 'wp-security-audit-log' )
								],
								'port' => [
									'label'      => __( 'Port', 'wp-security-audit-log' ),
									'type'       => 'text',
									'required'   => true,
									'validation' => 'port',
									'error'      => __( 'Invalid Port', 'wp-security-audit-log' )
								]
							]
						]
					]
				],

			]
		];
	}

	public function get_monolog_handler() {
		$destination = array_key_exists( 'destination', $this->connection ) ? $this->connection['destination'] : 'local';
		if ( array_key_exists( 'location', $this->connection ) ) {
			//  legacy settings support
			$destination = $this->connection['location'];
		}
		if ( $destination === 'local' ) {
			return new \WSAL_Vendor\Monolog\Handler\SyslogHandler( 'Security_Audit_Log' );
		} elseif ( $destination === 'remote' ) {
			return new \WSAL_Vendor\Monolog\Handler\SyslogUdpHandler( $this->connection['remote-host'], $this->connection['remote-port'] );
		}
	}

	protected static function add_extra_requirements( $checker ) {
		$checker->requirePhpExtensions( [ 'sockets' ] );

		return $checker;
	}
}
