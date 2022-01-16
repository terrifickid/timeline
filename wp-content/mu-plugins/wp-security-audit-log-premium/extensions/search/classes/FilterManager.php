<?php
/**
 * Class: Filter Manager
 *
 * Filter Manager for search extension.
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
 * Class WSAL_AS_FilterManager
 *
 * @package wsal
 * @subpackage search
 */
class WSAL_AS_FilterManager {

	/**
	 * Array of filters - WSAL_AS_Filters_AbstractFilter[]
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Widget cache.
	 *
	 * @var WSAL_AS_Filters_AbstractWidget[]
	 */
	protected $widgets = null;

	/**
	 * Instance of WSAL_SearchExtension.
	 *
	 * @var object
	 */
	protected $_plugin;

	/**
	 * Method: Constructor.
	 *
	 * @param object $plugin - Instance of WSAL_SearchExtension.
	 * @since 1.0.0
	 */
	public function __construct( WSAL_SearchExtension $plugin ) {
		$this->_plugin = $plugin;

		// Load filters.
		foreach ( glob( dirname( __FILE__ ) . '/Filters/*.php' ) as $file ) {
			$this->AddFromFile( $file );
		}

		add_action( 'wsal_audit_log_column_header', array( $this, 'display_filters' ), 10, 1 );
		add_action( 'wsal_search_filters_list', array( $this, 'display_search_filters_list' ), 10, 1 );
	}

	/**
	 * Add new filter from file inside autoloader path.
	 *
	 * @param string $file Path to file.
	 */
	public function AddFromFile( $file ) {
		$this->AddFromClass( $this->_plugin->wsal->GetClassFileClassName( $file ) );
	}

	/**
	 * Add new filter given class name.
	 *
	 * @param string $class Class name.
	 */
	public function AddFromClass( $class ) {
		if ( is_subclass_of( $class, 'WSAL_AS_Filters_AbstractFilter' ) ) {
			$this->AddInstance( new $class( $this->_plugin ) );
		}
	}

	/**
	 * Add newly created filter to list.
	 *
	 * @param WSAL_AS_Filters_AbstractFilter $filter The new view.
	 */
	public function AddInstance( WSAL_AS_Filters_AbstractFilter $filter ) {
		$this->filters[] = $filter;
		// Reset widget cache.
		if ( $this->widgets == null ) {
			$this->widgets = null;
		}
	}

	/**
	 * Get filters.
	 *
	 * @return WSAL_AS_Filters_AbstractFilter[]
	 */
	public function GetFilters() {
		return $this->filters;
	}

	/**
	 * Gets widgets grouped in arrays with widget class as key.
	 *
	 * @return WSAL_AS_Filters_AbstractWidget[][]
	 */
	public function GetWidgets() {
		if ( $this->widgets == null ) {
			$this->widgets = array();
			foreach ( $this->filters as $filter ) {
				$get_widget = $filter->GetWidgets();
				if ( ! empty( $get_widget ) ) {
					foreach ( $filter->GetWidgets() as $widget ) {
						$class = get_class( $widget );
						if ( ! isset( $this->widgets[ $class ] ) ) {
							$this->widgets[ $class ] = array();
						}
						$this->widgets[ $class ][] = $widget;
					}
				}
			}
		}
		return $this->widgets;
	}

	/**
	 * Find widget given filter and widget name.
	 *
	 * @param string $filter_name - Filter name.
	 * @param string $widget_name - Widget name.
	 * @return WSAL_AS_Filters_AbstractWidget|null
	 */
	public function FindWidget( $filter_name, $widget_name ) {
		foreach ( $this->filters as $filter ) {
			if ( $filter->GetSafeName() == $filter_name ) {
				foreach ( $filter->GetWidgets() as $widget ) {
					if ( $widget->GetSafeName() == $widget_name ) {
						return $widget;
					}
				}
			}
		}
		return null;
	}

	/**
	 * Find a filter given a supported prefix.
	 *
	 * @param string $prefix Filter prefix.
	 * @return WSAL_AS_Filters_AbstractFilter|null
	 */
	public function FindFilterByPrefix( $prefix ) {
		foreach ( $this->filters as $filter ) {
			if ( in_array( $prefix, $filter->GetPrefixes() ) ) {
				return $filter;
			}
		}
		return null;
	}

	/**
	 * Display column filters.
	 *
	 * @param string $column_key – Column key.
	 * @return string
	 * @since 3.2.3
	 */
	public function display_filters( $column_key ) {

		// For WSAL this is being moved elsewhere so returning early.
		if ( isset( $column_key ) ) {
			return;
		}
		/**
		 * Bail early if we have a match against this list of EXCLUDES.
		 *
		 * NOTE: Consider making this a filterable property.
		 */
		if ( in_array( $column_key, array( 'code', 'data', 'site' ), true ) ) {
			return;
		}

		// Sorting filter icon.
		echo '<a href="javascript:;" id="wsal-search-filter-' . esc_attr( $column_key ) . '" class="wsal-search-filter dashicons dashicons-filter"></a>';

		// Filter container.
		echo '<div id="wsal-filter-container-' . esc_attr( $column_key ) . '" class="wsal-filter-container">';

		// Close filter button.
		echo '<span data-container-id="wsal-filter-container-' . esc_attr( $column_key ) . '" class="dashicons dashicons-no-alt wsal-filter-container-close"></span>';

		/*
		 * Render the html for the given form col.
		 *
		 * TODO: Make this via a render factory.
		 */
		switch ( $column_key ) {
			case 'type':
				// Add event code filter widget.
				$filter = $this->FindFilterByPrefix( 'event' );

				// If filter is found, then add to container.
				if ( $filter ) {
					$filter->Render();
				}
				echo '<p class="description">';
				echo wp_kses( __( 'Refer to the <a href="https://wpactivitylog.com/support/kb/list-wordpress-activity-log-event-ids/" target="_blank">list of Event IDs</a> for reference.', 'wp-security-audit-log' ), $this->_plugin->wsal->allowed_html_tags );
				echo '</p>';
				break;

			case 'crtd':
				// Add date filter widget.
				$date = $this->FindFilterByPrefix( 'from' );

				// If from date filter is found, then add to container.
				if ( $date ) {
					$date->Render();
				}
				break;

			case 'user':
				// Add username filter widget.
				$username = $this->FindFilterByPrefix( 'username' );

				// If username filter is found, then add to container.
				if ( $username ) {
					$username->Render();
				}

				// Add firstname filter widget.
				$firstname = $this->FindFilterByPrefix( 'firstname' );

				// If firstname filter is found, then add to container.
				if ( $firstname ) {
					$firstname->Render();
				}

				// Add lastname filter widget.
				$lastname = $this->FindFilterByPrefix( 'lastname' );

				// If lastname filter is found, then add to container.
				if ( $lastname ) {
					$lastname->Render();
				}

				// Add userrole filter widget.
				$userrole = $this->FindFilterByPrefix( 'userrole' );

				// If userrole filter is found, then add to container.
				if ( $userrole ) {
					$userrole->Render();
				}
				break;

			case 'mesg':
				// Add post_status filter widget.
				$post_status = $this->FindFilterByPrefix( 'poststatus' );

				// If post_status filter is found, then add to container.
				if ( $post_status ) {
					$post_status->Render();
				}

				// Add post_type filter widget.
				$post_type = $this->FindFilterByPrefix( 'posttype' );

				// If post_type filter is found, then add to container.
				if ( $post_type ) {
					$post_type->Render();
				}

				// Add post_id filter widget.
				$post_id = $this->FindFilterByPrefix( 'postid' );

				// If post_id filter is found, then add to container.
				if ( $post_id ) {
					$post_id->Render();
				}

				// Add post_name filter widget.
				$post_name = $this->FindFilterByPrefix( 'postname' );

				// If post_name filter is found, then add to container.
				if ( $post_name ) {
					$post_name->Render();
				}
				break;

			case 'scip':
				// Add ip filter widget.
				$ip = $this->FindFilterByPrefix( 'ip' );

				// If ip filter is found, then add to container.
				if ( $ip ) {
					$ip->Render();
				}
				break;

			case 'object':
				// Add object filter widget.
				$object = $this->FindFilterByPrefix( 'object' );

				// If object filter is found, then add to container.
				if ( $object ) {
					$object->Render();
				}
				break;

			case 'event_type':
				// Add event type filter widget.
				$event_type = $this->FindFilterByPrefix( 'event-type' );

				// If event type filter is found, then add to container.
				if ( $event_type ) {
					$event_type->Render();
				}
				break;

			case 'code':
				// Add code (Severity) filter widget.
				$code = $this->FindFilterByPrefix( 'code' );

				// If code filter is found, then add to container.
				if ( $code ) {
					$code->Render();
				}
				break;

			case 'site':
				// Add code (Severity) filter widget.
				$site = $this->FindFilterByPrefix( 'site' );

				// If code filter is found, then add to container.
				if ( $site ) {
					$site->Render();
				}
				break;

			default:
		}

		echo '</div>';
	}

	/**
	 * Display list of search filters, load, and save search
	 * buttons and their pop-ups.
	 *
	 * @param string $nav_position – Table navigation position.
	 */
	public function display_search_filters_list( $nav_position ) {
		if ( empty( $nav_position ) ) {
			return;
		}

		if ( 'top' === $nav_position ) :
			$saved_search = $this->_plugin->wsal->GetGlobalSetting( 'save_search', array() );
			?>
			<div class="wsal-as-filter-list no-filters"></div>
			<!-- Filters List -->
			<?php

			/*
			 * This is a notice which shows when the filters have been changed.
			 *
			 * Check if the user has permanently disabled it.
			 */
			if ( ! $this->_plugin->wsal->views->views[0]->IsNoticeDismissed( 'filters-changed-permanent-hide' ) ) {
				?>
				<div class="wsal-filter-notice-zone" style="display:none;">
					<p><span class="wsal-notice-message"></span> <a id="wsal-filter-notice-permanant-dismiss" href="javascript:;"><?php esc_html_e( 'Do not show this message again', 'wp-security-audit-log' ); ?></a></p>
					<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'wp-security-audit-log' ); ?></span></button>
				</div>
				<!-- Filters Notices -->
				<?php
			}
			?>
			<div class="load-search-container">
				<button type="button" id="load-search-btn" class="button-secondary button wsal-button" <?php echo empty( $saved_search ) ? 'disabled' : false; ?>>
					<?php esc_html_e( 'Load Search & Filters', 'wp-security-audit-log' ); ?>
				</button>
				<div class="wsal-load-popup" style="display:none">
					<a class="close" href="javascript;" title="<?php esc_attr_e( 'Remove', 'wp-security-audit-log' ); ?>">&times;</a>
					<div class="wsal-load-result-list"></div>
				</div>
				<?php wp_nonce_field( 'load-saved-search-action', 'load_saved_search_field' ); ?>
			</div>
			<!-- Load Search & Filters Container -->

			<div class="save-search-container">
				<a href="javascript:;" id="save-search-btn" class="button wsal-button">
					<?php esc_html_e( 'Save Search & Filters', 'wp-security-audit-log' ); ?>
					<img src="<?php echo esc_url( WpSecurityAuditLog::GetInstance()->GetBaseUrl() . '/img/icons/save-search.svg' ); ?>" class="save-search-icon" />
				</a>
				<div class="wsal-save-popup" style="display: none;">
					<input name="wsal-save-search-name" id="wsal-save-search-name" placeholder="Search Save Name" />
					<span id="wsal-save-search-error"><?php esc_html_e( '* Invalid Name', 'wp-security-audit-log' ); ?></span>
					<p class="description">
						<?php esc_html_e( 'Name can only be 12 characters long and only letters, numbers and underscore are allowed.', 'wp-security-audit-log' ); ?>
					</p>
					<p class="description">
						<button type="submit" id="wsal-save-search-btn" class="button-primary"><?php esc_html_e( 'Save', 'wp-security-audit-log' ); ?></button>
					</p>
				</div>
			</div>
			<div class="wsal-button-grouping">
				<div class="filter-results-button">
					<button id="filter-container-toggle" class="button wsal-button dashicons-before dashicons-filter" type="button"><?php esc_html_e( 'Filter View', 'wp-security-audit-log' ); ?></button>
				</div>
			</div>
			<!-- Save Search & Filters Container -->
			<div id="wsal-filters-container" style="display:none">
				<div class="filter-col">
					<?php
					// Add event code filter widget.
					$filter = $this->FindFilterByPrefix( 'event' );

					// If filter is found, then add to container.
					if ( $filter ) {
						?>
						<div class="filter-wrap">
							<?php $filter->Render(); ?>
							<p class="description"><?php echo wp_kses( __( 'Refer to the <a href="https://wpactivitylog.com/support/kb/list-wordpress-activity-log-event-ids/" target="_blank" rel="nofollow noopener">list of Event IDs</a> for reference.', 'wp-security-audit-log' ), $this->_plugin->wsal->allowed_html_tags ); ?></p>
						</div>
						<?php
					}
					// Add object filter widget.
					$object = $this->FindFilterByPrefix( 'object' );

					// If object filter is found, then add to container.
					if ( $object ) {
						?>
						<div class="filter-wrap">
							<?php $object->Render(); ?>
							<p class="description"><?php echo wp_kses( __( 'Refer to the <a href="https://wpactivitylog.com/support/kb/list-wordpress-activity-log-event-ids/"  target="_blank" rel="nofollow noopener">metadata in the activity log</a> for reference.', 'wp-security-audit-log' ), $this->_plugin->wsal->allowed_html_tags ); ?></p>
						</div>
						<?php
					}
					// Add event type filter widget.
					$event_type = $this->FindFilterByPrefix( 'event-type' );

					// If event type filter is found, then add to container.
					if ( $event_type ) {
						?>
						<div class="filter-wrap">
							<?php $event_type->Render(); ?>
							<p class="description"><?php echo wp_kses( __( 'Refer to the <a href="https://wpactivitylog.com/support/kb/severity-levels-wordpress-activity-log/" target="_blank" rel="nofollow noopener">severity levels in the activity log</a> for reference.', 'wp-security-audit-log' ), $this->_plugin->wsal->allowed_html_tags ); ?></p>
						</div>
						<?php
					}

					// Add code (Severity) filter widget.
					$code = $this->FindFilterByPrefix( 'code' );

					// If code filter is found, then add to container.
					if ( $code ) {
						?>
						<div class="filter-wrap">
							<?php $code->Render(); ?>
							<p class="description"><?php echo wp_kses( __( 'Refer to the <a href="https://wpactivitylog.com/support/kb/list-wordpress-audit-trail-alerts/" target="_blank" rel="nofollow noopener">list of Event IDs</a> for reference.', 'wp-security-audit-log' ), $this->_plugin->wsal->allowed_html_tags ); ?></p>
						</div>
						<?php
					}
					?>
				</div>
				<div class="filter-col">
					<?php
					// Data for generating and redering users filters with.
					$user_filters = array(
						'username'  => array(
							'display'     => __( 'Username', 'wp-security-audit-log' ),
							'description' => __( 'Filter by username', 'wp-security-audit-log' ),
						),
						'firstname' => array(
							'display'     => __( 'First Name', 'wp-security-audit-log' ),
							'description' => __( 'Filter by user first name', 'wp-security-audit-log' ),
						),
						'lastname'  => array(
							'display'     => __( 'Last Name', 'wp-security-audit-log' ),
							'description' => __( 'Filter by user last name', 'wp-security-audit-log' ),
						),
						'userrole'  => array(
							'display'     => __( 'User Role', 'wp-security-audit-log' ),
							'description' => __( 'Filter by user roles', 'wp-security-audit-log' ),
						),
					);
					$this->render_filter_groups( __( 'User Filters', 'wp-security-audit-log' ), 'user', $user_filters );
					// The data for fetching and rendering posts filters with.
					$post_filters = array(
						'poststatus' => array(
							'display'     => __( 'Post Status', 'wp-security-audit-log' ),
							'description' => __( 'Filter by post status', 'wp-security-audit-log' ),
						),
						'posttype'   => array(
							'display'     => __( 'Post Type', 'wp-security-audit-log' ),
							'description' => __( 'Filter by post type', 'wp-security-audit-log' ),
						),
						'postid'     => array(
							'display'     => __( 'Post ID', 'wp-security-audit-log' ),
							'description' => __( 'Filter by post ID', 'wp-security-audit-log' ),
						),
						'postname'   => array(
							'display'     => __( 'Post Name', 'wp-security-audit-log' ),
							'description' => __( 'Filter by post name', 'wp-security-audit-log' ),
						),
					);
					$this->render_filter_groups( __( 'Post Filters', 'wp-security-audit-log' ), 'post', $post_filters );

					// Show site alerts widget.
					// NOTE: this is shown when the filter IS true.
					if ( is_multisite() && get_current_blog_id() == 1 && apply_filters( 'search_extensition_active', false ) ) {

						$curr = WpSecurityAuditLog::GetInstance()->settings->get_view_site_id();
						?>
						<div class="filter-wrap">
							<label for="wsal-ssas"><?php esc_html_e( 'Select Site to view', 'wp-security-audit-log' ); ?></label>
							<div class="wsal-widget-container">
								<?php
								if ( $this->get_site_count() > 15 ) {
									$curr = $curr ? get_blog_details( $curr ) : null;
									$curr = $curr ? ( $curr->blogname . ' (' . $curr->domain . ')' ) : 'All Sites';
									?>
									<input type="text" class="wsal-ssas" value="<?php echo esc_attr( $curr ); ?>"/>
									<?php
								} else {
									// Add code (Severity) filter widget.
									$site = $this->FindFilterByPrefix( 'site' );

									// If code filter is found, then add to container.
									if ( $site ) {
										?>
										<div class="filter-wrap">
											<?php $site->Render(); ?>
											<p class="description"><?php echo wp_kses( __( 'Refer to the <a href="https://wpactivitylog.com/support/kb/list-wordpress-activity-log-event-ids/" target="_blank" rel="nofollow noopener">list of Event IDs</a> for reference.', 'wp-security-audit-log' ), $this->_plugin->wsal->allowed_html_tags ); ?></p>
										</div>
										<?php
									}
								}
								?>
							</div>
							<p class="description"><?php echo wp_kses( __( 'Select A Specific Site from the Network', 'wp-security-audit-log' ), $this->_plugin->wsal->allowed_html_tags ); ?></p>
						</div>
						<?php
					}
					?>
				</div>
				<div class="filter-col filter-dates-col">
					<?php
					// Add date filter widget.
					$date = $this->FindFilterByPrefix( 'from' );

					// If from date filter is found, then add to container.
					if ( $date ) {
						$date->Render();
					}
					// Add ip filter widget.
					$ip = $this->FindFilterByPrefix( 'ip' );

					// If ip filter is found, then add to container.
					if ( $ip ) {
						?>
						<div class="filter-wrap">
							<?php $ip->Render(); ?>
							<p class="description"><?php echo wp_kses( __( 'Enter an IP address to filter', 'wp-security-audit-log' ), $this->_plugin->wsal->allowed_html_tags ); ?></p>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			<?php
		endif;
	}

	/**
	 * Renders an entire group of filters in a single area that is paired with
	 * a select box and some javascript show/hide.
	 *
	 * @method render_filter_groups
	 * @since  4.0.0
	 * @param  string $title Title to use as a lable above select box.
	 * @param  string $slug  The slug to use for identifying groups.
	 * @param  array  $group An array containing all the group data. An array with a handle containing an array of strings - `display` and `description`.
	 */
	public function render_filter_groups( $title = '', $slug = '', $group = array() ) {
		?>
		<div class="wsal-filters-group" id="wsal-user-filters">
			<div class="wsal-filter-group-select">
				<label for="wsal-<?php echo esc_attr( 'slug' ); ?>-filters-select"><?php echo esc_html( $title ); ?></label>
				<select id="wsal-<?php echo esc_attr( 'slug' ); ?>-filters-select">
					<?php
					foreach ( $group as $handle => $strings ) {
						// Render item.
						echo '<option value="' . esc_attr( $handle ) . '">' . esc_html( $strings['display'] ) . '</option>';
					}
					?>
				</select>
			</div>
			<div class="wsal-filter-group-inputs">
				<?php
				foreach ( $group as $handle => $strings ) {
					// Add username filter widget.
					$filter = $this->FindFilterByPrefix( $handle );

					// If username filter is found, then add to container.
					if ( $filter ) {
						?>
						<div class="filter-wrap wsal-filter-wrap-<?php echo sanitize_html_class( $handle ); ?>">
							<?php $filter->Render(); ?>
							<?php
							if ( isset( $strings['description'] ) && '' !== $strings['description'] ) {
								?>
								<p class="description"><?php echo wp_kses( $strings['description'], $this->_plugin->wsal->allowed_html_tags ); ?></p>
								<?php
							}
							?>
						</div>
					<?php
					}
				}
				?>
			</div>
			<div class="clearfix"></div>
		</div>
		<?php
	}

	/**
	 * Method: The number of sites on the network.
	 *
	 * @return int
	 */
	public function get_site_count() {
		global $wpdb;
		$sql = 'SELECT COUNT(*) FROM ' . $wpdb->blogs;
		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Method: Object with keys: blog_id, blogname, domain.
	 *
	 * @param int|null $limit - Maximum number of sites to return (null = no limit).
	 * @return object
	 */
	public function get_sites( $limit = null ) {
		global $wpdb;
		// Build query.
		$sql = 'SELECT blog_id, domain FROM ' . $wpdb->blogs;
		if ( ! is_null( $limit ) ) {
			$sql .= ' LIMIT ' . $limit;
		}

		// Execute query.
		$res = $wpdb->get_results( $sql );

		// Modify result.
		foreach ( $res as $row ) {
			$row->blogname = get_blog_option( $row->blog_id, 'blogname' );
		}

		// Return result.
		return $res;
	}
}
