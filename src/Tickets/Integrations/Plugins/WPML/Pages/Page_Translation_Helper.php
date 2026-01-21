<?php
/**
 * Helper for page translation operations.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Integrations\Plugins\WPML\Pages
 */

namespace TEC\Tickets\Integrations\Plugins\WPML\Pages;

use TEC\Tickets\Integrations\Plugins\WPML\Core\Wpml_Adapter;
use TEC\Tickets\Integrations\Plugins\WPML\Core\Language_Switcher;
use SitePress;

/**
 * Class Page_Translation_Helper
 *
 * Provides common page translation operations.
 *
 * @since TBD
 */
class Page_Translation_Helper {

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
	 * Translate a page URL to the current language.
	 *
	 * @since TBD
	 *
	 * @param string $url Original URL.
	 * @param string $option_key Option key for the page ID setting.
	 * @param string $element_type WPML element type (default: 'post_page').
	 *
	 * @return string Translated URL, or original if translation not available.
	 */
	public function translate_page_url( string $url, string $option_key, string $element_type = 'post_page' ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! $this->wpml->is_available() ) {
			return $url;
		}

		$original_page_id = (int) tribe_get_option( $option_key, 0 );
		if ( $original_page_id <= 0 ) {
			return $url;
		}

		$current_language = Language_Switcher::get_current_language();
		if ( null === $current_language ) {
			return $url;
		}

		$translated_page_id = $this->wpml->translate_post_id( $original_page_id, 'page', $current_language, true );
		if ( $translated_page_id <= 0 || $translated_page_id === $original_page_id ) {
			return $url;
		}

		// Switch language context to get correct permalink.
		return Language_Switcher::with_language(
			$current_language,
			function () use ( $translated_page_id, $url ) {
				$translated_url = get_permalink( $translated_page_id );
				if ( $translated_url && is_string( $translated_url ) && $translated_url !== $url ) {
					return $translated_url;
				}

				return $url;
			}
		);
	}

	/**
	 * Check if current page is a translation of a given page ID.
	 *
	 * @since TBD
	 *
	 * @param bool   $is_current_page Current page check result (before WPML check).
	 * @param int    $original_page_id Original page ID to check against.
	 * @param string $element_type WPML element type (default: 'post_page').
	 *
	 * @return bool
	 */
	public function is_translated_page( bool $is_current_page, int $original_page_id, string $element_type = 'post_page' ): bool {
		if ( $is_current_page || ! $this->wpml->is_available() ) {
			return $is_current_page;
		}

		$current_page = get_queried_object_id();
		if ( $current_page <= 0 || $original_page_id <= 0 ) {
			return $is_current_page;
		}

		// Check if current page is the original page.
		if ( $original_page_id === $current_page ) {
			return true;
		}

		// Check if current page is a translation of the original page.
		if ( ! class_exists( 'SitePress' ) ) {
			return $is_current_page;
		}

		global $sitepress;
		if ( ! $sitepress instanceof SitePress ) {
			return $is_current_page;
		}

		$trid = $sitepress->get_element_trid( $original_page_id, $element_type );
		if ( ! $trid ) {
			return $is_current_page;
		}

		$translations = $sitepress->get_element_translations( $trid, $element_type, true );
		foreach ( $translations as $translation ) {
			if ( isset( $translation->element_id ) && (int) $translation->element_id === $current_page ) {
				return true;
			}
		}

		return $is_current_page;
	}
}
