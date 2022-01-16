<?php
/**
 * Class: IP Filter
 *
 * IP Filter for search extension.
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
 * Class WSAL_AS_Filters_IpFilter
 *
 * @package wsal
 * @subpackage search
 */
class WSAL_AS_Filters_IpFilter extends WSAL_AS_Filters_AbstractFilter {

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
		return __( 'IP', 'wp-security-audit-log' );
	}

	/**
	 * Method: Returns true if this filter has suggestions for this query.
	 *
	 * @param string $query - Part of query to check.
	 */
	public function IsApplicable( $query ) {
		$query = explode( ':', $query );

		if ( count( $query ) > 1 ) {
			// maybe IPv6?
			// TODO do IPv6 validation.
		}
		$query = explode( '.', $query[0] );

		if ( count( $query ) > 1 ) {
			// maybe IPv4?
			foreach ( $query as $part ) {
				if ( ! is_numeric( $part ) || $part < 0 || $part > 255 ) {
					return false;
				}
			}
			return true;
		}
		return false; // All validations failed.
	}

	/**
	 * Method: Get Prefixes.
	 */
	public function GetPrefixes() {
		return array(
			'ip',
		);
	}

	/**
	 * Method: Get Widgets.
	 */
	public function GetWidgets() {
		$wgt = new WSAL_AS_Filters_IpWidget( $this, 'ip', esc_html__( 'IP Address', 'wp-security-audit-log' ) );
		$wgt->SetDataLoader( array( $this, 'GetMatchingIPs' ) );
		return array( $wgt );
	}

	/**
	 * Get matching IPs for autocomplete.
	 *
	 * @param WSAL_AS_Filters_IpWidget $wgt – Filter widget.
	 */
	public function GetMatchingIPs( WSAL_AS_Filters_IpWidget $wgt ) {
		// $tmp = new WSAL_Models_Meta();
		// $ips = $tmp->getAdapter()->GetMatchingIPs();
		// foreach ( $ips as $ip ) {
		// $wgt->Add( $ip, $ip );
		// }
	}

	/**
	 * Allow this filter to change the DB query according to the search value (usually a value from GetOptions()).
	 *
	 * @param WSAL_Models_OccurrenceQuery $query  - Database query for selecting occurrences.
	 * @param string                      $prefix - The filter name (filter string prefix).
	 * @param array                       $value  - The filter value (filter string suffix).
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

		// IP search condition.
		$sql = "$table_occ.id IN ( SELECT occurrence_id FROM $table_meta as meta WHERE meta.name='ClientIP' AND ( ";

		$count = count( $value );
		foreach ( $value as $ip ) {
			if ( $value[ $count - 1 ] === $ip ) {
				$sql .= "meta.value='%s'";
			} else {
				$sql .= "meta.value='$ip' OR ";
			}
		}

		$sql .= ' ) )';

		// Check prefix.
		switch ( $prefix ) {
			case 'ip':
				$query->addORCondition( array( $sql => $value[ $count - 1 ] ) );
				break;
			default:
				throw new Exception( 'Unsupported filter "' . $prefix . '".' );
		}
	}
}
