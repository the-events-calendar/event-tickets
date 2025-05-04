<?php
/**
 * Abstract Item object for Square synchronization.
 *
 * This abstract class provides the base functionality for representing WordPress objects
 * as Square catalog items. It handles common operations for syncing with Square's API.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects;

use JsonSerializable;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;
use TEC\Tickets\Commerce\Gateways\Square\Settings;

/**
 * Abstract Class Item
 *
 * Base class for all Square catalog items. Provides common functionality for
 * identifying, serializing, and syncing objects between WordPress and Square.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */
abstract class Item implements JsonSerializable {
	/**
	 * The data structure for the Square catalog item.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected array $data = [];

	/**
	 * Meta key for storing the Square object ID.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const SQUARE_ID_META = '_tec_tickets_commerce_square_object_id_%s';

	/**
	 * Meta key for storing the Square object version.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const SQUARE_VERSION_META = '_tec_tickets_commerce_square_version_%s';

	/**
	 * Meta key for storing the last sync timestamp.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const SQUARE_SYNCED_META = '_tec_tickets_commerce_square_synced_%s';

	/**
	 * Meta key for storing the sync history.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const SQUARE_SYNC_HISTORY_META = '_tec_tickets_commerce_square_sync_history_%s';

	/**
	 * The type of Square catalog item this class represents.
	 * Should be overridden by child classes.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const ITEM_TYPE = '';

	/**
	 * Get the WordPress ID of the object.
	 *
	 * @since TBD
	 *
	 * @return int The WordPress post ID.
	 */
	abstract public function get_wp_id(): int;

	/**
	 * Set the object values for synchronization with Square.
	 *
	 * @since TBD
	 *
	 * @return array The data array prepared for Square synchronization.
	 */
	abstract protected function set_object_values(): array;

	/**
	 * Get the Square ID for this object.
	 *
	 * @since TBD
	 *
	 * @return string The Square object ID.
	 */
	public function get_id(): string {
		if ( ! empty( $this->data['id'] ) ) {
			return $this->data['id'];
		}

		$square_id = self::get_remote_object_id( $this->get_wp_id() );

		if ( $square_id ) {
			$this->data['id'] = $square_id;
			return $this->data['id'];
		}

		$this->data['id'] = '#' . str_replace( [ 'https://', 'http://' ], '', home_url() ) . '-' . $this->get_wp_id();

		return $this->data['id'];
	}

	/**
	 * Get the remote object ID for a given WordPress ID.
	 *
	 * @since TBD
	 *
	 * @param int $id The WordPress ID.
	 *
	 * @return string The remote object ID.
	 */
	public static function get_remote_object_id( int $id ): string {
		return (string) Settings::get_environmental_meta( $id, self::SQUARE_ID_META );
	}

	/**
	 * Delete the remote data for a post.
	 *
	 * @since TBD
	 *
	 * @param int $id The ID.
	 *
	 * @return void
	 */
	public static function delete( int $id ): void {
		Settings::delete_environmental_meta( $id, self::SQUARE_ID_META );
		Settings::delete_environmental_meta( $id, self::SQUARE_SYNCED_META );
		Settings::delete_environmental_meta( $id, self::SQUARE_VERSION_META );
		Settings::delete_environmental_meta( $id, self::SQUARE_SYNC_HISTORY_META );
	}

	/**
	 * Serialize the object to JSON.
	 *
	 * @since TBD
	 *
	 * @return array The data array for JSON serialization.
	 */
	public function jsonSerialize(): array {
		return $this->to_array();
	}

	/**
	 * Convert the object to an array for Square API.
	 *
	 * @since TBD
	 *
	 * @return array The data array prepared for Square API.
	 */
	public function to_array(): array {
		$this->get_id();
		$version = (int) Settings::get_environmental_meta( $this->get_wp_id(), self::SQUARE_VERSION_META );
		if ( $version ) {
			$this->data['version'] = $version;
		}
		$this->data['present_at_location_ids'] = [ tribe( Merchant::class )->get_location_id() ];

		return $this->set_object_values();
	}

	/**
	 * Get the WordPress controlled fields for a given Square object.
	 *
	 * @since TBD
	 *
	 * @param array $square_object The Square object.
	 *
	 * @return array The WordPress controlled fields.
	 */
	public function get_wp_controlled_fields( array $square_object ): array {
		unset( $square_object['version'] );
		$myself = $this->to_array();

		$myself['present_at_location_ids'] = [ tribe( Merchant::class )->get_location_id() ];

		$square_object[ strtolower( static::ITEM_TYPE ) . '_data' ] = array_intersect_key(
			$square_object[ strtolower( static::ITEM_TYPE ) . '_data' ],
			$myself[ strtolower( static::ITEM_TYPE ) . '_data' ]
		);

		return array_intersect_key( $square_object, $myself );
	}

	/**
	 * Set a value in the data array.
	 *
	 * @since TBD
	 *
	 * @param string $key   The key to set.
	 * @param mixed  $value The value to set.
	 *
	 * @return void
	 */
	public function set( string $key, $value ): void {
		$this->data[ $key ] = $value;
	}

	/**
	 * Get a value from the data array.
	 *
	 * @since TBD
	 *
	 * @param string $key The key to get.
	 *
	 * @return mixed|null The value or null if not set.
	 */
	public function get( string $key ) {
		return $this->data[ $key ] ?? null;
	}

	/**
	 * Set a value in the item_data array.
	 *
	 * @since TBD
	 *
	 * @param string $key   The key to set.
	 * @param mixed  $value The value to set.
	 *
	 * @return void
	 */
	public function set_item_data( string $key, $value ): void {
		$this->data[ strtolower( static::ITEM_TYPE ) . '_data' ][ $key ] = $value;
	}

	/**
	 * Get a value from the item_data array.
	 *
	 * @since TBD
	 *
	 * @param string $key The key to get.
	 *
	 * @return mixed|null The value or null if not set.
	 */
	public function get_item_data( string $key ) {
		return $this->data[ strtolower( static::ITEM_TYPE ) . '_data' ][ $key ] ?? null;
	}

	/**
	 * Register hooks for this object.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function register_hooks(): void {
		if ( ! has_action( 'tec_tickets_commerce_square_sync_ticket_id_mapping_' . $this->get_id(), [ $this, 'on_ticket_id_mapping' ] ) ) {
			add_action( 'tec_tickets_commerce_square_sync_ticket_id_mapping_' . $this->get_id(), [ $this, 'on_ticket_id_mapping' ] );
		}

		if ( ! has_action( 'tec_tickets_commerce_square_sync_object_' . $this->get_id(), [ $this, 'on_sync_object' ] ) ) {
			add_action( 'tec_tickets_commerce_square_sync_object_' . $this->get_id(), [ $this, 'on_sync_object' ] );
		}
	}

	/**
	 * Handle ticket ID mapping from Square.
	 *
	 * @since TBD
	 *
	 * @param string $square_object_id The Square object ID.
	 *
	 * @return void
	 */
	public function on_ticket_id_mapping( string $square_object_id ): void {
		if ( ! has_action( 'tec_tickets_commerce_square_sync_object_' . $square_object_id, [ $this, 'on_sync_object' ] ) ) {
			add_action( 'tec_tickets_commerce_square_sync_object_' . $square_object_id, [ $this, 'on_sync_object' ] );
		}

		if ( $this->get_id() === $square_object_id ) {
			return;
		}

		$this->data['id'] = $square_object_id;

		Settings::set_environmental_meta( $this->get_wp_id(), self::SQUARE_ID_META, $square_object_id );
	}

	/**
	 * Handle object sync from Square.
	 *
	 * @since TBD
	 *
	 * @param array $square_object The Square object data.
	 *
	 * @return void
	 */
	public function on_sync_object( array $square_object ): void {
		Settings::set_environmental_meta( $this->get_wp_id(), self::SQUARE_SYNCED_META, time() );

		if ( isset( $square_object['version'] ) ) {
			Settings::set_environmental_meta( $this->get_wp_id(), self::SQUARE_VERSION_META, $square_object['version'] );
		}

		/**
		 * Fires when a object is synced from Square.
		 *
		 * @since TBD
		 *
		 * @param int   $wp_id The WordPress ID of the object.
		 * @param array $square_object The sync object.
		 * @param Item  $item The item object.
		 */
		do_action( 'tec_tickets_commerce_square_object_synced_' . $this->get_id(), $this->get_wp_id(), $square_object, $this );

		/**
		 * Fires when a object is synced from Square.
		 *
		 * @since TBD
		 *
		 * @param string $object_id The Square's object ID.
		 * @param int    $wp_id The WordPress ID of the object.
		 * @param array  $square_object The sync object.
		 * @param Item   $item The item object.
		 */
		do_action( 'tec_tickets_commerce_square_object_synced', $this->get_id(), $this->get_wp_id(), $square_object, $this );
	}
}
