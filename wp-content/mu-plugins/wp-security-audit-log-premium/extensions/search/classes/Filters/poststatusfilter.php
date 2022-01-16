<?php
/**
 * Filter: Post Status Filter
 *
 * Post Status filter for search.
 *
 * @since 3.1
 * @package wsal
 * @subpackage search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WSAL_AS_Filters_PostStatusFilter' ) ) :

	/**
	 * WSAL_AS_Filters_PostStatusFilter.
	 *
	 * Post type filter class.
	 */
	class WSAL_AS_Filters_PostStatusFilter extends WSAL_AS_Filters_AbstractFilter {

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
			return esc_html__( 'Post Status', 'wp-security-audit-log' );
		}

		/**
		 * Method: Get Prefixes.
		 */
		public function GetPrefixes() {
			return array(
				'poststatus',
			);
		}

		/**
		 * Method: Returns true if this filter has suggestions for this query.
		 *
		 * @param string $query - Part of query to check.
		 */
		public function IsApplicable( $query ) {
			$post_statuses = array( 'draft', 'future', 'pending', 'private', 'publish' );

			// Search for the post status in query from available post statuses.
			$key = array_search( $query, $post_statuses );

			if ( ! empty( $key ) ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Method: Get Widgets.
		 */
		public function GetWidgets() {
			// Intialize post status widget class.
			$widget = new WSAL_AS_Filters_PostStatusWidget( $this, 'poststatus', esc_html__( 'Post Status', 'wp-security-audit-log' ) );

			// Add select options to widget.
			foreach ( array( 'draft', 'future', 'pending', 'private', 'publish' ) as $status ) {
				$text = 'publish' === $status ? 'published' : $status;
				$widget->Add( ucwords( $text ), $status );
			}
			return array( $widget );
		}

		/**
		 * @inheritdoc
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

			// Post status search condition.
			$sql = "$table_occ.id IN ( SELECT occurrence_id FROM $table_meta as meta WHERE meta.name='PostStatus' AND ( ";

			// Get the last post status.
			$last_status = end( $value );

			foreach ( $value as $post_status ) {
				if ( $last_status === $post_status ) {
					continue;
				} else {
					$sql .= "meta.value='$post_status' OR ";
				}
			}

			// Add placeholder for the last post status.
			$sql .= "meta.value='%s' ) )";

			// Check prefix.
			switch ( $prefix ) {
				case 'poststatus':
					$query->addORCondition( array( $sql => $last_status ) );
					break;
				default:
					throw new Exception( 'Unsupported filter "' . $prefix . '".' );
			}
		}

	}

endif;
