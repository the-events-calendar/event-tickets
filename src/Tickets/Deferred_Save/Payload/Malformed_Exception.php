<?php
/**
 * Thrown for a request value that cannot be a payload at all.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Payload
 */

namespace TEC\Tickets\Deferred_Save\Payload;

use InvalidArgumentException;

/**
 * Class Malformed_Exception.
 *
 * A payload is refused as a whole, rather than entry by entry, when its root is not an array or
 * it carries a part the contract does not know. The message is ready to show to the user.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Payload
 */
class Malformed_Exception extends InvalidArgumentException {
}
