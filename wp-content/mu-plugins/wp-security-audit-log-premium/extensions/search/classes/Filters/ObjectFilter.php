<?php
/**
 * Object Filter
 *
 * Object filter for search.
 *
 * @package wsal
 * @subpackage search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WSAL_AS_Filters_ObjectFilter' ) ) :

	/**
	 * WSAL_AS_Filters_ObjectFilter.
	 *
	 * Object filter class.
	 */
	class WSAL_AS_Filters_ObjectFilter extends WSAL_AS_Filters_AbstractFilter {

		/**
		 * Get Name.
		 */
		public function GetName() {
			return __( 'Object', 'wp-security-audit-log' );
		}

		/**
		 * Get Prefixes.
		 */
		public function GetPrefixes() {
			return array( 'object' );
		}

		/**
		 * Returns true if this filter has suggestions for this query.
		 *
		 * @param string $query - Part of query to check.
		 */
		public function IsApplicable( $query ) {
			return true;
		}

		/**
		 * Get Widgets.
		 */
		public function GetWidgets() {
			// Intialize single select widget class.
			$widget = new WSAL_AS_Filters_ObjectWidget( $this, 'object', esc_html__( 'Object', 'wp-security-audit-log' ) );

			// Get event objects.
			$event_objects = WpSecurityAuditLog::GetInstance()->alerts->get_event_objects_data();

			// Add select options to widget.
			foreach ( $event_objects as $key => $role ) {
				$widget->Add( $role, $key );
			}

			return array( $widget );
		}

		/**
		 * @inheritdoc
		 */
		public function ModifyQuery( $query, $prefix, $value ) {
			// Get DB connection array.
			$connection = WpSecurityAuditLog::GetInstance()->getConnector()->getAdapter( 'Occurrence' )->get_connection();
			$connection->set_charset( $connection->dbh, 'utf8mb4', 'utf8mb4_general_ci' );

			// Tables.
			$meta       = new WSAL_Adapters_MySQL_Meta( $connection );
			$table_meta = $meta->GetTable(); // Metadata.
			$occurrence = new WSAL_Adapters_MySQL_Occurrence( $connection );
			$table_occ  = $occurrence->GetTable(); // Occurrences.

			// Object search condition.
			$sql = "$table_occ.id IN ( SELECT occurrence_id FROM $table_meta as meta WHERE meta.name='Object' AND ( ";

			// Get the last Object.
			$last_object = end( $value );

			foreach ( $value as $object ) {
				if ( $last_object === $object ) {
					continue;
				} else {
					$sql .= "meta.value='$object' OR ";
				}
			}

			// Add placeholder for the last Object.
			$sql .= "meta.value='%s' ) )";

			// Check prefix.
			switch ( $prefix ) {
				case 'object':
					$query->addORCondition( array( $sql => $last_object ) );
					break;
				default:
					throw new Exception( 'Unsupported filter "' . $prefix . '".' );
			}
		}
	}

endif;
