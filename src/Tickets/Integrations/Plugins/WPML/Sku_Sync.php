<?php
/**
 * Sync ticket SKU meta field to WPML translations when updated.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Integrations\WPML
 */

namespace TEC\Tickets\Integrations\Plugins\WPML;

/**
 * Class Sku_Sync.
 *
 * Ensures that when a ticket's `_sku` meta field is updated, WPML syncs
 * the new value to all translations. This fixes the issue where `_sku`
 * is updated after `wp_update_post()`, so WPML syncs the old value.
 *
 * @since TBD
 */
class Sku_Sync {

	/**
	 * Whether a sync is currently running to prevent recursion.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private static bool $is_syncing = false;

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
		add_action( 'updated_postmeta', [ $this, 'handle' ], 10, 4 );
	}

	/**
	 * Handle SKU meta field updates.
	 *
	 * @since TBD
	 *
	 * @param int    $meta_id    Meta ID.
	 * @param int    $post_id    Post ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 *
	 * @return void
	 */
	public function handle( $meta_id, $post_id, $meta_key, $meta_value ): void {
		if ( true === self::$is_syncing ) {
			return;
		}

		// Early bail: Only process _sku meta key.
		if ( '_sku' !== $meta_key ) {
			return;
		}

		$post_id = is_numeric( $post_id ) ? (int) $post_id : 0;

		// Early bail: Invalid post ID.
		if ( 0 >= $post_id ) {
			return;
		}

		// Early bail: Only process Tickets Commerce tickets.
		if ( 'tec_tc_ticket' !== (string) get_post_type( $post_id ) ) {
			return;
		}

		// Early bail: WPML must be available.
		if ( ! $this->wpml->is_available() ) {
			return;
		}

		// Early bail: Only sync outward from the original ticket.
		if ( ! $this->wpml->is_original_post( $post_id, 'post_tec_tc_ticket' ) ) {
			return;
		}

		// Use the stored value to avoid relying on possibly-slashed hook input.
		$sku = (string) get_post_meta( $post_id, '_sku', true );

		// Early bail: SKU value must not be empty.
		if ( '' === $sku ) {
			return;
		}

		// Early bail: Ensure the WPML sync action exists.
		if ( ! has_action( 'wpml_sync_custom_field' ) ) {
			return;
		}

		self::$is_syncing = true;

		// Trigger WPML to sync this custom field to all translations.
		do_action( 'wpml_sync_custom_field', $post_id, '_sku' );

		self::$is_syncing = false;
	}
}
