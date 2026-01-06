<?php
/**
 * Translate relationship meta after WPML copies custom fields.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Integrations\WPML
 */

namespace TEC\Tickets\Integrations\Plugins\WPML;

class Relationship_Meta_Translator {

	/**
	 * @since TBD
	 *
	 * @var Wpml_Adapter
	 */
	private Wpml_Adapter $wpml;

	/**
	 * @since TBD
	 *
	 * @var array<string>
	 */
	private array $meta_keys;

	/**
	 * @since TBD
	 *
	 * @param Wpml_Adapter $wpml      WPML adapter instance.
	 * @param array<string> $meta_keys Meta keys to translate.
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
		add_action( 'wpml_after_copy_custom_field', [ $this, 'handle' ], 10, 3 );
	}

	/**
	 * Handle meta translation after copy.
	 *
	 * @since TBD
	 *
	 * @param int    $original_id Original post ID.
	 * @param int    $post_id     Target post ID.
	 * @param string $key         Meta key.
	 *
	 * @return void
	 */
	public function handle( $original_id, $post_id, $key ): void {
		$post_id = is_numeric( $post_id ) ? (int) $post_id : 0;

		if ( $post_id <= 0 || empty( $key ) ) {
			return;
		}

		if ( ! in_array( $key, $this->meta_keys, true ) ) {
			return;
		}

		$value = get_post_meta( $post_id, $key, true );
		$value = is_numeric( $value ) ? (int) $value : 0;

		if ( $value <= 0 ) {
			return;
		}

		$post_type = (string) get_post_type( $post_id );
		if ( '' === $post_type ) {
			return;
		}

		$language = $this->wpml->get_post_language( $post_id, $post_type );
		if ( '' === $language ) {
			return;
		}

		// Relationship keys we care about point to events.
		$translated_id = $this->wpml->translate_post_id( $value, 'tribe_events', $language, true );
		if ( $translated_id <= 0 || $translated_id === $value ) {
			return;
		}

		update_post_meta( $post_id, $key, $translated_id );
	}
}
