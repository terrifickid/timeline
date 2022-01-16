<?php

class WSAL_UserSessions_View_Sessions {

	/**
	 * Stores all the tracked sessions from custom table so they can be
	 * handled/sorted/processed.
	 *
	 * @var array
	 */
	private $all_tracked_sessions = array();

	/**
	 * The array of user sessions to output in the loop.
	 *
	 * @var array
	 */
	public $user_sessions = array();

	/**
	 * Slug of this tab in the sessions pages.
	 *
	 * @var string
	 */
	public static $slug = 'sessions';

	/**
	 * @var WpSecurityAuditLog
	 */
	private $plugin;

	/**
	 * Returns a title to use for this tab/page.
	 *
	 * @method get_title
	 * @since  4.1.0
	 * @return string
	 */
	public function get_title() {
		return __( 'Logged In Users', 'wp-security-audito-log' );
	}

	public function __construct( WpSecurityAuditLog $plugin ) {
		$this->plugin = $plugin;

		$this->register_usersessions_tab();

		// Listen for session search submit.
		add_action( 'admin_post_wsal_sessions_search', array( $this, 'sessions_search_form' ) );

	}

	public function setup_session_data( $blog_id = 0 ) {
		// get all the sessions.
		$adapter                    = WSAL_UserSessions_Plugin::get_sessions_adapter();
		$this->all_tracked_sessions = $adapter->load_all_sessions_ordered_by_user_id( $blog_id );

		$all_sessions_cache_key = md5( wp_json_encode( $this->all_tracked_sessions ) );
		// short cache on this of 5 mins.
		set_transient( 'wsal_usersessions_cache_sum', $all_sessions_cache_key, 300 );

		$this->user_sessions = array();
		foreach ( $this->all_tracked_sessions as $tracked_session ) {
			$this->user_sessions[ $tracked_session->user_id ][] = $tracked_session;
		}
	}

	/**
	 * Renders a button to fetch session data.
	 *
	 * This is conditional and only shows on pages where a site is likely to
	 * have a large number of users that could cause timeouts.
	 *
	 * @method render_fetch_data_button
	 * @since  4.1.0
	 */
	public function render_fetch_data_button() {
		$nonce = wp_create_nonce( 'fetch_user_session_event_data' );
		?>
		<button class="button-primary wsal_fetch_users_event_data" type="button" data-nonce="<?php echo esc_attr( $nonce ); ?>"><?php esc_html_e( 'Retrieve user data', 'wp-security-audit-log' ); ?></button>
		<span class="fetch-progress-spinner spinner"></span>
		<?php
	}

	/**
	 * Renders a button terminate found sessions.
	 *
	 * @method render_terminate_search_result_sessions_button
	 * @param  array $search_results_user_data - possible data found during a search (optional).
	 * @since 4.3.4
	 */
	public function render_terminate_search_result_sessions_button( $search_results_user_data = [] ) {
		if ( isset( $_REQUEST['type'] ) && isset( $_REQUEST['keyword'] ) && ! empty( $search_results_user_data ) ) { ?>
			<button class="button-primary terminate-session-for-query type="button" data-users-to-terminate='<?php echo json_encode( $search_results_user_data ); ?>'><?php esc_html_e( 'Terminate all sessions that match this search criteria', 'wp-security-audit-log' ); ?></button>
			<span class="terminate-query-progress"></span>
		<?php
		}
	}

	/**
	 * Render this page or tab html contents.
	 *
	 * @method render
	 * @since  4.1.0
	 */
	public function render() {

		/**
		 * Loads all the sessions data we may need for rendering this page into
		 * properties.
		 */
		$current_blog_id = (int) $this->get_view_site_id();
		$this->setup_session_data( $current_blog_id );

		/**
		 * Performs the search filtering and displays a message about results.
		 */
		$search_results = array();
		if ( filter_input( INPUT_GET, 'keyword', FILTER_SANITIZE_STRING ) ) {
			try {
				// perform the search filtering.
				$search_results = $this->sessions_search();
				if ( empty( $search_results ) ) {
					$results_notice_text = ( filter_input( INPUT_GET, 'sessions-terminated', FILTER_SANITIZE_STRING ) ) ? esc_html__( 'Sessions successfully terminated', 'wp-security-audit-log' ) : esc_html__( 'No search results were found.', 'wp-security-audit-log' );
					// No search results found.
					?>
					<div class="updated">
						<p><?php echo $results_notice_text; ?></p>
					</div>
					<?php
				} else {
					// Dispaly a message with the search term.
					?>
					<div class="updated">
						<p>
							<?php esc_html_e( 'Showing results for ', 'wp-security-audit-log' ); ?>
							<?php echo filter_input( INPUT_GET, 'type', FILTER_SANITIZE_STRING ); ?>
							<strong><?php echo filter_input( INPUT_GET, 'keyword', FILTER_SANITIZE_STRING ); ?></strong>
						</p>
					</div>
					<?php
				}
			} catch ( Exception $ex ) {
				// catching a search failure error.
				?>
				<div class="error"><p><?php esc_html_e( 'Error: ', 'wp-security-audit-log' ); ?><?php echo esc_html( $ex->getMessage() ); ?></p></div>
				<?php
			}
		}

		// Get the type of name to display from settings.
		$type_name = $this->plugin->settings()->get_type_username();
		if ( 'display_name' === $type_name || 'first_last_name' === $type_name ) {
			$name_column = __( 'User', 'wp-security-audit-log' );
		} elseif ( 'username' === $type_name ) {
			$name_column = __( 'Username', 'wp-security-audit-log' );
		}

		$columns = array(
			'username'      => $name_column,
			'creation_time' => esc_html__( 'Created', 'wp-security-audit-log' ),
			'expiry_time'   => esc_html__( 'Expires', 'wp-security-audit-log' ),
			'ip'            => esc_html__( 'Source IP', 'wp-security-audit-log' ),
			'alert'         => esc_html__( 'Last Event', 'wp-security-audit-log' ),
			'action'        => esc_html__( 'Actions', 'wp-security-audit-log' ),
		);

		// Verify sessions form submission nonce.
		if ( isset( $_GET['wsal-sessions-form'] ) ) {
			check_admin_referer( 'wsal-sessions-form', 'wsal-sessions-form' );
		}

		// @codingStandardsIgnoreStart
		$sorted  = array();
		$spp     = ! empty( $_GET['sessions_per_page'] ) ? absint( $_GET['sessions_per_page'] ) : 10;
		$paged   = ! empty( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$offset  = absint( ( $paged - 1 ) * $spp );
		$orderby = ! empty( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'creation_time';
		$order   = ! empty( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'desc';
		$search_results_termination_data = [];
		// @codingStandardsIgnoreEnd

		if ( empty( $search_results ) ) {
			// with no results sessions list should be emptied.
			if ( ! empty( filter_input( INPUT_GET, 'keyword', FILTER_SANITIZE_STRING ) ) ) {
				$this->user_sessions = array();
			}
			$results = array_slice( $this->user_sessions, $offset, $spp );
		} else {
			$results = array_slice( $search_results, $offset, $spp );
		}

		foreach ( $results as $user_id => $user_session ) {
			reset( $user_session );
			$session  = current( $user_session );
			$sorted[] = $session->$orderby;

			// Make a note of found user IDs and their sessions in case we terminate.
			if ( ! empty( $search_results ) ) {
				$user_session_data = [
					$user_session[0]->user_id,
					$user_session[0]->session_token,
					wp_create_nonce( sprintf( 'destroy_session_nonce-%d', $user_session[0]->user_id ) )
				];
				array_push( $search_results_termination_data, $user_session_data );
			}
		}

		$total_sessions = empty( $search_results ) ? count( $this->user_sessions ) : count( $search_results );
		$current_admins = $this->count_admin_role_sessions( $this->user_sessions );
		$pages          = absint( ceil( $total_sessions / $spp ) );

		$users = $results;

		ob_start();

		// Selected type.
		$type = filter_input( INPUT_GET, 'type', FILTER_SANITIZE_STRING );

		// Searched keyword.
		$keyword = filter_input( INPUT_GET, 'keyword', FILTER_SANITIZE_STRING );

		// Pagination first page link.
		$first_link_args['page'] = 'wsal-usersessions-views';
		if ( ! empty( $type ) && ! empty( $keyword ) ) {
			$first_link_args['type']    = $type;
			$first_link_args['keyword'] = $keyword;
		}
		$first_link = add_query_arg( $first_link_args, admin_url( 'admin.php' ) );
		$base_link  = $first_link;

		// Pagination last link.
		$last_link_args['paged'] = $pages;
		if ( ! empty( $type ) && ! empty( $keyword ) ) {
			$last_link_args['type']    = $type;
			$last_link_args['keyword'] = $keyword;
		}
		$last_link = add_query_arg( $last_link_args, $first_link );

		// Previous link.
		if ( $paged > 2 ) {
			$prev_link_args = array(
				'paged'             => absint( $paged - 1 ),
				'sessions_per_page' => $spp,
			);
			if ( ! empty( $type ) && ! empty( $keyword ) ) {
				$prev_link_args['type']    = $type;
				$prev_link_args['keyword'] = $keyword;
			}
			$prev_link = add_query_arg( $prev_link_args, $first_link );
		} else {
			$prev_link = $first_link;
		}

		// Next link.
		if ( $pages > $paged ) {
			$next_link_args = array(
				'paged'             => absint( $paged + 1 ),
				'sessions_per_page' => $spp,
			);
			if ( ! empty( $type ) && ! empty( $keyword ) ) {
				$next_link_args['type']    = $type;
				$next_link_args['keyword'] = $keyword;
			}
			$next_link = add_query_arg( $next_link_args, $first_link );
		} else {
			$next_link = $last_link;
		}

		// Calculate the number of sessions after offset.
		$session_token = $total_sessions % 10;

		if ( empty( $search_results ) ) :
			$session_data = array(
				'token'         => $session_token,
				'blog_id'       => $current_blog_id,
				'session_nonce' => wp_create_nonce( 'wsal-session-auto-refresh' ),
			);
			?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					SessionAutoRefresh( '<?php echo wp_json_encode( $session_data ); ?>' );
				});</script>
			<?php
		endif;

		// Navigation links buffer start.
		ob_start();
		?>
		<div class="tablenav-pages">
			<span class="displaying-num">
				<?php
				/* translators: Number of sessions */
				printf( esc_html__( '%s users', 'wp-security-audit-log' ), number_format( $total_sessions ) );
				?>
			</span>
			<?php if ( $pages > 1 ) : ?>
				<span class="pagination-links">
					<a class="button first-page<?php echo ( 1 === $paged ) ? ' disabled' : null; ?>" title="<?php esc_attr_e( 'Go to the first page', 'wp-security-audit-log' ); ?>" href="<?php echo esc_url( $first_link ); ?>">«</a>
					<a class="button prev-page<?php echo ( 1 === $paged ) ? ' disabled' : null; ?>" title="<?php esc_attr_e( 'Go to the previous page', 'wp-security-audit-log' ); ?>" href="<?php echo esc_url( $prev_link ); ?>">‹</a>
					<span class="paging-input">
						<?php echo absint( $paged ); ?> <?php esc_html_e( 'of', 'wp-security-audit-log' ); ?> <span class="total-pages"><?php echo absint( $pages ); ?></span>
					</span>
					<a class="button next-page<?php echo ( $pages === $paged ) ? ' disabled' : null; ?>" title="<?php esc_attr_e( 'Go to the next page', 'wp-security-audit-log' ); ?>" href="<?php echo esc_url( $next_link ); ?>">›</a>
					<a class="button last-page<?php echo ( $pages === $paged ) ? ' disabled' : null; ?>" title="<?php esc_attr_e( 'Go to the last page', 'wp-security-audit-log' ); ?>" href="<?php echo esc_url( $last_link ); ?>">»</a>
				</span>
			<?php endif; ?>
		</div>
		<?php
		// Get navigation links buffer.
		$pagination = ob_get_clean();
		?>

					<p><?php esc_html_e( 'Total number of sessions with Administrator Role: ', 'wp-security-audit-log' ); ?> <strong><?php echo number_format( $current_admins ); ?></strong></p>

					<!-- Sessions Search -->
					<form method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="wsal_sessions__search" class="wsal_sessions_search">
						<?php
						// Options array.
						$options_arr = array(
							'username'  => __( 'Username', 'wp-security-audit-log' ),
							'email'     => __( 'Email', 'wp-security-audit-log' ),
							'firstname' => __( 'First Name', 'wp-security-audit-log' ),
							'lastname'  => __( 'Last Name', 'wp-security-audit-log' ),
							'ip'        => __( 'IP Address', 'wp-security-audit-log' ),
							'user-role' => __( 'User Role', 'wp-security-audit-log' ),
						);
						?>
						<select name="type" id="type">
							<?php foreach ( $options_arr as $option_value => $option_text ) : ?>
								<option value="<?php echo esc_attr( $option_value ); ?>"
									<?php echo ( $option_value === $type ) ? ' selected' : false; ?>>
									<?php echo esc_html( $option_text ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<input type="text" name="keyword" id="keyword" value="<?php echo esc_attr( $keyword ); ?>">
						<input type="hidden" name="action" value="wsal_sessions_search">
						<?php wp_nonce_field( 'wsal_session_search__nonce', 'wsal_session_search__nonce' ); ?>
						<input type="submit" class="button" name="wsal_session_search__btn" id="wsal_session_search__btn" value="<?php esc_attr_e( 'Search', 'wp-security-audit-log' ); ?>">
					</form>
					<!-- / Sessions Search -->

					<form id="sessionsForm" method="get">
						<?php
						$page    = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : false;
						$site_id = isset( $_GET['wsal-cbid'] ) ? sanitize_text_field( wp_unslash( $_GET['wsal-cbid'] ) ) : '0';
						?>
						<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>" />
						<input type="hidden" id="wsal-cbid" name="wsal-cbid" value="<?php echo esc_attr( $site_id ); ?>" />
						<input type="hidden" id="wsal-sessions-form" name="wsal-sessions-form" value="<?php echo esc_attr( wp_create_nonce( 'wsal-sessions-form' ) ); ?>">
						<div class="tablenav top">
							<?php
							// Show site alerts widget.
							if ( $this->is_multisite() && $this->is_main_blog() ) {
								$curr = $this->get_view_site_id();
								esc_html_e( 'Show:', 'wp-security-audit-log' );
								?>
								<div class="wsal-ssa">
									<?php if ( $this->get_site_count() > 15 ) : ?>
										<?php $curr = $curr ? get_blog_details( $curr ) : null; ?>
										<?php $curr = $curr ? ( $curr->blogname . ' (' . $curr->domain . ')' ) : 'Network-wide Logins'; ?>
										<input type="text" value="<?php echo esc_attr( $curr ); ?>"/>
									<?php else : ?>
										<select onchange="WsalSsasChange(value);">
											<option value="0"><?php esc_html_e( 'Network-wide Logins', 'wp-security-audit-log' ); ?></option>
											<?php foreach ( $this->get_sites() as $info ) : ?>
												<option value="<?php echo absint( $info->blog_id ); ?>" <?php echo ( (int) $info->blog_id === (int) $curr ) ? 'selected="selected"' : false; ?>>
													<?php echo esc_html( $info->blogname ) . ' (' . esc_html( $info->domain ) . ')'; ?>
												</option>
											<?php endforeach; ?>
										</select>
									<?php endif; ?>
								</div>
								<?php
							}
							echo $pagination; // phpcs:ignore
							if ( ! empty( $results ) ) {
								$this->render_fetch_data_button();
								$this->render_terminate_search_result_sessions_button( $search_results_termination_data );
							}
							?>
							<br class="clear">
						</div>
						<table class="wp-list-table widefat fixed users">
							<thead>
								<tr>
									<?php
									foreach ( $columns as $slug => $name ) {
										?>
										<th scope="col" class="manage-column column-<?php echo esc_attr( $slug ); ?>">
											<span><?php echo esc_html( $name ); ?>
											</span>
										</th>
										<?php
									}
									?>
								</tr>
							</thead>
							<?php if ( empty( $results ) && isset( $_REQUEST['type'] ) ) { ?>
								<tbody class="no-results-found">
									<tr>
										<td colspan="6">
											<?php esc_html_e( 'No logged in sessions meet your search criteria.', 'wp-security-audit-log' ); ?>
										</td>
									</tr>
								</tbody>
							<?php } elseif ( empty( $results ) ) { ?>
								<tbody class="no-results-found">
									<tr>
										<td colspan="6">
											<?php esc_html_e( 'WP Activity Log keeps its own user session data. This means that the sessions of already logged in users will only show up once they logout and log back in. The same applies to your session.', 'wp-security-audit-log' ); ?>
										</td>
									</tr>
								</tbody>
							<?php } else { ?>
								<tbody id="the-list">
								<?php
								$i = 0;
								foreach ( $users as $user_id => $result_sessions ) :
									$this->load_event_on_click_button_output = false;
									$i++;
									?>
									<tr <?php echo ( 0 !== $i % 2 ) ? 'class="alternate"' : ''; ?>>
										<td colspan="6">
											<table class="wp-list-table widefat fixed users">
												<?php
												foreach ( $result_sessions as $key => $result ) :
													$user_id   = absint( $result->user_id );
													$edit_link = add_query_arg(
														array(
															'wp_http_referer' => urlencode( wp_unslash( $_SERVER['REQUEST_URI'] ) ),
														),
														self_admin_url( sprintf( 'user-edit.php?user_id=%d', $user_id ) )
													);

													$created    = WSAL_Utilities_DateTimeFormatter::instance()->getFormattedDateTime( $result->creation_time );
													$expiration = WSAL_Utilities_DateTimeFormatter::instance()->getFormattedDateTime( $result->expiry_time );

													// not using microtime here so strip the seconds.
													$created    = WSAL_Utilities_DateTimeFormatter::removeMilliseconds( $created );
													$expiration = WSAL_Utilities_DateTimeFormatter::removeMilliseconds( $expiration );

													$user       = get_user_by( 'id', $user_id );
													$is_current_session = WSAL_UserSessions_Helpers::hash_token( wp_get_session_token() ) === $result->session_token;
													?>
													<tr id="<?php echo esc_html( $result->session_token ); ?>" <?php echo ( $is_current_session ) ? 'class="is-current-session"' : ''; ?>>
														<td class="username column-username" data-colname="Username">
															<?php echo get_avatar( $user_id, 32 ); ?>
															<a class="wsal_session_user" href="<?php echo esc_url( $edit_link ); ?>" target="_blank" data-login="<?php echo esc_attr( $user->data->user_login ); ?>">
																<?php echo WSAL_Utilities_UsersUtils::get_display_label( $this->plugin, $user ); ?>
															</a>
															<br>
															<?php echo WSAL_Utilities_UsersUtils::get_roles_label( $result->roles ); ?>
															<br><br>
															<span><strong><?php esc_html_e( 'Session ID: ', 'wp-security-audit-log' ); ?></strong><span class="user_session_id"><?php echo esc_html( $result->session_token ); ?></span></span>
														</td>
														<td class="created column-created" data-colname="Created">
															<?php echo $created; ?>
														</td>
														<td class="expiration column-expiration" data-colname="Expires">
															<?php echo $expiration; ?>
														</td>
														<td class="ip column-ip" data-colname="Source IP">
															<?php $url = 'whatismyipaddress.com/ip/'. $result->ip .'?utm_source=plugin&utm_medium=referral&utm_campaign=WPSAL'; ?>
															<a target="_blank" href="<?php echo esc_url( $url ); ?>"><?php echo $result->ip; ?></a>
														</td>
														<td class="alert column-alert" data-colname="Last Alert">
															<?php
															// outputs the load button message for loading events in only the first row.
															// other rows get just an empty placeholder for JS targeting.
															if ( ! $this->load_event_on_click_button_output ) {
																$message = __( 'Click the button above to retrieve the users\' last event.', 'wp-security-audit-log' );
																// set the flag to show we output the message already.
																$this->load_event_on_click_button_output = true;
															} else {
																$message = '';
															}
															echo '<span class="fetch_placeholder">' . esc_html( $message ) . '</span>';
															?>
														</td>
														<td class="action column-action" data-colname="<?php esc_attr_e( 'Actions', 'wp-security-audit-log' ); ?>">
															<?php
															if ( $this->plugin->settings()->CurrentUserCan( 'edit' ) ) {
																$user_data     = get_user_by( 'ID', $user_id );
																$user_wsal_url = add_query_arg(
																	array(
																		'page'    => 'wsal-auditlog',
																		'filters' => array( 'username:' . $user_data->user_login ),
																	),
																	admin_url( 'admin.php' )
																);
																echo '<a href="' . esc_url( $user_wsal_url ) . '" class="button-primary">' . esc_html__( 'Show me this user\'s events', 'wp-security-audit-log' ) . '</a>';

																echo ( ! $is_current_session ) ? '<a href="#"' : '<button type="button"';
																?>
																	data-action="destroy_session"
																	data-user-id="<?php echo esc_attr( $user_id ); ?>"
																	data-token="<?php echo esc_attr( $result->session_token ); ?>"
																	data-wpnonce="<?php echo esc_attr( wp_create_nonce( sprintf( 'destroy_session_nonce-%d', $user_id ) ) ); ?>"
																	class="button wsal_destroy_session"
																	<?php
																	if ( $is_current_session ) {
																		echo 'disabled';
																	}
																	?>
																>
																<?php
																esc_html_e( 'Terminate Session', 'wp-security-audit-log' );
																echo ( ! $is_current_session ) ? '</a>' : '</button>';
															}
															?>
														</td>
													</tr>
												<?php endforeach; ?>
											</table>
										</td>
									</tr>
									<?php
								endforeach;
								?>
							</tbody>
							<?php } ?>
							<tfoot>
								<tr>
									<?php
									foreach ( $columns as $slug => $name ) {
										?>
										<th scope="col" class="manage-column column-<?php echo esc_attr( $slug ); ?>">
											<span><?php echo esc_html( $name ); ?></span>
										</th>
										<?php
									}
									?>
								</tr>
							</tfoot>
						</table>
						<div class="tablenav bottom">
							<br class="clear">
						</div>
					</form>
		<?php


	}

	/**
	 * Registers this tab to the main page and setups the allowed tabs array.
	 *
	 * @method register_usersessions_tab
	 * @since  4.1.0
	 */
	public function register_usersessions_tab() {
		add_filter(
			'wsal_usersessions_views_nav_header_items',
			function( $tabs ) {
				$tabs[ self::$slug ] = array(
					'title' => $this->get_title(),
				);
				return $tabs;
			},
			10,
			1
		);
		add_filter(
			'wsal_usersessions_views_allowed_tabs',
			function( $allowed ) {
				$allowed[] = self::$slug;
				return $allowed;
			},
			10,
			1
		);
	}

	/**
	 * Groups a list of sessions by the user ID.
	 *
	 * @method get_sessions_grouped_by_user_id
	 * @since  4.1.0
	 * @param  array $sessions array of sessions to filter by.
	 * @return array
	 */
	public function get_sessions_grouped_by_user_id( $sessions ) {
		$results  = array();
		$sessions = ( ! empty( $sessions ) ) ? $sessions : $this->all_tracked_sessions;
		if ( ! empty( $sessions ) ) {
			foreach ( $sessions as $key => $data ) {
				$results[ $data->user_id ][] = $data;
			}
		}
		return $results;
	}

	/**
	 * Get sessions from Administrator users role
	 *
	 * @param array $user_sessions – Array of current blog sessions.
	 * @return array
	 */
	public function count_admin_role_sessions( $sessions = array() ) {
		$total = 0;
		// If user sessions array is empty then use from property.
		if ( empty( $sessions ) ) {
			$sessions = $this->all_tracked_sessions;
		}
		// If user sessions array is still empty then return 0.
		if ( empty( $sessions ) ) {
			return $total;
		}

		// Admin roles array.
		$arr_roles = array();

		// a sorted array will be an array of arrays to count.
		if ( is_array( $sessions ) && is_array( reset( $sessions ) ) ) {
			foreach ( $sessions as $id => $session_arr ) {
				// Check for admin roles in the user sessions array.
				foreach ( $session_arr as $key => $session ) {
					if ( property_exists( $session, 'roles' ) && in_array( 'administrator', $session->roles, true ) ) {
						$total++;
					}
				}
			}
		} else {
			// Check for admin roles in the user sessions array.
			foreach ( $sessions as $id => $session ) {
				if ( property_exists( $session, 'roles' ) && in_array( 'administrator', $session->roles, true ) ) {
					$total++;
				}
			}
		}

		// Return count of admin sessions.
		return $total;
	}

	/**
	 * Method: Search sessions form submit redirect.
	 */
	public function sessions_search_form() {
		// Get $_GET array.
		$filter_input_args = array(
			'type'                       => FILTER_SANITIZE_STRING,
			'keyword'                    => FILTER_SANITIZE_STRING,
			'wsal_session_search__nonce' => FILTER_SANITIZE_STRING,
		);
		$get_array         = filter_input_array( INPUT_GET, $filter_input_args );

		// Get redirect URL.
		$redirect = filter_input( INPUT_GET, '_wp_http_referer' );

		// Verify nonce.
		if ( isset( $get_array['wsal_session_search__nonce'] )
			&& wp_verify_nonce( $get_array['wsal_session_search__nonce'], 'wsal_session_search__nonce' ) ) {
			$redirect = add_query_arg(
				array(
					'type'    => $get_array['type'],
					'keyword' => $get_array['keyword'],
				),
				$redirect
			);
		}

		wp_safe_redirect( $redirect );
		die();
	}

	/**
	 * Method: Search Sessions.
	 *
	 * @return array - Array of search results.
	 * @throws Exception - Throw exception if sessions don't exist.
	 * @since 3.1.2
	 */
	protected function sessions_search() {
		// Get post array.
		$filter_input_args = array(
			'type'                => FILTER_SANITIZE_STRING,
			'keyword'             => FILTER_SANITIZE_STRING,
			'sessions-terminated' => FILTER_SANITIZE_STRING,
		);
		$get_array         = filter_input_array( INPUT_GET, $filter_input_args );
		
		// Verify user sessions exists.
		if ( ! is_array( $this->all_tracked_sessions ) && ! isset( $get_array['sessions-terminated'] ) || empty( $this->all_tracked_sessions ) && ! isset( $get_array['sessions-terminated'] ) ) {
			throw new Exception( __( 'User sessions do not exist.', 'wp-security-audit-log' ) );
		}

		// Search results.
		$results = array();

		// Get the type of search made.
		if ( isset( $get_array['type'] ) ) {
			switch ( $get_array['type'] ) {
				case 'username':
					// Search by username.
					if ( isset( $get_array['keyword'] ) ) {
						// Get user from WP.
						$user = get_user_by( 'login', $get_array['keyword'] );

						// If user exists then search in sessions.
						if ( $user && $user instanceof WP_User ) {
							// If user id match then add the sessions array to results array.
							if ( isset( $this->user_sessions[ $user->ID ] ) ) {
								$results[ $user->ID ] = $this->user_sessions[ $user->ID ];
							}
						}
					}
					break;

				case 'email':
					// Search by email.
					if ( isset( $get_array['keyword'] ) && is_email( $get_array['keyword'] ) ) {
						// Get user from WP.
						$user = get_user_by( 'email', $get_array['keyword'] );

						// If user exists then search in sessions.
						if ( $user && $user instanceof WP_User ) {
							// If user id match then add the sessions array to results array.
							if ( isset( $this->user_sessions[ $user->ID ] ) ) {
								$results[ $user->ID ] = $this->user_sessions[ $user->ID ];
							}
						}
					}
					break;

				case 'firstname':
					// Search by user first name.
					if ( isset( $get_array['keyword'] ) ) {
						// Ensure that incoming keyword is string.
						$name = (string) $get_array['keyword'];

						// Get users.
						$users_array = get_users(
							array(
								'meta_key'     => 'first_name',
								'meta_value'   => $name,
								'fields'       => array( 'ID', 'user_login' ),
								'meta_compare' => 'LIKE',
							)
						);

						// Extract user id.
						$user_ids = array();
						foreach ( $users_array as $user ) {
							$user_ids[] = $user->ID;
						}

						// If user_ids array is not empty then.
						if ( ! empty( $user_ids ) ) {
							// Search sessions by user id.
							foreach ( $user_ids as $user_id ) {
								// If user id match then add the sessions array to results array.
								if ( isset( $this->user_sessions[ $user_id ] ) ) {
									$results[ $user_id ] = $this->user_sessions[ $user_id ];
								}
							}
						}
					}
					break;

				case 'lastname':
					// Search by user last name.
					if ( isset( $get_array['keyword'] ) ) {
						// Ensure that incoming keyword is string.
						$name = (string) $get_array['keyword'];

						// Get users.
						$users_array = get_users(
							array(
								'meta_key'     => 'last_name',
								'meta_value'   => $name,
								'fields'       => array( 'ID', 'user_login' ),
								'meta_compare' => 'LIKE',
							)
						);

						// Extract user id.
						$user_ids = array();
						foreach ( $users_array as $user ) {
							$user_ids[] = $user->ID;
						}

						// If user_ids array is not empty then.
						if ( ! empty( $user_ids ) ) {
							// Search sessions by user id.
							foreach ( $user_ids as $user_id ) {
								// If user id match then add the sessions array to results array.
								if ( isset( $this->user_sessions[ $user_id ] ) ) {
									$results[ $user_id ] = $this->user_sessions[ $user_id ];
								}
							}
						}
					}
					break;

				case 'ip':
					// Search by ip.
					if ( isset( $get_array['keyword'] ) ) {
						// Search sessions by ip.
						foreach ( $this->user_sessions as $user_id => $sessions ) {
							// Search for matching IPs in $sessions.
							foreach ( $sessions as $session ) {
								if ( $get_array['keyword'] === $session->ip ) {
									$results[ $user_id ][] = $session;
								}
							}
						}
					}
					break;

				case 'user-role':
					// Search by user-role.
					if ( isset( $get_array['keyword'] ) ) {
						// Search sessions by user role.
						foreach ( $this->user_sessions as $user_id => $sessions ) {
							// Search for matching user role in $sessions.
							foreach ( $sessions as $session ) {
								if ( in_array( $get_array['keyword'], $session->roles, true ) ) {
									$results[ $user_id ][] = $session;
								}
							}
						}
					}
					break;

				default:
					// Default case.
					break;
			}
		}

		// Return results.
		return $results;
	}

	/**
	 * Query sites from WP DB.
	 *
	 * @param int|null $limit — Maximum number of sites to return (null = no limit).
	 * @return object — Object with keys: blog_id, blogname, domain
	 */
	public function get_sites( $limit = null ) {
		global $wpdb;

		$sql = 'SELECT blog_id, domain FROM ' . $wpdb->blogs;
		if ( ! is_null( $limit ) ) {
			$sql .= ' LIMIT ' . $limit;
		}
		$res = $wpdb->get_results( $sql );
		foreach ( $res as $row ) {
			$row->blogname = get_blog_option( $row->blog_id, 'blogname' );
		}
		return $res;
	}

	/**
	 * The number of sites on the network.
	 *
	 * @return int
	 */
	public function get_site_count() {
		global $wpdb;
		$sql = 'SELECT COUNT(*) FROM ' . $wpdb->blogs;
		return (int) $wpdb->get_var( $sql );
	}

	protected function is_multisite() {
		return $this->plugin->IsMultisite();
	}

	protected function is_main_blog() {
		return get_current_blog_id() == 1;
	}

	protected function is_specific_view() {
		return isset( $_REQUEST['wsal-cbid'] ) && $_REQUEST['wsal-cbid'] != '0';
	}

	protected function get_specific_view() {
		return isset( $_REQUEST['wsal-cbid'] ) ? (int) $_REQUEST['wsal-cbid'] : 0;
	}

	protected function get_view_site_id() {
		switch ( true ) {
			// Non-multisite.
			case ! $this->is_multisite():
				return 0;
			// Multisite + main site view.
			case $this->is_main_blog() && ! $this->is_specific_view():
				return 0;
			// Multisite + switched site view.
			case $this->is_main_blog() && $this->is_specific_view():
				return $this->get_specific_view();
			// Multisite + local site view.
			default:
				return get_current_blog_id();
		}
	}

}
