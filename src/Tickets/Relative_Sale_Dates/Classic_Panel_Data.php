<?php
/**
 * Hands the sales window rule to the classic ticket editor.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

/**
 * Adds the ticket's stored rule to the data the classic ticket edit panel is built from.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Classic_Panel_Data {
	/**
	 * The store of the ticket rules.
	 *
	 * @since TBD
	 *
	 * @var Rule_Store
	 */
	private Rule_Store $rule_store;

	/**
	 * Classic_Panel_Data constructor.
	 *
	 * @since TBD
	 *
	 * @param Rule_Store $rule_store The store of the ticket rules.
	 */
	public function __construct( Rule_Store $rule_store ) {
		$this->rule_store = $rule_store;
	}

	/**
	 * Adds the ticket's stored rule, or `null` for a new ticket or one without a rule, to the panel data.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|mixed $data      The data the ticket panel is built from.
	 * @param int                       $post_id   The ID of the post being edited.
	 * @param int|null                  $ticket_id The ID of the ticket being edited, or `null` for a new ticket.
	 *
	 * @return array<string,mixed>|mixed The panel data, with `relative_sale_dates`.
	 */
	public function add_rule_to_panel_data( $data, int $post_id, ?int $ticket_id ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$rule = $ticket_id ? Rule::from_stored( $this->rule_store->get( $ticket_id ), Window_Kind::sales() ) : null;

		$data[ Window_Kind::sales()->get_rule_keys()['data'] ] = $rule ? $rule->to_array() : null;

		return $data;
	}
}
