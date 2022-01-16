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
final class WSAL_Ext_StorageSwitchToExternal extends WSAL_Ext_StorageSwitch {

	/**
	 * Selected connection name.
	 *
	 * @var string
	 */
	private $connection;

	/**
	 * Selected database connection config.
	 *
	 * @var array
	 */
	private $db_connection;

	public function run_additional_connection_checks() {
		//  check selected connection
		$connection = isset( $_POST['connection'] ) ? sanitize_text_field( wp_unslash( $_POST['connection'] ) ) : false;
		if ( empty( $connection ) ) {
			wp_send_json_error( esc_html__( 'Connection name parameter is missing.', 'wp-security-audit-log' ) );
		}

		//  clear old external storage connection just to be safe (this should not be possible as of version 4.3.2)
		$old_conn_name = $this->_plugin->external_db_util->GetSettingByName( 'adapter-connection', false );
		if ( $old_conn_name && $connection !== $old_conn_name ) {
			// Get old connection object.
			$old_connection = $this->_plugin->external_db_util->get_connection( $old_conn_name );

			// Clear old connection used for.
			$old_connection['used_for'] = '';

			// Save the old connection object.
			$this->_plugin->external_db_util->save_connection( $old_connection['name'], $old_connection );
		}

		$this->connection = $connection;

		// Get connection option.
		$db_connection = $this->_plugin->external_db_util->get_connection( $connection );

		// Error handling.
		if ( empty( $db_connection ) ) {
			wp_send_json_error(
				sprintf(
					esc_html__( 'Connection %s not found.', 'wp-security-audit-log' ),
					'<strong>' . $connection . '</strong>'
				)
			);
		}

		$this->db_connection = $db_connection;
	}

	protected function get_ajax_action() {
		return 'wsal_MigrateOccurrence';
	}

	protected function check_existing_connection() {
		//  stop if the system is already using the external connection (this could happen if the UI was out of sync)
		$current_connection = $this->_plugin->GetGlobalSetting( 'adapter-connection' );
		if ( ! empty( $current_connection ) ) {
			wp_send_json_error( esc_html__( 'Plugin already uses an external storage.', 'wp-security-audit-log' ) );
		}
	}

	protected function get_data_migration_bg_job_args( $args ) {
		$args['connection'] = $this->connection;

		return $args;
	}

	protected function get_migration_direction() {
		return 'to_external';
	}

	protected function switch_connection_after_data_deleted() {
		$this->deleteTablesAndUpdateConnection(
			esc_html__( 'Activity log events deleted from local database', 'wp-security-audit-log' ),
			'<p>' . sprintf(
				esc_html__( 'The plugin has successfully deleted the activity log events from the local database. Now the plugin is connected and will save the activity log events using the external database connection %s.', 'wp-security-audit-log' ),
				'<strong>' . $this->connection . '</strong>'
			) . '</p>'
		);
	}

	private function deleteTablesAndUpdateConnection( $title, $content ) {
		//  this will cause the tables to be deleted, output buffering is here to capture table check error displayed if logging is enabled
		ob_start();
		$this->_plugin->external_db_util->MigrateOccurrence( $this->connection, 1 );
		$this->_plugin->external_db_util->updateConnectionAsExternal( $this->connection );
		ob_clean();

		wp_send_json_success( [
				'title'   => $title,
				'content' => $content
			]
		);
	}

	protected function get_target_database_connector() {
		return $this->_plugin->getConnector( $this->db_connection );
	}

	protected function switch_connection_with_no_data_migration() {
		$this->deleteTablesAndUpdateConnection(
			esc_html__( 'Switched to external database', 'wp-security-audit-log' ),
			'<p>' . sprintf(
				esc_html__( 'Plugin is now connected to an external database %s.', 'wp-security-audit-log' ),
				'<strong>' . $this->connection . '</strong>'
			) . '</p>'
		);
	}

	protected function get_decision_modal_context_data() {
		return [
			'connection-name' => $this->connection
		];
	}

	protected function run_connectivity_checks() {
		// Check connection.
		$connection_ok = WSAL_Connector_ConnectorFactory::CheckConfig( $this->db_connection );
		if ( ! $connection_ok ) {
			wp_send_json_error(
				esc_html__( 'Cannot connect to the selected database connection.', 'wp-security-audit-log' )
			);
		}
	}
}
