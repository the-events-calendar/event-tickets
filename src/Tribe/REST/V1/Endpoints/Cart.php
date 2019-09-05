<?php

class Tribe__Tickets__REST__V1__Endpoints__Cart
	extends Tribe__Tickets__REST__V1__Endpoints__Base
	implements Tribe__REST__Endpoints__READ_Endpoint_Interface,
	Tribe__REST__Endpoints__UPDATE_Endpoint_Interface,
	Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 */
	public function get_documentation() {
		$get_defaults = [
			'in'      => 'query',
			'default' => '',
		];

		$post_defaults = [
			'in'      => 'formData',
			'default' => '',
			'type'    => 'string',
		];

		return [
			'get'  => [
				'parameters' => $this->swaggerize_args( $this->READ_args(), $get_defaults ),
				'responses'  => [
					'200' => [
						'description' => __( 'Returns the list of tickets in the cart', 'event-tickets' ),
						'content'     => [
							'application/json' => [
								'schema' => [
									'type'       => 'object',
									'properties' => [
										'tickets' => [
											'type'        => 'array',
											'description' => __( 'The list of tickets and their quantities in the cart', 'event-tickets' ),
										],
									],
								],
							],
						],
					],
					'400' => [
						'description' => __( 'The post ID is invalid.', 'ticket-tickets' ),
						'content'     => [
							'application/json' => [
								'schema' => [
									'type' => 'object',
								],
							],
						],
					],
					'404' => [
						'description' => __( 'The post ID was not found.', 'event-tickets' ),
						'content'     => [
							'application/json' => [
								'schema' => [
									'type' => 'object',
								],
							],
						],
					],
				],
			],
			'post' => [
				'consumes'   => [ 'application/x-www-form-urlencoded' ],
				'parameters' => $this->swaggerize_args( $this->EDIT_args(), $post_defaults ),
				'responses'  => [
					'200' => [
						'description' => __( 'Returns the updated list of tickets in the cart and cart details', 'event-tickets' ),
						'content'     => [
							'application/json' => [
								'schema' => [
									'type'       => 'object',
									'properties' => [
										'tickets'      => [
											'type'        => 'array',
											'description' => __( 'The list of tickets and their quantities in the cart', 'event-tickets' ),
										],
										'cart_url'     => [
											'type'        => 'string',
											'description' => __( 'The provider cart URL', 'event-tickets' ),
										],
										'checkout_url' => [
											'type'        => 'string',
											'description' => __( 'The provider checkout URL', 'event-tickets' ),
										],
									],
								],
							],
						],
					],
					'400' => [
						'description' => __( 'The post ID is invalid.', 'ticket-tickets' ),
						'content'     => [
							'application/json' => [
								'schema' => [
									'type' => 'object',
								],
							],
						],
					],
					'403' => [
						'description' => __( 'The post does not have any tickets', 'the-events-calendar' ),
					],
					'404' => [
						'description' => __( 'The post ID was not found.', 'event-tickets' ),
						'content'     => [
							'application/json' => [
								'schema' => [
									'type' => 'object',
								],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 */
	public function get( WP_REST_Request $request ) {
		$post_id  = (int) $request->get_param( 'post_id' );
		$provider = $request->get_param( 'provider' );

		if ( null !== $provider ) {
			$provider = (array) $provider;
		}

		$data = [
			'tickets' => [],
		];

		// Confirm post has tickets.
		$has_tickets = false;

		if ( ! $has_tickets ) {
			return new WP_REST_Response( $data );
		}

		/** @var Tribe__Tickets__Editor__Configuration $editor_config */
		$editor_config = tribe( 'tickets.editor.configuration' );

		// Get list of providers.
		$providers = $editor_config->get_providers();

		// Fetch tickets for cart providers.
		foreach ( $providers as $provider_data ) {
			// Skip provider if we only want specific ones.
			if ( null !== $provider && ! in_array( $provider_data['name'], $provider, true ) ) {
				continue;
			}

			// Fetch tickets for provider cart.
			$cart_tickets = [];

			/**
			 * Get list of tickets in the cart for provider.
			 *
			 * The dynamic portion of the hook name, `$provider`, refers to the cart provider.
			 *
			 * @since TBD
			 *
			 * @param array $cart_tickets List of tickets in the cart.
			 */
			$cart_tickets = apply_filters( 'tribe_tickets_rest_cart_get_tickets_' . $provider, $cart_tickets );

			foreach ( $cart_tickets as $ticket_id => $quantity ) {
				// Skip ticket if it has no quantity or is not accessible.
				if ( $quantity < 1 || ! $this->is_ticket_readable( $ticket_id ) ) {
					continue;
				}

				$data['tickets'][] = [
					'ticket_id' => $ticket_id,
					'quantity'  => $quantity,
					'provider'  => $provider_data['name'],
				];
			}
		}

		return new WP_REST_Response( $data );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 */
	public function READ_args() {
		return [
			'post_id'  => [
				'in'                => 'path',
				'required'          => true,
				'type'              => 'integer',
				'description'       => __( 'The post ID', 'event-tickets' ),
				'validate_callback' => [ $this->validator, 'is_post_id' ],
			],
			'provider' => [
				'required'          => false,
				'description'       => __( 'Limit results to tickets provided by one of the providers specified in the CSV list or array; defaults to all available.', 'event-tickets' ),
				'sanitize_callback' => [
					'Tribe__Utils__Array',
					'list_to_array',
				],
				'swagger_type'      => [
					'oneOf' => [
						[
							'type'  => 'array',
							'items' => [
								'type' => 'string',
							],
						],
						[
							'type' => 'string',
						],
					],
				],
			],
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 */
	public function update( WP_REST_Request $request ) {
		$post_id  = (int) $request->get_param( 'post_id' );
		$provider = $request->get_param( 'provider' );
		$tickets  = $request->get_param( 'tickets' );
		$meta     = $request->get_param( 'meta' );

		// Confirm post has tickets.
		$has_tickets = false;

		if ( ! $has_tickets ) {
			$message = $this->messages->get_message( 'post-has-no-tickets' );

			return new WP_Error( 'post-has-no-tickets', $message, [ 'status' => 403 ] );
		}

		// Update cart quantities.
		if ( null !== $tickets ) {
			/**
			 * Update tickets in cart for provider.
			 *
			 * The dynamic portion of the hook name, `$provider`, refers to the cart provider.
			 *
			 * @since TBD
			 *
			 * @param array $tickets List of tickets with their ID and quantity.
			 */
			do_action( 'tribe_tickets_rest_cart_update_' . $provider, $tickets );

			/**
			 * Update tickets in cart.
			 *
			 * @since TBD
			 *
			 * @param array  $tickets  List of tickets with their ID and quantity.
			 * @param string $provider The cart provider.
			 */
			do_action( 'tribe_tickets_rest_cart_update', $tickets, $provider );
		}

		// Update ticket meta.
		if ( null !== $meta ) {
			/**
			 * Update ticket meta from Attendee Registration for provider.
			 *
			 * The dynamic portion of the hook name, `$provider`, refers to the cart provider.
			 *
			 * @since TBD
			 *
			 * @param array $meta    List of meta for each ticket to be saved for Attendee Registration.
			 * @param array $tickets List of tickets with their ID and quantity.
			 */
			do_action( 'tribe_tickets_rest_cart_meta_update_' . $provider, $meta, $tickets );

			/**
			 * Update ticket meta from Attendee Registration.
			 *
			 * @since TBD
			 *
			 * @param array  $meta     List of meta for each ticket to be saved for Attendee Registration.
			 * @param array  $tickets  List of tickets with their ID and quantity.
			 * @param string $provider The cart provider.
			 */
			do_action( 'tribe_tickets_rest_cart_meta_update', $meta, $tickets, $provider );
		}

		// Get the updated cart details.
		$response = $this->get( $request );

		// Update response with correct cart URLs.
		if ( $response instanceof WP_REST_Response ) {
			/** @var WP_REST_Response $response */
			$data = $response->get_data();

			$cart_url = '';
			$checkout_url = '';

			/**
			 * Get cart URL for provider.
			 *
			 * The dynamic portion of the hook name, `$provider`, refers to the cart provider.
			 *
			 * @since TBD
			 *
			 * @param string $cart_url Cart URL.
			 */
			$cart_url = apply_filters( 'tribe_tickets_rest_cart_get_cart_url_' . $provider, $cart_url );

			/**
			 * Get checkout URL for provider.
			 *
			 * The dynamic portion of the hook name, `$provider`, refers to the cart provider.
			 *
			 * @since TBD
			 *
			 * @param string $checkout_url Checkout URL.
			 */
			$checkout_url = apply_filters( 'tribe_tickets_rest_cart_get_checkout_url_' . $provider, $checkout_url );

			// Update cart and checkout URL.
			$data['cart_url']     = $cart_url;
			$data['checkout_url'] = $checkout_url;

			// Update response data.
			$response->set_data( $data );
		}

		return $response;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 */
	public function EDIT_args() {
		return [
			'post_id'  => [
				'in'                => 'path',
				'required'          => true,
				'type'              => 'integer',
				'description'       => __( 'The post ID', 'event-tickets' ),
				'validate_callback' => [ $this->validator, 'is_post_id' ],
			],
			'provider' => [
				'required'    => true,
				'type'        => 'string',
				'description' => __( 'The cart provider', 'event-tickets' ),
			],
			'tickets'  => [
				'required'     => false,
				'default'      => null,
				'swagger_type' => 'array',
				'description'  => __( 'List of tickets with their ID and quantity', 'event-tickets' ),
			],
			'meta'     => [
				'required'     => false,
				'default'      => null,
				'swagger_type' => 'array',
				'description'  => __( 'List of meta for each ticket to be saved for Attendee Registration', 'event-tickets' ),
			],
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 */
	public function can_edit() {
		// Everyone can edit their own cart.
		return true;
	}
}
