<?php
/**
 * Provides the information required to register the Tickets block server-side.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Blocks\Tickets;
 */

namespace TEC\Tickets\Blocks\Tickets;

use Tribe__Editor__Blocks__Abstract as Abstract_Block;
use Tribe__Tickets__Editor__Template as Template;
use Tribe__Tickets__Main as Tickets_Main;
use Tribe__Tickets__RSVP as RSVP;
use Tribe__Tickets__Status__Manager as Status_Manager;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;
use Tribe__Tickets__Tickets_View as Tickets_View;
use TEC\Common\Asset;

/**
 * Class Block.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Blocks\Tickets;
 */
class Block extends Abstract_Block {

	/**
	 * The slug of the editor script.
	 *
	 * @since 5.20.0
	 *
	 * @var string
	 */
	public const EDITOR_SCRIPT_SLUG = 'tec-tickets-tickets-block-editor-script';

	/**
	 * The slug of the editor style.
	 *
	 * @since 5.20.0
	 *
	 * @var string
	 */
	public const EDITOR_STYLE_SLUG = 'tec-tickets-tickets-block-editor-style';

	/**
	 * The slug of the frontend script.
	 *
	 * @since 5.20.0
	 *
	 * @var string
	 */
	public const FRONTEND_SCRIPT_SLUG = 'tribe-tickets-block';

	/**
	 * Hooks the block on the required actions.
	 *
	 * @since 5.8.0
	 */
	public function hook() {
		add_action( 'wp_ajax_ticket_availability_check', [ $this, 'ticket_availability' ] );
		add_action( 'wp_ajax_nopriv_ticket_availability_check', [ $this, 'ticket_availability' ] );
	}

	/**
	 * Which is the name/slug of this block
	 *
	 * @since 4.9
	 *
	 * @return string
	 */
	public function slug() {
		return 'tickets';
	}

	/**
	 * Since we are dealing with a Dynamic type of Block we need a PHP method to render it
	 *
	 * @since 4.9
	 *
	 * @param array<string,mixed> $attributes The attributes to use when rendering the block.
	 *
	 * @return string
	 */
	public function render( $attributes = [] ) {
		/** @var Template $template */
		$template     = tribe( 'tickets.editor.template' );
		$post_id      = $template->get( 'post_id', null, false );
		$tickets_view = Tickets_View::instance();

		return $tickets_view->get_tickets_block( $post_id, false );
	}

	/**
	 * Register block assets
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	public function assets() {
		// Check whether we use v1 or v2. We need to update this when we deprecate tickets v1.
		$tickets_js = tribe_tickets_new_views_is_enabled() ? 'v2/tickets-block.js' : 'tickets-block.js';
		$plugin     = Tickets_Main::instance();

		tribe_asset(
			$plugin,
			self::FRONTEND_SCRIPT_SLUG,
			$tickets_js,
			[
				'jquery',
				'wp-util',
				'wp-i18n',
				'wp-hooks',
				'tribe-common',
			],
			null,
			[
				'type'     => 'js',
				'groups'   => [ 'tribe-tickets-block-assets' ],
				'localize' => [
					[
						'name' => 'TribeTicketOptions',
						'data' => [ 'Tribe__Tickets__Tickets', 'get_asset_localize_data_for_ticket_options' ],
					],
					[
						'name' => 'TribeCurrency',
						'data' => [ 'Tribe__Tickets__Tickets', 'get_asset_localize_data_for_currencies' ],
					],
					[
						'name' => 'TribeCartEndpoint',
						'data' => static function () {
							return [ 'url' => tribe_tickets_rest_url( '/cart/' ) ];
						},
					],
					[
						'name' => 'TribeMessages',
						'data' => [ $this, 'set_messages' ],
					],
					[
						'name' => 'TribeTicketsURLs',
						'data' => [ 'Tribe__Tickets__Tickets', 'get_asset_localize_data_for_cart_checkout_urls' ],
					],
				],
			]
		);

		Tickets::$frontend_script_enqueued = true;
	}

	/**
	 * Overrides the parent method to register the editor scripts.
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	public function register() {
		parent::register();
		add_action( 'admin_enqueue_scripts', [ $this, 'register_editor_scripts' ] );
	}

	/**
	 * Check for ticket availability
	 *
	 * @since 4.9
	 *
	 * @param int[] $tickets The IDs of tickets to check.
	 *
	 * @return void
	 */
	public function ticket_availability( $tickets = [] ) {
		$response  = [ 'html' => '' ];
		$tickets ??= tribe_get_request_var( 'tickets', [] );

		// Bail if we receive no tickets.
		if ( empty( $tickets ) ) {
			wp_send_json_error( $response );
		}

		/** @var Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		/** @var Template $tickets_editor */
		$tickets_editor = tribe( 'tickets.editor.template' );

		// Parse the tickets and create the array for the response.
		foreach ( $tickets as $ticket_id ) {
			$ticket = Tickets::load_ticket_object( $ticket_id );

			if (
				! $ticket instanceof Ticket_Object
				|| empty( $ticket->ID )
			) {
				continue;
			}

			$available     = $ticket->available();
			$max_at_a_time = $tickets_handler->get_ticket_max_purchase( $ticket->ID );

			$response['tickets'][ $ticket_id ]['available']    = $available;
			$response['tickets'][ $ticket_id ]['max_purchase'] = $max_at_a_time;

			// If there are no more available we will send the template part HTML to update the DOM.
			if ( 0 === $available ) {
				$response['tickets'][ $ticket_id ]['unavailable_html'] = $tickets_editor->template( 'blocks/tickets/quantity-unavailable', $ticket, false );
			}
		}

		wp_send_json_success( $response );
	}

	/**
	 * Get all tickets for event/post, other than RSVP type because they're presented in a separate block.
	 *
	 * @since 4.9
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array
	 */
	public function get_tickets( $post_id ) {
		$all_tickets = Tickets::get_all_event_tickets( $post_id );

		if ( ! $all_tickets ) {
			return [];
		}

		/** @var RSVP $rsvp */
		$rsvp = tribe( 'tickets.rsvp' );

		$tickets = [];

		// We only want RSVP tickets.
		foreach ( $all_tickets as $ticket ) {
			if (
				! $ticket instanceof Ticket_Object
				|| $rsvp->class_name === $ticket->provider_class
			) {
				continue;
			}

			$tickets[] = $ticket;
		}

		return $tickets;
	}

	/**
	 * Get provider ID/slug.
	 *
	 * @since 4.9
	 * @since 4.12.3 Retrieve slug from updated Ticktes Status Manager method.
	 *
	 * @param Tickets $provider Provider class instance.
	 *
	 * @return string
	 */
	public function get_provider_id( $provider ) {
		/** @var Status_Manager $status */
		$status = tribe( 'tickets.status' );

		$slug = $status->get_provider_slug( $provider );

		if (
			empty( $slug )
			|| 'rsvp' === $slug
		) {
			$slug = 'tpp';
		}

		return $slug;
	}

	/**
	 * Get all tickets on sale
	 *
	 * @since 4.9
	 *
	 * @param array $tickets Array of all tickets.
	 *
	 * @return array
	 */
	public function get_tickets_on_sale( $tickets ) {
		$tickets_on_sale = [];

		foreach ( $tickets as $ticket ) {
			if ( tribe_events_ticket_is_on_sale( $ticket ) ) {
				$tickets_on_sale[] = $ticket;
			}
		}

		return $tickets_on_sale;
	}

	/**
	 * Get whether all ticket sales have passed or not
	 *
	 * @since 4.9
	 *
	 * @param array $tickets Array of all tickets.
	 *
	 * @return bool
	 */
	public function get_is_sale_past( $tickets ) {
		$is_sale_past = ! empty( $tickets );

		foreach ( $tickets as $ticket ) {
			$is_sale_past = ( $is_sale_past && $ticket->date_is_later() );
		}

		return $is_sale_past;
	}

	/**
	 * Get whether no ticket sales have started yet
	 *
	 * @since 4.11.0
	 *
	 * @param array $tickets Array of all tickets.
	 *
	 * @return bool
	 */
	public function get_is_sale_future( $tickets ) {
		$is_sale_future = ! empty( $tickets );

		foreach ( $tickets as $ticket ) {
			$is_sale_future = ( $is_sale_future && $ticket->date_is_earlier() );
		}

		return $is_sale_future;
	}

	/**
	 * Localized messages for errors, etc in javascript. Added in assets() above.
	 * Set up this way to amke it easier to add messages as needed.
	 *
	 * @since 4.11.0
	 *
	 * @return array<string,string> The localized messages.
	 */
	public function set_messages() {
		return [
			'api_error_title'        => _x( 'API Error', 'Error message title, will be followed by the error code.', 'event-tickets' ),
			'connection_error'       => __( 'Refresh this page or wait a few minutes before trying again. If this happens repeatedly, please contact the Site Admin.', 'event-tickets' ),
			'capacity_error'         => sprintf(
				/* Translators: %s - the singular, lowercase label for a ticket. */
				__( 'The %s for this event has sold out and has been removed from your cart.', 'event-tickets' ),
				tribe_get_ticket_label_singular_lowercase()
			),
			'validation_error_title' => __( 'Whoops!', 'event-tickets' ),
			/* Translators: %s - the HTML of the number of tickets with validation errors. */
			'validation_error'       => '<p>' . sprintf( _x( 'You have %s ticket(s) with a field that requires information.', 'The %s will change based on the error produced.', 'event-tickets' ), '<span class="tribe-tickets__notice--error__count">0</span>' ) . '</p>',
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.8.0
	 */
	public function get_registration_block_type() {
		return __DIR__ . '/block.json';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.8.0
	 *
	 * @param array<string,mixed> $args The default arguments the block would be registered with if this method is not
	 *                                  overridden.
	 *
	 * @return array<string,mixed> The arguments to use when registering the block.
	 */
	public function get_registration_args( array $args ): array {
		$args['title']       = _x( 'Tickets', 'Block title', 'event-tickets' );
		$args['description'] = _x( 'Sell tickets and register attendees.', 'Block description', 'event-tickets' );

		return $args;
	}

	/**
	 * Registers the editor scripts.
	 *
	 * @since 5.8.0
	 * @since 5.20.0 - Changed from WP methods to StellarWP/assets.
	 *
	 * @return void
	 */
	public function register_editor_scripts() {
		Asset::add(
			self::EDITOR_SCRIPT_SLUG,
			'Tickets/editor.js',
			Tickets_Main::VERSION
		)
			->add_to_group_path( 'et-tickets-blocks' )
			->set_dependencies(
				'tribe-tickets-gutenberg-vendor',
				'tribe-common-gutenberg-vendor'
			)
			->in_header()
			->register();

		Asset::add(
			self::EDITOR_STYLE_SLUG,
			'Tickets/editor.css',
			Tickets_Main::VERSION
		)
			->add_to_group_path( 'et-tickets-blocks' )
			->set_dependencies( 'tribe-tickets-gutenberg-main-styles' )
			->register();
	}
}
