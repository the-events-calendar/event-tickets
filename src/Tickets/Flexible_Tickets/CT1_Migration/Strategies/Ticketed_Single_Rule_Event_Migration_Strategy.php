<?php
/**
 * Handles the migration of Recurring Event with 1 recurrence rule and one or more non-RSVP tickets attached.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes\CT1_Migration\Strategies;
 */

namespace TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies;

use TEC\Events\Custom_Tables\V1\Migration\Strategies\Strategy_Interface;
use TEC\Events\Custom_Tables\V1\Traits\With_String_Dictionary;
use TEC\Events_Pro\Custom_Tables\V1\Migration\Strategy\Single_Rule_Event_Migration_Strategy;

/**
 * Class Ticketed_Single_Rule_Event_Migration_Strategy.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes\CT1_Migration\Strategies;
 */
class Ticketed_Single_Rule_Event_Migration_Strategy
	extends Single_Rule_Event_Migration_Strategy
	implements Strategy_Interface {
	use With_String_Dictionary;
	use Ticketed_Recurring_Event_Strategy_Trait;

	/**
	 * Returns this strategy's slug.
	 *
	 * @since 5.8.0
	 *
	 * @return string The slug of the strategy.
	 */
	public static function get_slug() {
		return 'tec-tickets-recurring-single-rule-with-tickets-strategy';
	}
}
