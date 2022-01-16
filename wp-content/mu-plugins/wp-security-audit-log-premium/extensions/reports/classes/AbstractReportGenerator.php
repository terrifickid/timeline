<?php
/**
 * Class WSAL_Rep_AbstractReportGenerator
 *
 * @package wsal/report
 */

if ( ! class_exists( 'WSAL_Rep_Plugin' ) ) {
	exit( 'You are not allowed to view this page.' );
}

/**
 * Abstract class for different report formats.
 *
 * @package wsal/report
 * @since 4.2.0
 */
abstract class WSAL_Rep_AbstractReportGenerator {

	/**
	 * Formats date for the presentation layer.
	 *
	 * @param string $timestamp Timestamp.
	 *
	 * @return string Formatted date.
	 * @since 4.2.0
	 */
	function getFormattedDate( $timestamp ) {
		return WSAL_Utilities_DateTimeFormatter::instance()->getFormattedDateTime( $timestamp, 'date' );
	}
}
