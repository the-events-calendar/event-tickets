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
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Created;
use Tribe\Shortcode\Shortcode_Abstract;
use TEC\Tickets\Commerce\Gateways\PayPal\Merchant;
use TEC\Tickets\Commerce\Gateways\PayPal\Settings;
use Tribe__Tickets__Editor__Template;
use TEC\Tickets\Commerce\Utils\Price;

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
			$this->template->set_template_folder( 'src/views/v2/commerce' );
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

		$merchant   = tribe( Merchant::class );
		$items      = tribe( Cart::class )->get_items_in_cart( true );
		$sections   = array_unique( array_filter( wp_list_pluck( $items, 'event_id' ) ) );
		$sub_totals = array_filter( wp_list_pluck( $items, 'sub_total' ) );

		$args = [
			'merchant'         => $merchant,
			'provider_id'      => Module::class,
			'provider'         => tribe( Module::class ),
			'items'            => $items,
			'sections'         => $sections,
			'total_value'      => tribe_format_currency( Price::total( $sub_totals ) ),
			'must_login'       => ! is_user_logged_in() && tribe( Module::class )->login_required(),
			'login_url'        => tribe( Checkout::class )->get_login_url(),
			'registration_url' => tribe( Checkout::class )->get_registration_url(),
		];

		// Add the rendering attributes into global context.
		$this->get_template()->add_template_globals( $args );

		$html = $this->get_template()->template( 'checkout', $args, false );

		// Enqueue assets.
		tribe_asset_enqueue_group( 'tribe-tickets-commerce-checkout' );

		return $html;
	}

}
