<?php

namespace TEC\Tickets\Integrations\Themes\Divi;

use TEC\Common\Integrations\Traits\Theme_Integration;
use TEC\Tickets\Integrations\Integration_Abstract;

class Provider extends Integration_Abstract {

	use Theme_Integration;

	/**
	 * @inheritDoc
	 *
	 * @return string The slug of the integration.
	 */
	public static function get_slug(): string {
		return 'divi';
	}

	/**
	 * @inheritDoc
	 *
	 * @return bool Whether or not integrations should load.
	 */
	public function load_conditionals(): bool {
		$theme             = wp_get_theme();
		$theme_name        = strtolower( $theme->get( 'Name' ) );
		$parent_theme_name = strtolower( $theme->get( 'Parent Theme' ) );

		return $theme_name === 'divi' || $parent_theme_name === 'divi';
	}

	/**
	 * @inheritDoc
	 *
	 * @return void
	 */
	protected function load(): void {
		add_action( 'wp', [ $this, 'disable_static_css_generation' ] );
		add_filter( 'the_title', [ $this, 'hide_page_heading' ] );
	}

	/**
	 * Disable dynamic assets for the Divi theme.
	 *
	 * @return void
	 */
	public function divi_disable_dynamic_assets(): void {
		// Disable Feature: Dynamic Assets.
		add_filter( 'et_disable_js_on_demand', '__return_true' );
		add_filter( 'et_use_dynamic_css', '__return_false' );
		add_filter( 'et_should_generate_dynamic_assets', '__return_false' );

		// Disable Feature: Critical CSS.
		add_filter( 'et_builder_critical_css_enabled', '__return_false' );
	}

	/**
	 * Disable static CSS generation for the Attendee Registration page.
	 *
	 * @return void
	 */
	public function disable_static_css_generation(): void {
		$is_on_ar_page = tribe( 'Tribe__Tickets__Attendee_Registration__Template' )->is_on_ar_page();

		if ( ! $is_on_ar_page ) {
			return;
		}
		$this->divi_disable_dynamic_assets();
	}

	/**
	 * Hide the page heading for the Attendee Registration page.
	 *
	 * @param string $title The current title.
	 *
	 * @return string The modified title or an empty string if the heading should be hidden.
	 */
	public function hide_page_heading( string $title ): string {

		$is_on_ar_page = tribe( 'Tribe__Tickets__Attendee_Registration__Template' )->is_on_ar_page();

		if ( ! $is_on_ar_page ) {
			// Return the original title for other pages/posts.
			return $title;
		}

		// Return an empty title to hide the heading.
		return '';
	}
}