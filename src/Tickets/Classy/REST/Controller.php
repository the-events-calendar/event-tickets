<?php
/**
 * The main controller class for the REST API Classy integration.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Classy\REST;
 */

declare( strict_types=1 );

namespace TEC\Tickets\Classy\REST;

use TEC\Common\Classy\REST\Controller as BaseController;
use TEC\Tickets\Classy\REST\Endpoints\Tickets;
use Tribe__Utils__Array as ArrayUtil;
use WP_REST_Server as Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Controller
 *
 * @since TBD
 */
class Controller extends BaseController {
	/**
	 * Registers the routes for the REST API.
	 *
	 * @since TBD
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/tickets',
			[
				'methods'             => Server::READABLE,
				'callback'            => $this->container->callback( Tickets::class, 'get' ),
				'permission_callback' => $this->get_permission_callback(),
				'args'                => $this->get_tickets_args(),
				'description'         => __( 'Retrieve tickets.', 'event-tickets' ),
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/tickets',
			[
				'methods'             => Server::CREATABLE,
				'callback'            => $this->container->callback( Tickets::class, 'create' ),
				'permission_callback' => $this->get_permission_callback(),
				'args'                => $this->get_ticket_args(),
				'description'         => __( 'Create a new ticket.', 'event-tickets' ),
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/tickets/(?P<id>[\d]+)',
			[
				'methods'             => Server::EDITABLE,
				'callback'            => $this->container->callback( Tickets::class, 'update' ),
				'permission_callback' => $this->get_permission_callback(),
				'args'                => $this->get_ticket_args(),
				'description'         => __( 'Update an existing ticket.', 'event-tickets' ),
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/tickets/(?P<id>[\d]+)',
			[
				'methods'             => Server::DELETABLE,
				'callback'            => $this->container->callback( Tickets::class, 'delete' ),
				'permission_callback' => $this->get_permission_callback(),
				'args'                => [],
				'description'         => __( 'Delete a ticket.', 'event-tickets' ),
			]
		);
	}

	/**
	 * Returns the arguments for the tickets endpoint.
	 *
	 * @since TBD
	 *
	 * @return array<string, array> The arguments for the tickets endpoint.
	 */
	protected function get_tickets_args(): array {
		return [
			'include_post' => [
				'description'       => __( 'Limit results to tickets that are assigned to one of the posts specified in the CSV list or array.', 'event-tickets' ),
				'required'          => false,
				'validate_callback' => static function ( $value ) {
					$posts = ArrayUtil::list_to_array( $value );
					$valid = array_filter(
						$posts,
						static fn( $id ) => is_numeric( $id ) && null !== get_post( $id )
					);

					return ! empty( $valid ) && count( $valid ) === count( $posts );
				},
				'sanitize_callback' => static fn( $value ) => ArrayUtil::list_to_array( $value ),
			],
			'per_page'     => [
				'description'       => __( 'How many tickets to return per results page; defaults to posts_per_page.', 'event-tickets' ),
				'type'              => 'integer',
				'default'           => get_option( 'posts_per_page' ),
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
			],
			'page'         => [
				'description'       => __( 'The page of results to return; defaults to 1', 'event-tickets' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'minimum'           => 1,
			],
		];
	}

	/**
	 * Returns the arguments for a ticket.
	 *
	 * @since TBD
	 *
	 * @return array<string, array> The arguments for a ticket.
	 */
	protected function get_ticket_args(): array {
		return [
			'name'             => [
				'type'    => 'string',
				'format'  => 'text-field',
				'default' => '',
			],
			'description'      => [
				'type'    => 'string',
				'format'  => 'text-field',
				'default' => '',
			],
			'price'            => [
				'type'    => 'string',
				'format'  => 'text-field',
				'default' => '',
			],
			'show_description' => [
				'type'    => 'string',
				'format'  => 'text-field',
				'default' => 'yes',
			],
			'start_date'       => [
				'type'   => 'string',
				'format' => 'date-time',
			],
			'start_time'       => [
				'type'   => 'string',
				'format' => 'date-time',
			],
			'end_date'         => [
				'type'   => 'string',
				'format' => 'date-time',
			],
			'end_time'         => [
				'type'   => 'string',
				'format' => 'date-time',
			],
			'sku'              => [
				'type'    => 'string',
				'default' => '',
			],
			'iac'              => [
				'type'    => 'string',
				'default' => '',
			],
			'ticket'           => [
				'type'       => 'object',
				'properties' => [
					'mode'           => [
						'type' => 'string',
					],
					'capacity'       => [
						'type' => 'string',
					],
					'event_capacity' => [
						'type'    => 'integer',
						'default' => 0,
					],
					'sale_price'     => [
						'type'       => 'object',
						'properties' => [
							'checked'    => [
								'type'    => 'boolean',
								'default' => false,
							],
							'price'      => [
								'type'    => 'string',
								'default' => '',
							],
							'start_date' => [
								'type'    => 'string',
								'format'  => 'date-time',
								'default' => '',
							],
							'end_date'   => [
								'type'    => 'string',
								'format'  => 'date-time',
								'default' => '',
							],
						],
					],
					'seating'        => [
						'type'       => 'object',
						'properties' => [
							'enabled'  => [
								'type'    => 'boolean',
								'default' => false,
							],
							'seatType' => [
								'type'    => 'string',
								'default' => '',
							],
							'layoutId' => [
								'type'    => 'integer',
								'default' => 0,
							],
						],
					],
				],
			],
		];
	}
}
