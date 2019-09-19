<?php
/**
 * The Tickets abstraction objece, used to add tickets-related properties to the event object crated by the
 * `trib_get_event` function.
 *
 * @since   TBD
 *
 * @package Tribe\Events\Tickets\Views\V2\Models
 */

namespace Tribe\Events\Tickets\Views\V2\Models;


use Tribe\Utils\Lazy_Events;

/**
 * Class Tickets
 *
 * @since   TBD
 *
 * @package Tribe\Events\Tickets\Views\V2\Models
 */
class Tickets implements \ArrayAccess, \Serializable{
	use Lazy_Events;

	/**
	 * The post ID this tickets model is for.
	 *
	 * @since 4.9.14
	 *
	 * @var int
	 */
	protected $post_id;

	/**
	 * The post thumbnail data.
	 *
	 * @since 4.9.14
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * A flag property indicating whether the post thumbnail for the post exists or not.
	 *
	 * @since 4.9.16
	 *
	 * @var bool
	 */
	protected $exists;

	/**
	 * An array of all the tickets for this event.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	 protected $all_tickets;

	/**
	 * Tickets constructor.
	 *
	 * @param int $post_id The post ID.
	 */
	public function __construct( $post_id ) {
		$this->post_id = $post_id;
	}

	/**
	 * {@inheritDoc}
	 */
	public function __get( $property ) {
		if ( 'exist' === $property ) {
			return $this->exist();
		}

		return $this->offsetGet( $property );
	}

	/**
	 * {@inheritDoc}
	 */
	public function __set( $property, $value ) {
		throw new \InvalidArgumentException( "The `Tickets::{$property}` property cannot be set." );
	}

	/**
	 * {@inheritDoc}
	 */
	public function __isset( $property ) {
		return $this->offsetExists( $property );
	}

	/**
	 * Returns the data about the post thumbnail, if any.
	 *
	 * @since 4.9.14
	 *
	 * @return array An array of objects containing the post thumbnail data.
	 */
	public function fetch_data() {
		if ( ! $this->exist() ) {
			return [];
		}

		if ( null !== $this->data ) {
			return $this->data;
		}

		// @todo @juanfra this needs to be refined; here is where we set all the values.

		$this->data['link'] = (object) [
			'anchor' => '#',
			'label'  => 'Test',
		];

		$this->data['stock'] = (object) [
			'available' => 23,
		];

		return $this->data;
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetExists( $offset ) {
		$this->data = $this->fetch_data();

		return isset( $this->data[ $offset ] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetGet( $offset ) {
		$this->data = $this->fetch_data();

		return isset( $this->data[ $offset ] )
			? $this->data[ $offset ]
			: null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetSet( $offset, $value ) {
		$this->data = $this->fetch_data();

		$this->data[ $offset ] = $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function offsetUnset( $offset ) {
		$this->data = $this->fetch_data();

		unset( $this->data[ $offset ] );
	}

	/**
	 * Returns an array representation of the post thumbnail data.
	 *
	 * @since 4.9.14
	 *
	 *
	 * @return array An array representation of the post thumbnail data.
	 */
	public function to_array() {
		$this->data = $this->fetch_data();

		return json_decode( json_encode( $this->data ), true );
	}

	/**
	 * {@inheritDoc}
	 */
	public function serialize() {
		$data            = $this->fetch_data();
		$data['post_id'] = $this->post_id;

		return serialize( $data );
	}

	/**
	 * {@inheritDoc}
	 */
	public function unserialize( $serialized ) {
		$data          = unserialize( $serialized );
		$this->post_id = $data['post_id'];
		unset( $data['post_id'] );
		$this->data = $data;
	}

	/**
	 * Returns whether an event has tickets at all or not.
	 *
	 * @since TBD
	 *
	 * @return bool Whether an event has tickets at all or not.
	 */
	public function exist() {
		if ( null !== $this->exists ) {
			return $this->exists;
		}

		$this->all_tickets = \Tribe__Tickets__Tickets::get_all_event_tickets( $this->post_id );

		$this->exists = ! empty( $this->all_tickets );

		return $this->exists;
	}
}