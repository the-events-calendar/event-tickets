<?php
/**
 * WPML adapter.
 *
 * Adapter class that wraps WPML functionality and provides a clean interface
 * for translation operations without directly coupling to WPML's API.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Integrations\Plugins\WPML\Core
 */

namespace TEC\Tickets\Integrations\Plugins\WPML\Core;

/**
 * Class Wpml_Adapter
 *
 * Adapter for WPML translation functionality.
 * Provides methods for translating post IDs, getting languages, and managing translation relationships.
 *
 * @since TBD
 */
class Wpml_Adapter {

	/**
	 * Whether WPML is available.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			return false;
		}

		if ( ! class_exists( 'SitePress' ) ) {
			return false;
		}

		if ( ! has_filter( 'wpml_object_id' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Translate a post ID to a specific language.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id Post ID.
	 * @param string $post_type Post type.
	 * @param string $language Target language code.
	 * @param bool   $fallback Whether to fallback to original if missing.
	 *
	 * @return int
	 */
	public function translate_post_id( int $post_id, string $post_type, string $language, bool $fallback = true ): int {
		if ( $post_id <= 0 ) {
			return 0;
		}

		$translated = apply_filters( 'wpml_object_id', $post_id, $post_type, $fallback, $language );

		return is_numeric( $translated ) ? (int) $translated : 0;
	}

	/**
	 * Translate a page ID to the current language.
	 *
	 * @since TBD
	 *
	 * @param int $page_id Page ID.
	 *
	 * @return int Translated page ID, or original if translation not found.
	 */
	public function translate_page_id( int $page_id ): int {
		if ( $page_id <= 0 ) {
			return 0;
		}

		$translated = apply_filters( 'wpml_object_id', $page_id, 'page', true );

		return is_numeric( $translated ) && $translated > 0 ? (int) $translated : $page_id;
	}

	/**
	 * Get a post language code.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id Post ID.
	 * @param string $post_type Post type.
	 *
	 * @return string Language code, empty string if not found.
	 */
	public function get_post_language( int $post_id, string $post_type ): string {
		if ( $post_id <= 0 || '' === $post_type ) {
			return '';
		}

		$args = [
			'element_id'   => $post_id,
			'element_type' => $post_type,
		];

		$language = apply_filters( 'wpml_element_language_code', false, $args );

		return is_string( $language ) ? $language : '';
	}

	/**
	 * Get all translation IDs for a post (including original).
	 *
	 * @since TBD
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array<int>
	 */
	public function get_translation_ids( int $post_id ): array {
		if ( $post_id <= 0 ) {
			return [];
		}

		$post_type = get_post_type( $post_id );
		if ( empty( $post_type ) ) {
			return [ $post_id ];
		}

		$element_type = (string) apply_filters( 'wpml_element_type', $post_type );
		if ( '' === $element_type ) {
			return [ $post_id ];
		}

		$trid = apply_filters( 'wpml_element_trid', null, $post_id, $element_type );
		if ( empty( $trid ) ) {
			return [ $post_id ];
		}

		$translations = apply_filters( 'wpml_get_element_translations', null, $trid, $element_type );
		if ( empty( $translations ) || ! is_array( $translations ) ) {
			return [ $post_id ];
		}

		$ids = wp_list_pluck( array_values( $translations ), 'element_id' );
		$ids = array_values( array_unique( array_filter( array_map( 'intval', (array) $ids ) ) ) );

		return ! empty( $ids ) ? $ids : [ $post_id ];
	}

	/**
	 * Ensure a post has language details set.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id Post ID.
	 * @param string $post_type Post type.
	 * @param string $language Language code.
	 *
	 * @return void
	 */
	public function set_language_details( int $post_id, string $post_type, string $language ): void {
		if ( $post_id <= 0 || '' === $post_type || '' === $language ) {
			return;
		}

		$element_type = (string) apply_filters( 'wpml_element_type', $post_type );
		if ( '' === $element_type ) {
			return;
		}

		$trid = apply_filters( 'wpml_element_trid', null, $post_id, $element_type );
		if ( ! empty( $trid ) ) {
			return;
		}

		do_action(
			'wpml_set_element_language_details',
			[
				'element_id'    => $post_id,
				'element_type'  => $element_type,
				'trid'          => false,
				'language_code' => $language,
			]
		);
	}

	/**
	 * Check if a post is the original post in its translation group.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id Post ID.
	 * @param string $post_type Post type (e.g., 'post_tec_tc_ticket').
	 *
	 * @return bool True if the post is the original, false otherwise.
	 */
	public function is_original_post( int $post_id, string $post_type ): bool {
		if ( $post_id <= 0 || '' === $post_type ) {
			return false;
		}

		global $wpml_post_translations;

		// If WPML post translations object is not available, assume it's original.
		if ( ! $wpml_post_translations ) {
			return true;
		}

		// Get the source language code for this post.
		// If it returns null/false, the post is the original.
		$source_lang = $wpml_post_translations->get_source_lang_code( $post_id );

		// If source_lang is null/false, this is the original post.
		return empty( $source_lang );
	}

	/**
	 * Get the current language code.
	 *
	 * @since TBD
	 *
	 * @return string|null Language code, or null if not available.
	 */
	public function get_current_language(): ?string {
		return Language_Switcher::get_current_language();
	}
}
