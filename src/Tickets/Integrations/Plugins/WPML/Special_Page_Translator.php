<?php
/**
 * Translate Tickets Commerce special pages.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Integrations\WPML
 */

namespace TEC\Tickets\Integrations\Plugins\WPML;

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
	 * @param Wpml_Adapter $wpml WPML adapter instance.
	 */
	public function __construct( Wpml_Adapter $wpml ) {
		$this->wpml = $wpml;
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
		if ( ! $this->wpml->is_available() ) {
			return $url;
		}

		global $sitepress;

		if ( ! $sitepress instanceof \SitePress ) {
			return $url;
		}

		// Get the original checkout page ID (before translation filter).
		$original_page_id = (int) tribe_get_option( \TEC\Tickets\Commerce\Settings::$option_checkout_page, 0 );
		if ( empty( $original_page_id ) ) {
			return $url;
		}

		// Get the current language.
		$current_language = apply_filters( 'wpml_current_language', null );
		if ( empty( $current_language ) ) {
			return $url;
		}

		// Get the translated page ID for current language.
		$translated_page_id = $this->wpml->translate_post_id( $original_page_id, 'page', $current_language, true );
		if ( empty( $translated_page_id ) || $translated_page_id === $original_page_id ) {
			return $url;
		}

		// Store original language context.
		$original_language = apply_filters( 'wpml_current_language', null );

		// Switch to the target language to ensure get_permalink() returns the correct URL.
		$sitepress->switch_lang( $current_language, true );

		try {
			// Get the permalink for the translated page in the correct language context.
			$translated_url = get_permalink( $translated_page_id );
			if ( $translated_url && is_string( $translated_url ) && $translated_url !== $url ) {
				$url = $translated_url;
			}
		} finally {
			// Restore original language context.
			if ( $original_language ) {
				$sitepress->switch_lang( $original_language, true );
			}
		}

		return $url;
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
		if ( ! $this->wpml->is_available() ) {
			return $url;
		}

		global $sitepress;

		if ( ! $sitepress instanceof \SitePress ) {
			return $url;
		}

		// Get the original success page ID (before translation filter).
		$original_page_id = (int) tribe_get_option( \TEC\Tickets\Commerce\Settings::$option_success_page, 0 );
		if ( empty( $original_page_id ) ) {
			return $url;
		}

		// Get the current language.
		$current_language = apply_filters( 'wpml_current_language', null );
		if ( empty( $current_language ) ) {
			return $url;
		}

		// Get the translated page ID for current language.
		$translated_page_id = $this->wpml->translate_post_id( $original_page_id, 'page', $current_language, true );
		if ( empty( $translated_page_id ) || $translated_page_id === $original_page_id ) {
			return $url;
		}

		// Store original language context.
		$original_language = apply_filters( 'wpml_current_language', null );

		// Switch to the target language to ensure get_permalink() returns the correct URL.
		$sitepress->switch_lang( $current_language, true );

		try {
			// Get the permalink for the translated page in the correct language context.
			$translated_url = get_permalink( $translated_page_id );
			if ( $translated_url && is_string( $translated_url ) && $translated_url !== $url ) {
				$url = $translated_url;
			}
		} finally {
			// Restore original language context.
			if ( $original_language ) {
				$sitepress->switch_lang( $original_language, true );
			}
		}

		return $url;
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
		// If already true, no need to check further.
		if ( $is_current_page ) {
			return $is_current_page;
		}

		// Only proceed if WPML is available.
		if ( ! $this->wpml->is_available() ) {
			return $is_current_page;
		}

		$current_page = get_queried_object_id();
		if ( empty( $current_page ) ) {
			return $is_current_page;
		}

		// Get the original success page ID (before translation filter).
		$original_page_id = (int) tribe_get_option( \TEC\Tickets\Commerce\Settings::$option_success_page, 0 );
		if ( empty( $original_page_id ) ) {
			return $is_current_page;
		}

		// Check if current page is the original page.
		if ( $original_page_id === $current_page ) {
			return true;
		}

		// Check if current page is a translation of the original page.
		if ( class_exists( 'SitePress' ) ) {
			global $sitepress;
			if ( $sitepress instanceof \SitePress ) {
				$trid = $sitepress->get_element_trid( $original_page_id, 'post_page' );
				if ( $trid ) {
					$translations = $sitepress->get_element_translations( $trid, 'post_page', true );
					foreach ( $translations as $translation ) {
						if ( isset( $translation->element_id ) && (int) $translation->element_id === $current_page ) {
							return true;
						}
					}
				}
			}
		}

		return $is_current_page;
	}
}
