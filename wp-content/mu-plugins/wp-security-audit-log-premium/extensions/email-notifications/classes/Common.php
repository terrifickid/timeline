<?php
/**
 * Class: Utility Class
 *
 * Utility class for common functions.
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
 * Class WSAL_NP_Common
 *
 * Utility class, used for all the common functions used in the plugin.
 *
 * @package wsal
 * @subpackage email-notifications
 */
class WSAL_NP_Common {

	/**
	 * Instance of WpSecurityAuditLog.
	 *
	 * @var WpSecurityAuditLog
	 */
	public $wsal = null;

	/**
	 * Daily Summary Report
	 *
	 * Schedule hook for daily report summary.
	 *
	 * @since 3.2.4
	 *
	 * @var string
	 */
	public static $schedule_hook = 'wsal_daily_summary_report';

	const TRANSIENT_FAILED_COUNT         = 'wsal-notifications-failed-known-count';
	const TRANSIENT_FAILED_UNKNOWN_COUNT = 'wsal-notifications-failed-unknown-count';

	/**
	 * Method: Constructor.
	 *
	 * @param WpSecurityAuditLog $wsal - Instance of WpSecurityAuditLog.
	 */
	public function __construct( WpSecurityAuditLog $wsal ) {
		$this->wsal = $wsal;

		$this->schedule_daily_summary();
	}

	/**
	 * Schedule daily summary report.
	 *
	 * @since 3.2.4
	 */
	public function schedule_daily_summary() {
		if ( '0' === $this->wsal->GetGlobalSetting( 'disable-daily-summary', '0' ) ) {
			// Hook scheduled method.
			add_action( self::$schedule_hook, array( $this, 'send_daily_summary_report' ) );

			if ( 'thirtyminutes' === wp_get_schedule( self::$schedule_hook ) ) {
				$timestamp = wp_next_scheduled( self::$schedule_hook );
				if ( $timestamp ) {
					wp_unschedule_event( $timestamp, self::$schedule_hook );
				}
				wp_schedule_event( $timestamp, 'hourly', self::$schedule_hook );
			} elseif ( ! wp_next_scheduled( self::$schedule_hook ) ) {
				// Schedule event if there isn't any already.
				wp_schedule_event(
					time(), // Timestamp.
					'hourly', // Frequency.
					self::$schedule_hook // Scheduled event.
				);
			}
		} else {
			// Clear the scheduled hook if feature is disabled.
			wp_clear_scheduled_hook( self::$schedule_hook );
		}
	}

	/**
	 * Send Daily Summary Report
	 *
	 * @since 3.2.4
	 *
	 * @param boolean $test - Test email.
	 * @return boolean
	 */
	public function send_daily_summary_report( $test = false ) {
		if ( ! $test && '01' !== $this->calculate_daily_hour() ) {
			return;
		}

		$daily_notif = new WSAL_NP_DailyNotification( $this->wsal );
		$report      = $daily_notif->get_report( $test );

		// Summary email address.
		$summary_emails = $this->wsal->GetGlobalSetting( 'daily-summary-email', get_bloginfo( 'admin_email' ) );
		$summary_emails = explode( ',', $summary_emails );
		$result         = false;
		if ( $summary_emails && isset( $report->subject ) && isset( $report->body ) ) {
			foreach ( $summary_emails as $email ) {
				$result = $this->SendNotificationEmail( $email, $report->subject, $report->body );
			}
		}
		return $result;
	}

	/**
	 * Creates an unique random number.
	 *
	 * @param int $size The length of the number to generate.
	 * @return string
	 */
	public function UniqueNumber( $size = 20 ) {
		$numbers = range( 0, 100 );
		shuffle( $numbers );
		$n = implode( '', array_slice( $numbers, 0, $size ) );
		return substr( $n, 0, $size );
	}

	/**
	 * Set the setting by name with the given value.
	 *
	 * @param string $option - Option name.
	 * @param mixed  $value - Value.
     *
	 */
	public function AddGlobalSetting( $option, $value ) {
		$this->DeleteCacheNotif();
		$this->wsal->SetGlobalSetting( $option, $value );
	}

	/**
	 * Update the setting by name with the given value.
	 *
	 * @param string $option - Option name.
	 * @param mixed  $value - Value.
	 * @return boolean result
	 */
	public function UpdateGlobalSetting( $option, $value ) {
		$this->DeleteCacheNotif();
		return $this->wsal->SetGlobalSetting( $option, $value );
	}

	/**
	 * Delete the setting by name.
	 *
	 * @param string $option - Option name.
	 * @return boolean result
	 *
	 */
	public function DeleteGlobalSetting( $option ) {
		$this->DeleteCacheNotif();
		return $this->wsal->DeleteGlobalSetting( $option );
	}

	/**
	 * Get the option by name.
	 *
	 * @param string $option - Option name.
	 * @return mixed value
	 */
	public function GetSettingByName( $option ) {
		return $this->wsal->GetGlobalSetting( $option );
	}

	/**
	 * Delete cache.
	 */
	public function DeleteCacheNotif() {
		if ( function_exists( 'wp_cache_delete' ) ) {
			wp_cache_delete( WSAL_CACHE_KEY );
		}
	}

	/**
	 * Retrieve the appropriate posts table name.
	 *
	 * @param wpdb $wpdb
	 * @return string
	 */
	public function GetPostsTableName( $wpdb ) {
		$pfx = $this->GetDbPrefix( $wpdb );
		if ( $this->wsal->IsMultisite() ) {
			global $blog_id;
			$bid = ( $blog_id == 1 ? '' : $blog_id . '_' );
			return $pfx . $bid . 'posts';
		}
		return $pfx . 'posts';
	}

	/**
	 * Return query to check post ids on a single
	 * or a multisite.
	 *
	 * @param wpdb $wpdb    — WP DB class.
	 * @param int  $post_id — Post ID.
	 * @return string
	 */
	public function get_post_id_query( $wpdb, $post_id ) {
		$prefix = $this->GetDbPrefix( $wpdb );

		if ( $this->wsal->IsMultisite() ) {
			$sql_query   = 'SELECT SUM(postcount) as postcount from (';
			$sites       = get_sites();
			$count_sites = count( $sites );
			$counter     = 1;

			foreach ( $sites as $site ) {
				if ( '1' !== $site->blog_id ) {
					$sql_query .= 'SELECT COUNT(ID) as postcount FROM ' . $prefix . $site->blog_id . '_posts WHERE ID = ' . $post_id;
				} else {
					$sql_query .= ' SELECT COUNT(ID) as postcount FROM ' . $prefix . 'posts WHERE ID = ' . $post_id;
				}
				if ( $counter < $count_sites ) {
					$sql_query .= ' UNION ALL ';
				}
				$counter++;
			}
			$sql_query .= ' ) as postcount';
			return $sql_query;
		}
		return sprintf( 'SELECT COUNT(ID) FROM ' . $prefix . 'posts WHERE ID = %d', $post_id );
	}

	/**
	 * Retrieve the appropriate db prefix.
	 *
	 * @param wpdb $wpdb
	 * @return mixed
	 */
	public function GetDbPrefix( $wpdb ) {
		if ( $this->wsal->IsMultisite() ) {
			return $wpdb->base_prefix;
		}
		return $wpdb->prefix;
	}

	/**
	 * Validate the input from a condition.
	 *
	 * @param string $string
	 * @return mixed
	 */
	public function ValidateInput( $string ) {
		$string = preg_replace( '/<script[^>]*?>.*?<\/script>/i', '', $string );
		$string = preg_replace( '/<[\/\!]*?[^<>]*?>/i', '', $string );
		$string = preg_replace( '/<style[^>]*?>.*?<\/style>/i', '', $string );
		$string = preg_replace( '/<![\s\S]*?--[ \t\n\r]*>/i', '', $string );
		return preg_replace( "/[^a-z0-9.':\-_]/i", '', $string );
	}

	/**
	 * Validate a partial IP address.
	 *
	 * @param string $ip
	 * @return bool
	 */
	public function IsValidPartialIP( $ip ) {
		if ( ! $ip or strlen( trim( $ip ) ) == 0 ) {
			return false;
		}
		$ip    = trim( $ip );
		$parts = explode( '.', $ip );
		if ( count( $parts ) <= 4 ) {
			foreach ( $parts as $part ) {
				if ( $part > 255 || $part < 0 ) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Retrieve list of role names.
	 *
	 * @return array List of role names.
	 */
	public function GetRoleNames() {
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		return $wp_roles->get_names();
	}

	/**
	 * @internal
	 * @param string $key The key to pad
	 * @return string
	 */
	public function PadKey( $key ) {
		if ( strlen( $key ) == 1 ) {
			$key = str_pad( $key, 4, '0', STR_PAD_LEFT );
		}
		return $key;
	}

	/**
	 * Used in the form validation.
	 *
	 * @return string
	 */
	public function DateValidFormat() {
		return WSAL_Helpers_Assets::DATEPICKER_DATE_FORMAT;
	}

	/**
	 * Time Format from WordPress General Settings.
	 *
	 * @return string
	 */
	public function GetTimeFormat() {
		return $this->wsal->settings()->GetTimeFormat();
	}

	/**
	 * Check time 24 hours.
	 *
	 * @return bool true/false
	 */
	public function Show24Hours() {
		$format = $this->GetTimeFormat();
		return strpos( $format, 'g' ) === false;
	}

	/**
	 * Validate a condition.
	 *
	 * @param string $input_value - Input text box.
	 * @param object $select_values - Select 2.
	 * @param object $comparisons - Select 3 / Comparison.
	 * @param object $post_status - Select 4 / Post Status.
	 * @param object $post_types - Select 5 / Post Type.
	 * @param object $user_roles - Select 6 / User Role.
	 * @param object $object_types - Select 7 / Object.
	 * @param object $event_types - Select 8 / Event type.
	 * @return bool|int|mixed
	 */
	public function ValidateCondition( $input_value, $select_values, $comparisons, $post_status, $post_types, $user_roles, $object_types, $event_types ) {
		$values   = $select_values->data;
		$selected = $select_values->selected;

		if ( ! isset( $values[ $selected ] ) ) {
			return array(
				'error' => __( 'The form is not valid. Please reload the page and try again.', 'wp-security-audit-log' ),
			);
		}

		// Get what's selected.
		$what = strtoupper( $values[ $selected ] );

		if ( 'EVENT ID' === $what ) { // if ALERT CODE.
			$alerts = $this->wsal->alerts->GetAlerts();
			if ( empty( $alerts ) ) {
				return array(
					'error' => __( 'Internal Error. Please reload the page and try again.', 'wp-security-audit-log' ),
				);
			}
			// Ensure this is a valid Alert Code.
			$keys = array_keys( $alerts ); // Get event ids.
			$keys = array_diff( $keys, $this->wsal->alerts->get_deprecated_events() ); // Remove deprecated events.
			$keys = array_map( array( $this, 'PadKey' ), $keys );
			if ( ! in_array( $input_value, $keys ) ) {
				return array(
					'error' => __( 'The EVENT ID is not valid.', 'wp-security-audit-log' ),
				);
			}
		} elseif ( 'USERNAME' === $what ) { // IF USERNAME.
			$length = strlen( $input_value );
			if ( $length > 50 ) {
				return array(
					'error' => __( 'The USERNAME is not valid. Maximum of 50 characters allowed.', 'wp-security-audit-log' ),
				);
			}
			// Make sure this is a valid username.
			if ( ! username_exists( $input_value ) ) {
				return array(
					'error' => __( 'The USERNAME does not exist.', 'wp-security-audit-log' ),
				);
			}
		} elseif ( 'USER ROLE' === $what ) { // IF USER ROLE.
			$e = sprintf( __( '%s is not valid', 'wp-security-audit-log' ), $what );
			if ( '0' !== $input_value && empty( $input_value ) ) {
				return array(
					'error' => $e,
				);
			}

			if ( ! isset( $user_roles->data[ $user_roles->selected ] ) ) {
				return array(
					'error' => __( 'Selected USER ROLE is not valid.', 'wp-security-audit-log' ),
				);
			}
		} elseif ( 'SOURCE IP' === $what ) { // IF SOURCE IP.
			$length = strlen( $input_value );
			if ( $length > 15 ) {
				return array(
					'error' => __( 'The SOURCE IP is not valid. Maximum of 15 characters allowed.', 'wp-security-audit-log' ),
				);
			}
			$val_s3 = $comparisons->data[ $comparisons->selected ];
			if ( ! $val_s3 ) {
				return array(
					'error' => __( 'The form is not valid. Please reload the page and try again.', 'wp-security-audit-log' ),
				);
			}
			if ( 'IS EQUAL' == $val_s3 ) {
				$r = filter_var( $input_value, FILTER_VALIDATE_IP );
				if ( $r ) {
					return true;
				} else {
					return array(
						'error' => __( 'The SOURCE IP is not valid.', 'wp-security-audit-log' ),
					);
				}
			}
			$r = $this->IsValidPartialIP( $input_value );
			if ( $r ) {
				return true;
			} else {
				return array(
					'error' => __( 'The SOURCE IP fragment is not valid.', 'wp-security-audit-log' ),
				);
			}
		} elseif ( 'DATE' === $what ) { // DATE.
			$date_format = $this->DateValidFormat();
			if ( 'mm-dd-yyyy' == $date_format || 'dd-mm-yyyy' == $date_format ) {
				// Regular expression to match date format mm-dd-yyyy or dd-mm-yyyy.
				$reg_ex = '/^\d{1,2}-\d{1,2}-\d{4}$/';
			} else {
				// Regular expression to match date format yyyy-mm-dd.
				$reg_ex = '/^\d{4}-\d{1,2}-\d{1,2}$/';
			}
			$r = preg_match( $reg_ex, $input_value );
			if ( $r ) {
				return true;
			} else {
				return array(
					'error' => __( 'DATE is not valid.', 'wp-security-audit-log' ),
				);
			}
		} elseif ( 'TIME' === $what ) { // TIME.
			$time_array = explode( ':', $input_value );
			if ( count( $time_array ) == 2 ) {
				$p1 = intval( $time_array[0] );
				if ( $p1 < 0 || $p1 > 23 ) {
					return array(
						'error' => __( 'TIME is not valid.', 'wp-security-audit-log' ),
					);
				}
				$p2 = intval( $time_array[1] );
				if ( $p2 < 0 || $p2 > 59 ) {
					return array(
						'error' => __( 'TIME is not valid.', 'wp-security-audit-log' ),
					);
				}
				return true;
			}
			return false;
		} elseif ( 'POST ID' === $what || 'PAGE ID' === $what || 'CUSTOM POST ID' === $what ) { // POST ID, PAGE ID, CUSTOM POST ID.
			$e           = sprintf( __( '%s is not valid', 'wp-security-audit-log' ), $what );
			$input_value = intval( $input_value );
			if ( ! $input_value ) {
				return array(
					'error' => $e,
				);
			}
			global $wpdb;
			$query  = $this->get_post_id_query( $wpdb, $input_value );
			$result = $wpdb->get_var( $query );

			if ( $result >= 1 ) {
				return true;
			} else {
				$e = sprintf( __( '%s was not found', 'wp-security-audit-log' ), $what );
				return array(
					'error' => $e,
				);
			}
		} elseif ( 'SITE DOMAIN' === $what ) { // SITE ID.
			$e = sprintf( __( '%s is not valid', 'wp-security-audit-log' ), $what );
			if ( ! $input_value ) {
				return array(
					'error' => $e,
				);
			}
			if ( $this->wsal->IsMultisite() ) {
				global $wpdb;
				$result = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $wpdb->blogs . ' WHERE blog_id = %s', $input_value ) );
			} else {
				return array(
					'error' => __( 'The enviroment is not multisite.', 'wp-security-audit-log' ),
				);
			}
			if ( ! empty( $result ) && $result >= 1 ) {
				return true;
			} else {
				$e = sprintf( __( '%s was not found', 'wp-security-audit-log' ), $what );
				return array(
					'error' => $e,
				);
			}
		} elseif ( 'POST TYPE' === $what ) { // POST TYPE.
			$e = sprintf( __( '%s is not valid', 'wp-security-audit-log' ), $what );
			if ( '0' !== $input_value && empty( $input_value ) ) {
				return array(
					'error' => $e,
				);
			}

			if ( ! $this->wsal->IsMultisite() && ! isset( $post_types->data[ $post_types->selected ] ) ) {
				return array(
					'error' => __( 'Selected POST TYPE is not valid.', 'wp-security-audit-log' ),
				);
			}
		} elseif ( 'POST STATUS' === $what ) {
			$e = sprintf( __( '%s is not valid', 'wp-security-audit-log' ), $what );
			if ( '0' !== $input_value && empty( $input_value ) ) {
				return array(
					'error' => $e,
				);
			}

			if ( ! isset( $post_status->data[ $post_status->selected ] ) ) {
				return array(
					'error' => __( 'Selected POST STATUS is not valid.', 'wp-security-audit-log' ),
				);
			}
		} elseif ( 'OBJECT' === $what ) {
			/* Translators: Event object */
			$e = sprintf( __( '%s is not valid', 'wp-security-audit-log' ), $what );
			if ( '0' !== $input_value && empty( $input_value ) ) {
				return array( 'error' => $e );
			}

			if ( ! isset( $object_types->data[ $object_types->selected ] ) ) {
				return array( 'error' => __( 'Selected OBJECT is not valid.', 'wp-security-audit-log' ) );
			}
		} elseif ( 'TYPE' === $what ) {
			/* Translators: Event type */
			$e = sprintf( __( '%s is not valid', 'wp-security-audit-log' ), $what );
			if ( '0' !== $input_value && empty( $input_value ) ) {
				return array( 'error' => $e );
			}

			if ( ! isset( $event_types->data[ $event_types->selected ] ) ) {
				return array( 'error' => __( 'Selected TYPE is not valid.', 'wp-security-audit-log' ) );
			}
		}

		return true;
	}

	/**
	 * Retrieve a notification from the database.
	 *
	 * @param int $id
	 * @return mixed
	 */
	public function GetNotification( $id ) {
		return $this->wsal->GetNotification( $id );
	}

	/**
	 * Retrieve all notifications from the database.
	 *
	 * @param string $how
	 * @return mixed
	 */
	public function GetNotifications() {
		return $this->wsal->GetNotificationsSetting( WSAL_OPT_PREFIX );
	}

	/**
	 * Check to see whether or not we can add a new notification.
	 *
	 * @return bool
	 */
	public function CanAddNotification() {
		$num = $this->wsal->CountNotifications( WSAL_OPT_PREFIX );
		return $num < WSAL_MAX_NOTIFICATIONS;
	}

	/**
	 * Get notifications disabled.
	 *
	 * @return stdClass[] notifications
	 */
	public function GetDisabledNotifications() {
		$notifications = $this->GetNotifications();

		// Check notifications.
		if ( ! empty( $notifications ) && is_array( $notifications ) ) {
			foreach ( $notifications as $i => &$entry ) {
				$item = maybe_unserialize( $entry->option_value );
				if ( $item->status == 1 ) {
					unset( $notifications[ $i ] );
				}
			}
		}

		if ( is_array( $notifications ) ) {
			$notifications = array_values( $notifications );
		}
		return $notifications;
	}

	/**
	 * Get notifications Not built-in.
	 *
	 * @return stdClass[] notifications
	 */
	public function GetNotBuiltInNotifications() {
		$notifications = $this->GetNotifications();

		// Check notifications.
		if ( ! empty( $notifications ) && is_array( $notifications ) ) {
			foreach ( $notifications as $i => &$entry ) {
				$item = maybe_unserialize( $entry->option_value );
				if ( isset( $item->built_in ) ) {
					unset( $notifications[ $i ] );
				}
			}
		}

		$notifications = ( $notifications && is_array( $notifications ) ) ? array_values( $notifications ) : null;
		return $notifications;
	}

	/**
	 * Get notifications built-in.
	 *
	 * @return stdClass[] notifications
	 */
	public function GetBuiltIn() {
		$notifications = $this->GetNotifications();
		$built_in      = array();

		// Check notifications.
		if ( ! empty( $notifications ) && is_array( $notifications ) ) {
			foreach ( $notifications as $i => &$entry ) {
				$item = maybe_unserialize( $entry->option_value );
				if ( isset( $item->built_in ) ) {
					$built_in[] = $notifications[ $i ];
				}
			}
		}
		return $built_in;
	}

	/**
	 * Check built-in by name.
	 *
	 * @param string $name
	 * @return array|null
	 */
	public function CheckBuiltInByName( $name ) {
		$name      = 'wsal-notification-built-in-' . $name;
		$aBuilt_in = $this->GetBuiltIn();
		if ( ! empty( $aBuilt_in ) ) {
			foreach ( $aBuilt_in as $element ) {
				if ( $element->option_name == $name ) {
					$item    = maybe_unserialize( $element->option_value );
					$checked = array();
					foreach ( $item->triggers as $value ) {
						array_push( $checked, $value['input1'] );
					}
					return array(
						'title'   => $item->title,
						'email'   => $item->email,
						'checked' => $checked,
					);
				}
			}
		}
		return null;
	}

	/**
	 * Check built-in by type.
	 *
	 * @param string $type
	 * @return boolean
	 */
	public function CheckBuiltInByType( $type ) {
		$type      = 'wsal-notification-built-in-' . $type;
		$aBuilt_in = $this->GetBuiltIn();
		if ( ! empty( $aBuilt_in ) ) {
			foreach ( $aBuilt_in as $element ) {
				if ( $element->option_name == $type ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Retrieve all notifications to display in the search view.
	 *
	 * @param wpdb   $wpdb
	 * @param $search
	 * @return array
	 */
	public function GetSearchResults( $search ) {
		if ( empty( $search ) ) {
			return array();
		}
		$notifications = $this->GetNotifications();
		$tmp           = array();
		foreach ( $notifications as $entry ) {
			$item = maybe_unserialize( $entry->option_value );
			if ( false !== ( $r = stristr( $item->title, $search ) ) ) {
				array_push( $tmp, $entry );
				continue;
			}
		}
		return $tmp;
	}

	/**
	 * JSON encode and display the Notification object in the Edit Notification view.
	 *
	 * @param WSAL_NP_NotificationBuilder $notif_builder
	 */
	public function CreateJsOutputEdit( WSAL_NP_NotificationBuilder $notif_builder ) {
		echo '<script type="text/javascript" id="wsalModelWp">';
		echo "var wsalModelWp = '" . json_encode( $notif_builder->get() ) . "';";
		echo '</script>';
	}

	/**
	 * Build the js script the view will use to rebuild the form in case of an error.
	 *
	 * @param WSAL_NP_NotificationBuilder $notif_builder - Notification builder object.
	 */
	public function CreateJsObjOutput( WSAL_NP_NotificationBuilder $notif_builder ) {
		echo '<script type="text/javascript" id="wsalModelWp">';
		echo "var wsalModelWp = '" . wp_json_encode( $notif_builder->get() ) . "';";
		echo '</script>';
	}

	/**
	 * Get notifications page URL.
	 *
	 * @return string URL
	 */
	public function GetNotificationsPageUrl() {
		$class = $this->wsal->views->FindByClassName( 'WSAL_NP_Notifications' );
		if ( false === $class ) {
			$class = new WSAL_NP_Notifications( $this->wsal );
		}
		return add_query_arg( 'tab', 'custom', $class->GetUrl() );
	}

	/**
	 * Save or update a notification into the database. This method will also validate the notification.
	 *
	 * @param WSAL_NP_NotificationBuilder $notif_builder - Instance of WSAL_NP_NotificationBuilder.
	 * @param object                      $notification - Instance of stdClass.
	 * @param bool                        $update - True for update | False for add operation.
	 * @return null|void
	 */
	public function SaveNotification( WSAL_NP_NotificationBuilder $notif_builder, $notification, $update = false ) {
		if ( ! $update && ! $this->CanAddNotification() ) :
			?>
			<div class="error">
				<p><?php esc_html_e( 'Title is required.', 'wp-security-audit-log' ); ?></p>
			</div>
			<?php
			$this->CreateJsObjOutput( $notif_builder );
			return;
		endif;

		// Sanitize Title & Email.
		$title = trim( $notification->info->title );
		$title = str_replace( array( '\\', '/' ), '', $title );
		$title = sanitize_text_field( $title );
		$email = trim( $notification->info->email );
		$phone = trim( $notification->info->phone );

		// If there is Email Template.
		if ( ! empty( $notification->info->subject ) && ! empty( $notification->info->body ) ) {
			// Sanitize subject and body.
			$subject = trim( $notification->info->subject );
			$subject = str_replace( array( '\\', '/' ), '', $subject );
			$subject = sanitize_text_field( $subject );
			$body    = $notification->info->body;
		}

		$notif_builder->clearTriggersErrors();

		// Validate title.
		if ( empty( $title ) ) :
			?>
			<div class="error"><p><?php esc_html_e( 'Title is required.', 'wp-security-audit-log' ); ?></p></div>
			<?php
			$notif_builder->update( 'errors', 'titleMissing', __( 'Title is required.', 'wp-security-audit-log' ) );
			$this->CreateJsObjOutput( $notif_builder );
			return;
		endif;

		$regex_title = '/[A-Z0-9\,\.\+\-\_\?\!\@\#\$\%\^\&\*\=]/si';
		if ( ! preg_match( $regex_title, $title ) ) {
			$notif_builder->update( 'errors', 'titleMissing', __( 'Title is not valid.', 'wp-security-audit-log' ) );
			$this->CreateJsObjOutput( $notif_builder );
			return;
		}

		// Set triggers.
		$triggers = $notification->triggers;

		// Validate triggers.
		if ( empty( $triggers ) ) {
			$notif_builder->update( 'errors', 'triggersMissing', __( 'Please add at least one condition.', 'wp-security-audit-log' ) );
			$this->CreateJsObjOutput( $notif_builder );
			return;
		}

		// ---------------------------------------------
		// Validate conditions
		// ---------------------------------------------
		$has_errors = false; // Just a flag so we won't have to count notifObj->errors->triggers.
		$conditions = array(); // Will hold the trigger entries that will be saved into DB, so we won't have to parse the obj again.
		foreach ( $triggers as $i => $entry ) {
			// Flag.
			$j = $i + 1; // To help us identify the right trigger in the DOM.

			// Simple obj mapping.
			$select1 = $entry->select1;
			$select2 = $entry->select2;
			$select3 = $entry->select3;
			$select4 = $entry->select4;
			$select5 = $entry->select5;
			$select6 = $entry->select6;
			$select7 = $entry->select7;
			$select8 = $entry->select8;
			$input1  = $entry->input1;

			/**
			 * PAGE ID and CUSTOM POST ID is deprecated
			 * since version 3.1.
			 *
			 * @deprecated PAGE ID, CUSTOM POST ID in select2.
			 * @since 3.1
			 */
			if ( 7 === $select2->selected || 8 === $select2->selected ) {
				$select2->selected = 6; // Assigning the value to POST ID.
			}

			// Checking if selected SITE DOMAIN(9).
			if ( 9 == $select2->selected ) {
				global $wpdb;
				$input1 = $wpdb->get_var( $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs WHERE domain = %s", $input1 ) );
			}
			// Validate each trigger/condition.
			if ( $i ) {
				// Ignore the first trigger's select1 - because it's not used
				// so we start with the second one
				// make sure the provided selected index exists in the correspondent data array.
				if ( ! isset( $select1->data[ $select1->selected ] ) ) {
					$has_errors = true;
					$notif_builder->updateTriggerError( $j, __( 'The form is not valid. Please refresh the page and try again.', 'wp-security-audit-log' ) );
					continue;
				}
			}
			if ( ! isset( $select2->data[ $select2->selected ] ) ) {
				$has_errors = true;
				$notif_builder->updateTriggerError( $j, __( 'The form is not valid. Please refresh the page and try again.', 'wp-security-audit-log' ) );
				continue;
			}
			if ( ! isset( $select3->data[ $select3->selected ] ) ) {
				$has_errors = true;
				$notif_builder->updateTriggerError( $j, __( 'The form is not valid. Please refresh the page and try again.', 'wp-security-audit-log' ) );
				continue;
			}

			// Sanitize and validate input.
			$input1 = $this->ValidateInput( $input1 );
			$size   = strlen( $input1 );
			if ( $size > 50 ) {
				$has_errors = true;
				$notif_builder->updateTriggerError( $j, __( "A trigger's condition must not be longer than 50 characters.", 'wp-security-audit-log' ) );
				continue;
			}

			$vm = $this->ValidateCondition( $input1, $select2, $select3, $select4, $select5, $select6, $select7, $select8 );
			if ( is_array( $vm ) ) {
				$has_errors = true;
				$notif_builder->updateTriggerError( $j, $vm['error'] );
				continue;
			}

			// Add condition.
			array_push(
				$conditions,
				array(
					'select1' => intval( $select1->selected ),
					'select2' => intval( $select2->selected ),
					'select3' => intval( $select3->selected ),
					'select4' => intval( $select4->selected ),
					'select5' => intval( $select5->selected ),
					'select6' => intval( $select6->selected ),
					'select7' => intval( $select7->selected ),
					'select8' => intval( $select8->selected ),
					'input1'  => strtolower( $input1 ),
				)
			);

			//  the custom user field name won't be turned to lowercase
			if ( $conditions[ $i ]['select2'] == 14 ) {
				$conditions[ $i ]['input1'] = $input1;
			}
		}

		// Validate email.
		if ( ! $email && ! $phone ) {
			$notif_builder->update( 'errors', 'emailMissing', __( 'Email or Username is required.', 'wp-security-audit-log' ) );
			$notif_builder->update( 'errors', 'phoneMissing', __( 'Mobile number is required.', 'wp-security-audit-log' ) );
			$this->CreateJsObjOutput( $notif_builder );
			return;
		} else {
			if ( $email && ! $this->CheckEmailOrUsername( $email ) ) {
				$notif_builder->update( 'errors', 'emailInvalid', __( 'Email or Username is not valid.', 'wp-security-audit-log' ) );
				$this->CreateJsObjOutput( $notif_builder );
				return;
			}

			// Validate phone.
			if ( $phone && ! $this->check_phone_number( $phone ) ) {
				$notif_builder->update( 'errors', 'phoneInvalid', __( 'Mobile number is not valid.', 'wp-security-audit-log' ) );
				$this->CreateJsObjOutput( $notif_builder );
				return;
			}
		}

		if ( $has_errors ) {
			$this->CreateJsObjOutput( $notif_builder );
			return;
		} else {
			// save notification
			// Build the object that will be saved into DB.
			if ( $update ) {
				$opt_name = $notification->special->optName;

				// Holds the notification data that will be saved into the db.
				$data            = new stdClass();
				$data->title     = $notification->info->title;
				$data->email     = $notification->info->email;
				$data->phone     = $notification->info->phone;
				$data->owner     = $notification->special->owner;
				$data->dateAdded = $notification->special->dateAdded;
				$data->status    = $notification->special->status;
				$data->viewState = $notification->viewState;
			} else {
				$opt_name = WSAL_OPT_PREFIX . $this->UniqueNumber();

				// Holds the notification data that will be saved into the db.
				$data            = new stdClass();
				$data->title     = $title;
				$data->email     = $email;
				$data->phone     = $phone;
				$data->owner     = get_current_user_id();
				$data->dateAdded = time();
				$data->status    = 1;
				$data->viewState = $notification->viewState;
			}

			// If there is Email Template.
			if ( ! empty( $subject ) && ! empty( $body ) ) {
				$data->subject = $subject;
				$data->body    = $body;
			}

			$data->triggers = $conditions; // This will be serialized by WP.

			$old_value = $this->wsal->GetGlobalSetting( $opt_name );

			$result = $update ? $this->UpdateGlobalSetting( $opt_name, $data ) : $this->AddGlobalSetting( $opt_name, $data );

			if ( false === $result ) {
				// catchy... update_option && update_site_option will both return false if one will use them to update an option
				// with the same value(s)
				// so we need to check the last error.
				?>
				<div class="error"><p><?php esc_html_e( 'Notification could not be saved.', 'wp-security-audit-log' ); ?></p></div>
				<?php
				$this->CreateJsObjOutput( $notif_builder );
				return;
			}

			if ( $update ) {
				$this->wsal->alerts->Trigger( 6315, [
					'EventType' => 'modified',
					'recipient' => \WSAL\Helpers\Options::create_recipient_string( $data->email, $data->phone ),
					'notification_name' => $data->title,
					'previous_recipient' => \WSAL\Helpers\Options::create_recipient_string( $old_value->email, $old_value->phone ),
				]);
			} else {
				$this->wsal->alerts->Trigger( 6314, [
					'EventType' => 'added',
					'recipient' => \WSAL\Helpers\Options::create_recipient_string( $data->email, $data->phone ),
					'notification_name' => $data->title,
				]);
			}
			
			// ALL GOOD.
			?>
			<div class="updated"><p><?php esc_html_e( 'Notification successfully saved.', 'wp-security-audit-log' ); ?></p></div>
			<?php
			// Send to Notifications page.
			echo '<script type="text/javascript" id="wsalModelReset">';
			echo 'window.setTimeout(function(){location.href="' . esc_url_raw( $this->GetNotificationsPageUrl() ) . '";}, 700);';
			echo '</script>';
		}
	}

	/**
	 * Get Email Template Tags.
	 *
	 * Returns the tags supported by WSAL email notification.
	 *
	 * @since 3.4
	 *
	 * @return array
	 */
	public function get_email_template_tags() {
		return apply_filters(
			'wsal_notification_email_template_tags',
			array(
				'{title}'          => esc_html__( 'Notification Title', 'wp-security-audit-log' ),
				'{site}'           => esc_html__( 'Website Name', 'wp-security-audit-log' ),
				'{username}'       => esc_html__( 'User Login Name', 'wp-security-audit-log' ),
				'{user_firstname}' => esc_html__( 'User First Name', 'wp-security-audit-log' ),
				'{user_lastname}'  => esc_html__( 'User Last Name', 'wp-security-audit-log' ),
				'{user_role}'      => esc_html__( 'Role(s) of the User', 'wp-security-audit-log' ),
				'{date_time}'      => esc_html__( 'Event generated on Date and Time', 'wp-security-audit-log' ),
				'{alert_id}'       => esc_html__( 'Event Code', 'wp-security-audit-log' ),
				'{severity}'       => esc_html__( 'Event Severity', 'wp-security-audit-log' ),
				'{message}'        => esc_html__( 'Event Message', 'wp-security-audit-log' ),
				'{meta}'           => esc_html__( 'Event Metadata', 'wp-security-audit-log' ),
				'{links}'          => esc_html__( 'Event Links', 'wp-security-audit-log' ),
				'{source_ip}'      => esc_html__( 'Client IP Address', 'wp-security-audit-log' ),
				'{object}'         => esc_html__( 'Event Object', 'wp-security-audit-log' ),
				'{event_type}'     => esc_html__( 'Event Type', 'wp-security-audit-log' ),
			)
		);
	}

	/**
	 * Get SMS Template Tags.
	 *
	 * Returns the tags supported by WSAL sms notification.
	 *
	 * @since 3.4
	 *
	 * @return array
	 */
	public function get_sms_template_tags() {
		return apply_filters(
			'wsal_notification_sms_template_tags',
			array(
				'{site}'       => 'Website Name',
				'{username}'   => 'User Login Name',
				'{user_role}'  => 'Role(s) of the User',
				'{date_time}'  => 'Event generated on Date and Time',
				'{alert_id}'   => 'Event Code',
				'{severity}'   => 'Event Severity',
				'{message}'    => 'Event Message',
				'{source_ip}'  => 'Client IP Address',
				'{object}'     => 'Event Object',
				'{event_type}' => 'Event Type',
			)
		);
	}

	/**
	 * Email Template by name.
	 *
	 * @param string  $name          - Email option name.
	 * @param boolean $force_default - Force default email template.
	 * @return array $email_body Body of the email
	 */
	public function GetEmailTemplate( $name, $force_default = false ) {
		$template     = array();
		$opt_name     = 'email-template-' . $name;
		$opt_template = $this->GetSettingByName( $opt_name );
		if ( ! empty( $opt_template ) && ! $force_default ) {
			$template = json_decode( wp_json_encode( $opt_template ), true );
		} else {
			$template['subject'] = __( 'Notification {title} on website {site} triggered', 'wp-security-audit-log' );
			$default_email_body  = '<p>' . __( 'Notification <strong>{title}</strong> was triggered. Below are the notification details:', 'wp-security-audit-log' ) . '</p>';
			$default_email_body .= '<ul>';
			$default_email_body .= '<li>' . __( 'Website', 'wp-security-audit-log' ) . ': {site}</li>';
			$default_email_body .= '<li>' . __( 'Event ID', 'wp-security-audit-log' ) . ': {alert_id}</li>';
			$default_email_body .= '<li>' . __( 'Username', 'wp-security-audit-log' ) . ': {username}</li>';
			$default_email_body .= '<li>' . __( 'User first name', 'wp-security-audit-log' ) . ': {user_firstname}</li>';
			$default_email_body .= '<li>' . __( 'User last name', 'wp-security-audit-log' ) . ': {user_lastname}</li>';
			$default_email_body .= '<li>' . __( 'User role', 'wp-security-audit-log' ) . ': {user_role}</li>';
			$default_email_body .= '<li>' . __( 'IP address', 'wp-security-audit-log' ) . ': {source_ip}</li>';
			$default_email_body .= '<li>' . __( 'Object', 'wp-security-audit-log' ) . ': {object}</li>';
			$default_email_body .= '<li>' . __( 'Event Type', 'wp-security-audit-log' ) . ': {event_type}</li>';
			$default_email_body .= '<li>' . __( 'Event Message', 'wp-security-audit-log' ) . ': {message}</li>';
			$default_email_body .= '<li>' . __( 'Event generated on', 'wp-security-audit-log' ) . ': {date_time}</li>';
			$default_email_body .= '</ul>';
			$default_email_body .= '<p>' . __( 'These email notifications are sent with <a href="http://wpactivitylog.com">WP Activity Log</a>, the most comprehensive WordPress activity log plugin solution.', 'wp-security-audit-log' ) . '</p>';
			$template['body']    = $default_email_body;
		}
		return $template;
	}

	/**
	 * SMS Template by name.
	 *
	 * @since 3.4
	 *
	 * @param string  $name          - SMS option name.
	 * @param boolean $force_default - Force default sms template.
	 * @return string
	 */
	public function get_sms_template( $name, $force_default = false ) {
		$template     = array();
		$opt_name     = 'sms-template-' . $name;
		$opt_template = $this->GetSettingByName( $opt_name );
		if ( ! empty( $opt_template ) && ! $force_default ) {
			$template = json_decode( wp_json_encode( $opt_template ), true );
		} else {
			$default_sms_body  = __( 'Site', 'wp-security-audit-log' ) . ': {site}' . "\r\n";
			$default_sms_body .= __( 'User/Role', 'wp-security-audit-log' ) . ': {username} / {user_role}' . "\r\n";
			$default_sms_body .= __( 'IP Address', 'wp-security-audit-log' ) . ': {source_ip}' . "\r\n";
			$default_sms_body .= __( 'Event ID', 'wp-security-audit-log' ) . ': {alert_id}' . "\r\n";
			$default_sms_body .= __( 'Event type', 'wp-security-audit-log' ) . ': {event_type}' . "\r\n";
			$default_sms_body .= __( 'Message', 'wp-security-audit-log' ) . ': {message}';
			$template['body']  = $default_sms_body;
		}
		return $template;
	}

	/**
	 * Get Test Email Notification Template.
	 *
	 * @since 3.4
	 *
	 * @param stdClass $notification - Notification object.
	 * @return array
	 */
	public function get_test_email_template( $notification ) {
		if ( ! $notification ) {
			return;
		}

		$template = array();
		if ( ! empty( $notification->subject ) && ! empty( $notification->body ) ) {
			$template['subject'] = $notification->subject;
			$template['body']    = $notification->body;
		} else {
			$template['subject'] = __( 'Notification {title} on website {site} triggered', 'wp-security-audit-log' );
			$default_email_body  = '<p>' . __( 'Notification <strong>{title}</strong> was triggered. Below are the notification details:', 'wp-security-audit-log' ) . '</p>';
			$default_email_body .= '<ul>';
			$default_email_body .= '<li>' . __( 'Event ID', 'wp-security-audit-log' ) . ': {alert_id}</li>';
			$default_email_body .= '<li>' . __( 'Username', 'wp-security-audit-log' ) . ': {username}</li>';
			$default_email_body .= '<li>' . __( 'User Role', 'wp-security-audit-log' ) . ': {user_role}</li>';
			$default_email_body .= '<li>' . __( 'Message', 'wp-security-audit-log' ) . ': {message}</li>';
			$default_email_body .= '<li>' . __( 'Generated On', 'wp-security-audit-log' ) . ': {date_time}</li>';
			$default_email_body .= '</ul>';
			$default_email_body .= '<p>' . __( 'Monitoring of WordPress and Email Notifications provided by <a href="http://wpactivitylog.com">WP Activity Log, WordPress most comprehensive audit trail plugin</a>.', 'wp-security-audit-log' ) . '</p>';
			$template['body']    = $default_email_body;
		}
		return $template;
	}

	/**
	 * Get Test SMS Notification Template.
	 *
	 * @since 3.4
	 *
	 * @param stdClass $notification - Notification object.
	 * @return string
	 */
	public function get_test_sms_template( $notification ) {
		if ( ! $notification ) {
			return;
		}

		$template = '';
		if ( ! empty( $notification->body ) ) {
			$template = $notification->body;
		} else {
			$template  = __( 'Site', 'wp-security-audit-log' ) . ': {site}' . "\r\n";
			$template .= __( 'User/Role', 'wp-security-audit-log' ) . ': {username} / {user_role}' . "\r\n";
			$template .= __( 'Event', 'wp-security-audit-log' ) . ': {alert_id}';
		}
		return $template;
	}

	/**
	 * Form fields for the email template.
	 *
	 * @param array $data - Subject and body.
	 */
	public function SpecificTemplate( $data = null ) {
		?>
		<table id="specific-template" class="form-table">
			<tbody class="widefat" id="email-template">
				<tr>
					<th class="left-column"><label for="columns"><?php esc_html_e( 'Subject ', 'wp-security-audit-log' ); ?></label></th>
					<td>
						<fieldset>
							<input class="field" type="text" name="subject" placeholder="Subject *" value="<?php echo ! empty( $data['subject'] ) ? $data['subject'] : null; ?>">
						</fieldset>
					</td>
				</tr>
				<tr>
					<th class="left-column">
						<label for="columns"><?php esc_html_e( 'Body ', 'wp-security-audit-log' ); ?></label>
						<br>
						<div class="tags">
							<span><?php esc_html_e( 'HTML is accepted. Available template tags:', 'wp-security-audit-log' ); ?></span>
							<ul>
								<?php
								foreach ( $this->get_email_template_tags() as $tag => $desc ) :
									echo '<li>' . esc_html( $tag ) . ' — ' . esc_html( $desc ) . '</li>';
								endforeach;
								?>
							</ul>
						</div>
					</th>
					<td>
						<fieldset>
							<?php
							$content   = ! empty( $data['body'] ) ? stripslashes( $data['body'] ) : '';
							$editor_id = 'body';
							$settings  = array(
								'media_buttons' => false,
								'editor_height' => 300,
							);

							// Template editor.
							wp_editor( $content, $editor_id, $settings );
							?>
						</fieldset>
						<br>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Count login failure and update the transient.
	 *
	 * @param string $ip - IP address.
	 * @param int    $site_id - Site ID.
	 * @param string $user - WPUser object.
	 */
	public function CounterLoginFailure( $ip, $site_id, $user ) {
		// Valid 12 hours.
		$expiration = 12 * 60 * 60;

		$get_fn = $this->wsal->IsMultisite() ? 'get_site_transient' : 'get_transient';
		$set_fn = $this->wsal->IsMultisite() ? 'set_site_transient' : 'set_transient';
		if ( $user ) {
			$data_known = $get_fn( self::TRANSIENT_FAILED_COUNT );
			if ( ! $data_known ) {
				$data_known = array();
			}
			if ( ! isset( $data_known[ $site_id . ':' . $user->ID . ':' . $ip ] ) ) {
				$data_known[ $site_id . ':' . $user->ID . ':' . $ip ] = 1;
			}
			$data_known[ $site_id . ':' . $user->ID . ':' . $ip ]++;
			$set_fn( self::TRANSIENT_FAILED_COUNT, $data_known, $expiration );
		} else {
			$data_unknown = $get_fn( self::TRANSIENT_FAILED_UNKNOWN_COUNT );
			if ( ! $data_unknown ) {
				$data_unknown = array();
			}
			if ( ! isset( $data_unknown[ $site_id . ':' . $ip ] ) ) {
				$data_unknown[ $site_id . ':' . $ip ] = 1;
			}
			$data_unknown[ $site_id . ':' . $ip ]++;
			$set_fn( self::TRANSIENT_FAILED_UNKNOWN_COUNT, $data_unknown, $expiration );
		}
	}

	/**
	 * Check login failure limit.
	 *
	 * @param int    $limit - Limit for the alert.
	 * @param string $ip - IP address.
	 * @param int    $site_id - Site ID.
	 * @param string $user - WPUser object.
	 * @param bool   $exceed - True if exceeded, otherwise false.
	 * @return boolean passed limit true|false
	 */
	public function IsLoginFailureLimit( $limit, $ip, $site_id, $user, $exceed = false ) {
		$get_fn = $this->wsal->IsMultisite() ? 'get_site_transient' : 'get_transient';
		$limit  = ( $limit + 1 );
		if ( $user ) {
			$data_known = $get_fn( self::TRANSIENT_FAILED_COUNT );
			if ( $exceed ) {
				return ( false !== $data_known ) && isset( $data_known[ $site_id . ':' . $user->ID . ':' . $ip ] ) && ( $data_known[ $site_id . ':' . $user->ID . ':' . $ip ] > $limit );
			}
			return ( false !== $data_known ) && isset( $data_known[ $site_id . ':' . $user->ID . ':' . $ip ] ) && ( $data_known[ $site_id . ':' . $user->ID . ':' . $ip ] == $limit );
		} else {
			$data_unknown = $get_fn( self::TRANSIENT_FAILED_UNKNOWN_COUNT );
			if ( $exceed ) {
				return ( false !== $data_unknown ) && isset( $data_unknown[ $site_id . ':' . $ip ] ) && ( $data_unknown[ $site_id . ':' . $ip ] > $limit );
			}
			return ( false !== $data_unknown ) && isset( $data_unknown[ $site_id . ':' . $ip ] ) && ( $data_unknown[ $site_id . ':' . $ip ] == $limit );
		}
	}

	/**
	 * Send notifications sms.
	 *
	 * @since 3.4
	 *
	 * @param string $phone_numbers - Phone number.
	 * @param string $content       - SMS content.
	 * @return bool
	 */
	public function send_notification_sms( $phone_numbers, $content ) {
		$phone_numbers = explode( ',', $phone_numbers );
		$sms_provider  = new WSAL_NP_SMSProviderSettings();

		foreach ( $phone_numbers as $number ) {
			try {
				$result = $sms_provider->send_sms( $number, $content );
			} catch ( Exception $ex ) {
				$result = $ex->getMessage();
			}
		}

		return $result;
	}

	/**
	 * Send notifications email.
	 *
	 * @param string  $email_address - Email Address.
	 * @param string  $subject       - Email subject.
	 * @param string  $content       - Email content.
	 * @param integer $alert_id      - (Optional) Alert ID.
	 * @return bool
	 */
	public function SendNotificationEmail( $email_address, $subject, $content, $alert_id = 0 ) {

		// Get email addresses even when there is the Username.
		$email_address = $this->GetEmails( $email_address );
		if ( WSAL_DEBUG_NOTIFICATIONS ) {
			error_log( 'WP Activity Log Notification' );
			error_log( 'Email address: ' . $email_address );
			error_log( 'Alert ID: ' . $alert_id );
		}

		// Give variable a value.
		$result = false;

		if ( class_exists( 'WSAL_Utilities_Emailer' ) ) {
			// Get email template.
			$result = WSAL_Utilities_Emailer::send_email( $email_address, $subject, $content );
		}

		if ( WSAL_DEBUG_NOTIFICATIONS ) {
			error_log( 'Email success: ' . print_r( $result, true ) );
		}
		return $result;
	}

	/**
	 * Get timezone from the settings.
	 *
	 * @return int $gmt_offset_sec
	 */
	public function GetTimezone() {
		$gmt_offset_sec = 0;
		$timezone       = $this->wsal->settings()->GetTimezone();

		/**
		 * Transform timezone values.
		 *
		 * @since 3.2.3
		 */
		if ( '0' === $timezone ) {
			$timezone = 'utc';
		} elseif ( '1' === $timezone ) {
			$timezone = 'wp';
		}

		if ( 'utc' === $timezone ) {
			$gmt_offset_sec = date( 'Z' );
		} else {
			$gmt_offset_sec = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
		}
		return $gmt_offset_sec;
	}

	/**
	 * Get datetime formatted for email or SMS notification.
	 *
	 * @return string $date
	 */
	public function GetFormattedDatetime() {
		return WSAL_Utilities_DateTimeFormatter::instance()->getFormattedDateTime(
			current_time( 'timestamp', true ),
			'datetime',
			true,
			false,
			false
		);
	}

	/**
	 * Get the blog name.
	 *
	 * @return string $blogname
	 */
	public function GetBlogname() {
		if ( is_multisite() ) {
			$blog_id  = function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 0;
			$blogname = get_blog_option( $blog_id, 'blogname' );
		} else {
			$blogname = get_option( 'blogname' );
		}
		return $blogname;
	}

	/**
	 * Get the blog URL.
	 *
	 * @since 3.4
	 *
	 * @return string
	 */
	public function get_blog_domain() {
		if ( is_multisite() ) {
			$blog_id     = function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 0;
			$blog_domain = get_blog_option( $blog_id, 'home' );
		} else {
			$blog_domain = get_option( 'home' );
		}

		// Replace protocols.
		$blog_domain = str_replace( array( 'http://', 'https://' ), '', $blog_domain );

		return $blog_domain;
	}

	/**
	 * Validation email or username field.
	 *
	 * @param string $input_string - Input string.
	 * @return boolean
	 */
	public function CheckEmailOrUsername( $input_string ) {
		$input_string      = trim( $input_string );
		$email_or_username = explode( ',', $input_string );
		foreach ( $email_or_username as $value ) {
			$value = htmlspecialchars( stripslashes( trim( $value ) ) );
			// Check if e-mail address is well-formed.
			if ( ! filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
				$user = get_user_by( 'login', $value );
				if ( empty( $user ) ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Validate Phone Number(s).
	 *
	 * @since 3.4
	 *
	 * @param string $phone - Phone number.
	 * @return mixed
	 */
	public function check_phone_number( $phone ) {
		// Convert phone numbers to array.
		$phone_numbers = explode( ',', $phone );

		foreach ( $phone_numbers as $number ) {
			/**
			 * Pattern only allows phone number with
			 * 10 to 14 characters and with or without
			 * plus (+) sign at the start.
			 */
			if ( ! preg_match( '/^[+]?[0-9]{10,14}$/', $number ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Get email addresses by usernames.
	 *
	 * @param string $input_string - String of emails.
	 * @return string $emails - Comma separated email.
	 */
	public function GetEmails( $input_string ) {
		$arr_emails        = array();
		$input_string      = trim( $input_string );
		$email_or_username = explode( ',', $input_string );
		foreach ( $email_or_username as $value ) {
			$value = htmlspecialchars( stripslashes( trim( $value ) ) );
			// Check if e-mail address is well-formed.
			if ( ! filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
				$user = get_user_by( 'login', $value );
				if ( $user ) {
					array_push( $arr_emails, $user->user_email );
				}
			} else {
				array_push( $arr_emails, $value );
			}
		}
		return implode( ',', $arr_emails );
	}

	/**
	 * Method: Get WP User roles.
	 *
	 * @return array
	 */
	public function get_wp_user_roles() {
		$wp_user_roles = '';
		// Check if function `wp_roles` exists.
		if ( function_exists( 'wp_roles' ) ) {
			// Get WP user roles.
			$wp_user_roles = wp_roles()->roles;
		} else { // WP Version is below 4.3.0
			// Get global wp roles variable.
			global $wp_roles;

			// If it is not set then initiate WP_Roles class object.
			if ( ! isset( $wp_roles ) ) {
				$new_wp_roles = new WP_Roles(); // Don't override the original global variable.
			}

			// Get WP user roles.
			$wp_user_roles = $new_wp_roles->roles;
		}
		return $wp_user_roles;
	}

	/**
	 * Method: Calculate and return hour of the day
	 * based on WordPress timezone.
	 *
	 * @since 3.2.4
	 *
	 * @return string - Hour of the day.
	 */
	private function calculate_daily_hour() {
		return date( 'H', time() + ( get_option( 'gmt_offset' ) * ( 60 * 60 ) ) );
	}

	/**
	 * Shorten URL.
	 *
	 * Process URL via Bit.ly URL Shortener API.
	 *
	 * @since 3.4
	 *
	 * @param  string $url - URL to be shortened.
	 * @return string Shortened URL in http://bit.ly/xxx format.
	 */
	public function shorten_url_bitly( $url ) {
		$bitly_token = $this->wsal->GetGlobalSetting( 'url-shortner-access-token' ); // Get Bit.ly access token.
		$group_guid  = $this->get_bitly_group_guid( $bitly_token ); // Get group id from Bit.ly API.

		$result = wp_remote_post(
			'https://api-ssl.bitly.com/v4/shorten',
			array(
				'body'    => wp_json_encode(
					array(
						'group_guid' => $group_guid,
						'long_url'   => esc_url_raw( $url ),
					)
				),
				'headers' => array(
					'Authorization' => "Bearer $bitly_token",
					'Content-Type'  => 'application/json',
				),
			)
		);

		// Return the URL if the request got an error.
		if ( is_wp_error( $result ) ) {
			return $url;
		}

		$result = json_decode( $result['body'] );
		if ( isset( $result->link ) ) {
			return $result->link;
		}
		return $url;
	}

	/**
	 * Bit.ly Group ID.
	 *
	 * Returns group guid via Bit.ly URL Shortener API.
	 *
	 * @since 3.4
	 *
	 * @param  string $bitly_token - Bit.ly access token.
	 * @return string
	 */
	public function get_bitly_group_guid( $bitly_token ) {
		$group_guid_trans = WpSecurityAuditLog::OPTIONS_PREFIX . 'bitly_group_guid';
		$group_guid       = $this->wsal->IsMultisite() ? get_site_transient( $group_guid_trans ) : get_transient( $group_guid_trans );

		if ( ! $group_guid ) {
			$result = wp_remote_get(
				'https://api-ssl.bitly.com/v4/groups',
				array(
					'headers' => array(
						'Authorization' => "Bearer $bitly_token",
						'Content-Type'  => 'application/json',
					),
				)
			);

			if ( ! is_wp_error( $result ) ) {
				$result = json_decode( $result['body'] );
				if ( isset( $result->groups ) && is_array( $result->groups ) ) {
					$group = reset( $result->groups );
					if ( isset( $group->guid ) ) {
						$group_guid = $group->guid;
						$fn         = $this->wsal->IsMultisite() ? 'set_site_transient' : 'set_transient';
						$fn( $group_guid_trans, $group_guid );
					}
				}
			}
		}
		return $group_guid;
	}

	/**
	 * Returns true if Twilio is configured, otherwise false.
	 *
	 * @since 3.4
	 *
	 * @return boolean
	 */
	public function is_twilio_configured() {
		return wsal_freemius()->is_plan_or_trial__premium_only( 'professional' ) && $this->wsal->GetGlobalSetting( 'twilio-account-sid', false ) && $this->wsal->GetGlobalSetting( 'twilio-auth-token', false );
	}

	/**
	 * Get alert severity label.
	 *
	 * @param int $alert_id Alert ID.
	 *
	 * @return string Translated severity label.
	 */
	public function get_alert_severity( $alert_id ) {
		if ( ! $alert_id ) {
			return;
		}

		// Get alert object.
		$alert = $this->wsal->alerts->GetAlert( $alert_id );

		if ( $alert instanceof WSAL_Alert ) {
			$severity_obj = $this->wsal->constants->get_constant_to_display( $alert->severity );

			return $severity_obj->name;
		}
	}
}
