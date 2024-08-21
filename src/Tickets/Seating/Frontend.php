<?php
/**
 * The main front-end controller. This controller will directly, or by delegation, subscribe to
 * front-end related hooks.
 *
 * @since   TBD
 *
 * @package TEC\Controller;
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Common\StellarWP\Assets\Asset;
use TEC\Tickets\Seating\Admin\Ajax;
use TEC\Tickets\Seating\Frontend\Timer;
use TEC\Tickets\Seating\Service\Service;
use Tribe__Template as Base_Template;
use Tribe__Tickets__Main as ET;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;
use WP_Error;

/**
 * Class Controller.
 *
 * @since   TBD
 *
 * @package TEC\Controller;
 */
class Frontend extends Controller_Contract {
	use Built_Assets;

	/**
	 * The ID of the modal used to display the seat selection modal.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const MODAL_ID = 'tec-tickets-seating-seat-selection-modal';

	/**
	 * The action that will be fired when this Controller registers.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $registration_action = 'tec_tickets_seating_frontend_registered';

	/**
	 * A reference to the template object.
	 *
	 * @since TBD
	 *
	 * @var Template
	 */
	private Template $template;

	/**
	 * A reference to the service object.
	 *
	 * @since TBD
	 *
	 * @var Service
	 */
	private Service $service;

	/**
	 * Controller constructor.
	 *
	 * @since TBD
	 *
	 * @param Container $container A reference to the container object.
	 * @param Template  $template  A reference to the template object.
	 * @param Service   $service   A reference to the service object.
	 */
	public function __construct( Container $container, Template $template, Service $service ) {
		parent::__construct( $container );
		$this->template = $template;
		$this->service  = $service;
	}

	/**
	 * Unregisters the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tribe_template_pre_html:tickets/v2/tickets', [ $this, 'print_tickets_block' ] );
	}

	/**
	 * Replace the Tickets' block with the one starting the seat selection flow.
	 *
	 * @since TBD
	 *
	 * @param string              $html     The initial HTML.
	 * @param string              $file     Complete path to include the PHP File.
	 * @param array               $name     Template name.
	 * @param Base_Template       $template Current instance of the Tribe__Template.
	 * @param array<string,mixed> $context  The context data passed to the template.
	 *
	 * @return string|null The template HTML, or `null` to let the default template process it.
	 */
	public function print_tickets_block( $html, $file, $name, $template, $context ): ?string {
		$data    = $template->get_values();
		$post_id = $data['post_id'];

		if ( ! tec_tickets_seating_enabled( $post_id ) ) {
			return $html;
		}

		$provider = Tickets::get_event_ticket_provider_object( $post_id );

		if ( ! $provider ) {
			return $html;
		}

		$prices = [];

		foreach ( tribe_tickets()->where( 'event', $post_id )->get_ids( true ) as $ticket_id ) {
			$ticket = $provider->get_ticket( $post_id, $ticket_id );
			if ( ! $ticket ) {
				continue;
			}
			$prices[] = $ticket->price;
		}

		if ( ! count( $prices ) ) {
			// Why are we here at all?
			return $html;
		}

		$inventory  = $this->get_events_ticket_capacity_for_seating( $post_id );
		$cost_range = tribe_format_currency( min( $prices ), $post_id )
						. ' - '
						. tribe_format_currency( max( $prices ), $post_id );

		$timeout = $this->container->get( Timer::class )->get_timeout( $post_id );

		$html = $this->template->template(
			'tickets-block',
			[
				'cost_range'    => $cost_range,
				'inventory'     => $inventory,
				'modal_content' => $this->get_seat_selection_modal_content( $post_id, $timeout ),
				'timeout'       => $timeout,
			],
			false
		);

		/**
		 * Filters the contents of the Tickets block.
		 *
		 * @since TBD
		 *
		 * @param string   $html     The HTML of the Tickets block.
		 * @param Template $template A reference to the template object.
		 */
		$html = apply_filters( 'tec_tickets_seating_tickets_block_html', $html, $template );

		return $html;
	}

	/**
	 * Adjusts the event's ticket capacity to consider seating.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return int
	 */
	public function get_events_ticket_capacity_for_seating( int $event_id ): int {
		if ( ! tec_tickets_seating_enabled( $event_id ) ) {
			return 0;
		}

		$provider = Tickets::get_event_ticket_provider_object( $event_id );

		if ( ! $provider ) {
			return 0;
		}

		$available = [];

		foreach ( tribe_tickets()->where( 'event', $event_id )->get_ids( true ) as $ticket_id ) {
			$ticket = $provider->get_ticket( $event_id, $ticket_id );
			if ( ! $ticket ) {
				continue;
			}

			// The array's keys are the seating types. In order for us to calculate the stock per type and NOT per ticket.
			$available[ get_post_meta( $ticket->ID, Meta::META_KEY_SEAT_TYPE, true ) ] = $ticket->stock();
		}

		if ( empty( $available ) ) {
			return 0;
		}

		return array_sum( $available );
	}

	/**
	 * Returns the HTML content of the seat selection modal.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID of the post to purchase tickets for.
	 * @param int $timeout The timeout in seconds.
	 *
	 * @return string The HTML content of the seat selection modal.
	 */
	private function get_seat_selection_modal_content( int $post_id, int $timeout ): string {
		$ephemeral_token = $this->service->get_ephemeral_token();
		$token           = is_string( $ephemeral_token ) ? $ephemeral_token : '';

		/*
		 * While the user might have 15 minutes to purchase tickets, that timer will not start on page load,
		 * but when the user starts the interaction withe the seat selection modal.
		 * For this reason the token request is made with a TTL of 4 times the timeout.
		 */
		$ephemeral_token_ttl = $timeout * 4;
		$iframe_url          = $this->service->get_seat_selection_url( $token, $post_id, $ephemeral_token_ttl );

		/** @var \Tribe\Dialog\View $dialog_view */
		$dialog_view = tribe( 'dialog.view' );
		$provider    = Tickets::get_event_ticket_provider_object( $post_id );
		/** @var \Tribe__Tickets__Commerce__Currency $currency */
		$currency = tribe( 'tickets.commerce.currency' );
		$content  = $this->template->template(
			'iframe-view',
			[
				'iframe_url'          => $iframe_url,
				'token'               => $token,
				'error'               => $ephemeral_token instanceof WP_Error ? $ephemeral_token->get_error_message() : '',
				'initial_total_text'  => _x( '0 Tickets', 'Seat selection modal initial total string', 'event-tickets' ),
				'initial_total_price' => $currency->get_formatted_currency_with_symbol( 0, $post_id, $provider, false ),
				'post_id'             => $post_id,
			],
			false
		);

		$args = [
			'button_text'             => esc_html_x( 'Find Seats', 'Find seats button text', 'event-tickets' ),
			'button_classes'          => [ 'tribe-common-c-btn', 'tribe-common-c-btn--small' ],
			'append_target'           => '.tec-tickets-seating__information',
			'content_wrapper_classes' => 'tribe-dialog__wrapper tec-tickets-seating__modal',
		];

		return $dialog_view->render_modal( $content, $args, self::MODAL_ID, false );
	}

	/**
	 * Registers the controller by subscribing to front-end hooks and binding implementations.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tribe_template_pre_html:tickets/v2/tickets', [ $this, 'print_tickets_block' ], 10, 5 );

		// Register the front-end JS.
		Asset::add(
			'tec-tickets-seating-frontend',
			$this->built_asset_url( 'frontend/ticketsBlock.js' ),
			ET::VERSION
		)
			->set_dependencies(
				'tribe-dialog-js',
				'tec-tickets-seating-service-bundle',
				'tec-tickets-seating-currency',
				'wp-hooks',
				'tec-tickets-seating-session'
			)
			->add_localize_script(
				'tec.tickets.seating.frontend.ticketsBlock',
				fn() => $this->get_ticket_block_data( get_the_ID() )
			)
			->enqueue_on( 'wp_enqueue_scripts' )
			->add_to_group( 'tec-tickets-seating-frontend' )
			->add_to_group( 'tec-tickets-seating' )
			->register();

		// Register the front-end CSS.
		Asset::add(
			'tec-tickets-seating-frontend-style',
			$this->built_asset_url( 'frontend/ticketsBlock.css' ),
			ET::VERSION
		)
			->enqueue_on( 'wp_enqueue_scripts' )
			->add_to_group( 'tec-tickets-seating-frontend' )
			->add_to_group( 'tec-tickets-seating' )
			->register();
	}

	/**
	 * Returns the data to be localized on the ticket block frontend.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return array{
	 *     objectName: string,
	 *     modalId: string,
	 *     seatTypeMap: array<array{
	 *         id: string,
	 *         tickets: array<array{
	 *             ticketId: string,
	 *             name: string,
	 *             price: string,
	 *             description: string
	 *         }>
	 *     }>
	 * } The data to be localized on the ticket block frontend.
	 */
	public function get_ticket_block_data( $post_id ): array {
		return [
			'objectName'                => 'dialog_obj_' . self::MODAL_ID,
			'modalId'                   => self::MODAL_ID,
			'seatTypeMap'               => $this->build_seat_type_map( $post_id ),
			'labels'                    => [
				'oneTicket'       => esc_html( _x( '1 Ticket', 'Seat selection modal total string', 'event-tickets' ) ),
				'multipleTickets' => esc_html(
					_x( '{count} Tickets', 'Seat selection modal total string', 'event-tickets' )
				),
			],
			'providerClass'             => esc_html( Tickets::get_event_ticket_provider( $post_id ) ),
			'postId'                    => $post_id,
			'ajaxUrl'                   => admin_url( 'admin-ajax.php' ),
			'ajaxNonce'                 => wp_create_nonce( Ajax::NONCE_ACTION ),
			'ACTION_POST_RESERVATIONS'  => Ajax::ACTION_POST_RESERVATIONS,
			'ACTION_CLEAR_RESERVATIONS' => Ajax::ACTION_CLEAR_RESERVATIONS,
		];
	}

	/**
	 * Builds the Seat Type map localized for the seat selection modal.
	 *
	 * @since TBD
	 *
	 * @param int|null $post_id The current post ID.
	 *
	 * @return array{
	 *     id: string,
	 *     tickets: array<array{
	 *         ticketId: string,
	 *         name: string,
	 *         price: string,
	 *         description: string
	 *     }>
	 * } The Seat Type map.
	 */
	public function build_seat_type_map( ?int $post_id = null ): array {
		if ( ! $post_id ) {
			return [];
		}

		$seat_type_map = [];
		$provider      = null;

		foreach ( tribe_tickets()->where( 'event', $post_id )->get_ids( true ) as $ticket_id ) {
			// The provider will be the same for all tickets in the loop, just init once.
			/** @var \Tribe__Tickets__Tickets $provider */
			$provider ??= tribe_tickets_get_ticket_provider( $ticket_id );
			$ticket     = $provider->get_ticket( $post_id, $ticket_id );

			if ( ! $ticket instanceof \Tribe__Tickets__Ticket_Object ) {
				continue;
			}

			$seat_type = get_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, true );

			if ( ! $seat_type ) {
				continue;
			}

			if ( ! isset( $seat_type_map[ $seat_type ] ) ) {
				$seat_type_map[ $seat_type ] = [
					'id'      => $seat_type,
					'tickets' => [],
				];
			}

			$seat_type_map[ $seat_type ]['tickets'][] = [
				'ticketId'    => $ticket_id,
				'name'        => $ticket->name,
				'price'       => $ticket->price,
				'description' => $ticket->description,
				'dateInRange' => $ticket->date_in_range(),
			];
		}

		return $seat_type_map;
	}
}
