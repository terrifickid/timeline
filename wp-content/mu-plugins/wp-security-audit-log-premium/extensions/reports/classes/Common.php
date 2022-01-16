<?php
/**
 * Reports Utility Class
 *
 * Provides utility methods to generate reports.
 *
 * @since 1.0.0
 * @package wsal
 * @subpackage reports
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WSAL_Rep_Plugin' ) ) {
	exit( 'You are not allowed to view this page.' );
}

/**
 * Class WSAL_Rep_Common
 * Provides utility methods to generate reports.
 *
 * @package wsal
 * @subpackage reports
 */
class WSAL_Rep_Common {

	const REPORT_HTML = 0;
	const REPORT_CSV = 1;
	const REPORT_DAILY = 'Daily';
	const REPORT_WEEKLY = 'Weekly';
	const REPORT_MONTHLY = 'Monthly';
	const REPORT_QUARTERLY = 'Quarterly';
	const WSAL_PR_PREFIX = 'periodic-report-';

	// Statistics reports criteria.
	const LOGIN_BY_USER = 1;
	const LOGIN_BY_ROLE = 2;
	const VIEWS_BY_USER = 3;
	const VIEWS_BY_ROLE = 4;
	const PUBLISHED_BY_USER = 5;
	const PUBLISHED_BY_ROLE = 6;
	const DIFFERENT_IP = 7;

	const SCHEDULED_HOOK_SUMMARY_EMAILS = 'wsal_summary_email_reports';
	const SCHEDULED_HOOK_REPORTS_PRUNING = 'wsal_reports_pruning';
	/**
	 * Is multisite?
	 *
	 * @var boolean
	 */
	private static $_iswpmu = false;
	/**
	 * Frequency daily hour
	 * For testing change hour here [01 to 23]
	 *
	 * @var string
	 */
	private static $_daily_hour = '08';
	/**
	 * Frequency montly date
	 * For testing change date here [01 to 31]
	 *
	 * @var string
	 */
	private static $_monthly_day = '01';
	/**
	 * Frequency weekly date
	 * For testing change date here [1 (for Monday) through 7 (for Sunday)]
	 *
	 * @var string
	 */
	private static $_weekly_day = '1';
	/**
	 * Schedule hook name
	 * For testing change the name
	 *
	 * @var string
	 */
	private static $_schedule_hook = 'summary_email_reports';
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
	 * Instance of WpSecurityAuditLog.
	 *
	 * @var object
	 */
	protected $wsal = null;
	/**
	 * Instance of WSAL_Rep_Util_O.
	 *
	 * @var object
	 */
	protected $ko = null;
	/**
	 * Instance of WSAL_Rep_Util_M.
	 *
	 * @var object
	 */
	protected $km = null;

	/**
	 * Sanitized date format. Used for filter and datepicker dates.
	 *
	 * @var string
	 */
	protected $_dateFormat = null;

	/**
	 * Time format.
	 *
	 * @var string
	 */
	protected $_timeFormat = null;
	/**
	 * Upload directory path.
	 *
	 * @var string
	 * @see CheckDirectory()
	 */
	protected $_uploadsDirPath = null;
	/**
	 * Attachments.
	 *
	 * @var null
	 */
	protected $_attachments = null;
	/**
	 * Holds the alert groups
	 *
	 * @var array
	 */
	private $_catAlertGroups = array();
	/**
	 * Errors array.
	 *
	 * @var array
	 */
	private $_errors = array();

	public function __construct( WpSecurityAuditLog $wsal ) {
		$this->wsal = $wsal;
		$this->ko   = new WSAL_Rep_Util_O();
		$this->km   = new WSAL_Rep_Util_M();

		// Get DateTime Format from WordPress General Settings.
		$this->_dateFormat = $this->wsal->settings()->GetDateFormat( true );
		$this->_timeFormat = $this->wsal->settings()->GetTimeFormat( true );

		self::$_iswpmu = $this->wsal->IsMultisite();

		// Cron job WordPress.
		add_action( self::SCHEDULED_HOOK_SUMMARY_EMAILS, array( $this, 'cronJob' ) );
		if ( ! wp_next_scheduled( self::SCHEDULED_HOOK_SUMMARY_EMAILS ) ) {
			wp_schedule_event( time(), 'hourly', self::SCHEDULED_HOOK_SUMMARY_EMAILS );
		}
		// Cron job Reports Directory Pruning.
		add_action( self::SCHEDULED_HOOK_REPORTS_PRUNING, array( $this, 'reportsPruning' ) );
		if ( ! wp_next_scheduled( self::SCHEDULED_HOOK_REPORTS_PRUNING ) ) {
			wp_schedule_event( time(), 'daily', self::SCHEDULED_HOOK_REPORTS_PRUNING );
		}

		// Set paths.
		$this->_base_dir = WSAL_BASE_DIR . 'extensions/reports';
		$this->_base_url = WSAL_BASE_URL . 'extensions/reports';

		add_action( 'user_register', array( $this, 'reset_users_counter' ) );
	}

	/**
	 * Method: Return Sites.
	 *
	 * @param int|null $limit Maximum number of sites to return (null = no limit).
	 *
	 * @return object Object with keys: blog_id, blogname, domain
	 */
	final public static function GetSites( $limit = null ) {
		global $wpdb;
		if ( self::$_iswpmu ) {
			$sql = 'SELECT blog_id, domain FROM ' . $wpdb->blogs;
			if ( ! is_null( $limit ) ) {
				$sql .= ' LIMIT ' . $limit;
			}
			$res = $wpdb->get_results( $sql );
			foreach ( $res as $row ) {
				$row->blogname = get_blog_option( $row->blog_id, 'blogname' );
			}
		} else {
			$res           = new stdClass();
			$res->blog_id  = get_current_blog_id();
			$res->blogname = esc_html( get_bloginfo( 'name' ) );
			$res           = array( $res );
		}

		return $res;
	}

	/**
	 * Method: Get site users.
	 *
	 * @param int|null $limit Maximum number of sites to return (null = no limit).
	 */
	final public static function GetUsers( $limit = null ) {
		global $wpdb;
		$t   = $wpdb->users;
		$sql = "SELECT ID, user_login FROM {$t}";
		if ( ! is_null( $limit ) ) {
			$sql .= ' LIMIT ' . $limit;
		}

		return $wpdb->get_results( $sql );
	}

	/**
	 * Get distinct values of IPs.
	 *
	 * @param int $limit - (Optional) Limit.
	 *
	 * @return array distinct values of IPs
	 */
	final public static function GetIPAddresses( $limit = null ) {
		$tmp = new WSAL_Models_Meta();
		$ips = $tmp->getAdapter()->GetMatchingIPs( $limit );

		return $ips;
	}

	/**
	 * Delete the setting by name.
	 *
	 * @param string $option - Option name.
	 *
	 * @return boolean result
	 */
	public function DeleteGlobalSetting( $option ) {
		$this->DeleteCacheNotif();

		return $this->wsal->DeleteGlobalSetting( $option );
	}

	/**
	 * Delete cache.
	 */
	public function DeleteCacheNotif() {
		if ( function_exists( 'wp_cache_delete' ) ) {
			wp_cache_delete( WSAL_CACHE_KEY_2 );
		}
	}

	/**
	 * Retrieve list of role names.
	 *
	 * @return array List of role names.
	 */
	public function GetRoles() {
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		return $wp_roles->get_names();
	}

	/**
	 * Get current users count.
	 *
	 * @return int
	 */
	public function get_users_count() {
		$is_multisite = $this->wsal->IsMultisite(); // Check for multisite.
		$get_fn       = $is_multisite ? 'get_site_transient' : 'get_transient';
		$set_fn       = $is_multisite ? 'set_site_transient' : 'set_transient';
		$blog_info    = self::GetCurrentBlogInfo();

		// Get transient.
		$count_transient = $is_multisite ? 'wsal_users_count_' . $blog_info->blog_id : 'wsal_users_count';
		$users_count     = $get_fn( $count_transient );

		if ( false === $users_count ) {
			$count       = count_users( 'time', $blog_info->blog_id );
			$users_count = isset( $count['total_users'] ) ? $count['total_users'] : false;
			$set_fn( $count_transient, $users_count, HOUR_IN_SECONDS );
		}

		return $users_count;
	}

	/**
	 * Retrieve the information about the current blog.
	 *
	 * @return mixed
	 */
	final public static function GetCurrentBlogInfo() {
		global $wpdb;
		$blog_id            = get_current_blog_id();
		$blog_data          = new stdClass();
		$blog_data->blog_id = $blog_id;

		if ( is_multisite() ) {
			$blog_data->blogname = get_blog_option( $blog_id, 'blogname' );
			$blog_data->domain   = $wpdb->get_var( 'SELECT domain FROM ' . $wpdb->blogs . ' WHERE blog_id=' . $blog_id );
		}

		return $blog_data;
	}

	/**
	 * Reset users count transient.
	 */
	public function reset_users_counter() {
		$is_multisite = $this->wsal->IsMultisite(); // Check for multisite.
		$get_fn       = $is_multisite ? 'get_site_transient' : 'get_transient';
		$del_fn       = $is_multisite ? 'delete_site_transient' : 'delete_transient';
		$blog_info    = self::GetCurrentBlogInfo();

		// Delete transient.
		$count_transient = $is_multisite ? 'wsal_users_count_' . $blog_info->blog_id : 'wsal_users_count';
		$users_count     = $get_fn( $count_transient );

		// If value exists then delete it.
		if ( false !== $users_count ) {
			$del_fn( $count_transient );
		}
	}

	/**
	 * Get alerts code.
	 *
	 * @return array
	 */
	final public function GetAlertCodes() {
		$data = $this->wsal->alerts->GetAlerts();
		$keys = array();
		if ( ! empty( $data ) ) {
			$keys = array_keys( $data );
			$keys = array_map( array( $this, 'PadKey' ), $keys );
		}

		return $keys;
	}

	/**
	 * Check to see whether or not the specified directory is accessible.
	 *
	 * @param string $dirPath - Directory Path.
	 *
	 * @return bool
	 */
	final public function CheckDirectory( $dirPath ) {
		if ( ! is_dir( $dirPath ) ) {
			return false;
		}
		if ( ! is_readable( $dirPath ) ) {
			return false;
		}
		if ( ! is_writable( $dirPath ) ) {
			return false;
		}
		// Create the index.php file if not already there.
		$this->CreateIndexFile( $dirPath );
		$this->_uploadsDirPath = $dirPath;

		return true;
	}

	/**
	 * Create an index.php file, if none exists, in order to avoid directory listing in the specified directory
	 *
	 * @param string $dirPath - Directory Path.
	 *
	 * @return bool
	 */
	final public function CreateIndexFile( $dirPath ) {
		// Check if index.php file exists.
		$dirPath = trailingslashit( $dirPath );
		$result  = 0;
		if ( ! is_file( $dirPath . 'index.php' ) ) {
			$result = @file_put_contents( $dirPath . 'index.php', '<?php /*[WP Activity Log Reporter plugin: This file was auto-generated to prevent directory listing ]*/ exit;' );
		}

		return ( $result > 0 );
	}

	final public function HasErrors() {
		return ( ! empty( $this->_errors ) );
	}

	final public function GetErrors() {
		return $this->_errors;
	}

	/**
	 * Erase the reports older than 1 week.
	 */
	public function reportsPruning() {
		$reports_dir_path = $this->wsal->settings()->get_working_dir_path( 'reports', true );
		if ( file_exists( $reports_dir_path ) ) {
			if ( $handle = opendir( $reports_dir_path ) ) {
				while ( false !== ( $entry = readdir( $handle ) ) ) {
					if ( $entry != '.' && $entry != '..' ) {
						$aFileName = explode( '_', $entry );
						if ( ! empty( $aFileName[2] ) ) {
							if ( $aFileName[2] <= date( 'mdYHis', strtotime( '-1 week' ) ) ) {
								@unlink( $reports_dir_path . DIRECTORY_SEPARATOR . $entry );
							}
						}
					}
				}
				closedir( $handle );
			}
		}
	}

	/**
	 * Execute cron job.
	 *
	 * @param bool $testSend - (Optional) Send now.
	 */
	public function cronJob( $testSend = false ) {
		$limit           = 100;
		$periodicReports = $this->GetPeriodicReports();
		if ( ! empty( $periodicReports ) ) {
			foreach ( $periodicReports as $name => $report ) {
				$sites     = $report->sites;
				$type      = $report->type;
				$frequency = $report->frequency;
				$send      = $this->checkCronJobDate( $frequency );
				if ( $send || $testSend ) {
					if ( ! empty( $report ) ) {
						$nextDate      = null;
						$aAlerts       = array();
						$post_types    = array();
						$post_statuses = array();
						// Unique IP report
						if ( ! empty( $report->enableUniqueIps ) ) {
							$this->SummaryReportUniqueIPS( $name );
						} else {
							$users       = ( ! empty( $report->users ) ? $report->users : array() );
							$roles       = ( ! empty( $report->roles ) ? $report->roles : array() );
							$ipAddresses = ( ! empty( $report->ipAddresses ) ? $report->ipAddresses : array() );
							$objects     = ( ! empty( $report->objects ) ? $report->objects : array() );
							$event_types = ( ! empty( $report->event_types ) ? $report->event_types : array() );

							if ( ! empty( $report->triggers ) ) {
								foreach ( $report->triggers as $key => $value ) {
									if ( isset( $value['alert_id'] ) && is_array( $value['alert_id'] ) ) {
										foreach ( $value['alert_id'] as $alert_id ) {
											array_push( $aAlerts, $alert_id );
										}
									} elseif ( isset( $value['alert_id'] ) ) {
										array_push( $aAlerts, $value['alert_id'] );
									}

									if ( isset( $value['post_types'] ) && is_array( $value['post_types'] ) ) {
										foreach ( $value['post_types'] as $post_type ) {
											array_push( $post_types, $post_type );
										}
									} elseif ( isset( $value['post_types'] ) ) {
										array_push( $post_types, $value['post_types'] );
									}

									if ( isset( $value['post_statuses'] ) && is_array( $value['post_statuses'] ) ) {
										foreach ( $value['post_statuses'] as $post_status ) {
											array_push( $post_statuses, $post_status );
										}
									} elseif ( isset( $value['post_statuses'] ) ) {
										array_push( $post_statuses, $value['post_statuses'] );
									}
								}
								$aAlerts = array_unique( $aAlerts );

								do {
									$nextDate = $this->BuildAttachment( $name, $aAlerts, $type, $frequency, $sites, $users, $roles, $ipAddresses, $nextDate, $limit, $post_types, $post_statuses, $objects, $event_types );
									$lastDate = $nextDate;
								} while ( $lastDate != null );

								if ( $lastDate == null ) {
									$this->sendSummaryEmail( $name, $aAlerts );
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Get an array with all the Configured Periodic Reports.
	 */
	public function GetPeriodicReports() {
		$aReports = array();
		$reports  = $this->wsal->GetNotificationsSetting( self::WSAL_PR_PREFIX );
		if ( ! empty( $reports ) ) {
			foreach ( $reports as $report ) {
				$aReports[ $report->option_name ] = unserialize( $report->option_value );
			}
		}

		return $aReports;
	}

	/**
	 * Check the cron job frequency.
	 *
	 * @param string $frequency - Frequency.
	 *
	 * @return bool - Send email or Not.
	 */
	private function checkCronJobDate( $frequency ) {
		$send = false;
		switch ( $frequency ) {
			case self::REPORT_DAILY:
				$send = ( self::$_daily_hour === $this->calculate_daily_hour() ) ? true : false;
				break;
			case self::REPORT_WEEKLY:
				$weekly_day = $this->calculate_weekly_day();
				if ( empty( $weekly_day ) ) {
					$send = false;
				} else {
					$send = ( $weekly_day === self::$_weekly_day ) ? true : false;
				}
				break;
			case self::REPORT_MONTHLY:
				$str_date = $this->calculate_monthly_day();
				if ( empty( $str_date ) ) {
					$send = false;
				} else {
					$send = ( date( 'Y-m-d' ) == $str_date ) ? true : false;
				}
				break;
			case self::REPORT_QUARTERLY:
				$send = $this->CheckQuarter();
				break;
			default:
				//  fallback for any other reports would go here
				break;
		}

		return $send;
	}

	/**
	 * Method: Calculate and return hour of the day
	 * based on WordPress timezone.
	 *
	 * @return string - Hour of the day.
	 * @since 2.1.1
	 */
	private function calculate_daily_hour() {
		return date( 'H', time() + ( get_option( 'gmt_offset' ) * ( 60 * 60 ) ) );
	}

	/**
	 * Method: Calculate and return day of the week
	 * based on WordPress timezone.
	 *
	 * @return string|bool - Day of the week or false.
	 * @since 2.1.1
	 */
	private function calculate_weekly_day() {
		if ( self::$_daily_hour === $this->calculate_daily_hour() ) {
			return date( 'w' );
		}

		return false;
	}

	/**
	 * Method: Calculate and return day of the month
	 * based on WordPress timezone.
	 *
	 * @return string|bool - Day of the week or false.
	 * @since 2.1.1
	 */
	private function calculate_monthly_day() {
		if ( self::$_daily_hour === $this->calculate_daily_hour() ) {
			return date( 'Y-m-' ) . self::$_monthly_day;
		}

		return false;
	}

	/**
	 * Check Quarter of the year
	 * in the cron job.
	 *
	 * @return bool true|false
	 */
	private function CheckQuarter() {
		$hour  = date( 'H', time() + ( get_option( 'gmt_offset' ) * ( 60 * 60 ) ) );
		$month = date( 'n', time() + ( get_option( 'gmt_offset' ) * ( 60 * 60 ) ) );
		$day   = date( 'j', time() + ( get_option( 'gmt_offset' ) * ( 60 * 60 ) ) );
		if ( '1' == $day && self::$_daily_hour === $hour ) {
			switch ( $month ) {
				case '1':
				case '4':
				case '7':
				case '10':
					return true;
					break;
				default:
					return false;
					break;
			}
		}

		return false;
	}

	/**
	 * Create and send the report unique IP by email.
	 *
	 * @param string $name - Group name.
	 *
	 * @throws Freemius_Exception
	 */
	public function SummaryReportUniqueIPS( $name ) {
		$report_name   = str_replace( WpSecurityAuditLog::OPTIONS_PREFIX, '', $name );
		$notifications = $this->GetSettingByName( $report_name );
		if ( ! empty( $notifications ) ) {
			if ( ! empty( $notifications->enableUniqueIps ) ) {
				$reportFormat = $notifications->type;
				$frequency    = $notifications->frequency;
				$email        = $notifications->email;

				$report_args = new WSAL_ReportArgs();
				if ( ! empty( $notifications->sites ) ) {
					$report_args->site__in = $notifications->sites;
				}

				if ( ! empty( $notifications->users ) ) {
					$report_args->user__in = $notifications->users;
				}

				if ( ! empty( $notifications->roles ) ) {
					$report_args->role__in = $notifications->roles;
				}

				if ( ! empty( $notifications->ipAddresses ) ) {
					$report_args->ip__in = $notifications->ipAddresses;
				}

				switch ( $frequency ) {
					case self::REPORT_DAILY:
						$dateStart = date( $this->_dateFormat, strtotime( '00:00:00' ) );
						break;
					case self::REPORT_WEEKLY:
						$dateStart = date( $this->_dateFormat, strtotime( '-1 week' ) );
						break;
					case self::REPORT_MONTHLY:
						$dateStart = date( $this->_dateFormat, strtotime( '-1 month' ) );
						break;
					case self::REPORT_QUARTERLY:
						$dateStart = date( $this->_dateFormat, strtotime( '-3 month' ) );
						break;
					default:
						//  fallback for any other reports would go here
						break;
				}

				if ( $dateStart ) {
					$report_args->start_date = $dateStart;
				}

				$dateEnd = date( $this->_dateFormat, current_time( 'timestamp' ) );
				if ( $dateEnd ) {
					$report_args->end_date = $dateEnd;
				}

				$results = $this->wsal->getConnector()->getAdapter( 'Occurrence' )->GetReportGrouped( $report_args );
				$results = array_values( $results );

				$this->_uploadsDirPath = $this->wsal->settings()->get_working_dir_path( 'reports' );

				if ( $reportFormat == self::REPORT_HTML ) {
					$htmlReport  = new WSAL_Rep_HtmlReportGenerator();
					$attachments = $htmlReport->GenerateUniqueIPS( $results, $this->_uploadsDirPath, $dateStart, $dateEnd );
				} else {
					$csvReport   = new WSAL_Rep_CsvReportGenerator();
					$attachments = $csvReport->GenerateUniqueIPS( $results, $this->_uploadsDirPath );
				}

				switch ( $frequency ) {
					case self::REPORT_DAILY:
						$pre_subject = sprintf( __( '%1$s - Website %2$s', 'wp-security-audit-log' ), date( $this->_dateFormat, time() ), get_bloginfo( 'name' ) );
						break;
					case self::REPORT_WEEKLY:
						$pre_subject = sprintf( __( 'Week number %1$s - Website %2$s', 'wp-security-audit-log' ), date( 'W', strtotime( '-1 week' ) ), get_bloginfo( 'name' ) );
						break;
					case self::REPORT_MONTHLY:
						$pre_subject = sprintf( __( 'Month %1$s %2$s- Website %3$s', 'wp-security-audit-log' ), date( 'F', strtotime( '-1 month' ) ), date( 'Y', strtotime( '-1 month' ) ), get_bloginfo( 'name' ) );
						break;
					case self::REPORT_QUARTERLY:
						$pre_subject = sprintf( __( 'Quarter %1$s - Website %2$s', 'wp-security-audit-log' ), $this->WhichQuarter(), get_bloginfo( 'name' ) );
						break;
					default:
						//  fallback for any other reports would go here
						break;
				}

				if ( ! empty( $attachments ) ) {
					$attachments = $this->_uploadsDirPath . $attachments;
					$subject     = $pre_subject . sprintf( __( ' - %s Email Report', 'wp-security-audit-log' ), 'List of unique IP addresses used by the same user' );
					$content     = '<p>The report with the list of unique IP addresses used by the same user on website ' . get_bloginfo( 'name' ) . ' for';
					switch ( $frequency ) {
						case self::REPORT_DAILY:
							$content .= ' ' . date( $this->_dateFormat, time() );
							break;
						case self::REPORT_WEEKLY:
							$content .= ' week ' . date( 'W', strtotime( '-1 week' ) );
							break;
						case self::REPORT_MONTHLY:
							$content .= ' the month of ' . date( 'F', strtotime( '-1 month' ) ) . ' ' . date( 'Y', strtotime( '-1 month' ) );
							break;
						case self::REPORT_QUARTERLY:
							$content .= ' the quarter ' . $this->WhichQuarter();
							break;
						default:
							//  fallback for any other reports would go here
							break;
					}
					$content .= ' is attached.</p>';

					WSAL_Utilities_Emailer::send_email( $email, $subject, $content, '', $attachments );
				}
			}
		}
	}

	/**
	 * Get the setting by name.
	 *
	 * @param string $option - Option name.
	 * @param mixed $default - Default option value.
	 *
	 * @return mixed value
	 */
	public function GetSettingByName( $option, $default = false ) {
		return $this->wsal->GetGlobalSetting( $option, $default );
	}

	/**
	 * Get Quarter of the year.
	 *
	 * @return string N. quarter
	 */
	private function WhichQuarter() {
		$month = date( 'n', time() );
		if ( $month >= 1 && $month <= 3 ) {
			return 'Q1';
		} elseif ( $month >= 4 && $month <= 6 ) {
			return 'Q2';
		} elseif ( $month >= 7 && $month <= 9 ) {
			return 'Q3';
		} elseif ( $month >= 10 && $month <= 12 ) {
			return 'Q4';
		}
	}

	/**
	 * Create the report appending in a json file.
	 *
	 * @return string $lastDate last_date
	 */
	public function BuildAttachment( $attachKey, $aAlerts, $type, $frequency, $sites, $users, $roles, $ipAddresses, $nextDate, $limit, $post_types = '', $post_statuses = '', $objects = array(), $event_types = array() ) {
		@ini_set( 'max_execution_time', '300' ); // Set execution time to 300.

		$lastDate = null;
		$result   = $this->GetListEvents( $aAlerts, $type, $frequency, $sites, $users, $roles, $ipAddresses, $nextDate, $limit, $post_types, $post_statuses, $objects, $event_types );

		if ( ! empty( $result['lastDate'] ) ) {
			$lastDate = $result['lastDate'];
		}

		$filename = $this->_uploadsDirPath . 'result_' . $this->secureFileName( $attachKey ) . '-user' . get_current_user_id() . '.json';
		if ( file_exists( $filename ) ) {
			$data = json_decode( file_get_contents( $filename ), true );
			if ( ! empty( $data ) ) {
				if ( ! empty( $result ) ) {
					$todays_date = date( 'm-d-Y', time() );
					foreach ( $result['data'] as $value ) {
						// first 10 chars in value are in format: 'mm-dd-YYYY'
						// NOTE: that is same as date( 'm-d-Y' ) formatted.
						$item_date = substr( $value['date'], 0, 10 );
						// we only want items from BEFORE today.
						if ( $todays_date !== $item_date ) {
							array_push( $data['data'], $value );
						}
					}
				}
				$data['lastDate'] = $lastDate;
				file_put_contents( $filename, json_encode( $data ) );
			}
		} else {
			if ( ! empty( $result ) ) {
				file_put_contents( $filename, json_encode( $result ) );
			}
		}

		return $lastDate;
	}

	/**
	 * Generate the file of the report (HTML or CSV).
	 *
	 * @return string|bool filename or false
	 */
	private function GetListEvents( $aAlerts, $type, $frequency, $sites, $users, $roles, $ipAddresses, $nextDate, $limit, $post_types, $post_statuses, $objects, $event_types ) {
		switch ( $frequency ) {
			case self::REPORT_DAILY:
				// get YESTERDAYS date.
				$start_date = date( $this->_dateFormat, strtotime( 'yesterday' ) );
				break;
			case self::REPORT_WEEKLY:
				$start_date = date( $this->_dateFormat, strtotime( 'last week' ) );
				$end_date   = date( $this->_dateFormat, strtotime( 'last week + 6 days' ) );
				break;
			case self::REPORT_MONTHLY:
				$start_date = date( $this->_dateFormat, strtotime( 'last month' ) );
				$end_date   = date( $this->_dateFormat, strtotime( 'this month - 1 day' ) );
				break;
			case self::REPORT_QUARTERLY:
				$start_date = $this->StartQuarter();
				break;
			default:
				//  fallback for any other reports would go here
				break;
		}
		$filters['sites']                        = $sites;
		$filters['users']                        = $users;
		$filters['roles']                        = $roles;
		$filters['ip-addresses']                 = $ipAddresses;
		$filters['objects']                      = $objects;
		$filters['event-types']                  = $event_types;
		$filters['alert_codes']['groups']        = array();
		$filters['alert_codes']['alerts']        = $aAlerts;
		$filters['alert_codes']['post_types']    = $post_types;
		$filters['alert_codes']['post_statuses'] = $post_statuses;
		$filters['date_range']['start']          = $start_date;
		$filters['date_range']['end']            = ( isset( $end_date ) ) ? $end_date : date( $this->_dateFormat, strtotime( 'yesterday' ) );
		$filters['report_format']                = $type;
		$filters['nextDate']                     = $nextDate;
		$filters['limit']                        = $limit;
		$this->_uploadsDirPath                   = $this->wsal->settings()->get_working_dir_path( 'reports' );
		return $this->GenerateReport( $filters, false );
	}

	/**
	 * Get Start Quarter of the year.
	 *
	 * @return string $start_date
	 */
	private function StartQuarter() {
		$month = date( 'n', time() );
		$year  = date( 'Y', time() );
		if ( $month >= 1 && $month <= 3 ) {
			$start_date = date( $this->_dateFormat, strtotime( $year . '-01-01' ) );
		} elseif ( $month >= 4 && $month <= 6 ) {
			$start_date = date( $this->_dateFormat, strtotime( $year . '-04-01' ) );
		} elseif ( $month >= 7 && $month <= 9 ) {
			$start_date = date( $this->_dateFormat, strtotime( $year . '-07-01' ) );
		} elseif ( $month >= 10 && $month <= 12 ) {
			$start_date = date( $this->_dateFormat, strtotime( $year . '-10-01' ) );
		}

		return $start_date;
	}

	/**
	 * Generate report matching the filter passed.
	 *
	 * @param array $filters - Filters.
	 * @param bool $validate - (Optional) Validation.
	 *
	 * @return array $dataAndFilters
	 * @throws Freemius_Exception
	 */
	public function GenerateReport( array $filters, $validate = true ) {

		// Fields we can loop.
		$possible_filters = [
			'sites',
			'sites-exclude',
			'users',
			'users-exclude',
			'roles',
			'roles-exclude',
			'ip-addresses',
			'ip-addresses-exclude',
			'alert_codes',
			'alert_codes|groups',
			'alert_codes|alerts',
			'alert_codes|post_types',
			'alert_codes|post_statuses',
			'date_range',
			'date_range|start',
			'date_range|end',
			'report_format',
			'objects',
			'objects-exclude',
			'event-types',
			'event-types-exclude',
		];

		// Arguments we will fill in.
		$actual_filters = [];

		// Validate if requested.
		if ( $validate ) {
			foreach ( $possible_filters as $filter ) {
				if ( strpos( $filter, '|' ) !== false ) {
					$array_indexes = explode( '|', $filter, 2 );
					if ( ! isset( $filters[ $array_indexes[0] ][ $array_indexes[1] ] ) ) {
						$this->_addError( sprintf( __( 'Internal error. <code>%s</code> key was not found.', 'wp-security-audit-log' ), $filter ) );

						return false;
					}
				} else {
					if ( ! isset( $filters[ $filter ] ) ) {
						$this->_addError( sprintf( __( 'Internal error. <code>%s</code> key was not found.', 'wp-security-audit-log' ), $filter ) );

						return false;
					}
				}
			}
		}

		// Create filters based on possible fields.
		foreach ( $possible_filters as $filter ) {
			if ( strpos( $filter, '|' ) !== false ) {
				$array_indexes                        = explode( '|', $filter, 2 );
				$value                                = ( isset( $filters[ $array_indexes[0] ][ $array_indexes[1] ] ) && ! empty( $filters[ $array_indexes[0] ][ $array_indexes[1] ] ) ) ? $filters[ $array_indexes[0] ][ $array_indexes[1] ] : null;
				$tidy_filter_index                    = str_replace( '|', '_', $filter );
				$actual_filters[ $tidy_filter_index ] = $value;
			} else {
				$value2                    = ( isset( $filters[ $filter ] ) && ! empty( $filters[ $filter ] ) ) ? $filters[ $filter ] : null;
				$actual_filters[ $filter ] = $value2;
			}
		}

		if ( empty( $actual_filters['alert_codes_groups'] ) && empty( $actual_filters['alert_codes_alerts'] ) ) {
			$this->_addError( __( 'Please specify at least one Alert Group or specify an Alert Code.', 'wp-security-audit-log' ) );

			return false;
		}

		// Filters.
		$reportFormat = ( empty( $filters['report_format'] ) ? self::REPORT_HTML : self::REPORT_CSV );
		if ( $reportFormat <> self::REPORT_CSV && $reportFormat <> self::REPORT_HTML ) {
			$this->_addError( __( 'Internal Error: Could not detect the type of the report to generate.', 'wp-security-audit-log' ) );

			return false;
		}

		$args = WSAL_ReportArgs::build_from_extension_filters( $actual_filters, $this );

		$_nextDate = ( empty( $filters['nextDate'] ) ? null : $filters['nextDate'] );
		$limit     = ( empty( $filters['limit'] ) ? WSAL_Rep_Views_Main::REPORT_LIMIT : $filters['limit'] );
		$_limit    = apply_filters( 'wsal_reporting_query_limit', $limit );

		$lastDate = null;

		if ( ! empty( $filters['unique_ip'] ) ) {
			$results = $this->wsal->getConnector()->getAdapter( 'Occurrence' )->GetReportGrouped( $args );
		} else {
			$results = $this->wsal->getConnector()->getAdapter( 'Occurrence' )->GetReporting( $args, $_nextDate, $_limit );
		}

		if ( ! empty( $results['lastDate'] ) ) {
			$lastDate = $results['lastDate'];
			unset( $results['lastDate'] );
		}

		if ( empty( $results ) ) {
			$this->_addError( __( 'There are no alerts that match your filtering criteria. Please try a different set of rules.', 'wp-security-audit-log' ) );

			return false;
		}

		$data           = array();
		$dataAndFilters = array();

		if ( ! empty( $filters['unique_ip'] ) ) {
			$data = array_values( $results );
		} else {
			// #! Get Alert details
			foreach ( $results as $i => $entry ) {
				$ip    = esc_html( $entry->ip );
				$ua    = esc_html( $entry->ua );
				$roles = maybe_unserialize( $entry->roles );

				if ( $entry->alert_id == '9999' ) {
					continue;
				}
				if ( is_string( $roles ) ) {
					$roles = str_replace( array( '"', '[', ']' ), ' ', $roles );
				}

				$t = $this->_getAlertDetails( $reportFormat, $entry->id, $entry->alert_id, $entry->site_id, $entry->created_on, $entry->object, $entry->event_type, $entry->user_id, $roles, $ip, $ua );
				if ( ! empty( $actual_filters['ip-addresses'] ) ) {
					if ( in_array( $entry->ip, $actual_filters['ip-addresses'] ) ) {
						array_push( $data, $t );
					}
				} else {
					array_push( $data, $t );
				}
			}
		}

		if ( empty( $data ) ) {
			$this->_addError( __( 'There are no alerts that match your filtering criteria. Please try a different set of rules.', 'wp-security-audit-log' ) );

			return false;
		}

		$dataAndFilters['data']         = $data;
		$dataAndFilters['filters']      = $filters;
		$dataAndFilters['lastDate']     = $lastDate;
		$dataAndFilters['events_found'] = count( $data );

		return $dataAndFilters;
	}

	private function _addError( $error ) {
		array_push( $this->_errors, $error );
	}

	/**
	 * Alert Groups
	 * if we have alert groups, we need to retrieve all alert codes for those groups
	 * and add them to a final alert of alert codes that will be sent to db in the select query
	 * the same goes for individual alert codes
	 */
	public function GetCodesByGroups( $alertGroups, $alertCodes, $showError = true ) {
		$_codes         = array();
		$hasAlertGroups = ( empty( $alertGroups ) ? false : true );
		$hasAlertCodes  = ( empty( $alertCodes ) ? false : true );
		if ( $hasAlertCodes ) {
			// Add the specified alerts to the final array.
			$_codes = $alertCodes;
		}
		if ( $hasAlertGroups ) {
			// Get categorized alerts.
			$alerts    = $this->wsal->alerts->GetCategorizedAlerts();
			$catAlerts = array();
			foreach ( $alerts as $cname => $group ) {
				foreach ( $group as $subname => $_entries ) {
					$catAlerts[ $subname ] = $_entries;
				}
			}
			$this->_catAlertGroups = array_keys( $catAlerts );
			if ( empty( $catAlerts ) ) {
				if ( $showError ) {
					$this->_addError( __( 'Internal Error. Could not retrieve the alerts from the main plugin.', 'wp-security-audit-log' ) );
				}

				return false;
			}
			// Make sure that all specified alert categories are valid.
			foreach ( $alertGroups as $k => $category ) {
				// get alerts from the category and add them to the final array
				// #! only if the specified category is valid, otherwise skip it.
				if ( isset( $catAlerts[ $category ] ) ) {
					// If this is the "System Activity" category...some of those alert needs to be padded.
					if ( $category == __( 'System Activity', 'wp-security-audit-log' ) ) {
						foreach ( $catAlerts[ $category ] as $i => $alert ) {
							$aid = $alert->code;
							if ( strlen( $aid ) == 1 ) {
								$aid = $this->PadKey( $aid );
							}
							array_push( $_codes, $aid );
						}
					} else {
						foreach ( $catAlerts[ $category ] as $i => $alert ) {
							array_push( $_codes, $alert->code );
						}
					}
				}
			}
		}
		if ( empty( $_codes ) ) {
			if ( $showError ) {
				$this->_addError( __( 'Please specify at least one Alert Group or specify an Alert Code.', 'wp-security-audit-log' ) );
			}

			return false;
		}

		return $_codes;
	}

	/**
	 * Method: Key padding.
	 *
	 * @param string $key - The key to pad.
	 *
	 * @return string
	 * @internal
	 */
	final public function PadKey( $key ) {
		if ( strlen( $key ) == 1 ) {
			$key = str_pad( $key, 4, '0', STR_PAD_LEFT );
		}

		return $key;
	}

	/**
	 * Get alert details.
	 *
	 * @param $report_format
	 * @param int $entryId - Entry ID.
	 * @param int $alertId - Alert ID.
	 * @param int $siteId - Site ID.
	 * @param string $createdOn - Alert generation time.
	 * @param string $object
	 * @param string $event_type
	 * @param int $userId - User ID.
	 * @param string|array $roles - User roles.
	 * @param string $ip - IP address of the user.
	 * @param string $ua - User agent.
	 *
	 * @return array|false details
	 * @throws Freemius_Exception
	 */
	private function _getAlertDetails( $report_format, $entryId, $alertId, $siteId, $createdOn, $object, $event_type, $userId = null, $roles = null, $ip = '', $ua = '' ) {
		// Must be a new instance every time, otherwise the alert message is not retrieved properly.
		$this->ko = new WSAL_Rep_Util_O();
		// #! Get alert details
		$alerts_manager = $this->wsal->alerts;
		$code           = $alerts_manager->GetAlert( $alertId );
		$code           = $code ? $code->severity : 0;
		$const          = $this->wsal->constants->get_constant_to_display( $code );

		// Blog details.
		if ( $this->wsal->IsMultisite() ) {
			$blogInfo = get_blog_details( $siteId, true );
			$blogName = esc_html__( 'Unknown Site', 'wp-security-audit-log' );
			$blogUrl  = '';
			if ( $blogInfo ) {
				$blogName = esc_html( $blogInfo->blogname );
				$blogUrl  = esc_attr( $blogInfo->siteurl );
			}
		} else {
			$blogName = get_bloginfo( 'name' );
			$blogUrl  = '';
			if ( empty( $blogName ) ) {
				$blogName = __( 'Unknown Site', 'wp-security-audit-log' );
			} else {
				$blogName = esc_html( $blogName );
				$blogUrl  = esc_attr( get_bloginfo( 'url' ) );
			}
		}

		// Get the alert message - properly.
		$this->ko->id         = $entryId;
		$this->ko->site_id    = $siteId;
		$this->ko->alert_id   = $alertId;
		$this->ko->created_on = $createdOn;
		if ( ! $this->ko->_cachedMessage ) {
			$this->ko->_cachedMessage = $this->ko->GetAlert()->mesg;
		}

		if ( empty( $userId ) ) {
			$username = __( 'System', 'wp-security-audit-log' );
			$role     = '';
		} else {
			$user     = new WP_User( $userId );
			$username = $user->user_login;
			$role     = ( is_array( $roles ) ? implode( ', ', $roles ) : $roles );
		}
		if ( empty( $role ) ) {
			$role = '';
		}

		$formattedDate  = WSAL_Utilities_DateTimeFormatter::instance()->getFormattedDateTime( $createdOn, 'datetime', true, false, false, false );
		$messageContext = ( $report_format == self::REPORT_CSV ) ? 'report-csv' : 'report-html';

		$alertObject     = $this->ko->GetAlert();
		$meta            = $this->ko->GetMetaArray();

		// Meta details.
		$result = array(
			'blog_name'  => $blogName,
			'blog_url'   => $blogUrl,
			'alert_id'   => $alertId,
			'date'       => $formattedDate,
			//  We need to keep the timestamp to be able to group entries by dates etc. The "date" field is not suitable
			//  as it is already translated, thus difficult to parse and process.
			'timestamp'  => $createdOn,
			'code'       => $const->name,
			// Fill variables in message.
			'message'    => $alertObject->GetMessage( $meta, $this->ko->_cachedMessage, $entryId, $messageContext ),
			'user_id'    => $userId,
			'user_name'  => $username,
			'role'       => $role,
			'user_ip'    => $ip,
			'object'     => $alerts_manager->get_event_objects_data( $object ),
			'event_type' => $alerts_manager->get_event_type_data( $event_type ),
			'user_agent' => $ua,
		);

		//  metadata and links are formatted separately for CSV only
		if ( $report_format == self::REPORT_CSV ) {
			$alert_formatter = WSAL_AlertFormatterFactory::getFormatter( $messageContext );
			$alert_formatter->set_end_of_line('; ');

			$result['metadata'] = $alertObject->get_formatted_metadata( $alert_formatter, $meta, $alertId );
			$result['links'] = $alertObject->get_formatted_hyperlinks( $alert_formatter, $meta, $alertId );
		}

		return $result;
	}

	/**
	 * Secures given string to make sure it cannot be used to traverse file system when used as (part of) a filename. It
	 * replaces any traversal character (slashes) with underscores.
	 *
	 * @param string $str String to secure.
	 *
	 * @return string Secure string.
	 * @since 4.1.5
	 *
	 */
	private function secureFileName( $str ) {
		$str = str_replace( '/', '_', $str );
		$str = str_replace( '\\', '_', $str );
		$str = str_replace( DIRECTORY_SEPARATOR, '_', $str ); // In case it does not equal the standard values

		return $str;
	}

	/**
	 * Send the summary email.
	 *
	 * @param string $name - Report name.
	 * @param array $alertCodes - Array of alert codes.
	 *
	 * @return bool $result
	 */
	public function sendSummaryEmail( $name, $alertCodes ) {
		$result        = null;
		$report_name   = str_replace( WpSecurityAuditLog::OPTIONS_PREFIX, '', $name );
		$notifications = $this->GetSettingByName( $report_name );

		if ( ! empty( $notifications ) ) {
			$email     = $notifications->email;
			$frequency = $notifications->frequency;
			$sites     = $notifications->sites;
			$title     = $notifications->title;

			switch ( $frequency ) {
				case self::REPORT_DAILY:
					$pre_subject = sprintf( __( '%1$s - Website %2$s', 'wp-security-audit-log' ), date( $this->_dateFormat, time() ), get_bloginfo( 'name' ) );
					break;
				case self::REPORT_WEEKLY:
					$pre_subject = sprintf( __( 'Week number %1$s - Website %2$s', 'wp-security-audit-log' ), date( 'W', strtotime( '-1 week' ) ), get_bloginfo( 'name' ) );
					break;
				case self::REPORT_MONTHLY:
					$pre_subject = sprintf( __( 'Month %1$s %2$s- Website %3$s', 'wp-security-audit-log' ), date( 'F', strtotime( '-1 month' ) ), date( 'Y', strtotime( '-1 month' ) ), get_bloginfo( 'name' ) );
					break;
				case self::REPORT_QUARTERLY:
					$pre_subject = sprintf( __( 'Quarter %1$s - Website %2$s', 'wp-security-audit-log' ), $this->WhichQuarter(), get_bloginfo( 'name' ) );
					break;
				default:
					//  fallback for any other reports would go here
					break;
			}

			// Number logins report.
			$isNumberLogins = false;
			if ( ! empty( $notifications->enableNumberLogins ) ) {
				$isNumberLogins = true;
			}

			$attachments = $this->GetAttachment( $name, $isNumberLogins );
			if ( ! empty( $attachments ) ) {
				$subject = $pre_subject . sprintf( __( ' - %s Email Report', 'wp-security-audit-log' ), $title );
				$content = '<p>The report ' . $title . ' from website ' . get_bloginfo( 'name' ) . ' for';
				switch ( $frequency ) {
					case self::REPORT_DAILY:
						$content .= ' ' . date( $this->_dateFormat, time() );
						break;
					case self::REPORT_WEEKLY:
						$content .= ' week ' . date( 'W', strtotime( '-1 week' ) );
						break;
					case self::REPORT_MONTHLY:
						$content .= ' the month of ' . date( 'F', strtotime( '-1 month' ) ) . ' ' . date( 'Y', strtotime( '-1 month' ) );
						break;
					case self::REPORT_QUARTERLY:
						$content .= ' the quarter ' . $this->WhichQuarter();
						break;
					default:
						//  fallback for any other reports would go here
						break;
				}
				$content .= ' is attached.</p>';

				if ( class_exists( 'WSAL_Utilities_Emailer' ) ) {
					// Get email template.
					$result = WSAL_Utilities_Emailer::send_email( $email, $subject, $content, '', $attachments );
				}
			}

			return $result;
		}

		return $result;
	}

	/**
	 * Generate the file (HTML or CSV) from the json file.
	 *
	 * @return string $result path of the file
	 */
	private function GetAttachment( $attachKey, $isNumberLogins ) {
		$result                = null;
		$this->_uploadsDirPath = $this->wsal->settings()->get_working_dir_path( 'reports', true );
		$filename              = $this->_uploadsDirPath . 'result_' . $this->secureFileName( $attachKey ) . '-user' . get_current_user_id() . '.json';
		if ( file_exists( $filename ) ) {
			$data = json_decode( file_get_contents( $filename ), true );
			if ( $isNumberLogins ) {
				$data['filters']['number_logins'] = true;
			}
			$result = $this->FileGenerator( $data['data'], $data['filters'] );
			$result = $this->_uploadsDirPath . $result;
		}
		@unlink( $filename );

		return $result;
	}

	/**
	 * Generate the file of the report (HTML or CSV).
	 *
	 * @param array $data - Data.
	 * @param array $filters - Filters.
	 *
	 * @return string|bool - Filename or false.
	 */
	private function FileGenerator( $data, $filters ) {
		$reportFormat = ( empty( $filters['report_format'] ) ? self::REPORT_HTML : self::REPORT_CSV );
		$dateStart    = ! empty( $filters['date_range']['start'] ) ? $filters['date_range']['start'] : null;
		$dateEnd      = ! empty( $filters['date_range']['end'] ) ? $filters['date_range']['end'] : null;
		if ( $reportFormat == self::REPORT_HTML ) {
			$htmlReport = new WSAL_Rep_HtmlReportGenerator();
			if ( isset( $filters['alert_codes']['alerts'] ) ) {
				$criteria = null;
				if ( ! empty( $filters['unique_ip'] ) ) {
					$criteria = 'Number & List of unique IP addresses per user';
				}
				if ( ! empty( $filters['number_logins'] ) ) {
					$criteria = 'Number of Logins per user';
				}
				if ( ! empty( $criteria ) ) {
					unset( $filters['alert_codes']['alerts'] );
					$filters['alert_codes']['alerts'][0] = $criteria;
				}
			}

			// Report Number and list of unique IP.
			if ( ! empty( $filters['unique_ip'] ) ) {
				$result = $htmlReport->GenerateUniqueIPS( $data, $this->_uploadsDirPath, $dateStart, $dateEnd );
			} else {
				$result = $htmlReport->Generate( $data, $filters, $this->_uploadsDirPath, $this->_catAlertGroups );
			}

			if ( $result === 0 ) {
				$this->_addError( __( 'There are no alerts that match your filtering criteria. Please try a different set of rules.', 'wp-security-audit-log' ) );
				$result = false;
			} elseif ( $result === 1 ) {
				$this->_addError( sprintf( __( 'Error: The <strong>%s</strong> path is not accessible.', 'wp-security-audit-log' ), $this->_uploadsDirPath ) );
				$result = false;
			}

			return $result;
		}

		$csvReport = new WSAL_Rep_CsvReportGenerator();
		// Report Number and list of unique IP.
		if ( ! empty( $filters['unique_ip'] ) ) {
			$result = $csvReport->GenerateUniqueIPS( $data, $this->_uploadsDirPath );
		} else {
			$result = $csvReport->Generate( $data, $filters, $this->_uploadsDirPath );
		}

		if ( $result === 0 ) {
			$this->_addError( __( 'There are no alerts that match your filtering criteria. Please try a different set of rules.', 'wp-security-audit-log' ) );
			$result = false;
		} elseif ( $result === 1 ) {
			$this->_addError( sprintf( __( 'Error: The <strong>%s</strong> path is not accessible.', 'wp-security-audit-log' ), $this->_uploadsDirPath ) );
			$result = false;
		}

		return $result;
	}

	/**
	 * Send periodic report.
	 *
	 * @param string $report_name - Report name.
	 * @param string $next_date - Next date of report.
	 * @param int $limit - Limit.
	 *
	 * @return string
	 */
	public function sendNowPeriodic( $report_name, $next_date = null, $limit = 100 ) {
		$report = $this->GetSettingByName( $report_name );
		if ( ! empty( $report ) ) {
			$aAlerts       = array();
			$post_types    = array();
			$post_statuses = array();
			$sites         = $report->sites;
			$type          = $report->type;
			$frequency     = $report->frequency;
			// Unique IP report.
			if ( ! empty( $report->enableUniqueIps ) ) {
				$this->SummaryReportUniqueIPS( $report_name );
				$lastDate = null;
			} else {
				$users       = ( ! empty( $report->users ) ? $report->users : array() );
				$roles       = ( ! empty( $report->roles ) ? $report->roles : array() );
				$ipAddresses = ( ! empty( $report->ipAddresses ) ? $report->ipAddresses : array() );

				if ( ! empty( $report->triggers ) ) {
					foreach ( $report->triggers as $key => $value ) {
						if ( isset( $value['alert_id'] ) && is_array( $value['alert_id'] ) ) {
							foreach ( $value['alert_id'] as $alert_id ) {
								array_push( $aAlerts, $alert_id );
							}
						} elseif ( isset( $value['alert_id'] ) ) {
							array_push( $aAlerts, $value['alert_id'] );
						}

						if ( isset( $value['post_types'] ) && is_array( $value['post_types'] ) ) {
							foreach ( $value['post_types'] as $post_type ) {
								array_push( $post_types, $post_type );
							}
						} elseif ( isset( $value['post_types'] ) ) {
							array_push( $post_types, $value['post_types'] );
						}

						if ( isset( $value['post_statuses'] ) && is_array( $value['post_statuses'] ) ) {
							foreach ( $value['post_statuses'] as $post_status ) {
								array_push( $post_statuses, $post_status );
							}
						} elseif ( isset( $value['post_statuses'] ) ) {
							array_push( $post_statuses, $value['post_statuses'] );
						}
					}
					$aAlerts   = array_unique( $aAlerts );
					$next_date = $this->BuildAttachment( $report_name, $aAlerts, $type, $frequency, $sites, $users, $roles, $ipAddresses, $next_date, $limit, $post_types, $post_statuses );
					$lastDate  = $next_date;

					if ( $lastDate == null ) {
						$this->sendSummaryEmail( $report_name, $aAlerts );
					}
				}
			}

			return $lastDate;
		}
	}

	/**
	 * Appending the report data to the content of the json file.
	 *
	 * @param string $report - Report data.
	 */
	public function generateReportJsonFile( $report ) {
		$this->_uploadsDirPath = $this->wsal->settings()->get_working_dir_path( 'reports' );
		if ( is_wp_error( $this->_uploadsDirPath ) ) {
			return;
		}

		$filename = $this->_uploadsDirPath . 'report-user' . get_current_user_id() . '.json';
		if ( file_exists( $filename ) ) {
			$data = json_decode( file_get_contents( $filename ), true );
			if ( ! empty( $data ) ) {
				if ( ! empty( $report ) ) {
					foreach ( $report['data'] as $value ) {
						array_push( $data['data'], $value );
					}
				}
				file_put_contents( $filename, json_encode( $data ) );
			}
		} else {
			if ( ! empty( $report ) ) {
				file_put_contents( $filename, json_encode( $report ) );
			}
		}
	}

	/**
	 * Generate the file on download it.
	 *
	 * @return string $download_page_url file URL
	 */
	public function downloadReportFile() {
		$download_page_url     = null;
		$this->_uploadsDirPath = $this->wsal->settings()->get_working_dir_path( 'reports', true );
		$filename              = $this->_uploadsDirPath . 'report-user' . get_current_user_id() . '.json';
		if ( file_exists( $filename ) ) {
			$data   = json_decode( file_get_contents( $filename ), true );
			$result = $this->FileGenerator( $data['data'], $data['filters'] );
			if ( ! empty( $result ) ) {
				$download_page_url = $this->buildReportDownloadUrl( $result, $data['filters']['report_format'] );
			}
		}
		@unlink( $filename );

		return $download_page_url;
	}

	/**
	 * Get alerts codes by a SINGLE group name.
	 *
	 * @param string $alertGroup - Group name.
	 *
	 * @return array codes
	 */
	public function GetCodesByGroup( $alertGroup ) {
		$_codes = array();
		$alerts = $this->wsal->alerts->GetCategorizedAlerts();
		foreach ( $alerts as $cname => $group ) {
			foreach ( $group as $subname => $_entries ) {
				if ( $subname == $alertGroup ) {
					foreach ( $_entries as $alert ) {
						array_push( $_codes, $alert->code );
					}
					break;
				}
			}
		}
		if ( empty( $_codes ) ) {
			return false;
		}

		return $_codes;
	}

	/*============================== Support Archive Database ==============================*/

	/**
	 * Create and send the report return the URL.
	 *
	 * @param array $filters - Filters.
	 *
	 * @return string $download_page_url - Group name.
	 */
	public function StatisticsUniqueIPS( $filters ) {
		$reportFormat = ( empty( $filters['report_format'] ) ? self::REPORT_HTML : self::REPORT_CSV );
		$args         = WSAL_ReportArgs::build_from_alternative_filters();

		$results               = $this->wsal->getConnector()->getAdapter( 'Occurrence' )->GetReportGrouped( $args );
		$results               = array_values( $results );
		$this->_uploadsDirPath = $this->wsal->settings()->get_working_dir_path( 'reports' );

		if ( $reportFormat == self::REPORT_HTML ) {
			$htmlReport = new WSAL_Rep_HtmlReportGenerator();
			$result     = $htmlReport->GenerateUniqueIPS( $results, $this->_uploadsDirPath, $dateStart, $dateEnd );
		} else {
			$csvReport = new WSAL_Rep_CsvReportGenerator();
			$result    = $csvReport->GenerateUniqueIPS( $results, $this->_uploadsDirPath );
		}

		if ( $result === 0 ) {
			$this->_addError( __( 'There are no alerts that match your filtering criteria. Please try a different set of rules.', 'wp-security-audit-log' ) );
			$result = false;
		} elseif ( $result === 1 ) {
			$this->_addError( sprintf( __( 'Error: The <strong>%s</strong> path is not accessible.', 'wp-security-audit-log' ), $this->_uploadsDirPath ) );
			$result = false;
		}
		$download_page_url = null;
		if ( ! empty( $result ) ) {
			$download_page_url = $this->buildReportDownloadUrl( $result, $reportFormat );
		}

		return $download_page_url;
	}

	/**
	 * Check if there is match on the report criteria.
	 *
	 * @param array $filters - Filters.
	 *
	 * @return bool value
	 */
	public function IsMatchingReportCriteria( $filters ) {
		// Filters.
		$sites         = ( empty( $filters['sites'] ) ? null : $filters['sites'] );
		$users         = ( empty( $filters['users'] ) ? null : $filters['users'] );
		$roles         = ( empty( $filters['roles'] ) ? null : $filters['roles'] );
		$ipAddresses   = ( empty( $filters['ip-addresses'] ) ? null : $filters['ip-addresses'] );
		$alertGroups   = ( empty( $filters['alert_codes']['groups'] ) ? null : $filters['alert_codes']['groups'] );
		$alertCodes    = ( empty( $filters['alert_codes']['alerts'] ) ? null : $filters['alert_codes']['alerts'] );
		$post_types    = ( empty( $filters['alert_codes']['post_types'] ) ? null : $filters['alert_codes']['post_types'] );
		$post_statuses = ( empty( $filters['alert_codes']['post_statuses'] ) ? null : $filters['alert_codes']['post_statuses'] );
		$dateStart     = ( empty( $filters['date_range']['start'] ) ? null : $filters['date_range']['start'] );
		$dateEnd       = ( empty( $filters['date_range']['end'] ) ? null : $filters['date_range']['end'] );

		$_codes = $this->GetCodesByGroups( $alertGroups, $alertCodes, false );

		$criteria['siteId']         = $sites ? "'" . implode( ',', $sites ) . "'" : 'null';
		$criteria['userId']         = $users ? "'" . implode( ',', $users ) . "'" : 'null';
		$criteria['roleName']       = 'null';
		$criteria['ipAddress']      = ! empty( $ipAddresses ) ? "'" . implode( ',', $ipAddresses ) . "'" : 'null';
		$criteria['alertCode']      = ! empty( $_codes ) ? "'" . implode( ',', $_codes ) . "'" : 'null';
		$criteria['startTimestamp'] = 'null';
		$criteria['endTimestamp']   = 'null';

		$criteria['post_types'] = 'null';
		if ( $post_types ) {
			$_post_types = array();
			foreach ( $post_types as $post_type ) {
				array_push( $_post_types, esc_sql( '(' . preg_quote( $post_type ) . ')' ) );
			}
			$criteria['post_types'] = "'" . implode( '|', $_post_types ) . "'";
		}

		$criteria['post_statuses'] = 'null';
		if ( $post_statuses ) {
			$_post_statuses = array();
			foreach ( $post_statuses as $post_status ) {
				array_push( $_post_statuses, esc_sql( '(' . preg_quote( $post_status ) . ')' ) );
			}
			$criteria['post_statuses'] = "'" . implode( '|', $_post_statuses ) . "'";
		}

		if ( $roles ) {
			$criteria['roleName'] = array();
			$_roleName            = array();
			foreach ( $roles as $k => $role ) {
				array_push( $_roleName, esc_sql( '(' . preg_quote( $role ) . ')' ) );
			}
			$criteria['roleName'] = "'" . implode( '|', $_roleName ) . "'";
		}

		if ( $dateStart ) {
			$start_datetime             = DateTime::createFromFormat( $this->_dateFormat . ' H:i:s', $dateStart . ' 00:00:00' );
			$criteria['startTimestamp'] = $start_datetime->format( 'U' );
		}

		if ( $dateEnd ) {
			$end_datetime             = DateTime::createFromFormat( $this->_dateFormat . ' H:i:s', $dateEnd . ' 23:59:59' );
			$criteria['endTimestamp'] = $end_datetime->format( 'U' );
		}

		$count = $this->wsal->getConnector()->getAdapter( 'Occurrence' )->CheckMatchReportCriteria( $criteria );

		return $count > 0;
	}

	/**
	 * Set the setting by name with the given value.
	 *
	 * @param string $option - Option name.
	 * @param mixed $value - value.
	 */
	public function AddGlobalSetting( $option, $value ) {
		$this->wsal->SetGlobalSetting( $option, $value );
	}

	/**
	 * @param int[] $user_ids List of user IDs.
	 *
	 * @return string[] List of user logins names.
	 * @since 4.3.2
	 */
	public function get_logings_for_user_ids( $user_ids ) {
		$user_logins = [];
		foreach ( $user_ids as $user_id ) {
			$user = get_user_by( 'ID', $user_id );
			if ( $user ) {
				$user_logins[] = $user->user_login;
			}
		}

		return $user_logins;
	}

	/**
	 * @param int $generatorResult Report generator result.
	 * @param int $reportFormat Report format,
	 *
	 * @return string URL to download the report.
	 * @since 4.3.2
	 */
	private function buildReportDownloadUrl($generatorResult, $reportFormat) {
		return add_query_arg( [
			'action' => 'wsal_report_download',
			'f'      => base64_encode( $generatorResult ),
			'ctype'  => $reportFormat,
			'nonce'  => wp_create_nonce( 'wpsal_reporting_nonce_action' )
		], admin_url( 'admin-ajax.php' ) );
	}
}