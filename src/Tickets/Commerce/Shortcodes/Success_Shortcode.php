<?php
/**
 * Shortcode [tec_tickets_success].
 *
 * @since   TBD
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce\Shortcodes;

use TEC\Tickets\Commerce\Module;

/**
 * Class for Shortcode Tribe_Tickets_Checkout.
 *
 * @since   TBD
 * @package Tribe\Tickets\Shortcodes
 */
class Success_Shortcode extends Shortcode_Abstract {

	/**
	 * Id of the current shortcode for filtering purposes.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $shortcode_id = 'success';

	/**
	 * {@inheritDoc}
	 */
	public function setup_template_vars() {

		$args = [
			'provider_id' => Module::class,
			'provider'    => tribe( Module::class ),
		];

		$this->template_vars = $args;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_html() {
		$context = tribe_context();

		if ( is_admin() && ! $context->doing_ajax() ) {
			return '';
		}

		$args = $this->get_template_vars();

		// Add the rendering attributes into global context.
		$this->get_template()->add_template_globals( $args );

		$html = $this->get_template()->template( 'success', $args, false );

		// Enqueue assets.
		tribe_asset_enqueue_group( 'tec-tickets-commerce-success' );

		return $html;
	}

}
