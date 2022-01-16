<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WSAL_Ext_Plugin' ) ) {
	exit( esc_html__( 'You are not allowed to view this page.', 'wp-security-audit-log' ) );
}

/**
 * Abstract handler class for AJAX plugin storage switching.
 *
 * @package wsal
 * @subpackage external-db
 * @since 4.3.2
 */
abstract class WSAL_Ext_StorageSwitch {

	/**
	 * Instance of WSAL.
	 *
	 * @var WpSecurityAuditLog
	 */
	protected $_plugin;

	/**
	 * Constructor.
	 *
	 * @param WpSecurityAuditLog $plugin – Instance of WSAL.
	 */
	public function __construct( $plugin ) {
		$this->_plugin = $plugin;
		$ajax_action   = $this->get_ajax_action();
		add_action( 'wp_ajax_' . $ajax_action, array( $this, 'handle_ajax_call' ) );
	}

	abstract protected function get_ajax_action();

	public function handle_ajax_call() {
		// verify nonce
		$this->check_nonce();

		//  check the existing connection config
		$this->check_existing_connection();

		//  run any further connection checks
		$this->run_additional_connection_checks();

		$decision = array_key_exists( 'decision', $_POST ) ? filter_var( $_POST['decision'], FILTER_SANITIZE_STRING ) : null;
		if ( ! is_null( $decision ) ) {
			//  if we received a decision attribute, all the checks already ran and we should either start the data
			//  migration or delete the data and switch the storage
			if ( 'migrate' === $decision ) {
				$job = new WSAL_Ext_DataMigration();

				$occurrence_model       = new WSAL_Models_Occurrence();
				$source_db_events_count = $occurrence_model->Count();

				$direction   = $this->get_migration_direction();
				$bg_job_args = $this->get_data_migration_bg_job_args( [
					'start_time'            => current_time( 'timestamp' ),
					'events_migrated_count' => 0,
					'total_events_count'    => $source_db_events_count,
					'batch_size'            => WSAL_Ext_Settings::QUERY_LIMIT,
					'direction'             => $direction,
				] );
				$job->push_to_queue( $bg_job_args );

				$job->save()->dispatch();

				$success_message = ( 'to_external' === $direction ) ? esc_html__( 'The migration of the activity log data from the local database to the external database has started.', 'wp-security-audit-log' ) : esc_html__( 'The migration of the activity log data from the external database to the local database has started.', 'wp-security-audit-log' );
				$success_message .= ' ' . sprintf(
						esc_html__( 'Click %s to close this prompt and see the progress.', 'wp-security-audit-log' ),
						'<strong>' . esc_html__( 'Continue', 'wp-security-audit-log' ) . '</strong>'
					);
				wp_send_json_success( [
					'title'   => esc_html__( 'Activity log migration has started', 'wp-security-audit-log' ),
					'content' => '<p>' . $success_message . '</p>'
				] );
			} else if ( 'delete' === $decision ) {
				//  delete data in the source database and switch to target storage
				$connector = $this->_plugin::getConnector();

				$connector->purge_activity();
				$occ = new WSAL_Adapters_MySQL_Occurrence( $connector->getConnection() );
				$occ->Uninstall();
				$meta = new WSAL_Adapters_MySQL_Meta( $connector->getConnection() );
				$meta->Uninstall();

				$this->switch_connection_after_data_deleted();
			}
		}

		$this->run_connectivity_checks();

		//  use output buffer in case error logging is enabled as non existing table check will produce an error below
		ob_start();

		//  check if the tables in the target database already exist
		$connector = $this->get_target_database_connector();
		if ( $connector->isInstalled() ) {
			/** @var WSAL_Adapters_MySQL_Occurrence $target_occurrence_adapter */
			$target_occurrence_adapter = $connector->getAdapter( 'Occurrence' );
			$target_db_events_count    = $target_occurrence_adapter->Count();
			if ( $target_db_events_count > 0 ) {
				wp_send_json_error(
					esc_html__( 'Plugin found non-empty tables in the target database.', 'wp-security-audit-log' )
					. ' ' . esc_html__( 'Please contact the support or empty the tables manually and try again.', 'wp-security-audit-log' )
				);
			}
		} else {
			//  create tables in the target database
			$connector->installAll( true );
			$connector->getAdapter( 'Occurrence' )->create_indexes();
			$connector->getAdapter( 'Meta' )->create_indexes();
		}
		ob_clean();

		//  check if there are any events to migrate
		$occurrence_model       = new WSAL_Models_Occurrence();
		$source_db_events_count = $occurrence_model->Count();
		if ( $source_db_events_count > 0 ) {
			wp_send_json_error( [
					'show_modal'   => 'wsal-external-db-source-data-choice-modal',
					'context_data' => $this->get_decision_modal_context_data()
				]
			);
		}

		//  no data to migrate, switch the storage
		$this->switch_connection_with_no_data_migration();
	}

	protected function check_nonce() {
		if ( false == wp_verify_nonce( $_POST['nonce'], 'wsal-external-storage-switch' ) ) {
			wp_send_json_error( esc_html__( 'Insecure request.', 'wp-security-audit-log' ) );
		}
	}

	/**
	 * Checks if the plugin uses the connection user wants to switch to and responds with wp_send_json_error if it does.
	 */
	protected function check_existing_connection() {
		//  no checks by default
	}

	/**
	 * Runs additional connection checks before the "decision" processing block. This should not do the actual
	 * connectivity test. Responds with wp_send_json_error if there are any problems.
	 */
	protected function run_additional_connection_checks() {
		//  no checks by default
	}

	/**
	 * @return string Migration direction.
	 */
	protected abstract function get_migration_direction();

	/**
	 * Function is intended to add extra data to the data passed to the migration background.
	 *
	 * @param array $args
	 *
	 * @return array Updated list arguments that are passed to the migration background job.
	 */
	protected function get_data_migration_bg_job_args( $args ) {
		return $args;
	}

	/**
	 * Switches connection after the data in source database has been deleted. It must respond with wp_send_json_success
	 * that contains return "title" and "content".
	 *
	 * @return mixed
	 */
	protected abstract function switch_connection_after_data_deleted();

	/**
	 * Runs connectivity check after the "decision" processing block. Responds with wp_send_json_error if there are any
	 * problems.
	 */
	protected function run_connectivity_checks() {
		//  no check by default
	}

	protected abstract function get_target_database_connector();

	protected function get_decision_modal_context_data() {
		return [];
	}

	protected abstract function switch_connection_with_no_data_migration();
}
