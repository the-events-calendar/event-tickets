<?php
/**
 * Accepts and returns the sales window rule through the ticket REST APIs.
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
use TEC\Common\REST\TEC\V1\Collections\PropertiesCollection;
use TEC\Tickets\Commerce\Module;
use Tribe__Tickets__Tickets as Tickets;
use WP_REST_Request;

/**
 * Carries the rule between the REST requests and responses and the ticket save.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Rest extends Controller_Contract {
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
	 * @param Container  $container  The DI container.
	 * @param Rule_Store $rule_store The store of the ticket rules.
	 */
	public function __construct( Container $container, Rule_Store $rule_store ) {
		parent::__construct( $container );

		$this->rule_store = $rule_store;
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_rest_single_ticket_add_data', [ $this, 'map_block_editor_rule' ] );
		remove_filter( 'tribe_tickets_rest_api_ticket_data', [ $this, 'add_rule_to_block_editor_ticket_data' ] );
		remove_filter( 'tec_rest_swagger_ticket_request_body_definition', [ $this, 'add_rule_to_definition' ] );
		remove_filter( 'tec_rest_swagger_ticket_definition', [ $this, 'add_rule_to_definition' ] );
		remove_filter( 'tec_rest_schema_filter', [ $this, 'keep_a_rule_sent_as_null' ] );
		remove_filter( 'tec_tickets_rest_ticket_upsert_params', [ $this, 'map_tec_rest_api_rule' ] );
		remove_filter( 'tec_rest_v1_tec_tc_ticket_transform_entity', [ $this, 'add_rule_to_tec_rest_api_ticket' ] );
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
	 * Documents `relative_sale_dates` in the TEC REST API ticket request body and ticket definitions.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $documentation The definition's documentation, in the format supported by Swagger.
	 *
	 * @return array<string,mixed> The documentation, with the rule.
	 */
	public function add_rule_to_definition( array $documentation ): array {
		$properties   = new PropertiesCollection();
		$properties[] = new Rule_Parameter();

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
	 * @return array<string,mixed> The kept data, with a rule sent as `null`.
	 */
	public function keep_a_rule_sent_as_null( array $filtered_data, array $data ): array {
		if ( array_key_exists( Ticket_Save::DATA_KEY, $data ) && null === $data[ Ticket_Save::DATA_KEY ] ) {
			$filtered_data[ Ticket_Save::DATA_KEY ] = null;
		}

		return $filtered_data;
	}

	/**
	 * Moves the rule sent to the TEC REST API from the post parameters to the parameters `ticket_add()` saves.
	 *
	 * An update is partial: one that leaves the rule out keeps the stored one, and one that sends `null` removes it.
	 *
	 * @since TBD
	 *
	 * @param array{post_params: array<string,mixed>, ticket_params: array<string,mixed>} $upsert_params  The post and ticket parameters.
	 * @param array<string,mixed>                                                         $request_params The request parameters.
	 *
	 * @return array{post_params: array<string,mixed>, ticket_params: array<string,mixed>} The parameters, with the rule in the ticket ones.
	 */
	public function map_tec_rest_api_rule( array $upsert_params, array $request_params ): array {
		unset( $upsert_params['post_params'][ Ticket_Save::DATA_KEY ] );

		if ( array_key_exists( Ticket_Save::DATA_KEY, $request_params ) ) {
			$upsert_params['ticket_params'][ Ticket_Save::DATA_KEY ] = $request_params[ Ticket_Save::DATA_KEY ] ?? '';

			return $upsert_params;
		}

		$stored_rule = empty( $request_params['id'] ) ? null : $this->get_stored_rule( absint( $request_params['id'] ) );

		if ( $stored_rule ) {
			$upsert_params['ticket_params'][ Ticket_Save::DATA_KEY ] = $stored_rule;
		}

		return $upsert_params;
	}

	/**
	 * Adds the ticket's stored rule, or `null`, to a Tickets Commerce ticket the TEC REST API returns.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $entity The ticket entity.
	 *
	 * @return array<string,mixed> The entity, with `relative_sale_dates`.
	 */
	public function add_rule_to_tec_rest_api_ticket( array $entity ): array {
		$entity[ Ticket_Save::DATA_KEY ] = empty( $entity['id'] ) ? null : $this->get_stored_rule( absint( $entity['id'] ) );

		return $entity;
	}

	/**
	 * Registers the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tec_tickets_rest_single_ticket_add_data', [ $this, 'map_block_editor_rule' ], 10, 3 );
		add_filter( 'tribe_tickets_rest_api_ticket_data', [ $this, 'add_rule_to_block_editor_ticket_data' ] );
		add_filter( 'tec_rest_swagger_ticket_request_body_definition', [ $this, 'add_rule_to_definition' ] );
		add_filter( 'tec_rest_swagger_ticket_definition', [ $this, 'add_rule_to_definition' ] );
		add_filter( 'tec_rest_schema_filter', [ $this, 'keep_a_rule_sent_as_null' ], 10, 2 );
		add_filter( 'tec_tickets_rest_ticket_upsert_params', [ $this, 'map_tec_rest_api_rule' ], 10, 2 );
		add_filter( 'tec_rest_v1_tec_tc_ticket_transform_entity', [ $this, 'add_rule_to_tec_rest_api_ticket' ] );
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
		try {
			$rule = Rule::from_array( $this->rule_store->get( $ticket_id ) );
		} catch ( InvalidArgumentException $e ) {
			return null;
		}

		return [
			'start' => $rule->get_start(),
			'end'   => $rule->get_end(),
		];
	}
}
