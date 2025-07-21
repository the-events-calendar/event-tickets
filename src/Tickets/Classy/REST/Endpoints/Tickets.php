<?php
/**
 * Tickets REST API Endpoints
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Classy\REST\Endpoints
 */

declare( strict_types=1 );

namespace TEC\Tickets\Classy\REST\Endpoints;

use Exception;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Exceptions\RESTException;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Tickets as Ticket_Provider;
use WP_Error;
use WP_Post;
use WP_REST_Request as Request;
use WP_REST_Response as Response;

/**
 * Class Tickets
 *
 * @since TBD
 */
class Tickets {

	/**
	 * Get tickets endpoint.
	 *
	 * @since TBD
	 *
	 * @param Request $request The request object.
	 *
	 * @return Response|WP_Error The response object containing the tickets data.
	 */
	public function get( Request $request ) {
		try {
			$include_post = $request->get_param( 'include_post' );
			$per_page     = $request->get_param( 'per_page' ) ?? 10;
			$page         = $request->get_param( 'page' ) ?? 1;

			// Get tickets for specific posts, or all tickets if no posts are specified.
			if ( ! empty( $include_post ) ) {
				$tickets = [];
				foreach ( $include_post as $post_id ) {
					$post_tickets = $this->get_tickets_for_post( $post_id );
					if ( ! empty( $post_tickets ) ) {
						$tickets = array_merge( $tickets, $post_tickets );
					}
				}
			} else {
				$tickets = $this->get_all_tickets();
			}

			// Filter readable tickets
			$readable_tickets = $this->filter_readable_tickets( $tickets );
			$total            = count( $readable_tickets );

			// Apply pagination
			$offset  = ( $page - 1 ) * $per_page;
			$tickets = array_slice( $readable_tickets, $offset, $per_page );

			$response_data = [
				'rest_url'    => rest_url( $request->get_route() ),
				'total'       => $total,
				'total_pages' => ceil( $total / $per_page ),
				'tickets'     => $tickets,
			];

			return new Response( $response_data );
		} catch ( RESTException $e ) {
			return $e->to_wp_error();
		} catch ( Exception $e ) {
			return new WP_Error(
				'server_error',
				__( 'An error occurred while fetching tickets', 'event-tickets' ),
				[ 'status' => 500, 'message' => $e->getMessage() ]
			);
		}
	}

	/**
	 * Create ticket endpoint.
	 *
	 * @since TBD
	 *
	 * @param Request $request The request object.
	 *
	 * @return WP_Error|Response
	 */
	public function create( Request $request ) {
		try {
			return $this->upsert_ticket( $request, 'add_ticket_nonce' );
		} catch ( RESTException $e ) {
			return $e->to_wp_error();
		} catch ( Exception $e ) {
			return new WP_Error(
				'server_error',
				__( 'An error occurred while creating the ticket', 'event-tickets' ),
				[ 'status' => 500, 'message' => $e->getMessage() ]
			);
		}
	}

	/**
	 * Update ticket endpoint.
	 *
	 * @since TBD
	 *
	 * @param Request $request The request object.
	 *
	 * @return WP_Error|Response
	 */
	public function update( Request $request ) {
		try {
			return $this->upsert_ticket( $request );
		} catch ( RESTException $e ) {
			return $e->to_wp_error();
		} catch ( Exception $e ) {
			return new WP_Error(
				'server_error',
				__( 'An error occurred while updating the ticket', 'event-tickets' ),
				[ 'status' => 500, 'message' => $e->getMessage() ]
			);
		}
	}

	/**
	 * Delete ticket endpoint.
	 *
	 * @since TBD
	 *
	 * @param Request $request The request object.
	 *
	 * @return WP_Error|Response
	 */
	public function delete( Request $request ) {
		try {
			$ticket_id   = $request['id'];
			$ticket_data = $this->get_readable_ticket_data( $ticket_id );

			$provider = tribe_tickets_get_ticket_provider( $ticket_id );
			if ( empty( $provider ) ) {
				throw new RESTException( 'bad_request', __( 'Commerce Module invalid', 'event-tickets' ), 400 );
			}

			// Pass the control to the child object
			$return = $provider->delete_ticket( $ticket_data['post_id'], $ticket_id );

			// Successfully deleted?
			if ( $return ) {
				/**
				 * Fire action when a ticket has been deleted
				 *
				 * @param int $post_id ID of parent "event" post
				 */
				do_action( 'tribe_tickets_ticket_deleted', $ticket_data['post_id'] );
			}

			$response = new Response( $return );
			$response->set_status( 202 );

			return $response;
		} catch ( RESTException $e ) {
			return $e->to_wp_error();
		} catch ( Exception $e ) {
			return new WP_Error(
				'server_error',
				__( 'An error occurred while deleting the ticket', 'event-tickets' ),
				[ 'status' => 500, 'message' => $e->getMessage() ]
			);
		}
	}

	/**
	 * Add ticket callback executed to update / add a new ticket.
	 *
	 * @since TBD
	 *
	 * @param Request $request The request object.
	 *
	 * @return Response
	 * @throws RESTException
	 */
	public function upsert_ticket( Request $request ) {
		$ticket_id     = empty( $request['id'] ) ? null : $request['id'];
		$provider_name = empty( $request['provider'] ) ? null : $request['provider'];

		// Merge the defaults to avoid usage of `empty` values
		$body = array_merge(
			[
				'ticket' => [],
				'iac'    => 'none',
			],
			$request->get_default_params(),
			$request->get_params()
		);

		$post_id = $body['post_id'];

		if ( ! empty( $ticket_id ) ) {
			$provider = tribe_tickets_get_ticket_provider( $ticket_id );
		}

		if (
			empty( $provider )
			&& ! empty( $provider_name )
		) {
			$provider = Ticket_Provider::get_ticket_provider_instance( $provider_name );
		}

		if ( empty( $provider ) ) {
			throw new RESTException( 'bad_request', __( 'Commerce Module invalid', 'event-tickets' ), 400 );
		}

		// If price field is left blank, we create a free ticket.
		if ( isset( $body['price'] ) && '' === trim( $body['price'] ) ) {
			$body['price'] = '0';
		}

		$ticket_data = [
			'ticket_name'             => $body['name'],
			'ticket_description'      => $body['description'],
			'ticket_price'            => $body['price'],
			'ticket_show_description' => 'yes',
			'ticket_start_date'       => $body['start_date'],
			'ticket_start_time'       => $body['start_time'],
			'ticket_end_date'         => $body['end_date'],
			'ticket_end_time'         => $body['end_time'],
			'ticket_sku'              => $body['sku'] ?? '',
			'ticket_iac'              => $body['iac'],
			'ticket_menu_order'       => $body['menu_order'],
			'tribe-ticket'            => $body['ticket'],
		];

		if ( null !== $ticket_id ) {
			$ticket_data['ticket_id'] = $ticket_id;
		}

		// Get the Ticket Object
		$ticket = $provider->ticket_add( $post_id, $ticket_data );

		if ( empty( $ticket ) ) {
			throw new RESTException(
				'not_acceptable',
				esc_html(
					sprintf(
						__( '%s was not able to be updated', 'event-tickets' ),
						tribe_get_ticket_label_singular( 'rest_add_ticket_error' )
					)
				),
				406
			);
		}

		/**
		 * Fires after a ticket has been added.
		 *
		 * @since TBD
		 *
		 * @param int                 $post_id     ID of post the ticket is attached to.
		 * @param int                 $ticket      Ticket ID that was just added.
		 * @param array<string,mixed> $ticket_data The body of the request.
		 */
		do_action( 'tribe_tickets_ticket_added', $post_id, $ticket, $ticket_data );

		$response = new Response( $this->get_readable_ticket_data( $ticket ) );
		$response->set_status( 202 );

		return $response;
	}

	/**
	 * Get readable ticket data with all Ticket type fields.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return array
	 * @throws RESTException
	 */
	protected function get_readable_ticket_data( $ticket_id ) {
		$ticket_post = get_post( $ticket_id );

		if ( ! $ticket_post instanceof WP_Post ) {
			throw new RESTException( 'ticket-not-found', __( 'Ticket not found', 'event-tickets' ), 404 );
		}

		$provider = tribe_tickets_get_ticket_provider( $ticket_id );
		if ( empty( $provider ) ) {
			throw new RESTException(
				'ticket-provider-not-found',
				__( 'Ticket provider not found', 'event-tickets' ),
				500
			);
		}

		$ticket = $provider->get_ticket( $ticket_post->ID, $ticket_id );
		if ( ! $ticket ) {
			throw new RESTException( 'ticket-object-not-found', __( 'Ticket object not found', 'event-tickets' ), 500 );
		}

		// Build complete ticket data matching the Ticket type
		return [
			// API response fields
			'id'                          => $ticket_id,
			'eventId'                     => $ticket->get_event_id(),
			'provider'                    => $this->get_provider_slug( $provider ),
			'type'                        => $ticket->type() ?: 'default',
			'globalId'                    => $this->generate_global_id( $ticket_id ),
			'globalIdLineage'             => $this->get_global_id_lineage( $ticket_id ),
			'title'                       => $ticket->name,
			'description'                 => $ticket->description,
			'image'                       => $this->get_ticket_image( $ticket_id ),
			'menuOrder'                   => $ticket->menu_order,

			// Availability
			'availableFrom'               => $this->format_date_time( $ticket->start_date, $ticket->start_time ),
			'availableFromDetails'        => $this->parse_date_details( $ticket->start_date, $ticket->start_time ),
			'availableUntil'              => $this->format_date_time( $ticket->end_date, $ticket->end_time ),
			'availableUntilDetails'       => $this->parse_date_details( $ticket->end_date, $ticket->end_time ),
			'isAvailable'                 => $this->is_ticket_available( $ticket ),
			'onSale'                      => $ticket->on_sale,

			// Capacity
			'capacity'                    => $ticket->capacity(),
			'capacityDetails'             => $this->get_capacity_details( $ticket ),

			// Pricing
			'cost'                        => $this->format_cost( $ticket->price ),
			'costDetails'                 => $this->get_cost_details( $ticket ),
			'price'                       => (float) $ticket->price,
			'priceSuffix'                 => null,

			// Sale price
			'salePriceData'               => $this->get_sale_price_data( $ticket_id ),

			// Features
			'supportsAttendeeInformation' => $this->supports_attendee_information( $ticket ),
			'iac'                         => $ticket->iac ?: '',

			// Attendees and checkin
			'attendees'                   => $this->get_ticket_attendees( $ticket_id ),
			'checkin'                     => $this->get_checkin_details( $ticket_id ),

			// Fees
			'fees'                        => $this->get_fees_data( $ticket_id ),

			// Additional fields for API compatibility
			'post_id'                     => $ticket->get_event_id(),
			'name'                        => $ticket->name,
			'start_date'                  => $ticket->start_date,
			'start_time'                  => $ticket->start_time,
			'end_date'                    => $ticket->end_date,
			'end_time'                    => $ticket->end_time,
			'menu_order'                  => $ticket->menu_order,
		];
	}

	/**
	 * Get tickets for a specific post.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return array
	 */
	protected function get_tickets_for_post( $post_id ) {
		$tickets = Ticket_Provider::get_event_tickets( $post_id );

		return is_array( $tickets ) ? $tickets : [];
	}

	/**
	 * Get all tickets.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	protected function get_all_tickets() {
		// This is a placeholder - in a real implementation, you'd query all tickets
		$tickets = [];
		$posts   = get_posts(
			[
				'post_type'      => [ 'tribe_tpp_tickets', 'tribe_rsvp_tickets', 'tec_tc_ticket' ],
				'posts_per_page' => -1,
			]
		);

		foreach ( $posts as $post ) {
			$tickets[] = $post;
		}

		return $tickets;
	}

	/**
	 * Filter readable tickets.
	 *
	 * @since TBD
	 *
	 * @param array $tickets The tickets to filter.
	 *
	 * @return array
	 */
	protected function filter_readable_tickets( $tickets ) {
		$readable = [];

		foreach ( $tickets as $ticket ) {
			$ticket_id = is_object( $ticket ) ? $ticket->ID : $ticket;
			try {
				$ticket_data = $this->get_readable_ticket_data( $ticket_id );
				$readable[]  = $ticket_data;
			} catch ( RESTException $e ) {
				// Skip tickets that can't be read
				continue;
			}
		}

		return $readable;
	}

	/**
	 * Validate if request has a valid nonce and user has valid permission
	 *
	 * @since TBD
	 *
	 * @param int|WP_Post|null $post_id      The post ID.
	 * @param string           $nonce        The nonce.
	 * @param string           $nonce_action The nonce action.
	 *
	 * @return bool
	 */
	private function has_permission( $post_id, $nonce, $nonce_action ) {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			return false;
		}

		return current_user_can( 'edit_event_tickets' )
		       || current_user_can( get_post_type_object( $post->post_type )->cap->edit_others_posts )
		       || current_user_can( 'edit_post', $post->ID );
	}

	/**
	 * Get provider slug.
	 *
	 * @since TBD
	 *
	 * @param object $provider The provider object.
	 *
	 * @return string
	 */
	protected function get_provider_slug( $provider ) {
		$class = get_class( $provider );

		$provider_map = [
			PayPal::class => 'paypal',
			Module::class => 'tc',
			RSVP::class   => 'rsvp',
		];

		return $provider_map[ $class ] ?? 'unknown';
	}

	/**
	 * Generate global ID for ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return string
	 */
	protected function generate_global_id( $ticket_id ) {
		return "ticket_{$ticket_id}";
	}

	/**
	 * Get global ID lineage.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return array
	 */
	protected function get_global_id_lineage( $ticket_id ) {
		return [ $this->generate_global_id( $ticket_id ) ];
	}

	/**
	 * Get ticket image.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return bool|string
	 */
	protected function get_ticket_image( $ticket_id ) {
		// Placeholder - return false for no image
		return false;
	}

	/**
	 * Format date and time.
	 *
	 * @since TBD
	 *
	 * @param string $date The date.
	 * @param string $time The time.
	 *
	 * @return string|null
	 */
	protected function format_date_time( $date, $time ) {
		if ( empty( $date ) ) {
			return null;
		}

		$date_time = $date;
		if ( ! empty( $time ) ) {
			$date_time .= ' ' . $time;
		}

		return $date_time;
	}

	/**
	 * Parse date details.
	 *
	 * @since TBD
	 *
	 * @param string $date The date.
	 * @param string $time The time.
	 *
	 * @return array
	 */
	protected function parse_date_details( $date, $time ) {
		if ( empty( $date ) ) {
			return [];
		}

		$date_time = $date;
		if ( ! empty( $time ) ) {
			$date_time .= ' ' . $time;
		}

		$timestamp = strtotime( $date_time );
		if ( false === $timestamp ) {
			return [];
		}

		return [
			'year'   => (int) date( 'Y', $timestamp ),
			'month'  => (int) date( 'n', $timestamp ),
			'day'    => (int) date( 'j', $timestamp ),
			'hour'   => (int) date( 'G', $timestamp ),
			'minute' => (int) date( 'i', $timestamp ),
			'second' => (int) date( 's', $timestamp ),
		];
	}

	/**
	 * Check if ticket is available.
	 *
	 * @since TBD
	 *
	 * @param object $ticket The ticket object.
	 *
	 * @return bool
	 */
	protected function is_ticket_available( $ticket ) {
		$capacity = $ticket->capacity();

		return $capacity > 0 || $capacity === -1; // -1 means unlimited
	}

	/**
	 * Get capacity details.
	 *
	 * @since TBD
	 *
	 * @param object $ticket The ticket object.
	 *
	 * @return array
	 */
	protected function get_capacity_details( $ticket ) {
		$capacity             = $ticket->capacity();
		$sold                 = $ticket->qty_sold();
		$pending              = $ticket->qty_pending();
		$available            = $capacity === -1 ? 999999 : max( 0, $capacity - $sold - $pending );
		$available_percentage = $capacity === -1 ? 100 : ( $capacity > 0 ? ( $available / $capacity ) * 100 : 0 );

		return [
			'available'           => $available,
			'availablePercentage' => min( 100, max( 0, $available_percentage ) ),
			'max'                 => $capacity === -1 ? 0 : $capacity,
			'sold'                => $sold,
			'pending'             => $pending,
			'globalStockMode'     => $ticket->global_stock_mode() ?: 'own',
		];
	}

	/**
	 * Format cost.
	 *
	 * @since TBD
	 *
	 * @param string $price The price.
	 *
	 * @return string
	 */
	protected function format_cost( $price ) {
		return '$' . number_format( (float) $price, 2 );
	}

	/**
	 * Get cost details.
	 *
	 * @since TBD
	 *
	 * @param object $ticket The ticket object.
	 *
	 * @return array
	 */
	protected function get_cost_details( $ticket ) {
		return [
			'currencySymbol'            => '$',
			'currencyPosition'          => 'left',
			'currencyDecimalSeparator'  => '.',
			'currencyThousandSeparator' => ',',
			'suffix'                    => null,
			'values'                    => [ (float) $ticket->price ],
		];
	}

	/**
	 * Get sale price data.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return array
	 */
	protected function get_sale_price_data( $ticket_id ) {
		// Placeholder - return default sale price data
		return [
			'enabled'   => false,
			'endDate'   => null,
			'salePrice' => '',
			'startDate' => null,
		];
	}

	/**
	 * Check if ticket supports attendee information.
	 *
	 * @since TBD
	 *
	 * @param object $ticket The ticket object.
	 *
	 * @return bool
	 */
	protected function supports_attendee_information( $ticket ) {
		// Placeholder - return false for now
		return false;
	}

	/**
	 * Get ticket attendees.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return array
	 */
	protected function get_ticket_attendees( $ticket_id ) {
		// Placeholder - return empty array
		return [];
	}

	/**
	 * Get checkin details.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return array
	 */
	protected function get_checkin_details( $ticket_id ) {
		// Placeholder - return default checkin data
		return [
			'checkedIn'             => 0,
			'uncheckedIn'           => 0,
			'checkedInPercentage'   => 0,
			'uncheckedInPercentage' => 100,
		];
	}

	/**
	 * Get fees data.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return array
	 */
	protected function get_fees_data( $ticket_id ) {
		// Placeholder - return empty fees data
		return [
			'availableFees' => [],
			'automaticFees' => [],
			'selectedFees'  => [],
		];
	}

	/**
	 * Get tickets endpoint arguments.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function GET_args() {
		return [
			'include_post' => [
				'type'        => 'array',
				'in'          => 'query',
				'description' => __( 'Array of post IDs to include tickets for', 'event-tickets' ),
				'required'    => false,
				'items'       => [
					'type' => 'integer',
				],
			],
			'per_page'     => [
				'type'        => 'integer',
				'in'          => 'query',
				'description' => __( 'Number of tickets per page', 'event-tickets' ),
				'required'    => false,
				'default'     => 10,
			],
			'page'         => [
				'type'        => 'integer',
				'in'          => 'query',
				'description' => __( 'Page number', 'event-tickets' ),
				'required'    => false,
				'default'     => 1,
			],
		];
	}

	/**
	 * Create ticket endpoint arguments.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function CREATE_args() {
		return [
			'post_id'          => [
				'type'              => 'string',
				'in'                => 'body',
				'description'       => __( 'The post ID to attach the ticket to', 'event-tickets' ),
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			],
			'name'             => [
				'type'              => 'string',
				'in'                => 'body',
				'description'       => __( 'The ticket name', 'event-tickets' ),
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			],
			'description'      => [
				'type'              => 'string',
				'in'                => 'body',
				'description'       => __( 'The ticket description', 'event-tickets' ),
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			],
			'price'            => [
				'type'              => 'string',
				'in'                => 'body',
				'description'       => __( 'The ticket price', 'event-tickets' ),
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			],
			'provider'         => [
				'type'              => 'string',
				'in'                => 'body',
				'description'       => __( 'The ticket provider', 'event-tickets' ),
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			],
			'type'             => [
				'type'              => 'string',
				'in'                => 'body',
				'description'       => __( 'The ticket type', 'event-tickets' ),
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'default',
			],
			'start_date'       => [
				'type'              => 'string',
				'in'                => 'body',
				'description'       => __( 'The ticket start date', 'event-tickets' ),
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			],
			'start_time'       => [
				'type'              => 'string',
				'in'                => 'body',
				'description'       => __( 'The ticket start time', 'event-tickets' ),
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			],
			'end_date'         => [
				'type'              => 'string',
				'in'                => 'body',
				'description'       => __( 'The ticket end date', 'event-tickets' ),
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			],
			'end_time'         => [
				'type'              => 'string',
				'in'                => 'body',
				'description'       => __( 'The ticket end time', 'event-tickets' ),
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			],
			'iac'              => [
				'type'              => 'string',
				'in'                => 'body',
				'description'       => __( 'The ticket IAC setting', 'event-tickets' ),
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			],
			'ticket'           => [
				'type'     => 'object',
				'in'       => 'body',
				'required' => false,
				'default'  => [],
			],
			'menu_order'       => [
				'type'              => 'string',
				'in'                => 'body',
				'description'       => __( 'The ticket menu order', 'event-tickets' ),
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '0',
			],
			'add_ticket_nonce' => [
				'type'              => 'string',
				'in'                => 'body',
				'description'       => __( 'The nonce for adding tickets', 'event-tickets' ),
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}

	/**
	 * Update ticket endpoint arguments.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function EDIT_args() {
		return array_merge(
			[
				'id'                => [
					'type'        => 'integer',
					'in'          => 'path',
					'description' => __( 'The ticket ID', 'event-tickets' ),
					'required'    => true,
				],
				'edit_ticket_nonce' => [
					'type'              => 'string',
					'in'                => 'body',
					'description'       => __( 'The nonce for editing tickets', 'event-tickets' ),
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
			$this->CREATE_args()
		);
	}

	/**
	 * Delete ticket endpoint arguments.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function DELETE_args() {
		return [
			'id'                  => [
				'type'        => 'integer',
				'in'          => 'path',
				'description' => __( 'The ticket ID', 'event-tickets' ),
				'required'    => true,
			],
			'remove_ticket_nonce' => [
				'type'              => 'string',
				'in'                => 'body',
				'description'       => __( 'The nonce for removing tickets', 'event-tickets' ),
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}
}
