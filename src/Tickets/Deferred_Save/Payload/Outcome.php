<?php
/**
 * A payload together with what was refused on the way to it.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Payload
 */

namespace TEC\Tickets\Deferred_Save\Payload;

use TEC\Tickets\Deferred_Save\Payload;

/**
 * Class Outcome.
 *
 * What the parser and the checks both produce: the entries that survived, as a `Payload`, and
 * the `Rejections` recorded while producing it. Immutable.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Payload
 */
class Outcome {
	/**
	 * The entries that survived.
	 *
	 * @since TBD
	 *
	 * @var Payload
	 */
	private Payload $payload;

	/**
	 * What was refused, and why.
	 *
	 * @since TBD
	 *
	 * @var Rejections
	 */
	private Rejections $rejections;

	/**
	 * Outcome constructor.
	 *
	 * @since TBD
	 *
	 * @param Payload    $payload    The entries that survived.
	 * @param Rejections $rejections What was refused, and why.
	 */
	public function __construct( Payload $payload, Rejections $rejections ) {
		$this->payload    = $payload;
		$this->rejections = $rejections;
	}

	/**
	 * Returns the entries that survived.
	 *
	 * @since TBD
	 *
	 * @return Payload The payload.
	 */
	public function payload(): Payload {
		return $this->payload;
	}

	/**
	 * Returns what was refused, and why.
	 *
	 * @since TBD
	 *
	 * @return Rejections The rejections.
	 */
	public function rejections(): Rejections {
		return $this->rejections;
	}
}
