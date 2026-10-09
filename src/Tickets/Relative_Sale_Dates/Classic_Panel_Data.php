<?php
/**
 * Hands the relative sale dates rules to the classic ticket editor.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

/**
 * Adds the ticket's stored rules to the data the classic ticket edit panel is built from.
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
	 * Adds the ticket's stored rule of each window kind, or `null` for a new ticket or one without that rule, to the panel
	 * data.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data      The data the ticket panel is built from.
	 * @param int                 $post_id   The ID of the post being edited.
	 * @param int|null            $ticket_id The ID of the ticket being edited, or `null` for a new ticket.
	 *
	 * @return array<string,mixed> The panel data, with `relative_sale_dates` and `ticket_sale_price_relative`.
	 */
	public function add_rule_to_panel_data( array $data, int $post_id, ?int $ticket_id ): array {
		$stored = $ticket_id ? $this->rule_store->get( $ticket_id ) : [];

		foreach ( Window_Kind::all() as $kind ) {
			$rule = Rule::from_stored( $stored, $kind );

			$data[ $kind->get_rule_keys()['data'] ] = $rule ? $rule->to_array() : null;
		}

		return $data;
	}
}
