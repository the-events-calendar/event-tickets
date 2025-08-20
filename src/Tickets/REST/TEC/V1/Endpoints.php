<?php
/**
 * Endpoints Controller class.
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST\TEC\V1
 */

declare( strict_types=1 );

namespace TEC\Tickets\REST\TEC\V1;

use TEC\Common\REST\TEC\V1\Contracts\Definition_Interface;
use TEC\Common\REST\TEC\V1\Contracts\Endpoint_Interface;
use TEC\Common\REST\TEC\V1\Contracts\Tag_Interface;
use TEC\Common\REST\TEC\V1\Abstracts\Endpoints_Controller;
use TEC\Tickets\REST\TEC\V1\Endpoints\Tickets;
use TEC\Tickets\REST\TEC\V1\Endpoints\Ticket;
use TEC\Tickets\REST\TEC\V1\Tags\Tickets_Tag;
use TEC\Tickets\REST\TEC\V1\Documentation\Ticket_Definition;
use TEC\Tickets\REST\TEC\V1\Documentation\Ticket_Request_Body_Definition;

/**
 * Endpoints Controller class.
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST\TEC\V1
 */
class Endpoints extends Endpoints_Controller {
	/**
	 * Returns the endpoints to register.
	 *
	 * @since 5.26.0
	 *
	 * @return Endpoint_Interface[]
	 */
	public function get_endpoints(): array {
		return [
			Tickets::class,
			Ticket::class,
		];
	}

	/**
	 * Returns the tags to register.
	 *
	 * @since 5.26.0
	 *
	 * @return Tag_Interface[]
	 */
	public function get_tags(): array {
		return [
			Tickets_Tag::class,
		];
	}

	/**
	 * Returns the definitions to register.
	 *
	 * @since 5.26.0
	 *
	 * @return Definition_Interface[]
	 */
	public function get_definitions(): array {
		return [
			Ticket_Definition::class,
			Ticket_Request_Body_Definition::class,
		];
	}
}
