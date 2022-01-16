<?php
/**
 * Class: Utility Class
 *
 * Check for current generated alert.
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
 * Class WSAL_Notifier
 *
 * Loop through notifications and check if any matches the current generated alert.
 *
 * @author wp.kytten
 * @package wp-security-audit-log
 */
class WSAL_NP_Notifier extends WSAL_AbstractLogger {

	/**
	 * Alert date.
	 *
	 * @var string
	 */
	private $_alert_date = null;

	/**
	 * Email.
	 *
	 * @var string
	 */
	private $_email_address = '';

	/**
	 * Phone Number.
	 *
	 * @var string
	 */
	private $phone_number = '';

	/**
	 * Alert ID.
	 *
	 * @var string
	 */
	private $_alert_id = null;

	/**
	 * Alert data.
	 *
	 * @var string
	 */
	private $_alert_data = null;

	/**
	 * Notification Select 1 data.
	 *
	 * @var string
	 */
	private $_s1_data = null;

	/**
	 * Notification Select 2 data.
	 *
	 * @var string
	 */
	private $_s2_data = null;

	/**
	 * Notification Select 3 data.
	 *
	 * @var string
	 */
	private $_s3_data = null;

	/**
	 * Notification Select 4 data.
	 *
	 * Post Status Select box.
	 *
	 * @var string
	 */
	private $_s4_data = null;

	/**
	 * Notification Select 5 data.
	 *
	 * Post Type Select box.
	 *
	 * @var string
	 */
	private $_s5_data = null;

	/**
	 * Notification Select 6 data.
	 *
	 * User Roles Select box.
	 *
	 * @var string
	 */
	private $_s6_data = null;

	/**
	 * Notification Select 7 data.
	 *
	 * Object Select box.
	 *
	 * @var string
	 */
	private $_s7_data = null;

	/**
	 * Notification Select 8 data.
	 *
	 * Event Type Select box.
	 *
	 * @var string
	 */
	private $_s8_data = null;

	/**
	 * Is built in?
	 *
	 * @var bool
	 */
	private $_is_built_in = false;

	/**
	 * Has template?
	 *
	 * @var bool
	 */
	protected $_has_template = false;

	/**
	 * Notifications.
	 *
	 * @var object
	 */
	private $_notifications = null;

	/**
	 * Cache Expire Time.
	 *
	 * Time = 12h (60*60*12).
	 *
	 * @var int
	 */
	private $_cache_expire = 43200;

	/**
	 * Is URL Shortner Option Enabled/Disabled.
	 *
	 * @var boolean
	 */
	private $is_url_shortner = false;

	/**
	 * Log alert.
	 *
	 * @param integer $type     - Alert code.
	 * @param array   $data     - Metadata.
	 * @param integer $date     - (Optional) Created on timestamp.
	 * @param integer $site_id   - (Optional) Site id.
	 */
	public function Log( $type, $data = array(), $date = null, $site_id = null ) {
		$this->_alert_id   = $type;
		$this->_alert_data = $data;
		$this->_alert_date = $this->get_correct_timestamp( $data, $date );

		//  we need to remove the timestamp
		unset( $data['Timestamp'] );

		$nb = new WSAL_NP_NotificationBuilder();

		$this->_s1_data = $nb->GetSelect1Data();
		$this->_s2_data = $nb->GetSelect2Data();
		$this->_s3_data = $nb->GetSelect3Data();
		$this->_s4_data = $nb->GetSelect4Data(); // Post status.
		$this->_s5_data = $nb->GetSelect5Data(); // Post types.
		$this->_s6_data = $nb->GetSelect6Data(); // User roles.
		$this->_s7_data = $nb->GetSelect7Data(); // Object.
		$this->_s8_data = $nb->GetSelect8Data(); // Event type.

		/**
		 * Cache notifications.
		 *
		 * @see http://codex.wordpress.org/Class_Reference/WP_Object_Cache
		 */
		$this->_notifications = wp_cache_get( WSAL_CACHE_KEY );

		if ( false === $this->_notifications ) {
			$this->_notifications = $this->plugin->notifications_util->GetNotifications();
			wp_cache_set( WSAL_CACHE_KEY, $this->_notifications, null, $this->_cache_expire );
		}
		$this->_notifyIfConditionMatch();
	}

	/**
	 * Notify if Condition Matches.
	 */
	private function _notifyIfConditionMatch() {
		if ( empty( $this->_notifications ) ) {
			return;
		}
		// Go through each notification.
		foreach ( $this->_notifications as $k => $v ) {
			$not_info = maybe_unserialize( $v->option_value );
			$enabled  = intval( $not_info->status );

			if ( 0 === $enabled ) {
				continue;
			}

			$skip = false;
			if ( ! empty( $not_info->firstTimeLogin ) && 1000 === $this->_alert_id ) {
				$users_login_list = $this->plugin->GetGlobalSetting( 'users_login_list' );
				if ( ! empty( $users_login_list ) ) {
					if ( in_array( $this->_alert_data['Username'], $users_login_list ) ) {
						$skip = true;
					} else {
						array_push( $users_login_list, $this->_alert_data['Username'] );
						$this->plugin->SetGlobalSetting( 'users_login_list', $users_login_list );
					}
				} else {
					$users_login_list = array();
					array_push( $users_login_list, $this->_alert_data['Username'] );
					$this->plugin->SetGlobalSetting( 'users_login_list', $users_login_list );
				}
			}
			// Skip Suspicious Activity.
			if ( ! empty( $not_info->failUser ) && 1002 === $this->_alert_id ) {
				$skip = true;
			}
			if ( ! empty( $not_info->failNotUser ) && 1003 === $this->_alert_id ) {
				$skip = true;
			}

			if ( $skip ) {
				continue;
			}

			$conditions           = $not_info->triggers;
			$num                  = count( $conditions );
			$title                = $not_info->title;
			$this->_email_address = $not_info->email;
			$this->phone_number   = ! empty( $not_info->phone ) ? $not_info->phone : false;

			if ( ! empty( $not_info->built_in ) ) {
				$this->_is_built_in = true;
			} else {
				$this->_is_built_in = false;
			}

			if ( ! empty( $not_info->subject ) && ! empty( $not_info->body ) ) {
				$this->_has_template['subject'] = $not_info->subject;
				$this->_has_template['body']    = $not_info->body;
			} else {
				$this->_has_template = false;
			}

			// #! one condition
			if ( 1 === $num ) {
				$condition = $conditions[0];

				// Handle PAGE ID AND CUSTOM POST ID deprecation.
				if ( 7 === $condition['select2'] || 8 === $condition['select2'] ) {
					$condition['select2'] = 6;
				}

				$s1 = $this->_s1_data[ $condition['select1'] ];
				$s2 = $this->_s2_data[ $condition['select2'] ];
				$s3 = $this->_s3_data[ $condition['select3'] ];
				$s4 = isset( $condition['select4'] ) ? $this->_s4_data[ $condition['select4'] ] : false; // Post status select.
				$s5 = isset( $condition['select5'] ) ? $this->_s5_data[ $condition['select5'] ] : false; // Post type select.
				$s6 = isset( $condition['select6'] ) ? $this->_s6_data[ $condition['select6'] ] : false; // User roles select.
				$s7 = isset( $condition['select7'] ) ? $this->_s7_data[ $condition['select7'] ] : false; // Object select.
				$s8 = isset( $condition['select8'] ) ? $this->_s8_data[ $condition['select8'] ] : false; // Event type select.
				$i1 = $condition['input1'];
				$this->_checkIfConditionMatch( $s1, $s2, $s3, $s4, $s5, $s6, $s7, $s8, $i1, $title, true );
			} else {
				// #! n conditions
				$test_array = array();
				$groups     = $not_info->viewState;
				$last_id    = 0;
				foreach ( $groups as $i => $entry ) {
					$i = $last_id;
					if ( is_string( $entry ) ) {
						array_push( $test_array, $conditions[ $i ] );
						$last_id++;
					} elseif ( is_array( $entry ) ) {
						$new = array();
						foreach ( $entry as $k => $item ) {
							array_push( $new, $conditions[ $last_id ] );
							$last_id++;
						}
						array_push( $test_array, $new );
					}
				}
				// Validate conditions.
				$exp    = new WSAL_NP_Expression( $this, $this->_s1_data, $this->_s2_data, $this->_s3_data, $title, $this->_s4_data, $this->_s5_data, $this->_s6_data, $this->_s7_data, $this->_s8_data );
				$result = $exp->EvaluateConditions( $test_array );
				if ( $result ) {
					$this->send_notification( $title );
				}
			}

			/* Trigger Critical alert*/
			$alert = $this->plugin->alerts->GetAlert( $this->_alert_id );
			if ( ! empty( $not_info->isCritical ) && 'E_CRITICAL' === $alert->severity ) {
				$this->send_notification( $title );
			}
		}
	}

	/**
	 * Check whether or not a condition matches anything in the Request $data
	 *
	 * @param string      $s1 - Select 1.
	 * @param string      $s2 - Select 2.
	 * @param string      $s3 - Select 3.
	 * @param string      $s4 - Select 4 / Post status.
	 * @param string      $s5 - Select 5 / Post type.
	 * @param string      $s6 - Select 6 / User role.
	 * @param string      $s7 - Select 7 / Object.
	 * @param string      $s8 - Select 8 / Event type.
	 * @param string      $i1 - Input 1.
	 * @param null|string $title - The title of the alert.
	 * @param bool        $send_email - Whether or not to send the notification email. Defaults to false.
	 * @return bool
	 */
	function _checkIfConditionMatch( $s1, $s2, $s3, $s4, $s5, $s6, $s7, $s8, $i1, $title = null, $send_email = false ) {
		$date_format    = $this->plugin->settings()->GetDateFormat();
		$time_format    = $this->plugin->settings()->GetTimeFormat();

		if ( 'IS EQUAL' == $s3 ) {
			// Default - $type == ALERT CODE.
			$value = $this->_alert_id;

			if ( 'DATE' == $s2 ) {
				$value = date( $date_format );
			} elseif ( 'TIME' == $s2 ) {
				$value = date( $time_format );
			} elseif ( 'USERNAME' == $s2 ) {
				$uid = ( isset( $this->_alert_data['CurrentUserID'] ) ? intval( $this->_alert_data['CurrentUserID'] ) : null );
				if ( empty( $uid ) ) { // will happen "on login"
					// This will be populated.
					if ( isset( $this->_alert_data['Username'] ) && ! empty( $this->_alert_data['Username'] ) ) {
						$value = $this->_alert_data['Username'];
					}
				} else {
					$user = get_user_by( 'id', $uid );
					if ( false === $user ) {
						$value = '';
					} else {
						$value = $user->user_login;
					}
				}
			} elseif ( 'USER ROLE' == $s2 ) {
				$roles = ( isset( $this->_alert_data['CurrentUserRoles'] ) ? $this->_alert_data['CurrentUserRoles'] : null );

				if ( is_array( $roles ) && !empty( $roles )) {
					$s6 = $this->sanitize_user_role( $s6 );
					foreach ( $roles as $role ) {
						if ( 0 === strcasecmp( $s6, $role ) ) {
							if ( $send_email ) {
								return $this->send_notification( $title );
							} else {
								return true;
							}
						}
					}
				}
			} elseif ( 'SOURCE IP' == $s2 ) {
				$value = $this->_alert_data['ClientIP'];
			} elseif ( 'PAGE ID' == $s2 || 'POST ID' == $s2 || 'CUSTOM POST ID' == $s2 ) {
				$pid = intval( $i1 );
				if ( empty( $pid ) || ! isset( $this->_alert_data['PostID'] ) ) {
					return false;
				}
				$dpid = intval( $this->_alert_data['PostID'] );

				if ( $pid <> $dpid ) {
					return false;
				}

				$post_type = strtolower( $this->_alert_data['PostType'] );

				if ( 'POST ID' == $s2 ) {
					if ( $send_email ) {
						return $this->send_notification( $title );
					} else {
						return true;
					}
				} elseif ( 'PAGE ID' == $s2 && 'page' == $post_type ) {
					if ( $send_email ) {
						return $this->send_notification( $title );
					} else {
						return true;
					}
				} elseif ( 'CUSTOM POST ID' == $s2 && ( 'post' != $post_type && 'page' != $post_type ) ) {
					if ( $send_email ) {
						return $this->send_notification( $title );
					} else {
						return true;
					}
				}
			} elseif ( 'SITE DOMAIN' == $s2 ) {
				$sid     = intval( $i1 );
				$blog_id = get_current_blog_id();
				if ( empty( $sid ) ) {
					return false;
				}

				if ( $sid <> $blog_id ) {
					return false;
				}
				if ( $send_email ) {
					return $this->send_notification( $title );
				} else {
					return true;
				}
			} elseif ( 'POST TYPE' == $s2 ) {
				$post_type = ( isset( $this->_alert_data['PostType'] ) ? strtolower( $this->_alert_data['PostType'] ) : null );

				if ( ! $this->plugin->IsMultisite() ) {
					// Convert value of $s5 to lowercase.
					$s5 = strtolower( $s5 );
				} else {
					$s5 = strtolower( $i1 );
				}

				if ( ! empty( $post_type ) && $s5 == $post_type ) {
					if ( $send_email ) {
						return $this->send_notification( $title );
					} else {
						return true;
					}
				}
			} elseif ( 'POST STATUS' === $s2 ) {
				// Get Post ID from alert data.
				$post_id = isset( $this->_alert_data['PostID'] ) ? intval( $this->_alert_data['PostID'] ) : false;

				// Get post status.
				$post_status = get_post_status( $post_id );

				// Return if post status is empty.
				if ( empty( $post_status ) ) {
					return false;
				}

				// Convert value of $s4 to lowercase.
				$s4 = strtolower( $s4 );

				// Check for publish post status.
				$post_status = ( 'publish' === $post_status ) ? 'published' : $post_status;

				// Send notification if the selected status matches with the post status.
				if ( ! empty( $s4 ) && $post_status === $s4 ) {
					if ( $send_email ) {
						return $this->send_notification( $title );
					} else {
						return true;
					}
				}
			} elseif ( 'OBJECT' === $s2 ) {
				$object = isset( $this->_alert_data['Object'] ) ? $this->_alert_data['Object'] : false;

				if ( ! $object || ! $s7) {
					return false;
				}

				//  most objects can be matched using the sanitized value as a key
				$match_found          = false;
				$sanitized_log_object = $this->sanitize_log_object( $s7 );
				if ( $object === $sanitized_log_object ) {
					$match_found = true;
				} else {
					//  some objects cannot be matched using the sanitized value, but can be found in the list of available objects
					$alternative_log_object = $this->get_alternative_object_key( $sanitized_log_object );
					if ( null !== $alternative_log_object && $object === $this->get_alternative_object_key( $sanitized_log_object )) {
						$match_found = true;
					}
				}

				if ( $match_found ) {
					if ( $send_email ) {
						return $this->send_notification( $title );
					}

					return true;
				}
			} elseif ( 'TYPE' === $s2 ) {
				$event_type = isset( $this->_alert_data['EventType'] ) ? $this->_alert_data['EventType'] : false;

				if ( ! $event_type ) {
					return false;
				}

				$s8 = strtolower( trim( $s8 ) );
				$s8 = str_replace( ' ', '-', $s8 );
				if ( $s8 && $event_type === $s8 ) {
					if ( $send_email ) {
						return $this->send_notification( $title );
					}
					return true;
				}
			} elseif ( 'CUSTOM USER FIELD' === $s2 ) {
				$result = in_array( $this->_alert_id, [
						4015,
						4016
					] ) && array_key_exists( 'custom_field_name', $this->_alert_data ) && $this->_alert_data['custom_field_name'] === $i1;
				if ( $result && $send_email ) {
					return $this->send_notification( $title );
				}

				return $result;
			}

			// Equality test - except user role.
			if ( $value == $i1 ) {
				if ( $send_email ) {
					return $this->send_notification( $title );
				} else {
					return true;
				}
			}
		} elseif ( 'CONTAINS' == $s3 ) { // Valid only for: SOURCE IP.
			if ( 'SOURCE IP' == $s2 ) {
				if ( false !== strpos( $this->_alert_data['ClientIP'], $i1 ) ) {
					if ( $send_email ) {
						return $this->send_notification( $title );
					} else {
						return true;
					}
				}
			}
		} elseif ( 'IS AFTER' == $s3 ) { // DATE & TIME ONLY.
			if ( 'DATE' == $s2 ) {
				$current_time  = current_time('timestamp');
				$value = strtotime( str_replace( '-', '/', $i1 ) );
				if ( $current_time > $value ) {
					if ( $send_email ) {
						return $this->send_notification( $title );
					} else {
						return true;
					}
				}
			} elseif ( 'TIME' == $s2 ) {
				$current_time  = current_time('timestamp');
				$configured_time = new DateTime( $i1, wp_timezone() );
				$value = $configured_time->getTimestamp();
				if ( $current_time > $value ) {
					if ( $send_email ) {
						return $this->send_notification( $title );
					} else {
						return true;
					}
				}
			}
		} elseif ( 'IS BEFORE' == $s3 ) { // TIME ONLY
			if ( 'TIME' == $s2 ) {
				$current_time  = current_time('timestamp');
				$configured_time = new DateTime( $i1, wp_timezone() );
				$value = $configured_time->getTimestamp();
				if ( $current_time < $value ) {
					if ( $send_email ) {
						return $this->send_notification( $title );
					} else {
						return true;
					}
				}
			}
		} elseif ( 'IS NOT' == $s3 ) {// USERNAME && USER ROLE && SOURCE IP
			if ( 'USERNAME' == $s2 ) {
				$uid = isset( $this->_alert_data['CurrentUserID'] ) ? $this->_alert_data['CurrentUserID'] : false;
				if ( false === $uid ) {
					$user = get_user_by( 'login', $i1 );
				} else {
					$user = get_user_by( 'id', $uid );
				}
				if ( false === $user ) {
					return false;
				}
				$value = $user->user_login;
				if ( $value != $i1 ) {
					if ( $send_email ) {
						return $this->send_notification( $title );
					} else {
						return true;
					}
				}
			} elseif ( 'USER ROLE' == $s2 ) {
				$role_found = false;
				$roles      = $this->_alert_data['CurrentUserRoles'];

				if ( is_array( $roles ) && !empty( $roles )) {
					$s6 = $this->sanitize_user_role( $s6 );
					foreach ( $roles as $role ) {
						if ( strcasecmp( $s6, $role ) == 0 ) {
							$role_found = true;
						}
					}
				}

				if ( ! $role_found ) {
					if ( $send_email ) {
						return $this->send_notification( $title );
					} else {
						return true;
					}
				}
			} elseif ( 'SOURCE IP' == $s2 ) {
				$value = $this->_alert_data['ClientIP'];
				if ( $i1 != $value ) {
					if ( $send_email ) {
						return $this->send_notification( $title );
					} else {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Send the notification email
	 *
	 * @param string $title - The Notification title.
	 *
	 * @return bool
	 * @throws Freemius_Exception
	 */
	public function send_notification( $title = '' ) {

		// Bail if we have null data.
		if ( ! isset( $this->_alert_data ) ) {
			return;
		}

		$alert         = $this->plugin->alerts->GetAlert( $this->_alert_id );
		$alert_message = $alert->GetMessage( (array) $this->_alert_data, null, 0, 'email' );
		$alert_formatter = WSAL_AlertFormatterFactory::getFormatter('email');

		$uid           = isset( $this->_alert_data['CurrentUserID'] ) ? $this->_alert_data['CurrentUserID'] : null;
		$username      = __( 'System', 'wp-security-audit-log' );

		if ( empty( $uid ) ) { // will happen "on login"
			// This will be populated.
			if ( ! empty( $this->_alert_data['Username'] ) ) {
				$username = $this->_alert_data['Username'];
			}
		} else {
			$user = get_user_by( 'id', $uid );
			if ( false !== $user ) {
				$username = $user->user_login;
			}
		}

		// Get user first and last names from resulting username.
		$current_user = get_user_by( 'login', $username );
		$first_name   = isset( $current_user->first_name ) ? $current_user->first_name : '';
		$last_name    = isset( $current_user->last_name ) ? $current_user->last_name : '';

		if ( $this->_alert_date ) {
			$date = WSAL_Utilities_DateTimeFormatter::instance()->getFormattedDateTime( $this->_alert_date );
		} else {
			$date = $this->plugin->notifications_util->GetFormattedDatetime();
		}

		$_user_roles = isset( $this->_alert_data['CurrentUserRoles'] ) ? $this->_alert_data['CurrentUserRoles'] : null;
		$user_role   = '';

		if ( isset( $_user_roles[0] ) && ! empty( $_user_roles[0] ) ) {
			if ( count( $_user_roles ) > 1 ) {
				$user_role = implode( ', ', $_user_roles );
			} else {
				$user_role = $_user_roles[0];
			}
		}

		$blogname           = $this->plugin->notifications_util->get_blog_domain();
		$name               = 'builder';
		$search_email_tags  = array_keys( $this->plugin->notifications_util->get_email_template_tags() );
		$replace_email_tags = array(
			$title,
			$blogname,
			$username,
			$first_name,
			$last_name,
			$user_role,
			$date,
			$this->_alert_id,
			$this->plugin->notifications_util->get_alert_severity( $this->_alert_id ),
			$alert_message,
			$alert->get_formatted_metadata( $alert_formatter, $this->_alert_data, 0 ),
			$alert->get_formatted_hyperlinks( $alert_formatter, $this->_alert_data, 0 ),
			$this->_alert_data['ClientIP'],
			$this->_alert_data['Object'],
			$this->_alert_data['EventType'],
		);

		if ( $this->_has_template ) {
			$subject = str_replace( $search_email_tags, $replace_email_tags, $this->_has_template['subject'] );
			$content = str_replace( $search_email_tags, $replace_email_tags, stripslashes( $this->_has_template['body'] ) );
		} else {
			$template = $this->plugin->notifications_util->GetEmailTemplate( $name );
			$subject  = str_replace( $search_email_tags, $replace_email_tags, $template['subject'] );
			$content  = str_replace( $search_email_tags, $replace_email_tags, stripslashes( $template['body'] ) );
		}

		// Send email notification.
		$email = false;
		if ( $this->_email_address ) {
			$email = $this->plugin->notifications_util->SendNotificationEmail( $this->_email_address, $subject, $content, $this->_alert_id );
		}

		// If phone number is set then send an SMS and return.
		$sms_message = false;
		if ( $this->phone_number ) {
			$this->is_url_shortner = $this->plugin->GetGlobalBooleanSetting( 'is-url-shortner' );
			$alert_message         = $alert->GetMessage( (array) $this->_alert_data, null, 0, 'sms' );
			$search_sms_tags       = array_keys( $this->plugin->notifications_util->get_sms_template_tags() );
			$replace_sms_tags      = array(
				$blogname,
				$username,
				$user_role,
				$date,
				$this->_alert_id,
				$this->plugin->notifications_util->get_alert_severity( $this->_alert_id ),
				$alert_message,
				$this->_alert_data['ClientIP'],
				$this->_alert_data['Object'],
				$this->_alert_data['EventType']
			);

			$sms_template = $this->plugin->notifications_util->get_sms_template( $name );
			$sms_content  = str_replace( $search_sms_tags, $replace_sms_tags, $sms_template['body'] );
			$sms_message  = $this->plugin->notifications_util->send_notification_sms( $this->phone_number, $sms_content );
		}

		return $email || $sms_message;
	}

	/**
	 * @param string $value User role string. Might contain spaces and uppercase letters.
	 *
	 * @return string Sanitized user role. All lowercase and underscores instead of spaces.
	 * @since 4.2.0
	 */
	private function sanitize_user_role( $value ) {
		return str_replace( ' ', '_', trim( strtolower( $value ) ) );
	}

	/**
	 * @param string $value Log object string. Might contain spaces and uppercase letters.
	 *
	 * @return string Sanitized log object. All lowercase and dashes instead of spaces.
	 * @since 4.2.0
	 */
	private function sanitize_log_object( $value ) {
		return str_replace( ' ', '-', trim( strtolower( $value ) ) );
	}

	/**
	 * Function provide an alternative log object key. Sometimes there is a mismatch between the log object identifier
	 * used in the UI and the key provided in the object's definition.
	 *
	 * @see WSAL_AlertManager::get_event_objects_data()
	 *
	 * @param string $sanitized_log_object Log object key used by the notifications UI.
	 *
	 * @return string|null An alternative key for the log object. Null if not available.
	 * @since 4.2.0
	 */
	private function get_alternative_object_key($sanitized_log_object) {
		$available_objects = $this->plugin->alerts->get_event_objects_data();
		$object_key        = array_search( $sanitized_log_object, $available_objects, true );
		if ( $object_key !== false ) {
			return $object_key;
		} else {
			foreach ( $available_objects as $log_object_key => $available_object ) {
				if ( $this->sanitize_log_object( $available_object ) === $sanitized_log_object ) {
					return $log_object_key;
				}
			}
		}

		return null;
	}
}
