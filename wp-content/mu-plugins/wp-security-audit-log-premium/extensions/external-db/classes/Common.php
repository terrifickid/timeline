<?php
/**
 * Class: Utility Class
 *
 * Utility class for common function.
 *
 * @since 1.0.0
 * @package wsal
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WSAL_Ext_Common
 *
 * Utility class, used for all the common functions used in the plugin.
 *
 * @package wsal/external-db
 */
class WSAL_Ext_Common {

	/**
	 * Instance of WpSecurityAuditLog.
	 *
	 * @var WpSecurityAuditLog
	 */
	public $wsal = null;

	/**
	 * Archive DB Connection Object.
	 *
	 * @var object
	 */
	protected static $_archive_db = null;

	/**
	 * @var WSAL_Ext_Plugin External DB extension.
	 * @since 4.3.0
	 */
	private $extension;

	/**
	 * @var string
	 */
	private $base_url;

	/**
	 * @var string
	 */
	private $base_dir;

	/**
	 * Local cache for mirror types.
	 *
	 * @var array
	 */
	private static $mirror_types;

	/**
	 * @var WSAL_Ext_MonologHelper
	 */
	private $monolog_helper;

	/**
	 * Method: Constructor
	 *
	 * @param WpSecurityAuditLog $wsal - Instance of WpSecurityAuditLog.
	 * @param WSAL_Ext_Plugin $extension Instance of external db extension.
	 */
	public function __construct( WpSecurityAuditLog $wsal, WSAL_Ext_Plugin $extension ) {
		$this->wsal = $wsal;
		$this->extension = $extension;
		$this->base_url = trailingslashit( WSAL_BASE_URL ) . 'extensions/external-db/';
		$this->base_dir = trailingslashit( WSAL_BASE_DIR ) . 'extensions/external-db/';
	}

	/**
	 * Set the setting by name with the given value.
	 *
	 * @param string $option - Option name.
	 * @param mixed  $value - value.
	 */
	public function AddGlobalSetting( $option, $value ) {
		$this->wsal->SetGlobalSetting( $option, $value );
	}

	/**
	 * Update the setting by name with the given value.
	 *
	 * @param string $option - Option name.
	 * @param mixed  $value - Value.
	 * @return boolean
	 */
	public function UpdateGlobalSetting( $option, $value ) {
		return $this->wsal->SetGlobalSetting( $option, $value );
	}

	/**
	 * Delete setting by name.
	 *
	 * @param string $option - Option name.
	 *
	 * @return boolean result
	 */
	public function DeleteGlobalSetting( $option ) {
		return $this->wsal->DeleteGlobalSetting( $option );
	}

	/**
	 * Get setting by name.
	 *
	 * @param string $option - Option name.
	 * @param mixed  $default - Default value.
	 * @return mixed value
	 */
	public function GetSettingByName( $option, $default = false ) {
		return $this->wsal->GetGlobalSetting( $option, $default );
	}

	/**
	 * Encrypt password, before saves it to the DB.
	 *
	 * @param string $data - Original text.
	 * @return string - Encrypted text
	 */
	public function EncryptPassword( $data ) {
		return $this->wsal->getConnector()->encryptString( $data );
	}

	/**
	 * Decrypt password, after reads it from the DB.
	 *
	 * @param string $ciphertext_base64 - Encrypted text.
	 * @return string - Original text.
	 */
	public function DecryptPassword( $ciphertext_base64 ) {
		return $this->wsal->getConnector()->decryptString( $ciphertext_base64 );
	}

	/**
	 * Method: Return URL based prefix for DB.
	 *
	 * @return string - URL based prefix.
	 *
	 * @param string $name - Name of the DB type.
	 */
	public function get_url_base_prefix( $name = '' ) {
		// Get home URL.
		$home_url  = get_home_url();
		$protocols = array( 'http://', 'https://' ); // URL protocols.
		$home_url  = str_replace( $protocols, '', $home_url ); // Replace URL protocols.
		$home_url  = str_replace( array( '.', '-' ), '_', $home_url ); // Replace `.` with `_` in the URL.

		// Concat name of the DB type at the end.
		if ( ! empty( $name ) ) {
			$home_url .= '_';
			$home_url .= $name;
			$home_url .= '_';
		} else {
			$home_url .= '_';
		}

		// Return the prefix.
		return $home_url;
	}

	/**
	 * Creates a connection and returns it
	 *
	 * @param array $connection_config - Array of connection configurations.
	 * @return wpdb Instance of WPDB
	 */
	private function CreateConnection( $connection_config ) {
		$password = $this->DecryptPassword( $connection_config['password'] );
		$new_wpdb = new wpdbCustom( $connection_config['user'], $password, $connection_config['db_name'], $connection_config['hostname'], $connection_config['is_ssl'], $connection_config['is_cc'], $connection_config['ssl_ca'], $connection_config['ssl_cert'], $connection_config['ssl_key'] );
		if ( array_key_exists( 'baseprefix', $connection_config ) ) {
			$new_wpdb->set_prefix( $connection_config['baseprefix'] );
		}
		return $new_wpdb;
	}

	/*============================== External Database functions ==============================*/

	/**
	 * Migrate to external database.
	 *
	 * @param string $connection_name External connection name.
	 * @param int $limit - Limit.
	 *
	 * @return int
	 */
	public function MigrateOccurrence( $connection_name, $limit ) {
		$db_connection = $this->wsal->external_db_util->get_connection( $connection_name );
		return $this->wsal->getConnector( $db_connection )->MigrateOccurrenceFromLocalToExternal( $limit );
	}

	/**
	 * Migrate back to WP database
	 *
	 * @param int $limit - Limit.
	 *
	 * @return int
	 */
	public function MigrateBackOccurrence( $limit ) {
		return $this->wsal->getConnector()->MigrateOccurrenceFromExternalToLocal( $limit );
	}

	/**
	 * Checks if the necessary tables are available.
	 *
	 * @return bool true|false
	 */
	public function IsInstalled() {
		return $this->wsal->getConnector()->isInstalled();
	}

	/**
	 * Remove External DB config.
	 */
	public function RemoveExternalStorageConfig() {
		// Get archive connection.
		$adapter_conn_name = $this->GetSettingByName( 'adapter-connection' );
		if ( $adapter_conn_name ) {
			$adapter_connection             = $this->get_connection( $adapter_conn_name );
			$adapter_connection['used_for'] = '';
			$this->save_connection( $adapter_connection );
		}

		$this->DeleteGlobalSetting( 'adapter-connection' );
	}

	/**
	 * Recreate DB tables on WP.
	 */
	public function RecreateTables() {
		$occurrence = new WSAL_Models_Occurrence();
		$occurrence->getAdapter()->InstallOriginal();
		$meta = new WSAL_Models_Meta();
		$meta->getAdapter()->InstallOriginal();
	}

	/*============================== Mirroring functions ==============================*/

	/*============================== Archiving functions ==============================*/

	/**
	 * Check if archiving is enabled.
	 *
	 * @return bool value
	 */
	public function IsArchivingEnabled() {
		return $this->GetSettingByName( 'archiving-e' );
	}

	/**
	 * Enable/Disable archiving.
	 *
	 * @param bool $enabled - Value.
	 */
	public function SetArchivingEnabled( $enabled ) {
		$this->AddGlobalSetting( 'archiving-e', $enabled );
		if ( empty( $enabled ) ) {
			$this->RemoveArchivingConfig();
			$this->DeleteGlobalSetting( 'archiving-last-created' );
		}
	}

	/**
	 * Get archiving date.
	 *
	 * @return int value
	 */
	public function GetArchivingDate() {
		return (int) $this->GetSettingByName( 'archiving-date', 1 );
	}

	/**
	 * Set archiving date.
	 *
	 * @param string $newvalue - New value.
	 */
	public function SetArchivingDate( $newvalue ) {
		$this->AddGlobalSetting( 'archiving-date', (int) $newvalue );
	}

	/**
	 * Get archiving date type.
	 *
	 * @return string value
	 */
	public function GetArchivingDateType() {
		return $this->GetSettingByName( 'archiving-date-type', 'months' );
	}

	/**
	 * Set archiving date type.
	 *
	 * @param string $newvalue - New value.
	 */
	public function SetArchivingDateType( $newvalue ) {
		$this->AddGlobalSetting( 'archiving-date-type', $newvalue );
	}

	/**
	 * Get archiving frequency.
	 *
	 * @return string frequency
	 */
	public function GetArchivingRunEvery() {
		return $this->GetSettingByName( 'archiving-run-every', 'hourly' );
	}

	/**
	 * Set archiving frequency.
	 *
	 * @param string $newvalue - New value.
	 */
	public function SetArchivingRunEvery( $newvalue ) {
		$this->AddGlobalSetting( 'archiving-run-every', $newvalue );
	}

	/**
	 * Check if archiving stop.
	 *
	 * @return bool value
	 */
	public function IsArchivingStop() {
		return $this->GetSettingByName( 'archiving-stop' );
	}

	/**
	 * Enable/Disable archiving stop.
	 *
	 * @param bool $enabled - Value.
	 */
	public function SetArchivingStop( $enabled ) {
		$this->AddGlobalSetting( 'archiving-stop', $enabled );
	}

	/**
	 * Remove the archiving config.
	 */
	public function RemoveArchivingConfig() {
		// Get archive connection.
		$archive_conn_name = $this->GetSettingByName( 'archive-connection' );

		if ( $archive_conn_name ) {
			$archive_connection             = $this->get_connection( $archive_conn_name );
			$archive_connection['used_for'] = '';
			$this->save_connection( $archive_connection );
		}

		$this->DeleteGlobalSetting( 'archive-connection' );
		$this->DeleteGlobalSetting( 'archiving-date' );
		$this->DeleteGlobalSetting( 'archiving-date-type' );
		$this->DeleteGlobalSetting( 'archiving-run-every' );
		$this->DeleteGlobalSetting( 'archiving-daily-e' );
		$this->DeleteGlobalSetting( 'archiving-weekly-e' );
		$this->DeleteGlobalSetting( 'archiving-week-day' );
		$this->DeleteGlobalSetting( 'archiving-time' );
	}

	/**
	 * Disable the pruning config.
	 */
	public function DisablePruning() {
		$this->SetGlobalBooleanSetting( 'pruning-date-e', false );
		$this->SetGlobalBooleanSetting( 'pruning-limit-e', false );
	}

	/**
	 * Archive alerts (Occurrences table)
	 *
	 * @param array $args - Arguments array.
	 *
	 * @return array|false|null
	 */
	public function ArchiveOccurrence( $args ) {
		$args['archive_db'] = $this->ArchiveDatabaseConnection();
		if ( empty( $args['archive_db'] ) ) {
			return false;
		}
		$last_created_on = $this->GetSettingByName( 'archiving-last-created' );
		if ( ! empty( $last_created_on ) ) {
			$args['last_created_on'] = $last_created_on;
		}
		return $this->wsal->getConnector()->ArchiveOccurrence( $args );
	}

	/**
	 * Archive alerts (Metadata table)
	 *
	 * @param array $args - Arguments array.
	 *
	 * @return array|false|null
	 */
	public function ArchiveMeta( $args ) {
		$args['archive_db'] = $this->ArchiveDatabaseConnection();
		return $this->wsal->getConnector()->ArchiveMeta( $args );
	}

	/**
	 * Delete alerts from the source tables
	 * after archiving them.
	 *
	 * @param array $args - Arguments array.
	 */
	public function DeleteAfterArchive( $args ) {
		$args['archive_db'] = $this->ArchiveDatabaseConnection();
		$this->wsal->getConnector()->DeleteAfterArchive( $args );
		if ( ! empty( $args['last_created_on'] ) ) {
			// update last_created
			$this->AddGlobalSetting( 'archiving-last-created', $args['last_created_on'] );
		}
	}

	/**
	 * Check if archiving cron job started.
	 *
	 * @return bool
	 */
	public function IsArchivingCronStarted() {
		return $this->GetSettingByName( 'archiving-cron-started' );
	}

	/**
	 * Enable/Disable archiving cron job started option.
	 *
	 * @param bool $value - Value.
	 */
	public function SetArchivingCronStarted( $value ) {
		if ( ! empty( $value ) ) {
			$this->AddGlobalSetting( 'archiving-cron-started', 1 );
		} else {
			$this->DeleteGlobalSetting( 'archiving-cron-started' );
		}
	}

	/**
	 * Archiving alerts.
	 */
	public function archiving_alerts() {
		if ( ! $this->IsArchivingCronStarted() ) {
			set_time_limit( 0 );
			// Start archiving.
			$this->SetArchivingCronStarted( true );

			$args          = array();
			$args['limit'] = 100;
			$args_result   = false;

			do {
				$num             = $this->GetArchivingDate();
				$type            = $this->GetArchivingDateType();
				$now             = current_time( 'timestamp' );
				$args['by_date'] = $now - ( strtotime( $num . ' ' . $type ) - $now );
				$args_result     = $this->ArchiveOccurrence( $args );
				if ( ! empty( $args_result ) ) {
					$args_result = $this->ArchiveMeta( $args_result );
				}
				if ( ! empty( $args_result ) ) {
					$this->DeleteAfterArchive( $args_result );
				}
			} while ( $args_result != false );
			// End archiving.
			$this->SetArchivingCronStarted( false );
		}
	}

	/**
	 * Get the Archive connection
	 *
	 * @return wpdb Instance of WPDB
	 */
	private function ArchiveDatabaseConnection() {
		if ( ! empty( self::$_archive_db ) ) {
			return self::$_archive_db;
		} else {
			$connection_config = $this->GetArchiveConfig();
			if ( empty( $connection_config ) ) {
				return null;
			} else {
				// Get archive DB connection.
				self::$_archive_db = $this->CreateConnection( $connection_config );

				// Check object for disconnection or other errors.
				$connected = true;
				if ( isset( self::$_archive_db->dbh->errno ) ) {
					$connected = 0 !== (int) self::$_archive_db->dbh->errno ? false : true; // Database connection error check.
				} elseif ( is_wp_error( self::$_archive_db->error ) ) {
					$connected = false;
				}

				if ( $connected ) {
					return self::$_archive_db;
				} else {
					return null;
				}
			}
		}
	}

	/**
	 * Get the Archive config
	 *
	 * @return array|null config
	 */
	private function GetArchiveConfig() {
		$connection_name = $this->GetSettingByName( 'archive-connection' );
		if ( empty( $connection_name ) ) {
			return null;
		}

		$connection = $this->get_connection( $connection_name );
		if ( ! is_array( $connection ) ) {
			return null;
		}

		return $connection;
	}

	/**
	 * Return Connection Object.
	 *
	 * @param string $connection_name - Connection name.
	 *
	 * @return array|bool
	 * @since 3.3
	 */
	public function get_connection( $connection_name ) {
		if ( empty( $connection_name ) ) {
			return false;
		}
		$result_raw = $this->GetSettingByName( WSAL_CONN_PREFIX . $connection_name );
		$result     = maybe_unserialize( $result_raw );

		return ( $result instanceof stdClass ) ? json_decode( json_encode( $result ), true ) : $result;
	}

	/**
	 * Set Connection Object.
	 *
	 * @since 3.3
	 *
	 * @param array|stdClass $connection - Connection object.
	 */
	public function save_connection( $connection ) {
		// stop here if no connection provided
		if ( empty( $connection ) ) {
			return;
		}

		$connection_name = ( $connection instanceof stdClass ) ? $connection->name : $connection['name'];

		$this->AddGlobalSetting( WSAL_CONN_PREFIX . $connection_name, $connection );
	}

	/**
	 * Delete Connection Object.
	 *
	 * @param string $connection_name - Connection name.
	 *
	 * @since 4.3.0
	 *
	 */
	public function delete_connection( $connection_name ) {
		$connection = $this->get_connection( $connection_name );
		
		if ( is_array( $connection ) && array_key_exists( 'type', $connection ) ) {
			$this->wsal->alerts->Trigger( 6320, [
				'EventType' => 'deleted',
				'type'      => $connection['type'],
				'name'      => $connection_name,
			] );
		}

		$this->DeleteGlobalSetting( WSAL_CONN_PREFIX . $connection_name );
	}

	/**
	 * Return Mirror Object.
	 *
	 * @since 3.3
	 *
	 * @param string $mirror_name - Mirror name.
	 * @return array|bool
	 */
	public function get_mirror( $mirror_name ) {
		if ( empty( $mirror_name ) ) {
			return false;
		}
		$result_raw = $this->GetSettingByName( WSAL_MIRROR_PREFIX . $mirror_name );
		$result = maybe_unserialize($result_raw);
		return ($result instanceof stdClass) ? json_decode( json_encode( $result ), true ) : $result;
	}

	/**
	 * Set Mirror Object.
	 *
	 * @since 3.3
	 *
	 * @param array|stdClass $mirror Mirror data.
	 */
	public function save_mirror( $mirror ) {
		if ( empty( $mirror ) ) {
			return;
		}
		
		$mirror_name = ( $mirror instanceof stdClass ) ? $mirror->name : $mirror['name'];

		$old_value = $this->wsal->GetGlobalSetting( WSAL_MIRROR_PREFIX . $mirror_name );

		if ( ! isset( $old_value['state'] ) ) {
			$this->wsal->alerts->Trigger( 6323, [
				'EventType' => 'added',
				'connection' => ( $mirror instanceof stdClass ) ? $mirror->connection : $mirror['connection'],
				'name' => $mirror_name,
			]);
		} elseif ( isset( $old_value['state'] ) && $old_value['state'] !== $mirror['state'] ) {
			$this->wsal->alerts->Trigger( 6325, [
				'EventType' => ( $mirror['state'] ) ? 'enabled' : 'disabled',
				'connection' => ( $mirror instanceof stdClass ) ? $mirror->connection : $mirror['connection'],
				'name' => $mirror_name,
			]);
		} else {
			$this->wsal->alerts->Trigger( 6324, [
				'EventType' => 'modified',
				'connection' => ( $mirror instanceof stdClass ) ? $mirror->connection : $mirror['connection'],
				'name' => $mirror_name,
			]);
		}
		
		$this->AddGlobalSetting( WSAL_MIRROR_PREFIX . $mirror_name, $mirror );
	}

	/**
	 * Delete mirror.
	 *
	 * @param string $mirror_name - Mirror name.
	 *
	 * @since 4.3.0
	 *
	 */
	public function delete_mirror( $mirror_name ) {
		$this->DeleteGlobalSetting( WSAL_MIRROR_PREFIX . $mirror_name );
	}

	/**
	 * @return array List of mirror types.
	 *
	 * @since 4.3.0
	 */
	public function get_mirror_types() {
		if ( !isset(self::$mirror_types)) {
			$result      = [];
			$file_filter = $this->get_base_dir() . 'classes' . DIRECTORY_SEPARATOR . 'mirrors' . DIRECTORY_SEPARATOR . '*Connection.php';
			foreach ( glob( $file_filter ) as $file ) {
				$base_filename = basename( $file );
				$class_name    = 'WSAL_Ext_Mirrors_' . substr( $base_filename, 0, strlen( $base_filename ) - 4 );
				try {
					require_once( $file );
					$result [ $class_name::get_type() ] = [
						'name'   => $class_name::get_name(),
						'config' => $class_name::get_config_definition(),
						'class'  => $class_name
					];
				} catch ( Exception $exception ) {
					//  skip unsuitable class
					//  @todo log to debug log
				}
			}

			self::$mirror_types = $result;
		}

		return self::$mirror_types;
	}

	/**
	 * @return string
	 * @since 4.3.0
	 */
	public function get_base_url() {
		return $this->base_url;
	}

	/**
	 * @return string
	 * @since 4.3.0
	 */
	public function get_base_dir() {
		return $this->base_dir;
	}

	/**
	 * Gets configuration data for all mirrors.
	 *
	 * @return array[][]
	 * @since 4.3.0
	 */
	public function get_all_mirrors() {
		return WSAL_Ext_Common::get_config_options_for_group( WSAL_MIRROR_PREFIX );
	}

	/**
	 * Gets configuration data for all connections.
	 *
	 * @return array[][]
	 * @since 4.3.0
	 */
	public function get_all_connections() {
		return WSAL_Ext_Common::get_config_options_for_group( WSAL_CONN_PREFIX );
	}

	/**
	 * Finds all mirrors using a specific connection.
	 *
	 * @param string $connection_name
	 *
	 * @return array[]
	 * @since 4.3.0
	 */
	public function get_mirrors_by_connection_name( $connection_name ) {
		$mirrors = $this->get_all_mirrors();
		$result = [];
		if ( ! empty( $mirrors ) ) {
			foreach ( $mirrors as $mirror ) {
				if ( $connection_name === $mirror['connection'] ) {
					array_push($result, $mirror);
				}
			}
		}

		return $result;
	}

	/**
	 * Gets configuration data for all a group of data using defined prefix.
	 *
	 * Method is static because it is used as part of an upgrade routine.
	 *
	 * @param string $prefix
	 *
	 * @return array[][]
	 * @since 4.3.0
	 */
	public static function get_config_options_for_group( $prefix ) {
		$result      = [];
		$group_items = WpSecurityAuditLog::GetInstance()->GetNotificationsSetting( $prefix );

		foreach ( $group_items as $group_item ) {
			//  we need to make sure to convert all legacy stdClass objects to arrays
			if ( $group_item instanceof stdClass ) {
				$group_item = json_decode( json_encode( $group_item ), true );
			}

			$group_data_raw = maybe_unserialize( $group_item['option_value'] );
			//  we need to make sure to convert all legacy stdClass objects to arrays
			$group_data = json_decode( json_encode( $group_data_raw ), true );

			//  filter our empty or invalid entries
			if ( empty( $group_data ) ) {
				continue;
			}

			array_push( $result, $group_data );
		}

		return $result;
	}

	/**
	 * @return WSAL_Ext_MonologHelper
	 * @since 4.3.0
	 */
	public function get_monolog_helper() {
		if ( ! isset( $this->monolog_helper ) ) {
			$this->monolog_helper = new WSAL_Ext_MonologHelper( $this->wsal );
		}

		return $this->monolog_helper;
	}

	/**
	 * Checks if the necessary tables are available.
	 *
	 * @return bool true|false
	 */
	protected function CheckIfTableExist() {
		return $this->IsInstalled();
	}

	/**
	 * Updates given connection to be used for external storage.
	 *
	 * @param string $connection_name
	 * @since 4.3.2
	 */
	public function updateConnectionAsExternal( $connection_name ) {
		//  set external storage to be used for logging events from now on
		$db_connection = $this->get_connection( $connection_name );

		// Error handling.
		if ( empty( $db_connection ) ) {
			return false;
		}

		// Set connection's used_for attribute.
		$db_connection['used_for'] = __( 'External Storage', 'wp-security-audit-log' );
		$this->AddGlobalSetting( 'adapter-connection', $connection_name );
		$this->save_connection( $db_connection );
		return true;
	}
}
