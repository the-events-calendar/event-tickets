<?php
/**
 * Stores a ticket's sale price rule and writes the sale price dates it resolves to.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use InvalidArgumentException;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Applies the sale price rule of a Tickets Commerce ticket on an event once the sale price itself is saved.
 *
 * The sale price save reads the dates from the ticket data, not from the ticket, so the resolved dates are written over
 * them afterwards. The rule sent with the ticket data replaces the stored one; a save that does not send the rule keeps
 * the stored one and applies it again, and an empty rule removes it. Removing the sale price removes its rule too.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Sale_Price_Save extends Controller_Contract {
	/**
	 * The ticket data key that carries the sale price rule; its value is a JSON string, an array, or `null` or `''` to
	 * remove it.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const DATA_KEY = 'ticket_sale_price_relative';

	/**
	 * The store of the ticket rules.
	 *
	 * @since TBD
	 *
	 * @var Rule_Store
	 */
	private Rule_Store $rule_store;

	/**
	 * The resolver and writer of the sale price dates.
	 *
	 * @since TBD
	 *
	 * @var Sale_Price_Dates
	 */
	private Sale_Price_Dates $sale_price_dates;

	/**
	 * Sale_Price_Save constructor.
	 *
	 * @since TBD
	 *
	 * @param Container        $container        The DI container.
	 * @param Rule_Store       $rule_store       The store of the ticket rules.
	 * @param Sale_Price_Dates $sale_price_dates The resolver and writer of the sale price dates.
	 */
	public function __construct( Container $container, Rule_Store $rule_store, Sale_Price_Dates $sale_price_dates ) {
		parent::__construct( $container );

		$this->rule_store       = $rule_store;
		$this->sale_price_dates = $sale_price_dates;
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_commerce_after_save_ticket', [ $this, 'save_rule' ] );
	}

	/**
	 * Stores the sale price rule, or removes it, and writes the sale price dates it resolves to.
	 *
	 * @since TBD
	 *
	 * @param int                 $post_id The ticket parent post ID.
	 * @param Ticket_Object       $ticket  The ticket that was saved.
	 * @param array<string,mixed> $data    The ticket data that was saved.
	 *
	 * @return void
	 */
	public function save_rule( int $post_id, Ticket_Object $ticket, array $data ): void {
		if (
			'tribe_events' !== get_post_type( $post_id )
			|| Series_Passes::TICKET_TYPE === get_post_meta( $ticket->ID, Ticket::$type_meta_key, true )
		) {
			return;
		}

		if ( ! $this->sale_price_dates->has_sale_price( $ticket->ID ) || $this->removes_rule( $data ) ) {
			$this->rule_store->remove( $ticket->ID, [ Sale_Price_Rule::KEY ] );

			return;
		}

		$rule = $this->parse_rule( $data[ self::DATA_KEY ] ?? null );

		if ( $rule ) {
			$this->rule_store->save( $ticket->ID, [ Sale_Price_Rule::KEY => $rule->to_array() ] );
		}

		$this->sale_price_dates->write( $ticket->ID, $post_id );
	}

	/**
	 * Registers the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tec_tickets_commerce_after_save_ticket', [ $this, 'save_rule' ], 10, 3 );
	}

	/**
	 * Returns whether the ticket data asks to remove the sale price rule, sending it as `null` or `''`.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The ticket data.
	 *
	 * @return bool Whether the sale price rule is to be removed.
	 */
	private function removes_rule( array $data ): bool {
		return array_key_exists( self::DATA_KEY, $data ) && in_array( $data[ self::DATA_KEY ], [ null, '' ], true );
	}

	/**
	 * Builds a sale price rule from the value the ticket data sends for it.
	 *
	 * An invalid rule is not a request to remove the valid one already stored, so it builds nothing.
	 *
	 * @since TBD
	 *
	 * @param mixed $raw The rule as sent, a JSON string or an array.
	 *
	 * @return Sale_Price_Rule|null The rule, or `null` when nothing valid was sent.
	 */
	private function parse_rule( $raw ): ?Sale_Price_Rule {
		try {
			if ( is_array( $raw ) ) {
				return Sale_Price_Rule::from_array( $raw );
			}

			if ( is_string( $raw ) ) {
				return Sale_Price_Rule::from_json( $raw );
			}
		} catch ( InvalidArgumentException $e ) {
			return null;
		}

		return null;
	}
}
