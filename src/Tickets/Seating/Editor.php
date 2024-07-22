<?php
/**
 * The main Editor controller, for both Classic and Blocks.
 *
 * @since   TBD
 *
 * @package TEC\Controller;
 */

namespace TEC\Tickets\Seating;

use TEC\Common\StellarWP\Assets\Asset;
use TEC\Common\StellarWP\Assets\Assets;
use TEC\Tickets\Seating\Service\Service;
use Tribe__Tickets__Main as Tickets;

/**
 * Class Editor.
 *
 * @since   TBD
 *
 * @package TEC\Controller;
 */
class Editor extends \TEC\Common\Contracts\Provider\Controller {
	use Built_Assets;

	/**
	 * Unregisters the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		$assets = Assets::instance();
		$assets->remove( 'tec-tickets-seating-block-editor' );
		remove_action( 'init', [ $this, 'register_meta' ], 1000 );
		remove_action( 'tribe_tickets_ticket_added', [ $this, 'save_ticket_seat_type' ] );
	}

	/**
	 * Returns the store data used to hydrate the store in Block Editor context.
	 *
	 * @since TBD
	 *
	 * @return array{
	 *     isUsingAssignedSeating: bool,
	 *     layouts: array<array{id: string, name: string, seats: int}>,
	 *     seatTypes: array<array{id: string, name: string, seats: int}>,
	 *     currentLayoutId: string,
	 *     seatTypesByPostId: array<string, string>,
	 *     isLayoutLocked: bool
	 * }
	 */
	public function get_store_data(): array {
		if ( tribe_context()->is_new_post() ) {
			// New posts will always use assigned seating.
			$is_using_assigned_seating = true;
			$layout_id                 = null;
			$seat_types_by_post_id     = [];
			$is_layout_locked          = false;
		} else {
			$post_id                   = get_the_ID();
			$is_using_assigned_seating = tribe_is_truthy( get_post_meta( $post_id, Meta::META_KEY_ENABLED, true ) );
			$layout_id                 = get_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, true );
			$is_layout_locked          = ! empty( $layout_id );
			$seat_types_by_post_id     = [];
			foreach ( tribe_tickets()->where( 'event', $post_id )->get_ids( true ) as $ticket_id ) {
				$seat_types_by_post_id[ $ticket_id ] = get_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, true );
			}
		}

		$service = $this->container->get( Service::class );

		return [
			'isUsingAssignedSeating' => $is_using_assigned_seating,
			'layouts'                => $service->get_layouts_in_option_format(),
			'seatTypes'              => $layout_id ? $service->get_seat_types_by_layout( $layout_id ) : [],
			'currentLayoutId'        => $layout_id,
			'seatTypesByPostId'      => $seat_types_by_post_id,
			'isLayoutLocked'         => $is_layout_locked,
		];
	}

	/**
	 * Registers the meta for the Tickets and the ticketable post types.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->register_block_editor_assets();
		add_action( 'init', [ $this, 'register_meta' ], 1000 );
		add_action( 'tribe_tickets_ticket_added', [ $this, 'save_ticket_seat_type' ], 10, 3 );
	}

	/**
	 * Registers the Block Editor JavaScript and CSS assets.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function register_block_editor_assets(): void {
		Asset::add(
			'tec-tickets-seating-block-editor',
			$this->built_asset_url( 'block-editor.js' ),
			Tickets::VERSION
		)
			->set_dependencies(
				'wp-hooks',
				'react',
				'react-dom',
				'tec-tickets-seating-utils',
				'tec-tickets-seating-ajax',
				'tribe-common-gutenberg-vendor'
			)
			->enqueue_on( 'enqueue_block_editor_assets' )
			->add_localize_script( 'tec.tickets.seating.blockEditor', [ $this, 'get_store_data' ] )
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
			$this->built_asset_url( 'block-editor.css' ),
			Tickets::VERSION
		)
			->enqueue_on( 'enqueue_block_editor_assets' )
			->add_to_group( 'tec-tickets-seating-editor' )
			->add_to_group( 'tec-tickets-seating' )
			->register();
	}

	/**
	 * Saves the seating details of a ticket from the POST or PUT request data sent to the REST API.
	 *
	 * @since TBD
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
			$body['tribe-ticket']['seating']['seatType'] 
		)
		) {
			return;
		}

		$enabled   = (bool) $body['tribe-ticket']['seating']['enabled'];
		$seat_type = (string) $body['tribe-ticket']['seating']['seatType'];

		update_post_meta( $ticket_id, Meta::META_KEY_ENABLED, $enabled ? '1' : '0' );

		if ( $seat_type ) {
			update_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, $seat_type );
		} else {
			delete_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE );
		}
	}
}
