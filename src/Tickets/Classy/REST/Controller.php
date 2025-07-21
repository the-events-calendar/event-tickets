<?php
/**
 * The main controller class for the REST API Classy integration.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Classy\REST;
 */

declare( strict_types=1 );

namespace TEC\Tickets\Classy\REST;

use TEC\Common\Classy\REST\Controller as BaseController;
use TEC\Tickets\Classy\REST\Endpoints\Tickets;
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
				'args'                => [],
				'description'         => __( 'Retrieve tickets.', 'event-tickets-classy' ),
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/tickets',
			[
				'methods'             => Server::CREATABLE,
				'callback'            => $this->container->callback( Tickets::class, 'create' ),
				'permission_callback' => $this->get_permission_callback(),
				'args'                => [],
				'description'         => __( 'Create a new ticket.', 'event-tickets-classy' ),
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/tickets/(?P<id>[\d]+)',
			[
				'methods'             => Server::EDITABLE,
				'callback'            => $this->container->callback( Tickets::class, 'update' ),
				'permission_callback' => $this->get_permission_callback(),
				'args'                => [],
				'description'         => __( 'Update an existing ticket.', 'event-tickets-classy' ),
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
				'description'         => __( 'Delete a ticket.', 'event-tickets-classy' ),
			]
		);
	}
}
