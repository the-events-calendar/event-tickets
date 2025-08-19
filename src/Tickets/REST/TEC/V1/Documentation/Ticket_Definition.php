<?php
/**
 * Ticket definition provider for the TEC REST API.
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST\TEC\V1\Documentation
 */

declare( strict_types=1 );

namespace TEC\Tickets\REST\TEC\V1\Documentation;

use TEC\Common\REST\TEC\V1\Abstracts\Definition;
use TEC\Common\REST\TEC\V1\Collections\PropertiesCollection;
use TEC\Common\REST\TEC\V1\Parameter_Types\Boolean;
use TEC\Common\REST\TEC\V1\Parameter_Types\Date_Time;
use TEC\Common\REST\TEC\V1\Parameter_Types\Date;
use TEC\Common\REST\TEC\V1\Parameter_Types\Number;
use TEC\Common\REST\TEC\V1\Parameter_Types\Positive_Integer;
use TEC\Common\REST\TEC\V1\Parameter_Types\Text;

/**
 * Ticket definition provider for the TEC REST API.
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST\TEC\V1\Documentation
 */
class Ticket_Definition extends Definition {
	/**
	 * Returns the type of the definition.
	 *
	 * @since 5.26.0
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'Ticket';
	}

	/**
	 * Returns the priority of the definition.
	 *
	 * @since 5.26.0
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 10;
	}

	/**
	 * Returns an array in the format used by Swagger.
	 *
	 * @since 5.26.0
	 *
	 * @return array An array description of a Swagger supported component.
	 */
	public function get_documentation(): array {
		$properties = new PropertiesCollection();

		$properties[] = (
			new Text(
				'description',
				fn() => __( 'The description of the ticket', 'event-tickets' ),
			)
		)->set_example( 'This is a description of the ticket' );

		$properties[] = (
			new Boolean(
				'on_sale',
				fn() => __( 'Whether the ticket is on sale', 'event-tickets' ),
			)
		)->set_example( true )->set_nullable( true );

		$properties[] = (
			new Number(
				'sale_price',
				fn() => __( 'The sale price of the ticket', 'event-tickets' ),
			)
		)->set_example( 20.00 )->set_nullable( true );

		$properties[] = (
			new Number(
				'price',
				fn() => __( 'The price of the ticket', 'event-tickets' ),
			)
		)->set_example( 25.00 );

		$properties[] = (
			new Number(
				'regular_price',
				fn() => __( 'The regular price of the ticket', 'event-tickets' ),
			)
		)->set_example( 25.00 );

		$properties[] = (
			new Boolean(
				'show_description',
				fn() => __( 'Whether to show the ticket description', 'event-tickets' ),
			)
		)->set_example( true );

		$properties[] = (
			new Date_Time(
				'start_date',
				fn() => __( 'The start sale date of the ticket', 'event-tickets' ),
			)
		)->set_example( '2025-05-01 00:00:00' )->set_pattern( '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$' )->set_nullable( true );

		$properties[] = (
			new Date_Time(
				'end_date',
				fn() => __( 'The end sale date of the ticket', 'event-tickets' ),
			)
		)->set_example( '2025-06-04 23:59:59' )->set_pattern( '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$' )->set_nullable( true );

		$properties[] = (
			new Date(
				'sale_price_start_date',
				fn() => __( 'The start date for the sale price', 'event-tickets' ),
			)
		)->set_example( '2025-06-01' )->set_pattern( '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' )->set_nullable( true );

		$properties[] = (
			new Date(
				'sale_price_end_date',
				fn() => __( 'The end date for the sale price', 'event-tickets' ),
			)
		)->set_example( '2025-06-30' )->set_pattern( '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' )->set_nullable( true );

		$properties[] = (
			new Positive_Integer(
				'event',
				fn() => __( 'The ID of the post this ticket is associated with. Normally an event-like post.', 'event-tickets' ),
			)
		)->set_example( 123 );

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
		)->set_example( 100 )->set_nullable( true );

		$properties[] = (
			new Text(
				'type',
				fn() => __( 'The type of ticket', 'event-tickets' ),
			)
		)->set_example( 'default' );

		$properties[] = (
			new Positive_Integer(
				'sold',
				fn() => __( 'The number of tickets sold', 'event-tickets' ),
			)
		)->set_example( 42 );

		$properties[] = (
			new Text(
				'sku',
				fn() => __( 'The SKU of the ticket', 'event-tickets' ),
			)
		)->set_example( 'TICKET-123' )->set_nullable( true );

		$documentation = [
			'allOf' => [
				[
					'$ref' => '#/components/schemas/TEC_Post_Entity',
				],
				[
					'type'        => 'object',
					'description' => __( 'A ticket', 'event-tickets' ),
					'title'       => 'Ticket',
					'properties'  => $properties,
				],
			],
		];

		$type = strtolower( $this->get_type() );

		/**
		 * Filters the Swagger documentation generated for a ticket in the TEC REST API.
		 *
		 * @since 5.26.0
		 *
		 * @param array             $documentation An associative PHP array in the format supported by Swagger.
		 * @param Ticket_Definition $this          The Ticket_Definition instance.
		 *
		 * @return array
		 */
		$documentation = (array) apply_filters( "tec_rest_swagger_{$type}_definition", $documentation, $this );

		/**
		 * Filters the Swagger documentation generated for a definition in the TEC REST API.
		 *
		 * @since 5.26.0
		 *
		 * @param array             $documentation An associative PHP array in the format supported by Swagger.
		 * @param Ticket_Definition $this          The Ticket_Definition instance.
		 *
		 * @return array
		 */
		return (array) apply_filters( 'tec_rest_swagger_definition', $documentation, $this );
	}
}
