<?php
/**
 * Single ticket endpoint for the TEC REST API V1.
 *
 * @since 5.26.0
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
use TEC\Common\REST\TEC\V1\Traits\Delete_Entity_Response;
use TEC\Common\REST\TEC\V1\Traits\Read_Entity_Response;
use TEC\Tickets\REST\TEC\V1\Traits\With_Ticket_Upsert;
use TEC\Tickets\REST\TEC\V1\Traits\With_Filtered_Ticket_Params;
use TEC\Tickets\REST\TEC\V1\Traits\With_Parent_Post_Read_Check;
use TEC\Tickets\REST\TEC\V1\Traits\With_TC_Provider;
use InvalidArgumentException;

/**
 * Single ticket endpoint for the TEC REST API V1.
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST\TEC\V1\Endpoints
 */
class Ticket extends Post_Entity_Endpoint implements RUD_Endpoint {
	use With_Tickets_ORM;
	use Read_Entity_Response;
	use Delete_Entity_Response;
	use With_Filtered_Ticket_Params;
	use With_Ticket_Upsert;
	use With_Parent_Post_Read_Check;
	use With_TC_Provider;

	/**
	 * Returns the base path for the endpoint.
	 *
	 * @since 5.26.0
	 *
	 * @return string
	 */
	public function get_base_path(): string {
		return '/tickets/%s';
	}

	/**
	 * Returns the path parameters for the endpoint.
	 *
	 * @since 5.26.0
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
	 * @since 5.26.0
	 *
	 * @return string
	 */
	public function get_model_class(): string {
		return Ticket_Model::class;
	}

	/**
	 * Returns whether the guest can read the object.
	 *
	 * @since 5.26.0
	 *
	 * @return bool
	 */
	public function guest_can_read(): bool {
		return true;
	}

	/**
	 * Returns the post type of the endpoint.
	 *
	 * @since 5.26.0
	 *
	 * @return string
	 */
	public function get_post_type(): string {
		return Ticket_CPT::POSTTYPE;
	}

	/**
	 * Returns the schema for the endpoint.
	 *
	 * @since 5.26.0
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
	 * @since 5.26.0
	 *
	 * @return QueryArgumentCollection
	 */
	public function read_params(): QueryArgumentCollection {
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
			$this->read_params()
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
	public function update_params(): RequestBodyCollection {
		$definition = new Ticket_Request_Body_Definition();

		$collection = new RequestBodyCollection();

		$collection[] = new Definition_Parameter( $definition );

		return $collection
			->set_description_provider( fn() => __( 'The ticket data to update.', 'event-tickets' ) )
			->set_required( true )
			->set_example( $definition->get_example() );
	}

	/**
	 * @inheritDoc
	 */
	public function update_schema(): OpenAPI_Schema {
		$schema = new OpenAPI_Schema(
			fn() => __( 'Update a Ticket', 'event-tickets' ),
			fn() => __( 'Update a ticket by ID', 'event-tickets' ),
			$this->get_operation_id( 'update' ),
			$this->get_tags(),
			$this->get_path_parameters(),
			null,
			$this->update_params(),
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

		$schema->add_response(
			500,
			fn() => __( 'Failed to update the ticket', 'event-tickets' ),
		);

		return $schema;
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
			$this->delete_params(),
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
			fn() => __( 'The ticket has already been trashed', 'event-tickets' ),
		);

		$schema->add_response(
			500,
			fn() => __( 'Failed to delete the ticket', 'event-tickets' ),
		);

		$schema->add_response(
			501,
			fn() => __( 'The ticket does not support trashing. Set force=true to delete', 'event-tickets' ),
		);

		return $schema;
	}

	/**
	 * Returns the tags for the endpoint.
	 *
	 * @since 5.26.0
	 *
	 * @return array
	 */
	public function get_tags(): array {
		return [ tribe( Tickets_Tag::class ) ];
	}

	/**
	 * Returns the operation ID for the endpoint.
	 *
	 * @since 5.26.0
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
