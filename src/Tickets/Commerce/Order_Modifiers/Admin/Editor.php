<?php
/**
 * Editor class.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Admin;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Assets\Assets;
use TEC\Tickets\Commerce\Order_Modifiers\API\Base_API;
use TEC\Tickets\Commerce\Order_Modifiers\API\Fees;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Asset_Build;
use Tribe__Main as Common;
use WP_Post;

/**
 * Class Editor
 *
 * @since 5.18.0
 */
final class Editor extends Controller_Contract {

	use Asset_Build;

	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->register_block_editor_assets();
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * Bound implementations should not be removed in this method!
	 *
	 * @since 5.18.0
	 *
	 * @return void Filters and actions hooks added by the controller are be removed.
	 */
	public function unregister(): void {
		Assets::init()->remove( 'tec-tickets-order-modifiers-rest-localization' );
		Assets::init()->remove( 'tec-tickets-order-modifiers-block-editor' );
	}

	/**
	 * Returns the store data used to hydrate the store in Block Editor context.
	 *
	 * @since 5.18.0
	 *
	 * @return array {
	 *     selectedFeesByPostId: array<int, string>,
	 * }
	 */
	private function get_store_data(): array {
		$post_id = Common::post_id_helper();
		if ( ! $post_id ) {
			return [];
		}

		return [
			'selectedFeesByPostId' => tribe( Fees::class )->get_selected_fees_for_post_by_ticket( $post_id ),
		];
	}

	/**
	 * Registers the block editor assets.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	private function register_block_editor_assets(): void {
		// Register the REST script.
		$this
			->add_asset(
				'tec-tickets-order-modifiers-rest-localization',
				'rest.js',
			)
			->add_localize_script( 'tec.tickets.orderModifiers.rest', fn() => $this->get_rest_data() )
			->set_condition( fn() => $this->should_enqueue_assets() )
			->register();

		// Register the main block editor script.
		$this
			->add_asset(
				'tec-tickets-order-modifiers-block-editor',
				'block-editor.js',
			)
			->set_dependencies(
				'wp-hooks',
				'wp-data',
				'react',
				'react-dom',
				'tec-tickets-order-modifiers-rest-localization',
			)
			->add_localize_script(
				'tec.tickets.orderModifiers.blockEditor',
				fn() => $this->get_store_data()
			)
			->enqueue_on( 'enqueue_block_editor_assets' )
			->set_condition( fn() => $this->should_enqueue_assets() )
			->register();

		// Register the block editor styles.
		$this
			->add_asset(
				'tec-tickets-order-modifiers-block-editor-css',
				'block-editor.css',
			)
			->enqueue_on( 'enqueue_block_editor_assets' )
			->set_condition( fn() => $this->should_enqueue_assets() )
			->register();
	}

	/**
	 * Checks if the current context is the Block Editor and the post type is ticket-enabled.
	 *
	 * @since 5.18.0
	 *
	 * @return bool Whether the assets should be enqueued or not.
	 */
	private function should_enqueue_assets(): bool {
		// We shouldn't enqueue on the frontend.
		if ( ! is_admin() ) {
			return false;
		}

		$ticketable_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
		if ( empty( $ticketable_post_types ) ) {
			return false;
		}

		$post = get_post();
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		return in_array( $post->post_type, $ticketable_post_types, true );
	}

	/**
	 * Get the REST data for the Order Modifiers feature.
	 *
	 * @since 5.18.0
	 *
	 * @return array The REST data for the Order Modifiers feature.
	 */
	private function get_rest_data(): array {
		return [
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'baseUrl' => rest_url( Base_API::NAMESPACE ),
		];
	}
}
