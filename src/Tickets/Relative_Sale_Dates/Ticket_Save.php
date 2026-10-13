<?php
/**
 * Stores a ticket's rules, its sales window rule and its sale price rule, and writes the dates they resolve to.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use TEC\Tickets\RSVP\V2\Constants as RSVP_V2_Constants;
use Tribe__Date_Utils as Dates;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Error;

/**
 * Applies the rules of a Tickets Commerce ticket on an event when the ticket is saved.
 *
 * Each kind of window has its own rule, sent under the kind's data key. The rule sent with the ticket data replaces the
 * stored one; a save that does not send the rule keeps the stored one and applies it again, and an empty rule removes
 * it. A rule whose window the ticket does not have, such as a sale price rule without a sale price, is removed.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Ticket_Save {
	/**
	 * The ticket data key that carries the sales window rule; its value is a JSON string, an array, or `null` or `''` to
	 * remove it.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const DATA_KEY = 'relative_sale_dates';

	/**
	 * The ticket types relative sale dates never apply to: Series Passes, and RSVPs stored as Tickets Commerce tickets.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	public const EXCLUDED_TICKET_TYPES = [ Series_Passes::TICKET_TYPE, RSVP_V2_Constants::TC_RSVP_TYPE ];

	/**
	 * The store of the ticket rules.
	 *
	 * @since TBD
	 *
	 * @var Rule_Store
	 */
	private Rule_Store $rule_store;

	/**
	 * The writer of the ticket dates.
	 *
	 * @since TBD
	 *
	 * @var Ticket_Dates
	 */
	private Ticket_Dates $ticket_dates;

	/**
	 * The sales window resolver.
	 *
	 * @since TBD
	 *
	 * @var Sale_Window
	 */
	private Sale_Window $sale_window;

	/**
	 * The effects only the sales window has.
	 *
	 * @since TBD
	 *
	 * @var Sales_Window_Effects
	 */
	private Sales_Window_Effects $sales_window_effects;

	/**
	 * The judge of the windows a save would store.
	 *
	 * @since TBD
	 *
	 * @var Window_Validator
	 */
	private Window_Validator $window_validator;

	/**
	 * Ticket_Save constructor.
	 *
	 * @since TBD
	 *
	 * @param Rule_Store           $rule_store           The store of the ticket rules.
	 * @param Sale_Window          $sale_window          The resolver of every kind of window.
	 * @param Ticket_Dates         $ticket_dates         The writer of the ticket dates.
	 * @param Sales_Window_Effects $sales_window_effects The effects only the sales window has.
	 * @param Window_Validator     $window_validator     The judge of the windows a save would store.
	 */
	public function __construct( Rule_Store $rule_store, Sale_Window $sale_window, Ticket_Dates $ticket_dates, Sales_Window_Effects $sales_window_effects, Window_Validator $window_validator ) {
		$this->rule_store           = $rule_store;
		$this->sale_window          = $sale_window;
		$this->ticket_dates         = $ticket_dates;
		$this->sales_window_effects = $sales_window_effects;
		$this->window_validator     = $window_validator;
	}

	/**
	 * Sets the resolved dates on the ticket before the provider saves it, so it writes them with the ticket.
	 *
	 * The block editor sends the start it loaded for a ticket whose start was relative before; a Now start resolves
	 * it to now.
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

		// Only the sales window dates are the ticket's own, which the provider saves with the ticket.
		$rule         = $this->get_rule_to_apply( $ticket->ID ?? 0, $data, Window_Kind::sales() );
		$ticket_start = $ticket->start_date ? trim( $ticket->start_date . ' ' . $ticket->start_time ) : '';
		$window       = $rule ? $this->sale_window->resolve_for_event( $rule, $post_id ) : null;

		if ( ! $window ) {
			return;
		}

		$window = $this->sales_window_effects->apply_now_start( $window, $rule, $ticket_start );

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
	 * Stores the rule of each kind to apply to the ticket, or removes it when the ticket data asks to or the ticket does
	 * not have the kind's window.
	 *
	 * Tickets Commerce has saved the sale price by now, and removed it when it is unchecked or not lower than the price.
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
		if ( ! $this->applies_to_saved_ticket( $ticket_id, $post_id ) ) {
			return;
		}

		$stored = $this->rule_store->get( $ticket_id );

		foreach ( Window_Kind::all() as $kind ) {
			if ( ! $kind->is_enabled_for_ticket( $ticket_id ) || $this->removes_rule( $data, $kind ) ) {
				if ( $this->rule_store->remove_rule( $ticket_id, $kind ) ) {
					$this->sales_window_effects->release_end( $ticket_id, $kind, ! empty( $data[ $kind->get_submitted_fields()['end']['date'] ] ) );
					$this->sales_window_effects->forget_timezone( $ticket_id, $kind );
				}

				continue;
			}

			$rule = $this->get_rule_to_apply( $ticket_id, $data, $kind );

			if ( $rule ) {
				$this->rule_store->save_rule( $ticket_id, $rule );
				$this->sales_window_effects->flag_end_left_to_ticket( $ticket_id, Rule::from_stored( $stored, $kind ), $rule );
			}
		}
	}

	/**
	 * Writes the dates the stored rule of each kind resolves to once the ticket is saved.
	 *
	 * `ticket_add()` replaces an empty start or end date with a default after the provider has saved the ticket,
	 * so the dates set before the save do not survive it when the ticket data leaves the start or end empty. Tickets
	 * Commerce writes the submitted sale price dates during the save, so the resolved ones go over them afterwards.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 * @param int $post_id   The ticket parent post ID.
	 *
	 * @return void
	 */
	public function write_resolved_dates( int $ticket_id, int $post_id ): void {
		if ( ! $this->applies_to_saved_ticket( $ticket_id, $post_id ) ) {
			return;
		}

		$stored = $this->rule_store->get( $ticket_id );

		foreach ( Window_Kind::all() as $kind ) {
			$rule = Rule::from_stored( $stored, $kind );

			if ( $rule ) {
				$this->ticket_dates->write( $ticket_id, $post_id, $rule );
			}
		}
	}

	/**
	 * Rejects ticket data whose rule of any kind is invalid, whose window ends before it starts, or whose window starts
	 * outside its parent window.
	 *
	 * Each kind is judged in turn, the parent first, and the first error is returned: a sales window it rejects is not
	 * judged again as the parent of the sale price. A kind the save drops, such as a sale price that is unchecked or not
	 * lower than the price, is not judged.
	 *
	 * @since TBD
	 *
	 * @param true|WP_Error       $valid   `true`, or the error an earlier callback rejected the data with.
	 * @param int                 $post_id The ticket parent post ID.
	 * @param array<string,mixed> $data    The ticket data about to be saved.
	 *
	 * @return true|WP_Error `true` when every window is valid or does not apply, the first error otherwise.
	 */
	public function validate_ticket_data( $valid, int $post_id, array $data ) {
		if (
			is_wp_error( $valid )
			|| Module::class !== ( $data['ticket_provider'] ?? Module::class )
			|| ! $this->applies_to( $post_id, $data['ticket_type'] ?? 'default' )
		) {
			return $valid;
		}

		foreach ( Window_Kind::all() as $kind ) {
			$error = $this->validate_window( $post_id, $data, $kind );

			if ( $error ) {
				return $error;
			}
		}

		return $valid;
	}

	/**
	 * Returns whether relative sale dates apply to a ticket of the given type on the given post.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id     The ticket parent post ID.
	 * @param string $ticket_type The ticket type.
	 *
	 * @return bool Whether the ticket is an event ticket of a type the rule applies to.
	 */
	private function applies_to( int $post_id, string $ticket_type ): bool {
		return 'tribe_events' === get_post_type( $post_id ) && ! in_array( $ticket_type, self::EXCLUDED_TICKET_TYPES, true );
	}

	/**
	 * Returns whether relative sale dates apply to a saved ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 * @param int $post_id   The ticket parent post ID.
	 *
	 * @return bool Whether the ticket is a Tickets Commerce ticket the rule applies to.
	 */
	private function applies_to_saved_ticket( int $ticket_id, int $post_id ): bool {
		return Ticket::POSTTYPE === get_post_type( $ticket_id )
			&& $this->applies_to( $post_id, get_post_meta( $ticket_id, Ticket::$type_meta_key, true ) ?: 'default' );
	}

	/**
	 * Returns whether the ticket data asks to remove the rule of a kind, sending it as `null` or `''`.
	 *
	 * A front-end ticket form, such as Community Events', offers no sales window options, so the dates it sends are the
	 * ones the person set: for a kind such a form removes, a save from it that does not send the rule removes the stored
	 * one. The classic editor save passes on the `is_admin` that `tickets.js` sends, false for such a form, in the ticket
	 * data; a REST request carries none, so leaving the rule out keeps it, whatever its query string holds.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The ticket data.
	 * @param Window_Kind         $kind The kind of the rule.
	 *
	 * @return bool Whether the rule is to be removed.
	 */
	private function removes_rule( array $data, Window_Kind $kind ): bool {
		$key = $kind->get_rule_keys()['data'];

		if ( array_key_exists( $key, $data ) ) {
			return in_array( $data[ $key ], [ null, '' ], true );
		}

		if ( ! $kind->is_removed_by_front_end_form() ) {
			return false;
		}

		return array_key_exists( 'is_admin', $data ) && ! tribe_is_truthy( $data['is_admin'] );
	}

	/**
	 * Gets the rule of a kind to apply to the ticket: the one in the ticket data, or else the stored one.
	 *
	 * A save that does not know about rules, like a plugin calling `ticket_add()` or an update of the price only, does
	 * not send one, and an invalid rule is not a request to remove the valid one already stored.
	 *
	 * @since TBD
	 *
	 * @param int                 $ticket_id The ticket post ID, or `0` for a ticket not saved yet.
	 * @param array<string,mixed> $data      The ticket data.
	 * @param Window_Kind         $kind      The kind of the rule.
	 *
	 * @return Rule|null The rule, or `null` when the ticket data removes it or neither holds a valid one.
	 */
	private function get_rule_to_apply( int $ticket_id, array $data, Window_Kind $kind ): ?Rule {
		if ( $this->removes_rule( $data, $kind ) ) {
			return null;
		}

		$rule = Rule::from_raw( $data[ $kind->get_rule_keys()['data'] ] ?? null, $kind );

		if ( $rule || ! $ticket_id ) {
			return $rule;
		}

		return Rule::from_stored( $this->rule_store->get( $ticket_id ), $kind );
	}

	/**
	 * Judges the window of one kind the ticket data would save.
	 *
	 * The rule judged is the one the save applies: the one sent, or else the one stored for the ticket. A rule sent that
	 * cannot be read is rejected, as the save would keep the stored one while the admin who sent it expects it to apply.
	 *
	 * @since TBD
	 *
	 * @param int                 $post_id The ticket parent post ID.
	 * @param array<string,mixed> $data    The ticket data about to be saved.
	 * @param Window_Kind         $kind    The kind of window to judge.
	 *
	 * @return WP_Error|null The error of the kind that rejects the window, or `null` when it is valid or not judged.
	 */
	private function validate_window( int $post_id, array $data, Window_Kind $kind ): ?WP_Error {
		if ( $this->removes_rule( $data, $kind ) || ! $kind->is_saved_with( $data ) ) {
			return null;
		}

		$key = $kind->get_rule_keys()['data'];

		if ( isset( $data[ $key ] ) && ! Rule::from_raw( $data[ $key ], $kind ) ) {
			return $kind->get_error( 'invalidRule' );
		}

		$ticket_id = absint( $data['ticket_id'] ?? 0 );
		$parent    = $kind->get_parent();

		return $this->window_validator->validate(
			$post_id,
			$data,
			$kind,
			$this->get_rule_to_apply( $ticket_id, $data, $kind ),
			$parent ? $this->get_rule_to_apply( $ticket_id, $data, $parent ) : null
		);
	}

}
