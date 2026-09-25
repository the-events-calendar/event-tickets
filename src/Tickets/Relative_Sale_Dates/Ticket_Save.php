<?php
/**
 * Stores a ticket's sales window rule and writes the dates it resolves to.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use Tribe__Date_Utils as Dates;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Error;

/**
 * Applies the rule sent with a Tickets Commerce ticket on an event when the ticket is saved.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Ticket_Save extends Controller_Contract {
	/**
	 * The ticket data key that carries the rule; its value is a JSON string or an array.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const DATA_KEY = 'relative_sale_dates';

	/**
	 * The store of the ticket rules.
	 *
	 * @since TBD
	 *
	 * @var Rule_Store
	 */
	private Rule_Store $rule_store;

	/**
	 * The resolver and writer of the ticket dates.
	 *
	 * @since TBD
	 *
	 * @var Ticket_Dates
	 */
	private Ticket_Dates $ticket_dates;

	/**
	 * Ticket_Save constructor.
	 *
	 * @since TBD
	 *
	 * @param Container    $container    The DI container.
	 * @param Rule_Store   $rule_store   The store of the ticket rules.
	 * @param Ticket_Dates $ticket_dates The resolver and writer of the ticket dates.
	 */
	public function __construct( Container $container, Rule_Store $rule_store, Ticket_Dates $ticket_dates ) {
		parent::__construct( $container );

		$this->rule_store   = $rule_store;
		$this->ticket_dates = $ticket_dates;
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_ticket_pre_save', [ $this, 'set_ticket_dates' ] );
		remove_action( 'tec_tickets_ticket_upserted', [ $this, 'save_rule' ] );
		remove_filter( 'tec_tickets_ticket_data_validation', [ $this, 'validate_ticket_data' ] );
	}

	/**
	 * Sets the resolved dates on the ticket before the provider saves it, so it writes them with the ticket.
	 *
	 * @since TBD
	 *
	 * @param int                 $post_id The ticket parent post ID.
	 * @param Ticket_Object       $ticket  The ticket that is being saved.
	 * @param array<string,mixed> $data    The ticket data that is being saved.
	 *
	 * @return void
	 */
	public function set_ticket_dates( int $post_id, Ticket_Object $ticket, array $data ): void {
		if ( Module::class !== $ticket->provider_class || ! $this->applies_to( $post_id, $data['ticket_type'] ?? 'default' ) ) {
			return;
		}

		$rule   = $this->get_rule( $data );
		$window = $rule ? $this->ticket_dates->resolve( $post_id, $rule ) : null;

		if ( ! $window ) {
			return;
		}

		$start = $window->get_start();
		if ( $start ) {
			$ticket->start_date = $start->format( Dates::DBDATEFORMAT );
			$ticket->start_time = $start->format( Dates::DBTIMEFORMAT );
		}

		$end = $window->get_end();
		if ( $end ) {
			$ticket->end_date = $end->format( Dates::DBDATEFORMAT );
			$ticket->end_time = $end->format( Dates::DBTIMEFORMAT );
		}
	}

	/**
	 * Stores the rule, or removes it, and writes the resolved dates again once the ticket is saved.
	 *
	 * `ticket_add()` replaces an empty start or end date with a default after the provider has saved the ticket,
	 * so the dates set before the save do not survive it when the rule resolves an end the data left empty.
	 *
	 * @since TBD
	 *
	 * @param int                 $ticket_id The ticket post ID.
	 * @param int                 $post_id   The ticket parent post ID.
	 * @param array<string,mixed> $data      The ticket data that was saved.
	 *
	 * @return void
	 */
	public function save_rule( int $ticket_id, int $post_id, array $data ): void {
		if (
			Ticket::POSTTYPE !== get_post_type( $ticket_id )
			|| ! $this->applies_to( $post_id, get_post_meta( $ticket_id, '_type', true ) ?: 'default' )
		) {
			return;
		}

		$rule = $this->get_rule( $data );

		if ( ! $rule ) {
			$this->rule_store->remove( $ticket_id, [ 'start', 'end' ] );

			return;
		}

		$this->rule_store->save(
			$ticket_id,
			[
				'start' => $rule->get_start(),
				'end'   => $rule->get_end(),
			]
		);

		$this->ticket_dates->write( $ticket_id, $post_id, $rule );
	}

	/**
	 * Rejects ticket data whose rule is invalid or whose sales window does not start before it ends.
	 *
	 * An end the rule leaves to the ticket, in `specific` mode, is judged with the date submitted for it, and rejected
	 * when none was.
	 *
	 * @since TBD
	 *
	 * @param true|WP_Error       $valid   `true`, or the error an earlier callback rejected the data with.
	 * @param int                 $post_id The ticket parent post ID.
	 * @param array<string,mixed> $data    The ticket data about to be saved.
	 *
	 * @return true|WP_Error `true` when the sales window is valid or does not apply, the error otherwise.
	 */
	public function validate_ticket_data( $valid, int $post_id, array $data ) {
		if (
			is_wp_error( $valid )
			|| empty( $data[ self::DATA_KEY ] )
			|| Module::class !== ( $data['ticket_provider'] ?? Module::class )
			|| ! $this->applies_to( $post_id, $data['ticket_type'] ?? 'default' )
		) {
			return $valid;
		}

		$rule = $this->get_rule( $data );

		if ( ! $rule ) {
			return $this->get_invalid_window_error();
		}

		$event_dates = $this->ticket_dates->get_event_dates( $post_id );
		$window      = $this->ticket_dates->resolve( $post_id, $rule );

		if ( ! $event_dates || ! $window ) {
			return $valid;
		}

		$timezone       = $event_dates[0]->getTimezone();
		$specific_start = Rule::MODE_SPECIFIC === $rule->get_start()['mode'];
		$specific_end   = Rule::MODE_SPECIFIC === $rule->get_end()['mode'];
		$start          = $specific_start ? $this->get_submitted_date( $data, 'start', $timezone ) : $window->get_start();
		$end            = $specific_end ? $this->get_submitted_date( $data, 'end', $timezone ) : $window->get_end();

		// Without its date, `ticket_add()` would default a specific end and the window could not be checked.
		if ( ( $specific_start && ! $start ) || ( $specific_end && ! $end ) ) {
			return $this->get_invalid_window_error();
		}

		return ( new Resolved_Window( $start, $end ) )->is_valid() ? $valid : $this->get_invalid_window_error();
	}

	/**
	 * Registers the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tec_tickets_ticket_pre_save', [ $this, 'set_ticket_dates' ], 10, 3 );
		// Before Ticket_Actions schedules the sales actions from the ticket dates, at 1000.
		add_action( 'tec_tickets_ticket_upserted', [ $this, 'save_rule' ], 10, 3 );
		add_filter( 'tec_tickets_ticket_data_validation', [ $this, 'validate_ticket_data' ], 10, 3 );
	}

	/**
	 * Returns whether relative sale dates apply to a ticket of the given type on the given post.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id     The ticket parent post ID.
	 * @param string $ticket_type The ticket type.
	 *
	 * @return bool Whether the ticket is an event ticket that is not a Series Pass.
	 */
	private function applies_to( int $post_id, string $ticket_type ): bool {
		return 'tribe_events' === get_post_type( $post_id ) && Series_Passes::TICKET_TYPE !== $ticket_type;
	}

	/**
	 * Reads the rule from the ticket data.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The ticket data.
	 *
	 * @return Rule|null The rule, or `null` when the data has no valid rule.
	 */
	private function get_rule( array $data ): ?Rule {
		$raw = $data[ self::DATA_KEY ] ?? null;

		try {
			if ( is_array( $raw ) ) {
				return Rule::from_array( $raw );
			}

			if ( is_string( $raw ) && '' !== $raw ) {
				return Rule::from_json( $raw );
			}
		} catch ( InvalidArgumentException $e ) {
			return null;
		}

		return null;
	}

	/**
	 * Gets the date submitted for one end of the sales window, read the way `ticket_add()` reads it.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data     The ticket data.
	 * @param string              $end      The end of the window, `start` or `end`.
	 * @param DateTimeZone        $timezone The event timezone.
	 *
	 * @return DateTimeImmutable|null The submitted date, or `null` when none was submitted or it cannot be read.
	 */
	private function get_submitted_date( array $data, string $end, DateTimeZone $timezone ): ?DateTimeImmutable {
		$date = $data[ "ticket_{$end}_date" ] ?? '';
		$time = $data[ "ticket_{$end}_time" ] ?? '';

		if ( ! is_string( $date ) || '' === $date ) {
			return null;
		}

		// A date the datepicker format cannot read comes back `false`, which `ticket_add()` would save as 1970.
		$date = Dates::maybe_format_from_datepicker( $date );

		if ( ! is_string( $date ) || '' === $date ) {
			return null;
		}

		try {
			return new DateTimeImmutable( trim( $date . ' ' . ( is_string( $time ) ? $time : '' ) ), $timezone );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Gets the error that rejects a sales window that does not start before it ends.
	 *
	 * @since TBD
	 *
	 * @return WP_Error The error, with a 400 status for REST responses.
	 */
	private function get_invalid_window_error(): WP_Error {
		return new WP_Error(
			'tec_tickets_relative_sale_dates_invalid_window',
			__( 'Ticket sales cannot end before they start. Please adjust the sales window.', 'event-tickets' ),
			[ 'status' => 400 ]
		);
	}
}
