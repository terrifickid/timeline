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
final class WSAL_Ext_StorageSwitchToLocal extends WSAL_Ext_StorageSwitch {
	/**
	 * @inheritDoc
	 */
	protected function get_ajax_action() {
		return 'wsal_MigrateBackOccurrence';
	}

	/**
	 * @inheritDoc
	 */
	protected function get_migration_direction() {
		return 'from_external';
	}

	/**
	 * @inheritDoc
	 */
	protected function switch_connection_after_data_deleted() {
		$this->deleteTablesAndRemoveConfig(
			esc_html__( 'Activity log events deleted from local database', 'wp-security-audit-log' ),
			'<p>' . esc_html__( 'The plugin has successfully deleted the activity log events from the external database. Now the plugin is connected and will save the activity log events using the local database.', 'wp-security-audit-log' ) . '</p>'
		);
	}

	private function deleteTablesAndRemoveConfig( $title, $content ) {
		//  this will cause the tables to be deleted, output buffering is here to capture table check error displayed if logging is enabled
		ob_start();
		$this->_plugin->external_db_util->MigrateBackOccurrence( 1 );
		$this->_plugin->external_db_util->RemoveExternalStorageConfig();
		ob_clean();

		wp_send_json_success( [
				'title'   => $title,
				'content' => $content
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function get_target_database_connector() {
		return $this->_plugin->getConnector( 'local' );
	}

	/**
	 * @inheritDoc
	 */
	protected function switch_connection_with_no_data_migration() {
		$this->deleteTablesAndRemoveConfig(
			esc_html__( 'Switched to local database', 'wp-security-audit-log' ),
			'<p>' . esc_html__( 'Plugin is now connected to the local database.', 'wp-security-audit-log' ) . '</p>'
		);
	}
}
