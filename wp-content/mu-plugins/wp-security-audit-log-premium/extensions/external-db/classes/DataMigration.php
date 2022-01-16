<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WSAL_Ext_Plugin' ) ) {
	exit( esc_html__( 'You are not allowed to view this page.', 'wp-security-audit-log' ) );
}

/**
 * Background process for handling the migration of activity log data to and from an external database.
 *
 * @package wsal
 * @subpackage external-db
 * @since 4.3.2
 */
class WSAL_Ext_DataMigration extends WSAL_Vendor\WP_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'wsal_ext_db_data_migration';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {

		//'start_time'
		//'events_migrated_count' => 0,
		//'total_events_count'    => 0,
		//'batch_size'            => 50,
		//'direction'             => 'to_external',
		//'connection'            => $connection

		$plugin = WpSecurityAuditLog::GetInstance();

		//  check if the migration should be cancelled
		$should_be_cancelled = $plugin->GetGlobalBooleanSetting( 'migration_job_cancel_pending', false );
		if ( $should_be_cancelled ) {
			$plugin->DeleteGlobalSetting( 'wsal_migration_job_cancel_pending' );
			$plugin->DeleteGlobalSetting( 'wsal_migration_job' );
			$this->cancel_process();
			return false;
		}

		$direction = $item['direction'];

		//  migrate next batch of events while keeping the direction of migration in mind
		$items_migrated = 0;
		if ( 'to_external' === $direction ) {
			$items_migrated = $plugin->external_db_util->MigrateOccurrence( $item['connection'], $item['batch_size'] );
		} else if ( 'from_external' === $direction ) {
			$items_migrated = $plugin->external_db_util->MigrateBackOccurrence( $item['batch_size'] );
		}

		if ( 0 === $items_migrated ) {
			//  all the data has been migrated
			try {
				//  delete the migration job info to indicate that the migration is done
				$plugin->DeleteGlobalSetting( 'wsal_migration_job' );

				if ( 'to_external' === $direction ) {
					//  update the connection details
					$plugin->external_db_util->updateConnectionAsExternal( $item['connection'], $plugin );
				} else if ( 'from_external' === $direction ) {
					$plugin->external_db_util->RemoveExternalStorageConfig();
				}
			} catch ( Exception $exception ) {
				$this->handle_error( $exception );
			}

			return false;
		}

		$item['events_migrated_count'] += $items_migrated;
		$plugin->SetGlobalSetting( 'migration_job', $item );

		return $item;
	}

	/**
	 * @param Exception $exception
	 */
	private function handle_error( $exception ) {
		//  @todo handle migration error
		//  -   maybe add the error to the database and show it in a dismissible notice
		//  -   and give the user option to either cancel or retry
	}

}