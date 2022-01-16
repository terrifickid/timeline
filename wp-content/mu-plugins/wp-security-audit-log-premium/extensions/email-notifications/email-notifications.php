<?php
/**
 * Extension: Email Notifications
 *
 * Email notifications extension for wsal.
 *
 * @since 2.7.0
 * @package wsal
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Holds the option prefix
 */
define( 'WSAL_OPT_PREFIX', 'notification-' );

/**
 * Holds the maximum number of notifications a user is allowed to add
 */
define( 'WSAL_MAX_NOTIFICATIONS', 50 );

/**
 * Holds the name of the cache key if cache available
 */
define( 'WSAL_CACHE_KEY', '__NOTIF_CACHE__' );

/**
 * Debugging true|false
 */
define( 'WSAL_DEBUG_NOTIFICATIONS', false );

/**
 * Class WSAL_NP_Plugin
 *
 * @package wp-security-audit-log
 */
class WSAL_NP_Plugin {

	/**
	 * Instance of WpSecurityAuditLog.
	 *
	 * @var WpSecurityAuditLog
	 */
	protected $wsal = null;

	/**
	 * Notifications Cache.
	 *
	 * @var array
	 */
	private $notifications = null;

	/**
	 * Cache Expiration Limit.
	 *
	 * Currently set to 12 hrs. 43200 = (12 * 60 * 60).
	 *
	 * @var int
	 */
	private $cache_expire = 43200;

	/**
	 * Method: Constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		add_action( 'wsal_init', array( $this, 'wsal_init' ) );
		add_action( 'wp_login_failed', array( $this, 'counter_login_failure' ) );
	}

	/**
	 * Triggered when the main plugin is loaded.
	 *
	 * @param WpSecurityAuditLog $wsal - Instance of WpSecurityAuditLog.
	 * @see WpSecurityAuditLog::load()
	 */
	public function wsal_init( WpSecurityAuditLog $wsal ) {
		// Autoload the files in `classes` folder.
		$wsal->autoloader->Register( 'WSAL_NP_', dirname( __FILE__ ) . '/classes' );
		$wsal_common      = new WSAL_NP_Common( $wsal );
		$wsal->notifications_util = $wsal_common;

		if ( isset( $wsal->views ) ) {
			// Add notifications view.
			$wsal->views->AddFromClass( 'WSAL_NP_Notifications' );

			// @codingStandardsIgnoreStart
			$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : false;
			// @codingStandardsIgnoreEnd

			if ( $current_page ) {
				$add_notif  = new WSAL_NP_AddNotification( $wsal );
				$edit_notif = new WSAL_NP_EditNotification( $wsal );
				$settings   = $wsal->views->FindByClassName( 'WSAL_Views_Settings' );

				// Get views names.
				$add_notif_page  = $add_notif->GetSafeViewName();
				$edit_notif_page = $edit_notif->GetSafeViewName();
				$settings_page   = $settings->GetSafeViewName();

				switch ( $current_page ) {
					case $add_notif_page:
						$wsal->views->AddFromClass( 'WSAL_NP_AddNotification' );
						break;
					case $edit_notif_page:
						$wsal->views->AddFromClass( 'WSAL_NP_EditNotification' );
						break;
					case $settings_page:
						new WSAL_NP_SMSProviderSettings();
						break;
					default:
						//  fallback for any other pages would go here
						break;
				}
			}
		}

		if ( isset( $wsal->alerts ) ) {
			$wsal->alerts->AddFromClass( 'WSAL_NP_Notifier' );
		}

		//  register alert formatters for sms and email notifications
		add_filter( 'wsal_alert_formatters', array( $this, 'register_alert_formatters' ), 10, 1 );

		// Set main plugin class object.
		$this->wsal = $wsal;

		// Remove built-in content notifications.
		$this->remove_built_in_content_notif();
	}

	/**
	 * Method: Remove built-in published content & modified
	 * content notifications.
	 *
	 * @since 3.2
	 */
	public function remove_built_in_content_notif() {
		// Check if the built in notifications exists.
		if ( $this->wsal->GetGlobalSetting( 'notification-built-in-6', false ) ) {
			// Remove the built-in notification: Published content is modified.
			$this->wsal->notifications_util->DeleteGlobalSetting( 'wsal-notification-built-in-6' );
		}
		if ( $this->wsal->GetGlobalSetting( 'notification-built-in-7', false ) ) {
			// Remove the built-in notification: Content is modified.
			$this->wsal->notifications_util->DeleteGlobalSetting( 'wsal-notification-built-in-7' );
		}
	}

	/**
	 * Triggered by Failed Login Hook.
	 *
	 * Increase the limit changes the max value when you call: $Notifications->CreateSelect().
	 *
	 * @param string $username - Username.
	 */
	public function counter_login_failure( $username ) {
		if ( empty( $this->wsal ) ) {
			$this->wsal_init( WpSecurityAuditLog::GetInstance() );
			remove_action( 'wsal_init', array( $this, 'wsal_init' ) );
		}

		// leave if the user is still logged in for some reason
		if ( is_user_logged_in() ) {
			return;
		}

		$alert_code = 1003;
		$username   = array_key_exists( 'log', $_POST ) ? wp_unslash( $_POST['log'] ) : $username;
		$user       = get_user_by( 'login', $username );
		$alert_code = $user ? 1002 : $alert_code;

		if ( empty( $this->wsal->alerts ) ) {
			$this->wsal->alerts = new WSAL_AlertManager( $this->wsal );
		}

		if ( ! $this->wsal->alerts->IsEnabled( $alert_code ) ) {
			return;
		}

		$site_id             = function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 0;
		$ip                  = $this->wsal->settings()->GetMainClientIP();
		$this->notifications = wp_cache_get( WSAL_CACHE_KEY );

		if ( false === $this->notifications ) {
			$this->notifications = $this->wsal->notifications_util->GetNotifications();
			wp_cache_set( WSAL_CACHE_KEY, $this->notifications, null, $this->cache_expire );
		}

		if ( ! empty( $this->notifications ) ) {
			foreach ( $this->notifications as $k => $v ) {
				$not_info = maybe_unserialize( $v->option_value );
				$enabled  = intval( $not_info->status );

				if ( 0 == $enabled ) {
					continue;
				}

				if ( ! empty( $not_info->failUser ) && $user ) {
					if ( $this->wsal->notifications_util->IsLoginFailureLimit( $not_info->failUser, $ip, $site_id, $user, true ) ) {
						break;
					}
					$this->wsal->notifications_util->CounterLoginFailure( $ip, $site_id, $user );

					if ( $this->wsal->notifications_util->IsLoginFailureLimit( $not_info->failUser, $ip, $site_id, $user ) ) {
						$this->send_suspicious_activity( $not_info, $ip, $site_id, $alert_code, $username );
					}
				}

				if ( ! empty( $not_info->failNotUser ) && ! $user ) {
					if ( $this->wsal->notifications_util->IsLoginFailureLimit( $not_info->failNotUser, $ip, $site_id, null, true ) ) {
						break;
					}
					$this->wsal->notifications_util->CounterLoginFailure( $ip, $site_id, $user );

					if ( $this->wsal->notifications_util->IsLoginFailureLimit( $not_info->failNotUser, $ip, $site_id, null ) ) {
						$this->send_suspicious_activity( $not_info, $ip, $site_id, $alert_code, $username );
					}
				}
			}
		}
	}

	/**
	 * Send Suspicious Activity email.
	 *
	 * Load the template and replace the tags with tha arguments passed.
	 *
	 * @param object $not_info   - Info object.
	 * @param string $ip         - IP Address.
	 * @param int    $site_id    - Site ID.
	 * @param int    $alert_code - Alert code.
	 * @param string $username   - Username.
	 */
	private function send_suspicious_activity( $not_info, $ip, $site_id, $alert_code, $username ) {
		$title         = $not_info->title;
		$email_address = $not_info->email;

		$alert      = $this->wsal->alerts->GetAlert( $alert_code );
		$user       = get_user_by( 'login', $username );
		$user_role  = '';
		$first_name = '';
		$last_name  = '';

		if ( ! empty( $user ) ) {
			$user_info  = get_userdata( $user->ID );
			$user_role  = implode( ', ', $user_info->roles );
			$first_name = $user_info->first_name;
			$last_name  = $user_info->last_name;
		}

		$date     = $this->wsal->notifications_util->GetFormattedDatetime();
		$blogname = $this->wsal->notifications_util->get_blog_domain();
		$search   = array( '%Attempts%', '%Msg%', '%LinkFile%', '%LogFileLink%', '%LogFileText%', '%URL%', '%LineBreak%' );

		if ( ! empty( $not_info->failUser ) ) {
			$replace = array( $not_info->failUser, '', '', '', '', '', ' ' );
		} elseif ( ! empty( $not_info->failNotUser ) ) {
			$replace = array( $not_info->failNotUser, '', '', '', '', '', ' ' );
		}

		$message = str_replace( $search, $replace, $alert->mesg );
		$search  = array_keys( $this->wsal->notifications_util->get_email_template_tags() );

		$alert_formatter = WSAL_AlertFormatterFactory::getFormatter('email');
		$metadata = $alert->get_formatted_metadata( $alert_formatter, $this->_alert_data, 0 );
		$hyperlinks = $alert->get_formatted_hyperlinks( $alert_formatter, $this->_alert_data, 0 );
		$replace = array( $title, $blogname, $username, $first_name, $last_name, $user_role, $date, $alert_code, $this->wsal->notifications_util->get_alert_severity( $alert_code ), $message, $metadata, $hyperlinks, $ip, $alert->object, $alert->event_type );

		$template = $this->wsal->notifications_util->GetEmailTemplate( 'builder' );
		$subject  = str_replace( $search, $replace, $template['subject'] );
		$content  = str_replace( $search, $replace, stripslashes( $template['body'] ) );

		// Email notification.
		$this->wsal->notifications_util->SendNotificationEmail( $email_address, $subject, $content, $alert_code );

		if ( ! empty( $not_info->phone ) ) {
			$search_sms_tags  = array_keys( $this->wsal->notifications_util->get_sms_template_tags() );
			$replace_sms_tags = array( $blogname, $username, $user_role, $date, $alert_code, $this->wsal->notifications_util->get_alert_severity( $alert_code ), $message, $ip, $alert->object, $alert->event_type );

			$sms_template = $this->wsal->notifications_util->get_sms_template( 'builder' );
			$sms_content  = str_replace( $search_sms_tags, $replace_sms_tags, $sms_template['body'] );

			// SMS notification.
			$this->wsal->notifications_util->send_notification_sms( $not_info->phone, $sms_content );
		}
	}

	/**
	 * Uninstall routine.
	 *
	 * @since 2.7.0
	 */
	public function email_notifications_uninstall_cleanup() {
		$this->wsal->DeleteByPrefix( WSAL_OPT_PREFIX );
	}

	/**
	 * @param array $formatters Formatter definition arrays.
	 *
	 * @return array
	 * @since 4.2.1
	 * @see WSAL_AlertFormatterFactory
	 */
	public function register_alert_formatters( $formatters ) {

		$email_configuration = WSAL_AlertFormatterConfiguration::buildHtmlConfiguration()
			->setIsJsInLinksAllowed( false )
			->setSupportsMetadata( false )
			->setSupportsHyperlinks( false );

		$formatters[ 'sms'] = WSAL_AlertFormatterConfiguration::buildPlainTextConfiguration();
		$formatters[ 'email'] = $email_configuration;

		return $formatters;
	}
}
