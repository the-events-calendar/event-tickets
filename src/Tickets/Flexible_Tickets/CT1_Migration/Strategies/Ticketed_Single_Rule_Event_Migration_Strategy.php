<?php
/**
 * Handles the migration of Recurring Event with 1 recurrence rule and one or more non-RSVP tickets attached.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies;
 */

namespace TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies;

use TEC\Events\Custom_Tables\V1\Migration\Migration_Exception;
use TEC\Events\Custom_Tables\V1\Migration\Reports\Event_Report;
use TEC\Events\Custom_Tables\V1\Migration\Strategies\Strategy_Interface;
use TEC\Events\Custom_Tables\V1\Migration\String_Dictionary;
use TEC\Events\Custom_Tables\V1\Traits\With_String_Dictionary;
use TEC\Events_Pro\Custom_Tables\V1\Migration\Strategy\Single_Rule_Event_Migration_Strategy;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use WP_Post;

/**
 * Class Ticketed_Single_Rule_Event_Migration_Strategy.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies;
 */
class Ticketed_Single_Rule_Event_Migration_Strategy
	extends Single_Rule_Event_Migration_Strategy
	implements Strategy_Interface {
	use With_String_Dictionary;
	use Ticketed_Recurring_Event_Strategy_Trait;

	/**
	 * Returns this strategy's slug.
	 *
	 * @since TBD
	 *
	 * @return string The slug of the strategy.
	 */
	public static function get_slug() {
		return 'tec-tickets-recurring-single-rule-with-tickets-strategy';
	}

	/**
	 * Ticketed_Single_Rule_Event_Migration_Strategy constructor.
	 *
	 * since TBD
	 *
	 * @param int  $post_id The post ID of the Event to migrate.
	 * @param bool $dry_run Whether the migration should actually commit information or not.
	 *
	 * @throws Migration_Exception If the post is not an Event or the Event is not Recurring, or the Event has no
	 *                             tickets.
	 */
	public function __construct( $post_id, $dry_run ) {
		parent::__construct( $post_id, $dry_run );

		if ( ! count( $this->get_ticket_ids( $post_id ) ) ) {
			throw new Migration_Exception( 'Recurring Event has no tickets.' );
		}
	}

	/**
	 * Applies the strategy to the given Event and updates the Event_Report.
	 *
	 * @since TBD
	 *
	 * @param Event_Report $event_report The Event_Report to update.
	 *
	 * @throws Migration_Exception
	 */
	public function apply( Event_Report $event_report ): Event_Report {
		parent::apply( $event_report );

		if ( $event_report->status !== 'success' ) {
			return $event_report;
		}

		$strings = tribe( String_Dictionary::class );

		$series = tec_series()->where( 'event_post_id', $this->post_id )->first_id();

		if ( $series === null ) {
			throw new Migration_Exception( sprintf(
				$strings->get( 'migration-failure-series-not-found' ),
				$this->get_event_link_markup( $this->post_id )
			) );
		}

		$this->ensure_series_ticketable();

		[ $moved_tickets, $moved_attendees ] = $this->move_tickets_to_series( $series );

		$this->set_default_ticket_provider( $series );
		$this->set_global_capacity( $series );

		$event_report->set( 'moved_tickets', $moved_tickets );
		$event_report->set( 'moved_attendees', $moved_attendees );

		return $event_report;
	}
}
