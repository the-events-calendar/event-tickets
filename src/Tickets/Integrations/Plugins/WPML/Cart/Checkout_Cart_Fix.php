<?php
/**
 * Fix checkout cart loading when WPML is active.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Integrations\Plugins\WPML\Cart
 */

namespace TEC\Tickets\Integrations\Plugins\WPML\Cart;

use TEC\Tickets\Integrations\Plugins\WPML\Core\Wpml_Adapter;
use TEC\Tickets\Integrations\Plugins\WPML\Core\Language_Switcher;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Checkout;
use SitePress;

/**
 * Class Checkout_Cart_Fix.
 *
 * Ensures cart items can be loaded regardless of current WPML language context.
 * The issue: When cart stores original (EN) ticket IDs but checkout page loads
 * in translated (ES) language, `get_post()` may fail due to WPML language filtering,
 * causing cart items to be filtered out and checkout to render empty.
 *
 * @since TBD
 */
class Checkout_Cart_Fix {

	/**
	 * @since TBD
	 *
	 * @var Wpml_Adapter
	 */
	private Wpml_Adapter $wpml;

	/**
	 * @since TBD
	 *
	 * @param Wpml_Adapter $wpml WPML adapter instance.
	 */
	public function __construct( Wpml_Adapter $wpml ) {
		$this->wpml = $wpml;
	}

	/**
	 * Original language before switching.
	 *
	 * @since TBD
	 *
	 * @var string|null
	 */
	private static ?string $original_language = null;

	/**
	 * Register hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->wpml->is_available() ) {
			return;
		}

		// Switch language context early on checkout page.
		add_action( 'template_redirect', [ $this, 'setup_language_context_for_cart' ], 1 );

		// Restore language context after page renders.
		add_action( 'wp_footer', [ $this, 'restore_language_context' ], 999 );

		// Also restore on shutdown as backup.
		add_action( 'shutdown', [ $this, 'restore_language_context' ], 1 );
	}

	/**
	 * Setup language context for cart loading.
	 *
	 * Temporarily switches WPML to 'all' language context so get_post()
	 * can find tickets regardless of current language.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function setup_language_context_for_cart(): void {
		global $sitepress;

		if ( ! $sitepress instanceof SitePress ) {
			return;
		}

		// Check if we have the cart cookie parameter (indicates checkout redirect).
		$cookie_param = tribe_get_request_var( Cart::$cookie_query_arg, false );
		if ( ! $cookie_param ) {
			// Also check if we're on the checkout page.
			$checkout = tribe( Checkout::class );
			if ( ! $checkout->is_current_page() ) {
				return;
			}
		}

		// Check if cart has items that need loading.
		$cart      = tribe( Cart::class );
		$cart_hash = $cart->get_cart_hash();
		if ( empty( $cart_hash ) && empty( $cookie_param ) ) {
			return;
		}

		// Store original language.
		if ( null === self::$original_language ) {
			self::$original_language = Language_Switcher::get_current_language();
		}

		// Switch to 'all' language to bypass filtering.
		$sitepress->switch_lang( 'all', true );
	}

	/**
	 * Restore original language context.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function restore_language_context(): void {
		global $sitepress;

		if ( ! $sitepress instanceof SitePress ) {
			return;
		}

		if ( null === self::$original_language ) {
			return;
		}

		// Restore original language.
		$sitepress->switch_lang( self::$original_language, true );

		// Reset for next request.
		self::$original_language = null;
	}
}
