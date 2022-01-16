<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract connection class.
 *
 * @package wsal
 * @subpackage external-db
 * @since 4.3.0
 */
abstract class WSAL_Ext_AbstractConnection implements WSAL_Ext_ConnectionInterface {

	/**
	 * Instance of WSAL.
	 *
	 * @var WpSecurityAuditLog
	 */
	protected $wsal;

	/**
	 * Raw connection configuration data.
	 *
	 * @var array
	 */
	protected $connection;

	/**
	 * @param WpSecurityAuditLog $wsal – Instance of WSAL.
	 * @param array $connection
	 */
	public function __construct( $wsal, $connection ) {
		$this->wsal       = $wsal;
		$this->connection = $connection;
	}

	public static function check_requirements() {
		$checker = new \WSAL_Vendor\MirazMac\Requirements\Checker();

		//  default requirements based on the Monolog library
		$checker->requirePhpVersion( '>=7.2' );

		//  let subclasses add extra requirements
		$checker = static::add_extra_requirements( $checker );

		$checker->check();
		if ( $checker->isSatisfied() ) {
			return [];
		}

		return $checker->getErrors();
	}

	/**
	 * Optionally add extra requirements in a subclass.
	 *
	 * @param \WSAL_Vendor\MirazMac\Requirements\Checker $checker
	 *
	 * @return \WSAL_Vendor\MirazMac\Requirements\Checker
	 */
	protected static function add_extra_requirements( $checker ) {
		return $checker;
	}

	public function pre_process_message( $message, $metadata ) {
		return $message;
	}

	public function pre_process_metadata( $metadata, $mirror ) {
		return $metadata;
	}

	public static function get_alternative_error_message( $original_error_message ) {
		return $original_error_message;
	}
}
