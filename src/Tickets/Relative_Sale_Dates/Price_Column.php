<?php
/**
 * Hands the price column of the classic tickets list what its script needs to follow a relative sale price.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Price_Column.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Price_Column {
	/**
	 * The markup the price column keeps of a price, as `editor/column-body-price` filters it.
	 *
	 * @since TBD
	 *
	 * @var array<string,array<string,array<empty>>>
	 */
	private const ALLOWED_HTML = [
		'span' => [
			'class' => [],
		],
		'ins'  => [
			'class' => [],
		],
		'del'  => [
			'class' => [],
		],
		'bdi'  => [],
	];

	/**
	 * The store of the ticket rules.
	 *
	 * @since TBD
	 *
	 * @var Rule_Store
	 */
	private Rule_Store $rule_store;

	/**
	 * Price_Column constructor.
	 *
	 * @since TBD
	 *
	 * @param Rule_Store $rule_store The store of the ticket rules.
	 */
	public function __construct( Rule_Store $rule_store ) {
		$this->rule_store = $rule_store;
	}

	/**
	 * Adds to the price column of a ticket row the sale price rule, the stored sale dates and both prices it can show.
	 *
	 * The prices are drawn here as the column draws them, so the script only picks one. The attributes are set on every
	 * row, empty for a ticket without a sale price rule: the template merges each row's context into the values the
	 * next row inherits.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|mixed $context The context of the price column template.
	 *
	 * @return array<string,mixed>|mixed The context, with `relative_sale_price_attributes`, or what was passed in when it
	 *                                   is not an array.
	 */
	public function filter_context( $context ) {
		if ( ! is_array( $context ) ) {
			return $context;
		}

		$context['relative_sale_price_attributes'] = [];
		$ticket                                    = $context['ticket'] ?? null;

		if ( ! $ticket instanceof Ticket_Object || ! ( $context['provider_obj'] ?? null ) instanceof Module ) {
			return $context;
		}

		// Tickets Commerce caches a ticket with the ID it was first loaded with, which can be a numeric string.
		$ticket_id = absint( $ticket->ID );
		$kind      = Window_Kind::sale_price();
		$rule      = $kind->is_enabled_for_ticket( $ticket_id ) ? Rule::from_stored( $this->rule_store->get( $ticket_id ), $kind ) : null;

		if ( ! $rule ) {
			return $context;
		}

		$context['relative_sale_price_attributes'] = [
			'data-sale-price-rule'  => $rule->to_json(),
			'data-sale-price-start' => $this->get_stored_date( $ticket_id, Ticket::$sale_price_start_date_key ),
			'data-sale-price-end'   => $this->get_stored_date( $ticket_id, Ticket::$sale_price_end_date_key ),
			'data-price-on-sale'    => $this->render_price( true, $ticket_id ),
			'data-price-regular'    => $this->render_price( false, $ticket_id ),
		];

		return $context;
	}

	/**
	 * Reads a stored sale price date of a ticket.
	 *
	 * @since TBD
	 *
	 * @param int    $ticket_id The ticket post ID.
	 * @param string $meta_key  The meta key of the date.
	 *
	 * @return string The date, `Y-m-d`, or empty when the ticket has none.
	 */
	private function get_stored_date( int $ticket_id, string $meta_key ): string {
		$date = get_post_meta( $ticket_id, $meta_key, true );

		return is_string( $date ) ? $date : '';
	}

	/**
	 * Draws the price of a ticket as the price column does, on sale or not.
	 *
	 * @since TBD
	 *
	 * @param bool $on_sale   Whether to draw the sale price.
	 * @param int  $ticket_id The ticket post ID.
	 *
	 * @return string The price HTML.
	 */
	private function render_price( bool $on_sale, int $ticket_id ): string {
		$tickets       = tribe( Ticket::class );
		$regular_price = Value::create( $tickets->get_regular_price( $ticket_id ) );
		$html          = $tickets->get_template()->template(
			'price',
			[
				'on_sale'       => $on_sale,
				'price'         => $on_sale ? Value::create( $tickets->get_sale_price( $ticket_id ) ) : $regular_price,
				'regular_price' => $regular_price,
			],
			false
		);

		return is_string( $html ) ? wp_kses( $html, self::ALLOWED_HTML ) : '';
	}
}
