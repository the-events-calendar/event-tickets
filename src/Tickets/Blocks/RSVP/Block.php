<?php
/**
 * Provides the information required to register the RSVP block server-side.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Blocks\RSVP;
 */

namespace TEC\Tickets\Blocks\RSVP;

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

		register_block_type( $block_root );

		$this->setup_assets();
	}

	/**
	 * Setup assets and localizes the data to the block editor script.
	 *
	 * @since TBD
	 */
	public function setup_assets() {
		$embed_url = $this->get_rsvp_url();

		wp_localize_script(
			'tec-tickets-rsvp-editor-script',
			'tec_tickets_commerce_rsvp_block_data',
			[
				'rsvp_url'   => $embed_url,
				'rsvp_nonce' => wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	/**
	 * Get the RSVP REST URL.
	 *
	 * @since TBD
	 *
	 * @return string The filtered embed URL.
	 */
	private function get_rsvp_url() {
		$default_embed_url = get_site_url() . '/wp-json/tec/v1/tickets/rsvp/';

		/**
		 * Filters the RSVP REST URL.
		 *
		 * @since TBD
		 *
		 * @param string $default_embed_url The RSVP REST URL.
		 */
		return apply_filters( 'tec_tickets_commerce_rsvp_block_query_url', $default_embed_url );
	}
}
