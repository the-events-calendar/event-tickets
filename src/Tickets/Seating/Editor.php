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
	 * }
	 */
	public function get_store_data(): array {
		if ( tribe_context()->is_new_post() ) {
			// New posts will always use assigned seating.
			$is_using_assigned_seating = true;
			$layout_id                 = null;
			$seat_types_by_post_id     = [];
		} else {
			// If not defined, assume it's using assigned seating.
			$post_id                   = get_the_ID();
			$is_using_assigned_seating = ! metadata_exists( 'post', $post_id, Meta::META_KEY_ENABLED )
			                             || tribe_is_truthy(
				                             get_post_meta( get_the_ID(), Meta::META_KEY_ENABLED, true )
			                             );
			$layout_id                 = get_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, true );

			$seat_types_by_post_id = [];
			foreach ( tribe_tickets()->where( 'event', $post_id )->get_ids( true ) as $ticket_id ) {
				$seat_types_by_post_id[ $ticket_id ] = get_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, true );
			}
		}

		$service = $this->container->get( Service::class );

		return [
			'isUsingAssignedSeating' => $is_using_assigned_seating,
			'layouts'                => $service->get_layouts_in_option_format(),
			'seatTypes'              => [],
			'currentLayoutId'        => $layout_id,
			'seatTypesByPostId'      => $seat_types_by_post_id
		];
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
			->add_localize_script( 'tec.seating.blockEditor', [ $this, 'get_store_data' ] )
			->add_localize_script( 'tec.seating.meta', fn() => [
				'META_KEY_ENABLED'   => Meta::META_KEY_ENABLED,
				'META_KEY_LAYOUT_ID' => Meta::META_KEY_LAYOUT_ID,
				'META_KEY_SEAT_TYPE' => Meta::META_KEY_SEAT_TYPE,
			] )
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
					Meta::META_KEY_SEAT_TYPE,
				] as $meta_key
			) {
				register_post_meta( $ticket_type, $meta_key, [
					'show_in_rest' => false,
					'single'       => true,
					'type'         => 'string',
				] );
			}
		}

		foreach ( (array) tribe_get_option( 'ticket-enabled-post-types', [] ) as $ticketable_type ) {
			foreach (
				[
					Meta::META_KEY_ENABLED,
					Meta::META_KEY_LAYOUT_ID
				] as $meta_key
			) {
				register_post_meta( $ticket_type, $meta_key, [
					'show_in_rest' => false,
					'single'       => true,
					'type'         => 'string',
				] );
			}
		}
	}
}