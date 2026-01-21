<?php
/**
 * Translate Tickets Commerce special pages.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Integrations\Plugins\WPML\Pages
 */

namespace TEC\Tickets\Integrations\Plugins\WPML\Pages;

use TEC\Tickets\Integrations\Plugins\WPML\Core\Wpml_Adapter;
use TEC\Tickets\Commerce\Settings;

/**
 * Class Special_Page_Translator
 *
 * Handles translation of special Tickets Commerce pages (checkout, success).
 *
 * @since TBD
 */
class Special_Page_Translator {

	/**
	 * @since TBD
	 *
	 * @var Wpml_Adapter
	 */
	private Wpml_Adapter $wpml;

	/**
	 * @since TBD
	 *
	 * @var Page_Translation_Helper
	 */
	private Page_Translation_Helper $page_helper;

	/**
	 * @since TBD
	 *
	 * @param Wpml_Adapter $wpml WPML adapter instance.
	 */
	public function __construct( Wpml_Adapter $wpml ) {
		$this->wpml        = $wpml;
		$this->page_helper = new Page_Translation_Helper( $wpml );
	}

	/**
	 * Register hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'tec_tickets_commerce_checkout_page_id', [ $this, 'translate_page_id' ] );
		add_filter( 'tec_tickets_commerce_success_page_id', [ $this, 'translate_page_id' ] );
		add_filter( 'tec_tickets_commerce_checkout_url', [ $this, 'translate_checkout_url' ], 10, 1 );
		add_filter( 'tec_tickets_commerce_cart_to_checkout_redirect_url_base', [ $this, 'translate_checkout_url' ], 10, 1 );
		add_filter( 'tec_tickets_commerce_success_url', [ $this, 'translate_success_url' ], 10, 1 );
		add_filter( 'tec_tickets_commerce_success_is_current_page', [ $this, 'check_success_page_translations' ], 10, 1 );
	}

	/**
	 * Translate a page ID.
	 *
	 * @since TBD
	 *
	 * @param int $page_id Page ID.
	 *
	 * @return int
	 */
	public function translate_page_id( $page_id ): int {
		$page_id = is_numeric( $page_id ) ? (int) $page_id : 0;

		return $this->wpml->translate_page_id( $page_id );
	}

	/**
	 * Translate checkout URL to use the current language's checkout page.
	 *
	 * When WPML is active, ensures the checkout URL points to the translated
	 * checkout page for the current language context.
	 *
	 * @since TBD
	 *
	 * @param string $url The checkout URL (may be from default language).
	 *
	 * @return string The translated checkout URL for current language.
	 */
	public function translate_checkout_url( $url ): string {
		return $this->page_helper->translate_page_url(
			$url,
			Settings::$option_checkout_page
		);
	}

	/**
	 * Translate success URL to use the current language's success page.
	 *
	 * When WPML is active, ensures the success URL points to the translated
	 * success page for the current language context.
	 *
	 * @since TBD
	 *
	 * @param string $url The success URL (may be from default language).
	 *
	 * @return string The translated success URL for current language.
	 */
	public function translate_success_url( $url ): string {
		return $this->page_helper->translate_page_url(
			$url,
			Settings::$option_success_page
		);
	}

	/**
	 * Check if current page is a translation of the success page.
	 *
	 * When WPML is active, the success page ID may be translated, but the current page
	 * might be the original or a different translation. This method checks all translations
	 * to determine if we're on the success page.
	 *
	 * @since TBD
	 *
	 * @param bool $is_current_page Whether the current page is the success page (before WPML check).
	 *
	 * @return bool
	 */
	public function check_success_page_translations( $is_current_page ): bool {
		$original_page_id = (int) tribe_get_option( Settings::$option_success_page, 0 );

		return $this->page_helper->is_translated_page( $is_current_page, $original_page_id );
	}
}


