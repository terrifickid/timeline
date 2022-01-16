<?php
/**
 * View: Reports
 *
 * Generate reports view.
 *
 * @since 2.7.0
 * @package wsal
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WSAL_Rep_Plugin' ) ) {
	exit( 'You are not allowed to view this page.' );
}

/**
 * Class WSAL_Rep_Views_Main for the page Reporting.
 *
 * @package report-wsal
 */
class WSAL_Rep_Views_Main extends WSAL_AbstractView {

	const REPORT_LIMIT = 1000;

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
	 * Method: Constructor
	 *
	 * @param WpSecurityAuditLog $plugin - Instance of WpSecurityAuditLog.
	 * @since  1.0.0
	 */
	public function __construct( WpSecurityAuditLog $plugin ) {
		// Call to parent class.
		parent::__construct( $plugin );

		// Ajax events for the report functions.
		add_action( 'wp_ajax_AjaxGenerateReport', array( $this, 'AjaxGenerateReport' ) );
		add_action( 'wp_ajax_AjaxCheckArchiveMatch', array( $this, 'AjaxCheckArchiveMatch' ) );
		add_action( 'wp_ajax_AjaxSummaryUniqueIPs', array( $this, 'AjaxSummaryUniqueIPs' ) );
		add_action( 'wp_ajax_AjaxSendPeriodicReport', array( $this, 'AjaxSendPeriodicReport' ) );
		add_action( 'wp_ajax_set_user_autocomplete', array( $this, 'set_user_autocomplete' ) );
		add_action( 'wp_ajax_wsal_report_download', array( $this, 'process_report_download' ) );

		// Select2 ajax call.
		add_action( 'wp_ajax_AjaxGetUserID', array( $this, 'AjaxGetUserID' ) );

		// Set paths.
		$this->_base_dir = WSAL_BASE_DIR . 'extensions/reports';
		$this->_base_url = WSAL_BASE_URL . 'extensions/reports';
	}

	/**
	 * Method: Get View Title.
	 */
	public function GetTitle() {
		return __( 'Reporting', 'wp-security-audit-log' );
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
		return __( 'Reports', 'wp-security-audit-log' );
	}

	/**
	 * Method: Get View Weight.
	 */
	public function GetWeight() {
		return 10;
	}

	/**
	 * Method: Get View Header.
	 */
	public function Header() {
		wp_enqueue_style( 'wsal-rep-select2-css', $this->_base_url . '/js/select2/select2.css', array(), '3.5.1' );
		wp_enqueue_style( 'wsal-rep-select2-bootstrap-css', $this->_base_url . '/js/select2/select2-bootstrap.css', array(), '3.5.1' );
		wp_enqueue_style( 'wsal-reporting-css', $this->_base_url . '/css/styles.css', array(), filemtime( trailingslashit( $this->_base_dir ) . 'css/styles.css' ) );

		wp_enqueue_script(
			'wsal-reporting-select2-js',
			$this->_base_url . '/js/select2/select2.min.js',
			array( 'jquery' ),
			'3.5.1',
			false
		);

		WSAL_Helpers_Assets::loadDatepicker();
	}

	/**
	 * Method: Get View Footer.
	 */
	public function Footer() {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				// tab handling code
				jQuery('#wsal-tabs>a').click(function(){
					jQuery('#wsal-tabs>a').removeClass('nav-tab-active');
					jQuery('div.wsal-tab').hide();
					jQuery(jQuery(this).addClass('nav-tab-active').attr('href')).show();
				});
				// show relevant tab
				var hashlink = jQuery('#wsal-tabs>a[href="' + location.hash + '"]');
				if (hashlink.length) {
					hashlink.click();
				} else {
					jQuery('#wsal-tabs>a:first').click();
				}
				// Add required to Report email and name
				jQuery('input[name=wsal-periodic]').click(function(){
					var valid = true;
					jQuery('#wsal-notif-email').attr("required", true);
					jQuery('#wsal-notif-name').attr("required", true);
					var report_email = jQuery('#wsal-notif-email').val();
					var report_name = jQuery('#wsal-notif-name').val();

					if (!validateEmail(report_email)) {
						//The report_email is illegal
						jQuery('#wsal-notif-email').css('border-color', '#dd3d36');
						valid = false;
					} else {
						jQuery('#wsal-notif-email').css('border-color', '#aaa');
					}

					if (!report_name.match(/^[A-Za-z0-9_\s\-]{1,32}$/)) {
						//The report_name is illegal
						jQuery('#wsal-notif-name').css('border-color', '#dd3d36');
						valid = false;
					} else {
						jQuery('#wsal-notif-name').css('border-color', '#aaa');
					}
					return valid;
				});
				jQuery('input[name=wsal-reporting-submit]').click(function(){
					jQuery('#wsal-notif-email').removeAttr("required");
					jQuery('#wsal-notif-name').removeAttr("required");
				});
			});

			function validateEmail(email) {
				var atpos = email.indexOf("@");
				var dotpos = email.lastIndexOf(".");
				if (atpos<1 || dotpos<atpos+2 || dotpos+2>=email.length) {
					return false;
				} else {
					return true;
				}
			}
		</script>
		<script type="text/javascript">
			var addArchive = false;
			var nextDate = null;

			function AjaxGenerateReport(filters) {
				var limit = <?php echo self::REPORT_LIMIT; ?>;
				jQuery.ajax({
					type: 'POST',
					url: ajaxurl,
					async: true,
					dataType: 'json',
					data: {
						action: 'AjaxGenerateReport',
						filters: filters,
						nextDate: nextDate,
						limit: limit,
						addArchive: addArchive
					},
					success: function(response) {
						jQuery("#events-progress").show();
						nextDate = response[0];
						if (nextDate != 0) {
							var current = parseInt( jQuery( "#events-progress-found" ).html() );
							jQuery( "#events-progress-found" ).html( current + parseInt( response['events_found'] ) );
							AjaxGenerateReport(filters);
						} else {
							if (response[1] !== null) {
								jQuery("#ajax-response").html("<?php esc_html_e( 'Process completed.', 'wp-security-audit-log' ); ?>");
								window.setTimeout(function(){ window.location.href = response[1]; }, 300);
							} else {
								jQuery("#ajax-response").html("<?php esc_html_e( 'There are no alerts that match your filtering criteria.', 'wp-security-audit-log' ); ?>");
							}
						}
					},
					error: function(xhr, textStatus, error) {
						console.log(xhr.statusText);
						console.log(textStatus);
						console.log(error);
					}
				});
			}

			function AjaxCheckArchiveMatch(filters) {
				jQuery.ajax({
					type: 'POST',
					url: ajaxurl,
					async: false,
					dataType: 'json',
					data: {
						action: 'AjaxCheckArchiveMatch',
						filters: filters
					},
					success: function(response) {
						if (response) {
							var r = confirm('There are alerts in the archive database that match your report criteria.\nShould these alerts be included in the report?');
							if (r == true) {
								addArchive = true;
							} else {
								addArchive = false;
							}
						}
					}
				});
			}

			function AjaxSummaryUniqueIPs(filters) {
				jQuery.ajax({
					type: 'POST',
					url: ajaxurl,
					async: true,
					dataType: 'json',
					data: {
						action: 'AjaxSummaryUniqueIPs',
						filters: filters
					},
					success: function(response) {
						if (response !== null) {
							jQuery("#ajax-response").html("<p>Process completed.</p>");
							window.setTimeout(function(){ window.location.href = response; }, 300);
						} else {
							jQuery("#ajax-response").html("<p>There are no alerts that match your filtering criteria.</p>");
						}
					}
				});
			}

			function AjaxSendPeriodicReport(name) {
				var limit = <?php echo self::REPORT_LIMIT; ?>;
				jQuery.ajax({
					type: 'POST',
					url: ajaxurl,
					async: true,
					dataType: 'json',
					data: {
						action: 'AjaxSendPeriodicReport',
						name: name,
						nextDate: nextDate,
						limit: limit
					},
					success: function(response) {
						checkStatus = setInterval( function() {
							nextDate = response;
							if (nextDate != 0) {
								var dateString = nextDate;
								dateString = dateString.split(".");
								var d = new Date(dateString[0]*1000);
								jQuery("#ajax-response-counter").html(' Last day examined: '+d.toDateString()+' last day.');
								AjaxSendPeriodicReport(name);
								jQuery("#events-progress").hide();
								jQuery("#response-message").html("<p>Email sent.</p>").addClass( 'sent' );
								clearInterval( checkStatus );
							} else {
								if ( ! jQuery( '#response-message.sent' ).length ) {
									jQuery("#events-progress").hide();
									jQuery("#response-message").html("<p>No events to report.</p>");
									clearInterval( checkStatus );
								};
							}
						}, 100);
					},
					error: function(xhr, textStatus, error) {
						console.log(xhr.statusText);
						console.log(textStatus);
						console.log(error);
					}
				});
			}

			function enable_user_autocomplete( element ) {
				var autocomplete = jQuery( element ).is( ':checked' );
				var nonce = jQuery( '#wsal-user-autocomplete-nonce' ).val();
				jQuery.ajax({
					type: 'POST',
					url: ajaxurl,
					async: true,
					dataType: 'json',
					data: {
						action: 'set_user_autocomplete',
						value: autocomplete,
						nonce: nonce
					},
					success: function(response) {
						if ( response.success ) {
							window.location.reload();
						} else {
							console.log( response.message );
						}
					},
					error: function(xhr, textStatus, error) {
						console.log(xhr.statusText);
						console.log(textStatus);
						console.log(error);
					}
				});
			}
		</script>
		<?php
	}

	/**
	 * Generate report through Ajax call.
	 */
	public function AjaxGenerateReport() {
		$selected_db = get_transient( 'wsal_wp_selected_db' );
		if ( ! empty( $selected_db ) && 'archive' == $selected_db ) {
			$this->_plugin->settings()->SwitchToArchiveDB();
		}
		$filters             = $_POST['filters'];
		$filters['nextDate'] = $_POST['nextDate'];
		$filters['limit']    = $_POST['limit'];

		$report = $this->_plugin->reports_util->GenerateReport( $filters, false );

		// Append to the JSON file.
		$this->_plugin->reports_util->generateReportJsonFile( $report );

		$response[0] = ( ! empty( $report['lastDate'] ) ) ? $report['lastDate'] : 0;
		$response['events_found'] = ( isset( $report['events_found'] ) && ! empty( $report['events_found'] ) ) ? $report['events_found'] : 0;

		if ( null == $response[0] ) {
			// Switch to Archive DB.
			if ( isset( $_POST['addArchive'] ) && 'true' === $_POST['addArchive'] ) {
				if ( 'archive' != $selected_db ) {
					// First time.
					$this->_plugin->settings()->SwitchToArchiveDB();
					$filters['nextDate'] = null;
					$report              = $this->_plugin->reports_util->GenerateReport( $filters, false );
					// Append to the JSON file.
					$this->_plugin->reports_util->generateReportJsonFile( $report );
					if ( ! empty( $report['lastDate'] ) ) {
						set_transient( 'wsal_wp_selected_db', 'archive' );
						$response[0] = $report['lastDate'];
						$response['events_found'] = ( isset( $_POST['events_found'] ) && ! empty( $_POST['events_found'] ) ) ? $_POST['events_found'] : 0;
					}
				} else {
					// Last time.
					delete_transient( 'wsal_wp_selected_db' );
				}
			}

			if ( null == $response[0] ) {
				$response[1] = $this->_plugin->reports_util->downloadReportFile();
				$this->_plugin->settings()->CloseArchiveDB();
			}
		}

		echo json_encode( $response );
		exit;
	}

	/**
	 * Send the periodic report email through Ajax call.
	 */
	public function AjaxSendPeriodicReport() {
		$report_name = $_POST['name'];
		$next_date   = $_POST['nextDate'];
		$limit       = $_POST['limit'];
		$last_date   = $this->_plugin->reports_util->sendNowPeriodic( $report_name, $next_date, $limit );
		$response    = ( ! empty( $last_date ) ? $last_date : 0 );
		echo json_encode( $response );
		exit;
	}

	/**
	 * Check if the Archive is matching the filters, through Ajax call.
	 */
	public function AjaxCheckArchiveMatch() {
		$response = false;
		if ( $this->_plugin->settings()->IsArchivingEnabled() ) {
			$filters = $_POST['filters'];
			$this->_plugin->settings()->SwitchToArchiveDB();
			$response = $this->_plugin->reports_util->IsMatchingReportCriteria( $filters );
		}
		echo json_encode( $response );
		exit;
	}

	/**
	 * Generate summary unique IP report through Ajax call.
	 */
	public function AjaxSummaryUniqueIPs() {
		$response = false;
		$filters  = $_POST['filters'];
		$response = $this->_plugin->reports_util->StatisticsUniqueIPS( $filters );
		echo json_encode( $response );
		exit;
	}

	/**
	 * Add/Edit Periodic Report.
	 *
	 * @param array $post_data - Post data array.
	 */
	public function SavePeriodicReport( $post_data ) {
		if ( isset( $post_data ) ) {
			$wsalCommon      = $this->_plugin->reports_util;
			$optName         = $wsalCommon::WSAL_PR_PREFIX . strtolower( str_replace( array( ' ', '_' ), '-', $post_data['name'] ) );
			$data            = new stdClass();
			$data->title     = $post_data['name'];
			$data->email     = $post_data['email'];
			$data->type      = $post_data['report_format'];
			$data->frequency = $post_data['frequency'];
			$data->sites     = array();
			if ( ! empty( $post_data['sites'] ) ) {
				$data->sites = $post_data['sites'];
			}

			$data->sites_exluded     = array();
			if ( ! empty( $post_data['sites-exclude'] ) ) {
				$data->sites_exluded = $post_data['sites-exclude'];
			}

			if ( ! empty( $post_data['users'] ) ) {
				$data->users = $post_data['users'];
			}

			if ( ! empty( $post_data['users-exclude'] ) ) {
				$data->users_excluded = $post_data['users-exclude'];
			}

			if ( ! empty( $post_data['roles'] ) ) {
				$data->roles = $post_data['roles'];
			}

			if ( ! empty( $post_data['roles-exclude'] ) ) {
				$data->roles_excluded = $post_data['roles-exclude'];
			}

			if ( ! empty( $post_data['ip-addresses'] ) ) {
				$data->ipAddresses = $post_data['ip-addresses'];
			}

			if ( ! empty( $post_data['ip-addresses-exclude'] ) ) {
				$data->ipAddresses_excluded = $post_data['ip-addresses-exclude'];
			}

			if ( ! empty( $post_data['objects'] ) ) {
				$data->objects = $post_data['objects'];
			}

			if ( ! empty( $post_data['objects-exclude'] ) ) {
				$data->objects_excluded = $post_data['objects-exclude'];
			}

			if ( ! empty( $post_data['event-types'] ) ) {
				$data->event_types = $post_data['event-types'];
			}

			if ( ! empty( $post_data['event-types-exclude'] ) ) {
				$data->event_types_excluded = $post_data['event-types-exclude'];
			}

			$data->owner     = get_current_user_id();
			$data->dateAdded = time();
			$data->status    = 1;
			$data->viewState = array();
			$data->triggers  = array();
			if ( ! empty( $post_data['alert_codes']['alerts'] ) ) {
				$data->viewState[] = 'codes';
				$data->triggers[]  = array(
					'alert_id' => $post_data['alert_codes']['alerts'],
				);
			}
			if ( ! empty( $post_data['alert_codes']['post_types'] ) ) {
				$data->viewState[] = 'post_types';
				$data->triggers[]  = array(
					'post_types' => $post_data['alert_codes']['post_types'],
				);
			}
			if ( ! empty( $post_data['alert_codes']['post_statuses'] ) ) {
				$data->viewState[] = 'post_statuses';
				$data->triggers[]  = array(
					'post_statuses' => $post_data['alert_codes']['post_statuses'],
				);
			}
			if ( ! empty( $post_data['alert_codes']['groups'] ) ) {
				foreach ( $post_data['alert_codes']['groups'] as $key => $group ) {
					$_codes            = $this->_plugin->reports_util->GetCodesByGroup( $group );
					$data->viewState[] = $group;
					$data->triggers[]  = array(
						'alert_id' => $_codes,
					);
				}
			}
			// By Criteria
			if ( ! empty( $post_data['unique_ip'] ) ) {
				$data->viewState[]     = 'unique_ip';
				$data->triggers[]      = array(
					'alert_id' => 1000,
				);
				$data->enableUniqueIps = true;
			}
			if ( ! empty( $post_data['number_logins'] ) ) {
				$data->viewState[]        = 'number_logins';
				$data->triggers[]         = array(
					'alert_id' => 1000,
				);
				$data->enableNumberLogins = true;
			}
			$this->_plugin->reports_util->AddGlobalSetting( $optName, $data );
		}
	}

	/**
	 * Generate Statistics Report.
	 *
	 * @param array $filters
	 */
	private function generateStatisticsReport( $filters ) {
		if ( isset( $_POST['wsal-criteria'] ) ) {
			$field                      = trim( $_POST['wsal-criteria'] );
			$filters['type_statistics'] = $field;
			if ( isset( $_POST[ 'wsal-summary-field_' . $field ] ) ) {
				switch ( $field ) {
					case WSAL_Rep_Common::LOGIN_BY_USER:
						$filters['users'] = explode( ',', $_POST[ 'wsal-summary-field_' . $field ] );
						// Logins alert
						$filters['alert_codes']['alerts'] = array( 1000 );
						break;
					case WSAL_Rep_Common::LOGIN_BY_ROLE:
						$filters['roles'] = explode( ',', $_POST[ 'wsal-summary-field_' . $field ] );
						// Logins alert
						$filters['alert_codes']['alerts'] = array( 1000 );
						break;
					case WSAL_Rep_Common::VIEWS_BY_USER:
						$filters['users'] = explode( ',', $_POST[ 'wsal-summary-field_' . $field ] );
						// Viewed content alerts
						$filters['alert_codes']['alerts'] = array( 2101, 2103, 2105 );
						break;
					case WSAL_Rep_Common::VIEWS_BY_ROLE:
						$filters['roles'] = explode( ',', $_POST[ 'wsal-summary-field_' . $field ] );
						// Viewed content alerts
						$filters['alert_codes']['alerts'] = array( 2101, 2103, 2105 );
						break;
					case WSAL_Rep_Common::PUBLISHED_BY_USER:
						$filters['users'] = explode( ',', $_POST[ 'wsal-summary-field_' . $field ] );
						// Published content alerts
						$filters['alert_codes']['alerts'] = array( 2001, 2005, 2030, 9001 );
						break;
					case WSAL_Rep_Common::PUBLISHED_BY_ROLE:
						$filters['roles'] = explode( ',', $_POST[ 'wsal-summary-field_' . $field ] );
						// Published content alerts
						$filters['alert_codes']['alerts'] = array( 2001, 2005, 2030, 9001 );
						break;
					default:
						//  fallback for any other fields would go here
						break;
				}
			}
			if ( $field == WSAL_Rep_Common::DIFFERENT_IP ) {
				if ( isset( $_POST['only_login'] ) ) {
					$filters['alert_codes']['alerts'] = array( 1000 );
				} else {
					$filters['alert_codes']['groups'] = array(
						'Blog Posts',
						'Comments',
						'Custom Post Types',
						'Pages',
						'BBPress Forum',
						'WooCommerce',
						'Other User Activity',
						'User Profiles',
						'Database',
						'MultiSite',
						'Plugins & Themes',
						'System Activity',
						'Menus',
						'Widgets',
						'Site Settings',
					);
				}
			}
		}
		if ( ! empty( $_POST['wsal-from-date'] ) ) {
			$filters['date_range']['start'] = trim( $_POST['wsal-from-date'] );
		}
		if ( ! empty( $_POST['wsal-to-date'] ) ) {
			$filters['date_range']['end'] = trim( $_POST['wsal-to-date'] );
		}

		if ( isset( $_POST['include-archive'] ) ) {
			$this->_plugin->reports_util->AddGlobalSetting( 'include-archive', true );
		} else {
			$this->_plugin->reports_util->DeleteGlobalSetting( 'include-archive' );
		}
		?>
		<script type="text/javascript">
			var filters = <?php echo json_encode( $filters ); ?>;
		</script>
		<?php
		if ( ! empty( $field ) && $field == WSAL_Rep_Common::DIFFERENT_IP ) {
			?>
			<script type="text/javascript">
				jQuery(document).ready(function(){
					AjaxSummaryUniqueIPs(filters);
				});
			</script>
			<?php
		} else {
			?>
			<script type="text/javascript">
				jQuery(document).ready(function(){
					AjaxCheckArchiveMatch(filters);
					AjaxGenerateReport(filters);
				});
			</script>
			<?php
		}
		?>
		<div class="updated">
			<p id="ajax-response">
				<img src="<?php echo esc_url( $this->_base_url ); ?>/css/loading.gif">
				<?php _e( ' Generating reports. Please do not close this window.', 'wp-security-audit-log' ); ?>
				<span id="ajax-response-counter"></span>
			</p>
		</div>
		<?php
	}

	/**
	 * Method: Get View.
	 */
	public function Render() {
		if ( ! $this->_plugin->settings()->CurrentUserCan( 'edit' ) ) {
			$network_admin = get_site_option( 'admin_email' );
			$message       = esc_html__( 'To generate a report or configure automated scheduled report please contact the administrator of this multisite network on ', 'wp-security-audit-log' );
			$message      .= '<a href="mailto:' . esc_attr( $network_admin ) . '" target="_blank">' . esc_html( $network_admin ) . '</a>';
			wp_die( $message );
		}

		// Verify the uploads directory.
		$wpsalRepUploadsDir = $this->_plugin->settings()->get_working_dir_path( 'reports' );

		if ( ! is_wp_error( $wpsalRepUploadsDir ) && $this->_plugin->reports_util->CheckDirectory( $wpsalRepUploadsDir ) ) {
			$pluginDir          = realpath( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR );
			include $pluginDir . '/inc/wsal-reporting-view.inc.php';
			return;
		}

		//  skip creation this time to get the path for the prompt even if the folder cannot be created
		$wpsalRepUploadsDir = $this->_plugin->settings()->get_working_dir_path( 'reports', true );
        ?>
            <div class="error">
                <p><?php printf( __( 'The %s directory which the Reports plugin uses to create reports in was either not found or is not accessible.', 'wp-security-audit-log' ), 'uploads' ); ?></p>
                <p>
                    <?php
                    printf(
                            __( 'In order for the plugin to function, the directory %1$s must be created and the plugin should have access to write to this directory, so please configure the following permissions: 0755. If you have any questions or need further assistance please %2$s', 'wp-security-audit-log' ),
                            '<strong>' . $wpsalRepUploadsDir . '</strong>',
                            '<a href="mailto:support@wpwhitesecurity.com">contact us</a>' );
                    ?>
                </p>
            </div>
        <?php

	}

	/**
	 * Get the user id through ajax, used in 'select2'.
	 */
	public function AjaxGetUserID() {
		$data = array();
		if ( isset( $_GET['term'] ) ) {
			$user = get_user_by( 'login', trim( $_GET['term'] ) );
			if ( $user ) {
				array_push(
					$data,
					array(
						'id'   => $user->ID,
						'name' => $user->user_login,
					)
				);
			}
		}
		echo json_encode( $data );
		die();
	}

	/**
	 * Save User Autocomplete Option.
	 */
	public function set_user_autocomplete() {
		// Check permissions.
		if ( ! $this->_plugin->settings()->CurrentUserCan( 'edit' ) ) {
			echo wp_json_encode(
				array(
					'success' => false,
					'message' => esc_html__( 'You do not have sufficient permissions.', 'wp-security-audit-log' ),
				)
			);
			die();
		}

		// @codingStandardsIgnoreStart
		$nonce        = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : false;
		$autocomplete = isset( $_POST['value'] ) ? sanitize_text_field( $_POST['value'] ) : false;
		// @codingStandardsIgnoreEnd

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wsal-reports-user-autocomplete' ) ) {
			echo wp_json_encode(
				array(
					'success' => false,
					'message' => esc_html__( 'Nonce verification failed.', 'wp-security-audit-log' ),
				)
			);
			die();
		}

        if ( 'false' !== $autocomplete ) {
            $this->_plugin->SetGlobalSetting( 'reports-user-autocomplete', '1' );
        } else {
            $this->_plugin->SetGlobalSetting( 'reports-user-autocomplete', '0' );
        }

        echo wp_json_encode(
            array(
                'success' => true,
            )
        );
		die();
	}

	/**
	 * Handles AJAX call that triggers report file download.
	 *
	 * @since 4.3.2
	 */
	public function process_report_download() {
		// #! No  cache
		if ( ! headers_sent() ) {
			header( 'Expires: Mon, 26 Jul 1990 05:00:00 GMT' );
			header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
			header( 'Cache-Control: no-store, no-cache, must-revalidate' );
			header( 'Cache-Control: post-check=0, pre-check=0', false );
			header( 'Pragma: no-cache' );
		}

		$strm = '[WSAL Reporting Plugin] Requesting download';

		// Validate nonce.
		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'wpsal_reporting_nonce_action' ) ) {
			wp_die( $strm . ' with a missing or invalid nonce [code: 1000]' );
		}

		// Missing f param from url.
		if ( ! isset( $_GET['f'] ) ) {
			wp_die( $strm . ' without the "f" parameter [code: 2000]' );
		}

		// Missing ctype param from url.
		if ( ! isset( $_GET['ctype'] ) ) {
			wp_die( $strm . ' without the "ctype" parameter [code: 3000]' );
		}

		// Invalid fn provided in the url.
		$fn = base64_decode( $_GET['f'] );
		if ( false === $fn ) {
			wp_die( $strm . ' without a valid base64 encoded file name [code: 4000]' );
		}

		// Make sure this is a file we created.
		if ( ! preg_match( '/^wsal_report_/i', $fn ) ) {
			wp_die( $strm . ' with an invalid file name (' . $fn . ') [code: 5000]' );
		}

		$dir       = $this->_plugin->settings()->get_working_dir_path( 'reports', true );
		$file_path = $dir . $fn;

		// Directory traversal attacks won't work here.
		if ( preg_match( '/\.\./', $file_path ) ) {
			wp_die( $strm . ' with an invalid file name (' . $fn . ') [code: 6000]' );
		}
		if ( ! is_file( $file_path ) ) {
			wp_die( $strm . ' with an invalid file name (' . $fn . ') [code: 7000]' );
		}

		if ( intval( $_GET['ctype'] ) === WSAL_Rep_Common::REPORT_HTML ) {
			$ctype = 'text/html';
		} elseif ( intval( $_GET['ctype'] ) === WSAL_Rep_Common::REPORT_CSV ) {
			$ctype = 'application/csv';
		} else { // Content type is not valid.
			wp_die( $strm . ' with an invalid content type [code: 7000]' );
		}

		$file_size = filesize( $file_path );
		$file      = fopen( $file_path, 'rb' );

		// - turn off compression on the server - that is, if we can...
		ini_set( 'zlib.output_compression', 'Off' );
		// set the headers, prevent caching + IE fixes.
		header( 'Pragma: public' );
		header( 'Expires: -1' );
		header( 'Cache-Control: public, must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Disposition: attachment; filename="' . $fn . '"' );
		header( "Content-Length: $file_size" );
		header( "Content-Type: {$ctype}" );
		set_time_limit( 0 );
		while ( ! feof( $file ) ) {
			print( fread( $file, 1024 * 8 ) );
			ob_flush();
			flush();
			if ( connection_status() != 0 ) {
				fclose( $file );
				exit;
			}
		}
		// File save was a success.
		fclose( $file );
		exit;
	}
}
