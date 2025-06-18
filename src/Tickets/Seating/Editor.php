<?php
/**
 * The main Editor controller, for both Classic and Blocks.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller;
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Asset;
use TEC\Common\StellarWP\Assets\Assets;
use TEC\Tickets\Seating\Service\Service;
use Tribe__Tickets__Main as Tickets;
use TEC\Tickets\Commerce\Ticket;
use Tribe__Tickets__Tickets as Tribe_Tickets;
use WP_Post;
use WP_REST_Request;

/**
 * Class Editor.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller;
 */
class Editor extends \TEC\Common\Contracts\Provider\Controller {
	/**
	 * Unregisters the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$assets = Assets::instance();
		$assets->remove( 'tec-tickets-seating-block-editor' );
		remove_action( 'init', [ $this, 'register_meta' ], 1000 );
		remove_action( 'tribe_tickets_ticket_added', [ $this, 'save_ticket_seat_type' ] );
		remove_filter( 'tribe_rest_single_ticket_data', [ $this, 'filter_seating_totals' ], 20 );
	}

	/**
	 * Returns the store data used to hydrate the store in Block Editor context.
	 *
	 * @since 5.16.0
	 *
	 * @return array{
	 *     isUsingAssignedSeating: bool,
	 *     layouts: array<array{id: string, name: string, seats: int}>,
	 *     seatTypes: array<array{id: string, name: string, seats: int}>,
	 *     currentLayoutId: string,
	 *     seatTypesByPostId: array<string, string>,
	 *     isLayoutLocked: bool,
	 *     eventCapacity: number,
	 * }
	 */
	public function get_store_data(): array {
		if ( tribe_context()->is_new_post() ) {
			// New posts will always use assigned seating as long as license exists.
			$is_using_assigned_seating = true;
			$layout_id                 = null;
			$seat_types_by_post_id     = [];
			$is_layout_locked          = false;
			$event_capacity            = 0;
		} else {
			$post_id                   = get_the_ID();
			$is_using_assigned_seating = tribe_is_truthy( get_post_meta( $post_id, Meta::META_KEY_ENABLED, true ) );
			$layout_id                 = get_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, true );
			$seat_types_by_post_id     = [];
			$is_layout_locked          = ! empty( $layout_id );
			foreach ( tribe_tickets()->where( 'event', $post_id )->get_ids( true ) as $ticket_id ) {
				if ( ! $ticket_id ) {
					continue;
				}

				$is_layout_locked                    = true; // If there are tickets already, layout is definitely locked!
				$seat_types_by_post_id[ $ticket_id ] = get_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, true );
			}

			$event_capacity = tribe_get_event_capacity( $post_id );
		}

		$service        = tribe( Service::class );
		$service_status = $service->get_status();

		return [
			// isUsingAssignedSeating should never be true when there is no license. The ASC controls are hidden when no license is there.
			'isUsingAssignedSeating' => ! $service_status->has_no_license() && $is_using_assigned_seating,
			'layouts'                => $service->get_layouts_in_option_format(),
			'seatTypes'              => $layout_id ? $service->get_seat_types_by_layout( $layout_id ) : [],
			'currentLayoutId'        => $layout_id,
			'seatTypesByPostId'      => $seat_types_by_post_id,
			'isLayoutLocked'         => $is_layout_locked,
			'eventCapacity'          => $event_capacity,
			'serviceStatus'          => [
				'ok'         => $service_status->is_ok(),
				'status'     => $service_status->get_status_string(),
				'connectUrl' => $service_status->get_connect_url(),
			],
		];
	}

	/**
	 * Registers the meta for the Tickets and the ticketable post types.
	 *
	 * @since 5.16.0
	 */
	public function register_meta(): void {
		foreach ( tribe_tickets()->ticket_types() as $ticket_type ) {
			foreach (
				[
					Meta::META_KEY_ENABLED,
					Meta::META_KEY_LAYOUT_ID,
				] as $meta_key
			) {
				register_post_meta(
					$ticket_type,
					$meta_key,
					[
						'show_in_rest'  => true,
						'single'        => true,
						'type'          => 'string',
						'auth_callback' => function () {
							return current_user_can( 'edit_posts' );
						},
					]
				);
			}
		}

		foreach ( (array) tribe_get_option( 'ticket-enabled-post-types', [] ) as $ticketable_type ) {
			foreach ( [ Meta::META_KEY_ENABLED, Meta::META_KEY_LAYOUT_ID ] as $meta_key ) {
				register_post_meta(
					$ticketable_type,
					$meta_key,
					[
						'show_in_rest'  => true,
						'single'        => true,
						'type'          => 'string',
						'auth_callback' => function () {
							return current_user_can( 'edit_posts' );
						},
					]
				);
			}
		}
	}

	/**
	 * Registers the controller by subscribing to WordPress hooks and binding implementations.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->register_block_editor_assets();
		add_action( 'init', [ $this, 'register_meta' ], 1000 );
		add_action( 'tribe_tickets_ticket_added', [ $this, 'save_ticket_seat_type' ], 10, 3 );
		// Priority is important to be above 10!
		add_filter( 'tribe_rest_single_ticket_data', [ $this, 'filter_seating_totals' ], 20, 2 );
	}

	/**
	 * Filters the seating totals during a rest request.
	 *
	 * @since 5.16.0
	 *
	 * @param array           $data    The data to be shared with the block editor.
	 * @param WP_REST_Request $request The block editor's request.
	 *
	 * @return array The filtered totals.
	 */
	public function filter_seating_totals( array $data, WP_REST_Request $request ): array {
		$ticket_id = $request['id'] ?? false;

		if ( ! $ticket_id ) {
			return $data;
		}

		$ticket_object = Tribe_Tickets::load_ticket_object( $ticket_id );

		$event_id = get_post_meta( $ticket_id, Ticket::$event_relation_meta_key, true );

		if ( ! ( $ticket_object && $event_id && tec_tickets_seating_enabled( $event_id ) ) ) {
			return $data;
		}

		$data['totals'] = array_merge(
			$data['totals'],
			[
				'stock' => $ticket_object->stock(),
			]
		);

		return $data;
	}

	/**
	 * Registers the Block Editor JavaScript and CSS assets.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	private function register_block_editor_assets(): void {
		Asset::add(
			'tec-tickets-seating-block-editor',
			'blockEditor.js',
			Tickets::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->set_condition( [ $this, 'should_enqueue_assets' ] )
			->set_dependencies(
				'wp-hooks',
				'react',
				'react-dom',
				'tec-tickets-seating-utils',
				'tec-tickets-seating-ajax',
				'tribe-common-gutenberg-vendor'
			)
			->enqueue_on( 'enqueue_block_editor_assets' )
			->add_localize_script( 'tec.tickets.seating.blockEditorData', [ $this, 'get_store_data' ] )
			->add_localize_script(
				'tec.tickets.seating.meta',
				fn() => [
					'META_KEY_ENABLED'   => Meta::META_KEY_ENABLED,
					'META_KEY_LAYOUT_ID' => Meta::META_KEY_LAYOUT_ID,
					'META_KEY_SEAT_TYPE' => Meta::META_KEY_SEAT_TYPE,
				]
			)
			->add_to_group( 'tec-tickets-seating-editor' )
			->add_to_group( 'tec-tickets-seating' )
			->register();

		Asset::add(
			'tec-tickets-seating-block-editor-style',
			'style-blockEditor.css',
			Tickets::VERSION
		)
			->add_to_group_path( 'tec-seating' )
			->set_condition( [ $this, 'should_enqueue_assets' ] )
			->enqueue_on( 'enqueue_block_editor_assets' )
			->add_to_group( 'tec-tickets-seating-editor' )
			->add_to_group( 'tec-tickets-seating' )
			->register();
	}

	/**
	 * Checks if the current context is the Block Editor and the post type is ticket-enabled.
	 *
	 * @since 5.17.0
	 *
	 * @return bool Whether the assets should be enqueued or not.
	 */
	public function should_enqueue_assets() {
		$ticketable_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		if ( empty( $ticketable_post_types ) ) {
			return false;
		}

		$post = get_post();

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		return is_admin()
				&& in_array( $post->post_type, $ticketable_post_types, true );
	}

	/**
	 * Saves the seating details of a ticket from the POST or PUT request data sent to the REST API.
	 *
	 * @since 5.16.0
	 *
	 * @param int                 $post_id   The ID of the post the ticket is attached to.
	 * @param int                 $ticket_id The ID of the ticket.
	 * @param array<string,mixed> $body      The body of the request.
	 *
	 * @return void The seating details are saved.
	 */
	public function save_ticket_seat_type( $post_id, $ticket_id, $body ) {
		if ( ! isset(
			$body['tribe-ticket'],
			$body['tribe-ticket']['seating'],
			$body['tribe-ticket']['seating']['enabled'],
			$body['tribe-ticket']['seating']['seatType'],
			$body['tribe-ticket']['seating']['layoutId']
		)
		) {
			return;
		}

		$enabled   = (bool) $body['tribe-ticket']['seating']['enabled'];
		$seat_type = (string) $body['tribe-ticket']['seating']['seatType'];
		$layout_id = (string) $body['tribe-ticket']['seating']['layoutId'];

		update_post_meta( $ticket_id, Meta::META_KEY_ENABLED, $enabled );
		update_post_meta( $post_id, Meta::META_KEY_ENABLED, $enabled );

		if ( $layout_id ) {
			update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, $layout_id );
		} else {
			delete_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID );
		}

		if ( $seat_type ) {
			update_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, $seat_type );
		} else {
			delete_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE );
		}
	}
}
