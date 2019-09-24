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
										'meta' => [
											'type'        => 'array',
											'description' => __( 'The list of meta for each ticket item in the cart', 'event-tickets' ),
										],
									],
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
										'meta'         => [
											'type'        => 'array',
											'description' => __( 'The list of meta for each ticket item in the cart', 'event-tickets' ),
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
		$provider = $request->get_param( 'provider' );

		if ( null !== $provider ) {
			$provider = (array) $provider;
		}

		$data = [
			'tickets' => [],
			'meta'    => [],
		];

		/** @var Tribe__Tickets__Editor__Configuration $editor_config */
		$editor_config = tribe( 'tickets.editor.configuration' );

		// Get list of providers.
		$providers = $editor_config->get_providers();

		// Fetch tickets for cart providers.
		foreach ( $providers as $provider_data ) {
			/** @var Tribe__Tickets__Tickets $provider_object */
			$provider_object = call_user_func( [ $provider_data['class'], 'get_instance' ] );

			$provider_key             = $provider_object->orm_provider;
			$provider_attendee_object = $provider_object->attendee_object;

			// Skip provider if we only want specific ones.
			if (
				null !== $provider
				&& ! in_array( $provider_key, $provider, true )
				&& ! in_array( $provider_attendee_object, $provider, true )
			) {
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
			$cart_tickets = apply_filters( 'tribe_tickets_rest_cart_get_tickets_' . $provider_key, $cart_tickets );

			$default_ticket = [
				'ticket_id' => 0,
				'quantity'  => 0,
				'post_id'   => 0,
				'optout'    => 0,
			];
bdump($cart_tickets);
			foreach ( $cart_tickets as $ticket ) {
				$ticket = array_merge( $default_ticket, $ticket );

				// Enforce types.
				$ticket['ticket_id'] = absint( $ticket['ticket_id'] );
				$ticket['quantity']  = absint( $ticket['quantity'] );
				$ticket['post_id']   = absint( $ticket['post_id'] );
				$ticket['optout']    = (int) filter_var( $ticket['optout'], FILTER_VALIDATE_BOOLEAN );

				$ticket_id = $ticket['ticket_id'];
				$quantity  = $ticket['quantity'];

				// Skip ticket if it has no quantity or is not accessible.
				if ( $quantity < 1 || ! $this->is_ticket_readable( $ticket_id ) ) {
					continue;
				}

				$data['tickets'][] = $ticket;
			}
		}

		// Fetch meta for cart.
		$cart_meta = [];

		/**
		 * Get list of ticket meta in the cart.
		 *
		 * @since TBD
		 *
		 * @param array $cart_meta List of ticket meta in the cart.
		 * @param array $tickets   List of tickets in the cart.
		 */
		$cart_meta = apply_filters( 'tribe_tickets_rest_cart_get_ticket_meta', $cart_meta, $data['tickets'] );

		$data['meta'] = $cart_meta;

		return new WP_REST_Response( $data );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 */
	public function READ_args() {
		return [
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
		$post_id  = $request->get_param( 'post_id' );
		$provider = $request->get_param( 'provider' );
		$tickets  = $request->get_param( 'tickets' );
		$meta     = $request->get_param( 'meta' );

		if ( 0 < $post_id ) {
			// Confirm post has tickets.
			$has_tickets = ! empty( Tribe__Tickets__Tickets::get_all_event_tickets( $post_id ) );

			if ( ! $has_tickets ) {
				$message = $this->messages->get_message( 'post-has-no-tickets' );

				return new WP_Error( 'post-has-no-tickets', $message, [ 'status' => 403 ] );
			}
		}

		// Update cart quantities.
		if ( null !== $tickets ) {
			$providers = [];

			// Setup tickets.
			foreach ( $tickets as $k => $ticket ) {
				$defaults = [
					'ticket_id' => 0,
					'quantity'  => 0,
					'optout'    => 0,
					'provider'  => $provider,
				];

				$ticket = array_merge( $defaults, $ticket );

				$ticket['ticket_id'] = absint( $ticket['ticket_id'] );
				$ticket['quantity']  = absint( $ticket['quantity'] );

				// Skip ticket if ticket_id is not set.
				if ( 0 === $ticket['ticket_id'] ) {
					unset( $tickets[ $k ] );

					continue;
				}

				// Update ticket in array for use later.
				$tickets[ $k ] = $ticket;

				// Add provider if not yet added.
				if ( ! isset( $providers[ $ticket['provider'] ] ) ) {
					$providers[ $ticket['provider'] ] = [];
				}

				// Add ticket to provider.
				$providers[ $ticket['provider'] ][] = $ticket;
			}

			try {
				foreach ( $providers as $provider_tickets ) {
					/**
					 * Update tickets in cart for provider.
					 *
					 * The dynamic portion of the hook name, `$provider`, refers to the cart provider.
					 *
					 * @since TBD
					 *
					 * @param array $provider_tickets List of tickets with their ID and quantity.
					 * @param int   $post_id          Post ID for the cart.
					 */
					do_action( 'tribe_tickets_rest_cart_update_tickets_' . $provider, $provider_tickets, $post_id );
				}

				/**
				 * Update tickets in cart.
				 *
				 * @since TBD
				 *
				 * @param array  $tickets  List of tickets with their ID and quantity.
				 * @param string $provider The cart provider.
				 * @param int    $post_id  Post ID for the cart.
				 */
				do_action( 'tribe_tickets_rest_cart_update_tickets', $tickets, $provider, $post_id );
			} catch ( Tribe__REST__Exceptions__Exception $exception ) {
				return new WP_Error( $exception->getCode(), esc_html( $exception->getMessage() ), [ 'status' => $exception->getStatus() ] );
			}
		}

		// Update ticket meta.
		if ( null !== $meta ) {
			// Setup meta.
			foreach ( $meta as $k => $ticket_meta ) {
				$defaults = [
					'ticket_id' => 0,
					'provider'  => $provider,
					'items'     => [],
				];

				$ticket_meta = array_merge( $defaults, $ticket_meta );

				$ticket_meta['ticket_id'] = absint( $ticket_meta['ticket_id'] );

				$meta[ $k ] = $ticket_meta;
			}

			try {
				/**
				 * Update ticket meta from Attendee Registration.
				 *
				 * @since TBD
				 *
				 * @param array  $meta     List of meta for each ticket to be saved for Attendee Registration.
				 * @param array  $tickets  List of tickets with their ID and quantity.
				 * @param string $provider The cart provider.
				 * @param int    $post_id  Post ID for the cart.
				 */
				do_action( 'tribe_tickets_rest_cart_update_ticket_meta', $meta, $tickets, $provider, $post_id );
			} catch ( Tribe__REST__Exceptions__Exception $exception ) {
				return new WP_Error( $exception->getCode(), esc_html( $exception->getMessage() ), [ 'status' => $exception->getStatus() ] );
			}
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
			 * @param array  $data     REST API response data to be sent.
			 * @param int    $post_id  Post ID for the cart.
			 */
			$cart_url = apply_filters( 'tribe_tickets_rest_cart_get_cart_url_' . $provider, $cart_url, $data, $post_id );

			/**
			 * Get checkout URL for provider.
			 *
			 * The dynamic portion of the hook name, `$provider`, refers to the cart provider.
			 *
			 * @since TBD
			 *
			 * @param string $checkout_url Checkout URL.
			 * @param array  $data         REST API response data to be sent.
			 * @param int    $post_id      Post ID for the cart.
			 */
			$checkout_url = apply_filters( 'tribe_tickets_rest_cart_get_checkout_url_' . $provider, $checkout_url, $data, $post_id );

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
			'post_id'  => [
				'required'          => false,
				'type'              => 'integer',
				'description'       => __( 'The post ID', 'event-tickets' ),
				'validate_callback' => [ $this->validator, 'is_post_id' ],
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
