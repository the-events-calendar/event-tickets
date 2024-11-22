<?php
/**
 * Editor class.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Admin;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;
use TEC\Common\StellarWP\Assets\Assets;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Asset_Build;
use Tribe__Tickets__Main as Tickets;
use Tribe__Main as Common;
use TEC\Tickets\Commerce\Order_Modifiers\API\Fees;

/**
 * Class Editor
 *
 * @since TBD
 */
class Editor extends Controller_Contract {

	use Asset_Build;

	/**
	 * ServiceProvider constructor.
	 *
	 * @param Container $container The DI container.
	 */
	public function __construct( Container $container ) {
		parent::__construct( $container );
		$this->plugin = Tickets::instance();
	}

	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return void Filters and actions hooks added by the controller are be removed.
	 */
	public function unregister(): void {
		Assets::instance()->remove( 'tec-tickets-order-modifiers-block-editor' );
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
			->register();
	}
}
