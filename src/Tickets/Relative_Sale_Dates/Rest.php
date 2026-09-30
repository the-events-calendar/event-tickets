<?php
/**
 * Accepts and returns the relative sale dates rules through the ticket REST APIs.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Common\REST\TEC\V1\Collections\PropertiesCollection;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use Tribe__Tickets__Tickets as Tickets;
use WP_REST_Request;

/**
 * Carries the sales window and sale price rules between the REST requests and responses and the ticket save.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Rest {
	/**
	 * The TEC REST API ticket field that carries the sale price rule, next to `sale_price_start_date` and `sale_price_end_date`.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const SALE_PRICE_RULE_FIELD = 'sale_price_relative';

	/**
	 * The key of the sale price rule in the block editor's `ticket[sale_price]` request and `sale_price_data` response.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const SALE_PRICE_DATA_RULE_KEY = 'relative';

	/**
	 * The store of the ticket rules.
	 *
	 * @since TBD
	 *
	 * @var Rule_Store
	 */
	private Rule_Store $rule_store;

	/**
	 * Rest constructor.
	 *
	 * @since TBD
	 *
	 * @param Rule_Store $rule_store The store of the ticket rules.
	 */
	public function __construct( Rule_Store $rule_store ) {
		$this->rule_store = $rule_store;
	}

	/**
	 * Copies the rule the block editor sends in `ticket[relative_sale_dates]` into the ticket data it saves.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $ticket_data The ticket data built from the request body.
	 * @param WP_REST_Request     $request     The request.
	 * @param Tickets             $provider    The provider that saves the ticket.
	 *
	 * @return array<string,mixed> The ticket data, with the rule of a Tickets Commerce ticket.
	 */
	public function map_block_editor_rule( array $ticket_data, WP_REST_Request $request, Tickets $provider ): array {
		$ticket = $request->get_param( 'ticket' );

		if ( ! $provider instanceof Module || ! is_array( $ticket ) || ! array_key_exists( Ticket_Save::DATA_KEY, $ticket ) ) {
			return $ticket_data;
		}

		$ticket_data[ Ticket_Save::DATA_KEY ] = $ticket[ Ticket_Save::DATA_KEY ];

		return $ticket_data;
	}

	/**
	 * Copies the sale price rule the block editor sends in `ticket[sale_price][relative]` into the ticket data it saves.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $ticket_data The ticket data built from the request body.
	 * @param WP_REST_Request     $request     The request.
	 * @param Tickets             $provider    The provider that saves the ticket.
	 *
	 * @return array<string,mixed> The ticket data, with the sale price rule of a Tickets Commerce ticket.
	 */
	public function map_block_editor_sale_price_rule( array $ticket_data, WP_REST_Request $request, Tickets $provider ): array {
		$ticket     = $request->get_param( 'ticket' );
		$sale_price = is_array( $ticket ) ? ( $ticket['sale_price'] ?? null ) : null;

		if ( ! $provider instanceof Module || ! is_array( $sale_price ) || ! array_key_exists( self::SALE_PRICE_DATA_RULE_KEY, $sale_price ) ) {
			return $ticket_data;
		}

		$ticket_data[ Sale_Price_Save::DATA_KEY ] = $sale_price[ self::SALE_PRICE_DATA_RULE_KEY ];

		return $ticket_data;
	}

	/**
	 * Adds the ticket's stored rule, or `null`, to the ticket data the block editor reads.
	 *
	 * The ticket ID comes from the data: the filter also passes the caller's argument, which can be a post or a ticket
	 * object.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The ticket data.
	 *
	 * @return array<string,mixed> The ticket data, with `relative_sale_dates`.
	 */
	public function add_rule_to_block_editor_ticket_data( array $data ): array {
		$data[ Ticket_Save::DATA_KEY ] = empty( $data['id'] ) ? null : $this->get_stored_rule( absint( $data['id'] ) );

		return $data;
	}

	/**
	 * Adds the stored sale price rule, or `null`, to the sale price data of a Tickets Commerce ticket the block editor reads.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The ticket data.
	 *
	 * @return array<string,mixed> The ticket data, with `sale_price_data.relative` for a Tickets Commerce ticket.
	 */
	public function add_sale_price_rule_to_block_editor_ticket_data( array $data ): array {
		$ticket_id = absint( $data['id'] ?? 0 );

		if ( ! $ticket_id || ! is_array( $data['sale_price_data'] ?? null ) || Ticket::POSTTYPE !== get_post_type( $ticket_id ) ) {
			return $data;
		}

		$data['sale_price_data'][ self::SALE_PRICE_DATA_RULE_KEY ] = $this->get_stored_sale_price_rule( $ticket_id );

		return $data;
	}

	/**
	 * Documents `relative_sale_dates` and `sale_price_relative` in the TEC REST API ticket request body and ticket definitions.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $documentation The definition's documentation, in the format supported by Swagger.
	 *
	 * @return array<string,mixed> The documentation, with the rules.
	 */
	public function add_rule_to_definition( array $documentation ): array {
		$properties   = new PropertiesCollection();
		$properties[] = Rule_Parameter::for_sales_window();
		$properties[] = Rule_Parameter::for_sale_price();

		$documentation['allOf'][] = [
			'type'       => 'object',
			'properties' => $properties,
		];

		return $documentation;
	}

	/**
	 * Keeps a rule sent as `null`, which the TEC REST API drops with every other `null` it is sent.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $filtered_data The request data the schema keeps.
	 * @param array<string,mixed> $data          The request data as it was sent.
	 *
	 * @return array<string,mixed> The kept data, with a sales window or sale price rule sent as `null`.
	 */
	public function keep_a_rule_sent_as_null( array $filtered_data, array $data ): array {
		foreach ( [ Ticket_Save::DATA_KEY, self::SALE_PRICE_RULE_FIELD ] as $field ) {
			if ( array_key_exists( $field, $data ) && null === $data[ $field ] ) {
				$filtered_data[ $field ] = null;
			}
		}

		return $filtered_data;
	}

	/**
	 * Adds the stored sales window and sale price rules to a TEC REST API update that leaves them out, so the update
	 * keeps them.
	 *
	 * The TEC REST API sends the rules it receives with the parameters `ticket_add()` saves; this fills in the stored
	 * ones when the request had none.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $ticket_params The parameters `ticket_add()` saves the ticket with.
	 * @param array<string,mixed> $params        The request parameters left for the ticket post.
	 *
	 * @return array<string,mixed> The ticket parameters, with the stored rules the request did not send.
	 */
	public function keep_stored_rules_in_tec_rest_api_update( array $ticket_params, array $params ): array {
		$ticket_id = absint( $params['id'] ?? 0 );

		if ( ! $ticket_id ) {
			return $ticket_params;
		}

		$ticket_params = $this->add_stored_rule( $ticket_params, Ticket_Save::DATA_KEY, fn() => $this->get_stored_rule( $ticket_id ) );

		return $this->add_stored_rule( $ticket_params, Sale_Price_Save::DATA_KEY, fn() => $this->get_stored_sale_price_rule( $ticket_id ) );
	}

	/**
	 * Adds the ticket's stored sales window and sale price rules, or `null`, to a Tickets Commerce ticket the TEC REST API
	 * returns.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $entity The ticket entity.
	 *
	 * @return array<string,mixed> The entity, with `relative_sale_dates` and `sale_price_relative`.
	 */
	public function add_rule_to_tec_rest_api_ticket( array $entity ): array {
		$ticket_id = absint( $entity['id'] ?? 0 );

		$entity[ Ticket_Save::DATA_KEY ]       = $ticket_id ? $this->get_stored_rule( $ticket_id ) : null;
		$entity[ self::SALE_PRICE_RULE_FIELD ] = $ticket_id ? $this->get_stored_sale_price_rule( $ticket_id ) : null;

		return $entity;
	}

	/**
	 * Gets a ticket's stored rule in its canonical form.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return array{start: array{mode: string, value?: int, unit?: int, anchor?: string}, end: array{mode: string, value?: int, unit?: int, anchor?: string}}|null The rule, or `null` when the ticket has no valid rule.
	 */
	private function get_stored_rule( int $ticket_id ): ?array {
		$rule = Rule::from_stored( $this->rule_store->get( $ticket_id ) );

		return $rule ? $rule->to_array() : null;
	}

	/**
	 * Gets a ticket's stored sale price rule in its canonical form.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return array{start: array{mode: string, value?: int, unit?: int}, end: array{mode: string, value?: int, unit?: int}}|null The sale price rule, or `null` when the ticket has no valid one.
	 */
	private function get_stored_sale_price_rule( int $ticket_id ): ?array {
		$rule = Sale_Price_Rule::from_stored( $this->rule_store->get( $ticket_id ) );

		return $rule ? $rule->to_array() : null;
	}

	/**
	 * Adds a ticket's stored rule to the ticket parameters when the request did not send that rule.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>                                       $ticket_params   The parameters `ticket_add()` saves the ticket with.
	 * @param string                                                    $data_key        The ticket data key the save reads the rule from.
	 * @param callable(): (array<string,array<string,int|string>>|null) $get_stored_rule Gets the ticket's stored rule.
	 *
	 * @return array<string,mixed> The ticket parameters, with the stored rule when the request sent none.
	 */
	private function add_stored_rule( array $ticket_params, string $data_key, callable $get_stored_rule ): array {
		if ( array_key_exists( $data_key, $ticket_params ) ) {
			return $ticket_params;
		}

		$stored_rule = $get_stored_rule();

		if ( $stored_rule ) {
			$ticket_params[ $data_key ] = $stored_rule;
		}

		return $ticket_params;
	}
}
