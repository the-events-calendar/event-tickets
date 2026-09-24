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
use Exception;
use InvalidArgumentException;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use Tribe__Date_Utils as Dates;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Timezones as Timezones;

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
	 * The sales window resolver.
	 *
	 * @since TBD
	 *
	 * @var Sale_Window
	 */
	private Sale_Window $sale_window;

	/**
	 * Ticket_Save constructor.
	 *
	 * @since TBD
	 *
	 * @param Container   $container   The DI container.
	 * @param Rule_Store  $rule_store  The store of the ticket rules.
	 * @param Sale_Window $sale_window The sales window resolver.
	 */
	public function __construct( Container $container, Rule_Store $rule_store, Sale_Window $sale_window ) {
		parent::__construct( $container );

		$this->rule_store  = $rule_store;
		$this->sale_window = $sale_window;
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
		$window = $rule ? $this->resolve( $post_id, $rule ) : null;

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

		$window = $this->resolve( $post_id, $rule );

		if ( ! $window ) {
			return;
		}

		$start = $window->get_start();
		if ( $start ) {
			update_post_meta( $ticket_id, Ticket::START_DATE_META_KEY, $start->format( Dates::DBDATEFORMAT ) );
			update_post_meta( $ticket_id, Ticket::START_TIME_META_KEY, $start->format( Dates::DBTIMEFORMAT ) );
		}

		$end = $window->get_end();
		if ( $end ) {
			update_post_meta( $ticket_id, Ticket::END_DATE_META_KEY, $end->format( Dates::DBDATEFORMAT ) );
			update_post_meta( $ticket_id, Ticket::END_TIME_META_KEY, $end->format( Dates::DBTIMEFORMAT ) );
		}
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
	 * Resolves a rule against the event's dates.
	 *
	 * @since TBD
	 *
	 * @param int  $post_id The event post ID.
	 * @param Rule $rule    The sales window rule.
	 *
	 * @return Resolved_Window|null The resolved window, or `null` when the event has no valid dates.
	 */
	private function resolve( int $post_id, Rule $rule ): ?Resolved_Window {
		$start = get_post_meta( $post_id, '_EventStartDate', true );
		$end   = get_post_meta( $post_id, '_EventEndDate', true );

		if ( ! is_string( $start ) || '' === $start || ! is_string( $end ) || '' === $end ) {
			return null;
		}

		$timezone = Timezones::build_timezone_object( get_post_meta( $post_id, '_EventTimezone', true ) ?: null );

		try {
			$event_start = new DateTimeImmutable( $start, $timezone );
			$event_end   = new DateTimeImmutable( $end, $timezone );
		} catch ( Exception $e ) {
			return null;
		}

		return $this->sale_window->resolve( $rule, $event_start, $event_end );
	}
}
