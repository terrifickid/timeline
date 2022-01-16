<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Slack connection class.
 *
 * @package wsal
 * @subpackage external-db
 * @since 4.3.0
 */
class WSAL_Ext_Mirrors_PapertrailConnection extends WSAL_Ext_AbstractConnection {

	public static function get_type() {
		return 'papertrail';
	}

	public static function get_name() {
		return __( 'Papertrail', 'wp-security-audit-log' );
	}

	public static function get_config_definition() {
		return [
			'desc'   => __( 'General mirror connection description.', 'wp-security-audit-log' ),
			'fields' => [
				'destination'  => [
					'label'      => __( 'Destination', 'wp-security-audit-log' ),
					'type'       => 'text',
					'validation' => 'papertrailLocation',
					'required'   => true,
					'desc'       => sprintf(
					/* translators: %s: Log destinations link */
						esc_html__( 'Specify your destination. You can find your Papertrail Destination in the %s section of your Papertrail account page. It should have the following format: logs4.papertrailapp.com:54321', 'wp-security-audit-log' ),
						'<a href="https://papertrailapp.com/account/destinations" target="_blank">' . esc_html__( 'Log Destinations', 'wp-security-audit-log' ) . '</a>'
					),
					'error'      => esc_html__( 'Invalid Papertrail Destination', 'wp-security-audit-log' )
				],
				'colorization' => [
					'label' => __( 'Colorization', 'wp-security-audit-log' ),
					'type'  => 'checkbox',
					'text'  => __( 'Enable', 'wp-security-audit-log' )
				]
			]
		];
	}

	public function get_monolog_handler() {
		return new \WSAL_Vendor\Monolog\Handler\SocketHandler( 'tls://' . $this->connection['destination'] );
	}

	public function pre_process_message( $message, $metadata ) {
		if ( array_key_exists( 'colorization', $this->connection ) && 'yes' === $this->connection['colorization'] ) {
			$message = self::colorise_json( $message );
		}

		return $message;
	}

	/**
	 * Colorise Papertrail App Message.
	 *
	 * @param string $json – Message.
	 *
	 * @return string
	 */
	private function colorise_json( $json ) {
		$seq    = array(
			'reset' => "\033[0m",
			'color' => "\033[1;%dm",
			'bold'  => "\033[1m",
		);
		$fcolor = array(
			'black'   => "\033[30m",
			'red'     => "\033[31m",
			'green'   => "\033[32m",
			'yellow'  => "\033[33m",
			'blue'    => "\033[34m",
			'magenta' => "\033[35m",
			'cyan'    => "\033[36m",
			'white'   => "\033[37m",
		);

		$output = $json;
		$output = preg_replace( '/(":)([0-9]+)/', '$1' . $fcolor['magenta'] . '$2' . $seq['reset'], $output );
		$output = preg_replace( '/(":)(true|false)/', '$1' . $fcolor['magenta'] . '$2' . $seq['reset'], $output );
		$output = str_replace( '{"', '{' . $fcolor['green'] . '"', $output );
		$output = str_replace( ',"', ',' . $fcolor['green'] . '"', $output );
		$output = str_replace( '":', '"' . $seq['reset'] . ':', $output );
		$output = str_replace( ':"', ':' . $fcolor['green'] . '"', $output );
		$output = str_replace( '",', '"' . $seq['reset'] . ',', $output );
		$output = str_replace( '",', '"' . $seq['reset'] . ',', $output );

		return $seq['reset'] . $output . $seq['reset'];
	}

	protected static function add_extra_requirements( $checker ) {
		$checker->requirePhpExtensions( [ 'sockets' ] );

		return $checker;
	}
}
