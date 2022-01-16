<?php
/**
 * Class: Username Filter
 *
 * Username Filter for search extension.
 *
 * @since 1.0.0
 * @package wsal
 * @subpackage search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WSAL_AS_Filters_UserFilter
 *
 * @package wsal
 * @subpackage search
 */
class WSAL_AS_Filters_UserNameFilter extends WSAL_AS_Filters_AbstractFilter {

	/**
	 * Instance of WpSecurityAuditLog.
	 *
	 * @var WpSecurityAuditLog
	 */
	public $wsal;

	/**
	 * Method: Constructor.
	 *
	 * @param object $search_wsal – Instance of main plugin.
	 * @since 3.1.0
	 */
	public function __construct( $search_wsal ) {
		$this->wsal = $search_wsal->wsal;
	}

	/**
	 * Method: Get Name.
	 */
	public function GetName() {
		return esc_html__( 'User', 'wp-security-audit-log' );
	}

	/**
	 * Method: Returns true if this filter has suggestions for this query.
	 *
	 * @param string $query - Part of query to check.
	 *
	 * @return bool
	 */
	public function IsApplicable( $query ) {
		global $wpdb;
		$args = array( esc_sql( $query ) . '%', esc_sql( $query ) . '%' );
		return $wpdb->count( 'SELECT COUNT(*) FROM wp_user WHERE name LIKE %s OR username LIKE %s', $args ) > 0;
	}

	/**
	 * Method: Get Prefixes.
	 */
	public function GetPrefixes() {
		return array(
			'username',
		);
	}

	/**
	 * Method: Get Widgets.
	 */
	public function GetWidgets() {
		return array( new WSAL_AS_Filters_UserNameWidget( $this, 'username', 'Username' ) );
	}

	/**
	 * Allow this filter to change the DB query according to the search value.
	 *
	 * @param WSAL_Models_OccurrenceQuery $query  - Database query for selecting occurrences.
	 * @param string                      $prefix - The filter name.
	 * @param array                       $value  - The filter value.
	 * @throws Exception Thrown when filter is unsupported.
	 */
	public function ModifyQuery( $query, $prefix, $value ) {
		// Get DB connection array.
		$connection = $this->wsal->getConnector()->getAdapter( 'Occurrence' )->get_connection();
		$connection->set_charset( $connection->dbh, 'utf8mb4', 'utf8mb4_general_ci' );

		// Tables.
		$meta       = new WSAL_Adapters_MySQL_Meta( $connection );
		$table_meta = $meta->GetTable(); // Metadata.
		$occurrence = new WSAL_Adapters_MySQL_Occurrence( $connection );
		$table_occ  = $occurrence->GetTable(); // Occurrences.

		switch ( $prefix ) {
			case 'username':
				// Search query.
				$sql = "$table_occ.id IN ( SELECT occurrence_id FROM $table_meta as meta WHERE ";

				if ( ! empty( $value ) ) {
					$user_count = count( $value );
					$sql       .= "( meta.name='CurrentUserID' AND ( ";

					foreach ( $value as $username ) {
						$user = get_user_by( 'login', $username );

						if ( ! $user ) {
							$user = get_user_by( 'slug', $username );
						}

						if ( $user ) {
							if ( $value[ $user_count - 1 ] === $username ) {
								$sql .= "meta.value='$user->ID'";
							} else {
								$sql .= "meta.value='$user->ID' OR ";
							}
						} else {
							$sql .= "meta.value=''";
						}
					}

					$sql .= ' ) )';
					$sql .= ' OR ';
					$sql .= "( meta.name='Username' AND ( ";

					foreach ( $value as $username ) {
						if ( $value[ $user_count - 1 ] === $username ) {
							$sql .= "meta.value='%s'";
						} else {
							$sql .= "meta.value='$username' OR ";
						}
					}

					$sql .= ' ) ) )';
					$query->addORCondition( array( $sql => $value[ $user_count - 1 ] ) );
				} else {
					$sql .= "( meta.name='CurrentUserID' AND meta.value='' )";
					$sql .= ' OR ';
					$sql .= "( meta.name='Username' AND meta.value='%s' ) )";
					$query->addORCondition( array( $sql => '' ) );
				}
				break;
			default:
				throw new Exception( 'Unsupported filter "' . $prefix . '".' );
		}
	}
}
