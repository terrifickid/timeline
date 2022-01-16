<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logger handling writing to all mirrors defined in the external DB extension.
 *
 * @since 4.3.0
 * @package wsal
 * @subpackage external-db
 */
class WSAL_Ext_MirrorLogger extends WSAL_AbstractLogger {

	public function Log( $type, $data = array(), $date = null, $site_id = null ) {

		$mirrors = $this->plugin->external_db_util->get_all_mirrors();
		if ( empty( $mirrors ) ) {
			return;
		}

		$monolog_helper = $this->plugin->external_db_util->get_monolog_helper();

		//  add event code to metadata otherwise we lose it
		$data = [ 'Code' => $type ] + $data;

		//  prepare the log message
		$alert_obj = $this->plugin->alerts->GetAlert( $type );
		$message   = $alert_obj->GetMessage( $data, null, 0, 'plain' );

		foreach ( $mirrors as $mirror ) {
			//  skip disabled mirror
			if ( true !== $mirror['state'] ) {
				continue;
			}

			try {
				$connection = $this->plugin->external_db_util->get_connection( $mirror['connection'] );
				$monolog_helper->log( $connection, $mirror, $type, $message, $data );
			} catch ( Exception $exception ) {
				$this->handle_failed_attempt( $exception, $mirror );
			}
		}
	}

	/**
	 * Handle failed attempt to send an event data to a monolog handler. This could be a failure to instantiate a handler
	 * using given configuration or a communication error with the logging service.
	 *
	 * @param Exception $exception
	 * @param array $mirror
	 */
	public function handle_failed_attempt( $exception, $mirror ) {
		//  @todo save the failed attempt to the database and email site admin
		error_log($exception->getMessage());
	}
}
