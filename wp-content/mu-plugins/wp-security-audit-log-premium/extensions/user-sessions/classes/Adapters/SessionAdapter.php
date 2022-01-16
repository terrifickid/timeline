<?php
/**
 * Adapter: Session.
 *
 * MySQL database Session class.
 *
 * @package wsal
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MySQL database Session class.
 *
 * MySQL wsal_metadata table used for to store the sessions data:
 * user_id, session_token, creation_time, expiry_time, ip.
 *
 * @package wsal
 */
class WSAL_Adapters_MySQL_Session extends WSAL_Adapters_MySQL_ActiveRecord implements WSAL_Adapters_SessionInterface {

	/**
	 * Contains the table name.
	 *
	 * @var string
	 */
	protected $_table = 'wsal_sessions';

	/**
	 * Contains primary key column name, override as required.
	 *
	 * @var string
	 */
	protected $_idkey = 'session_token';

	/**
	 * Field to hold the user ID.
	 *
	 * @var integer
	 */
	public $user_id = 0;

	/**
	 * Field to hold the session token.
	 *
	 * @var string
	 */
	public $session_token = '';

	/**
	 * Field to store the session creation time.
	 *
	 * @var int
	 */
	public $creation_time = 0;

	/**
	 * Field to store the session expiry time.
	 *
	 * @var int
	 */
	public $expiry_time = 0;

	/**
	 * Field to store the user IP.
	 *
	 * @var string
	 */
	public $ip = '';

	/**
	 * Field to store the user roles.
	 *
	 * @var array
	 */
	public $roles = array();

	/**
	 * Field to store the sites.
	 *
	 * @var string
	 */
	public $sites = '';

	/**
	 * Session token max length.
	 *
	 * @var int
	 */
	public static $session_token_maxlength = 128;

	/**
	 * Session ip max length.
	 *
	 * @var int
	 */
	public static $ip_maxlength = 45;

	/**
	 * Field to store the user roles.
	 *
	 * @var array
	 */
	public static $roles_maxlength = 128;

	/**
	 * Session value.
	 *
	 * @var mixed
	 */
	public $value = array(); // Force mixed type.

	/**
	 * Returns the model class for adapter.
	 *
	 * @return WSAL_Models_Session
	 */
	public function GetModel() {
		return new WSAL_Models_Session();
	}

	/**
	 * Deletes session from the table by session token.
	 *
	 * @param array $session_token - Session tokens to delete.
	 */
	public function delete_by_session_token( $session_token ) {
		if ( is_array($session_token) ) {
			$this->delete_by_session_tokens($session_token);
		}

		if ( ! empty( $session_token ) ) {
			$sql = 'DELETE FROM `' . $this->GetTable() . '` WHERE `session_token` = "' . $session_token . '"';
			// Execute query.
			parent::DeleteQuery( $sql );
		}
	}

	/**
	 * Delete sessions from the table by session tokens.
	 *
	 * @param array $session_tokens - List of session tokens to delete.
	 */
	public function delete_by_session_tokens( $session_tokens = array() ) {
		if ( is_array( $session_tokens ) && ! empty( $session_tokens ) ) {
			$sql = 'DELETE FROM `' . $this->GetTable() . '` WHERE `session_token` IN ("' . implode( '", "', $session_tokens ) . '")';
			// Execute query.
			parent::DeleteQuery( $sql );
		}
	}

	/**
	 * Delete sessions from the table by user ids.
	 *
	 * @param array $user_ids - List of user ids to delete tokens for.
	 *
	 * @return int
	 */
	public function delete_by_user_ids( $user_ids = array() ) {
		if ( ! empty( $user_ids ) ) {
			$sql = 'DELETE FROM `' . $this->GetTable() . '` WHERE `user_id` IN (' . implode( ',', $user_ids ) . ')';
			// Execute query.
			return intval( parent::DeleteQuery( $sql ) );
		}

		return 0;
	}

	/**
	 * Delete sessions for a user excluding the passed session.
	 *
	 * @param string $session_token - a single session token to keep - removing others.
	 */
	public function delete_all_user_sessions_except( $session_token = '' ) {
		// try get the user ID from the session token.
		if ( ! empty( $session_token ) ) {
			$session = $this->load_by_session( $session_token );
			$user_id = ( isset( $session->user_id ) ) ? $session->user_id : 0;
		}
		// using the user_id and $session_token perform the delete query.
		if ( ! empty( $user_id ) && ! empty( $session_token ) ) {
			$sql = 'DELETE FROM `' . $this->GetTable() . '` WHERE `user_id` = "' . $user_id . '" AND NOT `session` = "' . $session_token . '"';
			// Execute query.
			parent::DeleteQuery( $sql );
		}
	}

	/**
	 * Loads all the sessions that have exceeded the expiry time.
	 *
	 * @method get_all_expired_sessions
	 * @since  4.1.0
	 * @return array
	 */
	public function get_all_expired_sessions() {
		return $this->LoadArray( 'expiry_time < %d', array( time() ) );
	}

	/**
	 * Load by name and occurrence id.
	 *
	 * @param string $session_token - a session token.
	 * @return WSAL_Models_Session[]
	 */
	public function load_by_session_token( $session_token = '' ) {
		return $this->Load( 'session_token = %s', array( $session_token ) );
	}

	/**
	 * Load by user ID.
	 *
	 * @param int $user_id - a single user ID token.
	 * @return array of WSAL_Models_Session objects
	 */
	public function load_all_sessions_by_user_id( $user_id = 0 ) {
		return $this->Load( 'user_id = %d', array( $user_id ) );
	}

	/**
	 * Load by user ID.
	 *
	 * @param int $site_id Optional parameter to allow filtering by site ID.
	 *
	 * @return WSAL_Models_Session[]
	 * @throws Exception
	 */
	public function load_all_sessions_ordered_by_user_id( $site_id = 0 ) {
		return $this->load_array_ordered_by( 'user_id', 'ASC', $site_id );
	}

	/**
	 * Tries to retrieve an array of sessions ordered by the filed passed.
	 *
	 * @method load_array_ordered_by
	 * @param string $ordered_by the field to order by.
	 * @param string $order the direction to order - either ASC or DESC.
	 * @param int $site_id Optional parameter to allow filtering by site ID.
	 * @return WSAL_Models_Session[]
	 * @throws Exception
	 * @since  4.1.0
	 */
	public function load_array_ordered_by( $ordered_by = 'user_id', $order = 'ASC', $site_id = 0 ) {
		// ensure we have a correct order string.
		if ( 'ASC' !== $order || 'DESC' !== $order ) {
			$order = 'ASC';
		}
		$_wpdb  = $this->connection;
		$result = array();
		$query = 'SELECT * FROM ' . $this->GetTable();
		$replacements = [];
		if ( $site_id > 0 ) {
			$query .= ' WHERE sites = "all" OR FIND_IN_SET(%s, sites) > 0 ';
			array_push($replacements, $site_id);
		}
		$query .= ' ORDER BY %s ' . $order;
		array_push($replacements, $ordered_by );

		$prepared_query    = $_wpdb->prepare( $query, $replacements );
		foreach ( $_wpdb->get_results( $prepared_query, ARRAY_A ) as $data ) {
			$result[] = $this->getModel()->LoadData( $data );
		}
		return $result;
	}

	/**
	 * Create an index on the sessions table that connects the token and user_id.
	 */
	public function create_indexes() {
		$db_connection = $this->get_connection();
		$db_connection->query( 'CREATE INDEX session_token ON ' . $this->GetTable() . ' (user_id)' );
	}

	/**
	 * For back-compat this wrapper was added for a non coding standards based
	 * function name.
	 *
	 * @method _GetInstallQuery
	 * @since  4.1.0
	 * @param  boolean $prefix a custom prefix to use when installing the table.
	 * @return string
	 */
	protected function _GetInstallQuery( $prefix = false ) {
		return $this->get_install_query( $prefix );
	}

	/**
	 * Table install query.
	 *
	 * @since  4.1.0
	 * @param  string|false $prefix - (Optional) Table prefix.
	 * @return string - Must return SQL for creating table.
	 */
	protected function get_install_query( $prefix = false ) {
		$_wpdb      = $this->connection;
		$class      = get_class( $this );
		$copy       = new $class( $this->connection );
		$table_name = $this->GetTable();
		$sql        = 'CREATE TABLE IF NOT EXISTS ' . $table_name . ' (' . PHP_EOL;
		$cols       = $this->GetColumns();
		foreach ( $cols as $key ) {
			$sql .= '    ';
			switch ( true ) {
				case is_int( $copy->$key ):
					$sql .= $key . ' BIGINT NOT NULL,' . PHP_EOL;
					break;
				case is_float( $copy->$key ):
					$sql .= $key . ' DOUBLE NOT NULL,' . PHP_EOL;
					break;
				case is_string( $copy->$key ):
					$maxlength = $key . '_maxlength';
					if ( property_exists( $class, $maxlength ) ) {
						// The double `$$` is intentional.
						$sql .= $key . ' VARCHAR(' . (int) $class::$$maxlength . ') NOT NULL,' . PHP_EOL;
					} else {
						$sql .= $key . ' LONGTEXT NOT NULL,' . PHP_EOL;
					}
					break;
				case is_bool( $copy->$key ):
					$sql .= $key . ' BIT NOT NULL,' . PHP_EOL;
					break;
				case is_array( $copy->$key ):
				case is_object( $copy->$key ):
					$sql .= $key . ' LONGTEXT NOT NULL,' . PHP_EOL;
					break;
				default:
					//  fallback for any other columns would go here
					break;
			}
		}

		$sql .= $this->GetTableOptions() . PHP_EOL;

		$sql .= ')';

		if ( ! empty( $_wpdb->charset ) ) {
			$sql .= ' DEFAULT CHARACTER SET ' . $_wpdb->charset;
		}

		return $sql;
	}

	/**
	 * Must return SQL for removing table (at a minimum, it should be ` 'DROP TABLE ' . $this->_table `).
	 *
	 * NOTE: this is a wrapper invoked elsewhere but uses a non coding standards
	 * compliant name so was adjusted to be only a wrapper.
	 *
	 * @return string
	 */
	protected function _GetUninstallQuery() {
		return $this->get_uninstall_query();
	}


	/**
	 * Returns SQL string for removing the custom tables added to support the
	 * user sessions tracking in the plugin.
	 *
	 * @method get_uninstall_query
	 * @since
	 * @return string
	 */
	protected function get_uninstall_query() {
		return 'DROP TABLE IF EXISTS ' . $this->GetTable();
	}

	/**
	 * Loads IP addresses of sessions associated with a given user. Possibly filtered by site ID and not including
	 * a selected sessions entry.
	 *
	 * @param int $user_id User ID.
	 * @param int $site_id Optional parameter to allow filtering by site ID.
	 * @param string $exempt_session_token Session token to be excluded from the results.
	 *
	 * @return string[]
	 * @since  4.1.4
	 */
	public function load_user_ip_addresses( $user_id, $site_id = 0, $exempt_session_token = '' ) {
		$_wpdb  = $this->connection;
		$query = 'SELECT DISTINCT(ip) FROM ' . $this->GetTable() . ' WHERE user_id = %d ';
		$replacements = [ $user_id ];
		if ( $site_id > 0 ) {
			$query .= ' AND sites = "all" OR FIND_IN_SET(%s, sites) > 0 ';
			array_push($replacements, $site_id);
		}
		if ( ! empty( $exempt_session_token ) ) {
			$query .= ' AND session_token != "%s" ';
			array_push( $replacements, $exempt_session_token );
		}

		$prepared_query    = $_wpdb->prepare( $query, $replacements );
		return $_wpdb->get_col( $prepared_query );
	}

}
