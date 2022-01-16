<?php
/**
 * Class: Code Filter
 *
 * IP Filter for search extension.
 *
 * @since 3.5.1
 * @package wsal
 * @subpackage search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the filters for the code (Severity) items in the admin list.
 *
 * @since 3.5.1
 */
class WSAL_AS_Filters_CodeFilter extends WSAL_AS_Filters_AbstractFilter {

	/**
	 * @var WpSecurityAuditLog
	 */
	private $wsal;

	/**
	 * Method: Constructor.
	 *
	 * @param object $search_wsal – Instance of main plugin.
	 * @since 3.5.1
	 */
	public function __construct( $search_wsal ) {
		$this->wsal = $search_wsal->wsal;
	}

	/**
	 * Method: Get Name.
	 */
	public function GetName() {
		return __( 'Severity', 'wp-security-audit-log' );
	}

	/**
	 * Method: Returns true if this filter has suggestions for this query.
	 *
	 * @param string $query - Part of query to check.
	 */
	public function IsApplicable( $query ) {
		// NOTE: I'm not certain this is the correct test method.
		return strtolower( substr( trim( $query ), 0, 4 ) ) == 'code';
	}

	/**
	 * Method: Get Prefixes.
	 */
	public function GetPrefixes() {
		return array(
			'code',
		);
	}

	/**
	 * Method: Get Widgets.
	 */
	public function GetWidgets() {
		$widget = new WSAL_AS_Filters_CodeWidget( $this, 'code', esc_html__( 'Severity', 'wp-security-audit-log' ) );
		// TODO: Add these from a helper method where they are already translated.
		$options = array(
			'CRITICAL'      => __( 'Critical', 'wp-security-audit-log' ),
			'HIGH'          => __( 'High', 'wp-security-audit-log' ),
			'MEDIUM'        => __( 'Medium', 'wp-security-audit-log' ),
			'LOW'           => __( 'Low', 'wp-security-audit-log' ),
			'INFORMATIONAL' => __( 'Info', 'wp-security-audit-log' ),
		);
		foreach ( $options as $key => $option ) {
			$widget->Add( $option, $key );
		}

		return array( $widget );
	}

	/**
	 * @inheritdoc
	 */
	public function ModifyQuery( $query, $prefix, $value ) {
		// NOTE: This was adapted from the IP filter.
		// Get DB connection array.
		$connection = $this->wsal->getConnector()->getAdapter( 'Occurrence' )->get_connection();
		$connection->set_charset( $connection->dbh, 'utf8mb4', 'utf8mb4_general_ci' );

		// Tables.
		$meta       = new WSAL_Adapters_MySQL_Meta( $connection );
		$table_meta = $meta->GetTable(); // Metadata.
		$occurrence = new WSAL_Adapters_MySQL_Occurrence( $connection );
		$table_occ  = $occurrence->GetTable(); // Occurrences.

		// IP search condition.
		$sql = "$table_occ.id IN ( SELECT occurrence_id FROM $table_meta as meta WHERE meta.name='Severity' AND ( ";

		$count = count( $value );
		foreach ( $value as $key => $code ) {
			$code          = $this->convert_to_int( $code );
			$value[ $key ] = $code;
			if ( $value[ $count - 1 ] === $code ) {
				$sql .= "meta.value='%s'";
			} else {
				$sql .= "meta.value='$code' OR ";
			}
		}

		$sql .= ' ) )';

		// Check prefix.
		switch ( $prefix ) {
			case 'code':
				$query->addORCondition( array( $sql => $value[ $count - 1 ] ) );
				break;
			default:
				throw new Exception( 'Unsupported filter "' . $prefix . '".' );
		}
	}

	/**
	 * Converts string representing severity in the UI filters to and integer suitable to match the data in the DB.
	 *
	 * Defaults to code for INFO severity.
	 *
	 * @param string $severity_string A string representing the severity in the UI.
	 *
	 * @return string
	 * @since  3.5.1
	 */
	private function convert_to_int( $severity_string ) {
		//  try the given string first (this should work for the legacy PHP error based severity codes)
		$constant = $this->wsal->constants->GetConstantBy( 'name', $severity_string );
		if ( null === $constant ) {
			//  no match, let's try to prefix with "WSAL_" as this should match all the remaining cases
			$constant = $this->wsal->constants->GetConstantBy( 'name', 'WSAL_' . $severity_string );
		}

		if ( null === $constant ) {
			//  fallback
			$constant = $this->wsal->constants->GetConstantBy( 'name', 'WSAL_INFORMATIONAL' );
		}

		if ( null === $constant ) {
			//  still nothing? default to INFO (200): Interesting events.
			return '200';
		}

		return (string) $constant->value;
	}
}
