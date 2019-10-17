<?php

class Tribe__Tickets__REST__V1__Endpoints__Cart
	extends Tribe__Tickets__REST__V1__Endpoints__Base
	implements Tribe__REST__Endpoints__READ_Endpoint_Interface,
	Tribe__REST__Endpoints__UPDATE_Endpoint_Interface,
	Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * @var bool Whether this endpoint is currently active.
	 */
	public $is_active = false;

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
					'403' => [
						'description' => __( 'The post does not have any tickets', 'event-tickets' ),
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
						'description' => __( 'The post does not have any tickets', 'event-tickets' ),
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
		$this->is_active = true;

		$post_id   = $request->get_param( 'post_id' );
		$providers = $request->get_param( 'provider' );

		if ( 0 < $post_id ) {
			// Confirm post has tickets.
			$has_tickets = ! empty( Tribe__Tickets__Tickets::get_all_event_tickets( $post_id ) );

			if ( ! $has_tickets ) {
				$message = $this->messages->get_message( 'post-has-no-tickets' );

				return new WP_Error( 'post-has-no-tickets', $message, [ 'status' => 403 ] );
			}
		}

		if ( null === $providers ) {
			$providers = [];
		}

		$providers = (array) $providers;

		$data = [
			'tickets' => [],
			'meta'    => [],
		];

		/** @var Tribe__Tickets__Editor__Configuration $editor_config */
		$editor_config = tribe( 'tickets.editor.configuration' );

		// Get list of providers.
		$all_providers = $editor_config->get_providers();

		$found_providers = [];

		// Fetch tickets for cart providers.
		foreach ( $all_providers as $provider_data ) {
			/** @var Tribe__Tickets__Tickets $provider_object */
			$provider_object = call_user_func( [ $provider_data['class'], 'get_instance' ] );

			$provider_key             = $provider_object->orm_provider;
			$provider_attendee_object = $provider_object->attendee_object;

			// Skip provider if we only want specific ones.
			if (
				[] !== $providers
				&& ! in_array( $provider_key, $providers, true )
				&& ! in_array( $provider_attendee_object, $providers, true )
			) {
				continue;
			}

			// Fetch tickets for provider cart.
			$cart_tickets = [];

			/**
			 * Get list of tickets in the cart for provider.
			 *
			 * The dynamic portion of the hook name, `$provider_key`, refers to the cart provider.
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

				if ( ! in_array( $provider_key, $found_providers, true ) ) {
					$found_providers[] = $provider_key;
				}
			}
		}

		// Set providers as the ones we found tickets for.
		if ( [] === $providers ) {
			$providers = $found_providers;
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

		$data['meta']         = $cart_meta;
		$data['cart_url']     = '';
		$data['checkout_url'] = '';

		if ( ! empty( $data['tickets'] ) ) {
			foreach ( $providers as $cart_provider ) {
				/**
				 * Get cart URL for provider.
				 *
				 * The dynamic portion of the hook name, `$cart_provider`, refers to the cart provider.
				 *
				 * @since TBD
				 *
				 * @param string $cart_url Cart URL.
				 * @param array  $data     REST API response data to be sent.
				 * @param int    $post_id  Post ID for the cart.
				 */
				$data['cart_url'] = apply_filters( 'tribe_tickets_rest_cart_get_cart_url_' . $cart_provider, '', $data, $post_id );

				/**
				 * Get checkout URL for provider.
				 *
				 * The dynamic portion of the hook name, `$cart_provider`, refers to the cart provider.
				 *
				 * @since TBD
				 *
				 * @param string $checkout_url Checkout URL.
				 * @param array  $data         REST API response data to be sent.
				 * @param int    $post_id      Post ID for the cart.
				 */
				$data['checkout_url'] = apply_filters( 'tribe_tickets_rest_cart_get_checkout_url_' . $cart_provider, '', $data, $post_id );

				// Stop after first provider URLs are set.
				if ( '' !== $data['cart_url'] || '' !== $data['checkout_url'] ) {
					break;
				}
			}
		}

		/**
		 * Get response data for the cart.
		 *
		 * @since TBD
		 *
		 * @param array $data      Cart response data.
		 * @param array $providers List of cart providers.
		 * @param int   $post_id   Post ID for cart.
		 */
		$data = apply_filters( 'tribe_tickets_rest_cart_get_data', $data, $providers, $post_id );

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
	public function update( WP_REST_Request $request ) {
		$this->is_active = true;

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
			$defaults = [
				'ticket_id' => 0,
				'quantity'  => 0,
				'optout'    => 0,
				'provider'  => $provider,
			];

			// Setup tickets.
			foreach ( $tickets as $k => $ticket ) {
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
			$defaults = [
				'ticket_id' => 0,
				'provider'  => $provider,
				'items'     => [],
			];

			foreach ( $meta as $k => $ticket_meta ) {
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
		return $this->get( $request );
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
