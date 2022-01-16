<?php
/**
 * Class: Abstract Filter
 *
 * Abstract filter class.
 *
 * @since 1.0.0
 * @package wsal
 * @subpackage search
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WSAL_AS_Filters_AbstractFilter
 *
 * @package wsal
 * @subpackage search
 */
abstract class WSAL_AS_Filters_AbstractFilter {

	/**
	 * Whether to print title for filter or not.
	 *
	 * @var boolean
	 */
	public $IsTitled = false;

	/**
	 * Returns true if this filter has suggestions for this query.
	 *
	 * @param string $query The part of query to check.
	 * @return boolean If filter has suggestions for query or not.
	 */
	abstract public function IsApplicable($query);

	/**
	 * List of filter prefixes (the stuff before the colon).
	 *
	 * @return array
	 */
	abstract public function GetPrefixes();

	/**
	 * List of widgets to be used in UI.
	 *
	 * @return WSAL_AS_Filters_AbstractWidget[]
	 */
	abstract public function GetWidgets();

	/**
	 * Filter name (used in UI).
	 *
	 * @return string
	 */
	abstract public function GetName();

	/**
	 * Allow this filter to change the DB query according to the search value (usually a value from GetOptions()).
	 *
	 * @param WSAL_Models_OccurrenceQuery $query Database query for selecting occurrences.
	 * @param string                      $prefix The filter name (filter string prefix).
	 * @param string                      $value The filter value (filter string suffix).
	 * @throws Exception Thrown when filter is unsupported.
	 */
	abstract public function ModifyQuery( $query, $prefix, $value );

	/**
	 * Renders filter widgets.
	 */
	public function Render() {
		if ( $this->IsTitled ) {
			?><strong><?php echo $this->GetName(); ?></strong>
			<?php
		}
		foreach ( $this->GetWidgets() as $widget ) {
			?>
			<div class="wsal-as-filter-widget">
				<?php $widget->Render(); ?>
			</div>
			<?php
		}
	}

	/**
	 * Generates a widget name.
	 *
	 * @return string
	 */
	public function GetSafeName() {
		return strtolower( get_class( $this ) );
	}
}
