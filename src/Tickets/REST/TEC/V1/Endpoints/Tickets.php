<?php
/**
 * Archive tickets endpoint for the TEC REST API V1.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1\Endpoints
 */

declare( strict_types=1 );

namespace TEC\Tickets\REST\TEC\V1\Endpoints;

use TEC\Common\REST\TEC\V1\Abstracts\Post_Entity_Endpoint;
use TEC\Common\REST\TEC\V1\Contracts\Readable_Endpoint;
use TEC\Common\REST\TEC\V1\Contracts\Creatable_Endpoint;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Models\Ticket_Model;
use TEC\Tickets\REST\TEC\V1\Tags\Tickets_Tag;
use TEC\Common\REST\TEC\V1\Traits\Read_Archive_Response;
use TEC\Common\REST\TEC\V1\Traits\Create_Entity_Response;
use TEC\Common\REST\TEC\V1\Collections\HeadersCollection;
use TEC\Common\REST\TEC\V1\Collections\QueryArgumentCollection;
use TEC\Common\REST\TEC\V1\Collections\RequestBodyCollection;
use TEC\Common\REST\TEC\V1\Parameter_Types\Boolean;
use TEC\Common\REST\TEC\V1\Parameter_Types\Positive_Integer;
use TEC\Common\REST\TEC\V1\Parameter_Types\Text;
use TEC\Common\REST\TEC\V1\Parameter_Types\Array_Of_Type;
use TEC\Common\REST\TEC\V1\Endpoints\OpenApiDocs;
use TEC\Common\REST\TEC\V1\Parameter_Types\URI;
use TEC\Tickets\REST\TEC\V1\Documentation\Ticket_Definition;
use TEC\Tickets\REST\TEC\V1\Documentation\Ticket_Request_Body_Definition;
use TEC\Common\REST\TEC\V1\Documentation\OpenAPI_Schema;
use TEC\Common\REST\TEC\V1\Parameter_Types\Definition_Parameter;
use TEC\Tickets\REST\TEC\V1\Traits\With_Tickets_ORM;

/**
 * Archive tickets endpoint for the TEC REST API V1.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1\Endpoints
 */
class Tickets extends Post_Entity_Endpoint implements Readable_Endpoint, Creatable_Endpoint {
	use Read_Archive_Response;
	use Create_Entity_Response;
	use With_Tickets_ORM;

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
	 * Returns the base path of the endpoint.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_base_path(): string {
		return '/tickets';
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
		return Ticket::POSTTYPE;
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
			'title'   => 'tickets',
			'type'    => 'array',
			'items'   => [
				'$ref' => tribe( OpenApiDocs::class )->get_url() . '#/components/schemas/Ticket',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function read_schema(): OpenAPI_Schema {
		$schema = new OpenAPI_Schema(
			fn() => __( 'Retrieve Tickets', 'event-tickets' ),
			fn() => __( 'Returns a list of tickets', 'event-tickets' ),
			'getTickets',
			[ tribe( Tickets_Tag::class ) ],
			null,
			$this->read_args()
		);

		$headers_collection = new HeadersCollection();

		$headers_collection[] = new Positive_Integer(
			'X-WP-Total',
			fn() => __( 'The total number of tickets matching the request.', 'event-tickets' ),
			null,
			null,
			null,
			true
		);

		$headers_collection[] = new Positive_Integer(
			'X-WP-TotalPages',
			fn() => __( 'The total number of pages for the request.', 'event-tickets' ),
			null,
			null,
			null,
			true
		);

		$headers_collection[] = new Array_Of_Type(
			'Link',
			fn() => __(
				'RFC 5988 Link header for pagination. Contains navigation links with relationships:
				`rel="next"` for the next page (if not on last page),
				`rel="prev"` for the previous page (if not on first page).
				Header is omitted entirely if there\'s only one page',
				'event-tickets'
			),
			URI::class,
		);

		$response = new Array_Of_Type(
			'Ticket',
			null,
			Ticket_Definition::class,
		);

		$schema->add_response(
			200,
			fn() => __( 'Returns the list of tickets', 'event-tickets' ),
			$headers_collection,
			'application/json',
			$response,
		);

		$schema->add_response(
			400,
			fn() => __( 'A required parameter is missing or an input parameter is in the wrong format', 'event-tickets' ),
		);

		$schema->add_response(
			404,
			fn() => __( 'The requested page was not found', 'event-tickets' ),
		);

		return $schema;
	}

	/**
	 * Returns the arguments for the read request.
	 *
	 * @since TBD
	 *
	 * @return QueryArgumentCollection
	 */
	public function read_args(): QueryArgumentCollection {
		$collection = new QueryArgumentCollection();

		$collection[] = new Positive_Integer(
			'page',
			fn() => __( 'The collection page number.', 'event-tickets' ),
			1,
			1
		);

		$collection[] = new Positive_Integer(
			'per_page',
			fn() => __( 'Maximum number of items to be returned in result set.', 'event-tickets' ),
			$this->get_default_posts_per_page(),
			1,
			100,
		);

		$collection[] = new Text(
			'search',
			fn() => __( 'Limit results to those matching a string.', 'event-tickets' ),
		);

		$collection[] = new Array_Of_Type(
			'events',
			fn() => __( 'Limit result set to tickets assigned to specific events.', 'event-tickets' ),
			Positive_Integer::class,
		);

		$collection[] = new Text(
			'orderby',
			fn() => __( 'Sort collection by attribute.', 'event-tickets' ),
			'date',
			[
				'date',
				'id',
				'include',
				'relevance',
				'slug',
				'include_slugs',
				'title',
			]
		);

		$collection[] = new Text(
			'order',
			fn() => __( 'Order sort attribute ascending or descending.', 'event-tickets' ),
			'desc',
			[
				'asc',
				'desc',
			]
		);

		$collection[] = new Text(
			'status',
			fn() => __( 'Limit result set to tickets assigned one or more statuses.', 'event-tickets' ),
			'publish',
		);

		$collection[] = new Array_Of_Type(
			'include',
			fn() => __( 'Limit result set to specific IDs.', 'event-tickets' ),
			Positive_Integer::class,
		);

		$collection[] = new Array_Of_Type(
			'exclude',
			fn() => __( 'Ensure result set excludes specific IDs.', 'event-tickets' ),
			Positive_Integer::class,
		);

		$collection[] = new Boolean(
			'show_hidden',
			fn() => __( 'Include tickets marked as hidden from view.', 'event-tickets' ),
			false,
		);

		return $collection;
	}

	/**
	 * @inheritDoc
	 */
	public function create_args(): QueryArgumentCollection {
		return new QueryArgumentCollection();
	}

	/**
	 * @inheritDoc
	 */
	public function create_schema(): OpenAPI_Schema {
		$collection = new RequestBodyCollection();

		$definition = new Ticket_Request_Body_Definition();
		$collection[] = new Definition_Parameter( $definition );

		$schema = new OpenAPI_Schema(
			fn() => __( 'Create a Ticket', 'event-tickets' ),
			fn() => __( 'Create a new ticket', 'event-tickets' ),
			'createTicket',
			[ tribe( Tickets_Tag::class ) ],
			null,
			null,
			$collection->set_description_provider( fn() => __( 'The ticket data to create.', 'event-tickets' ) )->set_required( true )->set_example( $definition->get_example() ),
			true
		);

		$response = new Definition_Parameter(
			new Ticket_Definition(),
			'ticket'
		);

		$schema->add_response(
			201,
			fn() => __( 'Returns the created ticket', 'event-tickets' ),
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

		return $schema;
	}
}
