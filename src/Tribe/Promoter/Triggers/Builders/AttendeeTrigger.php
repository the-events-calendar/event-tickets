<?php


namespace Tribe\Tickets\Promoter\Triggers\Builders;


use Tribe\Tickets\Promoter\Triggers\Models\Attendee as Model;
use Tribe\Tickets\Promoter\Triggers\Director;
use Tribe__Tickets__Tickets;

/**
 * Class AttendeeTrigger
 *
 * @since TBD
 */
class AttendeeTrigger extends Director {
	/**
	 * @var Tribe__Tickets__Tickets
	 */
	private $provider;
	/**
	 * @var string
	 */
	private $type;

	/**
	 * Attendee constructor.
	 *
	 * @param string                  $trigger_name
	 * @param Model                   $attendee
	 * @param Tribe__Tickets__Tickets $provider
	 */
	public function __construct( $trigger_name, $attendee, $provider ) {
		$this->type     = $trigger_name;
		$this->attendee = $attendee;
		$this->provider = $provider;
	}

	/**
	 * @inheritDoc
	 */
	public function create_attendee() {
		$this->attendee->build();
	}

	/**
	 * @inheritDoc
	 */
	public function find_ticket() {
		$this->ticket = $this->provider->get_ticket( $this->attendee->event_id(), $this->attendee->product_id() );
	}

	/**
	 * @inheritDoc
	 */
	public function find_event() {
		$this->event = get_post( $this->attendee->event_id() );
	}

	/**
	 * @inheritDoc
	 */
	public function type() {
		return $this->type;
	}
}