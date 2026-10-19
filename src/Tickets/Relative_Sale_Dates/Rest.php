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
use TEC\Common\REST\TEC\V1\Contracts\OpenAPI_Schema;
use TEC\Common\REST\TEC\V1\Parameter_Types\Definition_Parameter;
use TEC\Common\StellarWP\Arrays\Arr;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Ticket;
use Tribe__Tickets__Tickets as Tickets;
use WP_REST_Request;

/**
 * Carries the rule of each window kind between the REST requests and responses and the ticket save.
 *
 * Each kind names the keys that carry its rule in each API, and which tickets the block editor gets its rule for; the
 * code that reads and writes them is the same for every kind.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Rest {
	/**
	 * The store of the ticket rules.
	 *
	 * @since TBD
	 *
	 * @var Rule_Store
	 */
	private Rule_Store $rule_store;

	/**
	 * The providers the relative sales window applies to.
	 *
	 * @since TBD
	 *
	 * @var Providers
	 */
	private Providers $providers;

	/**
	 * Rest constructor.
	 *
	 * @since TBD
	 *
	 * @param Rule_Store $rule_store The store of the ticket rules.
	 * @param Providers  $providers  The providers the relative sales window applies to.
	 */
	public function __construct( Rule_Store $rule_store, Providers $providers ) {
		$this->rule_store = $rule_store;
		$this->providers  = $providers;
	}

	/**
	 * Copies the rules the block editor sends, such as `ticket[relative_sale_dates]` and `ticket[sale_price][relative]`,
	 * into the ticket data it saves.
	 *
	 * A rule the request does not send is left out, so the save keeps the stored one.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|mixed $ticket_data The ticket data built from the request body.
	 * @param WP_REST_Request           $request     The request.
	 * @param Tickets                   $provider    The provider that saves the ticket.
	 *
	 * @return array<string,mixed>|mixed The ticket data, with the rules of a ticket of a provider the relative sales
	 *                                   window applies to.
	 */
	public function map_block_editor_rule( $ticket_data, WP_REST_Request $request, Tickets $provider ) {
		if ( ! is_array( $ticket_data ) ) {
			return $ticket_data;
		}

		$ticket = $request->get_param( 'ticket' );

		if ( ! $this->providers->accepts_provider( $provider->class_name ) || ! is_array( $ticket ) ) {
			return $ticket_data;
		}

		foreach ( Window_Kind::all() as $kind ) {
			// The Ticket block offers another provider's ticket a sale price, which only Tickets Commerce saves.
			if ( ! $kind->owns_ticket_sales_dates() && ! $provider instanceof Module ) {
				continue;
			}

			$keys   = $kind->get_rule_keys();
			$path   = $keys['block_editor_request'];
			$key    = array_pop( $path );
			$parent = Arr::get( $ticket, $path );

			// A rule sent as `null` still removes the stored one, so the key is looked for, not its value.
			if ( is_array( $parent ) && array_key_exists( $key, $parent ) ) {
				$ticket_data[ $keys['data'] ] = $parent[ $key ];
			}
		}

		return $ticket_data;
	}

	/**
	 * Names the TEC REST API params the rules are sent in, so the ticket upsert passes them on as ticket data.
	 *
	 * @since TBD
	 *
	 * @param array<string,string>|mixed $ticket_data_keys The ticket data keys, keyed by the REST param they are sent in.
	 *
	 * @return array<string,string>|mixed The keys, with the rules'.
	 */
	public function add_rule_to_ticket_data_params( $ticket_data_keys ) {
		if ( ! is_array( $ticket_data_keys ) ) {
			return $ticket_data_keys;
		}
		foreach ( Window_Kind::all() as $kind ) {
			$keys = $kind->get_rule_keys();

			$ticket_data_keys[ $keys['tec_rest'] ] = $keys['data'];
		}

		return $ticket_data_keys;
	}

	/**
	 * Adds the ticket's stored rules, or `null`, to the ticket data the block editor reads.
	 *
	 * The ticket ID comes from the data: the filter also passes the caller's argument, which can be a post or a ticket
	 * object. A rule at the top level of the data, as `relative_sale_dates`, is added for a ticket of every provider; one
	 * nested in the provider's own data, as `sale_price_data.relative`, only for a Tickets Commerce ticket, and only
	 * where that data is an array.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|mixed $data The ticket data.
	 *
	 * @return array<string,mixed>|mixed The ticket data, with `relative_sale_dates` and, for a Tickets Commerce ticket,
	 *                             `sale_price_data.relative`.
	 */
	public function add_rule_to_block_editor_ticket_data( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}
		$ticket_id          = absint( $data['id'] ?? 0 );
		$is_commerce_ticket = $ticket_id && Ticket::POSTTYPE === get_post_type( $ticket_id );

		foreach ( Window_Kind::all() as $kind ) {
			$path = $kind->get_rule_keys()['block_editor_response'];

			// `Arr::set()` would create the levels the path goes through, which the data must already have.
			if (
				( ! $is_commerce_ticket && count( $path ) > 1 )
				|| ! is_array( Arr::get( $data, array_slice( $path, 0, -1 ) ) )
			) {
				continue;
			}

			$data = Arr::set( $data, $path, $ticket_id ? $this->get_stored_rule( $ticket_id, $kind ) : null );
		}

		return $data;
	}

	/**
	 * Documents the rule of every window kind, such as `relative_sale_dates` and `sale_price_relative`, in the TEC REST
	 * API ticket request body and ticket definitions.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|mixed $documentation The definition's documentation, in the format supported by Swagger.
	 *
	 * @return array<string,mixed>|mixed The documentation, with the rules.
	 */
	public function add_rule_to_definition( $documentation ) {
		if ( ! is_array( $documentation ) ) {
			return $documentation;
		}
		$properties = new PropertiesCollection();

		foreach ( Window_Kind::all() as $kind ) {
			$properties[] = Rule_Parameter::for_kind( $kind );
		}

		$documentation['allOf'][] = [
			'type'       => 'object',
			'properties' => $properties,
		];

		return $documentation;
	}

	/**
	 * Keeps a rule sent as `null`, which the TEC REST API drops with every other `null` it is sent.
	 *
	 * Every TEC REST API endpoint runs this filter, so a rule is only put back for a request whose schema documents it:
	 * an event, a venue or an organizer would save the key as post meta.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|mixed $filtered_data The request data the schema keeps.
	 * @param array<string,mixed>       $data          The request data as it was sent.
	 * @param OpenAPI_Schema            $schema        The schema of the request.
	 *
	 * @return array<string,mixed>|mixed The kept data, with each rule sent as `null`.
	 */
	public function keep_a_rule_sent_as_null( $filtered_data, array $data, OpenAPI_Schema $schema ) {
		if ( ! is_array( $filtered_data ) ) {
			return $filtered_data;
		}
		foreach ( Window_Kind::all() as $kind ) {
			$field = $kind->get_rule_keys()['tec_rest'];

			if ( array_key_exists( $field, $data ) && null === $data[ $field ] && $this->documents_field( $schema, $field ) ) {
				$filtered_data[ $field ] = null;
			}
		}

		return $filtered_data;
	}

	/**
	 * Adds the ticket's stored rules, or `null`, to a ticket the TEC REST API returns.
	 *
	 * A provider added through `tec_tickets_relative_sale_dates_ticket_providers` hooks this to its own ticket entity;
	 * only a Tickets Commerce ticket has a sale price rule.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|mixed $entity The ticket entity.
	 *
	 * @return array<string,mixed>|mixed The entity, with `relative_sale_dates` and, for a Tickets Commerce ticket,
	 *                                   `sale_price_relative`.
	 */
	public function add_rule_to_tec_rest_api_ticket( $entity ) {
		if ( ! is_array( $entity ) ) {
			return $entity;
		}
		$ticket_id       = absint( $entity['id'] ?? 0 );
		$is_other_ticket = $ticket_id && Ticket::POSTTYPE !== get_post_type( $ticket_id );

		foreach ( Window_Kind::all() as $kind ) {
			if ( $is_other_ticket && ! $kind->owns_ticket_sales_dates() ) {
				continue;
			}

			$entity[ $kind->get_rule_keys()['tec_rest'] ] = $ticket_id ? $this->get_stored_rule( $ticket_id, $kind ) : null;
		}

		return $entity;
	}

	/**
	 * Returns whether the request body of a schema documents a field, directly or through a definition.
	 *
	 * @since TBD
	 *
	 * @param OpenAPI_Schema $schema The schema of the request.
	 * @param string         $field  The field name.
	 *
	 * @return bool Whether the request body documents the field.
	 */
	private function documents_field( OpenAPI_Schema $schema, string $field ): bool {
		foreach ( $schema->get_request_body() ?? [] as $parameter ) {
			$collections = $parameter instanceof Definition_Parameter ? $parameter->get_collections() : [ [ $parameter ] ];

			foreach ( $collections as $collection ) {
				foreach ( $collection as $property ) {
					if ( $field === $property->get_name() ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Gets a ticket's stored rule of a kind in its canonical form.
	 *
	 * @since TBD
	 *
	 * @param int         $ticket_id The ticket post ID.
	 * @param Window_Kind $kind      The kind of the rule.
	 *
	 * @return array{start: array{mode: string, value?: int, unit?: int, anchor?: string}, end: array{mode: string, value?: int, unit?: int, anchor?: string}}|null The rule, or `null` when the ticket has no valid rule of the kind.
	 */
	private function get_stored_rule( int $ticket_id, Window_Kind $kind ): ?array {
		$rule = Rule::from_stored( $this->rule_store->get( $ticket_id ), $kind );

		return $rule ? $rule->to_array() : null;
	}
}
