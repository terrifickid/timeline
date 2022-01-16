<?php
/**
 * Class: Post ID Filter
 *
 * Filter for Post IDs.
 *
 * @since 3.2.3
 * @package wsal
 * @subpackage search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WSAL_AS_Filters_PostIDFilter' ) ) {
	/**
	 * WSAL_AS_Filters_PostIDFilter.
	 *
	 * Post type filter class.
	 */
	class WSAL_AS_Filters_PostIDFilter extends WSAL_AS_Filters_AbstractFilter {

		/**
		 * Method: Get Name.
		 */
		public function GetName() {
			return __( 'Post ID', 'wp-security-audit-log' );
		}

		/**
		 * Method: Returns true if this filter has suggestions for this query.
		 *
		 * @param string $query - Part of query to check.
		 */
		public function IsApplicable( $query ) {
			if ( ! is_int( $query ) ) {
				return false;
			}
			return true;
		}

		/**
		 * Method: Get Prefixes.
		 */
		public function GetPrefixes() {
			return array(
				'postid',
			);
		}

		/**
		 * Method: Get Widgets.
		 */
		public function GetWidgets() {
			return array( new WSAL_AS_Filters_PostIDWidget( $this, 'postid', esc_html__( 'Post ID', 'wp-security-audit-log' ) ) );
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

			// Post id search condition.
			$sql = "$table_occ.id IN ( SELECT occurrence_id FROM $table_meta as meta WHERE meta.name='PostID' AND ( ";

			// Get the last post id.
			$last_id = end( $value );

			foreach ( $value as $post_id ) {
				if ( $last_id === $post_id ) {
					continue;
				} else {
					$sql .= "meta.value='$post_id' OR ";
				}
			}

			// Add placeholder for the last post id.
			$sql .= "meta.value='%s' ) )";

			// Check prefix.
			switch ( $prefix ) {
				case 'postid':
					$query->addORCondition( array( $sql => $last_id ) );
					break;
				default:
					throw new Exception( 'Unsupported filter "' . $prefix . '".' );
			}
		}
	}
}
