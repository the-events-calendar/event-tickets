<?php
/**
 * Shortcode [tec_tickets_checkout].
 *
 * @since   TBD
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce\Shortcodes;

use TEC\Tickets\Commerce\Checkout;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use Tribe\Shortcode\Shortcode_Abstract;
use TEC\Tickets\Commerce\Gateways\PayPal\Merchant;
use TEC\Tickets\Commerce\Gateways\PayPal\Settings;
use Tribe__Tickets__Editor__Template;

/**
 * Class for Shortcode Tribe_Tickets_Checkout.
 *
 * @since   TBD
 * @package Tribe\Tickets\Shortcodes
 */
class Checkout_Shortcode extends Shortcode_Abstract {

	/**
	 * {@inheritDoc}
	 */
	protected $slug = 'tec_tickets_checkout';

	/**
	 * Stores the instance of the template engine that we will use for rendering the page.
	 *
	 * @since TBD
	 *
	 * @var \Tribe__Template
	 */
	protected $template;

	/**
	 * Gets the template instance used to setup the rendering of the page.
	 *
	 * @since TBD
	 *
	 * @return \Tribe__Template
	 */
	public function get_template() {
		if ( empty( $this->template ) ) {
			$this->template = new \Tribe__Template();
			$this->template->set_template_origin( \Tribe__Tickets__Main::instance() );
			$this->template->set_template_folder( 'src/views/commerce' );
			$this->template->set_template_context_extract( true );
			$this->template->set_template_folder_lookup( true );
		}

		return $this->template;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_html() {
		$context = tribe_context();

		if ( is_admin() && ! $context->doing_ajax() ) {
			return '';
		}
		$merchant = tribe( Merchant::class );

//		$post     = get_post( $data['post_id'] );
//		$is_event = 'tribe_events' === $post->post_type;
//		$event    = null;
//		if ( $is_event && function_exists( 'tribe_get_event' ) ) {
//			$event = tribe_get_event( $post );
//		}

		$args = [
			'merchant'    => $merchant,
			'provider_id' => Module::class,
			'provider'    => tribe( Module::class ),
			'items'       => tribe( Cart::class )->get_tickets_in_cart(),
		];

		$args['paypal_attribution_id'] = \TEC\Tickets\Commerce\Gateways\PayPal\Gateway::ATTRIBUTION_ID;

		// Add the rendering attributes into global context.
		$this->get_template()->add_template_globals( $args );

		// Enqueue assets.
		tribe_asset_enqueue_group( 'tribe-tickets-commerce-checkout' );

		return $this->get_template()->template( 'checkout/page', $args, false );
	}

}
