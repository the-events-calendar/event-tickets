<?php
/**
 * Data Transfer Object for Classic Editor RSVP metabox POST data.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Data_Transfer_Objects
 */

declare( strict_types=1 );

namespace TEC\Tickets\RSVP\V2\Data_Transfer_Objects;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\RSVP\V2\Constants;
use Tribe__Tickets__Global_Stock as Global_Stock;

/**
 * Class Classic_Editor_Post_Data
 *
 * Parses the RSVP metabox fields submitted through the Classic Editor and
 * exposes them as typed properties. Serializes to the array shape expected
 * by ticket_add() via to_ticket_add_data().
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2\Data_Transfer_Objects
 *
 * @phpstan-type Post_Data array{
 *     ticket_type?: string,
 *     tec_tickets_rsvp_enable?: mixed,
 *     rsvp_id?: string|int,
 *     rsvp_limit?: string|int,
 *     show_not_going?: string,
 *     rsvp_start_date?: string,
 *     rsvp_start_time?: string,
 *     rsvp_end_date?: string,
 *     rsvp_end_time?: string,
 * }
 *
 * @phpstan-type Ticket_Add_Data array{
 *     ticket_id: positive-int|null,
 *     ticket_name: string,
 *     ticket_description: string,
 *     ticket_price: int,
 *     ticket_type: string,
 *     ticket_provider: class-string,
 *     show_not_going: bool,
 *     'tribe-ticket': array{mode: string, capacity?: positive-int},
 *     ticket_start_date?: string,
 *     ticket_start_time?: string,
 *     ticket_end_date?: string,
 *     ticket_end_time?: string,
 * }
 */
class Classic_Editor_Post_Data {

	/**
	 * The existing RSVP ticket ID, or null when creating a new one.
	 *
	 * @since TBD
	 *
	 * @var positive-int|null
	 */
	public ?int $ticket_id;

	/**
	 * Attendee capacity limit, or null for unlimited.
	 *
	 * @since TBD
	 *
	 * @var positive-int|null
	 */
	public ?int $capacity;

	/**
	 * Whether the "Not Going" option is shown to attendees.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	public bool $show_not_going;

	/**
	 * RSVP window open date (Y-m-d), or null if not set.
	 *
	 * @since TBD
	 *
	 * @var string|null
	 */
	public ?string $start_date;

	/**
	 * RSVP window open time (H:i:s), or null if not set.
	 *
	 * @since TBD
	 *
	 * @var string|null
	 */
	public ?string $start_time;

	/**
	 * RSVP window close date (Y-m-d), or null if not set.
	 *
	 * @since TBD
	 *
	 * @var string|null
	 */
	public ?string $end_date;

	/**
	 * RSVP window close time (H:i:s), or null if not set.
	 *
	 * @since TBD
	 *
	 * @var string|null
	 */
	public ?string $end_time;

	/**
	 * Builds a DTO instance from a raw Classic Editor metabox POST array.
	 *
	 * All sanitization and type-coercion live here, keeping downstream
	 * code free of raw-array access and string-to-type guessing.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $post_data The unslashed $_POST data from the metabox.
	 * @phpstan-param Post_Data   $post_data
	 *
	 * @return self
	 */
	public static function from_post_data( array $post_data ): self {
		$self = new self();

		$rsvp_id         = absint( $post_data['rsvp_id'] ?? 0 );
		$self->ticket_id = $rsvp_id ?: null;

		$limit                = trim( (string) ( $post_data['rsvp_limit'] ?? '' ) );
		$self->capacity       = ( '' !== $limit && (int) $limit > 0 ) ? (int) $limit : null;
		$self->show_not_going = false;
		$self->start_date     = null;
		$self->start_time     = null;
		$self->end_date       = null;
		$self->end_time       = null;

		if ( isset( $post_data['show_not_going'] ) ) {
			$self->show_not_going = tribe_is_truthy( $post_data['show_not_going'] );
		}

		if ( isset( $post_data['rsvp_start_date'] ) ) {
			$self->start_date = sanitize_text_field( (string) $post_data['rsvp_start_date'] );
		}

		if ( isset( $post_data['rsvp_start_time'] ) ) {
			$self->start_time = sanitize_text_field( (string) $post_data['rsvp_start_time'] );
		}

		if ( isset( $post_data['rsvp_end_date'] ) ) {
			$self->end_date = sanitize_text_field( (string) $post_data['rsvp_end_date'] );
		}

		if ( isset( $post_data['rsvp_end_time'] ) ) {
			$self->end_time = sanitize_text_field( (string) $post_data['rsvp_end_time'] );
		}

		return $self;
	}

	/**
	 * Serializes the DTO into the array shape expected by ticket_add().
	 *
	 * @since TBD
	 *
	 * @return Ticket_Add_Data
	 */
	public function to_ticket_add_data(): array {
		$tribe_ticket = [ 'mode' => '' ];

		if ( null !== $this->capacity ) {
			$tribe_ticket['mode']     = Global_Stock::OWN_STOCK_MODE;
			$tribe_ticket['capacity'] = $this->capacity;
		}

		$data = [
			'ticket_id'          => $this->ticket_id,
			'ticket_name'        => 'RSVP',
			'ticket_description' => '',
			'ticket_price'       => 0,
			'ticket_type'        => Constants::TC_RSVP_TYPE,
			'ticket_provider'    => Module::class,
			'show_not_going'     => $this->show_not_going,
			'tribe-ticket'       => $tribe_ticket,
		];

		if ( null !== $this->start_date ) {
			$data['ticket_start_date'] = $this->start_date;
		}

		if ( null !== $this->start_time ) {
			$data['ticket_start_time'] = $this->start_time;
		}

		if ( null !== $this->end_date ) {
			$data['ticket_end_date'] = $this->end_date;
		}

		if ( null !== $this->end_time ) {
			$data['ticket_end_time'] = $this->end_time;
		}

		return $data;
	}
}
