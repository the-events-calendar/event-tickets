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
	 * Registers the controller by subscribing to front-end hooks and binding implementations.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tribe_template_pre_html:tickets/v2/tickets', [ $this, 'print_tickets_block' ], 10, 5 );

		$data = fn() => [
			'objectName' => 'dialog_obj_' . self::MODAL_ID,
			'modalId' => self::MODAL_ID,
		];

		// Register the front-end JS.
		Asset::add(
			'tec-tickets-seating-frontend',
			$this->built_asset_url( 'frontend/tickets-block.js' ),
			ET::VERSION
		)
			->set_dependencies(
				'tribe-dialog-js',
				'tec-tickets-seating-service-bundle'
			)
			->add_localize_script('tec.seating.frontend.ticketsBlock',$data)
			->enqueue_on( 'wp_enqueue_scripts' )
			->add_to_group( 'tec-tickets-seating-frontend' )
			->add_to_group( 'tec-tickets-seating' )
			->register();

		// Register the front-end CSS.
		Asset::add(
			'tec-tickets-seating-frontend-style',
			$this->built_asset_url( 'frontend/tickets-block.css' ),
			ET::VERSION
		)
			->set_dependencies( 'tribe-dialog' )
			->enqueue_on( 'wp_enqueue_scripts' )
			->add_to_group( 'tec-tickets-seating-frontend' )
			->add_to_group( 'tec-tickets-seating' )
			->register();
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

		$prices   = [];
		$provider = Tickets::get_event_ticket_provider_object( $post_id );
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

		$cost_range = tribe_format_currency( min( $prices ), $post_id )
		              . ' - '
		              . tribe_format_currency( max( $prices ), $post_id );

		/**
		 * @var Tickets_Handler $tickets_handler
		 */
		$tickets_handler   = tribe( 'tickets.handler' );
		$capacity_meta_key = $tickets_handler->key_capacity;
		$inventory         = get_post_meta( $post_id, $capacity_meta_key, true );

		/**
		 * Filters the seat selection timeout.
		 *
		 * @since TBD
		 *
		 * @param int $timeout The timeout in seconds.
		 * @param int $post_id The post ID the iframe is for.
		 */
		$timeout = apply_filters( 'tec_tickets_seating_selection_timeout', 15 * 60, $post_id );

		return $this->template->template(
			'tickets-block',
			[
				'cost_range'    => $cost_range,
				'inventory'     => $inventory,
				'modal_content' => $this->get_seat_selection_modal_content( $post_id, $timeout ),
				'timeout'       => $timeout,
			],
			false
		);
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
		$iframe_url      = $this->service->get_seat_selection_url( $token, $post_id, $timeout );
		/** @var \Tribe\Dialog\View $dialog_view */
		$dialog_view = tribe( 'dialog.view' );
		$content     = $this->template->template( 'iframe-view', [
			'iframe_url' => $iframe_url,
			'token'      => $token,
			'error'      => $ephemeral_token instanceof WP_Error ? $ephemeral_token->get_error_message() : '',
		], false );
		$args        = [
			'button_text'    => esc_html_x( 'Find Seats', 'Find seats button text', 'event-tickets' ),
			'button_classes' => [ 'tribe-common-c-btn', 'tribe-common-c-btn--small' ],
			'append_target'  => '.tec-tickets-seating__tickets-block__information',
			'content_wrapper_classes' =>  'tribe-dialog__wrapper tec-tickets-seating__seat-selection-modal'
		];

		return $dialog_view->render_modal( $content, $args, self::MODAL_ID, false );
	}
}
