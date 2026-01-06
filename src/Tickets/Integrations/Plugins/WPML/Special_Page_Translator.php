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
}
