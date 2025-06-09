<?php
/**
 * Handles the migration of Recurring Events, with one or more recurrence rules, that have RSVP tickets applied.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes\CT1_Migration\Strategies;
 */

namespace TEC\Tickets\Flexible_Tickets\CT1_Migration\Strategies;

use TEC\Events\Custom_Tables\V1\Migration\Expected_Migration_Exception;
use TEC\Events\Custom_Tables\V1\Migration\Migration_Exception;
use TEC\Events\Custom_Tables\V1\Migration\Reports\Event_Report;
use TEC\Events\Custom_Tables\V1\Migration\Strategies\Strategy_Interface;
use TEC\Events\Custom_Tables\V1\Migration\String_Dictionary;
use TEC\Events\Custom_Tables\V1\Traits\With_String_Dictionary;
use Tribe__Events__Main as TEC;

/**
 * Class RSVP_Ticketed_Recurring_Event_Strategy.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes\CT1_Migration\Strategies;
 */
class RSVP_Ticketed_Recurring_Event_Strategy implements Strategy_Interface {
	use With_String_Dictionary;

	/**
	 * The ID of the Event being migrated.
	 *
	 * @since 5.8.0
	 *
	 * @var int
	 */
	protected int $post_id;

	/**
	 * Whether the migration is being run in dry-run mode.
	 *
	 * @since 5.8.0
	 *
	 * @var bool
	 */
	protected bool $dry_run;

	/**
	 * Returns this strategy's slug.
	 *
	 * @since 5.8.0
	 *
	 * @return string The slug of the strategy.
	 */
	public static function get_slug() {
		return 'tec-tickets-recurring-with-rsvp-strategy';
	}

	/**
	 * RSVP_Ticketed_Recurring_Event_Strategy constructor.
	 *
	 * since 5.8.0
	 *
	 * @param int $post_id The ID of the Event.
	 *
	 * @throws Migration_Exception If the post is not an Event or the Event is not Recurring.
	 */
	public function __construct( int $post_id, bool $dry_run ) {
		$this->post_id = $post_id;
		$this->dry_run = $dry_run;

		$post_type = get_post_type( $post_id );

		if ( $post_type !== TEC::POSTTYPE ) {
			throw new Migration_Exception( 'Post is not an Event.' );
		}

		$recurrence_meta = get_post_meta( $post_id, '_EventRecurrence', true );

		if ( ! ( is_array( $recurrence_meta ) && isset( $recurrence_meta['rules'] ) ) ) {
			throw new Migration_Exception( 'Event Post is not recurring.' );
		}

		if ( ! tribe_tickets( 'rsvp' )->where( 'event', $post_id )->count() ) {
			throw new Migration_Exception( 'Event Post does not have RSVP tickets.' );
		}
	}

	/**
	 * Applies the strategy to the Event, blocking its migration and offering the user a way to resolve the issue.
	 *
	 * @since 5.8.0
	 *
	 * @param Event_Report $event_report A reference to the report.
	 *
	 * @return void The Event report is updated with the migration results.
	 *
	 * @throws Expected_Migration_Exception Always thrown to block the migration of Recurring Events with RSVP tickets.
	 */
	public function apply( Event_Report $event_report ) {
		$event_report->set_tickets_provider( 'RSVP' );
		$text = tribe( String_Dictionary::class );

		$message = sprintf(
			$text->get( 'migration-error-recurring-with-rsvp-tickets' ),
			$this->get_event_link_markup( $this->post_id ),
			'<a target="_blank" href="https://evnt.is/r-rsvp">',
			'</a>'
		);

		throw new Expected_Migration_Exception( $message );
	}
}
