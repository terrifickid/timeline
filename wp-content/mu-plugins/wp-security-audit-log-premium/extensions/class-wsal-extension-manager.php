<?php
/**
 * Extensions Manager Class
 *
 * Class file for extensions management.
 *
 * @since 3.0.0
 * @package wsal
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'WSAL_Extension_Manager' ) ) :

	/**
	 * WSAL_Extension_Manager.
	 *
	 * Extension manager class.
	 */
	class WSAL_Extension_Manager {

		/**
		 * Extensions.
		 *
		 * @var array
		 */
		public $extensions;

		/**
		 * WSAL Instance.
		 *
		 * @var WpSecurityAuditLog
		 */
		public $wsal;

		/**
		 * Method: Constructor.
		 */
		public function __construct() {
			// Include extension files.
			$this->includes();

			// Initialize the extensions.
			$this->init();
		}

		/**
		 * Include extension manually.
		 *
		 * @param string $extension - Extension.
		 */
		public static function include_extension( $extension ) {
			switch ( $extension ) {
				case 'search':
					if ( file_exists( WSAL_BASE_DIR . '/extensions/search/search-init.php' ) ) {
						require_once WSAL_BASE_DIR . '/extensions/search/search-init.php';
						new WSAL_SearchExtension();
					}
					break;
				case 'notifications':
					if ( file_exists( WSAL_BASE_DIR . '/extensions/email-notifications/email-notifications.php' ) ) {
						require_once WSAL_BASE_DIR . '/extensions/email-notifications/email-notifications.php';
						new WSAL_NP_Plugin();
					}
					break;
				case 'reports':
					if ( file_exists( WSAL_BASE_DIR . '/extensions/reports/reports-init.php' ) ) {
						require_once WSAL_BASE_DIR . '/extensions/reports/reports-init.php';
						new WSAL_Rep_Plugin();
					}
					break;
				case 'sessions':
				case 'usersessions':
					if ( file_exists( WSAL_BASE_DIR . '/extensions/users-sessions/user-sessions.php' ) ) {
						require_once WSAL_BASE_DIR . '/extensions/users-sessions/user-sessions.php';
						new WSAL_UserSessions_Plugin();
					}
					break;
				case 'external-db':
					if ( file_exists( WSAL_BASE_DIR . '/extensions/external-db/external-db-init.php' ) ) {
						require_once WSAL_BASE_DIR . '/extensions/external-db/external-db-init.php';
						new WSAL_Ext_Plugin();
					}
					break;
				case 'logs-management':
					if ( file_exists( WSAL_BASE_DIR . '/extensions/logs-management/logs-management.php' ) ) {
						require_once WSAL_BASE_DIR . '/extensions/logs-management/logs-management.php';
						new WSAL_LogsManagement();
					}
					break;

				case 'settings-import-export':
					if ( file_exists( WSAL_BASE_DIR . '/extensions/settings-import-export/settings-import-export.php' ) ) {
						require_once WSAL_BASE_DIR . '/extensions/settings-import-export/settings-import-export.php' ;
						new WSAL_SettingsExporter();
					}
					break;
				default:
					break;
			}
		}

		/**
		 * Method: Include extensions.
		 */
		protected function includes() {
			// Extensions for BASIC and above plans.
			if ( wsal_freemius()->is_plan_or_trial__premium_only( 'starter' ) ) {
				/**
				 * Search.
				 */
				if ( file_exists( WSAL_BASE_DIR . '/extensions/search/search-init.php' ) ) {
					require_once WSAL_BASE_DIR . '/extensions/search/search-init.php';
				}

				/**
				 * Email Notifications.
				 */
				if ( file_exists( WSAL_BASE_DIR . '/extensions/email-notifications/email-notifications.php' ) ) {
					require_once WSAL_BASE_DIR . '/extensions/email-notifications/email-notifications.php';
				}
			}

			// Extensions for PROFESSIONAL and above plans.
			if ( wsal_freemius()->is_plan_or_trial__premium_only( 'professional' ) ) {
				/**
				 * Reports
				 */
				if ( file_exists( WSAL_BASE_DIR . '/extensions/reports/reports-init.php' ) ) {
					require_once WSAL_BASE_DIR . '/extensions/reports/reports-init.php';
				}

				/**
				 * Users Sessions Management.
				 */
				if ( file_exists( WSAL_BASE_DIR . '/extensions/user-sessions/user-sessions.php' ) ) {
					require_once WSAL_BASE_DIR . '/extensions/user-sessions/user-sessions.php';
				}
			}

			// Extensions for BUSINESS and above plans.
			if ( wsal_freemius()->is_plan_or_trial__premium_only( 'business' ) ) {
				/**
				 * External DB
				 */
				if ( file_exists( WSAL_BASE_DIR . '/extensions/external-db/external-db-init.php' ) ) {
					require_once WSAL_BASE_DIR . '/extensions/external-db/external-db-init.php';
				}

				if ( file_exists( WSAL_BASE_DIR . '/extensions/logs-management/logs-management.php' ) ) {
					require_once WSAL_BASE_DIR . '/extensions/logs-management/logs-management.php';
				}

				if ( file_exists( WSAL_BASE_DIR . '/extensions/settings-import-export/settings-import-export.php' ) ) {
					require_once WSAL_BASE_DIR . '/extensions/settings-import-export/settings-import-export.php';
				}
			}
		}

		/**
		 * Method: Initialize the extensions.
		 */
		protected function init() {
			// Basic package extensions.
			if ( wsal_freemius()->is_plan_or_trial__premium_only( 'starter' ) ) {
				// Search filters.
				if ( class_exists( 'WSAL_SearchExtension' ) ) {
					$this->extensions[] = new WSAL_SearchExtension();
				}

				// Email Notifications.
				if ( class_exists( 'WSAL_NP_Plugin' ) ) {
					$this->extensions[] = new WSAL_NP_Plugin();
				}
			}

			// Professional package extensions.
			if ( wsal_freemius()->is_plan_or_trial__premium_only( 'professional' ) ) {
				// Reports.
				if ( class_exists( 'WSAL_Rep_Plugin' ) ) {
					$this->extensions[] = new WSAL_Rep_Plugin();
				}

				// Users Sessions Management.
				if ( class_exists( 'WSAL_UserSessions_Plugin' ) ) {
					$this->extensions[] = new WSAL_UserSessions_Plugin();
				}
			}

			// Business package extensions.
			if ( wsal_freemius()->is_plan_or_trial__premium_only( 'business' ) ) {
				// External DB.
				if ( class_exists( 'WSAL_Ext_Plugin' ) ) {
					$this->extensions[] = new WSAL_Ext_Plugin();
				}

				if ( class_exists( 'WSAL_LogsManagement' ) ) {
					$this->extensions[] = new WSAL_LogsManagement();
				}

				if ( class_exists( 'WSAL_SettingsExporter' ) ) {
					$this->extensions[] = new WSAL_SettingsExporter();
				}
			}
		}
	}

endif;
