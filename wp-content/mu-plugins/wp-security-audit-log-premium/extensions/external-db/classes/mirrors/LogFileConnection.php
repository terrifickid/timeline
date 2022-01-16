<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Log file connection class.
 *
 * @package wsal
 * @subpackage external-db
 * @since 4.3.0
 */
class WSAL_Ext_Mirrors_LogFileConnection extends WSAL_Ext_AbstractConnection {

	public static function get_type() {
		return 'log_file';
	}

	public static function get_name() {
		return __( 'Log file(s)', 'wp-security-audit-log' );
	}

	public static function get_config_definition() {
		return [
			'desc'   => __( 'WP Activity Log can write the WordPress activity log to a log file..', 'wp-security-audit-log' ),
			'fields' => [
				'rotation' => [
					'label'   => __( 'Log file(s) rotation', 'wp-security-audit-log' ),
					'type'    => 'select',
					'options' => [
						'daily'   => __( 'daily', 'wp-security-audit-log' ),
						'monthly' => __( 'monthly', 'wp-security-audit-log' ),
						'yearly'  => __( 'yearly', 'wp-security-audit-log' ),
					],
				],
				'prefix'   => [
					'label' => __( 'Log file prefix', 'wp-security-audit-log' ),
					'type'  => 'text',
					'desc'  => sprintf(
						esc_html__( 'Optional. Default prefix is %s.', 'wp-security-audit-log' ),
						'"wsal"'
					)
				],

			]
		];
	}

	public function get_monolog_handler() {

		$prefix = 'wsal';
		if ( array_key_exists( 'prefix', $this->connection ) && ! empty ( trim( $this->connection['prefix'] ) ) ) {
			$prefix = trim( $this->connection['prefix'] );
		}

		$filename = $prefix . '.log';
		$dir_path = $this->wsal->settings()->get_working_dir_path( 'logs' );
		$result   = new \WSAL_Vendor\Monolog\Handler\RotatingFileHandler( $dir_path . $filename );

		$date_format = \WSAL_Vendor\Monolog\Handler\RotatingFileHandler::FILE_PER_DAY;
		switch ( $this->connection['rotation'] ) {
			case 'monthly':
				$date_format = \WSAL_Vendor\Monolog\Handler\RotatingFileHandler::FILE_PER_MONTH;
				break;

			case 'yearly':
				$date_format = \WSAL_Vendor\Monolog\Handler\RotatingFileHandler::FILE_PER_YEAR;
				break;
		}

		$result->setFilenameFormat( '{filename}-{date}', $date_format );

		return $result;
	}
}
