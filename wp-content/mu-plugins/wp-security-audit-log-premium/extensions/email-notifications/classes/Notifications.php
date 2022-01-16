<?php
/**
 * Class: Notifications Page
 *
 * View class for notification settings page.
 *
 * @since 1.0.0
 * @package wsal
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WSAL_OPT_PREFIX' ) ) {
	exit( 'Invalid request' );
}

/**
 * Class WSAL_NP_Notifications for Notifications Page.
 *
 * @package wsal
 */
class WSAL_NP_Notifications extends WSAL_AbstractView {

	// @internal
	const WPSALP_NOTIF_ERROR = 1;

	/**
	 * Search View – Email Notifications.
	 *
	 * @var boolean
	 */
	private $_search_view = false;

	/**
	 * Searched Term – Email Notifications.
	 *
	 * @var string
	 */
	private $search_term = '';

	/**
	 * Extension directory path.
	 *
	 * @var string
	 */
	public $_base_dir;

	/**
	 * Extension directory url.
	 *
	 * @var string
	 */
	public $_base_url;

	/**
	 * Configured Notifications.
	 *
	 * @since 3.4
	 *
	 * @var array
	 */
	private $notifications = array();

	/**
	 * Extension Tabs.
	 *
	 * @since 3.4
	 *
	 * @var array
	 */
	private $wsal_extension_tabs = array();

	/**
	 * Current Tabs.
	 *
	 * @since 3.4
	 *
	 * @var string
	 */
	private $current_tab = '';

	/**
	 * Current Section.
	 *
	 * @since 3.4
	 *
	 * @var string
	 */
	private $current_section = '';

	public $built_in_notification_titles = array(
		1  => 'User logs in',
		2  => 'New user is created',
		3  => 'User changed password',
		4  => 'User changed the password of another user',
		5  => 'User\'s role has changed',
		6  => 'Published content is modified',
		7  => 'Content is published',
		8  => 'First time user logs in',
		9  => 'New plugin is installed',
		10 => 'Installed plugin is activated',
		11 => 'Plugin file is modified',
		12 => 'New theme is installed',
		13 => 'Installed theme is activated',
		14 => 'Theme file is modified',
		15 => 'Critical Alert is Generated',
		16 => 'Failed login for WordPress users',
		17 => 'Failed login for non existing WordPress users',
		20 => 'New content is published',
		21 => 'Content in a post, page or custom post type is changed',
		22 => 'Anything but content in a post is changed',
		26 => 'User granted super admin',
		27 => 'User revoked super admin',
		28 => 'User added to site',
		29 => 'User removed from site',
		30 => 'Site changes',
		31 => 'Activated theme on network',
		32 => 'Deactivated theme from network',
		33 => 'Any product change',
		34 => 'Any store settings change',
		35 => 'Any coupon code changes',
		36 => 'Any orders changes',
		37 => 'WordPress was updated',
		38 => 'Installed plugin is deactivated',
		39 => 'A plugin is uninstalled',
		40 => 'Installed plugin is upgraded',
		41 => 'A theme is uninstalled',
		42 => 'Installed theme is updated',
		43 => 'User changed email address',
	);

	/**
	 * Method: Constructor.
	 *
	 * @param WpSecurityAuditLog $plugin - Instance of WpSecurityAuditLog.
	 */
	public function __construct( WpSecurityAuditLog $plugin ) {
		// Call to parent class.
		parent::__construct( $plugin );
		add_action( 'wp_ajax_send_daily_summary', array( $this, 'send_daily_summary' ) );
		add_action( 'wp_ajax_wsal_test_notifications', array( $this, 'test_notifications' ) );
		add_action( 'wp_ajax_wsal_trigger_test_notification', array( $this, 'trigger_test_notification' ) );
		add_action( 'admin_init', array( $this, 'setup_notifications_page_tabs' ) );

		// Set the paths.
		$this->_base_dir = WSAL_BASE_DIR . 'extensions/email-notifications';
		$this->_base_url = WSAL_BASE_URL . 'extensions/email-notifications';
	}

	/**
	 * Ajax handler for test summary button.
	 */
	public function send_daily_summary() {
		if ( ! $this->_plugin->settings()->CurrentUserCan( 'edit' ) ) {
			die( esc_html__( 'Access Denied.', 'wp-security-audit-log' ) );
		}

		// Verify nonce.
		if ( ! isset( $_GET['wsalSecurity'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['wsalSecurity'] ) ), 'wsal-notifications-script-nonce' ) ) {
			echo esc_html__( 'Nonce verification failed. Please refresh and try again.', 'wp-security-audit-log' );
			exit();
		}

		$result        = $this->_plugin->notifications_util->send_daily_summary_report( true );
		$redirect_args = array(
			'page' => 'wsal-np-notifications',
			'send' => 'summary',
		);

		if ( $result ) {
			$redirect_args['activate'] = true;
		} else {
			$redirect_args['error'] = true;
		}

		$redirect_url = add_query_arg( $redirect_args, network_admin_url( 'admin.php' ) );
		wp_safe_redirect( $redirect_url );
		exit();
	}

	/**
	 * Setup Notifications Page Tabs.
	 *
	 * @since 3.4
	 */
	public function setup_notifications_page_tabs() {
		// @codingStandardsIgnoreStart
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : false;
		// @codingStandardsIgnoreEnd

		// Verify that the current page is WSAL settings page.
		if ( empty( $page ) || $this->GetSafeViewName() !== $page ) {
			return;
		}

		// Tab links.
		$built_in_tab_link = add_query_arg( 'tab', 'built-in', $this->GetUrl() );
		$template_tab_link = add_query_arg( 'tab', 'templates', $this->GetUrl() );

		$wsal_extension_tabs = array(
			'built-in'  => array(
				'name'     => __( 'Built-in Notifications', 'wp-security-audit-log' ),
				'link'     => $built_in_tab_link,
				'render'   => array( $this, 'tab_built_in' ),
				'sections' => array(
					''                => array(
						'name' => __( 'WordPress System', 'wp-security-audit-log' ),
						'link' => $built_in_tab_link,
					),
					'user-profiles'   => array(
						'name' => __( 'Logins & Users Profiles', 'wp-security-audit-log' ),
						'link' => add_query_arg( 'section', 'user-profiles', $built_in_tab_link ),
					),
					'content-changes' => array(
						'name' => __( 'Content Changes', 'wp-security-audit-log' ),
						'link' => add_query_arg( 'section', 'content-changes', $built_in_tab_link ),
					),
					'multisite'       => array(
						'name' => __( 'Multisite', 'wp-security-audit-log' ),
						'link' => add_query_arg( 'section', 'multisite', $built_in_tab_link ),
					),
					'woocommerce'     => array(
						'name' => __( 'WooCommerce', 'wp-security-audit-log' ),
						'link' => add_query_arg( 'section', 'woocommerce', $built_in_tab_link ),
					),
				),
			),
			'custom'    => array(
				'name'   => __( 'Custom Notifications', 'wp-security-audit-log' ),
				'link'   => add_query_arg( 'tab', 'custom', $this->GetUrl() ),
				'render' => array( $this, 'tab_trigger_builder' ),
				'save'   => array( $this, 'tab_trigger_builder_save' ),
			),
			'templates' => array(
				'name'     => __( 'Notifications Templates', 'wp-security-audit-log' ),
				'link'     => $template_tab_link,
				'render'   => array( $this, 'tab_templates' ),
				'save'     => array( $this, 'tab_templates_save' ),
				'sections' => array(
					''    => array(
						'name' => __( 'Default Email Template', 'wp-security-audit-log' ),
						'link' => $template_tab_link,
					),
					'sms' => array(
						'name' => __( 'Default SMS Template', 'wp-security-audit-log' ),
						'link' => add_query_arg( 'section', 'sms', $template_tab_link ),
					),
				),
			),
		);

		/**
		 * Filter: `wsal_notifications_extension_tabs`
		 *
		 * This filter is used to filter the tabs of this extension.
		 *
		 * @param array $wsal_extension_tabs – Array of Extension Tabs.
		 */
		$this->wsal_extension_tabs = apply_filters( 'wsal_notifications_extension_tabs', $wsal_extension_tabs );

		// @codingStandardsIgnoreStart
		$this->current_tab     = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'built-in';
		$this->current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Return Current URL of the Page.
	 *
	 * @since 3.4
	 *
	 * @return string
	 */
	public function get_current_url() {
		return add_query_arg(
			array(
				'page' => $this->GetSafeViewName(),
				'tab'  => $this->current_tab,
			),
			network_admin_url( 'admin.php' )
		);
	}

	/**
	 * Method: Get View Title.
	 */
	public function GetTitle() {
		return __( 'Notifications', 'wp-security-audit-log' );
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
		return __( 'Notifications', 'wp-security-audit-log' );
	}

	/**
	 * Method: Get View Weight.
	 */
	public function GetWeight() {
		return 8;
	}

	/**
	 * Method: Get View Header.
	 */
	public function Header() {
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_style(
			'wsal-notif-css',
			$this->_base_url . '/css/styles.css',
			array(),
			filemtime( untrailingslashit( $this->_base_dir ) . '/css/styles.css' )
		);

		echo "<script type='text/javascript'> var dateFormat = '" . esc_html( $this->_plugin->notifications_util->DateValidFormat() ) . "'; </script>";

		wp_register_script(
			'wsal-notif-utils-js',
			$this->_base_url . '/js/wsal-notification-utils.js',
			array( 'jquery', 'jquery-ui-dialog' ),
			filemtime( untrailingslashit( $this->_base_dir ) . '/js/wsal-notification-utils.js' ),
			false
		);
		$script_data = array(
			'ajaxURL'            => admin_url( 'admin-ajax.php' ),
			'scriptNonce'        => wp_create_nonce( 'wsal-notifications-script-nonce' ),
			'okButton'           => __( 'OK', 'wp-security-audit-log' ),
			'testPopupTitle'     => __( 'Test Notifications', 'wp-security-audit-log' ),
			'triggerTestTitle'   => __( 'Trigger Builder Test Notification', 'wp-security-audit-log' ),
			'triggerTestPopupID' => '#wsal-test-notification-dialog',
			'emptyFieldsError'   => __( 'Please specify an email address or a phone number to test.', 'wp-security-audit-log' ),
		);
		wp_localize_script( 'wsal-notif-utils-js', 'scriptData', $script_data );
		wp_enqueue_script( 'wsal-notif-utils-js' );
	}

	/**
	 * Inspect the REQUEST and detect the requested view.
	 */
	private function PrepareView() {
		// Default view.
		if ( ! isset( $_REQUEST['action'] ) ) {
			return $this->_plugin->notifications_util->GetNotBuiltInNotifications();
		}

		// From here on, all requests must be signed.
		check_admin_referer( 'nonce-notifications-view', 'wsalSecurity' );

		$valid_actions = array( 'disable_notification', 'enable_notification', 'delete_notification', 'view_disabled', 'search', 'bulk' );
		$action        = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : false;
		$id            = isset( $_REQUEST['id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['id'] ) ) : null; // The notification's ID.

		if ( ! in_array( $action, $valid_actions, true ) ) {
			return self::WPSALP_NOTIF_ERROR;
		}

		if ( in_array( $action, array( 'disable_notification', 'enable_notification', 'delete_notification' ), true ) ) {
			// Return error if ID is empty.
			if ( empty( $id ) ) {
				return self::WPSALP_NOTIF_ERROR;
			}

			if (
				( 'disable_notification' === $action && ! $this->_disableNotification( $id ) ) // Disable Notification Action.
				|| ( 'enable_notification' === $action && ! $this->enable_notification( $id ) ) // Enable Notification Action.
				|| ( 'delete_notification' === $action && ! $this->_deleteNotification( $id ) ) // Delete Notification Action.
			) {
				return self::WPSALP_NOTIF_ERROR;
			}

			return $this->_plugin->notifications_util->GetNotBuiltInNotifications();
		} elseif ( 'view_disabled' === $action ) {
			return $this->_plugin->notifications_util->GetDisabledNotifications();
		} elseif ( 'search' === $action ) {
			$this->search_term = isset( $_REQUEST['s'] ) ? trim( sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) ) : null; // Searched term.
			if ( empty( $this->search_term ) ) {
				// Display the default view.
				return $this->_plugin->notifications_util->GetNotBuiltInNotifications();
			}
			$this->_search_view = true;
			return $this->_plugin->notifications_util->GetSearchResults( $this->search_term );
		} elseif ( 'bulk' === $action ) {
			$rm = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : false;
			if ( 'POST' !== $rm ) {
				return self::WPSALP_NOTIF_ERROR;
			}

			if ( isset( $_POST['bulk'] ) || isset( $_POST['bulk2'] ) ) {
				$entries = ! empty( $_POST['entries'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['entries'] ) ) : null;
				if ( empty( $entries ) ) {
					// Noting to do; display the default view.
					return $this->_plugin->notifications_util->GetNotBuiltInNotifications();
				}

				$b1 = strtolower( sanitize_text_field( wp_unslash( $_POST['bulk'] ) ) );
				$b2 = strtolower( sanitize_text_field( wp_unslash( $_POST['bulk2'] ) ) );

				// Invalid request.
				if ( '-1' === $b1 && '-1' === $b2 ) {
					return self::WPSALP_NOTIF_ERROR;
				} elseif ( '-1' === $b1 ) {
					// b2 must have valid values.
					if ( 'enable' === $b2 ) {
						$this->bulk_enable( $entries );
					} elseif ( 'disable' === $b2 ) {
						$this->_bulkDisable( $entries );
					} elseif ( 'delete' === $b2 ) {
						$this->_bulkDelete( $entries );
					}
					return $this->_plugin->notifications_util->GetNotBuiltInNotifications();
				} elseif ( '-1' === $b2 ) {
					// b1 must have valid values.
					if ( 'enable' === $b1 ) {
						$this->bulk_enable( $entries );
					} elseif ( 'disable' === $b1 ) {
						$this->_bulkDisable( $entries );
					} elseif ( 'delete' === $b1 ) {
						$this->_bulkDelete( $entries );
					}
					return $this->_plugin->notifications_util->GetNotBuiltInNotifications();
				}
			}
			// Invalid request.
			return self::WPSALP_NOTIF_ERROR;
		}
		return self::WPSALP_NOTIF_ERROR;
	}

	/**
	 * Disable Notification.
	 *
	 * @param string $id - Notification id.
	 * @return bool
	 */
	private function _disableNotification( $id ) {
		$notif = $this->_plugin->notifications_util->GetNotification( $id );
		if ( $notif === false ) {
			return false;
		}
		$opt_name         = $notif->option_name;
		$opt_data         = maybe_unserialize( $notif->option_value );
		$opt_data->status = 0;

		$this->_plugin->alerts->Trigger( 6316, [
			'EventType' => 'disabled',
			'notification_name' => $opt_data->title,
		]);

		return $this->_plugin->notifications_util->UpdateGlobalSetting( $opt_name, $opt_data );
	}

	/**
	 * Enable Notification.
	 *
	 * @param string $id - Notification id.
	 * @return bool
	 */
	private function enable_notification( $id ) {
		$notif = $this->_plugin->notifications_util->GetNotification( $id );
		if ( false === $notif ) {
			return false;
		}
		$opt_name         = $notif->option_name;
		$opt_data         = maybe_unserialize( $notif->option_value );
		$opt_data->status = 1;

		$this->_plugin->alerts->Trigger( 6316, [
			'EventType' => 'enabled',
			'notification_name' => $opt_data->title,
		]);

		return $this->_plugin->notifications_util->UpdateGlobalSetting( $opt_name, $opt_data );
	}

	/**
	 * Delete Notification.
	 *
	 * @param string $id - Notification id.
	 * @return bool
	 */
	private function _deleteNotification( $id ) {
		if ( ! $this->_plugin->settings()->CurrentUserCan( 'edit' ) ) {
			return false;
		}
		$notif = $this->_plugin->notifications_util->GetNotification( $id );
		if ( $notif === false ) {
			return false;
		}

		$notif_info = maybe_unserialize( $notif->option_value );

		$this->_plugin->alerts->Trigger( 6317, [
			'EventType' => 'deleted',
			'notification_name' => $notif_info->title,
		]);

		return $this->_plugin->notifications_util->DeleteGlobalSetting( $notif->option_name );
	}

	/**
	 * Bulk Enable Notifications.
	 *
	 * @param array $entries - Notification entries.
	 */
	private function bulk_enable( array $entries ) {
		foreach ( $entries as $i => $id ) {
			$this->enable_notification( $id );
		}
	}

	/**
	 * Bulk Disable Notifications.
	 *
	 * @param array $entries - Notification entries.
	 */
	private function _bulkDisable( array $entries ) {
		foreach ( $entries as $i => $id ) {
			$this->_disableNotification( $id );
		}
	}

	/**
	 * Bulk Delete Notifications.
	 *
	 * @param array $entries - Notification entries.
	 */
	private function _bulkDelete( array $entries ) {
		foreach ( $entries as $i => $id ) {
			$this->_deleteNotification( $id );
		}
	}

	/**
	 * Create Built-in Notification.
	 *
	 * @return array
	 */
	private function createBuilt_in() {
		// @codingStandardsIgnoreStart
		$built_in_nonce  = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : false;
		$built_in_notifs = isset( $_POST['built-in'] ) ? $_POST['built-in'] : false;
		// @codingStandardsIgnoreEnd

		if ( empty( $built_in_nonce ) || ! wp_verify_nonce( $built_in_nonce, 'wsal-built-in-notifications' ) ) :
			?>
			<div class="error">
				<p><?php esc_html_e( 'Nonce verification failed.', 'wp-security-audit-log' ); ?></p>
			</div>
			<?php
			return;
		endif;

		$alert_errors = array();
		$emails       = array();
		$titles       = $this->built_in_notification_titles;

		$events = array(
			1  => '1000',
			2  => array( '4000', '4001', '4012' ),
			3  => '4003',
			4  => '4004',
			5  => '4002',
			6  => array( '2065', '2066', '2067' ),
			7  => array( '2001', '2005', '2030' ),
			8  => '1000',
			9  => '5000',
			10 => '5001',
			11 => '2051',
			12 => '5005',
			13 => '5006',
			14 => '2046',
			15 => '2046',
			16 => '1002',
			17 => '1003',
			20 => '2001',
			21 => '2065',
			22 => array( '2002', '2016', '2017', '2019', '2021', '2025', '2027', '2047', '2048', '2049', '2050', '2053', '2054', '2055', '2062', '2086', '2119', '2120', '2131', '2132' ),
			26 => '4008',
			27 => '4009',
			28 => '4010',
			29 => '4011',
			30 => array( '7000', '7001', '7002', '7003', '7004', '7005' ),
			31 => '5008',
			32 => '5009',
			33 => array( '9000', '9001', '9003', '9004', '9005', '9006', '9008', '9009', '9010', '9011', '9012', '9013', '9014', '9015', '9072', '9073', '9077', '9007', '9016', '9017', '9018', '9019', '9020', '9021', '9022', '9023', '9024', '9025', '9026', '9042', '9043', '9044', '9045', '9046', '9047', '9048', '9049', '9050', '9051' ),
			34 => array( '9027', '9028', '9029', '9030', '9031', '9032', '9033', '9034', '9074', '9075', '9076' ),
			35 => array( '9063', '9064', '9065', '9066', '9067', '9068', '9069', '9070', '9071' ),
			36 => array( '9035', '9036', '9037', '9038', '9039', '9040', '9041' ),
			37 => '6004',
			38 => '5002',
			39 => '5003',
			40 => '5004',
			41 => '5007',
			42 => '5031',
			43 => array( '4005', '4006' ),
		);

		$msg = __( 'Notification could not be saved.', 'wp-security-audit-log' );

		foreach ( $built_in_notifs as $id => $notif ) {
			if ( isset( $notif['toggle'] ) && ( isset( $notif['email'] ) || isset( $notif['toggle'] ) && isset( $notif['phone'] ) ) ) {
				$email = isset( $notif['email'] ) ? sanitize_text_field( wp_unslash( $notif['email'] ) ) : false;
				$phone = isset( $notif['phone'] ) ? sanitize_text_field( wp_unslash( $notif['phone'] ) ) : false;

				// Validate email and phone number.
				if ( ! $email && ! $phone ) {
					$msg                 = __( 'Email Address and Mobile Number cannot be empty.', 'wp-security-audit-log' );
					$alert_errors[ $id ] = 2;
				} else {
					if ( $email ) {
						if ( $this->_plugin->notifications_util->CheckEmailOrUsername( $email ) ) {
							$emails[ $id ] = trim( $email );
						} else {
							$alert_errors[ $id ] = 2;
							$msg                 = __( 'Email Address or Username is not valid.', 'wp-security-audit-log' );
						}
					}

					if ( $phone ) {
						$phone = $this->check_phone_number( $phone );

						if ( ! $phone ) {
							$alert_errors[ $id ] = 2;
							$msg                .= ! empty( $msg ) ? ' ' : false;
							$msg                .= __( 'Phone number is not valid.', 'wp-security-audit-log' );
						}
					}
				}

				if ( empty( $alert_errors[ $id ] ) ) {
					$count               = isset( $notif['count'] ) ? absint( sanitize_text_field( wp_unslash( $notif['count'] ) ) ) : 0;
					$alert_errors[ $id ] = $this->saveBuilt_in( $id, $titles[ $id ], trim( $email ), $events[ $id ], true, $count, $phone );
				}
			} else {
				$alert_errors[ $id ] = $this->saveBuilt_in( $id, null, null, null );
			}
		}

		if ( isset( $_POST['daily-summary-switch'] ) ) {
			$summary_email = isset( $_POST['daily-summary-email'] ) ? sanitize_text_field( wp_unslash( $_POST['daily-summary-email'] ) ) : false;

			$old_address = $this->_plugin->GetGlobalSetting( 'daily-summary-email' );
			if ( $summary_email !== $old_address ) {
				$this->_plugin->alerts->Trigger( 6311, [
					'EventType' => 'modified',
					'recipient' => $summary_email,
					'previous_recipient' => $old_address,
				]);
			}

			
			$old_value = $this->_plugin->GetGlobalBooleanSetting( 'disable-daily-summary' );

			if ( ! $old_value ) {				
				$this->_plugin->alerts->Trigger( 6310, [
					'EventType' => 'enabled',
				]);
			}

			$this->_plugin->SetGlobalSetting( 'disable-daily-summary', '0' );
			$this->_plugin->SetGlobalSetting( 'daily-summary-email', $summary_email );
		} else {

			$old_value = $this->_plugin->GetGlobalBooleanSetting( 'disable-daily-summary' );
			if ( $old_value ) {
				$this->_plugin->alerts->Trigger( 6310, [
					'EventType' => 'disabled',
				]);
			}

			$this->_plugin->SetGlobalSetting( 'disable-daily-summary', '1' );
			$this->_plugin->SetGlobalSetting( 'daily-summary-email', false );
		}

		if ( in_array( 2, $alert_errors, true ) ) :
			?>
			<div class="error">
				<p><?php echo esc_html( $msg ); ?></p>
			</div>
			<?php
		elseif ( in_array( 1, $alert_errors, true ) ) :
			?>
			<div class="updated">
				<p><?php esc_html_e( 'Notification successfully saved.', 'wp-security-audit-log' ); ?></p>
			</div>
			<?php
		endif;
		return $alert_errors;
	}

	/**
	 * Save Built-in Notification.
	 *
	 * @param string  $id       - Notification id.
	 * @param string  $title    - Email Title.
	 * @param string  $email    - Email address.
	 * @param mixed   $events   - Events.
	 * @param boolean $built_in - (Optional) True if built-in notification, otherwise false.
	 * @param integer $count    - (Optional) Event counter.
	 * @param string  $phone    - (Optional) Phone number.
	 */
	public function saveBuilt_in( $id, $title, $email, $events, $built_in = true, $count = 0, $phone = false ) {
		$opt_name        = WSAL_OPT_PREFIX . 'built-in-' . $id;
		$data            = new stdClass();
		$data->title     = $title;
		$data->email     = $email;
		$data->phone     = $phone;
		$data->owner     = get_current_user_id();
		$data->dateAdded = time();
		$data->status    = 1;
		$data->viewState = array();
		$data->triggers  = array();
		$data->id        = $id;
		if ( $built_in ) {
			$data->built_in = 1;
		}
		if ( 'First time user logs in' === $title ) {
			$data->firstTimeLogin = 1;
		}
		if ( 'Critical Alert is Generated' === $title ) {
			$data->isCritical = 1;
		}
		if ( 'Failed login for WordPress users' === $title ) {
			$data->failUser = $count;
		}
		if ( 'Failed login for non existing WordPress users' === $title ) {
			$data->failNotUser = $count;
		}
		if ( isset( $events ) ) {
			if ( is_array( $events ) ) {
				foreach ( $events as $key => $event ) {
					$data->viewState[] = 'trigger_id_' . $id;
					$data->triggers[]  = array(
						'select1' => ( 0 == $key ? 0 : 1 ),
						'select2' => 0,
						'select3' => 0,
						'select4' => 0,
						'select5' => 0,
						'select6' => 0,
						'input1'  => $event,
					);
				}
			} else {
				$data->viewState[] = 'trigger_id_' . $id;
				$data->triggers[]  = array(
					'select1' => 0,
					'select2' => 0,
					'select3' => 0,
					'select4' => 0,
					'select5' => 0,
					'select6' => 0,
					'input1'  => $events,
				);
			}
		}

		$old_value = $this->_plugin->GetGlobalSetting( $opt_name );

		if ( count( $data->triggers ) > 0 ) {
			$result = $this->_plugin->notifications_util->AddGlobalSetting( $opt_name, $data );
			if ( false === $result ) {
				return 2;
			} else {
				if ( ! isset( $old_value->status ) || 0 == $old_value->status ) {
					$this->_plugin->alerts->Trigger( 6312, [
						'notification_name' => $this->built_in_notification_titles[ $data->id ],
						'EventType' => 'enabled',
					] );
				} else if ( $old_value->email !== $data->email || $old_value->phone !== $data->phone ) {
					$this->_plugin->alerts->Trigger( 6313, [
						'notification_name' => $this->built_in_notification_titles[ $data->id  ],
						'recipient' => self::create_recipient_string( $data->email, $data->phone ),
						'previous_recipient' => self::create_recipient_string( $old_value->email, $old_value->phone ),
						'EventType' => 'modified',
					] );
				}
				
				return 1;
			}


		} else {
			if ( isset( $old_value->status ) && 1 == $old_value->status ) {
				$this->_plugin->alerts->Trigger( 6312, [
					'notification_name' => $this->built_in_notification_titles[ $data->id  ],
					'EventType' => 'disabled',
				] );
			}
			$this->_plugin->notifications_util->DeleteGlobalSetting( $opt_name );
			return 0;
		}
	}

	/**
	 * Notifications View.
	 */
	public function Render() {
		if ( ! $this->_plugin->settings()->CurrentUserCan( 'edit' ) ) {
			$network_admin = get_site_option( 'admin_email' );
			$message       = esc_html__( 'To configure email notifications please contact the administrator of this multisite network on ', 'wp-security-audit-log' );
			$message      .= '<a href="mailto:' . esc_attr( $network_admin ) . '" target="_blank">' . esc_html( $network_admin ) . '</a>';
			wp_die( wp_kses( $message, $this->_plugin->allowed_html_tags ) );
		}

		if ( 'custom' === $this->current_tab ) :
			$this->notifications = $this->PrepareView();
			if ( self::WPSALP_NOTIF_ERROR === $this->notifications ) :
				?>
				<div class="error"><p><?php esc_html_e( 'Invalid request.', 'wp-security-audit-log' ); ?></p></div>
				<?php
			endif;
		elseif ( 'built-in' === $this->current_tab && isset( $_GET['send'] ) && 'summary' === sanitize_text_field( wp_unslash( $_GET['send'] ) ) ) :
			if ( isset( $_GET['error'] ) ) :
				?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'An error occurred while sending the daily summary email.', 'wp-security-audit-log' ); ?></p></div>
				<?php
			elseif ( isset( $_GET['activate'] ) ) :
				?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Daily summary email sent.', 'wp-security-audit-log' ); ?></p></div>
				<?php
			endif;
		endif;
		?>
		<nav id="wsal-tabs" class="nav-tab-wrapper">
			<?php foreach ( $this->wsal_extension_tabs as $tab_id => $tab ) : ?>
				<?php if ( ! empty( $this->current_tab ) ) : ?>
					<a href="<?php echo esc_url( $tab['link'] ); ?>" class="nav-tab <?php echo ( $tab_id === $this->current_tab ) ? 'nav-tab-active' : false; ?>">
						<?php echo esc_html( $tab['name'] ); ?>
					</a>
				<?php endif; ?>
			<?php endforeach; ?>
		</nav>
		<!-- Primary Tabs -->
		<div class="nav-tabs<?php echo 'custom' === $this->current_tab ? ' trigger-builder' : false; ?>">
			<?php if ( isset( $this->wsal_extension_tabs[ $this->current_tab ]['sections'] ) ) : ?>
				<nav id="wsal-sub-tabs" class="nav-tab-wrapper">
					<?php foreach ( $this->wsal_extension_tabs[ $this->current_tab ]['sections'] as $section_id => $section ) : ?>
						<a href="<?php echo esc_url( $section['link'] ); ?>" class="nav-tab <?php echo ( $section_id === $this->current_section ) ? 'nav-tab-active' : false; ?>"><?php echo esc_html( $section['name'] ); ?></a>
						<?php
					endforeach;
					?>
				</nav>
				<?php
			endif;

			if ( ! empty( $this->current_tab ) && ! empty( $this->wsal_extension_tabs[ $this->current_tab ]['render'] ) ) {
				call_user_func( $this->wsal_extension_tabs[ $this->current_tab ]['render'] );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Built-in Tab.
	 *
	 * @since 3.4
	 */
	public function tab_built_in() {
		// Save the Built-in Notifications.
		$alert_errors = array();
		if ( isset( $_POST['wsal-submit'] ) ) {
			check_admin_referer( 'wsal-built-in-notifications' );
			$alert_errors = $this->createBuilt_in();
		}

		// Twilio settings tab link.
		$twilio_settings = add_query_arg(
			array(
				'page' => 'wsal-settings',
				'tab'  => 'sms-provider',
			),
			network_admin_url( 'admin.php' )
		);

		// Get built-in events.
		$alert_built_in = $this->_plugin->notifications_util->GetBuiltIn();

		$phone_disabled = ! $this->_plugin->notifications_util->is_twilio_configured();
		$phone_help     = false;

		if ( $phone_disabled ) {
			/* Translators: Twilio settings hyperlink. */
			$phone_help = sprintf( __( 'Click %s to configure Twilio integration for SMS notifications.', 'wp-security-audit-log' ), '<a href="' . esc_url( $twilio_settings ) . '">' . __( 'here', 'wp-security-audit-log' ) . '</a>' );
		}
		?>
		<form id="wsal-trigger-form" method="post">
			<?php wp_nonce_field( 'wsal-built-in-notifications' ); ?>
			<p class="description">
				<?php
				echo sprintf(
					/* Translators: %s: Twilio settings link */
					esc_html__( 'Tick the check box and specify an email address or username to enable a notification. You can specify a phone number to send a SMS notification as well (%s). Click the Save Notifications button to save the changes.', 'wp-security-audit-log' ),
					'<a href="' . esc_url( $twilio_settings ) . '">' . esc_html__( 'Configure Twilio account integration', 'wp-security-audit-log' ) . '</a>'
				);
				?>
			</p>
			<p class="description">
				<?php
				echo sprintf(
					/* Translators: %s: Twilio settings link */
					esc_html__( 'You can create your own notification criteria in the %s tab.', 'wp-security-audit-log' ),
					'<a href="' . esc_url( $this->wsal_extension_tabs['custom']['link'] ) . '">' . esc_html__( 'Custom Notifications', 'wp-security-audit-log' ) . '</a>'
				);
				?>
			</p>
			<?php
			if ( ! $this->current_section ) {
				$this->section_wp_system( $alert_built_in, $alert_errors );
			} elseif ( 'user-profiles' === $this->current_section ) {
				$this->section_user_profiles( $alert_built_in, $alert_errors );
			} elseif ( 'content-changes' === $this->current_section ) {
				$this->section_content_changes( $alert_built_in, $alert_errors );
			} elseif ( 'multisite' === $this->current_section ) {
				$this->section_multisite( $alert_built_in, $alert_errors );
			} elseif ( 'woocommerce' === $this->current_section ) {
				$this->section_woocommerce( $alert_built_in, $alert_errors );
			}
			?>
			<p class="submit">
				<input type="button" id="wsal-test-notifications" class="button button-primary" value="<?php esc_attr_e( 'Test Notifications', 'wp-security-audit-log' ); ?>">
				<input type="submit" name="wsal-submit" id="wsal-submit" class="button button-primary" value="<?php esc_attr_e( 'Save Notifications', 'wp-security-audit-log' ); ?>">
			</p>
		</form>
		<!-- Enable/Disable Notifications -->

		<div id="wsal-test-notif-dialog" class="hidden">
			<form id="step-1">
				<fieldset>
					<label for="wsal-test-email">
						<?php esc_html_e( 'Specify an email address to where you would like to send a test email notification:', 'wp-security-audit-log' ); ?>
					</label>
					<p><input type="text" id="wsal-test-email"></p>
				</fieldset>
				<fieldset>
					<label for="wsal-test-number"><?php esc_html_e( 'Specify a mobile phone number to where you would like to send a test SMS notification:', 'wp-security-audit-log' ); ?></label>
					<p><input type="text" id="wsal-test-number" <?php echo $phone_disabled ? 'disabled' : false; ?>></p>
					<?php echo $phone_help ? '<p class="description">' . wp_kses( $phone_help, $this->_plugin->allowed_html_tags ) . '</p>' : false; ?>
				</fieldset>
				<fieldset>
					<p>
						<input type="button" id="send-test-notif" class="button button-primary" value="<?php esc_attr_e( 'Send', 'wp-security-audit-log' ); ?>">
						<input type="button" id="cancel-test-notif" class="button button-primary" value="<?php esc_attr_e( 'Cancel', 'wp-security-audit-log' ); ?>">
						<img class="loader hidden" src="<?php echo esc_url( trailingslashit( admin_url() ) ); ?>images/loading.gif" alt="Loader">
					</p>
				</fieldset>
			</form>
			<div id="step-2" class="hidden">
				<p class="response"></p>
				<input type="button" id="close-test-notif" class="button button-primary" value="<?php esc_attr_e( 'OK', 'wp-security-audit-log' ); ?>">
			</div>
		</div>
		<?php
	}

	/**
	 * Section: WP-System.
	 *
	 * @since 3.4
	 *
	 * @param array $alert_built_in - Built-in alerts.
	 * @param array $alert_errors   - Errors occurred while creating notification alert.
	 */
	private function section_wp_system( $alert_built_in, $alert_errors ) {
		$checked = array();
		$email   = array();
		$phone   = array();

		if ( ! empty( $alert_built_in ) && count( $alert_built_in ) > 0 ) {
			foreach ( $alert_built_in as $k => $v ) {
				$opt_value               = maybe_unserialize( $v->option_value );
				$checked[]               = $opt_value->viewState[0];
				$email[ $opt_value->id ] = $opt_value->email;
				$phone[ $opt_value->id ] = isset( $opt_value->phone ) ? $opt_value->phone : false;
				if ( ! empty( $opt_value->failUser ) ) {
					$fail_user_count = $opt_value->failUser;
				}
				if ( ! empty( $opt_value->failNotUser ) ) {
					$fail_not_user_count = $opt_value->failNotUser;
				}
			}
		}

		$phone_disabled = ! $this->_plugin->notifications_util->is_twilio_configured();
		$phone_help     = false;

		if ( $phone_disabled ) {
			// Twilio settings tab link.
			$twilio_settings = add_query_arg(
				array(
					'page' => 'wsal-settings',
					'tab'  => 'sms-provider',
				),
				network_admin_url( 'admin.php' )
			);

			/* Translators: Twilio settings hyperlink. */
			$phone_help = sprintf( __( 'Click %s to configure Twilio integration for SMS notifications.', 'wp-security-audit-log' ), '<a href="' . esc_url( $twilio_settings ) . '">' . __( 'here', 'wp-security-audit-log' ) . '</a>' );
		}
		?>
		<h3><?php esc_html_e( 'Daily Summary of Activity Log', 'wp-security-audit-log' ); ?></h3>
		<table class="form-table wsal-tab">
			<tbody class="widefat">
				<tr>
					<?php
					$daily_summary_switch   = $this->_plugin->GetGlobalSetting( 'disable-daily-summary', '0' );
					$daily_summary_email    = $this->_plugin->GetGlobalSetting( 'daily-summary-email', get_bloginfo( 'admin_email' ) );
					$test_daily_summary_url = add_query_arg( [
						'action'       => 'send_daily_summary',
						'wsalSecurity' => wp_create_nonce( 'wsal-notifications-script-nonce' )
					], admin_url( 'admin-ajax.php' ) );
					?>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="daily-summary-switch" id="daily-summary-switch" value="1" <?php checked( $daily_summary_switch, '0' ); ?> /></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="daily-summary-switch" class="built-in-row">
							<?php esc_html_e( 'Send me a summary of what happens every day.', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column"><input type="text" class="built-in-email" name="daily-summary-email" id="daily-summary-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php echo esc_attr( $daily_summary_email ); ?>" /></td>
					<td class="wsal-tab-column wsal-sms-column"><a href="<?php echo esc_url( $test_daily_summary_url ); ?>" class="button-primary"><?php esc_html_e( 'Send Summary Now', 'wp-security-audit-log' ); ?></a></td>
				</tr>
			</tbody>
		</table>

		<div class="wsal-table-heading">
			<h3><?php esc_html_e( 'Suspicious Activity', 'wp-security-audit-log' ); ?></h3>
			<?php echo $phone_help ? '<p class="description">' . wp_kses( $phone_help, $this->_plugin->allowed_html_tags ) . '</p>' : false; ?>
		</div>
		<table class="form-table wsal-tab">
			<tbody class="widefat">
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[16][toggle]" id="built-in_16" <?php echo checked( in_array( 'trigger_id_16', $checked, true ) ) ; ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_16" class="built-in-title">
							<?php esc_html_e( 'There are more than', 'wp-security-audit-log' ); ?>
							<?php $this->create_input( 16, ! empty( $fail_user_count ) ? $fail_user_count : 10 ); ?>
							<?php esc_html_e( 'failed WordPress logins for a WordPress user (Event ID 1002)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[16] ) && 2 === $alert_errors[16] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[16][email]" id="built-in_16-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[16] ) ) { echo esc_attr( $email[16] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[16][phone]" id="built-in_16-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[16] ) ) { echo esc_attr( $phone[16] ); } ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[17][toggle]" id="built-in_17" <?php echo checked( in_array( 'trigger_id_17', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_17" class="built-in-title">
							<?php esc_html_e( 'There are more than', 'wp-security-audit-log' ); ?>
							<?php $this->create_input( 17, ! empty( $fail_not_user_count ) ? $fail_not_user_count : 10 ); ?>
							<?php esc_html_e( 'failed logins of non existing users (Event ID 1003)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[17] ) && 2 === $alert_errors[17] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[17][email]" id="built-in_17-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[17] ) ) { echo esc_attr( $email[17] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[17][phone]" id="built-in_17-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[17] ) ) { echo esc_attr( $phone[17] ); } ?>">
					</td>
				</tr>
			</tbody>
		</table>

		<div class="wsal-table-heading">
			<h3><?php esc_html_e( 'WordPress Install Changes', 'wp-security-audit-log' ); ?></h3>
			<?php echo $phone_help ? '<p class="description">' . wp_kses( $phone_help, $this->_plugin->allowed_html_tags ) . '</p>' : false; ?>
		</div>
		<table class="form-table wsal-tab">
			<tbody class="widefat">
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[37][toggle]" id="built-in_37" <?php echo checked( in_array( 'trigger_id_37', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_37" class="built-in-title">
							<?php esc_html_e( 'WordPress was updated (Event ID 6004)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[37] ) && 2 === $alert_errors[37] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[37][email]" id="built-in_37-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[37] ) ) { echo esc_attr( $email[37] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[37][phone]" id="built-in_37-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[37] ) ) { echo esc_attr( $phone[37] ); } ?>">
					</td>
				</tr>
			</tbody>
		</table>

		<div class="wsal-table-heading">
			<h3><?php esc_html_e( 'Plugin Changes Notifications', 'wp-security-audit-log' ); ?></h3>
			<?php echo $phone_help ? '<p class="description">' . wp_kses( $phone_help, $this->_plugin->allowed_html_tags ) . '</p>' : false; ?>
		</div>
		<table class="form-table wsal-tab">
			<tbody class="widefat">
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[9][toggle]" id="built-in_9" <?php echo checked( in_array( 'trigger_id_9', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_9" class="built-in-title"><?php esc_html_e( 'New plugin is installed (Event ID 5000)', 'wp-security-audit-log' ); ?></label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[9] ) && 2 === $alert_errors[9] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[9][email]" id="built-in_9-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[9] ) ) { echo esc_attr( $email[9] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[9][phone]" id="built-in_9-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[9] ) ) { echo esc_attr( $phone[9] ); } ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[10][toggle]" id="built-in_10" <?php echo checked( in_array( 'trigger_id_10', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_10" class="built-in-title"><?php esc_html_e( 'Installed plugin is activated (Event ID 5001)', 'wp-security-audit-log' ); ?></label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[10] ) && 2 === $alert_errors[10] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[10][email]" id="built-in_10-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[10] ) ) { echo esc_attr( $email[10] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[10][phone]" id="built-in_10-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[10] ) ) { echo esc_attr( $phone[10] ); } ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[11][toggle]" id="built-in_11" <?php echo checked( in_array( 'trigger_id_11', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_11" class="built-in-title"><?php esc_html_e( 'Plugin file is modified (Event ID 2051)', 'wp-security-audit-log' ); ?></label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[11] ) && 2 === $alert_errors[11] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[11][email]" id="built-in_11-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[11] ) ) { echo esc_attr( $email[11] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[11][phone]" id="built-in_11-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[11] ) ) { echo esc_attr( $phone[11] ); } ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[38][toggle]" id="built-in_38" <?php echo checked( in_array( 'trigger_id_38', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_38" class="built-in-title"><?php esc_html_e( 'Installed plugin is deactivated (Event ID 5002)', 'wp-security-audit-log' ); ?></label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[38] ) && 2 === $alert_errors[38] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[38][email]" id="built-in_38-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[38] ) ) { echo esc_attr( $email[38] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[38][phone]" id="built-in_38-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[38] ) ) { echo esc_attr( $phone[38] ); } ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[39][toggle]" id="built-in_39" <?php echo checked( in_array( 'trigger_id_39', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_39" class="built-in-title"><?php esc_html_e( 'A plugin is uninstalled (Event ID 5003)', 'wp-security-audit-log' ); ?></label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[39] ) && 2 === $alert_errors[39] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[39][email]" id="built-in_39-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[39] ) ) { echo esc_attr( $email[39] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[39][phone]" id="built-in_39-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[39] ) ) { echo esc_attr( $phone[39] ); } ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[40][toggle]" id="built-in_40" <?php echo checked( in_array( 'trigger_id_40', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_40" class="built-in-title"><?php esc_html_e( 'Installed plugin is upgraded (Event ID 5004)', 'wp-security-audit-log' ); ?></label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[40] ) && 2 === $alert_errors[40] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[40][email]" id="built-in_40-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[40] ) ) { echo esc_attr( $email[40] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[40][phone]" id="built-in_40-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[40] ) ) { echo esc_attr( $phone[40] ); } ?>">
					</td>
				</tr>
			</tbody>
		</table>

		<div class="wsal-table-heading">
			<h3><?php esc_html_e( 'Themes Changes Notifications', 'wp-security-audit-log' ); ?></h3>
			<?php echo $phone_help ? '<p class="description">' . wp_kses( $phone_help, $this->_plugin->allowed_html_tags ) . '</p>' : false; ?>
		</div>
		<table class="form-table wsal-tab">
			<tbody class="widefat">
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[12][toggle]" id="built-in_12" <?php echo checked( in_array( 'trigger_id_12', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_12" class="built-in-title">
							<?php esc_html_e( 'New theme is installed (Event ID 5005)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[12] ) && 2 === $alert_errors[12] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[12][email]" id="built-in_12-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[12] ) ) { echo esc_attr( $email[12] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[12][phone]" id="built-in_12-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[12] ) ) { echo esc_attr( $phone[12] ); } ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[13][toggle]" id="built-in_13" <?php echo checked( in_array( 'trigger_id_13', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_13" class="built-in-title">
							<?php esc_html_e( 'Installed theme is activated (Event ID 5006)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[13] ) && 2 === $alert_errors[13] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[13][email]" id="built-in_13-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[13] ) ) { echo esc_attr( $email[13] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[13][phone]" id="built-in_13-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[13] ) ) { echo esc_attr( $phone[13] ); } ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[14][toggle]" id="built-in_14" <?php echo checked( in_array( 'trigger_id_14', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_14" class="built-in-title">
							<?php esc_html_e( 'Theme file is modified (Event ID 2046)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[14] ) && 2 === $alert_errors[14] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[14][email]" id="built-in_14-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[14] ) ) { echo esc_attr( $email[14] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[14][phone]" id="built-in_14-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[14] ) ) { echo esc_attr( $phone[14] ); } ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[41][toggle]" id="built-in_41" <?php echo checked( in_array( 'trigger_id_41', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_41" class="built-in-title">
							<?php esc_html_e( 'A theme is uninstalled (Event ID 5007)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[41] ) && 2 === $alert_errors[41] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[41][email]" id="built-in_41-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[41] ) ) { echo esc_attr( $email[41] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[41][phone]" id="built-in_41-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[41] ) ) { echo esc_attr( $phone[41] ); } ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[42][toggle]" id="built-in_42" <?php echo checked( in_array( 'trigger_id_42', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_42" class="built-in-title">
							<?php esc_html_e( 'Installed theme is updated (Event ID 5031)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[42] ) && 2 === $alert_errors[42] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[42][email]" id="built-in_42-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[42] ) ) { echo esc_attr( $email[42] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[42][phone]" id="built-in_42-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[42] ) ) { echo esc_attr( $phone[42] ); } ?>">
					</td>
				</tr>
			</tbody>
		</table>

		<div class="wsal-table-heading">
			<h3><?php esc_html_e( 'Critical Events', 'wp-security-audit-log' ); ?></h3>
			<?php echo $phone_help ? '<p class="description">' . wp_kses( $phone_help, $this->_plugin->allowed_html_tags ) . '</p>' : false; ?>
		</div>
		<table class="form-table wsal-tab">
			<tbody class="widefat">
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[15][toggle]" id="built-in_15" <?php echo checked( in_array( 'trigger_id_15', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_15" class="built-in-title"><?php esc_html_e( 'Critical Event is Generated', 'wp-security-audit-log' ); ?></label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[15] ) && 2 === $alert_errors[15] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[15][email]" id="built-in_15-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[15] ) ) { echo esc_attr( $email[15] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[15][phone]" id="built-in_15-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[15] ) ) { echo esc_attr( $phone[15] ); } ?>">
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Section: User Profiles.
	 *
	 * @since 3.4
	 *
	 * @param array $alert_built_in - Built-in alerts.
	 * @param array $alert_errors   - Errors occurred while creating notification alert.
	 */
	private function section_user_profiles( $alert_built_in, $alert_errors ) {
		$checked = array();
		$email   = array();
		$phone   = array();

		if ( ! empty( $alert_built_in ) && count( $alert_built_in ) > 0 ) {
			foreach ( $alert_built_in as $k => $v ) {
				$opt_value               = maybe_unserialize( $v->option_value );
				$checked[]               = $opt_value->viewState[0];
				$email[ $opt_value->id ] = $opt_value->email;
				$phone[ $opt_value->id ] = isset( $opt_value->phone ) ? $opt_value->phone : false;
			}
		}

		$phone_disabled = ! $this->_plugin->notifications_util->is_twilio_configured();
		$phone_help     = false;

		if ( $phone_disabled ) {
			// Twilio settings tab link.
			$twilio_settings = add_query_arg(
				array(
					'page' => 'wsal-settings',
					'tab'  => 'sms-provider',
				),
				network_admin_url( 'admin.php' )
			);

			/* Translators: Twilio settings hyperlink. */
			$phone_help = sprintf( __( 'Click %s to configure Twilio integration for SMS notifications.', 'wp-security-audit-log' ), '<a href="' . esc_url( $twilio_settings ) . '">' . __( 'here', 'wp-security-audit-log' ) . '</a>' );
		}
		?>
		<div class="wsal-table-heading">
			<h3><?php esc_html_e( 'User Activity', 'wp-security-audit-log' ); ?></h3>
			<?php echo $phone_help ? '<p class="description">' . wp_kses( $phone_help, $this->_plugin->allowed_html_tags ) . '</p>' : false; ?>
		</div>
		<table class="form-table wsal-tab">
			<tbody class="widefat">
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[1][toggle]" id="built-in_1" class="built-in" <?php echo checked( in_array( 'trigger_id_1', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_1" class="built-in-title">
							<?php esc_html_e( 'User logs in (Event ID 1000)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[1] ) && 2 === $alert_errors[1] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[1][email]" id="built-in_1-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[1] ) ) { echo esc_attr( $email[1] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[1][phone]" id="built-in_1-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[1] ) ) { echo esc_attr( $phone[1] ); } ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[8][toggle]" id="built-in_8" class="built-in" <?php echo checked( in_array( 'trigger_id_8', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_8" class="built-in-title">
							<?php esc_html_e( 'First time user logs in', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[8] ) && 2 === $alert_errors[8] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[8][email]" id="built-in_8-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[8] ) ) { echo esc_attr( $email[8] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[8][phone]" id="built-in_8-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[8] ) ) { echo esc_attr( $phone[8] ); } ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[3][toggle]" id="built-in_3" class="built-in" <?php echo checked( in_array( 'trigger_id_3', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_3" class="built-in-title">
							<?php esc_html_e( 'User changed password (Event ID 4003)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[3] ) && 2 === $alert_errors[3] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[3][email]" id="built-in_3-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[3] ) ) { echo esc_attr( $email[3] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[3][phone]" id="built-in_3-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[3] ) ) { echo esc_attr( $phone[3] ); } ?>">
					</td>
				</tr>
			</tbody>
		</table>

		<div class="wsal-table-heading">
			<h3><?php esc_html_e( 'User Profile Changes', 'wp-security-audit-log' ); ?></h3>
			<?php echo $phone_help ? '<p class="description">' . wp_kses( $phone_help, $this->_plugin->allowed_html_tags ) . '</p>' : false; ?>
		</div>
		<table class="form-table wsal-tab">
			<tbody class="widefat">
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[43][toggle]" id="built-in_43" class="built-in" <?php echo checked( in_array( 'trigger_id_43', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_43" class="built-in-title">
							<?php esc_html_e( 'User changed email address (Event IDs 4005, 4006)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[43] ) && 2 === $alert_errors[43] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[43][email]" id="built-in_43-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[43] ) ) { echo esc_attr( $email[43] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[43][phone]" id="built-in_43-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[43] ) ) { echo esc_attr( $phone[43] ); } ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[5][toggle]" id="built-in_5" class="built-in" <?php echo checked( in_array( 'trigger_id_5', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_5" class="built-in-title">
							<?php esc_html_e( 'User\'s role has changed (Event ID 4002)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[5] ) && 2 === $alert_errors[5] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[5][email]" id="built-in_5-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[5] ) ) { echo esc_attr( $email[5] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[5][phone]" id="built-in_5-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[5] ) ) { echo esc_attr( $phone[5] ); } ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[4][toggle]" id="built-in_4" class="built-in" <?php echo checked( in_array( 'trigger_id_4', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_4" class="built-in-title">
							<?php esc_html_e( 'User changed the password of another user (Event ID 4004)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[4] ) && 2 === $alert_errors[4] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[4][email]" id="built-in_4-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[4] ) ) { echo esc_attr( $email[4] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[4][phone]" id="built-in_4-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[4] ) ) { echo esc_attr( $phone[4] ); } ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[2][toggle]" id="built-in_2" class="built-in" <?php echo checked( in_array( 'trigger_id_2', $checked, true ) ); ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_2" class="built-in-title">
							<?php esc_html_e( 'New user is created (Event IDs 4000, 4001, 4012)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[2] ) && 2 === $alert_errors[2] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[2][email]" id="built-in_2-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[2] ) ) { echo esc_attr( $email[2] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[2][phone]" id="built-in_2-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php if ( ! empty( $phone[2] ) ) { echo esc_attr( $phone[2] ); } ?>">
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Section: Content Changes.
	 *
	 * @since 3.4
	 *
	 * @param array $alert_built_in - Built-in alerts.
	 * @param array $alert_errors   - Errors occurred while creating notification alert.
	 */
	private function section_content_changes( $alert_built_in, $alert_errors ) {
		$checked = array();
		$email   = array();
		$phone   = array();

		if ( ! empty( $alert_built_in ) && count( $alert_built_in ) > 0 ) {
			foreach ( $alert_built_in as $k => $v ) {
				$opt_value               = maybe_unserialize( $v->option_value );
				$checked[]               = $opt_value->viewState[0];
				$email[ $opt_value->id ] = $opt_value->email;
				$phone[ $opt_value->id ] = isset( $opt_value->phone ) ? $opt_value->phone : false;
			}
		}

		$phone_disabled = ! $this->_plugin->notifications_util->is_twilio_configured();
		$phone_help     = false;

		if ( $phone_disabled ) {
			// Twilio settings tab link.
			$twilio_settings = add_query_arg(
				array(
					'page' => 'wsal-settings',
					'tab'  => 'sms-provider',
				),
				network_admin_url( 'admin.php' )
			);

			/* Translators: Twilio settings hyperlink. */
			$phone_help = sprintf( __( 'Click %s to configure Twilio integration for SMS notifications.', 'wp-security-audit-log' ), '<a href="' . esc_url( $twilio_settings ) . '">' . __( 'here', 'wp-security-audit-log' ) . '</a>' );
		}
		?>
		<?php echo $phone_help ? '<p class="description">' . wp_kses( $phone_help, $this->_plugin->allowed_html_tags ) . '</p>' : false; ?>
		<table class="form-table wsal-tab">
			<tbody class="widefat">
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[20][toggle]" id="built-in_20" class="built-in" <?php echo in_array( 'trigger_id_20', $checked, true ) ? esc_attr( 'checked' ) : ''; ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_20" class="built-in-title">
							<?php esc_html_e( 'New content is published (Event ID 2001)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[20] ) && 2 === $alert_errors[20] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[20][email]" id="built-in_20-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[20] ) ) { echo esc_attr( $email[20] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[20][phone]" id="built-in_20-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php echo ! empty( $phone[20] ) ? esc_attr( $phone[20] ) : null; ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[21][toggle]" id="built-in_21" class="built-in" <?php echo in_array( 'trigger_id_21', $checked, true ) ? esc_attr( 'checked' ) : ''; ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_21" class="built-in-title">
							<?php esc_html_e( 'Content in a post, page or custom post type is changed (Event ID 2065)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[21] ) && 2 === $alert_errors[21] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[21][email]" id="built-in_21-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[21] ) ) { echo esc_attr( $email[21] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[21][phone]" id="built-in_21-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php echo ! empty( $phone[21] ) ? esc_attr( $phone[21] ) : null; ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column"><input type="checkbox" name="built-in[22][toggle]" id="built-in_22" class="built-in" <?php echo in_array( 'trigger_id_22', $checked, true ) ? esc_attr( 'checked' ) : ''; ?>></td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_22" class="built-in-title">
							<?php esc_html_e( 'Anything but content in a post is changed (such as date, category, status, parent page etc)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[22] ) && 2 === $alert_errors[22] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[22][email]" id="built-in_22-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" value="<?php if ( ! empty( $email[22] ) ) { echo esc_attr( $email[22] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[22][phone]" id="built-in_22-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php echo ! empty( $phone[22] ) ? esc_attr( $phone[22] ) : null; ?>">
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Section: Multisite.
	 *
	 * @since 3.4
	 *
	 * @param array $alert_built_in - Built-in alerts.
	 * @param array $alert_errors   - Errors occurred while creating notification alert.
	 */
	private function section_multisite( $alert_built_in, $alert_errors ) {
		$checked    = array();
		$email      = array();
		$phone      = array();
		$disabled   = false;
		$phone_help = false;

		if ( ! empty( $alert_built_in ) && count( $alert_built_in ) > 0 ) {
			foreach ( $alert_built_in as $k => $v ) {
				$opt_value               = maybe_unserialize( $v->option_value );
				$checked[]               = $opt_value->viewState[0];
				$email[ $opt_value->id ] = $opt_value->email;
				$phone[ $opt_value->id ] = isset( $opt_value->phone ) ? $opt_value->phone : false;
			}
		}

		if ( ! is_multisite() ) {
			$phone_disabled = 'disabled';
			$disabled       = 'disabled';
		} else {
			$phone_disabled  = ! $this->_plugin->notifications_util->is_twilio_configured() ? 'disabled' : false;
			$twilio_settings = add_query_arg(
				array(
					'page' => 'wsal-settings',
					'tab'  => 'sms-provider',
				),
				network_admin_url( 'admin.php' )
			);

			/* Translators: Twilio settings hyperlink. */
			$phone_help = sprintf( __( 'Click %s to configure Twilio integration for SMS notifications.', 'wp-security-audit-log' ), '<a href="' . esc_url( $twilio_settings ) . '">' . __( 'here', 'wp-security-audit-log' ) . '</a>' );
		}
		?>
		<?php echo $phone_help ? '<p class="description">' . wp_kses( $phone_help, $this->_plugin->allowed_html_tags ) . '</p>' : false; ?>
		<table class="form-table wsal-tab">
			<tbody class="widefat">
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column">
						<input type="checkbox" name="built-in[26][toggle]" id="built-in_26" class="built-in" <?php echo in_array( 'trigger_id_26', $checked, true ) ? esc_attr( 'checked' ) : ''; ?> <?php echo esc_attr( $disabled ); ?>>
					</td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_26" class="built-in-title"><?php esc_html_e( 'User granted super admin (Event ID 4008)', 'wp-security-audit-log' ); ?></label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[26] ) && 2 === $alert_errors[26] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[26][email]" id="built-in_26-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $disabled ); ?> value="<?php if ( ! empty( $email[26] ) ) { echo esc_attr( $email[26] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[26][phone]" id="built-in_26-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $phone_disabled ); ?> value="<?php echo ! empty( $phone[26] ) ? esc_attr( $phone[26] ) : null; ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column">
						<input type="checkbox" name="built-in[27][toggle]" id="built-in_27" class="built-in" <?php echo in_array( 'trigger_id_27', $checked, true ) ? esc_attr( 'checked' ) : ''; ?> <?php echo esc_attr( $disabled ); ?>>
					</td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_27" class="built-in-title">
							<?php esc_html_e( 'User revoked super admin (Event ID 4009)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[27] ) && 2 === $alert_errors[27] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[27][email]" id="built-in_27-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $disabled ); ?> value="<?php if ( ! empty( $email[27] ) ) { echo esc_attr( $email[27] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[27][phone]" id="built-in_27-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $phone_disabled ); ?> value="<?php echo ! empty( $phone[27] ) ? esc_attr( $phone[27] ) : null; ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column">
						<input type="checkbox" name="built-in[28][toggle]" id="built-in_28" class="built-in" <?php echo in_array( 'trigger_id_28', $checked, true ) ? esc_attr( 'checked' ) : ''; ?> <?php echo esc_attr( $disabled ); ?>>
					</td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_28" class="built-in-title">
							<?php esc_html_e( 'User added to site (Event ID 4010)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[28] ) && 2 === $alert_errors[28] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[28][email]" id="built-in_28-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $disabled ); ?> value="<?php if ( ! empty( $email[28] ) ) { echo esc_attr( $email[28] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[28][phone]" id="built-in_28-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $phone_disabled ); ?> value="<?php echo ! empty( $phone[28] ) ? esc_attr( $phone[28] ) : null; ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column">
						<input type="checkbox" name="built-in[29][toggle]" id="built-in_29" class="built-in" <?php echo in_array( 'trigger_id_29', $checked, true ) ? esc_attr( 'checked' ) : ''; ?> <?php echo esc_attr( $disabled ); ?>>
					</td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_29" class="built-in-title">
							<?php esc_html_e( 'User removed from site (Event ID 4011)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[29] ) && 2 === $alert_errors[29] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[29][email]" id="built-in_29-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $disabled ); ?> value="<?php if ( ! empty( $email[29] ) ) { echo esc_attr( $email[29] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[29][phone]" id="built-in_29-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $phone_disabled ); ?> value="<?php echo ! empty( $phone[29] ) ? esc_attr( $phone[29] ) : null; ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column">
						<input type="checkbox" name="built-in[30][toggle]" id="built-in_30" class="built-in" <?php echo in_array( 'trigger_id_30', $checked, true ) ? esc_attr( 'checked' ) : ''; ?> <?php echo esc_attr( $disabled ); ?>>
					</td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_30" class="built-in-title">
							<?php esc_html_e( 'Site changes', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[30] ) && 2 === $alert_errors[30] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[30][email]" id="built-in_30-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $disabled ); ?> value="<?php if ( ! empty( $email[30] ) ) { echo esc_attr( $email[30] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[30][phone]" id="built-in_30-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $phone_disabled ); ?> value="<?php echo ! empty( $phone[30] ) ? esc_attr( $phone[30] ) : null; ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column">
						<input type="checkbox" name="built-in[31][toggle]" id="built-in_31" class="built-in" <?php echo in_array( 'trigger_id_31', $checked, true ) ? esc_attr( 'checked' ) : ''; ?> <?php echo esc_attr( $disabled ); ?>>
					</td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_31" class="built-in-title">
							<?php esc_html_e( 'Activated theme on network (Event ID 5008)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[31] ) && 2 === $alert_errors[31] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[31][email]" id="built-in_31-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $disabled ); ?> value="<?php if ( ! empty( $email[31] ) ) { echo esc_attr( $email[31] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[31][phone]" id="built-in_31-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $phone_disabled ); ?> value="<?php echo ! empty( $phone[31] ) ? esc_attr( $phone[31] ) : null; ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column">
						<input type="checkbox" name="built-in[32][toggle]" id="built-in_32" class="built-in" <?php echo in_array( 'trigger_id_32', $checked, true ) ? esc_attr( 'checked' ) : ''; ?> <?php echo esc_attr( $disabled ); ?>>
					</td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_32" class="built-in-title">
							<?php esc_html_e( 'Deactivated theme from network (Event ID 5009)', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[32] ) && 2 === $alert_errors[32] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[32][email]" id="built-in_32-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $disabled ); ?> value="<?php if ( ! empty( $email[32] ) ) { echo esc_attr( $email[32] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[32][phone]" id="built-in_32-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $phone_disabled ); ?> value="<?php echo ! empty( $phone[32] ) ? esc_attr( $phone[32] ) : null; ?>">
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Section: WooCommerce.
	 *
	 * @since 3.4
	 *
	 * @param array $alert_built_in - Built-in alerts.
	 * @param array $alert_errors   - Errors occurred while creating notification alert.
	 */
	private function section_woocommerce( $alert_built_in, $alert_errors ) {
		$checked    = array();
		$email      = array();
		$phone      = array();
		$disabled   = false;
		$phone_help = false;

		if ( ! empty( $alert_built_in ) && count( $alert_built_in ) > 0 ) {
			foreach ( $alert_built_in as $k => $v ) {
				$opt_value               = maybe_unserialize( $v->option_value );
				$checked[]               = $opt_value->viewState[0];
				$email[ $opt_value->id ] = $opt_value->email;
				$phone[ $opt_value->id ] = isset( $opt_value->phone ) ? $opt_value->phone : false;
			}
		}

		if ( ! WpSecurityAuditLog::is_woocommerce_active() ) {
			$disabled       = 'disabled';
			$phone_disabled = 'disabled';
		} else {
			$phone_disabled  = ! $this->_plugin->notifications_util->is_twilio_configured();
			$twilio_settings = add_query_arg(
				array(
					'page' => 'wsal-settings',
					'tab'  => 'sms-provider',
				),
				network_admin_url( 'admin.php' )
			);

			/* Translators: Twilio settings hyperlink. */
			$phone_help = sprintf( __( 'Click %s to configure Twilio integration for SMS notifications.', 'wp-security-audit-log' ), '<a href="' . esc_url( $twilio_settings ) . '">' . __( 'here', 'wp-security-audit-log' ) . '</a>' );
		}
		?>
		<?php echo $phone_help ? '<p class="description">' . wp_kses( $phone_help, $this->_plugin->allowed_html_tags ) . '</p>' : false; ?>
		<table class="form-table wsal-tab">
			<tbody class="widefat">
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column">
						<input type="checkbox" name="built-in[33][toggle]" id="built-in_33" class="built-in" <?php echo in_array( 'trigger_id_33', $checked, true ) ? esc_attr( 'checked' ) : ''; ?> <?php echo esc_attr( $disabled ); ?>>
					</td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_33" class="built-in-title">
							<?php esc_html_e( 'Any product change', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[33] ) && 2 === $alert_errors[33] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[33][email]" id="built-in_33-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $disabled ); ?> value="<?php if ( ! empty( $email[33] ) ) { echo esc_attr( $email[33] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[33][phone]" id="built-in_33-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php echo ! empty( $phone[33] ) ? esc_attr( $phone[33] ) : null; ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column">
						<input type="checkbox" name="built-in[34][toggle]" id="built-in_34" class="built-in" <?php echo in_array( 'trigger_id_34', $checked, true ) ? esc_attr( 'checked' ) : ''; ?> <?php echo esc_attr( $disabled ); ?>>
					</td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_34" class="built-in-title">
							<?php esc_html_e( 'Any store settings change', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[34] ) && 2 === $alert_errors[34] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[34][email]" id="built-in_34-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $disabled ); ?> value="<?php if ( ! empty( $email[34] ) ) { echo esc_attr( $email[34] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[34][phone]" id="built-in_34-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php echo ! empty( $phone[34] ) ? esc_attr( $phone[34] ) : null; ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column">
						<input type="checkbox" name="built-in[35][toggle]" id="built-in_35" class="built-in" <?php echo in_array( 'trigger_id_35', $checked, true ) ? esc_attr( 'checked' ) : ''; ?> <?php echo esc_attr( $disabled ); ?>>
					</td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_35" class="built-in-title">
							<?php esc_html_e( 'Any coupon code changes', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[35] ) && 2 === $alert_errors[35] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[35][email]" id="built-in_35-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $disabled ); ?> value="<?php if ( ! empty( $email[35] ) ) { echo esc_attr( $email[35] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[35][phone]" id="built-in_35-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php echo ! empty( $phone[35] ) ? esc_attr( $phone[35] ) : null; ?>">
					</td>
				</tr>
				<tr>
					<td class="wsal-tab-column wsal-checkbox-column">
						<input type="checkbox" name="built-in[36][toggle]" id="built-in_36" class="built-in" <?php echo in_array( 'trigger_id_36', $checked, true ) ? esc_attr( 'checked' ) : ''; ?> <?php echo esc_attr( $disabled ); ?>>
					</td>
					<td class="wsal-tab-column wsal-details-column">
						<label for="built-in_36" class="built-in-title">
							<?php esc_html_e( 'Any orders changes', 'wp-security-audit-log' ); ?>
						</label>
					</td>
					<td class="wsal-tab-column wsal-email-column">
						<?php $class = ( ! empty( $alert_errors[36] ) && 2 === $alert_errors[36] ) ? ' invalid' : ''; ?>
						<input type="text" class="built-in-email<?php echo esc_attr( $class ); ?>" name="built-in[36][email]" id="built-in_36-email" placeholder="<?php esc_attr_e( 'Email', 'wp-security-audit-log' ); ?>" <?php echo esc_attr( $disabled ); ?> value="<?php if ( ! empty( $email[36] ) ) { echo esc_attr( $email[36] ); } ?>">
					</td>
					<td class="wsal-tab-column wsal-sms-column">
						<input type="text" class="built-in-phone<?php echo esc_attr( $class ); ?>" name="built-in[36][phone]" id="built-in_36-phone" placeholder="<?php esc_attr_e( 'Mobile Number', 'wp-security-audit-log' ); ?>" <?php echo $phone_disabled ? 'disabled' : false; ?> value="<?php echo ! empty( $phone[36] ) ? esc_attr( $phone[36] ) : null; ?>">
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Trigger Builder Tab.
	 *
	 * @since 3.4
	 */
	public function tab_trigger_builder() {
		// Notifications count.
		$all_notifications_count = is_array( $this->notifications ) ? count( $this->notifications ) : 0;

		// @codingStandardsIgnoreStart
		$notification_action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : false;
		// @codingStandardsIgnoreEnd

		if ( $notification_action ) {
			if ( 'view_disabled' === $notification_action ) {
				$disabled_notifications_count = $all_notifications_count;
			} else {
				$disabled_notifications_count = count( $this->_plugin->notifications_util->GetDisabledNotifications() );
			}
		} else {
			$disabled_notifications_count = count( $this->_plugin->notifications_util->GetDisabledNotifications() );
		}

		$nonce             = wp_create_nonce( 'nonce-notifications-view' );
		$view_all_url      = add_query_arg( 'tab', $this->current_tab, $this->GetUrl() );
		$disable_url       = $view_all_url . '&action=disable_notification&wsalSecurity=' . $nonce;
		$enable_url        = $view_all_url . '&action=enable_notification&wsalSecurity=' . $nonce;
		$delete_url        = $view_all_url . '&action=delete_notification&wsalSecurity=' . $nonce;
		$view_disabled_url = $view_all_url . '&action=view_disabled&wsalSecurity=' . $nonce;
		$edit_notif_class  = $this->_plugin->views->FindByClassName( 'WSAL_NP_EditNotification' );
		if ( false === $edit_notif_class ) {
			$edit_notif_class = new WSAL_NP_EditNotification( $this->_plugin );
		}

		$edit_url    = $edit_notif_class->GetUrl() . '&action=wsal_edit_notification&wsalSecurity=' . wp_create_nonce( 'nonce-edit-notification' );
		$description = sprintf(
			/* Translators: WSAL Notifications Documentation hyperlink */
			esc_html__( 'Use the trigger builder to build any type of criteria that triggers email and / or SMS notifications. Refer to the %s for more detailed information.', 'wp-security-audit-log' ),
			'<a href="https://wpactivitylog.com/support/kb/getting-started-sms-email-notifications/#creating-notification-builder" target="_blank">' . esc_html__( 'WordPress notifications documentation', 'wp-security-audit-log' ) . '</a>'
		);
		echo '<p class="description">' . wp_kses( $description, $this->_plugin->allowed_html_tags ) . '</p>';
		?>
		<a href="<?php echo esc_url( add_query_arg( 'page', 'wsal-np-addnotification', network_admin_url( 'admin.php' ) ) ); ?>" class="button add-new-notification"><?php esc_html_e( 'Add New', 'wp-security-audit-log' ); ?></a>
		<?php if ( ! empty( $this->notifications ) ) : ?>
<script type="text/javascript">
	jQuery( document ).ready( function( $ ) {
		$( '.wsal_js_no_click' ).on( 'click', function( e ) { e.preventDefault(); return false; } );
		// Disable the "view disabled" link if there are no disabled notifications.
			<?php if ( ! $disabled_notifications_count ) : ?>
	$( '#wsal-view-disabled-link' ).on( 'click', function() { return false; } );
			<?php endif; ?>
});
</script>
			<br>
			<ul class="subsubsub">
				<li class="all"><a class="current" href="<?php echo esc_url( $view_all_url ); ?>"><?php esc_html_e( 'All', 'wp-security-audit-log' ); ?> <span class="count">(<?php echo esc_html( $all_notifications_count ); ?>)</span></a> |</li>
				<li class="disabled"><a href="<?php echo esc_url( $view_disabled_url ); ?>" id="wsal-view-disabled-link"><?php esc_html_e( 'Disabled', 'wp-security-audit-log' ); ?> <span class="count">(<?php echo esc_html( $disabled_notifications_count ); ?>)</span></a></li>
			</ul>
			<form method="GET">
				<input type="hidden" name="page" value="<?php echo esc_attr( $this->GetSafeViewName() ); ?>">
				<input type="hidden" name="tab" value="<?php echo esc_attr( $this->current_tab ); ?>">
				<input type="hidden" name="wsalSecurity" value="<?php echo esc_attr( $nonce ); ?>">
				<input type="hidden" name="action" value="search">
				<p class="search-box">
					<label for="notification-search-input" class="screen-reader-text"><?php esc_html_e( 'Search Notifications', 'wp-security-audit-log' ); ?>:</label>
					<input type="search" name="s" id="notification-search-input" maxlength="125" value="<?php echo esc_attr( $this->search_term ); ?>" />
					<input type="submit" class="button" id="search-submit" value="<?php esc_attr_e( 'Search Notifications', 'wp-security-audit-log' ); ?>" />
				</p>
			</form>
			<form method="POST" id="notifications-filter">
				<input type="hidden" name="page" value="<?php echo esc_attr( $this->GetSafeViewName() ); ?>">
				<input type="hidden" name="tab" value="<?php echo esc_attr( $this->current_tab ); ?>">
				<input type="hidden" name="wsalSecurity" value="<?php echo esc_attr( $nonce ); ?>">
				<input type="hidden" name="action" value="bulk">
				<div class="tablenav top">
					<div class="alignleft actions bulkactions">
						<select id="bulk" name="bulk">
							<option selected="selected" value="-1"><?php esc_html_e( 'Bulk actions', 'wp-security-audit-log' ); ?></option>
							<option class="hide-if-no-js" value="enable"><?php esc_html_e( 'Enable', 'wp-security-audit-log' ); ?></option>
							<option class="hide-if-no-js" value="disable"><?php esc_html_e( 'Disable', 'wp-security-audit-log' ); ?></option>
							<option value="delete"><?php esc_html_e( 'Delete', 'wp-security-audit-log' ); ?></option>
						</select>
						<input type="submit" value="<?php esc_attr_e( 'Apply', 'wp-security-audit-log' ); ?>" class="button action" id="doaction" name=""/>
					</div>
					<br class="clear">
				</div>
				<table id="wsal-notif-table" class="wp-list-table widefat plugins">
					<thead>
						<tr>
							<td class="manage-column column-cb check-column" id="cb" scope="col">
								<label for="cb-select-all-1" class="screen-reader-text"><?php esc_html_e( 'Select All', 'wp-security-audit-log' ); ?></label>
								<input type="checkbox" id="cb-select-all-1">
							</td>
							<th class="manage-column column-title" scope="col"><?php esc_html_e( 'Title', 'wp-security-audit-log' ); ?></th>
							<th class="manage-column column-author" scope="col"></th>
							<th class="manage-column column-date" scope="col"></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td class="manage-column column-cb check-column" scope="col">
								<label for="cb-select-all-2" class="screen-reader-text"><?php esc_html_e( 'Select All', 'wp-security-audit-log' ); ?></label>
								<input type="checkbox" id="cb-select-all-2">
							</td>
							<th class="manage-column column-title" scope="col"><?php esc_html_e( 'Title', 'wp-security-audit-log' ); ?></th>
							<th class="manage-column column-author" scope="col"></th>
							<th class="manage-column column-date" scope="col"></th>
						</tr>
					</tfoot>
					<tbody id="the-list">
						<?php
						$phone_disabled = ! $this->_plugin->notifications_util->is_twilio_configured();
						// ================================
						// SHOW NOTIFICATIONS
						// ================================
						foreach ( $this->notifications as $k => $entry ) :
							$entry_id     = $entry->option_id;
							$opt_value    = maybe_unserialize( $entry->option_value );
							$title        = $opt_value->title;
							$enabled      = $opt_value->status;
							$edit_url    .= '&id=' . $entry_id;
							$entry_class  = $entry_id;
							$entry_class .= $enabled ? ' active' : ' inactive';
							$notif_id     = str_replace( WpSecurityAuditLog::OPTIONS_PREFIX, '', $entry->option_name );
							?>
							<tr class="entry-<?php echo esc_attr( $entry_class ); ?>" id="entry-<?php echo esc_attr( $entry_id ); ?>">
								<th class="check-column" scope="row">
									<label for="cb-select-1" class="screen-reader-text"><?php echo esc_html__( 'Select', 'wp-security-audit-log' ) . ' ' . esc_html( $title ); ?></label>
									<input type="checkbox" value="<?php echo esc_attr( $entry_id ); ?>" name="entries[]" id="cb-select-1">
								</th>
								<td class="post-title page-title column-title">
									<strong><a title="<?php esc_attr_e( 'Edit this notification', 'wp-security-audit-log' ); ?>" href="<?php echo esc_url( $edit_url ); ?>" class="row-title"><?php echo esc_html( $title ); ?></a></strong>
									<div class="row-actions">
										<span class="edit"><a title="<?php esc_attr_e( 'Edit this notification', 'wp-security-audit-log' ); ?>" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'wp-security-audit-log' ); ?></a> | </span>
										<span class="view">
											<?php
											if ( $enabled ) :
												echo sprintf( '<a title="%s" href="%s" >%s</a>', esc_html__( 'Disable this notification', 'wp-security-audit-log' ), esc_url( $disable_url ) . '&id=' . esc_attr( $entry_id ), esc_html__( 'Disable', 'wp-security-audit-log' ) );
											else :
												echo sprintf( '<a title="%s" href="%s" >%s</a>', esc_html__( 'Enable this notification', 'wp-security-audit-log' ), esc_url( $enable_url ) . '&id=' . esc_attr( $entry_id ), esc_html__( 'Enable', 'wp-security-audit-log' ) );
											endif;
											?>
										| </span>
										<span class="trash"><?php echo sprintf( '<a href="%s" title="%s" class="submitdelete">%s</a>', esc_url( $delete_url ) . '&id=' . esc_attr( $entry_id ), esc_attr__( 'Delete this notification', 'wp-security-audit-log' ), esc_html__( 'Delete', 'wp-security-audit-log' ) ); ?></span>
									</div>
								</td>
								<td class="column-author"><input class="page-title-action" type="button" value="<?php esc_attr_e( 'Send Test Email', 'wp-security-audit-log' ); ?>" onclick="sendTriggerTestNotif( '<?php echo esc_attr( $notif_id ); ?>', 'email' )"></td>
								<td class="column-date"><input class="page-title-action" type="button" value="<?php esc_attr_e( 'Send Test SMS', 'wp-security-audit-log' ); ?>" onclick="sendTriggerTestNotif( '<?php echo esc_attr( $notif_id ); ?>', 'sms' )" <?php echo $phone_disabled ? 'disabled' : false; ?>></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<div class="tablenav bottom">
					<div class="alignleft actions bulkactions">
						<select id="bulk2" name="bulk2">
							<option selected="selected" value="-1"><?php esc_html_e( 'Bulk actions', 'wp-security-audit-log' ); ?></option>
							<option class="hide-if-no-js" value="enable"><?php esc_html_e( 'Enable', 'wp-security-audit-log' ); ?></option>
							<option class="hide-if-no-js" value="disable"><?php esc_html_e( 'Disable', 'wp-security-audit-log' ); ?></option>
							<option value="delete"><?php esc_html_e( 'Delete', 'wp-security-audit-log' ); ?></option>
						</select>
						<input type="submit" value="<?php esc_attr_e( 'Apply', 'wp-security-audit-log' ); ?>" class="button action" id="doaction2" name=""/>
					</div>
					<div class="alignleft actions"></div>
					<br class="clear">
				</div>
			</form>
			<div id="wsal-test-notification-dialog" class="hidden">
				<div class="loader hidden"><div class="wsal-lds-ellipsis"><div></div><div></div><div></div><div></div></div></div>
				<div class="response hidden">
					<p></p>
					<input type="button" class="close button-primary" value="<?php esc_html_e( 'OK', 'wp-security-audit-log' ); ?>">
				</div>
			</div>
			<?php
		elseif ( ! empty( $alert_built_in ) && count( $alert_built_in ) > 0 ) :
			// Do nothing.
		else :
			// Display the search form.
			if ( $this->_search_view ) :
				?>
				<div class="notice notice-error"><p><?php esc_html_e( 'No notifications found to match your search.', 'wp-security-audit-log' ); ?></p></div>
				<form method="get" id="notifications-filter">
					<input type="hidden" name="page" value="<?php echo esc_attr( $this->GetSafeViewName() ); ?>">
					<input type="hidden" name="tab" value="<?php echo esc_attr( $this->current_tab ); ?>">
					<input type="hidden" name="wsalSecurity" value="<?php echo esc_attr( $nonce ); ?>">
					<input type="hidden" name="action" value="search">
					<p class="search-box">
						<label for="notification-search-input" class="screen-reader-text"><?php esc_html_e( 'Search Notifications', 'wp-security-audit-log' ); ?>:</label>
						<input type="search" name="s" id="notification-search-input" maxlength="125" value="<?php echo esc_attr( $this->search_term ); ?>" />
						<input type="submit" value="<?php esc_attr_e( 'Search Notifications', 'wp-security-audit-log' ); ?>" class="button" id="search-submit" />
					</p>
				</form>
				<?php
			else :
				echo '<div class="no-notifications-msg"><p>' . wp_kses( __( 'No notifications found. Click the <code>Add New</code> button above to create one.', 'wp-security-audit-log' ), $this->_plugin->allowed_html_tags ) . '</p></div>';
			endif;
		endif;
		?>
		<!-- Tab Built-in Notifications-->
		<?php
	}

	/**
	 * Templates Tab.
	 *
	 * @since 3.4
	 */
	public function tab_templates() {
		// Save the Email Templates.
		if ( isset( $_POST['wsal-template'] ) ) { // @codingStandardsIgnoreLine
			$this->save_template();
		}
		?>
		<?php if ( 'sms' === $this->current_section ) : ?>
			<p class="description"><?php esc_html_e( 'You can modify the default notification SMS template from here.', 'wp-security-audit-log' ); ?></p>
			<h3><?php esc_html_e( 'Default SMS Template', 'wp-security-audit-log' ); ?></h3>
			<p class="description"><?php esc_html_e( 'This is the default template for SMS notifications. The maximum number of characters for a SMS is 160, so if you configure longer notifications you will be charged for multiple SMS notifications.', 'wp-security-audit-log' ); ?></p>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'You can modify the default notification email template from here.', 'wp-security-audit-log' ); ?></p>
			<h3><?php esc_html_e( 'Default Email Template', 'wp-security-audit-log' ); ?></h3>
			<p class="description"><?php esc_html_e( 'This is the default template. You can override this default template with notification specific template which you can modify when using the Trigger Builder.', 'wp-security-audit-log' ); ?></p>
			<?php
		endif;
		$data = array();
		if ( 'sms' === $this->current_section ) {
			$data = $this->_plugin->notifications_util->get_sms_template( 'builder' );
			$this->formTemplate( 'sms', $data );
		} else {
			$data = $this->_plugin->notifications_util->GetEmailTemplate( 'builder' );
			$this->formTemplate( 'email', $data );
		}
	}

	/**
	 * Email Template for Notifications.
	 *
	 * @param string $type - Type of section.
	 * @param array  $data - Email data.
	 */
	private function formTemplate( $type, $data = null ) {
		if ( 'sms' === $type ) :
			$is_url_shortner           = $this->_plugin->GetGlobalBooleanSetting( 'is-url-shortner' );
			$url_shortner_access_token = $this->_plugin->GetGlobalSetting( 'url-shortner-access-token' );
		endif;

		$form_action = ! $this->current_section ? $this->get_current_url() : $this->get_current_url() . '&section=' . $this->current_section;
		?>
		<div id="notification-template-postbox" class="postbox">
			<div class="inside">
				<form action="<?php echo esc_url( $form_action ); ?>" method="post">
					<?php wp_nonce_field( 'wsal-notifications-templates' ); ?>
					<input type="hidden" name="template" value="<?php echo 'email' === $type ? 'email' : 'sms'; ?>" />
					<table class="form-table wsal-tab" id="tab-templates">
						<tbody class="widefat" id="<?php echo esc_attr( $type ); ?>-template">
							<?php if ( 'sms' !== $type ) : ?>
								<tr>
									<th><label for="columns"><?php esc_html_e( 'Subject', 'wp-security-audit-log' ); ?></label></th>
									<td><fieldset><input class="field" type="text" name="subject" placeholder="Subject *" value="<?php echo ! empty( $data['subject'] ) ? esc_html( $data['subject'] ) : null; ?>"></fieldset></td>
								</tr>
							<?php endif; ?>
							<tr>
								<th><label for="columns"><?php esc_html_e( 'Body', 'wp-security-audit-log' ); ?></label></th>
								<td>
									<fieldset>
										<?php
										$content   = ! empty( $data['body'] ) ? stripslashes( $data['body'] ) : '';
										$editor_id = 'body';
										if ( 'sms' === $type ) {
											$settings = array(
												'media_buttons' => false,
												'editor_height' => 200,
												'tinymce' => false,
												'quicktags' => false,
											);
										} else {
											$settings = array(
												'media_buttons' => false,
												'editor_height' => 400,
											);
										}
										wp_editor( $content, $editor_id, $settings );
										?>
									</fieldset>
									<br>
									<label for="body" class="tags">
										<?php
										$label  = 'sms' !== $type ? __( 'HTML is accepted.', 'wp-security-audit-log' ) : '';
										$label .= ' ' . __( 'Available template tags:', 'wp-security-audit-log' );
										echo esc_html( $label );
										?>
										<br>
										<ul>
											<?php
											$template_tags = array();
											if ( 'sms' !== $type ) :
												$template_tags = $this->_plugin->notifications_util->get_email_template_tags();
											else :
												$template_tags = $this->_plugin->notifications_util->get_sms_template_tags();
											endif;
											foreach ( $template_tags as $tag => $desc ) {
												echo '<li>' . esc_html( $tag ) . ' — ' . esc_html( $desc ) . '</li>';
											}
											?>
										</ul>
									</label>
								</td>
							</tr>
							<?php if ( 'sms' === $type ) : ?>
								<tr>
									<th><label for="is_url_shortner"><?php esc_html_e( 'Shorten URLs', 'wp-security-audit-log' ); ?></label></th>
									<td>
										<fieldset>
											<label>
												<input type="checkbox" name="is_url_shortner" id="is_url_shortner" value="1" <?php checked( $is_url_shortner ); ?>>
												<span><?php esc_html_e( 'Shorten URLs with Bit.ly', 'wp-security-audit-log' ); ?></span>
											</label>
											<br>
											<label><input type="text" class="field-text" name="url_shortner_access_token" placeholder="<?php esc_attr_e( 'Bit.ly Access Token', 'wp-security-audit-log' ); ?>" value="<?php echo esc_attr( $url_shortner_access_token ); ?>" <?php echo ! $is_url_shortner ? 'disabled' : false; ?>></label>
											<br>
											<p class="description">
												<?php
												/* Translators: Bit.ly documentation hyperlink */
												echo sprintf( esc_html__( 'The URL shortener works for URLs in the {message} variable and will not shorten the URL of the website in the variable {site}. Shorten all URLs in the message using the %s.', 'wp-security-audit-log' ), '<a href="https://dev.bitly.com/v4_documentation.html" target="_blank">' . esc_html__( 'Bit.ly URL Shortener API', 'wp-security-audit-log' ) . '</a>' );
												?>
											</p>
										</fieldset>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
					<!-- Tab Email Templates -->
					<input type="submit" name="wsal-template" value="Save Template" class="button-primary">
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Save Notification Template.
	 */
	public function save_template() {
		// Check nonce.
		check_admin_referer( 'wsal-notifications-templates' );
		if ( isset( $_POST['template'] ) ) {
			$opt_name = sanitize_text_field( wp_unslash( $_POST['template'] ) ) . '-template-builder';

			if ( ! empty( $_POST['body'] ) ) {
				$data             = new stdClass();
				$data->subject    = ! empty( $_POST['subject'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['subject'] ) ) ) : '';
				$data->body       = 'sms' === $this->current_section ? wp_unslash( $_POST['body'] ) : wpautop( wp_unslash( $_POST['body'] ) );
				$data->date_added = time();

				$this->_plugin->alerts->Trigger( 6318, [
					'EventType' => 'modified',
					'template_name' => ( 'sms' === $this->current_section ) ? 'SMS' : 'Email',
				]);

				$result           = $this->_plugin->notifications_util->AddGlobalSetting( $opt_name, $data );

				if ( 'sms' === $this->current_section ) {
					$this->_plugin->SetGlobalBooleanSetting( 'is-url-shortner', isset( $_POST['is_url_shortner'] ) );
					$this->_plugin->SetGlobalSetting( 'url-shortner-access-token', isset( $_POST['url_shortner_access_token'] ) ? sanitize_text_field( wp_unslash( $_POST['url_shortner_access_token'] ) ) : false );
				}

				if ( false === $result ) :
					?>
					<div class="error"><p><?php esc_html_e( 'Template could not be saved.', 'wp-security-audit-log' ); ?></p></div>
				<?php else : ?>
					<div class="updated"><p><?php esc_html_e( 'Template successfully saved.', 'wp-security-audit-log' ); ?></p></div>
					<?php
				endif;
			} else {
				$this->_plugin->notifications_util->DeleteGlobalSetting( $opt_name );
			}
		}
	}

	/**
	 * Create Select Input for Settings.
	 *
	 * @param string  $id          - Input element id.
	 * @param integer $max         - Max number of values to create.
	 * @param integer $selectedNum - Selected value.
	 */
	public function CreateSelect( $id, $max, $selectedNum ) {
		?>
		<select name="built-in-count_<?php echo $id; ?>" id="built-in_<?php echo $id; ?>-count" >
			<?php
			for ( $num = 1; $num <= $max; $num++ ) {
				$selected = '';
				if ( ! empty( $selectedNum ) && $selectedNum == $num ) {
					$selected = ' selected';
				}
				?>
				<option value="<?php echo $num; ?>"<?php echo $selected; ?>><?php echo $num; ?></option>
				<?php
			}
			?>
		</select>
		<?php
	}

	/**
	 * Method: Generate Input tag for setting.
	 *
	 * @param int $id - Input ID.
	 * @param int $value - Input value.
	 */
	public function create_input( $id, $value ) {
		// Make sure both parameters are not empty.
		if ( ! empty( $id ) && ! empty( $value ) ) {
			?>
			<input type="text" name="built-in[<?php echo esc_attr( $id ); ?>][count]"
				id="built-in_<?php echo esc_attr( $id ); ?>-count"
				value="<?php echo esc_attr( $value ); ?>" />
			<?php
		}
	}

	/**
	 * Method: Get View Footer.
	 */
	public function Footer() {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				// tab handling code
				// jQuery('#wsal-tabs>a').click(function(){
				// 	jQuery('#wsal-tabs>a').removeClass('nav-tab-active');
				// 	jQuery('table.wsal-tab').hide();
				// 	jQuery(jQuery(this).addClass('nav-tab-active').attr('href')).show();
				// });
				// show relevant tab
				// var hashlink = jQuery('#wsal-tabs>a[href="' + location.hash + '"]');
				// if (hashlink.length) {
				// 	hashlink.click();
				// } else {
				// 	jQuery('#wsal-tabs>a:first').click();
				// }

				jQuery('#wsal-trigger-form input[type=checkbox]').unbind('change').change(function() {
					current = this.name+'-email';
					count = this.name+'-count';
					if (jQuery(this).is(':checked')) {
						jQuery('#'+current).prop('required', true);
						if (jQuery('#'+count).length) {
							jQuery('#'+count).prop('required', true);
						}
					} else {
						jQuery('#'+current).removeProp('required');
						if (jQuery('#'+count).length) {
							jQuery('#'+count).removeProp('required');
						}
					}
				});

				// Verify the format of phone numbers.
				jQuery('#wsal-trigger-form input.built-in-phone').on( 'change keyup paste', function() {
					var phoneNumber = jQuery( this ).val();
					var phonePattern = /((?:\+|00)[17]?|(?:\+|00)[1-9]\d{0,2}?|(?:\+|00)1\d{3}?)?(0\d|\([0-9]{3}\)|[1-9]{0,3})(?:([0-9]{2}){4}|((?:[0-9]{2}){4})|([0-9]{3}[0-9]{4})|([0-9]{7}))/g;
					var formSubmit = jQuery( '#wsal-submit' );

					jQuery( this ).removeClass( 'invalid' );
					formSubmit.removeAttr( 'disabled' );
					if ( phoneNumber && ! phonePattern.test( phoneNumber ) ) {
						jQuery( this ).addClass( 'invalid' );
						formSubmit.attr( 'disabled', true );
					}
				});
			});
		</script>
		<?php
	}

	/**
	 * Test Notifications.
	 *
	 * Test email address or phone number for notifications.
	 *
	 * @since 3.4
	 */
	public function test_notifications() {
		if ( ! $this->_plugin->settings()->CurrentUserCan( 'edit' ) ) {
			echo wp_json_encode(
				array(
					'success' => false,
					'message' => esc_html__( 'You do not have sufficient permissions to perform this test.', 'wp-security-audit-log' ),
				)
			);
			exit();
		}

		// Verify nonce.
		if ( ! isset( $_POST['wsalSecurity'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wsalSecurity'] ) ), 'wsal-notifications-script-nonce' ) ) {
			echo wp_json_encode(
				array(
					'success' => false,
					'message' => esc_html__( 'Nonce verification failed. Please refresh and try again.', 'wp-security-audit-log' ),
				)
			);
			exit();
		}

        // Get email and phone number.
        $email_address = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : false;
        $phone_number  = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : false;

        // Response message.
        $response       = array();
        $email_response = false;
        $phone_response = false;

        if ( $email_address ) {
            if ( is_email( $email_address ) ) {
                $subject        = '[TEST] ' . __( 'Test email notification from the WP Activity Log plugin.', 'wp-security-audit-log' );
                $content        = '<p>' . __( 'This is a test email notification sent with the WP Activity Log plugin.', 'wp-security-audit-log' ) . '</p>';
                $email_response = $this->_plugin->notifications_util->SendNotificationEmail( $email_address, $subject, $content );
            } else {
                $response[] = __( 'Email address is invalid.', 'wp-security-audit-log' );
            }
        }

        if ( $phone_number ) {
            $verified_number = $this->check_phone_number( $phone_number ); // Verify phone number.
            if ( $verified_number ) {
                $content        = __( 'This is a test SMS notification sent with the WP Activity Log plugin.', 'wp-security-audit-log' );
                $phone_response = $this->_plugin->notifications_util->send_notification_sms( $verified_number, $content );
            } else {
                $response[] = __( 'Phone number is invalid.', 'wp-security-audit-log' );
            }
        }

        if ( $email_response && true !== $phone_response ) {
            $success = true;
            $message = esc_html__( 'Email sent successfully.', 'wp-security-audit-log' );
        } elseif ( true === $phone_response && ! $email_response ) {
            $success = true;
            $message = esc_html__( 'SMS sent successfully.', 'wp-security-audit-log' );
        } elseif ( true === $phone_response && $email_response ) {
            $success = true;
            $message = esc_html__( 'Email / SMS sent successfully.', 'wp-security-audit-log' );
        } elseif ( ! empty( $response ) ) {
            $success = false;
            $message = implode( '<br>', $response );
        } else {
            $success = false;

            if ( is_string( $phone_response ) ) {
                /* Translators: Support hyperlink. */
                $message = '<span>' . sprintf( esc_html__( 'There was a problem sending the SMS. Below is the error we got back from the SMS provider. Please contact us on %s if you need assistance with this issue.', 'wp-security-audit-log' ), '<a href="mailto:support@wpwhitesecurity.com" target="_blank">support@wpwhitesecurity.com</a>' ) . '</span><br><br><span>' . esc_html( $phone_response ) . '</span>';
            } else {
                /* Translators: Support email hyperlink */
                $message = sprintf( esc_html__( 'There are some problems sending the test email / SMS. Please contact us on %s to assist you with this problem.', 'wp-security-audit-log' ), '<a href="mailto:support@wpsecurityauditlog.com" target="_blank">support@wpsecurityauditlog.com</a>' );
            }
        }

        echo wp_json_encode(
            array(
                'success' => $success,
                'message' => $message,
            )
        );

		exit();
	}

	/**
	 * Validate Phone Number.
	 *
	 * @since 3.4
	 *
	 * @param string $phone - Phone number.
	 * @return mixed
	 */
	public function check_phone_number( $phone ) {
		$filtered_phone_number = filter_var( $phone, FILTER_SANITIZE_NUMBER_INT ); // Allow +, - and . in phone number.
		$filtered_phone_number = str_replace( array( '-', ' ', '(', ')' ), '', $filtered_phone_number ); // Remove "-" from number.
		if ( strlen( $filtered_phone_number ) < 10 || strlen( $filtered_phone_number ) > 14 ) { // Check the lenght of number.
			return false;
		}
		return $filtered_phone_number;
	}

	/**
	 * Trigger Builder Test Notifications.
	 *
	 * Test email address or phone number for trigger builder notifications.
	 *
	 * @since 3.4
	 */
	public function trigger_test_notification() {
		if ( ! $this->_plugin->settings()->CurrentUserCan( 'edit' ) ) {
			echo esc_html__( 'You do not have sufficient permissions to perform this test.', 'wp-security-audit-log' ) . '</p>';
			exit();
		}

		// Verify nonce.
		if ( ! isset( $_POST['wsalSecurity'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wsalSecurity'] ) ), 'wsal-notifications-script-nonce' ) ) {
			echo esc_html__( 'Nonce verification failed. Please refresh and try again.', 'wp-security-audit-log' );
			exit();
		}

		// Get notification data.
        $notification_name = isset( $_POST['notificationId'] ) ? sanitize_text_field( wp_unslash( $_POST['notificationId'] ) ) : false;
        $notification_type = isset( $_POST['notificationType'] ) ? sanitize_text_field( wp_unslash( $_POST['notificationType'] ) ) : false;
        $notification      = $this->_plugin->GetGlobalSetting( $notification_name );

        if ( ! $notification ) {
            echo esc_html__( 'Unknown notification.', 'wp-security-audit-log' );
        } else {
            $date     = $this->_plugin->notifications_util->GetFormattedDatetime();
            $blogname = $this->_plugin->notifications_util->get_blog_domain();

            if ( 'email' === $notification_type ) {
                $template = $this->_plugin->notifications_util->get_test_email_template( $notification );
                $message  = 'This is a test email notification from the WP Activity Log plugin.';

                $search  = array_keys( $this->_plugin->notifications_util->get_email_template_tags() );
	            $test_metadata = '';
	            $test_links = '';
                $replace = array( $notification->title, $blogname, 'Dummy User', 'Dummy User First Name', 'Dummy User Last Name', 'Dummy Role', $date, '9999', 'Notification', $message, $test_metadata, $test_links, '127.0.0.1', 'email', 'test' );

                $subject = str_replace( $search, $replace, $template['subject'] );
                $subject = '[TEST] ' . $subject;
                $content = str_replace( $search, $replace, stripslashes( $template['body'] ) );

                if ( isset( $notification->email ) && $this->_plugin->notifications_util->SendNotificationEmail( $notification->email, $subject, $content ) ) {
                    echo esc_html__( 'Email sent successfully.', 'wp-security-audit-log' );
                } else {
                    /* Translators: Support hyperlink. */
                    echo sprintf( esc_html__( 'There are some problems sending the test email. Please contact us on %s to assist you with this problem.', 'wp-security-audit-log' ), '<a href="mailto:support@wpsecurityauditlog.com" target="_blank">support@wpsecurityauditlog.com</a>' );
                }
            } elseif ( 'sms' === $notification_type ) {
                $template = $this->_plugin->notifications_util->get_test_sms_template( $notification );
                $message  = 'This is a test SMS notification from the WP Activity Log plugin.';

                $search  = array_keys( $this->_plugin->notifications_util->get_sms_template_tags() );
                $replace = array( $blogname, 'Dummy User', 'Dummy Role', $date, '9999', 'Notification', $message, '127.0.0.1', 'email', 'test' );

                $content  = str_replace( $search, $replace, $template );
                $response = $this->_plugin->notifications_util->send_notification_sms( $notification->phone, $content );

                if ( ! $notification->phone ) {
                    echo esc_html__( 'Mobile number is not set for this notification.', 'wp-security-audit-log' );
                } elseif ( is_string( $response ) ) {
                    /* Translators: Support hyperlink. */
                    echo '<span>' . sprintf( esc_html__( 'There was a problem sending the SMS. Below is the error we got back from the SMS provider. Please contact us on %s if you need assistance with this issue.', 'wp-security-audit-log' ), '<a href="mailto:support@wpwhitesecurity.com" target="_blank">support@wpwhitesecurity.com</a>' ) . '</span><br><br><span>' . esc_html( $response ) . '</span>';
                } elseif ( $response ) {
                    echo esc_html__( 'SMS sent successfully.', 'wp-security-audit-log' );
                } else {
                    /* Translators: Support hyperlink. */
                    echo sprintf( esc_html__( 'There are some problems sending the test SMS. Please contact us on %s to assist you with this problem.', 'wp-security-audit-log' ), '<a href="mailto:support@wpsecurityauditlog.com" target="_blank">support@wpsecurityauditlog.com</a>' );
                }
            } else {
                echo esc_html__( 'Unknown notification type.', 'wp-security-audit-log' );
            }
        }

		exit();
	}
}
