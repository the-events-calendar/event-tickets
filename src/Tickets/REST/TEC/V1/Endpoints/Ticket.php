<?php
/**
 * Single ticket endpoint for the TEC REST API V1.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1\Endpoints
 */

declare( strict_types=1 );

namespace TEC\Tickets\REST\TEC\V1\Endpoints;

use TEC\Common\REST\TEC\V1\Abstracts\Post_Entity_Endpoint;
use TEC\Common\REST\TEC\V1\Contracts\RUD_Endpoint;
use TEC\Tickets\Commerce\Ticket as Ticket_CPT;
use TEC\Tickets\Commerce\Models\Ticket_Model;
use TEC\Tickets\REST\TEC\V1\Tags\Tickets_Tag;
use TEC\Common\REST\TEC\V1\Collections\QueryArgumentCollection;
use TEC\Common\REST\TEC\V1\Collections\PathArgumentCollection;
use TEC\Common\REST\TEC\V1\Collections\RequestBodyCollection;
use TEC\Common\REST\TEC\V1\Parameter_Types\Positive_Integer;
use TEC\Tickets\REST\TEC\V1\Documentation\Ticket_Definition;
use TEC\Tickets\REST\TEC\V1\Documentation\Ticket_Request_Body_Definition;
use TEC\Common\REST\TEC\V1\Documentation\OpenAPI_Schema;
use TEC\Common\REST\TEC\V1\Endpoints\OpenApiDocs;
use TEC\Common\REST\TEC\V1\Parameter_Types\Definition_Parameter;
use TEC\Tickets\REST\TEC\V1\Traits\With_Tickets_ORM;
use TEC\Common\REST\TEC\V1\Traits\Update_Entity_Response;
use TEC\Common\REST\TEC\V1\Traits\Delete_Entity_Response;
use TEC\Common\REST\TEC\V1\Traits\Read_Entity_Response;
use Tribe__Tickets__Global_Stock as Global_Stock;
use InvalidArgumentException;

/**
 * Single ticket endpoint for the TEC REST API V1.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1\Endpoints
 */
class Ticket extends Post_Entity_Endpoint implements RUD_Endpoint {
	use With_Tickets_ORM;
	use Read_Entity_Response;
	use Update_Entity_Response;
	use Delete_Entity_Response;

	/**
	 * Returns the base path for the endpoint.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_base_path(): string {
		return '/tickets/%s';
	}

	/**
	 * Returns the path parameters for the endpoint.
	 *
	 * @since TBD
	 *
	 * @return PathArgumentCollection
	 */
	public function get_path_parameters(): PathArgumentCollection {
		$collection = new PathArgumentCollection();

		$collection[] = new Positive_Integer(
			'id',
			fn() => __( 'The ID of the ticket', 'event-tickets' ),
		);

		return $collection;
	}

	/**
	 * Returns the model class.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_model_class(): string {
		return Ticket_Model::class;
	}

	/**
	 * Returns whether the guest can read the object.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function guest_can_read(): bool {
		return true;
	}

	/**
	 * Returns the post type of the endpoint.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_post_type(): string {
		return Ticket_CPT::POSTTYPE;
	}

	/**
	 * Returns the schema for the endpoint.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_schema(): array {
		return [
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => 'ticket',
			'type'    => 'object',
			'$ref'    => tribe( OpenApiDocs::class )->get_url() . '#/components/schemas/Ticket',
		];
	}

	/**
	 * Returns the arguments for the read request.
	 *
	 * @since TBD
	 *
	 * @return QueryArgumentCollection
	 */
	public function read_args(): QueryArgumentCollection {
		return new QueryArgumentCollection();
	}

	/**
	 * @inheritDoc
	 */
	public function read_schema(): OpenAPI_Schema {
		$schema = new OpenAPI_Schema(
			fn() => __( 'Retrieve a Ticket', 'event-tickets' ),
			fn() => __( 'Retrieve a ticket by ID', 'event-tickets' ),
			$this->get_operation_id( 'read' ),
			$this->get_tags(),
			$this->get_path_parameters(),
			$this->read_args()
		);

		$response = new Definition_Parameter(
			new Ticket_Definition(),
			'ticket'
		);

		$schema->add_response(
			200,
			fn() => __( 'Returns the ticket', 'event-tickets' ),
			null,
			'application/json',
			$response,
		);

		$schema->add_response(
			400,
			fn() => __( 'The ticket ID is invalid', 'event-tickets' ),
		);

		$schema->add_response(
			404,
			fn() => __( 'The ticket does not exist', 'event-tickets' ),
		);

		return $schema;
	}

	/**
	 * @inheritDoc
	 */
	public function update_args(): QueryArgumentCollection {
		return new QueryArgumentCollection();
	}

	/**
	 * @inheritDoc
	 */
	public function update_schema(): OpenAPI_Schema {
		$definition = new Ticket_Request_Body_Definition();

		$collection = new RequestBodyCollection();

		$collection[] = new Definition_Parameter( $definition );

		$schema = new OpenAPI_Schema(
			fn() => __( 'Update a Ticket', 'event-tickets' ),
			fn() => __( 'Update a ticket by ID', 'event-tickets' ),
			$this->get_operation_id( 'update' ),
			$this->get_tags(),
			$this->get_path_parameters(),
			null,
			$collection->set_description_provider( fn() => __( 'The ticket data to update.', 'event-tickets' ) )->set_required( true )->set_example( $definition->get_example() ),
			true
		);

		$response = new Definition_Parameter(
			new Ticket_Definition(),
			'ticket'
		);

		$schema->add_response(
			200,
			fn() => __( 'Returns the updated ticket', 'event-tickets' ),
			null,
			'application/json',
			$response,
		);

		$schema->add_response(
			400,
			fn() => __( 'A required parameter is missing or an input parameter is in the wrong format', 'event-tickets' ),
		);

		$schema->add_response(
			401,
			fn() => __( 'The request was not authorized', 'event-tickets' ),
		);

		$schema->add_response(
			404,
			fn() => __( 'The ticket does not exist', 'event-tickets' ),
		);

		return $schema;
	}

	/**
	 * Filters ticket stock, capacity, and mode parameters according to business logic.
	 *
	 * @since TBD
	 *
	 * @param array $params The request parameters.
	 *
	 * @return array The filtered parameters.
	 */
	protected function filter_create_params( array $params ): array {
		$stock    = $params['stock'] ?? null;
		$capacity = $params['capacity'] ?? null;
		$mode     = $params['mode'] ?? null;

		// If stock or capacity is passed and the other is not, they should be the same.
		if ( ! is_null( $stock ) && is_null( $capacity ) ) {
			$params['capacity'] = $stock;
		} elseif ( ! is_null( $capacity ) && is_null( $stock ) ) {
			$params['stock'] = $capacity;
		}

		// If stock or capacity is passed, mode should default to "own".
		if ( ( ! is_null( $stock ) || ! is_null( $capacity ) ) && is_null( $mode ) ) {
			$params['mode'] = Global_Stock::OWN_STOCK_MODE;
		}

		// If nothing is passed, mode should default to unlimited (this is the existing behavior).
		if ( is_null( $stock ) && is_null( $capacity ) && is_null( $mode ) ) {
			$params['mode'] = Global_Stock::UNLIMITED_STOCK_MODE;
		}

		return $params;
	}

	/**
	 * @inheritDoc
	 */
	public function delete_args(): QueryArgumentCollection {
		return new QueryArgumentCollection();
	}

	/**
	 * @inheritDoc
	 */
	public function delete_schema(): OpenAPI_Schema {
		$schema = new OpenAPI_Schema(
			fn() => __( 'Delete a Ticket', 'event-tickets' ),
			fn() => __( 'Move a ticket to the trash', 'event-tickets' ),
			$this->get_operation_id( 'delete' ),
			$this->get_tags(),
			$this->get_path_parameters(),
			null,
			null,
			true
		);

		$response = new Definition_Parameter(
			new Ticket_Definition(),
			'ticket'
		);

		$schema->add_response(
			200,
			fn() => __( 'Returns the trashed ticket', 'event-tickets' ),
			null,
			'application/json',
			$response,
		);

		$schema->add_response(
			401,
			fn() => __( 'The request was not authorized', 'event-tickets' ),
		);

		$schema->add_response(
			404,
			fn() => __( 'The ticket does not exist', 'event-tickets' ),
		);

		$schema->add_response(
			410,
			fn() => __( 'The ticket has already been deleted', 'event-tickets' ),
		);

		return $schema;
	}

	/**
	 * Returns the tags for the endpoint.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_tags(): array {
		return [ tribe( Tickets_Tag::class ) ];
	}

	/**
	 * Returns the operation ID for the endpoint.
	 *
	 * @since TBD
	 *
	 * @param string $operation The operation to get the operation ID for.
	 *
	 * @return string
	 *
	 * @throws InvalidArgumentException If the operation is invalid.
	 */
	public function get_operation_id( string $operation ): string {
		switch ( $operation ) {
			case 'read':
				return 'getTicket';
			case 'update':
				return 'updateTicket';
			case 'delete':
				return 'deleteTicket';
		}

		throw new InvalidArgumentException( sprintf( 'Invalid operation: %s', $operation ) );
	}
}
