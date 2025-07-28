<?php
/**
 * Ticket request body definition provider for the TEC REST API.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1\Documentation
 */

namespace TEC\Tickets\REST\TEC\V1\Documentation;

use TEC\Common\REST\TEC\V1\Abstracts\Definition;
use TEC\Common\REST\TEC\V1\Collections\PropertiesCollection;
use TEC\Common\REST\TEC\V1\Parameter_Types\Boolean;
use TEC\Common\REST\TEC\V1\Parameter_Types\Date_Time;
use TEC\Common\REST\TEC\V1\Parameter_Types\Number;
use TEC\Common\REST\TEC\V1\Parameter_Types\Positive_Integer;
use TEC\Common\REST\TEC\V1\Parameter_Types\Text;

/**
 * Ticket request body definition provider for the TEC REST API.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1\Documentation
 */
class Ticket_Request_Body_Definition extends Definition {
	/**
	 * Returns the type of the definition.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'Ticket_Request_Body';
	}

	/**
	 * Returns the priority of the definition.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 10;
	}

	/**
	 * Returns the documentation for the definition.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_documentation(): array {
		$properties = new PropertiesCollection();

		$properties[] = (
			new Positive_Integer(
				'event_id',
				fn() => __( 'The ID of the event this ticket is associated with', 'event-tickets' ),
			)
		)->set_example( 123 );

		$properties[] = (
			new Number(
				'price',
				fn() => __( 'The price of the ticket', 'event-tickets' ),
			)
		)->set_example( 25.05 );

		$properties[] = (
			new Number(
				'sale_price',
				fn() => __( 'The sale price of the ticket', 'event-tickets' ),
			)
		)->set_example( 20.05 );

		$properties[] = (
			new Date_Time(
				'sale_price_start_date',
				fn() => __( 'The start date for the sale price', 'event-tickets' ),
			)
		)->set_example( '2025-06-01 00:00:00' )->set_pattern( '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$' );

		$properties[] = (
			new Date_Time(
				'sale_price_end_date',
				fn() => __( 'The end date for the sale price', 'event-tickets' ),
			)
		)->set_example( '2025-06-30 23:59:59' )->set_pattern( '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$' );

		$properties[] = (
			new Boolean(
				'manage_stock',
				fn() => __( 'Whether stock is being managed for this ticket', 'event-tickets' ),
			)
		)->set_example( true );

		$properties[] = (
			new Positive_Integer(
				'stock',
				fn() => __( 'The stock quantity available', 'event-tickets' ),
			)
		)->set_example( 100 );

		$properties[] = (
			new Boolean(
				'show_description',
				fn() => __( 'Whether to show the ticket description', 'event-tickets' ),
			)
		)->set_example( true );

		$properties[] = (
			new Text(
				'type',
				fn() => __( 'The type of ticket', 'event-tickets' ),
			)
		)->set_example( 'default' );

		$properties[] = (
			new Date_Time(
				'start_date',
				fn() => __( 'The start sale date of the ticket', 'event-tickets' ),
			)
		)->set_example( '2025-05-01 00:00:00' )->set_pattern( '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$' );

		$properties[] = (
			new Date_Time(
				'end_date',
				fn() => __( 'The end sale date of the ticket', 'event-tickets' ),
			)
		)->set_example( '2025-06-04 23:59:59' )->set_pattern( '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$' );

		$properties[] = (
			new Text(
				'sku',
				fn() => __( 'The SKU of the ticket', 'event-tickets' ),
			)
		)->set_example( 'TICKET-123' );

		return [
			'allOf' => [
				[
					'$ref' => '#/components/schemas/TEC_Post_Entity_Request_Body',
				],
				[
					'title'       => 'Ticket Request Body',
					'description' => __( 'The request body for the ticket endpoint', 'event-tickets' ),
					'type'        => 'object',
					'properties'  => $properties,
				],
			],
		];
	}
}
