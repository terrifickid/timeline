<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extension: Logs Management
 *
 * Log management extension for wsal.
 *
 * @since 4.3.3
 */
class WSAL_SettingsExporter {

	public function __construct() {
		add_filter( 'wsal_setting_tabs', array( $this, 'add_logs_management_tab' ), 10, 1 );
		add_filter( 'wp_ajax_wsal_export_settings', array( $this, 'export_settings' ), 10, 1 );
		add_filter( 'wp_ajax_wsal_check_setting_pre_import', array( $this, 'check_setting_pre_import' ), 10, 1 );
		add_filter( 'wp_ajax_wsal_process_import', array( $this, 'process_import' ), 10, 1 );

		// Add scripts.
		add_action( 'admin_init', array( $this, 'setup_logs_management' ) );
	}

	public function is_active() {
		return wsal_freemius()->is_plan_or_trial__premium_only( 'professional' );
	}

	/**
	 * Add scripts and styles for this extenion.
	 *
	 * @return void
	 */
	public function setup_logs_management() {

		// Get current tab.
		$current_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );

		if ( 'settings-export-import' == $current_tab ) {
			$wsal = WpSecurityAuditLog::GetInstance();
			wp_enqueue_script(
				'settings-export-import',
				$wsal->GetBaseUrl() . '/extensions/settings-import-export/js/settings-export.js',
				[],
				WSAL_VERSION,
				true
			);

			// Link to build in contact form.
			if ( $wsal->IsMultisite() ) {
				$help_page = add_query_arg( 'page', 'wsal-help&tab=contact', network_admin_url( 'admin.php' ) );
			} else {
				$help_page = add_query_arg( 'page', 'wsal-help&tab=contact', admin_url( 'admin.php' ) );
			}

			$help_text = esc_html__( 'For more information and / or if you require assistance, please', 'wp-security-audit-log' );
			$help_text .= ' <a href="'. $help_page .'">'. esc_html__( 'Contact Us', 'wp-security-audit-log') .'</a>';

	
			// Passing nonce for security to JS file.
			$wsal_data = array(
				'wp_nonce'            => wp_create_nonce( 'wsal-export-settings' ),
				'checkingMessage'     => esc_html__( 'Checking import contents', 'wp-security-audit-log' ),
				'checksPassedMessage' => esc_html__( 'Ready to import', 'wp-security-audit-log' ),
				'checksFailedMessage' => esc_html__( 'Issues found', 'wp-security-audit-log' ),
				'importingMessage'    => esc_html__( 'Importing settings', 'wp-security-audit-log' ),
				'importedMessage'     => esc_html__( 'Settings imported', 'wp-security-audit-log' ),
				'helpMessage'         => esc_html__( 'Help', 'wp-security-audit-log' ),
				'notFoundMessage'     => esc_html__( 'The role, user or post type contained in your settings are not currently found in this website. Importing such settings could lead to abnormal behavour. For more information and / or if you require assistance, please', 'wp-security-audit-log' ),
				'notSupportedMessage' => esc_html__( 'Currently this data is not supported by our export/import wizard.', 'wp-security-audit-log' ),
				'wrongFormat'         => esc_html__( 'Please upload a valid JSON file.', 'wp-security-audit-log' ),
				'cancelMessage'       => esc_html__( 'Cancel', 'wp-security-audit-log' ),
				'readyMessage'        => esc_html__( 'The settings file has been tested and the configuration is ready to be imported. Would you like to proceed?', 'wp-security-audit-log' ),
				'proceedMessage'      => esc_html__( 'The configuration has been successfully imported. Click OK to close this window', 'wp-security-audit-log' ),				
				'proceed'             => esc_html__( 'Proceed', 'wp-security-audit-log' ),			
				'ok'                  => esc_html__( 'OK', 'wp-security-audit-log' ),
				'helpPage'            => $help_page,
				'helpLinkText'        => esc_html__( 'Contact Us', 'wp-security-audit-log' ),

			);
			wp_localize_script( 'settings-export-import', 'wsal_import_data', $wsal_data );
			wp_enqueue_style( 'settings-export-importstyle', $wsal->GetBaseUrl() . '/extensions/settings-import-export/css/style.css' );
		}
	}

	/**
	 * Add log management tab to WSAL settings.
	 *
	 * @param array $wsal_setting_tabs
	 * @return array - Tabs, plus our tab.
	 */
	public function add_logs_management_tab( $wsal_setting_tabs ) {
		$wsal_setting_tabs['settings-export-import'] = array(
			'name'     => esc_html__( 'Export/import settings', 'wp-security-audit-log' ),
			'link'     => add_query_arg( 'tab', 'settings-export-import' ),
			'render'   => array( $this, 'logs_management_tab' ),
			'save'     => false,
			'priority' => 100,
		);
		return $wsal_setting_tabs;
	}

	/**
	 * Handle content.
	 *
	 * @return void
	 */
	public function logs_management_tab() {
		$this->tab_content();
	}

	/**
	 * The actual settings/tab content.
	 *
	 * @return void
	 */
	private function tab_content() {
		$disabled  = ! $this->is_active() ? 'disabled' : '';
		$admin_url = ! is_multisite() ? 'admin_url' : 'network_admin_url';
		$buy_now   = add_query_arg( 'page', 'wsal-auditlog-pricing', $admin_url( 'admin.php' ) );
		$html_tags = WpSecurityAuditLog::GetInstance()->allowed_html_tags;
		$nonce     = wp_create_nonce( 'wsal-export-settings' );

		$tab_info_msg = __( 'From here you can export the plugin\'s settings configuration and also import them from an export file. Use the export file to keep a backup of the plugin\'s configuration or to import the same settings configuration to another website.', 'wp-security-audit-log' );
		if ( $disabled ) {
			/* Translators: Upgrade now hyperlink. */
			$tab_info_msg = sprintf( esc_html__( 'Settings import/export is available in the Professional and Business Plans. %s to configure and receive this feature.', 'wp-security-audit-log' ), '<a href="' . $buy_now . '">' . esc_html__( 'Upgrade now', 'wp-security-audit-log' ) . '</a>' );
		}
		?>

		<?php 
		// Show if applicable.
		if ( ! $disabled ) : ?>
		<p class="description"> <?php echo wp_kses( $tab_info_msg, $html_tags ); ?></p>
		<table class="form-table wsal-tab logs-management-settings">
		<tr>
				<th><label><?php esc_html_e( 'Export settings', 'wp-security-audit-log' ); ?></label></th>
				<td>
					<fieldset <?php echo esc_attr( $disabled ); ?>>
						<input type="button" id="export-settings" class="button-primary" value="<?php esc_html_e( 'Export', 'wp-security-audit-log' ); ?>" data-export-wsal-settings data-nonce="<?php echo esc_attr( $nonce  ); ?>">
						<p class="description">
							<?php esc_html_e( 'Once the settings are exported a download will automatically start. The settings are exported to a JSON file.', 'wp-security-audit-log' ); ?>
						</p>
					</fieldset>
				</td>
			</tr>

			<tr>
				<th><label><?php esc_html_e( 'Import settings', 'wp-security-audit-log' ); ?></label></th>
				<td>
					<fieldset <?php echo esc_attr( $disabled ); ?>>

						<input type="file" id="wsal-settings-file" name="filename"><br>
						<input style="margin-top: 7px;" type="submit" id="import-settings" class="button-primary" data-import-wsal-settings data-nonce="<?php echo esc_attr( $nonce  ); ?>" value="<?php esc_html_e( 'Validate & Import', 'wp-security-audit-log' ); ?>">
						<p class="description">
							<?php esc_html_e( 'Once you choose a JSON settings file, it will be checked prior to being imported to alert you of any issues, if there are any.', 'wp-security-audit-log' ); ?>
						</p>
						<div id="import-settings-modal">
							<div class="modal-content">
								<h3 id="wsal-modal-title"></h3>
								<span class="import-settings-modal-close">&times;</span>
								<span><ul id="wsal-settings-file-output"></ul></span>
							</div>
						</div>
						
					</fieldset>
				</td>
			</tr>
			
		</table>
		<?php
		endif;
	}

	/**
	 * Creates a JSON file containing WSAL settings.
	 */
	public function export_settings() {
		// Grab POSTed data.
		$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );

		// Check nonce.
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wsal-export-settings' ) ) {
			wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'wp-security-audit-log' ) );
		}

		global $wpdb;
		$prepared_query	= $wpdb->prepare(
			"SELECT `option_name`, `option_value` FROM `{$wpdb->options}` WHERE `option_name` LIKE %s ORDER BY `option_name` ASC",
			WpSecurityAuditLog::OPTIONS_PREFIX . '%'
		);
		$results = $wpdb->get_results( $prepared_query );
		
		wp_send_json_success( json_encode( $results ) );
		exit;
	}

	public function check_setting_pre_import() {

		//Grab POSTed data.
		$nonce           = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );

		// Check nonce.
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wsal-export-settings' ) ) {
			wp_send_json_error( esc_html__( 'Nonce Verification Failed.', 'wp-security-audit-log' ) );
		}

		$setting_name   = filter_input( INPUT_POST, 'setting_name', FILTER_SANITIZE_STRING );
		$process_import = filter_input( INPUT_POST, 'process_import', FILTER_SANITIZE_STRING );
		$setting_value  = filter_input( INPUT_POST, 'setting_value', FILTER_DEFAULT, FILTER_FORCE_ARRAY );
		$setting_value  = $setting_value[0];

		$message = [
			'setting_checked' => $setting_name,
		];

		$failed = false;

		// Check if relevant data is present for setting to be operable before import.
		if ( ! empty( $setting_value ) ) {	

			if ( 'wsal_custom-post-types' == $setting_name ) {
				$setting_value_to_test = $this->trim_and_explode( $setting_value );
				foreach ( $setting_value_to_test as $post_type ) {
					if ( ! in_array( $post_type, $this->get_all_post_types() ) ) {
						$message[ 'failure_reason' ] = __( 'Post type not found: ', 'wp-security-audit-log' ) . $post_type;
						$message[ 'failure_reason_type' ] = 'not_found';
						$failed = true;
					}
				}
			} else if ( 'wsal_excluded-roles' == $setting_name ) {
				$setting_value_to_test = $this->trim_and_explode( $setting_value );
				foreach ( $setting_value_to_test as $role ) {
					if ( ! in_array( $role, array_keys( get_editable_roles() ) ) ) {
						$message[ 'failure_reason' ] = __( 'Role not found: ', 'wp-security-audit-log' ) . $role;
						$message[ 'failure_reason_type' ] = 'not_found';		
						$failed = true;				
					}
				}
			} else if ( 'wsal_excluded-users' == $setting_name ) {
				$setting_value_to_test = $this->trim_and_explode( $setting_value );
				foreach ( $setting_value_to_test as $user_login ) {
					if ( ! get_user_by( 'login', $user_login ) ) {
						$message[ 'failure_reason' ] = __( 'User not found: ', 'wp-security-audit-log' ) . $user_login;
						$message[ 'failure_reason_type' ] = 'not_found';
						$failed = true;
					}
				}
			} else if ( strpos( $setting_name, 'wsal_usersessions_policy_' ) === 0)  {
				$role_to_check = str_replace( 'wsal_usersessions_policy_', '', $setting_name );
				if ( ! in_array( $role_to_check, array_keys( get_editable_roles() ) ) ) {
					$message[ 'failure_reason' ] = __( 'Role not found: ', 'wp-security-audit-log' ) . $role_to_check;
					$message[ 'failure_reason_type' ] = 'not_found';
					$failed = true;
				}
			} else if ( strpos( $setting_name, 'wsal_notification-' ) === 0 && strpos( $setting_name, 'built-in' ) === false  )  {
				$message[ 'failure_reason' ] = __( 'Custom notifications are not supported', 'wp-security-audit-log' );
				$message[ 'failure_reason_type' ] = 'not_supported';	
				$failed = true;	
			}

			if ( 'true' !== $process_import && $failed ) {
				wp_send_json_error( $message );
			}
		}

		// If set to import the data once checked, then do so.
		if ( 'true' == $process_import && ! isset( $message[ 'failure_reason' ] ) ) {
			$updated = ( ! update_option( $setting_name, maybe_unserialize( $setting_value ) ) ) ? __( 'Setting updated', 'wp-security-audit-log' ) : __( 'Setting created', 'wp-security-audit-log' );
			$message[ 'import_confirmation' ] = $updated;
			wp_send_json_success( $message );
		}

		wp_send_json_success( $message );
		exit;
	}

	/**
	 * Simpler helper to get all available post types.
	 *
	 * @return array
	 */
	public function get_all_post_types() {
		global $wp_post_types;
		return array_keys( $wp_post_types );
	}

	/**
	 * Gets value ready for checking when needed.
	 */
	public function trim_and_explode( $value ) {
		if ( is_array( $value ) ) {
			return explode( ',', $value[0] );	
		} else {
			$setting_value = trim( $value, '"' );
			return $setting_value = str_replace( '""', '"', explode( ',', $setting_value ) );	
		}	
	}
}
