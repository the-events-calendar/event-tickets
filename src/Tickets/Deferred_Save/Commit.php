<?php
/**
 * Saves the ticket changes sent with a post save.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */

namespace TEC\Tickets\Deferred_Save;

use Tribe__Tickets__Tickets as Tickets;

/**
 * Class Commit.
 *
 * The one handler that turns the raw `tec_tickets` value of a request into saved tickets. It parses
 * the payload, runs the checks against the post being saved, lets other code route the entries, and
 * replays each part through the functions Event Tickets uses for ticket writes today, so every hook
 * that fires on a ticket save or delete today still fires, in the same order.
 *
 * Parts run in the order `update`, `create`, `delete`. One failing entry never stops the others.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */
class Commit {
	/**
	 * The checks a payload passes before anything is saved.
	 *
	 * @since TBD
	 *
	 * @var Checks
	 */
	private Checks $checks;

	/**
	 * Commit constructor.
	 *
	 * @since TBD
	 *
	 * @param Checks $checks The checks a payload passes before anything is saved.
	 */
	public function __construct( Checks $checks ) {
		$this->checks = $checks;
	}

	/**
	 * Parses, checks, routes and saves the ticket changes for a post.
	 *
	 * @since TBD
	 *
	 * @param mixed $raw     The raw `tec_tickets` value of the request. `null` means "no ticket changes".
	 * @param int   $post_id The ID of the post being saved.
	 *
	 * @return Result The created ticket IDs by position and one error per entry that did not go through.
	 */
	public function run( $raw, int $post_id ): Result {
		$payload = Payload::from_array( $raw );

		if ( ! $payload->is_valid() ) {
			return new Result( [], $payload->get_errors() );
		}

		$entries = count( $payload->get_update() ) + count( $payload->get_create() ) + count( $payload->get_delete() ) + count( $payload->get_move() );

		/**
		 * Filters how many entries one payload may carry.
		 *
		 * Every entry is at least one post write plus every listener on the ticket save actions, so a
		 * payload is capped to keep one save from queueing thousands of writes.
		 *
		 * @since TBD
		 *
		 * @param int $max_entries The maximum number of entries across all parts. Default 100.
		 * @param int $post_id     The ID of the post being saved.
		 */
		$max_entries = (int) apply_filters( 'tec_tickets_deferred_save_max_entries', 100, $post_id );

		if ( $entries > $max_entries ) {
			return new Result(
				[],
				[
					[
						'part'    => null,
						'key'     => null,
						'message' => sprintf(
							/* translators: %d: the maximum number of ticket changes in one save. */
							__( 'Too many ticket changes in one save; the limit is %d.', 'event-tickets' ),
							$max_entries
						),
					],
				]
			);
		}

		$checked = $this->checks->run( $payload, $post_id );
		$result  = new Result( [], $checked->get_errors() );

		if ( ! $checked->has_changes() ) {
			return $result;
		}

		/**
		 * Filters which post each part of a checked payload is applied to.
		 *
		 * By default the whole payload is applied to the post being saved. A callback may return any map of
		 * post ID to `Payload`, redirecting entries to another post or splitting them across posts; positions
		 * in `create` are kept, so the result still reports created IDs by the position the editor sent.
		 *
		 * The checks have already run against the post being saved. A payload routed to any other post is
		 * checked again against that post, so its user must be able to edit it and its `update`, `delete`
		 * and `move` entries must name tickets on it.
		 *
		 * @since TBD
		 *
		 * @param array<int,Payload> $routes  Post ID => the payload to apply to it.
		 * @param int                $post_id The ID of the post being saved.
		 * @param Payload            $payload The checked payload.
		 */
		$routes = apply_filters( 'tec_tickets_deferred_save_routes', [ $post_id => $checked ], $post_id, $checked );

		foreach ( (array) $routes as $route_post_id => $route_payload ) {
			if ( ! $route_payload instanceof Payload || ! is_numeric( $route_post_id ) ) {
				continue;
			}

			$route_post_id = (int) $route_post_id;

			if ( $route_post_id !== $post_id ) {
				$route_payload = $this->checks->run( $route_payload, $route_post_id );
				$result        = new Result( $result->get_created(), array_merge( $result->get_errors(), $route_payload->get_errors() ) );
			}

			$result = $result->merge( $this->replay( $route_payload, $route_post_id ) );
		}

		return $result;
	}

	/**
	 * Saves the entries of a checked payload on a post.
	 *
	 * @since TBD
	 *
	 * @param Payload $payload The checked payload.
	 * @param int     $post_id The post to apply it to.
	 *
	 * @return Result The outcome for this post.
	 */
	private function replay( Payload $payload, int $post_id ): Result {
		$result = new Result();

		foreach ( $payload->get_update() as $ticket_id => $data ) {
			$result = $this->update( $result, $post_id, $ticket_id, $data );
		}

		foreach ( $payload->get_create() as $position => $data ) {
			$result = $this->create( $result, $post_id, $position, $data );
		}

		foreach ( $payload->get_delete() as $ticket_id ) {
			$result = $this->delete( $result, $post_id, $ticket_id );
		}

		return $result;
	}

	/**
	 * Saves an existing ticket through its own provider.
	 *
	 * Values the entry does not mention keep what the ticket has: `ticket_add()` would otherwise reset
	 * the type to `default` and, for Tickets Commerce, the menu order to 0.
	 *
	 * @since TBD
	 *
	 * @param Result              $result    The result so far.
	 * @param int                 $post_id   The post being saved.
	 * @param int                 $ticket_id The ticket to save.
	 * @param array<string,mixed> $data      The ticket data, as the editor sent it.
	 *
	 * @return Result The result with this entry folded in.
	 */
	private function update( Result $result, int $post_id, int $ticket_id, array $data ): Result {
		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		if ( ! $provider instanceof Tickets ) {
			return $result->with_error( Payload::UPDATE, $ticket_id, $this->no_provider_message() );
		}

		$data['ticket_id']   = $ticket_id;
		$data['ticket_type'] = $this->ticket_type( $data, get_post_meta( $ticket_id, '_type', true ) ?: 'default' );

		if ( ! isset( $data['ticket_menu_order'] ) ) {
			$data['ticket_menu_order'] = (int) get_post_field( 'menu_order', $ticket_id );
		}

		$saved = $provider->ticket_add( $post_id, $data );

		if ( ! $saved ) {
			return $result->with_error( Payload::UPDATE, $ticket_id, $this->not_saved_message() );
		}

		$this->fire_added( $post_id, $ticket_id, $data );

		return $result;
	}

	/**
	 * Creates a ticket through the provider the entry names.
	 *
	 * @since TBD
	 *
	 * @param Result              $result   The result so far.
	 * @param int                 $post_id  The post being saved.
	 * @param int                 $position The position of the entry in the `create` part.
	 * @param array<string,mixed> $data     The ticket data, as the editor sent it.
	 *
	 * @return Result The result with this entry folded in.
	 */
	private function create( Result $result, int $post_id, int $position, array $data ): Result {
		$provider = empty( $data['ticket_provider'] ) || ! is_string( $data['ticket_provider'] )
			? false
			: Tickets::get_ticket_provider_instance( $data['ticket_provider'] );

		if ( ! $provider instanceof Tickets ) {
			return $result->with_error( Payload::CREATE, $position, $this->no_provider_message() );
		}

		unset( $data['ticket_id'] );
		$data['ticket_type'] = $this->ticket_type( $data, 'default' );

		$ticket_id = $provider->ticket_add( $post_id, $data );

		if ( empty( $ticket_id ) ) {
			return $result->with_error( Payload::CREATE, $position, $this->not_saved_message() );
		}

		$this->fire_added( $post_id, (int) $ticket_id, $data );

		return $result->with_created( $position, (int) $ticket_id );
	}

	/**
	 * Deletes a ticket through its own provider.
	 *
	 * @since TBD
	 *
	 * @param Result $result    The result so far.
	 * @param int    $post_id   The post being saved.
	 * @param int    $ticket_id The ticket to delete.
	 *
	 * @return Result The result with this entry folded in.
	 */
	private function delete( Result $result, int $post_id, int $ticket_id ): Result {
		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		if ( ! $provider instanceof Tickets ) {
			return $result->with_error( Payload::DELETE, $ticket_id, $this->no_provider_message() );
		}

		if ( ! $provider->delete_ticket( $post_id, $ticket_id ) ) {
			return $result->with_error(
				Payload::DELETE,
				$ticket_id,
				sprintf(
					/* translators: %d: the ticket ID. */
					__( 'Ticket %d could not be deleted.', 'event-tickets' ),
					$ticket_id
				)
			);
		}

		/** This action is documented in src/Tribe/Metabox.php */
		do_action( 'tribe_tickets_ticket_deleted', $post_id );

		return $result;
	}

	/**
	 * Fires the action the classic and REST callers fire after a ticket is saved.
	 *
	 * @since TBD
	 *
	 * @param int                 $post_id   The post the ticket is on.
	 * @param int                 $ticket_id The saved ticket.
	 * @param array<string,mixed> $data      The data it was saved with.
	 *
	 * @return void
	 */
	private function fire_added( int $post_id, int $ticket_id, array $data ): void {
		/** This action is documented in src/Tribe/Metabox.php */
		do_action( 'tribe_tickets_ticket_added', $post_id, $ticket_id, $data );
	}

	/**
	 * Sanitizes the ticket type the entry names, as the classic AJAX save does, falling back when it names none.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data     The ticket data.
	 * @param string              $fallback The type to use when the data names none.
	 *
	 * @return string The ticket type.
	 */
	private function ticket_type( array $data, string $fallback ): string {
		$type = $data['ticket_type'] ?? '';
		$type = is_scalar( $type ) ? sanitize_text_field( (string) $type ) : '';

		return '' !== $type ? $type : $fallback;
	}

	/**
	 * The message for an entry whose provider cannot be resolved.
	 *
	 * @since TBD
	 *
	 * @return string The message.
	 */
	private function no_provider_message(): string {
		return __( 'The ticket provider is missing or not active.', 'event-tickets' );
	}

	/**
	 * The message for an entry the provider refused to save.
	 *
	 * @since TBD
	 *
	 * @return string The message.
	 */
	private function not_saved_message(): string {
		return __( 'The ticket could not be saved.', 'event-tickets' );
	}
}
