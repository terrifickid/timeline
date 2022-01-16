<?php
/**
 * Extension: Reports
 *
 * Reports extension for wsal.
 *
 * @since 1.0.0
 * @package wsal
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Holds the name of the cache key if cache available
 */
define( 'WSAL_CACHE_KEY_2', '__NOTIF_CACHE__' );

/**
 * Class WSAL_Rep_Plugin
 *
 * @package report-wsal
 */
class WSAL_Rep_Plugin {

	/**
	 * Instance of WpSecurityAuditLog.
	 *
	 * @var WpSecurityAuditLog
	 */
	protected $wsal = null;

	/**
	 * Method: Constructor
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		// Function to hook at `wsal_init`.
		add_action( 'wsal_init', array( $this, 'wsal_init' ) );
	}

	/**
	 * Triggered when the main plugin is loaded.
	 *
	 * @param WpSecurityAuditLog $wsal - Instance of WpSecurityAuditLog.
	 * @see WpSecurityAuditLog::load()
	 */
	public function wsal_init( WpSecurityAuditLog $wsal ) {
		// Autoload files in /classes.
		$wsal->autoloader->Register( 'WSAL_Rep_', dirname( __FILE__ ) . '/classes' );

		// Initialize utility classes.
		$wsal->reports_util = new WSAL_Rep_Common( $wsal );

		if ( isset( $wsal->views ) ) {
			$wsal->views->AddFromClass( 'WSAL_Rep_Views_Main' );
		}

		//  register alert formatters for sms and email notifications
		add_filter( 'wsal_alert_formatters', array( $this, 'register_alert_formatters' ), 10, 1 );
	}

	/**
	 * @param array $formatters Formatter definition arrays.
	 *
	 * @return array
	 * @since 4.2.1
	 * @see WSAL_AlertFormatterFactory
	 */
	public function register_alert_formatters( $formatters ) {
		$html_report_configuration  = ( WSAL_AlertFormatterConfiguration::buildHtmlConfiguration() )
			->setIsJsInLinksAllowed( false );
		$formatters ['report-html'] = $html_report_configuration;

		$csv_report_configuration  = ( WSAL_AlertFormatterConfiguration::buildPlainTextConfiguration() )
			->setUseHtmlMarkupForLinks( false );
		$formatters ['report-csv'] = $csv_report_configuration;

		return $formatters;
	}
}
