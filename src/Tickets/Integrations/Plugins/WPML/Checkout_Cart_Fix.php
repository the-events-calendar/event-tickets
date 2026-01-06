<?php
/**
 * Fix checkout cart loading when WPML is active.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Integrations\WPML
 */

namespace TEC\Tickets\Integrations\Plugins\WPML;

use TEC\Tickets\Commerce\Cart;

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

		// Debug logging (if enabled).
		add_action( 'tec_tickets_commerce_checkout_page_parse_request', [ $this, 'log_checkout_context' ], 999 );
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

		if ( ! $sitepress instanceof \SitePress ) {
			return;
		}

		// Check if we have the cart cookie parameter (indicates checkout redirect).
		$cookie_param = tribe_get_request_var( Cart::$cookie_query_arg, false );
		if ( ! $cookie_param ) {
			// Also check if we're on the checkout page.
			$checkout = tribe( \TEC\Tickets\Commerce\Checkout::class );
			if ( ! $checkout->is_current_page() ) {
				return;
			}
		}

		// Check if cart has items that need loading.
		$cart = tribe( Cart::class );
		$cart_hash = $cart->get_cart_hash();
		if ( empty( $cart_hash ) && empty( $cookie_param ) ) {
			return;
		}

		// Store original language.
		if ( null === self::$original_language ) {
			self::$original_language = apply_filters( 'wpml_current_language', null );
		}

		// Switch to 'all' language to bypass filtering.
		$sitepress->switch_lang( 'all', true );

		Checkout_Cart_Debug::log( 'language_switch', [
			'original_lang' => self::$original_language,
			'switched_to'   => 'all',
			'cart_hash'     => $cart_hash ?? 'none',
			'cookie_param'  => $cookie_param ? 'present' : 'none',
		] );
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

		if ( ! $sitepress instanceof \SitePress ) {
			return;
		}

		if ( null === self::$original_language ) {
			return;
		}

		// Restore original language.
		$sitepress->switch_lang( self::$original_language, true );

		Checkout_Cart_Debug::log( 'language_restore', [
			'restored_to' => self::$original_language,
		] );

		// Reset for next request.
		self::$original_language = null;
	}

	/**
	 * Load ticket object bypassing WPML language filtering.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id Ticket ID.
	 *
	 * @return \Tribe__Tickets__Ticket_Object|null
	 */
	private function load_ticket_without_language_filter( int $ticket_id ): ?\Tribe__Tickets__Ticket_Object {
		// Temporarily disable WPML language filtering for get_post().
		// This allows us to load tickets regardless of current language context.
		global $sitepress;

		$original_lang = null;
		$lang_switched = false;

		if ( $sitepress instanceof \SitePress ) {
			// Get current language.
			$original_lang = apply_filters( 'wpml_current_language', null );

			// Temporarily switch to 'all' language context to bypass filtering.
			// This is safe because we're only reading, not modifying.
			$sitepress->switch_lang( 'all', true );
			$lang_switched = true;
		}

		try {
			// Load ticket object - should now work regardless of stored language.
			$ticket_object = \Tribe__Tickets__Tickets::load_ticket_object( $ticket_id );
		} finally {
			// Restore original language context.
			if ( $lang_switched && $sitepress instanceof \SitePress && null !== $original_lang ) {
				$sitepress->switch_lang( $original_lang, true );
			}
		}

		return $ticket_object instanceof \Tribe__Tickets__Ticket_Object ? $ticket_object : null;
	}

	/**
	 * Log checkout context for debugging.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function log_checkout_context(): void {
		$cart_hash = tribe( Cart::class )->get_cart_hash();
		Checkout_Cart_Debug::log_cart_hash( $cart_hash ?? '' );

		$items = tribe( Cart::class )->get_items_in_cart( true );
		Checkout_Cart_Debug::log_cart_items( $items );
	}
}

