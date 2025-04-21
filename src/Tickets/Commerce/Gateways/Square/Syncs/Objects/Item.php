<?php

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects;

use JsonSerializable;

abstract class Item implements JsonSerializable {
	protected array $data = [];

	public const SQUARE_ID_META = '_tec_tickets_commerce_square_object_id';

	public const SQUARE_VERSION_META = '_tec_tickets_commerce_square_version';

	public const SQUARE_SYNCED_META = '_tec_tickets_commerce_square_synced';

	public const SQUARE_SYNC_HISTORY_META = '_tec_tickets_commerce_square_sync_history';

	protected const ITEM_TYPE = '';

	abstract public function get_wp_id(): int;

	abstract protected function set_object_values(): array;

	public function get_id(): string {
		if ( ! empty( $this->data['id'] ) ) {
			return $this->data['id'];
		}

		$square_id = get_post_meta( $this->get_wp_id(), self::SQUARE_ID_META, true );

		if ( $square_id ) {
			$this->data['id'] = $square_id;
			return $this->data['id'];
		}

		$this->data['id'] = '#' . str_replace( [ 'https://', 'http://' ], '', home_url() ) . '-' . $this->get_wp_id();

		return $this->data['id'];
	}

	public function jsonSerialize(): array {
		return $this->to_array();
	}

	public function to_array(): array {
		$this->get_id();
		$data = $this->set_object_values();
		return $data;
	}

	public function set( string $key, $value ): void {
		$this->data[ $key ] = $value;
	}

	public function get( string $key ) {
		return $this->data[ $key ] ?? null;
	}

	public function set_item_data( string $key, $value ): void {
		$this->data[ strtolower( static::ITEM_TYPE ) . '_data' ][ $key ] = $value;
	}

	public function get_item_data( string $key ) {
		return $this->data[ strtolower( static::ITEM_TYPE ) . '_data' ][ $key ] ?? null;
	}

	protected function register_hooks(): void {
		if ( ! has_action( 'tec_tickets_commerce_square_sync_ticket_id_mapping_' . $this->get_id(), [ $this, 'on_ticket_id_mapping' ] ) ) {
			add_action( 'tec_tickets_commerce_square_sync_ticket_id_mapping_' . $this->get_id(), [ $this, 'on_ticket_id_mapping' ] );
		}
	}

	public function on_ticket_id_mapping( string $square_object_id ): void {
		update_post_meta( $this->get_wp_id(), self::SQUARE_SYNCED_META, time() );

		if ( ! has_action( 'tec_tickets_commerce_square_sync_object_' . $square_object_id, [ $this, 'on_sync_object' ] ) ) {
			add_action( 'tec_tickets_commerce_square_sync_object_' . $square_object_id, [ $this, 'on_sync_object' ] );
		}

		if ( $this->get_id() === $square_object_id ) {
			return;
		}

		$this->data['id'] = $square_object_id;

		update_post_meta( $this->get_wp_id(), self::SQUARE_ID_META, $square_object_id );
	}

	public function on_sync_object( array $square_object ): void {
		if ( isset( $square_object['version'] ) ) {
			update_post_meta( $this->get_wp_id(), self::SQUARE_VERSION_META, $square_object['version'] );
		}

		/**
		 * Fires when a object is synced from Square.
		 *
		 * @since TBD
		 *
		 * @param int   $wp_id The WordPress ID of the object.
		 * @param array $square_object The sync object.
		 */
		do_action( 'tec_tickets_commerce_square_object_synced_' . $this->get_id(), $this->get_wp_id(), $square_object );

		/**
		 * Fires when a object is synced from Square.
		 *
		 * @since TBD
		 *
		 * @param string $object_id The Square's object ID.
		 * @param int    $wp_id The WordPress ID of the object.
		 * @param array  $square_object The sync object.
		 */
		do_action( 'tec_tickets_commerce_square_object_synced', $this->get_id(), $this->get_wp_id(), $square_object );
	}
}
