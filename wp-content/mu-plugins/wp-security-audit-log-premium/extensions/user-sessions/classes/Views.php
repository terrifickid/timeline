<?php
/**
 * View: User Sessions Views
 *
 * Class file for users sessions views.
 *
 * @since 4.1.0
 * @package wsal
 */

/**
 * Class WSAL_UserSessions_Views of the page Users Sessions & Management
 *
 * @package wsal
 */
class WSAL_UserSessions_Views extends WSAL_AbstractView {

	/**
	 * Extension directory path.
	 *
	 * @var string
	 */
	public $base_dir;

	/**
	 * Extension directory url.
	 *
	 * @var string
	 */
	public $base_url;

	/**
	 * Hold the pages classes that contain the contents for a page tab.
	 *
	 * @var array
	 */
	public $pages = array();

	/**
	 * Holds the tab being requested for displaying.
	 *
	 * @var string;
	 */
	private $requested_tab = '';

	/**
	 * Allowed HTML Tags in Blocked Sessions
	 * Error Message.
	 *
	 * @since 4.1.0
	 * @var  array
	 */
	private $allowed_error_tags = array();

	/**
	 * Method: Constructor
	 *
	 * @param WpSecurityAuditLog $plugin - Instance of WpSecurityAuditLog.
	 * @since  1.0.0
	 */
	public function __construct( WpSecurityAuditLog $plugin ) {
		// Call to the parent class.
		parent::__construct( $plugin );

		// setup the main display page, policies page and settings page.
		$this->setup_pages();
		$this->requested_tab = ( isset( $_GET['tab'] ) && in_array( $_GET['tab'], $this->allowed_navtab_keys(), true ) ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'sessions'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- verifying against known list.

		// Ajax call for session auto refresh.
		add_action( 'wp_ajax_wsal_usersession_auto_refresh', array( $this, 'session_auto_refresh' ) );

		// Set paths for plugin.
		$this->base_dir = WSAL_BASE_DIR . 'extensions/user-sessions';
		$this->base_url = WSAL_BASE_URL . 'extensions/user-sessions';

		// Ajax endpoints to destroy user sessions.
		add_action( 'wp_ajax_destroy_session', array( $this, 'ajax_destroy_user_session' ) );
		add_action( 'wp_ajax_wsal_terminate_all_sessions', array( $this, 'terminate_all_sessions' ) );

		// Listen for session search submit.
		add_action( 'admin_post_wsal_sessions_search', array( $this, 'sessions_search_form' ) );

		// Set allowed HTML tags for blocked sessions error message.
		$this->allowed_error_tags = array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
			),
		);

		add_action( 'wp_ajax_wsal_fetch_session_event_data_chunk', array( $this, 'fetch_session_event_data_chunk' ) );
	}

	/**
	 * Setup all page tabs that are used for user sessions management.
	 *
	 * @method setup_pages
	 * @since  4.1.0
	 */
	private function setup_pages() {
		$this->pages[ WSAL_UserSessions_View_Sessions::$slug ] = new WSAL_UserSessions_View_Sessions( $this->_plugin );
		$this->pages[ WSAL_UserSessions_View_Options::$slug ]  = new WSAL_UserSessions_View_Options( $this->_plugin );
		$this->pages[ WSAL_UserSessions_View_Settings::$slug ] = new WSAL_UserSessions_View_Settings( $this->_plugin );
	}

	/**
	 * Method: Get View Title.
	 */
	public function GetTitle() {
		return __( 'Users Sessions & Management', 'wp-security-audit-log' );
	}

	/**
	 * Method: Get View Icon.
	 */
	public function GetIcon() {
		return 'dashicons-admin-generic';
	}

	/**
	 * Method: Get View Name.
	 */
	public function GetName() {
		return __( 'Logged In Users', 'wp-security-audit-log' );
	}

	/**
	 * Method: Get View Weight.
	 */
	public function GetWeight() {
		return 7;
	}

	/**
	 * Method: Get View Header.
	 */
	public function Header() {
		// Sessions styles.
		wp_enqueue_style(
			'wsal-security-css',
			$this->base_url . '/css/style.css',
			array(),
			filemtime( $this->base_dir . '/css/style.css' )
		);

		// Remodal styles.
		wp_enqueue_style( 'wsal-remodal', WSAL_BASE_URL . 'css/remodal.css', array(), '1.1.1' );
		wp_enqueue_style( 'wsal-remodal-theme', WSAL_BASE_URL . 'css/remodal-default-theme.css', array(), '1.1.1' );

		// Darktooltip styles.
		wp_enqueue_style(
			'darktooltip',
			$this->_plugin->GetBaseUrl() . '/css/darktooltip.css',
			array(),
			'0.4.0'
		);

		// admin notices styles
		wp_enqueue_style(
			'wsal_admin_notices',
			$this->_plugin->GetBaseUrl() . '/css/admin-notices.css',
			array(),
			filemtime( $this->_plugin->GetBaseDir() . '/css/admin-notices.css' )
		);
	}

	/**
	 * Method: Get View Footer.
	 * @throws Exception
	 */
	public function Footer() {
		// Remodal script.
		wp_enqueue_script(
			'wsal-remodal-js',
			WSAL_BASE_URL . 'js/remodal.min.js',
			array(),
			'1.1.1',
			true
		);

		$adapter              = WSAL_UserSessions_Plugin::get_sessions_adapter();
		$all_tracked_sessions = $adapter->load_all_sessions_ordered_by_user_id();
		$total_sessions_count = count( $all_tracked_sessions );

		// Sessions script.
		wp_register_script(
			'wsal-security-js',
			$this->base_url . '/js/script.js',
			array( 'jquery' ),
			null,
			true
		);
		$script_data = array(
			'script_nonce'      => wp_create_nonce( 'script_nonce' ),
			'loggingOut'        => __( 'Logging out...', 'wp-security-audit-log' ),
			'refreshing'        => __( 'Refreshing...', 'wp-security-audit-log' ),
			'sessionWarning'    => __( 'This could result in loss of unsaved work. Are you sure?', 'wp-security-audit-log' ),
			'remainingSessions' => __( 'Remaining sessions:', 'wp-security-audit-log' ),
			/* Translators: %s: Total number of users */
			'sessionsTerminated' => sprintf( __( 'out of %s user sessions terminated.', 'wp-security-audit-log' ), $total_sessions_count ),
			'fetchEventStrings' => array(
				'buttonDefault'   => esc_html__( 'Retrieve user data', 'wp-security-audit-log' ),
				'buttonRunning'   => esc_html__( 'Retrieving data', 'wp-security-audit-log' ),
				'buttonFinished'  => esc_html__( 'Data retrieved', 'wp-security-audit-log' ),
				'idString'        => esc_html__( 'Event ID: ', 'wp-security-audit-log' ),
				'objectString'    => esc_html__( 'Object: ', 'wp-security-audit-log' ),
				'eventTypeString' => esc_html__( 'Event Type: ', 'wp-security-audit-log' ),
			),
		);
		wp_localize_script( 'wsal-security-js', 'script_data', $script_data );
		wp_enqueue_script( 'wsal-security-js' );
	}

	/**
	 * Auto refresh of the page if it seems like sessions may have changed since
	 * loading.
	 *
	 * It does this by remembering a hash of the sessions query in a transient
	 * for a short time.
	 */
	public function session_auto_refresh() {
		// Start assuming no need to refresh.
		$refresh = false;

		/**
		 * Get all sessions and generate a hash to compare against previously
		 * saved hash.
		 */
		$adapter = WSAL_UserSessions_Plugin::get_sessions_adapter();
		$all_tracked_sessions      = $adapter->load_all_sessions_ordered_by_user_id();
		$all_sessions_cache_key    = md5( wp_json_encode( $all_tracked_sessions ) );
		$stored_sessions_cache_key = get_transient( 'wsal_usersessions_cache_sum' );

		// If the stored hash does not match the latest then we should store the
		// latest hash and inform frontend to do a refresh.
		if ( $all_sessions_cache_key !== $stored_sessions_cache_key ) {
			$refresh = true;
		}

		// always update the transient so the page never refreshes if sessions don't change
		set_transient( 'wsal_usersessions_cache_sum', $all_sessions_cache_key, 3 * MINUTE_IN_SECONDS );

		// no changes detected, no need to refresh.
		wp_send_json_success(
			array(
				'refresh' => $refresh,
			)
		);

	}

	/**
	 * Method: Render view.
	 */
	public function Render() {
		if ( isset( $_GET['terminate'] ) && isset( $_GET['terminate_security'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['terminate_security'] ) ), 'wsal-terminate-all-sessions' ) ) {
			$this->render_terminate_all_sessions();
			return;
		}

		$this->render_terminate_all_button();
		$this->render_nav( $this->requested_tab );
		$this->render_page_content( $this->requested_tab );
		?>
		<!-- Terminal all sessions modal -->
		<div class="remodal" data-remodal-id="wsal-terminate-sessions">
			<button data-remodal-action="close" class="remodal-close"></button>
			<h3><?php esc_html_e( 'Terminate all logged in sessions', 'wp-security-audit-log' ); ?></h3>
			<p><?php esc_html_e( 'This will terminate all users\' sessions including yours, which could result in unsaved work. Do you like to proceed?', 'wp-security-audit-log' ); ?></p>
			<br>
			<?php
			$terminate_all_url = add_query_arg(
				array(
					'terminate'          => 'all-sessions',
					'terminate_security' => wp_create_nonce( 'wsal-terminate-all-sessions' ),
				),
				$this->GetUrl()
			);
			?>
			<a href="<?php echo esc_url( $terminate_all_url ); ?>" class="remodal-confirm"><?php esc_html_e( 'YES', 'wp-security-audit-log' ); ?></a>
			<button data-remodal-action="cancel" class="remodal-cancel"><?php esc_html_e( 'NO', 'wp-security-audit-log' ); ?></button>
		</div>
		<?php

	}

	public function render_terminate_all_button() {
		if ( current_user_can( 'manage_options' ) ) {
			?>
			<a href="#wsal-terminate-sessions" class="button-nav-right" id="wsal_terminate_all_link_wrapper">
				<button id="wsal_terminate_all" class="button alignright">
					<span class="dashicons dashicons-no"></span>
					<?php esc_html_e( 'Terminate All Sessions', 'wp-security-audit-log' ); ?>
				</button>
			</a>
			<?php
		}
	}

	/**
	 * Renders a navtab based on items passed.
	 *
	 * @method render_nav
	 * @since  4.1.0
	 * @return [type]
	 */
	private function render_nav( $active_tab ) {
		// get the tabs for the main nav. return early if not an array.
		$nav_header_items = $this->get_nav_items();
		if ( ! is_array( $nav_header_items ) ) {
			return;
		}
		// if we don't ahve a title to use then unset this item.
		foreach ( $nav_header_items as $key => $header_item ) {
			if ( ! is_array( $header_item ) || ! isset( $header_item['title'] ) ) {
				unset( $nav_header_items[ $key ] );
				continue;
			}
		}
		// return before any output if we have zero items.
		if ( count( $nav_header_items ) <= 0 ) {
			return;
		}
		?>
		<h2 id="wsal-usersessions-mainnav" class="nav-tab-wrapper">
			<?php
			foreach ( $nav_header_items as $key => $header_item ) {
				// if $active_tab matches the $slug this is the active tab.
				$is_active = $this->is_active_navtab( $key, $active_tab ) ? 'nav-tab-active' : '';
				?>
				<a href="<?php echo esc_url( remove_query_arg( 'subtab', add_query_arg( 'tab', $key ) ) ); ?>" id="nav-tab-<?php echo esc_attr( $key ); ?>" class="nav-tab <?php echo esc_attr( $is_active ); ?>"><?php echo esc_html( $header_item['title'] ); ?></a>
				<?php
			}
			?>
		</h2>
		<?php
	}

	/**
	 * Gets a list of the main page nav tab items to generate the menu from.
	 *
	 * @method get_nav_items
	 * @since  4.1.0
	 * @return array
	 */
	private function get_nav_items() {
		return apply_filters(
			'wsal_usersessions_views_nav_header_items',
			array()
		);
	}

	/**
	 * Checks if the current tab matches the requested tab and if so it's the
	 * active one.
	 *
	 * @method is_active_navtab
	 * @since  4.1.0
	 * @param  string $slug      current slug for a tab.
	 * @param  string $requested requested tab slug.
	 * @return boolean
	 */
	private function is_active_navtab( $slug = '', $requested = '' ) {
		$active = false;
		if ( $requested === $slug ) {
			$active = true;
		}
		return $active;
	}

	/**
	 * Generate a whitelist of allowed tabs for the views. This is filtered so
	 * pages can add their tab at registration time.
	 *
	 * @method allowed_navtab_keys
	 * @since  4.1.0
	 * @return array
	 */
	private function allowed_navtab_keys() {
		return apply_filters( 'wsal_usersessions_views_allowed_tabs', array() );
	}


	/**
	 * Renders a tab of content to the page.
	 *
	 * @method render_page_content
	 * @since  4.1.0
	 * @param  string $active_tab the tab that is requested.
	 * @return void
	 */
	private function render_page_content( $active_tab = '' ) {
		if ( empty( $active_tab ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( is_a( $this->pages[ $active_tab ], 'WSAL_UserSessions_View_' . ucfirst( $active_tab ) ) ) {
			$this->pages[ $active_tab ]->render();
		}
	}

	/**
	 * Terminate All Sessions View.
	 *
	 * @since 4.1.0
	 */
	private function render_terminate_all_sessions() {
		if ( isset( $_GET['terminate'] ) && 'all-sessions' === sanitize_text_field( wp_unslash( $_GET['terminate'] ) ) ) {
			?>
			<div id="wsal-termination-loader">
				<p><?php esc_html_e( 'Users sessions termination is in progress. Please wait...', 'wp-security-audit-log' ); ?></p>
				<div class="wsal-lds-ellipsis"><div></div><div></div><div></div><div></div></div>
			</div>
			<div id="wsal-termination-progress"></div>
			<script type="text/javascript">
				jQuery( document ).ready( function() {
					terminate_all_sessions( "<?php echo esc_html( wp_create_nonce( 'wsal-terminate-all-sessions' ) ); ?>" );
				});
			</script>
			<?php
		} else {
			// Redirect to Logged In Users page after log in if the nonce has expired.
			wp_safe_redirect( $this->GetUrl() );
			exit();
		}
	}

	/**
	 * Method: Destroy User Session.
	 *
	 * @since 3.1
	 */
	public function ajax_destroy_user_session() {
		// Check if current user can manage options.
		if ( ! $this->_plugin->settings()->CurrentUserCan( 'edit' ) ) {
			$response = array(
				'success' => false,
				'message' => esc_html__( 'You do not have sufficient permissions.', 'wp-security-audit-log' ),
			);
			echo wp_json_encode( $response );
			exit;
		}

		// Set filter input args.
		$filter_input_args = array(
			'action'  => FILTER_SANITIZE_STRING,
			'user_id' => FILTER_VALIDATE_INT,
			'token'   => FILTER_SANITIZE_STRING,
			'nonce'   => FILTER_SANITIZE_STRING,
		);

		// Get $_POST array & Verify nonce.
		$post_array = filter_input_array( INPUT_POST, $filter_input_args );

		if ( ! empty( $post_array['nonce'] )
			&& ! empty( $post_array['action'] )
			&& 'destroy_session' === $post_array['action']
			&& ! empty( $post_array['user_id'] )
			&& ! empty( $post_array['token'] ) ) {
			$user_id = absint( $post_array['user_id'] );
			if ( ! wp_verify_nonce( $post_array['nonce'], sprintf( 'destroy_session_nonce-%d', $user_id ) ) ) {
				$response = array(
					'success' => false,
					'message' => esc_html__( 'No sessions.', 'wp-security-audit-log' ),
				);
				echo wp_json_encode( $response );
				exit;
			}

			//  delete the session from everywhere
			WSAL_UserSessions_Plugin::delete_session( $user_id, $post_array['token'] );

			//  fire event 1007 destroyed session and logged out
			$user_data = get_userdata( $user_id );
			if ( is_a( $user_data, '\WP_User' ) ) {
				$this->_plugin->alerts->Trigger(
					1007,
					array(
						'TargetUserName'  => $user_data->data->user_login,
						'TargetUserRole'  => is_array( $user_data->roles ) ? implode( ', ', $user_data->roles ) : $user_data->roles,
						'TargetSessionID' => $post_array['token'],
					),
					true
				);
			}

			$response = array(
				'success' => true,
				'message' => esc_html__( 'Session destroyed.', 'wp-security-audit-log' ),
			);
			echo wp_json_encode( $response );
			exit;
		}

		$response = array(
			'success' => false,
			'message' => esc_html__( 'User session data is not set.', 'wp-security-audit-log' ),
		);
		echo wp_json_encode( $response );
		exit;
	}

	/**
	 * Method: Destroy all sessions.
	 *
	 * @since 3.1.4
	 */
	public function terminate_all_sessions() {
		// Check if current user can manage options.
		if ( ! $this->_plugin->settings()->CurrentUserCan( 'edit' ) ) {
			$response = array(
				'success' => false,
				'message' => esc_html__( 'User do not have sufficient permissions.', 'wp-security-audit-log' ),
			);
			echo wp_json_encode( $response );
			exit;
		}

		// Get nonce and verify it.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wsal-terminate-all-sessions' ) ) {
			$response = array(
				'success' => false,
				'message' => esc_html__( 'Nonce verification failed.', 'wp-security-audit-log' ),
			);
			echo wp_json_encode( $response );
			exit();
		}

        $users_limit = 50;
        $current_user_id = get_current_user_id();
        $excluded_id = $current_user_id;
        $users_args   = array(
            'blog_id' => 0,
            'exclude' => array( $excluded_id ),
            'fields'  => array( 'ID' ),
            'number'  => $users_limit,
            //  no offset necessary here as the sessions are being deleted in each iteration
            'meta_key'     => 'session_tokens',
            'meta_compare' => 'EXISTS',
        );

        $completed           = false;
        $sessions_deleted = 0;
        $users_obj        = new WP_User_Query( $users_args );
        $result_users     = $users_obj->get_results();

        if ( ! empty( $result_users ) ) {
            foreach ( $result_users as $key => $user ) {
                $sessions_deleted += WSAL_Sensors_UserSessionsTracking::clear_existing_sessions( $user->ID );
            }
        }

        //  check if this is the last batch and we can also delete current user's session
        if ( count( $result_users ) < $users_limit ) {
            $sessions_deleted += WSAL_Sensors_UserSessionsTracking::clear_existing_sessions( $current_user_id );
            //  this will stop further requests from the UI
            $completed = true;
        }

        //  work out number of previously deleted sessions (this is received in the request as this is a batch process)
        $previously_deleted_sessions = array_key_exists('sessions_deleted', $_POST) ? intval($_POST['sessions_deleted']) : 0;
        $response = array(
            'success'          => true,
            'completed'        => $completed ? 'yes' : 'no',
            'sessions_deleted' => $previously_deleted_sessions + $sessions_deleted,
            'message'          => esc_html__( 'Sessions destroyed!', 'wp-security-audit-log' )
        );
        echo wp_json_encode( $response );

		exit();
	}

	/**
	 * Gets data about a chunk of the displayed sessions so it can be grabbed
	 * in sections and prevent time outs on sites with many users and active
	 * sessions.
	 *
	 * @method fetch_session_event_data_chunk
	 * @since  4.0.1
	 * @return void or a json response.
	 */
	public function fetch_session_event_data_chunk() {
		// Check if current user can manage options.
		if ( ! $this->_plugin->settings()->CurrentUserCan( 'edit' ) ) {
			$response = array(
				'success' => false,
				'message' => esc_html__( 'User do not have sufficient permissions.', 'wp-security-audit-log' ),
			);
			echo wp_json_encode( $response );
			exit;
		}

		// Get nonce and verify it.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'fetch_user_session_event_data' ) ) {
			$response = array(
				'success' => false,
				'message' => esc_html__( 'Nonce check failed.', 'wp-security-audit-log' ),
			);
			echo wp_json_encode( $response );
			exit;
		}

		// get the chunk to fetch for now.
		$sessions = ( isset( $_POST['sessions'] ) ) ? wp_unslash( $_POST['sessions'] ) : array();
		$limit    = ( isset( $_POST['limit'] ) ) ? absint( $_POST['limit'] ) : 5;
		$step     = ( isset( $_POST['step'] ) ) ? absint( $_POST['step'] ) : 0;

		$session_chunk = array_slice( $sessions, ( $limit * $step ), $limit, true );
		if ( empty( $session_chunk ) ) {
			wp_send_json_success( array( 'done' => true ) );
		}

        $result = array();
        foreach ( $session_chunk as $key => $session_data ) {
            $user_last_alert = $this->_plugin->usersessions->get_last_user_alert( $session_data['user'], $session_data['session'] );

            // Check for empty alert.
            if ( false === $user_last_alert ) {
                continue;
            }

            $last_alert['created_on'] = $user_last_alert->created_on;
            $last_alert['event_id']   = $user_last_alert->get_alert_id();
            $last_alert['client_ip']  = $user_last_alert->GetSourceIP();
            $last_alert['object']     = $user_last_alert->object;
            $last_alert['event_type'] = $user_last_alert->event_type;

            $result[ $session_data['session'] ] = $last_alert;
        }

		wp_send_json_success(
			array(
				'step' => ++$step,
				'data' => $result,
			)
		);
	}
}
