<?php
/**
 * Editor class.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Admin;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Assets\Assets;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Asset_Build;
use Tribe__Main as Common;
use TEC\Tickets\Commerce\Order_Modifiers\API\Fees;
use WP_Post;
use TEC\Tickets\Commerce\Order_Modifiers\API\Base_API;

/**
 * Class Editor
 *
 * @since TBD
 */
class Editor extends Controller_Contract {

	use Asset_Build;

	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		/*
		 * This is a workaround to avoid the following error:
		 *
		 * ReferenceError: Can't find variable: tec
		 *
		 * This error is caused by webpack trying to map the tec.tickets.orderModifiers.rest object
		 * before is has been initialized in the browser. It's not yet known why this is happening.
		 *
		 * @todo: remove this workaround once the issue is fixed.
		 */
		add_action(
			'admin_enqueue_scripts',
			function () {
				?>
				<script type="text/javascript">
					(function () {
						window.tec = window.tec || {};
						window.tec.tickets = window.tec.tickets || {};
						window.tec.tickets.orderModifiers = window.tec.tickets.orderModifiers || {};
						window.tec.tickets.orderModifiers.rest = window.tec.tickets.orderModifiers.rest || {};
					})();
				</script>
				<?php
			}
		);
		$this->register_block_editor_assets();
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * Bound implementations should not be removed in this method!
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return array{
	 *     selectedFeesByPostId: array<int, string>,
	 * }
	 */
	public function get_store_data(): array {
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
	 * @since TBD
	 *
	 * @return void
	 */
	private function register_block_editor_assets() {
		// Register the main block editor script.
		$this->add_asset(
			'tec-tickets-order-modifiers-rest-localization',
			'rest.js',
		)
			->add_localize_script( 'tec.tickets.orderModifiers.rest', fn() => $this->get_rest_data() )
			->register();

		$this->add_asset(
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
			->add_localize_script( 'tec.tickets.orderModifiers.blockEditor', [ $this, 'get_store_data' ] )
			->enqueue_on( 'enqueue_block_editor_assets' )
			->set_condition( [ $this, 'should_enqueue_assets' ] )
			->register();

		// Register the block editor styles.
		$this->add_asset(
			'tec-tickets-order-modifiers-block-editor-css',
			'block-editor.css',
		)
			->enqueue_on( 'enqueue_block_editor_assets' )
			->register();
	}

	/**
	 * Checks if the current context is the Block Editor and the post type is ticket-enabled.
	 *
	 * @since TBD
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

		return is_admin() && in_array( $post->post_type, $ticketable_post_types, true );
	}

	/**
	 * Get the REST data for the Order Modifiers feature.
	 *
	 * @since TBD
	 *
	 * @return array The REST data for the Order Modifiers feature.
	 */
	protected function get_rest_data(): array {
		return [
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'baseUrl' => rest_url( Base_API::NAMESPACE ),
		];
	}
}
