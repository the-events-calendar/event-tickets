<?php

class Tribe__Tickets__Commerce__Cart {

	/**
	 * Add hooks needed for cart to function.
	 *
	 * @since TBD
	 */
	public function hook() {
		add_action( 'wp', [ $this, 'process_cart' ] );
	}

	/**
	 * Process cart on any given (non-admin) page.
	 *
	 * @since TBD
	 */
	public function process_cart() {
		if ( empty( $_POST['tribe_tickets_ar'] ) && ! is_admin() ) {
			return;
		}

		$post_id  = isset( $_POST['tribe_tickets_post_id'] ) ? $_POST['tribe_tickets_post_id'] : null;
		$provider = isset( $_POST['tribe_tickets_provider'] ) ? $_POST['tribe_tickets_provider'] : null;
		$tickets  = isset( $_POST['tribe_tickets_tickets'] ) ? $_POST['tribe_tickets_tickets'] : null;
		$meta     = isset( $_POST['tribe_tickets_meta'] ) ? $_POST['tribe_tickets_meta'] : null;

		$response = $this->update( [
			'post_id'  => $post_id,
			'provider' => $provider,
			'tickets'  => $tickets,
			'meta'     => $meta,
		] );

		if ( 'tribe-commerce' === $provider ) {
			$data = $this->get( [
				'post_id'  => $post_id,
				'provider' => $provider,
			] );

			// Redirect to Tribe Commerce checkout URL.
			if ( ! empty( $data['checkout_url'] ) ) {
				wp_redirect( $data['checkout_url'] );
				die();
			}
		}
	}

	/**
	 * Get cart data.
	 *
	 * @since TBD
	 *
	 * @param array $args {
	 *      List of arguments for getting cart.
	 *
	 *      @type int|null    $post_id   Post ID.
	 *      @type string|null $provider  Provider to get cart for.
	 *      @type array|null  $providers List of providers to get cart for.
	 * }
	 *
	 * @return array Cart data.
	 */
	public function get( $args ) {
		$post_id   = isset( $args['post_id'] ) ? $args['post_id'] : null;
		$provider  = isset( $args['provider'] ) ? $args['provider'] : null;
		$providers = isset( $args['providers'] ) ? $args['providers'] : [];

		if ( [] === $providers && null !== $provider ) {
			$providers = (array) $provider;
		}

		$data = [
			'tickets' => [],
			'meta'    => [],
		];

		/** @var Tribe__Tickets__Editor__Configuration $editor_config */
		$editor_config = tribe( 'tickets.editor.configuration' );

		// Get list of providers.
		$all_providers = $editor_config->get_providers();

		$found_providers = [];

		/** @var Tribe__Tickets__Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );

		// Fetch tickets for cart providers.
		foreach ( $all_providers as $provider_data ) {
			/** @var Tribe__Tickets__Tickets $provider_object */
			$provider_object = call_user_func( [ $provider_data['class'], 'get_instance' ] );

			$provider_key             = $provider_object->orm_provider;
			$provider_attendee_object = $provider_object->attendee_object;

			// Skip provider if we only want specific ones.
			if ( [] !== $providers && ! in_array( $provider_key, $providers, true ) && ! in_array( $provider_attendee_object, $providers, true ) ) {
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
			$cart_tickets = apply_filters( 'tribe_tickets_commerce_cart_get_tickets_' . $provider_key, $cart_tickets );

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
				if ( $quantity < 1 || ! $handler->is_ticket_readable( $ticket_id ) ) {
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
		$cart_meta = apply_filters( 'tribe_tickets_commerce_cart_get_ticket_meta', $cart_meta, $data['tickets'] );

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
				 * @param array  $data     Commerce response data to be sent.
				 * @param int    $post_id  Post ID for the cart.
				 */
				$data['cart_url'] = apply_filters( 'tribe_tickets_commerce_cart_get_cart_url_' . $cart_provider, '', $data, $post_id );

				/**
				 * Get checkout URL for provider.
				 *
				 * The dynamic portion of the hook name, `$cart_provider`, refers to the cart provider.
				 *
				 * @since TBD
				 *
				 * @param string $checkout_url Checkout URL.
				 * @param array  $data         Commerce response data to be sent.
				 * @param int    $post_id      Post ID for the cart.
				 */
				$data['checkout_url'] = apply_filters( 'tribe_tickets_commerce_cart_get_checkout_url_' . $cart_provider, '', $data, $post_id );

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
		$data = apply_filters( 'tribe_tickets_commerce_cart_get_data', $data, $providers, $post_id );

		return $data;
	}

	/**
	 * Update cart data.
	 *
	 * @since TBD
	 *
	 * @param array $args {
	 *      List of arguments for updating cart.
	 *
	 *      @type int|null    $post_id  Post ID.
	 *      @type string|null $provider Provider to update cart for.
	 *      @type array|null  $tickets  List of tickets to add to cart.
	 *      @type array|null  $meta     List of meta to set.
	 * }
	 *
	 * @return true|WP_Error Successful updates return true and errors are returned as WP_Error.
	 */
	public function update( $args ) {
		$post_id  = isset( $args['post_id'] ) ? $args['post_id'] : null;
		$provider = isset( $args['provider'] ) ? $args['provider'] : null;
		$tickets  = isset( $args['tickets'] ) ? $args['tickets'] : null;
		$meta     = isset( $args['meta'] ) ? $args['meta'] : null;

		// Update cart quantities.
		if ( null !== $tickets ) {
			$providers = [];
			$defaults  = [
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
					do_action( 'tribe_tickets_commerce_cart_update_tickets_' . $provider, $provider_tickets, $post_id );
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
				do_action( 'tribe_tickets_commerce_cart_update_tickets', $tickets, $provider, $post_id );
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
				do_action( 'tribe_tickets_commerce_cart_update_ticket_meta', $meta, $tickets, $provider, $post_id );
			} catch ( Tribe__REST__Exceptions__Exception $exception ) {
				return new WP_Error( $exception->getCode(), esc_html( $exception->getMessage() ), [ 'status' => $exception->getStatus() ] );
			}
		}

		return true;
	}

}
