<?php
/**
 * Sync ticket meta fields to WPML translations when updated.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Integrations\Plugins\WPML\Meta
 */

namespace TEC\Tickets\Integrations\Plugins\WPML\Meta;

use TEC\Tickets\Integrations\Plugins\WPML\Core\Wpml_Adapter;

/**
 * Class Meta_Sync.
 *
 * Ensures that when ticket meta fields are updated after `wp_update_post()`,
 * WPML syncs the new values to all translations. This fixes the issue where
 * meta fields updated after `wp_update_post()` cause WPML to sync stale values
 * during `after_save_post`.
 *
 * @since TBD
 */
class Meta_Sync {

	/**
	 * Whether a sync is currently running to prevent recursion.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private static bool $is_syncing = false;

	/**
	 * Meta keys that should be synced when updated.
	 *
	 * @since TBD
	 *
	 * @var array<string>
	 */
	private array $meta_keys;

	/**
	 * @since TBD
	 *
	 * @var Wpml_Adapter
	 */
	private Wpml_Adapter $wpml;

	/**
	 * @since TBD
	 *
	 * @param Wpml_Adapter  $wpml WPML adapter instance.
	 * @param array<string> $meta_keys Meta keys to sync.
	 */
	public function __construct( Wpml_Adapter $wpml, array $meta_keys ) {
		$this->wpml      = $wpml;
		$this->meta_keys = $meta_keys;
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
	 * Handle meta field updates.
	 *
	 * @since TBD
	 *
	 * @param int    $meta_id Meta ID.
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value.
	 *
	 * @return void
	 */
	public function handle( $meta_id, $post_id, $meta_key, $meta_value ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Early bail: Prevent recursion.
		if ( true === self::$is_syncing ) {
			return;
		}

		// Early bail: Only process configured meta keys.
		if ( ! in_array( $meta_key, $this->meta_keys, true ) ) {
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

		// Early bail: Ensure the WPML sync action exists.
		if ( ! has_action( 'wpml_sync_custom_field' ) ) {
			return;
		}

		// Use the stored value to avoid relying on possibly-slashed hook input.
		$stored_value = get_post_meta( $post_id, $meta_key, true );

		// For some meta keys, empty values are valid (e.g., deleted meta).
		// We still want to sync deletions, so we only skip if the value is explicitly null.
		// However, for most fields, we want to skip empty strings/zeros.
		// Let WPML handle the sync regardless - it will copy the actual stored value.

		self::$is_syncing = true;

		// Trigger WPML to sync this custom field to all translations.
		do_action( 'wpml_sync_custom_field', $post_id, $meta_key );

		self::$is_syncing = false;
	}
}
