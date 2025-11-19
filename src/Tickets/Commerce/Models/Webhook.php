<?php
/**
 * Webhook model.
 *
 * @since 5.24.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Models;

use TEC\Tickets\Commerce\Tables\Webhooks as Table;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Exceptions\Not_Found_Exception;
use TEC\Tickets\Exceptions\DuplicateEntryException;
use Exception;
use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;

/**
 * Webhook model.
 *
 * @since 5.24.0
 */
class Webhook {
	/**
	 * The data of the webhook.
	 *
	 * @var array
	 */
	private array $data = [];

	/**
	 * The constructor.
	 *
	 * @param string $uid The uid of the webhook.
	 *
	 * @throws Not_Found_Exception If the webhook is not found.
	 */
	public function __construct( string $uid ) {
		$record = Table::get_by_id( $uid );

		if ( ! $record ) {
			throw new Not_Found_Exception( 'Webhook not found' );
		}

		$columns = Table::get_columns();

		foreach ( $columns->get_names() as $column ) {
			$this->data[ $column ] = $record[ $column ] ?? null;
		}
	}

	/**
	 * Get a property of the webhook.
	 *
	 * @param string $key The key of the property.
	 *
	 * @return mixed The value of the property.
	 *
	 * @throws Exception If the property is not a valid webhook property.
	 */
	public function __get( string $key ) {
		if ( ! isset( $this->data[ $key ] ) ) {
			throw new Exception( "Property $key is not a valid webhook property" );
		}

		return $this->data[ $key ];
	}

	/**
	 * Create a new webhook.
	 *
	 * @param array $data The data of the webhook.
	 *
	 * @return self The webhook.
	 *
	 * @throws DuplicateEntryException If the webhook already exists.
	 */
	public static function create( array $data ): self {
		try {
			Table::insert_many( [ $data ] );

			return new self( $data[ Table::uid_column() ] ?? '' );
		} catch ( DatabaseQueryException $e ) {
			throw new DuplicateEntryException();
		}
	}

	/**
	 * Get a webhook by its uid.
	 *
	 * @param string $uid The uid of the webhook.
	 *
	 * @return self The webhook.
	 *
	 * @throws Not_Found_Exception If the webhook is not found.
	 */
	public static function get( string $uid ): self {
		return new self( $uid );
	}

	/**
	 * Update a webhook.
	 *
	 * @param array $data The data of the webhook.
	 *
	 * @return self The webhook.
	 */
	public static function update( array $data ): self {
		Table::update_many( [ $data ] );

		return new self( $data[ Table::uid_column() ] ?? '' );
	}

	/**
	 * Delete a webhook.
	 *
	 * @param string $uid The uid of the webhook.
	 *
	 * @return bool True if the webhook was deleted, false otherwise.
	 */
	public static function delete( string $uid ): bool {
		return (bool) Table::delete_many( [ $uid ] );
	}
}
