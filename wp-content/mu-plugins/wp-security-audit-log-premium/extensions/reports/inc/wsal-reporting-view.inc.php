<?php
/**
 * View: Reports Main
 *
 * Main reports view.
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
	return;
}

// Class mapping.
/** @var WSAL_Rep_Common $wsal_common */
$wsal_common = $this->_plugin->reports_util;

// Get available roles.
$roles = $wsal_common->GetRoles();

// Get available alert categories.
$alerts = $this->_plugin->alerts->GetCategorizedAlerts();

// Get the Request method.
$rm = strtoupper( $_SERVER['REQUEST_METHOD'] );

// region >>>  PREPARE DATA FOR JS
// ## SITES
// Limit 0f 100.
$wsal_a         = WSAL_Rep_Common::GetSites( 100 );
$wsal_rep_sites = array();
foreach ( $wsal_a as $entry ) {
	// entry.blog_id, entry.domain.
	$c       = new stdClass();
	$c->id   = $entry->blog_id;
	$c->text = $entry->blogname;
	array_push( $wsal_rep_sites, $c );
}
$wsal_rep_sites = json_encode( $wsal_rep_sites );

// ## ROLES
$wp_roles = array();
foreach ( $roles as $i => $entry ) {
	// entry.blog_id, entry.domain.
	$c       = new stdClass();
	$c->id   = $i;
	$c->text = $entry;
	array_push( $wp_roles, $c );
}
$wsal_rep_roles = json_encode( $wp_roles );
// ## IPs
// limit 0f 100
$wsal_ips     = WSAL_Rep_Common::GetIPAddresses( 100 );
$wsal_rep_ips = array();
foreach ( $wsal_ips as $entry ) {
	$c       = new stdClass();
	$c->id   = $entry;
	$c->text = $entry;
	array_push( $wsal_rep_ips, $c );
}
$wsal_rep_ips = json_encode( $wsal_rep_ips );

// ## ALERT GROUPS
$_alerts = array();
foreach ( $alerts as $cname => $group ) {
	foreach ( $group as $subname => $_entries ) {
		if ( __( 'Pages', 'wp-security-audit-log' ) === $subname || __( 'Custom Post Types', 'wp-security-audit-log' ) === $subname ) {
			continue;
		}
		$_alerts[ $subname ] = $_entries;
	}
}
$ag = array();
foreach ( $_alerts as $cname => $_entries ) {
	$t           = new stdClass();
	$t->text     = $cname;
	$t->children = array();
	foreach ( $_entries as $i => $_arr_obj ) {
		$c       = new stdClass();
		$c->id   = $_arr_obj->code;
		$c->text = $c->id . ' (' . $_arr_obj->desc . ')';
		array_push( $t->children, $c );
	}
	array_push( $ag, $t );
}
$wsal_rep_alert_groups = json_encode( $ag );

// Post Types.
$post_types     = get_post_types( [], 'names' );
$post_types_arr = array();
foreach ( $post_types as $post_type ) {
	// Skip attachment post type.
	if ( 'attachment' === $post_type ) {
		continue;
	}

	$type       = new stdClass();
	$type->id   = $post_type;
	$type->text = ucfirst( $post_type );
	array_push( $post_types_arr, $type );
}
$wsal_rep_post_types = wp_json_encode( $post_types_arr );

// Post Statuses.
$post_statuses           = get_post_statuses();
$post_statuses['future'] = 'Future';
$post_status_arr         = array();
foreach ( $post_statuses as $key => $post_status ) {
	$status       = new stdClass();
	$status->id   = $key;
	$status->text = $post_status;
	array_push( $post_status_arr, $status );
}
$wsal_rep_post_statuses = wp_json_encode( $post_status_arr );

// Event objects.
$event_objects     = $this->_plugin->alerts->get_event_objects_data();
$event_objects_arr = array();

foreach ( $event_objects as $key => $event_object ) {
	$object       = new \stdClass();
	$object->id   = $key;
	$object->text = $event_object;
	array_push( $event_objects_arr, $object );
}
$wsal_rep_event_objects = wp_json_encode( $event_objects_arr );

// Event types.
$event_types     = $this->_plugin->alerts->get_event_type_data();
$event_types_arr = array();

foreach ( $event_types as $key => $event_type ) {
	$e_type       = new \stdClass();
	$e_type->id   = $key;
	$e_type->text = $event_type;
	array_push( $event_types_arr, $e_type );
}
$wsal_rep_event_types = wp_json_encode( $event_types_arr );

// Endregion >>>  PREPARE DATA FOR JS.
// The final filter array to use to filter alerts.
$filters = array(
	// Option #1 - By Site(s).
	'sites'         => array(), // By default, all sites.

	// Option #2 - By user(s).
	'users'         => array(), // By default, all users.

	// Option #3 - By Role(s).
	'roles'         => array(), // By default, all roles.

	// Option #4 - By IP Address(es).
	'ip-addresses'  => array(), // By default, all IPs.

	// Option #5 - By Alert Code(s).
	'alert_codes'   => array(
		'groups' => array(),
		'alerts' => array(),
	),

	// Option #6 - Date range.
	'date_range'    => array(
		'start' => null,
		'end'   => null,
	),

	// Option #7 - Report format (HTML || CSV).
	'report_format' => $wsal_common::REPORT_HTML,

	// By event objects.
	'objects'       => array(),

	// By event types.
	'event-types'   => array(),
);

// Get users count & users autocomplete option.
$users_count  = $wsal_common->get_users_count();
$autocomplete = $this->_plugin->GetGlobalSetting( 'reports-user-autocomplete', '0' );

if ( 'POST' == $rm && isset( $_POST['wsal_reporting_view_field'] ) ) {
	// Verify nonce.
	if ( ! wp_verify_nonce( $_POST['wsal_reporting_view_field'], 'wsal_reporting_view_action' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page - rep plugin.', 'reports-wsal' ) );
	}

	// The default error message to display if the form is not valid.
	$message_form_not_valid = __( 'Invalid Request. Please refresh the page and try again.', 'wp-security-audit-log' );

	// Inspect the form data.
	$form_data = $_POST;

	// Region >>>> By Site(s).
	if ( isset( $form_data['wsal-rb-sites'] ) ) {
		$rbs = intval( $form_data['wsal-rb-sites'] );
		if ( 1 == $rbs ) {
			/*[ already implemented in the $filters array ]*/
		} elseif ( 2 == $rbs ) {
			// The textbox must be here and have values - these will be validated later on.
			if ( ! isset( $form_data['wsal-rep-sites'] ) || empty( $form_data['wsal-rep-sites'] ) ) {
				?><div class="error"><p><?php esc_html_e( 'Error (TODO - error message): Please select SITES', 'wp-security-audit-log' ); ?></p></div>
				<?php
			} else {
				$filters['sites'] = explode( ',', $form_data['wsal-rep-sites'] );
			}
		} elseif ( 3 == $rbs ) {
			// The textbox must be here and have values - these will be validated later on.
			if ( ! isset( $form_data['wsal-rep-sites-exclude'] ) || empty( $form_data['wsal-rep-sites-exclude'] ) ) {
				?><div class="error"><p><?php esc_html_e( 'Error (TODO - error message): Please select SITES', 'wp-security-audit-log' ); ?></p></div>
				<?php
			} else {
				$filters['sites-exclude'] = explode( ',', $form_data['wsal-rep-sites-exclude'] );
			}
		}
	} else {
		?>
		<div class="error"><p><?php echo esc_html( $message_form_not_valid ); ?></p></div>
		<?php
	}
	// endregion >>>> By Site(s)
	// Region >>>> By User(s).
	if ( isset( $form_data['wsal-rb-users'] ) ) {
		$rbs = intval( $form_data['wsal-rb-users'] );
		if ( 1 == $rbs ) {
			/*[ already implemented in the $filters array ]*/
		} elseif ( 2 == $rbs ) {
			// The textbox must be here and have values - these will be validated later on.
			if ( ! isset( $form_data['wsal-rep-users'] ) || empty( $form_data['wsal-rep-users'] ) ) {
				?>
				<div class="error"><p><?php esc_html_e( 'Error (TODO - error message): Please select USERS', 'wp-security-audit-log' ); ?></p></div>
				<?php
			} else {
				if ( $users_count > 100 ) {
					if ( '0' === $autocomplete ) {
						$user_logins = sanitize_text_field( trim( $form_data['wsal-rep-users'] ) );
						$user_logins = explode( ',', $user_logins );
						$users       = array();

						if ( ! empty( $user_logins ) && is_array( $user_logins ) ) {
							foreach ( $user_logins as $user_login ) {
								// Get user data.
								$user = get_user_by( 'login', $user_login );

								if ( $user ) {
									$users[] = $user->ID;
								}
							}
						}
						$filters['users'] = $users;
					} else {
						$filters['users'] = explode( ',', $form_data['wsal-rep-users'] );
					}
				} else {
					$filters['users'] = explode( ',', $form_data['wsal-rep-users'] );
				}
			}
		} elseif ( 3 == $rbs ) {
			// The textbox must be here and have values - these will be validated later on.
			if ( ! isset( $form_data['wsal-rep-users-exclude'] ) || empty( $form_data['wsal-rep-users-exclude'] ) ) {
				?>
                <div class="error"><p><?php esc_html_e( 'Error (TODO - error message): Please select USERS', 'wp-security-audit-log' ); ?></p></div>
				<?php
			} else {
				if ( $users_count > 100 ) {
					if ( '0' === $autocomplete ) {
						$user_logins = sanitize_text_field( trim( $form_data['wsal-rep-users-exclude'] ) );
						$user_logins = explode( ',', $user_logins );
						$users       = array();

						if ( ! empty( $user_logins ) && is_array( $user_logins ) ) {
							foreach ( $user_logins as $user_login ) {
								// Get user data.
								$user = get_user_by( 'login', $user_login );

								if ( $user ) {
									$users[] = $user->ID;
								}
							}
						}
						$filters['users-exclude'] = $users;
					} else {
						$filters['users-exclude'] = explode( ',', $form_data['wsal-rep-users-exclude'] );
					}
				} else {
					$filters['users-exclude'] = explode( ',', $form_data['wsal-rep-users-exclude'] );
				}
			}
		}
	} else {
		?>
		<div class="error"><p><?php echo esc_html( $message_form_not_valid ); ?></p></div>
		<?php
	}
	// endregion >>>> By User(s)
	// Region >>>> By Role(s).
	if ( isset( $form_data['wsal-rb-roles'] ) ) {
		$rbs = intval( $form_data['wsal-rb-roles'] );
		if ( 1 == $rbs ) {
		    /*[ already implemented in the $filters array ]*/
		} elseif ( 2 == $rbs ) {
			// The textbox must be here and have values - these will be validated later on.
			if ( ! isset( $form_data['wsal-rep-roles'] ) || empty( $form_data['wsal-rep-roles'] ) ) {
				?>
				<div class="error"><p><?php esc_html_e( 'Error: Please select at least one role', 'wp-security-audit-log' ); ?></p></div>
				<?php
			} else {
				$user_roles   = explode( ',', $form_data['wsal-rep-roles'] );
				$filter_roles = array();
				if ( ! empty( $user_roles ) ) {
					global $wp_roles;
					foreach ( $user_roles as $index => $urole ) {
						$role_name = strtolower( $urole );
						// if role contains a space try convert it to valid slug.
						if ( strpos( $urole, ' ' ) ) {
							// get the role slug from the passed role nicename.
							$match = false;
							foreach ( $wp_roles->roles as $key => $single_role ) {
								if ( $urole === $single_role['name'] ) {
									$role_name = $key;
									$match     = true;
									break;
								}
							}
							if ( ! $match ) {
								// if we reached this point without a match use
								// lowercase and swap spaces to underscores.
								$role_name = str_replace( ' ', '_', $role_name );

							}
						}
						$filter_roles[] = $role_name;
					}
				}
				$filters['roles'] = $filter_roles;
			}
		} elseif ( 3 == $rbs ) {
			// The textbox must be here and have values - these will be validated later on.
			if ( ! isset( $form_data['wsal-rep-roles-exclude'] ) || empty( $form_data['wsal-rep-roles-exclude'] ) ) {
				?>
                <div class="error"><p><?php esc_html_e( 'Error: Please select at least one role', 'wp-security-audit-log' ); ?></p></div>
				<?php
			} else {
				$user_roles   = explode( ',', $form_data['wsal-rep-roles-exclude'] );
				$filter_roles = array();
				if ( ! empty( $user_roles ) ) {
					global $wp_roles;
					foreach ( $user_roles as $index => $urole ) {
						$role_name = strtolower( $urole );
						// if role contains a space try convert it to valid slug.
						if ( strpos( $urole, ' ' ) ) {
							// get the role slug from the passed role nicename.
							$match = false;
							foreach ( $wp_roles->roles as $key => $single_role ) {
								if ( $urole === $single_role['name'] ) {
									$role_name = $key;
									$match     = true;
									break;
								}
							}
							if ( ! $match ) {
								// if we reached this point without a match use
								// lowercase and swap spaces to underscores.
								$role_name = str_replace( ' ', '_', $role_name );

							}
						}
						$filter_roles[] = $role_name;
					}
				}
				$filters['roles-exclude'] = $filter_roles;
			}
		}
	} else {
		?>
		<div class="error"><p><?php echo esc_html( $message_form_not_valid ); ?></p></div>
		<?php
	}
	// endregion >>>> By Role(s)
	// Region >>>> By IP(s).gw.
	if ( isset( $form_data['wsal-rb-ip-addresses'] ) ) {
		$rbs = intval( $form_data['wsal-rb-ip-addresses'] );
		if ( 1 == $rbs ) { /*[ already implemented in the $filters array ]*/
		} elseif ( 2 == $rbs ) {
			// The textbox must be here and have values - these will be validated later on.
			if ( ! isset( $form_data['wsal-rep-ip-addresses'] ) || empty( $form_data['wsal-rep-ip-addresses'] ) ) {
				?>
				<div class="error"><p><?php esc_html_e( 'Error: Please select at least one IP address', 'wp-security-audit-log' ); ?></p></div>
				<?php
			} else {
				$filters['ip-addresses'] = explode( ',', $form_data['wsal-rep-ip-addresses'] );
			}
		} elseif ( 3 == $rbs ) {
			// The textbox must be here and have values - these will be validated later on.
			if ( ! isset( $form_data['wsal-rep-ip-addresses-exclude'] ) || empty( $form_data['wsal-rep-ip-addresses-exclude'] ) ) {
				?>
                <div class="error"><p><?php esc_html_e( 'Error: Please select at least one IP address', 'wp-security-audit-log' ); ?></p></div>
				<?php
			} else {
				$filters['ip-addresses-exclude'] = explode( ',', $form_data['wsal-rep-ip-addresses-exclude'] );
			}
		}
	} else {
		?>
		<div class="error"><p><?php echo esc_html( $message_form_not_valid ); ?></p></div>
		<?php
	}
	// endregion >>>> By IP(s)
	if ( isset( $form_data['wsal-rb-event-objects'] ) ) {
		$rbs = intval( $form_data['wsal-rb-event-objects'] );
		if ( 2 === $rbs ) {
			// The textbox must be here and have values - these will be validated later on.
			if ( ! isset( $form_data['wsal-rep-event-objects'] ) || empty( $form_data['wsal-rep-event-objects'] ) ) :
				?>
				<div class="error"><p><?php esc_html_e( 'Error: Please select at least one object', 'wp-security-audit-log' ); ?></p></div>
				<?php
			else :
				$filters['objects'] = explode( ',', $form_data['wsal-rep-event-objects'] );
			endif;
		} else if ( 3 === $rbs ) {
			// The textbox must be here and have values - these will be validated later on.
			if ( ! isset( $form_data['wsal-rep-event-objects-exclude'] ) || empty( $form_data['wsal-rep-event-objects-exclude'] ) ) :
				?>
                <div class="error"><p><?php esc_html_e( 'Error: Please select at least one object', 'wp-security-audit-log' ); ?></p></div>
			<?php
			else :
				$filters['objects-exclude'] = explode( ',', $form_data['wsal-rep-event-objects-exclude'] );
			endif;
		}
	} else {
		?>
		<div class="error"><p><?php echo esc_html( $message_form_not_valid ); ?></p></div>
		<?php
	}
	if ( isset( $form_data['wsal-rb-event-types'] ) ) {
		$rbs = intval( $form_data['wsal-rb-event-types'] );
		if ( 2 === $rbs ) {
			// The textbox must be here and have values - these will be validated later on.
			if ( ! isset( $form_data['wsal-rep-event-types'] ) || empty( $form_data['wsal-rep-event-types'] ) ) :
				?>
				<div class="error"><p><?php esc_html_e( 'Error: Please select at least one event object', 'wp-security-audit-log' ); ?></p></div>
				<?php
			else :
				$filters['event-types'] = explode( ',', $form_data['wsal-rep-event-types'] );
			endif;
		} else if ( 3 === $rbs ) {
			// The textbox must be here and have values - these will be validated later on.
			if ( ! isset( $form_data['wsal-rep-event-types-exclude'] ) || empty( $form_data['wsal-rep-event-types-exclude'] ) ) :
				?>
                <div class="error"><p><?php esc_html_e( 'Error: Please select at least one event object', 'wp-security-audit-log' ); ?></p></div>
			<?php
			else :
				$filters['event-types-exclude'] = explode( ',', $form_data['wsal-rep-event-types-exclude'] );
			endif;
		}
	} else {
		?>
		<div class="error"><p><?php echo esc_html( $message_form_not_valid ); ?></p></div>
		<?php
	}
	// Region >>>> By Alert Code(s).
	$_select_all_groups = ( isset( $form_data['wsal-rb-groups'] ) ? true : false );

	// Check alert groups.
	if ( $_select_all_groups ) {
		$filters['alert_codes']['groups'] = array_keys( $_alerts );
	} else {
		// Check for selected alert groups.
		if ( isset( $form_data['wsal-rb-alerts'] ) && ! empty( $form_data['wsal-rb-alerts'] ) ) {
			$filters['alert_codes']['groups'] = $form_data['wsal-rb-alerts'];
		}

		// Check for selected post types.
		if ( isset( $form_data['wsal-rb-post-types'] ) && isset( $form_data['wsal-rep-post-types'] ) && ! empty( $form_data['wsal-rep-post-types'] ) ) {
			// Get selected post types.
			$filters['alert_codes']['post_types'] = explode( ',', $form_data['wsal-rep-post-types'] );
		}

		// Check for selected post statuses.
		if ( isset( $form_data['wsal-rb-post-status'] ) && isset( $form_data['wsal-rep-post-status'] ) && ! empty( $form_data['wsal-rep-post-status'] ) ) {
			// Get selected post status.
			$filters['alert_codes']['post_statuses'] = explode( ',', $form_data['wsal-rep-post-status'] );
		}

		// Check for individual alerts.
		if ( isset( $form_data['wsal-rb-alert-codes'] ) && isset( $form_data['wsal-rep-alert-codes'] ) && ! empty( $form_data['wsal-rep-alert-codes'] ) ) {
			$filters['alert_codes']['alerts'] = explode( ',', $form_data['wsal-rep-alert-codes'] );
		}
	}

	// Report Number of logins.
	if ( isset( $form_data['number_logins'] ) ) {
		$filters['number_logins']         = true;
		$filters['alert_codes']['alerts'] = array( 1000 );
	}

	// Report Number and list of unique IP.
	if ( isset( $form_data['unique_ip'] ) ) {
		$filters['unique_ip']             = true;
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

	// Region >>>> By Date Range(s).
	if ( isset( $form_data['wsal-start-date'] ) ) {
		$filters['date_range']['start'] = trim( $form_data['wsal-start-date'] );
	}
	if ( isset( $form_data['wsal-end-date'] ) ) {
		$filters['date_range']['end'] = trim( $form_data['wsal-end-date'] );
	}
	// endregion >>>> By Date Range(s)
	// Region >>>> Reporting Format.
	if ( isset( $form_data['wsal-rb-report-type'] ) ) {
		if ( $form_data['wsal-rb-report-type'] == $wsal_common::REPORT_HTML ) {
			$filters['report_format'] = $wsal_common::REPORT_HTML;
		} elseif ( $form_data['wsal-rb-report-type'] == $wsal_common::REPORT_CSV ) {
			$filters['report_format'] = $wsal_common::REPORT_CSV;
		} else {
			?>
			<div class="error"><p><?php _e( 'Please select the report format.', 'wp-security-audit-log' ); ?></p></div>
			<?php
		}
	} else {
		?>
		<div class="error"><p><?php echo esc_html( $message_form_not_valid ); ?></p></div>
		<?php
	}
	// Endregion >>>> Reporting Format.
	if ( isset( $form_data['wsal-reporting-submit'] ) ) {
		// Button Generate Report Now.
		?>
		<script type="text/javascript">
			var filters = <?php echo json_encode( $filters ); ?>;
			jQuery(document).ready(function(){
				AjaxCheckArchiveMatch(filters);
				AjaxGenerateReport(filters);
			});
		</script>
		<div class="updated">
			<p id="ajax-response">
				<span id="response-message">
					<img alt="<?php esc_html_e( 'Loading', 'wp-security-audit-log' ); ?>" src="<?php echo esc_url( WSAL_BASE_URL . 'extensions/reports/css/loading.gif' ); ?>">
					<?php esc_html_e( ' Generating report. Please do not close this window.', 'wp-security-audit-log' ); ?>
					<span id="ajax-response-counter"></span>
				</span>
				<span id="events-progress">
					<?php esc_html_e( 'Searching events, ', 'wp-security-audit-log' ); ?><span id="events-progress-found">0</span><?php esc_html_e( ' currently found.', 'wp-security-audit-log' ); ?>
				</span>
			</p>
		</div>
		<?php
		/* Delete the JSON file if exist */
		$this->_uploadsDirPath = $this->_plugin->settings()->get_working_dir_path( 'reports' );
		$filename              = $this->_uploadsDirPath . 'report-user' . get_current_user_id() . '.json';
		if ( file_exists( $filename ) ) {
			@unlink( $filename );
		}
	} elseif ( isset( $form_data['wsal-periodic'] ) ) {
		// Buttons Configure Periodic Reports.
		$filters['frequency'] = $form_data['wsal-periodic'];
		if ( isset( $form_data['wsal-notif-email'] ) && isset( $form_data['wsal-notif-name'] ) ) {
			$filters['email'] = '';
			$arr_emails       = explode( ',', $form_data['wsal-notif-email'] );
			foreach ( $arr_emails as $email ) {
				$filters['email'] .= filter_var( trim( $email ), FILTER_SANITIZE_EMAIL ) . ',';
			}
			$filters['email'] = rtrim( $filters['email'], ',' );
			$filters['name']  = filter_var( trim( $form_data['wsal-notif-name'] ), FILTER_SANITIZE_STRING );
			// By Criteria.
			if ( isset( $form_data['unique_ip'] ) ) {
				$filters['unique_ip'] = true;
			}
			if ( isset( $form_data['number_logins'] ) ) {
				$filters['number_logins'] = true;
			}
			$this->SavePeriodicReport( $filters );
			?>
			<div class="updated">
				<p><?php esc_html_e( 'Periodic Report successfully saved.', 'wp-security-audit-log' ); ?></p>
			</div>
			<?php
		}
	}
}
// Send Now Periodic Report button.
if ( 'POST' == $rm && isset( $_POST['report-send-now'] ) ) {
	if ( isset( $_POST['report-name'] ) ) {
		$report_name = str_replace( WpSecurityAuditLog::OPTIONS_PREFIX, '', $_POST['report-name'] );
		?>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				AjaxSendPeriodicReport( "<?php echo $report_name; ?>" );
			});
		</script>
		<div class="updated">
			<p>
				<span id="response-message">
					<img alt="<?php esc_html_e( 'Loading', 'wp-security-audit-log' ); ?>" src="<?php echo esc_url( WSAL_BASE_URL . 'extensions/reports/css/loading.gif' ); ?>">
					<?php esc_html_e( ' Generating report. Please do not close this window.', 'wp-security-audit-log' ); ?>
					<span id="ajax-response-counter"></span>
				</span>
				<span id="events-progress">
					<?php esc_html_e( 'Searching events, ', 'wp-security-audit-log' ); ?><span id="events-progress-found">0</span><?php esc_html_e( ' currently found.', 'wp-security-audit-log' ); ?>
				</span>
			</p>
		</div>
		<?php
	}
}

// Modify Periodic Report button.
if ( 'POST' == $rm && isset( $_POST['report-modify'] ) ) {
	if ( isset( $_POST['report-name'] ) ) {
		$report_name    = str_replace( WpSecurityAuditLog::OPTIONS_PREFIX, '', $_POST['report-name'] );
		$current_report = $wsal_common->GetSettingByName( $report_name );
	}
}

// Delete Periodic Report button.
if ( 'POST' == $rm && isset( $_POST['report-delete'] ) ) {
	if ( isset( $_POST['report-name'] ) ) {
		$wsal_common->DeleteGlobalSetting( $_POST['report-name'] );
		?>
		<div class="updated">
			<p><?php esc_html_e( 'Periodic Report successfully Deleted.', 'wp-security-audit-log' ); ?></p>
		</div>
		<?php
	}
}

if ( 'POST' == $rm && isset( $_POST['wsal-statistics-submit'] ) ) {
	if ( isset( $_POST['wsal-summary-type'] ) ) {
		if ( $_POST['wsal-summary-type'] == $wsal_common::REPORT_HTML ) {
			$filters['report_format'] = $wsal_common::REPORT_HTML;
		} else {
			$filters['report_format'] = $wsal_common::REPORT_CSV;
		}
	}
	// Statistics report generator.
	$this->generateStatisticsReport( $filters );
}
?>
<style type="text/css">
	#wsal-rep-container label input[type="checkbox"]+span {
		margin-left: 3px;
	}
	#wsal-rep-container #label-xps:after {
		content: ' ';
		display:block;
		clear: both;
		margin-top: 3px;
	}
</style>
<div id="wsal-rep-container">
	<h2 id="wsal-tabs" class="nav-tab-wrapper">
		<a href="#tab-reports" class="nav-tab"><?php esc_html_e( 'Generate & Configure Periodic Reports', 'wp-security-audit-log' ); ?></a>
		<a href="#tab-summary" class="nav-tab"><?php esc_html_e( 'Statistics Reports', 'wp-security-audit-log' ); ?></a>
	</h2>
	<div class="nav-tabs">
		<div class="wsal-tab wrap" id="tab-reports">
			<p style="clear:both; margin-top: 30px"></p>
			<?php
			$allowed_tags     = array(
				'a' => array(
					'href'   => true,
					'target' => true,
				),
			);
			$description_text = sprintf(
				'Refer to the %1$sgetting started with WordPress reports%2$s for detailed information on how to generate reports.',
				'<a href="https://wpactivitylog.com/support/kb/getting-started-reports-wordpress/" target="_blank">',
				'</a>'
			);
			?>
			<p><?php echo wp_kses( $description_text, $allowed_tags ); ?></p>
			<form id="wsal-rep-form" action="<?php echo esc_url( $this->GetUrl() ); ?>" method="post">
				<h4><?php esc_html_e( 'Generate a report', 'wp-security-audit-log' ); ?></h4>

				<!-- SECTION #1 -->
				<h4 class="wsal-reporting-subheading"><?php esc_html_e( 'Step 1: Select the type of report', 'wp-security-audit-log' ); ?></h4>

				<div class="wsal-rep-form-wrapper">

					<!--// BY SITE -->
					<div class="wsal-rep-section">
						<label class="wsal-rep-label-fl"><?php esc_html_e( 'By Site(s)', 'wp-security-audit-log' ); ?></label>
						<div class="wsal-rep-section-fl">
							<p class="wsal-rep-clear">
								<input type="radio" name="wsal-rb-sites" id="wsal-rb-sites-1" value="1" checked="checked" />
								<label for="wsal-rb-sites-1"><?php esc_html_e( 'All Sites', 'wp-security-audit-log' ); ?></label>
							</p>
							<p class="wsal-rep-clear">
								<input type="radio" name="wsal-rb-sites" id="wsal-rb-sites-2" value="2"/>
								<label for="wsal-rb-sites-2"><?php esc_html_e( 'These specific sites', 'wp-security-audit-log' ); ?></label>
								<input type="hidden" name="wsal-rep-sites" id="wsal-rep-sites" class="js-wsal-rep-sites" />
							</p>
                            <p class="wsal-rep-clear">
                                <input type="radio" name="wsal-rb-sites" id="wsal-rb-sites-3" value="3"/>
                                <label for="wsal-rb-sites-3"><?php esc_html_e( 'All sites except these', 'wp-security-audit-log' ); ?></label>
                                <input type="hidden" name="wsal-rep-sites-exclude" id="wsal-rep-sites-exclude" class="js-wsal-rep-sites" />
                            </p>
						</div>
					</div>

                    <?php
                    $use_autocomplete_for_users = $users_count <= 100 || ( $users_count > 100 && '1' === $autocomplete );
                    $user_logins = array();
                    $user_logins_excluded = array();
                    if ( $use_autocomplete_for_users && ! empty( $current_report ) ) {
	                    if ( property_exists( $current_report, 'users' ) ) {
		                    $user_logins = $wsal_common->get_logings_for_user_ids( $current_report->users );
	                    }
	                    if ( property_exists( $current_report, 'users_excluded' ) ) {
		                    $user_logins_excluded = $wsal_common->get_logings_for_user_ids( $current_report->users_excluded );
	                    }
                    }
                    ?>
					<!--// BY USER -->
					<div class="wsal-rep-section">
						<label class="wsal-rep-label-fl"><?php esc_html_e( 'By User(s)', 'wp-security-audit-log' ); ?></label>
						<div class="wsal-rep-section-fl wsal-rep-section-users">
							<p class="wsal-rep-clear">
								<input type="radio" name="wsal-rb-users" id="wsal-rb-users-1" value="1" checked="checked" />
								<label for="wsal-rb-users-1"><?php esc_html_e( 'All Users', 'wp-security-audit-log' ); ?></label>
							</p>
							<p class="wsal-rep-clear">
								<input type="radio" name="wsal-rb-users" id="wsal-rb-users-2" value="2" />
								<label for="wsal-rb-users-2"><?php esc_html_e( 'These specific users', 'wp-security-audit-log' ); ?></label>
								<?php if ( $use_autocomplete_for_users ) : ?>
									<input type="hidden" name="wsal-rep-users" id="wsal-rep-users" class="js-wsal-rep-users" />
                                <?php else: ?>
									<input type="text" name="wsal-rep-users" id="wsal-rep-users" class="wsal-rep-text-field" value="<?php echo esc_attr( implode( ',', $user_logins ) ); ?>" />
								<?php endif; ?>
							</p>
                            <p class="wsal-rep-clear">
                                <input type="radio" name="wsal-rb-users" id="wsal-rb-users-3" value="3" />
                                <label for="wsal-rb-users-3"><?php esc_html_e( 'All users except these', 'wp-security-audit-log' ); ?></label>
								<?php if ( $use_autocomplete_for_users ) : ?>
                                    <input type="hidden" name="wsal-rep-users-exclude" id="wsal-rep-users-exclude" class="js-wsal-rep-users" />
								<?php else: ?>
                                    <input type="text" name="wsal-rep-users-exclude" id="wsal-rep-users-exclude" class="wsal-rep-text-field" value="<?php echo esc_attr( implode( ',', $user_logins_excluded ) ); ?>" />
								<?php endif; ?>
                            </p>
							<?php if ( $users_count > 100 ) : ?>
								<div class="wsal-rep-clear">
									<p>
										<?php esc_html_e( 'Automated verification of usernames is disabled because you have more than 100 users. The process might require a lot of resources to work with a lot of users. Tick the option below to enable it again.', 'wp-security-audit-log' ); ?>
									</p>
									<label for="wsal-enable-user-autocomplete">
										<input type="checkbox" name="wsal-enable-user-autocomplete" id="wsal-enable-user-autocomplete" value="1" onchange="enable_user_autocomplete(this)" <?php checked( $autocomplete, '1' ); ?> />
										<input type="hidden" id="wsal-user-autocomplete-nonce" value="<?php echo esc_attr( wp_create_nonce( 'wsal-reports-user-autocomplete' ) ); ?>" />
										<?php esc_html_e( 'Enable Autocomplete', 'wp-security-audit-log' ); ?>
									</label>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<!--// BY ROLE -->
					<div class="wsal-rep-section">
						<label class="wsal-rep-label-fl"><?php esc_html_e( 'By Role(s)', 'wp-security-audit-log' ); ?></label>
						<div class="wsal-rep-section-fl">
							<p class="wsal-rep-clear">
								<input type="radio" name="wsal-rb-roles" id="wsal-rb-roles-1" value="1" checked="checked" />
								<label for="wsal-rb-roles-1"><?php esc_html_e( 'All Roles', 'wp-security-audit-log' ); ?></label>
							</p>
							<p class="wsal-rep-clear">
								<input type="radio" name="wsal-rb-roles" id="wsal-rb-roles-2" value="2"/>
								<label for="wsal-rb-roles-2"><?php esc_html_e( 'These specific roles', 'wp-security-audit-log' ); ?></label>
								<input type="hidden" name="wsal-rep-roles" id="wsal-rep-roles" class="js-wsal-rep-roles" />
							</p>
                            <p class="wsal-rep-clear">
                                <input type="radio" name="wsal-rb-roles" id="wsal-rb-roles-3" value="3"/>
                                <label for="wsal-rb-roles-3"><?php esc_html_e( 'All roles except these', 'wp-security-audit-log' ); ?></label>
                                <input type="hidden" name="wsal-rep-roles-exclude" id="wsal-rep-roles-exclude" class="js-wsal-rep-roles" />
                            </p>
						</div>
					</div>

					<!--// BY IP ADDRESS -->
					<div class="wsal-rep-section">
						<label class="wsal-rep-label-fl"><?php esc_html_e( 'By IP Address(es)', 'wp-security-audit-log' ); ?></label>
						<div class="wsal-rep-section-fl">
							<p class="wsal-rep-clear">
								<input type="radio" name="wsal-rb-ip-addresses" id="wsal-rb-ip-addresses-1" value="1" checked="checked" />
								<label for="wsal-rb-ip-addresses-1"><?php esc_html_e( 'All IP Addresses', 'wp-security-audit-log' ); ?></label>
							</p>
							<p class="wsal-rep-clear">
								<input type="radio" name="wsal-rb-ip-addresses" id="wsal-rb-ip-addresses-2" value="2"/>
								<label for="wsal-rb-ip-addresses-2"><?php esc_html_e( 'These specific IP addresses', 'wp-security-audit-log' ); ?></label>
								<input type="hidden" name="wsal-rep-ip-addresses" id="wsal-rep-ip-addresses" class="js-wsal-rep-ip-addresses"/>
							</p>
                            <p class="wsal-rep-clear">
                                <input type="radio" name="wsal-rb-ip-addresses" id="wsal-rb-ip-addresses-3" value="3"/>
                                <label for="wsal-rb-ip-addresses-3"><?php esc_html_e( 'All IP addresses except these', 'wp-security-audit-log' ); ?></label>
                                <input type="hidden" name="wsal-rep-ip-addresses-exclude" id="wsal-rep-ip-addresses-exclude" class="js-wsal-rep-ip-addresses" />
                            </p>
						</div>
					</div>

					<!--// BY OBJECT -->
					<div class="wsal-rep-section">
						<label class="wsal-rep-label-fl"><?php esc_html_e( 'By Object(s)', 'wp-security-audit-log' ); ?></label>
						<div class="wsal-rep-section-fl">
							<p class="wsal-rep-clear">
								<input type="radio" name="wsal-rb-event-objects" id="wsal-rb-event-objects-1" value="1" checked="checked" />
								<label for="wsal-rb-event-objects-1"><?php esc_html_e( 'All Objects', 'wp-security-audit-log' ); ?></label>
							</p>
							<p class="wsal-rep-clear">
								<input type="radio" name="wsal-rb-event-objects" id="wsal-rb-event-objects-2" value="2"/>
								<label for="wsal-rb-event-objects-2"><?php esc_html_e( 'These specific objects', 'wp-security-audit-log' ); ?></label>
								<input type="hidden" name="wsal-rep-event-objects" id="wsal-rep-event-objects" class="js-wsal-rep-objects" />
							</p>
                            <p class="wsal-rep-clear">
                                <input type="radio" name="wsal-rb-event-objects" id="wsal-rb-event-objects-3" value="3" />
                                <label for="wsal-rb-event-objects-3"><?php esc_html_e( 'All objects except these', 'wp-security-audit-log' ); ?></label>
                                <input type="hidden" name="wsal-rep-event-objects-exclude" id="wsal-rep-event-objects-exclude" class="js-wsal-rep-objects" />
                            </p>
						</div>
					</div>

					<!--// BY EVENT TYPE -->
					<div class="wsal-rep-section">
						<label class="wsal-rep-label-fl"><?php esc_html_e( 'By Event Type(s)', 'wp-security-audit-log' ); ?></label>
						<div class="wsal-rep-section-fl">
							<p class="wsal-rep-clear">
								<input type="radio" name="wsal-rb-event-types" id="wsal-rb-event-types-1" value="1" checked="checked" />
								<label for="wsal-rb-event-types-1"><?php esc_html_e( 'All Event Types', 'wp-security-audit-log' ); ?></label>
							</p>
							<p class="wsal-rep-clear">
								<input type="radio" name="wsal-rb-event-types" id="wsal-rb-event-types-2" value="2"/>
								<label for="wsal-rb-event-types-2"><?php esc_html_e( 'These specific event types', 'wp-security-audit-log' ); ?></label>
								<input type="hidden" name="wsal-rep-event-types" id="wsal-rep-event-types" class="js-wsal-rep-event-types" />
							</p>
                            <p class="wsal-rep-clear">
                                <input type="radio" name="wsal-rb-event-types" id="wsal-rb-event-types-3" value="3"/>
                                <label for="wsal-rb-event-types-3"><?php esc_html_e( 'All event types except these', 'wp-security-audit-log' ); ?></label>
                                <input type="hidden" name="wsal-rep-event-types-exclude" id="wsal-rep-event-types-exclude" class="js-wsal-rep-event-types" />
                            </p>
						</div>
					</div>

					<!--// BY ALERT GROUPS/CODE -->
					<div class="wsal-rep-section">
						<label class="wsal-rep-label-fl"><?php esc_html_e( 'By Event Code(s)', 'wp-security-audit-log' ); ?></label>
						<div class="wsal-rep-section-fl">
							<p id="wsal-rep-js-groups" class="wsal-rep-clear">
								<?php
								$checked = array();
								if ( ! empty( $current_report ) ) {
									$checked = $current_report->viewState;
								}
								?>
								<!-- Select All -->
								<label for="wsal-rb-groups" class="wsal-rep-clear" id="label-xps">
									<input type="radio" name="wsal-rb-groups" id="wsal-rb-groups" value="0" <?php echo ( empty( $current_report ) || count( $checked ) == 15 ) ? ' checked="checked"' : false; ?> />
									<span style="margin-left: 0"><?php esc_html_e( 'Select All', 'wp-security-audit-log' ); ?></span>
								</label>
								<!-- / Select All -->

								<?php
								if ( empty( $_alerts ) ) {
									echo '<span>' . esc_html__( 'No alerts were found', 'reports-wsal' ) . '</span>';
								} else {
									$arr_alerts = array_keys( $_alerts );
									foreach ( $arr_alerts as $i => $alert ) {
										$id    = 'wsal-rb-alert-' . $i;
										$class = 'wsal-rb-alert-' . str_replace( ' ', '-', strtolower( $alert ) );
										echo '<label for="' . esc_attr( $id ) . '" class="wsal-rep-clear ' . esc_attr( $class ) . '">';
										echo '<input type="checkbox" name="wsal-rb-alerts[]" id="' . esc_attr( $id ) . '" class="wsal-js-groups"';
										if ( in_array( $alert, $checked ) && count( $checked ) < 15 ) {
											echo ' checked';
										}
										echo ' value="' . esc_attr( $alert ) . '"/>';

										if ( 'content' === strtolower( $alert ) ) :
											echo '<span>' . esc_html__( 'Posts', 'wp-security-audit-log' ) . '</span>';
											echo '</label>';
											?>
											<!-- Post Types -->
											<label for="wsal-rb-post-types" class="wsal-rep-clear" id="label-cpts">
												<input type="checkbox" name="wsal-rb-post-types" id="wsal-rb-post-types" class="wsal-js-groups" />
												<?php esc_html_e( 'Post Type', 'wp-security-audit-log' ); ?>
												<input type="hidden" name="wsal-rep-post-types" id="wsal-rep-post-types"/>
											</label>
											<!-- / Post Types -->

											<!-- Post Statuses -->
											<label for="wsal-rb-post-status" class="wsal-rep-clear" id="label-statuses">
												<input type="checkbox" name="wsal-rb-post-status" id="wsal-rb-post-status" class="wsal-js-groups" />
												<?php esc_html_e( 'Post Status', 'wp-security-audit-log' ); ?>
												<input type="hidden" name="wsal-rep-post-status" id="wsal-rep-post-status"/>
											</label>
											<!-- / Post Statuses -->
											<?php
										else :
											echo '<span>' . esc_html( $alert ) . '</span>';
											echo '</label>';
										endif;
										$i++;
									}
								}
								?>
								<input type="checkbox" name="wsal-rb-alert-codes" id="wsal-rb-alert-codes-1"/>
								<label for="wsal-rb-alert-codes-1"><?php esc_html_e( 'Specify Event Codes', 'wp-security-audit-log' ); ?></label>
								<input type="hidden" name="wsal-rep-alert-codes" id="wsal-rep-alert-codes"/>
							</p>
						</div>
					</div>

					<!--// By the Below Criteria -->
					<div class="wsal-rep-section">
						<label class="wsal-rep-label-fl"><?php _e( 'By the Below Criteria', 'wp-security-audit-log' ); ?></label>
						<div class="wsal-rep-section-fl">
							<p class="wsal-rep-clear">
								<label for="unique_ip">
									<input type="checkbox" name="unique_ip" id="unique_ip" class="wsal-criteria" <?php echo ( in_array( 'unique_ip', $checked ) ) ? 'checked' : false; ?> value="1" />
									<span><?php _e( 'Number & List of unique IP addresses per user', 'wp-security-audit-log' ); ?></span>
								</label>
								<br/>
								<label for="number_logins">
									<input type="checkbox" name="number_logins" id="number_logins" class="wsal-criteria" <?php echo ( in_array( 'number_logins', $checked ) ) ? 'checked' : false; ?> value="1" />
									<span><?php _e( 'Number of Logins per user', 'wp-security-audit-log' ); ?></span>
								</label>
								<br/>
							</p>
						</div>
					</div>
				</div>
				<script id="wpsal_rep_s2" type="text/javascript">
					jQuery( document ).ready( function( $ ) {

						// Toggle Post Type and Post Status.
						var content_filter_toggle = function() {
							var cpt_filter = $( '#label-cpts' );
							var status_filter = $( '#label-statuses' );

							cpt_filter.hide();
							status_filter.hide();

							if ( $( '.wsal-rb-alert-content input' ).is( ':checked' ) ) {
								cpt_filter.show();
								status_filter.show();
							}
						}
						content_filter_toggle();

						// Toggle post type and status filters visibility.
						$( '.wsal-rb-alert-content input' ).on( 'change', function() {
							content_filter_toggle();
						} );

						// Alert groups
						var wsalAlertGroups = $( '.wsal-js-groups');

						$(".js-wsal-rep-sites").select2({
							data: JSON.parse('<?php echo $wsal_rep_sites; ?>'),
							placeholder: "<?php esc_html_e( 'Select site(s)', 'wp-security-audit-log' ); ?>",
							minimumResultsForSearch: 10,
							multiple: true
						}).on('select2-open',function(e){
							var v = $(this).val();
							if(!v.length){
                              $(e.target).siblings('input[type="radio"]').prop('checked', true);
							}
						}).on('select2-removed', function(){
							var v = $(this).val();
							if(!v.length){
								$( '#wsal-rb-sites-1').prop('checked',true);
							}
						}).on('select2-close', function(){
							var v = $(this).val();
							if(!v.length){
								$( '#wsal-rb-sites-1').prop('checked',true);
							}
						});

						<?php if ( $users_count <= 100 || '1' === $autocomplete ) : ?>
							$(".js-wsal-rep-users").select2({
								placeholder: "<?php esc_html_e( 'Select user(s)', 'wp-security-audit-log' ); ?>",
								multiple: true,
								ajax: {
									url: ajaxurl + '?action=AjaxGetUserID',
									dataType: 'json',
									type: "GET",
									data: function (term) {
										return {
											term: term
										};
									},
									results: function (data) {
										return {
											results: $.map(data, function (item) {
												return {
													text: item.name,
													id: item.id
												}
											})
										};
									}
								}
							}).on('select2-open',function(e){
								var v = $(this).val();
								if(!v.length){
									$(e.target).siblings('input[type="radio"]').prop('checked', true);
								}
							}).on('select2-removed', function(){
								var v = $(this).val();
								if(!v.length){
									$( '#wsal-rb-users-1').prop('checked',true);
								}
							}).on('select2-close', function(){
								var v = $(this).val();
								if(!v.length){
									$( '#wsal-rb-users-1').prop('checked',true);
								}
							});
						<?php endif; ?>

						$(".js-wsal-rep-roles").select2({
							data: JSON.parse('<?php echo $wsal_rep_roles; ?>'),
							placeholder: "<?php esc_html_e( 'Select role(s)', 'wp-security-audit-log' ); ?>",
							minimumResultsForSearch: 10,
							multiple: true
						}).on('select2-open',function(e){
							var v = $(this).val();
							if(!v.length){
                              $(e.target).siblings('input[type="radio"]').prop('checked', true);
							}
						}).on('select2-removed', function(){
							var v = $(this).val();
							if(!v.length){
								$( '#wsal-rb-roles-1').prop('checked',true);
							}
						}).on('select2-close', function(){
							var v = $(this).val();
							if(!v.length){
								$( '#wsal-rb-roles-1').prop('checked',true);
							}
						});

						$(".js-wsal-rep-ip-addresses").select2({
							data: JSON.parse('<?php echo $wsal_rep_ips; ?>'),
							placeholder: "<?php esc_html_e( 'Select IP address(es)', 'wp-security-audit-log' ); ?>",
							minimumResultsForSearch: 10,
							multiple: true
						}).on('select2-open',function(e){
							var v = $(this).val();
							if(!v.length){
                              $(e.target).siblings('input[type="radio"]').prop('checked', true);
							}
						}).on('select2-removed', function(){
							var v = $(this).val();
							if(!v.length){
								$( '#wsal-rb-ip-addresses-1').prop('checked',true);
							}
						}).on('select2-close', function(){
							var v = $(this).val();
							if(!v.length){
								$( '#wsal-rb-ip-addresses-1').prop('checked',true);
							}
						});

						$( '.js-wsal-rep-objects').select2({
							data: JSON.parse('<?php echo $wsal_rep_event_objects; ?>'),
							placeholder: "<?php esc_html_e( 'Select Objects(s)', 'wp-security-audit-log' ); ?>",
							minimumResultsForSearch: 10,
							multiple: true
						}).on('select2-open',function(e){
							var v = $(this).val();
							if(!v.length){
                              $(e.target).siblings('input[type="radio"]').prop('checked', true);
							}
						}).on('select2-removed', function(){
							var v = $(this).val();
							if(!v.length){
								$( '#wsal-rb-event-objects-1').prop('checked',true);
							}
						}).on('select2-close', function(){
							var v = $(this).val();
							if(!v.length){
								$( '#wsal-rb-event-objects-1').prop('checked',true);
							}
						});

						$( '.js-wsal-rep-event-types').select2({
							data: JSON.parse('<?php echo $wsal_rep_event_types; ?>'),
							placeholder: "<?php esc_html_e( 'Select Type(s)', 'wp-security-audit-log' ); ?>",
							minimumResultsForSearch: 10,
							multiple: true
						}).on('select2-open',function(e){
							var v = $(this).val();
							if(!v.length){
                              $(e.target).siblings('input[type="radio"]').prop('checked', true);
							}
						}).on('select2-removed', function(){
							var v = $(this).val();
							if(!v.length){
								$( '#wsal-rb-event-types-1').prop('checked',true);
							}
						}).on('select2-close', function(){
							var v = $(this).val();
							if(!v.length){
								$( '#wsal-rb-event-types-1').prop('checked',true);
							}
						});

						$("#wsal-rep-alert-codes").select2({
							data: <?php echo $wsal_rep_alert_groups; ?>,
							placeholder: "<?php esc_html_e( 'Select Event Code(s)', 'wp-security-audit-log' ); ?>",
							minimumResultsForSearch: 10,
							multiple: true,
							width: '500px'
						}).on('select2-open', function(e){
							var v = $(e).val;
							if(v.length){
								$( '#wsal-rb-alert-codes-1').prop('checked', true);
								$( '#wsal-rb-groups').prop('checked', false);
							}
						}).on('select2-selecting', function(e){
							var v = $(e).val;
							if(v.length){
								$( '#wsal-rb-alert-codes-1').prop('checked', true);
								$( '#wsal-rb-groups').prop('checked', false);
							}
						}).on('select2-removed', function(e){
							var v = $(this).val();
							if(!v.length){
								$( '#wsal-rb-alert-codes-1').prop('checked', false);
								// if none is checked, check the Select All input
								var checked = $( '.wsal-js-groups:checked');
								if(!checked.length){
									$( '#wsal-rb-groups').prop('checked', true);
								}
							}
						});

						$( '#wsal-rep-post-types' ).select2( {
							data: <?php echo $wsal_rep_post_types; ?>,
							placeholder: "<?php esc_html_e( 'Select Post Type(s)', 'wp-security-audit-log' ); ?>",
							minimumResultsForSearch: 10,
							multiple: true,
							width: '500px',
						} ).on( 'select2-open', function( e ) {
							var v = $(e).val;
							if ( v.length ) {
								$( '#wsal-rb-post-types').prop('checked', true);
								$( '#wsal-rb-groups').prop('checked', false);
							}
						} ).on( 'select2-selecting', function( e ) {
							var v = $(e).val;
							if(v.length){
								$( '#wsal-rb-post-types').prop('checked', true);
								$( '#wsal-rb-groups').prop('checked', false);
							}
						} ).on( 'select2-removed', function( e ) {
							var v = $(this).val();
							if(!v.length){
								$( '#wsal-rb-post-types').prop('checked', false);
								// if none is checked, check the Select All input
								var checked = $( '.wsal-js-groups:checked');
								if(!checked.length){
									$( '#wsal-rb-groups').prop('checked', true);
								}
							}
						} );

						$( '#wsal-rep-post-status' ).select2( {
							data: <?php echo $wsal_rep_post_statuses; ?>,
							placeholder: "<?php esc_html_e( 'Select Post Status(es)', 'wp-security-audit-log' ); ?>",
							minimumResultsForSearch: 10,
							multiple: true,
							width: '500px',
						} ).on( 'select2-open', function( e ) {
							var v = $(e).val;
							if ( v.length ) {
								$( '#wsal-rb-post-status').prop('checked', true);
								$( '#wsal-rb-groups').prop('checked', false);
							}
						} ).on( 'select2-selecting', function( e ) {
							var v = $(e).val;
							if(v.length){
								$( '#wsal-rb-post-status').prop('checked', true);
								$( '#wsal-rb-groups').prop('checked', false);
							}
						} ).on( 'select2-removed', function( e ) {
							var v = $(this).val();
							if(!v.length){
								$( '#wsal-rb-post-status').prop('checked', false);
								// if none is checked, check the Select All input
								var checked = $( '.wsal-js-groups:checked');
								if(!checked.length){
									$( '#wsal-rb-groups').prop('checked', true);
								}
							}
						} );

						function _deselectGroups(){
							wsalAlertGroups.each(function(){
								$(this).prop('checked', false);
							});
						}
						$( '#wsal-rb-groups').on('change', function(){
							if ($(this).is(':checked')) {
								// deselect all
								_deselectGroups();
								// Deselect the alert codes checkbox if selected and no alert codes are provided.
								if ( $( '#wsal-rb-alert-codes-1' ).is( ':checked' ) ) {
									if ( ! $( '#wsal-rep-alert-codes' ).val().length ) {
										$( '#wsal-rb-alert-codes-1' ).prop( 'checked', false );
									}
								}
								if ( $( '#wsal-rb-post-types' ).is( ':checked' ) ) {
									if ( ! $( '#wsal-rep-post-types' ).val().length ) {
										$( '#wsal-rb-post-types' ).prop( 'checked', false );
									}
								}

								if ( $( '#wsal-rb-post-status' ).is( ':checked' ) ) {
									if ( ! $( '#wsal-rep-post-status' ).val().length ) {
										$( '#wsal-rb-post-status' ).prop( 'checked', false );
									}
								}
							} else {
								$(this).prop('checked', false);
								// select first
								$( '.wsal-js-groups').get(0).prop('checked', true);
							}
						});
						$( '#wsal-rb-alert-codes-1').on('change', function(){
							if ($(this).prop('checked') == true) {
								$( '#wsal-rb-groups').prop('checked', false);
							} else {
								// if none is checked, check the Select All input
								var checked = $( '.wsal-js-groups:checked');
								if(!checked.length){
									$( '#wsal-rb-groups').prop('checked', true);
								}
							}
						});
						$( '#wsal-rb-post-types' ).on( 'change', function() {
							if ( $( this ).prop( 'checked' ) == true ) {
								$( '#wsal-rb-groups' ).prop( 'checked', false );
							} else {
								// If none is checked, check the Select All input.
								var checked = $( '.wsal-js-groups:checked' );
								if ( ! checked.length ) {
									$( '#wsal-rb-groups' ).prop( 'checked', true );
								}
							}
						} );
						$( '#wsal-rb-post-status' ).on( 'change', function() {
							if ( $( this ).prop( 'checked' ) == true ) {
								$( '#wsal-rb-groups' ).prop( 'checked', false );
							} else {
								// If none is checked, check the Select All input.
								var checked = $( '.wsal-js-groups:checked' );
								if ( ! checked.length ) {
									$( '#wsal-rb-groups' ).prop( 'checked', true );
								}
							}
						} );
						wsalAlertGroups.on( 'change', function() {
							if ( $( this ).is( ':checked' ) ) {
								$( '#wsal-rb-groups' ).prop( 'checked', false );
							} else {
								// If none is checked, check the Select All input.
								var checked = $( '.wsal-js-groups:checked' );
								var post_type_check = $( '#wsal-rb-post-types:checked' );
								var post_status_check = $( '#wsal-rb-post-status:checked' );
								if ( ! checked.length && ! post_type_check && ! post_status_check ) {
									$( '#wsal-rb-groups' ).prop( 'checked', true );
									var e = $( "#wsal-rep-alert-codes" ).select2( 'val' );
									var post_types = $( '#wsal-rep-post-types' ).select2( 'val' );
									var post_status = $( '#wsal-rep-post-status' ).select2( 'val' );
									if ( ! e.length ) {
										$( '#wsal-rb-alert-codes-1' ).prop( 'checked', false );
									}
									if ( ! post_types.length ) {
										$( '#wsal-rb-post-types' ).prop( 'checked', false );
									}
									if ( ! post_status.length ) {
										$( '#wsal-rb-post-status' ).prop( 'checked', false );
									}
								}
							}
						});
						// Validation date format
						$( '.date-range').on('change', function(){
							if (wsal_CheckDate($(this).val())) {
								jQuery(this).css('border-color', '#aaa');
							} else {
								jQuery(this).css('border-color', '#dd3d36');
							}
						});
						// Criteria disables all the alert codes
						function _disableGroups(){
							var checked = $( '.wsal-criteria:checked');
							if(checked.length){
								$( '#wsal-rep-js-groups').find('input').each(function(){
									$(this).attr('disabled', true);
								});
							} else {
								$( '#wsal-rep-js-groups').find('input').each(function(){
									$(this).attr('disabled', false);
								});
							}
						}

						_disableGroups();
						// By Criteria changes
						$( '.wsal-criteria').on('change', function(){
							if ($(this).is(':checked')) {
								$( '#wsal-rb-groups').prop('checked', false);
								// deselect all
								_deselectGroups();
							}
							_disableGroups();
							// Allows to select only one
							$( 'input[type="checkbox"]').not(this).prop('checked', false);
						});

						<?php
						// Set the the values for the Select2.
						if ( ! empty( $current_report ) && ! empty( $current_report->sites ) ) {
							$sSites = '[';
							foreach ( $current_report->sites as $site ) {
								$sSites .= $site . ',';
							}
							$sSites  = rtrim( $sSites, ',' );
							$sSites .= ']';
							?>
							$("#wsal-rep-sites").select2("val", <?php echo $sSites; ?>);
							$( '#wsal-rb-sites-2').prop('checked', true);
							<?php
						}
						if ( ! empty( $current_report ) && ! empty( $current_report->users ) ) {
							$sUsers = '[';
							foreach ( $current_report->users as $user_id ) {
								$user = get_user_by( 'ID', $user_id );
								if ( $user ) {
									$sUsers .= '{id: ' . $user->ID . ', text: "' . $user->user_login . '"},';
								}
							}
							$sUsers  = rtrim( $sUsers, ',' );
							$sUsers .= ']';
							?>
							$("#wsal-rep-users").select2('data', <?php echo $sUsers; ?>);
							$( '#wsal-rb-users-2').prop('checked', true);
							<?php
						}
						if ( ! empty( $current_report ) && ! empty( $current_report->roles ) ) {
							$sRoles = '[';
							foreach ( $current_report->roles as $role ) {
								$sRoles .= '"' . $role . '",';
							}
							$sRoles  = rtrim( $sRoles, ',' );
							$sRoles .= ']';
							?>
							$("#wsal-rep-roles").select2("val", <?php echo $sRoles; ?>);
							$( '#wsal-rb-roles-2').prop('checked', true);
							<?php
						}
						if ( ! empty( $current_report ) && ! empty( $current_report->ipAddresses ) ) {
							$sIPs = '[';
							foreach ( $current_report->ipAddresses as $ip ) {
								$sIPs .= '"' . $ip . '",';
							}
							$sIPs  = rtrim( $sIPs, ',' );
							$sIPs .= ']';
							?>
							$("#wsal-rep-ip-addresses").select2("val", <?php echo $sIPs; ?>);
							$( '#wsal-rb-ip-addresses-2').prop('checked', true);
							<?php
						}
						if ( ! empty( $current_report ) && ! empty( $current_report->objects ) ) {
							$selected_objs = '[';
							foreach ( $current_report->objects as $obj ) {
								$selected_objs .= '"' . $obj . '",';
							}
							$selected_objs  = rtrim( $selected_objs, ',' );
							$selected_objs .= ']';
							?>
							$( '#wsal-rep-event-objects').select2("val", <?php echo $selected_objs; ?>);
							$( '#wsal-rb-event-objects-2').prop('checked', true);
							<?php
						}
						if ( ! empty( $current_report ) && ! empty( $current_report->event_types ) ) {
							$selected_types = '[';
							foreach ( $current_report->event_types as $type ) {
								$selected_types .= '"' . $type . '",';
							}
							$selected_types  = rtrim( $selected_types, ',' );
							$selected_types .= ']';
							?>
							$( '#wsal-rep-event-types').select2("val", <?php echo $selected_types; ?>);
							$( '#wsal-rb-event-types-2').prop('checked', true);
							<?php
						}
						if ( ! empty( $current_report ) && ! empty( $current_report->viewState ) ) {
							$arr_alerts         = array();
							$post_type_alerts   = array();
							$post_status_alerts = array();

							// Extract selected alerts or post types in the current report.
							foreach ( $current_report->viewState as $key => $state ) {
								if ( $state == 'codes' ) {
									$arr_alerts = $current_report->triggers[ $key ]['alert_id'];
								}
								if ( 'post_types' === $state ) {
									$post_type_alerts = $current_report->triggers[ $key ]['post_types'];
								}
								if ( 'Blog Posts' === $state ) {
									$post_type_alerts[] = 'post';
								}
								if ( 'Pages' === $state ) {
									$post_type_alerts[] = 'page';
								}
								if ( 'post_statuses' === $state ) {
									$post_status_alerts = $current_report->triggers[ $key ]['post_statuses'];
								}
							}

							// Selected alerts.
							$selected_alerts = '[';
							foreach ( $arr_alerts as $alert_id ) {
								$selected_alerts .= $alert_id . ',';
							}
							$selected_alerts  = rtrim( $selected_alerts, ',' );
							$selected_alerts .= ']';

							// Selected post types.
							$selected_cpts = '[';
							foreach ( $post_type_alerts as $post_type ) {
								$selected_cpts .= '"' . $post_type . '",';
							}
							$selected_cpts  = rtrim( $selected_cpts, ',' );
							$selected_cpts .= ']';

							// Selected post statuses.
							$selected_statuses = '[';
							foreach ( $post_status_alerts as $post_status ) {
								$selected_statuses .= '"' . $post_status . '",';
							}
							$selected_statuses  = rtrim( $selected_statuses, ',' );
							$selected_statuses .= ']';

							if ( ! empty( $arr_alerts ) ) :
								?>
								// Add to select box.
								$("#wsal-rep-alert-codes").select2("val", <?php echo $selected_alerts; ?>);
								$( '#wsal-rb-alert-codes-1').prop('checked', true);
								<?php
							endif;
							if ( ! empty( $post_type_alerts ) ) :
								?>
								$("#wsal-rep-post-types").select2("val", <?php echo $selected_cpts; ?>);
								$( '#wsal-rb-post-types').prop('checked', true);
								<?php
							endif;
							if ( ! empty( $post_status_alerts ) ) :
								?>
								$("#wsal-rep-post-status").select2("val", <?php echo $selected_statuses; ?>);
								$( '#wsal-rb-post-status').prop('checked', true);
								<?php
							endif;
						}
						?>
					});
				</script>

				<!-- SECTION #2 -->
				<h4 class="wsal-reporting-subheading"><?php _e( 'Step 2: Select the date range', 'wp-security-audit-log' ); ?></h4>

				<div class="wsal-note"><?php _e( 'Note: Do not specify any dates if you are creating a scheduled report or if you want to generate a report from when you started the audit trail.', 'wp-security-audit-log' ); ?></div>

				<div class="wsal-rep-form-wrapper">
					<!--// BY DATE -->
					<div class="wsal-rep-section">
						<label class="wsal-rep-label-fl label-datepick"><?php _e( 'Start Date', 'wp-security-audit-log' ); ?></label>
						<div class="wsal-rep-section-fl">
							<p class="wsal-rep-clear">
								<input type="text" class="date-range" id="wsal-start-date" name="wsal-start-date" placeholder="<?php _e( 'Select start date', 'wp-security-audit-log' ); ?>"/>
								<span class="description"> (<?php echo WSAL_Helpers_Assets::DATEPICKER_DATE_FORMAT; ?>)</span>
							</p>
						</div>
					</div>
					<div class="wsal-rep-section">
						<label class="wsal-rep-label-fl label-datepick"><?php _e( 'End Date', 'wp-security-audit-log' ); ?></label>
						<div class="wsal-rep-section-fl">
							<p class="wsal-rep-clear">
								<input type="text" class="date-range" id="wsal-end-date" name="wsal-end-date" placeholder="<?php _e( 'Select end date', 'wp-security-audit-log' ); ?>"/>
								<span class="description"> (<?php echo WSAL_Helpers_Assets::DATEPICKER_DATE_FORMAT; ?>)</span>
							</p>
						</div>
					</div>
					<script type="text/javascript">
						jQuery(document).ready(function($){
							wsal_CreateDatePicker($, $( '#wsal-start-date'), null);
							wsal_CreateDatePicker($, $( '#wsal-end-date'), null);
						});
					</script>
				</div>

			<!-- SECTION #3 -->
				<h4 class="wsal-reporting-subheading"><?php _e( 'Step 3: Select Report Format', 'wp-security-audit-log' ); ?></h4>

				<div class="wsal-rep-form-wrapper">
					<div class="wsal-rep-section">
						<div class="wsal-rep-section-fl">
							<p class="wsal-rep-clear">
								<input type="radio" name="wsal-rb-report-type" id="wsal-rb-type-1" value="<?php echo $wsal_common::REPORT_HTML; ?>" checked="checked" />
								<label for="wsal-rb-type-1"><?php _e( 'HTML', 'wp-security-audit-log' ); ?></label>
							</p>
							<p class="wsal-rep-clear">
								<input type="radio" name="wsal-rb-report-type" id="wsal-rb-type-2" value="<?php echo $wsal_common::REPORT_CSV; ?>"
									<?php echo ( ! empty( $current_report ) && ( $wsal_common::REPORT_CSV == $current_report->type ) ) ? 'checked="checked"' : false; ?> />
								<label for="wsal-rb-type-2"><?php _e( 'CSV', 'wp-security-audit-log' ); ?></label>
							</p>
						</div>
					</div>
				</div>

			<!-- SECTION #4 -->
				<h4 class="wsal-reporting-subheading"><?php _e( 'Step 4: Generate Report Now or Configure Periodic Reports', 'wp-security-audit-log' ); ?></h4>
				<div class="wsal-rep-form-wrapper">
					<div class="wsal-rep-section">
						<input type="submit" name="wsal-reporting-submit" id="wsal-reporting-submit" class="button-primary" value="<?php _e( 'Generate Report Now', 'wp-security-audit-log' ); ?>">
					</div>
					<div class="wsal-rep-section">
						<span class="description"><?php _e( ' Use the buttons below to use the above criteria for a daily, weekly and monthly summary report which is sent automatically via email.', 'wp-security-audit-log' ); ?></span>
					</div>
					<div class="wsal-rep-section">
						<label class="wsal-rep-label-fl"><?php _e( 'Email address(es)', 'wp-security-audit-log' ); ?></label>
						<div class="wsal-rep-section-fl">
							<input type="text" id="wsal-notif-email" style="min-width:270px;border: 1px solid #aaa;" name="wsal-notif-email" placeholder="Email *" value="<?php echo ! empty( $current_report ) ? esc_html( $current_report->email ) : false; ?>">
						</div>
					</div>
					<div class="wsal-rep-section">
						<label class="wsal-rep-label-fl"><?php _e( 'Report Name', 'wp-security-audit-log' ); ?></label>
						<div class="wsal-rep-section-fl">
							<input type="text" id="wsal-notif-name" style="min-width:270px;border: 1px solid #aaa;" name="wsal-notif-name" placeholder="Name" value="<?php echo ! empty( $current_report ) ? esc_html( $current_report->title ) : false; ?>">
						</div>
					</div>
					<div class="wsal-rep-section">
						<label class="wsal-rep-label-fl"><?php _e( 'Frequency', 'wp-security-audit-log' ); ?></label>
						<div class="wsal-rep-section-fl">
							<input type="submit" name="wsal-periodic" class="button-primary" value="<?php _e( 'Daily', 'wp-security-audit-log' ); ?>">
							<input type="submit" name="wsal-periodic" class="button-primary" value="<?php _e( 'Weekly', 'wp-security-audit-log' ); ?>">
							<input type="submit" name="wsal-periodic" class="button-primary" value="<?php _e( 'Monthly', 'wp-security-audit-log' ); ?>">
							<input type="submit" name="wsal-periodic" class="button-primary" value="<?php _e( 'Quarterly', 'wp-security-audit-log' ); ?>">
						</div>
					</div>
				</div>

				<?php wp_nonce_field( 'wsal_reporting_view_action', 'wsal_reporting_view_field' ); ?>
			</form>

			<!-- SECTION Configured Periodic Reports -->
			<?php
			$periodic_reports = $wsal_common->GetPeriodicReports();
			if ( ! empty( $periodic_reports ) ) {
				?>
				<h4 class="wsal-reporting-subheading"><?php _e( 'Configured Periodic Reports', 'wp-security-audit-log' ); ?></h4>
				<div class="wsal-rep-form-wrapper">
					<div class="wsal-rep-section">
						<span class="description"><?php esc_html_e( 'Below is the list of configured periodic reports. Click on Modify to load the criteria and configure it above. To save the new criteria as a new report change the report name and save it. Do not change the report name to overwrite the existing periodic report.', 'wp-security-audit-log' ); ?></span>
						<br />
						<br />
						<span class="description"><?php esc_html_e( 'Note: Use the Send Now button to generate a report with data from the last 90 days if a quarterly report is configured, 30 days if monthly report is configured and 7 days if weekly report is configured.', 'wp-security-audit-log' ); ?></span>
					</div>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr><th>Name</th><th>Email address(es)</th><th>Frequency</th><th></th><th></th><th></th></tr>
						</thead>
						<tbody>
							<?php
							foreach ( $periodic_reports as $key => $report ) {
								$arr_emails = explode( ',', $report->email );
								?>
								<tr>
									<form action="<?php echo $this->GetUrl(); ?>" method="post">
										<input type="hidden" name="report-name" value="<?php echo $key; ?>">
										<td><?php echo $report->title; ?></td>
										<td>
											<?php
											foreach ( $arr_emails as $email ) {
												echo $email . '<br>';
											}
											?>
										</td>
										<td><?php echo $report->frequency; ?></td>
										<td><input type="submit" name="report-send-now" class="button-secondary" value="Send Now"></td>
										<td><input type="submit" name="report-modify" class="button-secondary" value="Modify"></td>
										<td><input type="submit" name="report-delete" class="button-secondary" value="Delete"></td>
									</form>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
				</div>
			<?php } ?>
		</div>
		<!-- Tab Built-in Archives
		<div class="wsal-tab wrap" id="tab-archives">
		</div>-->
		<!-- Tab Built-in Summary-->
		<div class="wsal-tab wrap" id="tab-summary">
			<p style="clear:both; margin-top: 30px"></p>

			<form id="wsal-summary-form" method="post">
				<!-- SECTION #1 -->
				<h4 class="wsal-reporting-subheading"><?php _e( 'Step 1: Choose Date Range', 'wp-security-audit-log' ); ?></h4>
				<div class="wsal-rep-form-wrapper">
					<!--// BY DATE -->
					<div class="wsal-rep-section">
						<label class="wsal-rep-label-fl label-datepick"><?php _e( 'From', 'wp-security-audit-log' ); ?></label>
						<div class="wsal-rep-section-fl">
							<p class="wsal-rep-clear">
								<input type="text" class="date-range" id="wsal-from-date" name="wsal-from-date" placeholder="<?php _e( 'Select start date', 'wp-security-audit-log' ); ?>"/>
								<span class="description"> (<?php echo WSAL_Helpers_Assets::DATEPICKER_DATE_FORMAT; ?>)</span>
							</p>
						</div>
					</div>
					<div class="wsal-rep-section">
						<label class="wsal-rep-label-fl label-datepick"><?php _e( 'To', 'wp-security-audit-log' ); ?></label>
						<div class="wsal-rep-section-fl">
							<p class="wsal-rep-clear">
								<input type="text" class="date-range" id="wsal-to-date" name="wsal-to-date" placeholder="<?php _e( 'Select end date', 'wp-security-audit-log' ); ?>"/>
								<span class="description"> (<?php echo WSAL_Helpers_Assets::DATEPICKER_DATE_FORMAT; ?>)</span>
							</p>
						</div>
					</div>
					<script type="text/javascript">
						jQuery(document).ready(function($){
							wsal_CreateDatePicker($, $( '#wsal-from-date'), null);
							wsal_CreateDatePicker($, $( '#wsal-to-date'), null);
						});
					</script>
				</div>
				<!-- SECTION #2 -->
				<h4 class="wsal-reporting-subheading"><?php _e( 'Step 2: Choose Criteria', 'wp-security-audit-log' ); ?></h4>
				<div class="wsal-rep-form-wrapper">
					<div class="wsal-rep-section">
						<label class="wsal-rep-label-fl"><?php _e( 'Report for', 'wp-security-audit-log' ); ?></label>
						<div class="wsal-rep-section-fl">
							<fieldset>
								<label for="criteria_1">
									<input type="radio" name="wsal-criteria" id="criteria_1" style="margin-top: 2px;" value="<?php echo WSAL_Rep_Common::LOGIN_BY_USER; ?>" checked="checked">
									<span class="name-criteria"><?php _e( 'Number of logins for user', 'wp-security-audit-log' ); ?></span>
									<input type="hidden" name="wsal-summary-field_1" class="wsal-summary-users"/>
								</label><br><br>
								<label for="criteria_2">
									<input type="radio" name="wsal-criteria" id="criteria_2" style="margin-top: 2px;" value="<?php echo WSAL_Rep_Common::LOGIN_BY_ROLE; ?>">
									<span class="name-criteria"><?php _e( 'Number of logins for users with the role of', 'wp-security-audit-log' ); ?></span>
									<input type="hidden" name="wsal-summary-field_2" class="wsal-summary-roles"/>
								</label><br><br>
								<label for="criteria_3">
									<input type="radio" name="wsal-criteria" id="criteria_3" style="margin-top: 2px;" value="<?php echo WSAL_Rep_Common::VIEWS_BY_USER; ?>">
									<span class="name-criteria"><?php _e( 'Number of views for user', 'wp-security-audit-log' ); ?></span>
									<input type="hidden" name="wsal-summary-field_3" class="wsal-summary-users"/>
								</label><br><br>
								<label for="criteria_4">
									<input type="radio" name="wsal-criteria" id="criteria_4" style="margin-top: 2px;" value="<?php echo WSAL_Rep_Common::VIEWS_BY_ROLE; ?>">
									<span class="name-criteria"><?php _e( 'Number of views for users with the role of', 'wp-security-audit-log' ); ?></span>
									<input type="hidden" name="wsal-summary-field_4" class="wsal-summary-roles"/>
								</label><br><br>
								<label for="criteria_5">
									<input type="radio" name="wsal-criteria" id="criteria_5" style="margin-top: 2px;" value="<?php echo WSAL_Rep_Common::PUBLISHED_BY_USER; ?>">
									<span class="name-criteria"><?php _e( 'Number of published content for user', 'wp-security-audit-log' ); ?></span>
									<input type="hidden" name="wsal-summary-field_5" class="wsal-summary-users"/>
								</label><br><br>
								<label for="criteria_6">
									<input type="radio" name="wsal-criteria" id="criteria_6" style="margin-top: 2px;" value="<?php echo WSAL_Rep_Common::PUBLISHED_BY_ROLE; ?>">
									<span class="name-criteria"><?php _e( 'Number of published content for users with the role of', 'wp-security-audit-log' ); ?></span>
									<input type="hidden" name="wsal-summary-field_6" class="wsal-summary-roles"/>
								</label><br><br>
								<label for="criteria_7">
									<input type="radio" name="wsal-criteria" id="criteria_7" style="margin-top: 2px;" value="<?php echo WSAL_Rep_Common::DIFFERENT_IP; ?>">
									<span><?php _e( 'Different IP addresses for Usernames', 'wp-security-audit-log' ); ?></span>
								</label><br>
								<div class="sub-options">
									<label for="only_login">
										<input type="checkbox" name="only_login" id="only_login" style="margin: 2px;">
										<span><?php _e( 'List only IP addresses used during login', 'wp-security-audit-log' ); ?></span>
									</label><br>
									<span class="description"><?php _e( 'If the above option is enabled the report will only include the IP addresses from where the user logged in. If it is disabled it will list all the IP addresses from where the plugin recorded activity originating from the user.', 'wp-security-audit-log' ); ?></span>
								</div>
							</fieldset>
						</div>
					</div>
				</div>
				<!--// BY SITE -->
				<?php
				/*
				<div class="wsal-rep-section">
					<label class="wsal-rep-label-fl"><?php _e('By Site(s)', 'reports-wsal');?></label>
					<div class="wsal-rep-section-fl">
						<p class="wsal-rep-clear">
							<input type="radio" name="wsal-sum-sites" id="wsal-sum-sites-1" value="1" checked="checked">
							<label for="wsal-sum-sites-1"><?php _e('All Sites', 'reports-wsal');?></label>
						</p>
						<p class="wsal-rep-clear">
							<input type="radio" name="wsal-sum-sites" id="wsal-sum-sites-2" value="2">
							<label for="wsal-sum-sites-2"><?php _e('Specify sites', 'reports-wsal');?></label>
							<input type="hidden" name="wsal-summary-sites" id="wsal-summary-sites"/>
						</p>
					</div>
				</div>
				*/
				?>
				<!-- SECTION #3 -->
				<h4 class="wsal-reporting-subheading"><?php _e( 'Step 3: Select Report Format', 'wp-security-audit-log' ); ?></h4>
				<div class="wsal-rep-form-wrapper">
					<div class="wsal-rep-section">
						<div class="wsal-rep-section-fl">
							<p class="wsal-rep-clear">
								<input type="radio" name="wsal-summary-type" id="wsal-summary-type-1" value="<?php echo $wsal_common::REPORT_HTML; ?>" checked="checked">
								<label for="wsal-summary-type-1"><?php _e( 'HTML', 'wp-security-audit-log' ); ?></label>
							</p>
							<p class="wsal-rep-clear">
								<input type="radio" name="wsal-summary-type" id="wsal-summary-type-2" value="<?php echo $wsal_common::REPORT_CSV; ?>">
								<label for="wsal-summary-type-2"><?php _e( 'CSV', 'wp-security-audit-log' ); ?></label>
							</p>
						</div>
					</div>
				</div>
				<div class="wsal-rep-form-wrapper">
					<div class="wsal-rep-section">
						<div class="wsal-rep-section-fl">
							<input type="submit" id="wsal-submit-now" name="wsal-statistics-submit" value="Generate Report" class="button-primary">
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<script type="text/javascript">
	jQuery(document).ready(function($){
		$( '#wsal-rep-form').on('submit', function(){
			//#! Sites
			if ( $( '#wsal-rb-sites-2' ).is(':checked') && $( '#wsal-rep-sites').val().length === 0 ){
                alert("<?php _e( 'Please specify at least one site', 'wp-security-audit-log' ); ?>");
                return false;
			}

              if ( $( '#wsal-rb-sites-3' ).is(':checked') && $( '#wsal-rep-sites-exclude').val().length === 0 ){
                alert("<?php _e( 'Please specify at least one site', 'wp-security-audit-log' ); ?>");
                return false;
              }

			//#! Users
			if ( $( '#wsal-rb-users-2' ).is( ':checked' ) && $( '#wsal-rep-users').val().length === 0 ) {
                alert("<?php _e( 'Please specify at least one user', 'wp-security-audit-log' ); ?>");
                return false;
			}

              if ( $( '#wsal-rb-users-3' ).is( ':checked' ) && $( '#wsal-rep-users-exclude').val().length === 0 ) {
                alert("<?php _e( 'Please specify at least one user', 'wp-security-audit-log' ); ?>");
                return false;
              }

			//#! Roles
			if ( $( '#wsal-rb-roles-2' ).is( ':checked' ) && $( '#wsal-rep-roles').val().length === 0 ) {
                alert("<?php _e( 'Please specify at least one role', 'wp-security-audit-log' ); ?>");
                return false;
			}

            if ( $( '#wsal-rb-roles-3' ).is( ':checked' ) && $( '#wsal-rep-roles-exclude').val().length === 0 ) {
                alert("<?php _e( 'Please specify at least one role', 'wp-security-audit-log' ); ?>");
                return false;
            }

			//#! IP addresses
			if ( $( '#wsal-rb-ip-addresses-2' ).is( ':checked' ) && $( '#wsal-rep-ip-addresses').val().length === 0 ) {
				alert("<?php _e( 'Please specify at least one IP address', 'wp-security-audit-log' ); ?>");
				return false;
			}

            if ( $( '#wsal-rb-ip-addresses-3' ).is( ':checked' ) && $( '#wsal-rep-ip-addresses-exclude').val().length === 0 ) {
                alert("<?php _e( 'Please specify at least one IP address', 'wp-security-audit-log' ); ?>");
                return false;
            }

			//#! Event Objects
			if ( $( '#wsal-rb-event-objects-2' ).is( ':checked' ) && $( '#wsal-rep-event-objects').val().length === 0 ) {
			    alert("<?php esc_html_e( 'Please specify at least one object', 'wp-security-audit-log' ); ?>");
				return false;
			}

            if ( $( '#wsal-rb-event-objects-3' ).is( ':checked' ) && $( '#wsal-rep-event-objects-exclude').val().length === 0 ) {
                alert("<?php esc_html_e( 'Please specify at least one object', 'wp-security-audit-log' ); ?>");
                return false;
            }

			//#! Event types
			if ( $( '#wsal-rb-event-types-2' ).is( ':checked' ) && $( '#wsal-rep-event-types').val().length === 0 ) {
				alert("<?php esc_html_e( 'Please specify at least one event type', 'wp-security-audit-log' ); ?>");
				return false;
			}

            if ( $( '#wsal-rb-event-types-2' ).is( ':checked' ) && $( '#wsal-rep-event-types-exclude').val().length === 0 ) {
                alert("<?php esc_html_e( 'Please specify at least one event type', 'wp-security-audit-log' ); ?>");
                return false;
            }

			//#! Alert groups
			if ( ( ! $( '#wsal-rb-groups' ).is( ':checked' ) && ! $( '.wsal-js-groups:checked' ).length ) ) {
				if ( ! $( '#wsal-rep-alert-codes' ).val().length ) {
					if ( ! $( '.wsal-criteria:checked' ).length ) {
						alert( "<?php esc_html_e( 'Please specify at least one Alert group or specify an Alert code', 'wp-security-audit-log' ); ?>" );
						return false;
					}
				}
			}

			return true;
		});

		$("#wsal-summary-sites").select2({
			data: JSON.parse('<?php echo $wsal_rep_sites; ?>'),
			placeholder: "<?php _e( 'Select site(s)' ); ?>",
			minimumResultsForSearch: 10,
			multiple: true,
		}).on('select2-open',function(e){
			var v = $(this).val();
			if(!v.length){
				$( '#wsal-sum-sites-2').prop('checked', true);
			}
		}).on('select2-removed', function(){
			var v = $(this).val();
			if(!v.length){
				$( '#wsal-sum-sites-1').prop('checked',true);
			}
		}).on('select2-close', function(){
			var v = $(this).val();
			if(!v.length){
				$( '#wsal-sum-sites-1').prop('checked',true);
			}
		});

		$(".wsal-summary-users").select2({
			placeholder: "<?php _e( 'Select user' ); ?>",
			multiple: false,
			ajax: {
				url: ajaxurl + '?action=AjaxGetUserID',
				dataType: 'json',
				type: "GET",
				data: function (term) {
					return {
						term: term
					};
				},
				results: function (data) {
					return {
						results: $.map(data, function (item) {
							return {
								text: item.name,
								id: item.id
							}
						})
					};
				}
			}
		});

		$(".wsal-summary-roles").select2({
			data: JSON.parse('<?php echo $wsal_rep_roles; ?>'),
			placeholder: "<?php _e( 'Select role' ); ?>",
			minimumResultsForSearch: 10,
			multiple: false
		});

		$( '#wsal-summary-form').on('submit', function(){
			var sel = $("input[name='wsal-criteria']:checked").val();
			var field = $("input[name='wsal-summary-field_"+sel+"']").val();
			// field required
			if (field != '') {
				return true;
			} else {
				alert("Add User(s)/Role(s) for the report.");
				return false;
			}
		});
	});
</script>
