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

if ( ! class_exists( 'WSAL_AS_Filters_SiteFilter' ) ) :

	/**
	 * WSAL_AS_Filters_SitesFilter.
	 *
	 * Object filter class.
	 */
	class WSAL_AS_Filters_SiteFilter extends WSAL_AS_Filters_AbstractFilter {

		/**
		 * Get Name.
		 */
		public function GetName() {
			return __( 'Site', 'wp-security-audit-log' );
		}

		/**
		 * Get Prefixes.
		 */
		public function GetPrefixes() {
			return array( 'site' );
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
			// bail early if this is not a multisite.
			if ( ! is_multisite() ) {
				return;
			}
			// Intialize single select widget class.
			$widget = new WSAL_AS_Filters_SiteWidget( $this, 'site', esc_html__( 'Sites', 'wp-security-audit-log' ) );

			// Get event objects.
			// TODO: consider making this a transient so we don't need a limit.
			$sites = get_sites(
				array(
					'number' => 15,
					'fields' => 'ids',
				)
			);

			// Add select options to widget.
			foreach ( $sites as $site ) {
				$details = get_blog_details( $site );
				$name    = $details->blogname;
				$widget->Add( $name, $site . ': ' . $name );
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
			$occurrence = new WSAL_Adapters_MySQL_Occurrence( $connection );
			$table_occ  = $occurrence->GetTable(); // Occurrences.

			// Object search condition.
			$sql = "$table_occ.id IN ( SELECT id FROM $table_occ as meta WHERE ( ";

			// Get just the 'blog id' from each array item.
			foreach ( $value as $key => $blog ) {
				preg_match( '/^([1-9]*?):/', $blog, $matches );
				if ( isset( $matches[1] ) ) {
					$value[ $key ] = $matches[1];
				} else {
					// we didn't get a match so unset this value.
					unset( $value[ $key ] );
				}
			}


			// Get the last Site.
			$last_site = end( $value );

			foreach ( $value as $site ) {
				if ( $last_site === $site ) {
					continue;
				} else {
					$sql .= "meta.site_id='$site' OR ";
				}
			}

			// Add placeholder for the last Object.
			$sql .= "meta.site_id='%s' ) )";

			// Check prefix.
			switch ( $prefix ) {
				case 'site':
					$query->addORCondition( array( $sql => $last_site ) );
					break;
				default:
					throw new Exception( 'Unsupported filter "' . $prefix . '".' );
			}
		}
	}

endif;
