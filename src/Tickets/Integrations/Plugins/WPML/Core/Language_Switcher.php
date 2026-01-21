<?php
/**
 * Helper for WPML language switching operations.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Integrations\Plugins\WPML\Core
 */

namespace TEC\Tickets\Integrations\Plugins\WPML\Core;

use SitePress;

/**
 * Class Language_Switcher
 *
 * Provides safe language switching operations with automatic restoration.
 *
 * @since TBD
 */
class Language_Switcher {

	/**
	 * Get the SitePress instance.
	 *
	 * @since TBD
	 *
	 * @return SitePress|null
	 */
	private static function get_sitepress(): ?SitePress {
		global $sitepress;

		return $sitepress instanceof SitePress ? $sitepress : null;
	}

	/**
	 * Get the current language.
	 *
	 * @since TBD
	 *
	 * @return string|null
	 */
	public static function get_current_language(): ?string {
		$language = apply_filters( 'wpml_current_language', null );

		return is_string( $language ) && '' !== $language ? $language : null;
	}

	/**
	 * Switch to a language and execute a callback, then restore the original language.
	 *
	 * @since TBD
	 *
	 * @param string   $target_language Target language code.
	 * @param callable $callback Callback to execute in the target language context.
	 *
	 * @return mixed Result of the callback.
	 */
	public static function with_language( string $target_language, callable $callback ) {
		$sitepress = self::get_sitepress();
		if ( ! $sitepress instanceof SitePress ) {
			return $callback();
		}

		$original_language = self::get_current_language();

		// Switch to target language.
		$sitepress->switch_lang( $target_language, true );

		try {
			return $callback();
		} finally {
			// Restore original language.
			if ( $original_language ) {
				$sitepress->switch_lang( $original_language, true );
			}
		}
	}

	/**
	 * Switch to 'all' language context and execute a callback, then restore.
	 *
	 * @since TBD
	 *
	 * @param callable $callback Callback to execute in 'all' language context.
	 *
	 * @return mixed Result of the callback.
	 */
	public static function with_all_languages( callable $callback ) {
		return self::with_language( 'all', $callback );
	}
}
