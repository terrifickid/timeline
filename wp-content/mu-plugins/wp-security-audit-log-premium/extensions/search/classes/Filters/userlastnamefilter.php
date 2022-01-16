<?php
/**
 * Filter: User Last Name Filter
 *
 * User last name filter for search.
 *
 * @since   1.1.7
 * @package wsal
 * @subpackage search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WSAL_AS_Filters_UserLastNameFilter' ) ) :

	/**
	 * WSAL_AS_Filters_UserLastNameFilter.
	 *
	 * User last name filter class.
	 *
	 * @since 1.1.7
	 */
	class WSAL_AS_Filters_UserLastNameFilter extends WSAL_AS_Filters_AbstractFilter {

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
		 *
		 * @since  1.1.7
		 */
		public function GetName() {
			return esc_html__( 'User' );
		}

		/**
		 * Method: Get Prefixes.
		 *
		 * @since  1.1.7
		 */
		public function GetPrefixes() {
			return array( 'lastname' );
		}

		/**
		 * Method: Returns true if this filter has suggestions for this query.
		 *
		 * @param string $query - Part of query to check.
		 *
		 * @return bool
		 * @since 1.1.7
		 */
		public function IsApplicable( $query ) {
			global $wpdb;
			$args = array( esc_sql( $query ) . '%', esc_sql( $query ) . '%' );
			return $wpdb->count( 'SELECT COUNT(*) FROM wp_user WHERE name LIKE %s OR username LIKE %s', $args ) > 0;
		}

		/**
		 * Method: Get Widgets.
		 *
		 * @since  1.1.7
		 */
		public function GetWidgets() {
			return array( new WSAL_AS_Filters_UserLastNameWidget( $this, 'lastname', 'Last Name' ) );
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
				case 'lastname':
					$users = array();
					foreach ( $value as $last_name ) {
						$users_array = get_users(
							array(
								'meta_key'     => 'last_name',
								'meta_value'   => $last_name,
								'fields'       => array( 'ID', 'user_login' ),
								'meta_compare' => 'LIKE',
							)
						);

						foreach ( $users_array as $user ) {
							$users[] = $user;
						}
					}

					// Search query.
					$sql = "$table_occ.id IN ( SELECT occurrence_id FROM $table_meta as meta WHERE ";

					if ( ! empty( $users ) ) {
						$user_count = count( $users );
						$sql       .= "( meta.name='CurrentUserID' AND ( ";

						foreach ( $users as $user ) {
							if ( $users[ $user_count - 1 ]->ID === $user->ID ) {
								$sql .= "meta.value='$user->ID'";
							} else {
								$sql .= "meta.value='$user->ID' OR ";
							}
						}

						$sql .= ' ) )';
						$sql .= ' OR ';
						$sql .= "( meta.name='Username' AND ( ";

						foreach ( $users as $user ) {
							if ( $users[ $user_count - 1 ]->ID === $user->ID ) {
								$sql .= "meta.value='%s'";
							} else {
								$sql .= "meta.value='$user->user_login' OR ";
							}
						}

						$sql .= ' ) ) )';
						$query->addORCondition( array( $sql => $users[ $user_count - 1 ]->user_login ) );
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

endif;
