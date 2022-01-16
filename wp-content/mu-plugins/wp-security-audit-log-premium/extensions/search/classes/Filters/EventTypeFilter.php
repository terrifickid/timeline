<?php
/**
 * Event Type Filter
 *
 * Event Type filter for search.
 *
 * @package wsal
 * @subpackage search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WSAL_AS_Filters_EventTypeFilter' ) ) :

	/**
	 * WSAL_AS_Filters_EventTypeFilter.
	 *
	 * Event Type filter class.
	 */
	class WSAL_AS_Filters_EventTypeFilter extends WSAL_AS_Filters_AbstractFilter {

		/**
		 * Get Name.
		 */
		public function GetName() {
			return __( 'Event Type', 'wp-security-audit-log' );
		}

		/**
		 * Get Prefixes.
		 */
		public function GetPrefixes() {
			return array( 'event-type' );
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
			$widget = new WSAL_AS_Filters_EventTypeWidget( $this, 'event-type', esc_html__( 'Event Type', 'wp-security-audit-log' ) );

			// Get event objects.
			$event_objects = WpSecurityAuditLog::GetInstance()->alerts->get_event_type_data();

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
			$sql = "$table_occ.id IN ( SELECT occurrence_id FROM $table_meta as meta WHERE meta.name='EventType' AND ( ";

			// Get the last event type.
			$last_value = end( $value );

			foreach ( $value as $event_type ) {
				if ( $last_value === $event_type ) {
					continue;
				} else {
					$sql .= "meta.value='$event_type' OR ";
				}
			}

			// Add placeholder for the last event type.
			$sql .= "meta.value='%s' ) )";

			// Check prefix.
			switch ( $prefix ) {
				case 'event-type':
					$query->addORCondition( array( $sql => $last_value ) );
					break;
				default:
					throw new Exception( 'Unsupported filter "' . $prefix . '".' );
			}
		}
	}

endif;
