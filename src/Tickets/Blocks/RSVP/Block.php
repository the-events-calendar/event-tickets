<?php
/**
 * Provides the information required to register the RSVP block server-side.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Blocks\RSVP;
 */

namespace TEC\Tickets\Blocks\RSVP;

use TEC\Tickets\Commerce\RSVP\REST\Order_Endpoint;
use Tribe__Tickets__Main;

/**
 * Class Block
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Blocks\RSVP
 */
class Block {

	/**
	 * Registers the RSVP block.
	 *
	 * @since TBD
	 */
	public function register_block() {
		$build_path = Tribe__Tickets__Main::instance()->plugin_path . 'build';
		$block_root = "{$build_path}/resources/js/commerce/rsvp-block";
		if ( ! file_exists( "{$block_root}/index.js" ) ) {
			return;
		}

		// Register block with render callback to ensure render.php is used.
		register_block_type(
			$block_root,
			[
				'render_callback' => [ $this, 'render_block' ],
			]
		);

		$this->setup_assets();
	}

	/**
	 * Render the RSVP block on the frontend.
	 *
	 * @since TBD
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 *
	 * @return string Rendered block output.
	 */
	public function render_block( $attributes, $content, $block ) {
		// Use the render.php file to render the block.
		$build_path = Tribe__Tickets__Main::instance()->plugin_path . 'build';
		$render_file = "{$build_path}/resources/js/commerce/rsvp-block/render.php";

		// If render.php exists, include it.
		if ( file_exists( $render_file ) ) {
			ob_start();
			include $render_file;
			return ob_get_clean();
		}

		// Fallback to empty string if render.php doesn't exist.
		return '';
	}

	/**
	 * Setup assets and localizes the data to the block editor script.
	 *
	 * @since TBD
	 */
	public function setup_assets() {
		$rest_url = $this->get_rsvp_url();

		wp_localize_script(
			'tec-tickets-rsvp-editor-script',
			'tec_tickets_commerce_rsvp_block_data',
			[
				'rsvp_rest_url' => $rest_url,
				'rsvp_nonce'    => wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	/**
	 * Get the RSVP REST URL.
	 *
	 * @since TBD
	 *
	 * @return string The filtered REST URL.
	 */
	private function get_rsvp_url() {
		$default_rest_url = tribe_callback( Order_Endpoint::class, 'get_route_url' )();

		/**
		 * Filters the RSVP REST URL.
		 *
		 * @since TBD
		 *
		 * @param string $default_rest_url The RSVP REST URL.
		 */
		return apply_filters( 'tec_tickets_commerce_rsvp_block_query_url', $default_rest_url );
	}
}
