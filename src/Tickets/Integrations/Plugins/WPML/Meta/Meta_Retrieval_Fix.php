<?php
/**
 * Fix meta retrieval for translated tickets in admin context.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Integrations\Plugins\WPML\Meta
 */

namespace TEC\Tickets\Integrations\Plugins\WPML\Meta;

use TEC\Tickets\Integrations\Plugins\WPML\Core\Wpml_Adapter;

/**
 * Class Meta_Retrieval_Fix
 *
 * Ensures that when retrieving attendee registration meta for translated tickets,
 * we fall back to the original ticket's meta if the translation doesn't have it yet.
 *
 * @since TBD
 */
class Meta_Retrieval_Fix {

	/**
	 * @since TBD
	 *
	 * @var Wpml_Adapter
	 */
	private Wpml_Adapter $wpml;

	/**
	 * Meta keys that should fall back to original ticket.
	 *
	 * @since TBD
	 *
	 * @var array<string>
	 */
	private array $meta_keys;

	/**
	 * @since TBD
	 *
	 * @param Wpml_Adapter  $wpml WPML adapter instance.
	 * @param array<string> $meta_keys Meta keys to handle.
	 */
	public function __construct( Wpml_Adapter $wpml, array $meta_keys ) {
		$this->wpml      = $wpml;
		$this->meta_keys = $meta_keys;
	}

	/**
	 * Flag to prevent recursion when retrieving meta.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private static bool $retrieving_meta = false;

	/**
	 * Register hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register(): void {
		// Filter meta retrieval to fall back to original ticket.
		// This needs to run everywhere (admin, frontend, WP CLI) so fields display correctly.
		add_filter( 'get_post_metadata', [ $this, 'handle_meta_retrieval' ], 10, 4 );
		
		// Also filter the fields result to ensure we get fields from original if translation is empty.
		// Use priority 20 to run after other filters but ensure we catch empty results.
		add_filter( 'event_tickets_plus_meta_fields_by_ticket', [ $this, 'handle_fields_filter' ], 20, 2 );
	}

	/**
	 * Handle meta retrieval for translated tickets.
	 *
	 * @since TBD
	 *
	 * @param mixed  $value     The value to return.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key.
	 * @param bool   $single    Whether to return a single value.
	 *
	 * @return mixed Meta value or null to continue with default retrieval.
	 */
	public function handle_meta_retrieval( $value, $object_id, $meta_key, $single ) {
		// Early bail: Only process configured meta keys.
		if ( ! in_array( $meta_key, $this->meta_keys, true ) ) {
			return $value;
		}

		// Early bail: Prevent recursion.
		if ( true === self::$retrieving_meta ) {
			return $value;
		}

		// Early bail: WPML must be available.
		if ( ! $this->wpml->is_available() ) {
			return $value;
		}

		$object_id = is_numeric( $object_id ) ? (int) $object_id : 0;

		// Early bail: Invalid object ID.
		if ( 0 >= $object_id ) {
			return $value;
		}

		$post_type = get_post_type( $object_id );
		if ( empty( $post_type ) ) {
			return $value;
		}

		// Only handle ticket post types.
		$ticket_post_types = [ 'tec_tc_ticket', 'product', 'tribe_rsvp_tickets', 'tribe_tpp_tickets' ];
		if ( ! in_array( $post_type, $ticket_post_types, true ) ) {
			return $value;
		}

		// Check if this is a translated ticket (not the original).
		if ( $this->wpml->is_original_post( $object_id, "post_{$post_type}" ) ) {
			return $value;
		}

		// Get the original ticket ID.
		$original_language = apply_filters( 'wpml_default_language', null );
		if ( empty( $original_language ) ) {
			return $value;
		}

		$original_id = $this->wpml->translate_post_id( $object_id, $post_type, $original_language, true );
		if ( $original_id <= 0 || $original_id === $object_id ) {
			return $value;
		}

		// Set flag to prevent recursion.
		self::$retrieving_meta = true;

		// Check if the translated ticket has the meta by querying database directly.
		global $wpdb;
		/** @var \wpdb $wpdb */
		$translated_meta_exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
				$object_id,
				$meta_key
			)
		);

		if ( $translated_meta_exists > 0 ) {
			// Translation has the meta, let WordPress retrieve it normally.
			self::$retrieving_meta = false;
			return $value;
		}

		// Translation doesn't have the meta, get it from the original ticket.
		$original_meta = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1",
				$original_id,
				$meta_key
			)
		);

		self::$retrieving_meta = false;

		if ( null !== $original_meta && '' !== $original_meta ) {
			// Unserialize if needed.
			$original_meta = maybe_unserialize( $original_meta );
			// Return the original meta value.
			// When $single is true, WordPress expects an array and takes the first element.
			// When $single is false, WordPress expects an array of values.
			return $single ? [ $original_meta ] : [ $original_meta ];
		}

		// Neither has the meta, let WordPress handle it normally.
		return $value;
	}

	/**
	 * Handle the fields filter to ensure translated tickets get fields from original.
	 *
	 * @since TBD
	 *
	 * @param array $fields    List of meta field objects for the ticket.
	 * @param int   $ticket_id The ticket ID.
	 *
	 * @return array List of meta field objects.
	 */
	public function handle_fields_filter( $fields, $ticket_id ) {
		// Early bail: If we already have fields, use them.
		if ( ! empty( $fields ) ) {
			return $fields;
		}

		// Early bail: WPML must be available.
		if ( ! $this->wpml->is_available() ) {
			return $fields;
		}

		$ticket_id = is_numeric( $ticket_id ) ? (int) $ticket_id : 0;

		// Early bail: Invalid ticket ID.
		if ( 0 >= $ticket_id ) {
			return $fields;
		}

		$post_type = get_post_type( $ticket_id );
		if ( empty( $post_type ) ) {
			return $fields;
		}

		// Only handle ticket post types.
		$ticket_post_types = [ 'tec_tc_ticket', 'product', 'tribe_rsvp_tickets', 'tribe_tpp_tickets' ];
		if ( ! in_array( $post_type, $ticket_post_types, true ) ) {
			return $fields;
		}

		// Check if this is a translated ticket (not the original).
		if ( $this->wpml->is_original_post( $ticket_id, "post_{$post_type}" ) ) {
			return $fields;
		}

		// Get the original ticket ID.
		$original_language = apply_filters( 'wpml_default_language', null );
		if ( empty( $original_language ) ) {
			return $fields;
		}

		$original_id = $this->wpml->translate_post_id( $ticket_id, $post_type, $original_language, true );
		if ( $original_id <= 0 || $original_id === $ticket_id ) {
			return $fields;
		}

		// Get fields from the original ticket.
		/** @var \Tribe__Tickets_Plus__Meta $meta */
		$meta = tribe( 'tickets-plus.meta' );
		$original_fields = $meta->get_meta_fields_by_ticket( $original_id );

		// If original has fields, regenerate them for the translated ticket.
		if ( ! empty( $original_fields ) ) {
			$regenerated_fields = [];
			foreach ( $original_fields as $field ) {
				// Regenerate the field object for the translated ticket ID.
				$field_data = (array) $field;
				$regenerated_field = $meta->generate_field( $ticket_id, $field->type, $field_data );
				if ( $regenerated_field ) {
					$regenerated_fields[] = $regenerated_field;
				}
			}
			return $regenerated_fields;
		}

		return $fields;
	}
}

