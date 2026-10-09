<?php
/**
 * Saves the ticket changes sent with a post save.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */

namespace TEC\Tickets\Deferred_Save;

use TEC\Tickets\Deferred_Save\Payload\Malformed_Exception;
use TEC\Tickets\Deferred_Save\Payload\Parser;
use TEC\Tickets\Deferred_Save\Payload\Rejections;
use TEC\Tickets\Event;
use TEC\Tickets\Commerce\Module;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Main as Tickets_Main;
use Tribe__Tickets__Tickets as Tickets;

/**
 * Class Commit.
 *
 * The one handler that turns the raw `tec_tickets` value of a request into saved tickets. It parses
 * the payload, runs the checks against the post being saved, lets other code route the entries, and
 * replays each part through the functions Event Tickets uses for ticket writes today, so every hook
 * that fires on a ticket save or delete today still fires, in the same order.
 *
 * Parts run in the order `update`, `move`, `create`, `delete`; the parser refuses a ticket named in
 * more than one of them. One failing entry never stops the others.
 *
 * A write that happened is never reported as refused: when something that runs after it throws, the
 * entry's error is marked `applied`. A ticket a provider created but did not finish is reported with its
 * ID and an error that is not `applied`, so the editor saves it again as an update. A `create` entry may carry a key; a save that never got its answer
 * sends the entry again with the same key, and the ticket that key created is saved over, not created again.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */
final class Commit {
	/**
	 * The field of a `create` entry that names it across retries of the same save.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const CREATE_KEY = 'tec_tickets_create_key';

	/**
	 * The meta key that keeps, on a ticket a save created, the key its `create` entry carried.
	 *
	 * Public so that copying a ticket can leave it out: a copy is another ticket.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const CREATE_KEY_META = '_tec_tickets_deferred_save_create_key';

	/**
	 * The checks a payload passes before anything is saved.
	 *
	 * @since TBD
	 *
	 * @var Checks
	 */
	private Checks $checks;

	/**
	 * The parser that turns the raw request value into a payload.
	 *
	 * @since TBD
	 *
	 * @var Parser
	 */
	private Parser $parser;

	/**
	 * Commit constructor.
	 *
	 * @since TBD
	 *
	 * @param Checks $checks The checks a payload passes before anything is saved.
	 * @param Parser $parser The parser that turns the raw request value into a payload.
	 */
	public function __construct( Checks $checks, Parser $parser ) {
		$this->checks = $checks;
		$this->parser = $parser;
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
		// Write against the post the checks answer for: an occurrence's provisional ID becomes its post's, as on the AJAX path.
		$post_id = (int) Event::filter_event_id( $post_id, 'deferred_save' );

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

		// Counted before parsing, invalid entries included, so no payload makes the parser work through more than the cap.
		if ( $this->count_raw_entries( $raw ) > $max_entries ) {
			$too_many = sprintf(
				/* translators: %d: the maximum number of ticket changes in one save. */
				__( 'Too many ticket changes in one save; the limit is %d.', 'event-tickets' ),
				$max_entries
			);

			return new Result( [], ( new Rejections() )->with( null, null, $too_many )->all() );
		}

		try {
			$parsed = $this->parser->parse( $raw );
		} catch ( Malformed_Exception $e ) {
			// Input that cannot be a payload is answered like any other whole-payload refusal.
			return new Result( [], ( new Rejections() )->with( null, null, $e->getMessage() )->all() );
		}

		$payload = $parsed->payload();

		$checked    = $this->checks->run( $payload, $post_id );
		$rejections = $parsed->rejections()->merge( $checked->rejections() );
		$result     = new Result( [], $rejections->all() );
		$payload    = $checked->payload();

		if ( ! $payload->has_changes() ) {
			return $result;
		}

		/**
		 * Filters which post each part of a checked payload is applied to.
		 *
		 * By default the whole payload is applied to the post being saved. A callback may return any map of
		 * post ID to `Payload`, redirecting entries to another post or splitting them across posts; positions
		 * in `create` are kept, so the result still reports created IDs by the position the editor sent.
		 *
		 * The checks have already run against the post being saved. Any other payload, and any payload routed
		 * to another post, is checked again against the post it is routed to, so its user must be able to edit
		 * it and its `update`, `delete` and `move` entries must name tickets on it. Route keys must be post IDs
		 * as ints; a checked entry no route hands out is reported as not saved.
		 *
		 * @since TBD
		 *
		 * @param array<int,Payload> $routes  Post ID => the payload to apply to it.
		 * @param int                $post_id The ID of the post being saved.
		 * @param Payload            $payload The checked payload.
		 */
		$routes = apply_filters( 'tec_tickets_deferred_save_routes', [ $post_id => $payload ], $post_id, $payload );

		$checked_post_id = (int) Event::filter_event_id( $post_id, 'deferred_save' );
		$handled         = new Payload();

		foreach ( (array) $routes as $route_post_id => $route_payload ) {
			// A key that is not an int, or a payload whose keys are not, is not a route; its entries are reported below.
			if ( ! $route_payload instanceof Payload || ! is_int( $route_post_id ) || ! $this->has_int_keys( $route_payload ) ) {
				continue;
			}

			$route_post_id = (int) Event::filter_event_id( $route_post_id, 'deferred_save' );
			$handled       = $this->with_entries( $handled, $route_payload );

			// Only the payload the checks returned, applied to the post they ran against, skips a second check.
			if ( $route_payload !== $payload || $route_post_id !== $checked_post_id ) {
				$route         = $this->checks->run( $route_payload, $route_post_id );
				$route_payload = $route->payload();
				$result        = new Result( $result->get_created(), array_merge( $result->get_errors(), $route->rejections()->all() ) );
			}

			$result = $result->merge( $this->replay( $route_payload, $route_post_id ) );
		}

		return $this->with_unrouted( $result, $payload, $handled );
	}

	/**
	 * Whether every key of a payload is an int, as a parsed payload's are.
	 *
	 * @since TBD
	 *
	 * @param Payload $payload The payload a route returned.
	 *
	 * @return bool Whether every ticket ID and position is an int.
	 */
	private function has_int_keys( Payload $payload ): bool {
		$keys = array_merge(
			array_keys( $payload->get_update() ),
			array_keys( $payload->get_create() ),
			array_values( $payload->get_delete() ),
			array_keys( $payload->get_move() )
		);

		return [] === array_filter( $keys, static fn( $key ) => ! is_int( $key ) );
	}

	/**
	 * Adds a route's entries to the ones the routes have handed out so far.
	 *
	 * @since TBD
	 *
	 * @param Payload $handled The entries handed out so far.
	 * @param Payload $route   The payload of one route.
	 *
	 * @return Payload The entries handed out, this route's included.
	 */
	private function with_entries( Payload $handled, Payload $route ): Payload {
		return new Payload(
			$handled->get_update() + $route->get_update(),
			$handled->get_create() + $route->get_create(),
			array_merge( $handled->get_delete(), $route->get_delete() ),
			$handled->get_move() + $route->get_move()
		);
	}

	/**
	 * Reports every checked entry that no route handed out, so a route that drops entries is not a silent success.
	 *
	 * @since TBD
	 *
	 * @param Result  $result  The result so far.
	 * @param Payload $checked The checked payload.
	 * @param Payload $handled The entries the routes handed out.
	 *
	 * @return Result The result with one error per dropped entry.
	 */
	private function with_unrouted( Result $result, Payload $checked, Payload $handled ): Result {
		$message = __( 'No route applied this ticket change, so it was not saved.', 'event-tickets' );
		$dropped = [
			Parser::UPDATE => array_diff( array_keys( $checked->get_update() ), array_keys( $handled->get_update() ) ),
			Parser::CREATE => array_diff( array_keys( $checked->get_create() ), array_keys( $handled->get_create() ) ),
			Parser::DELETE => array_diff( $checked->get_delete(), $handled->get_delete() ),
			Parser::MOVE   => array_diff( array_keys( $checked->get_move() ), array_keys( $handled->get_move() ) ),
		];

		foreach ( $dropped as $part => $keys ) {
			foreach ( $keys as $key ) {
				$result = $result->with_error( $part, $key, $message );
			}
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
			$result = $this->guarded( $result, Parser::UPDATE, $ticket_id, fn( Result $r ) => $this->update( $r, $post_id, $ticket_id, $data ) );
		}

		foreach ( $payload->get_move() as $ticket_id => $destination_id ) {
			$result = $this->guarded( $result, Parser::MOVE, $ticket_id, fn( Result $r ) => $this->move( $r, $ticket_id, $destination_id ) );
		}

		foreach ( $payload->get_create() as $position => $data ) {
			$result = $this->guarded( $result, Parser::CREATE, $position, fn( Result $r ) => $this->create( $r, $post_id, $position, $data ) );
		}

		foreach ( $payload->get_delete() as $ticket_id ) {
			$result = $this->guarded( $result, Parser::DELETE, $ticket_id, fn( Result $r ) => $this->delete( $r, $post_id, $ticket_id ) );
		}

		return $result;
	}

	/**
	 * Runs one entry's save so that an exception inside a provider becomes that entry's error.
	 *
	 * The post save must finish and the other entries must still be applied whatever one provider
	 * does with one entry's data; the exception is logged for the developer, not shown to the editor.
	 *
	 * @since TBD
	 *
	 * @param Result                  $result The result so far.
	 * @param string                  $part   The part the entry belongs to.
	 * @param int                     $key    The entry's key.
	 * @param callable(Result):Result $step   The save step.
	 *
	 * @return Result The result with the step, or its failure, folded in.
	 */
	private function guarded( Result $result, string $part, int $key, callable $step ): Result {
		try {
			return $step( $result );
		} catch ( \Throwable $e ) {
			// `ticket_add()` turns the manual-update flag on around the save; the exception skipped turning it off.
			tribe( 'tickets.handler' )->toggle_manual_update_flag( false );
			$this->log_failure(
				'Deferred ticket save: an entry could not be saved.',
				$e,
				[
					'part' => $part,
					'key'  => $key,
				] 
			);

			return $result->with_error( $part, $key, $this->not_saved_message() );
		}
	}

	/**
	 * Saves an existing ticket through its own provider.
	 *
	 * An update replaces the ticket, so the entry must carry the whole ticket, as the editors send it: a price,
	 * capacity, description or date it leaves out is saved empty. Only the type and the menu order keep what the
	 * ticket has when the entry does not mention them, since `ticket_add()` would otherwise reset the type to
	 * `default` and, for Tickets Commerce, the menu order to 0.
	 *
	 * @since TBD
	 *
	 * @param Result              $result    The result so far.
	 * @param int                 $post_id   The post being saved.
	 * @param int                 $ticket_id The ticket to save.
	 * @param array<string,mixed> $data      The ticket data, as the editor sent it.
	 * @param string              $part      The part the editor sent the entry in.
	 * @param int|null            $key       The entry's key in that part; the ticket ID when `null`.
	 *
	 * @return Result The result with this entry folded in.
	 */
	private function update( Result $result, int $post_id, int $ticket_id, array $data, string $part = Parser::UPDATE, ?int $key = null ): Result {
		$key    ??= $ticket_id;
		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		if ( ! $provider instanceof Tickets ) {
			return $result->with_error( $part, $key, $this->no_provider_message() );
		}

		if ( $this->has_invalid_price( $provider, $data ) ) {
			return $result->with_error( $part, $key, $this->invalid_price_message() );
		}

		$data                = $this->sanitize( $data );
		$data['ticket_id']   = $ticket_id;
		$data['ticket_type'] = $this->ticket_type( $data, get_post_meta( $ticket_id, '_type', true ) ?: 'default' );

		if ( ! isset( $data['ticket_menu_order'] ) ) {
			$data['ticket_menu_order'] = (int) get_post_field( 'menu_order', $ticket_id );
		}

		$saved = $provider->ticket_add( $post_id, $data );

		if ( ! $saved ) {
			return $result->with_error( $part, $key, $this->not_saved_message() );
		}

		return $this->fire_added( $post_id, $ticket_id, $data )
			? $result
			: $result->with_error( $part, $key, $this->saved_listener_failed_message(), true );
	}

	/**
	 * Saves a `create` entry over the ticket an earlier try of the same save created, checked as the update it is.
	 *
	 * @since TBD
	 *
	 * @param Result              $result    The result so far.
	 * @param int                 $post_id   The post being saved.
	 * @param int                 $position  The position of the entry in the `create` part.
	 * @param int                 $ticket_id The ticket the entry's key created.
	 * @param array<string,mixed> $data      The ticket data, as the editor sent it.
	 *
	 * @return Result The result with this entry folded in, the ticket reported as created at its position.
	 */
	private function update_created( Result $result, int $post_id, int $position, int $ticket_id, array $data ): Result {
		// The ticket exists whatever happens next: the editor must know its ID, or it sends the create once more.
		$result  = $result->with_created( $position, $ticket_id );
		$checked = $this->checks->run( new Payload( [ $ticket_id => $data ] ), $post_id );

		if ( ! array_key_exists( $ticket_id, $checked->payload()->get_update() ) ) {
			$refusals = $checked->rejections()->all();

			return $result->with_error( Parser::CREATE, $position, $refusals[0]['message'] ?? $this->not_saved_message() );
		}

		// Guarded here, so that a listener throwing during the update does not lose the ID the result now holds.
		return $this->guarded( $result, Parser::CREATE, $position, fn( Result $r ) => $this->update( $r, $post_id, $ticket_id, $data, Parser::CREATE, $position ) );
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
	 *
	 * @throws \Throwable When the provider throws before the ticket is on the post; `guarded()` reports it.
	 */
	private function create( Result $result, int $post_id, int $position, array $data ): Result {
		$provider = empty( $data['ticket_provider'] ) || ! is_string( $data['ticket_provider'] )
			? false
			: Tickets::get_ticket_provider_instance( $data['ticket_provider'] );

		if ( ! $provider instanceof Tickets ) {
			return $result->with_error( Parser::CREATE, $position, $this->no_provider_message() );
		}

		if ( $this->has_invalid_price( $provider, $data ) ) {
			return $result->with_error( Parser::CREATE, $position, $this->invalid_price_message() );
		}

		$key = $this->create_key( $data );
		unset( $data[ self::CREATE_KEY ] );
		$created = '' === $key ? 0 : $this->find_created( $provider, $post_id, $key );

		if ( $created ) {
			// An earlier try of this save created the ticket and its answer never reached the editor.
			return $this->update_created( $result, $post_id, $position, $created, $data );
		}

		$data = $this->sanitize( $data );
		unset( $data['ticket_id'] );
		$data['ticket_type'] = $this->ticket_type( $data, 'default' );
		// What is on the post already, to tell the ticket this save adds if something throws once it is there.
		$before = $this->attached_ids( $provider, $post_id );

		try {
			$ticket_id = $provider->ticket_add( $post_id, $data );
		} catch ( \Throwable $e ) {
			// Whichever listener threw, and wherever the provider relates the ticket to the post, the ticket is there or not.
			$added = array_values( array_diff( $this->attached_ids( $provider, $post_id ), $before ) );

			if ( 1 !== count( $added ) ) {
				throw $e;
			}

			// The provider never returned, so what it wrote is not known: report the ticket, so the editor never
			// creates it again, and the entry as not saved, so the editor saves it again as an update of that ticket.
			tribe( 'tickets.handler' )->toggle_manual_update_flag( false );
			$this->log_failure( 'Deferred ticket save: a ticket was created, then its save failed before it finished.', $e, [ 'ticket_id' => $added[0] ] );
			$this->remember_key( $added[0], $key );

			return $result
				->with_created( $position, $added[0] )
				->with_error( Parser::CREATE, $position, __( 'The ticket was created, but not all of its settings were saved.', 'event-tickets' ) );
		}

		if ( empty( $ticket_id ) ) {
			return $result->with_error( Parser::CREATE, $position, $this->not_saved_message() );
		}

		$ticket_id = (int) $ticket_id;
		$this->remember_key( $ticket_id, $key );
		$result = $result->with_created( $position, $ticket_id );

		return $this->fire_added( $post_id, $ticket_id, $data )
			? $result
			: $result->with_error( Parser::CREATE, $position, $this->saved_listener_failed_message(), true );
	}

	/**
	 * Moves a ticket to another post through the function "Move ticket type" uses today.
	 *
	 * The checks have already required the ticket to be on the post being saved and the destination to be
	 * a post the user can edit. The function fires the actions that move the attendees and the stock along.
	 *
	 * @since TBD
	 *
	 * @param Result $result         The result so far.
	 * @param int    $ticket_id      The ticket to move.
	 * @param int    $destination_id The post to move it to.
	 *
	 * @return Result The result with this entry folded in.
	 *
	 * @throws \Throwable When the move throws before the ticket is on the destination; `guarded()` reports it.
	 */
	private function move( Result $result, int $ticket_id, int $destination_id ): Result {
		try {
			$moved = Tickets_Main::instance()->move_ticket_types()->move_ticket_type( $ticket_id, $destination_id );
		} catch ( \Throwable $e ) {
			// A listener on the moved action threw after the ticket was reassigned: the move happened, not all of it.
			if ( ! $this->is_on_post( $ticket_id, $destination_id ) ) {
				throw $e;
			}

			$this->log_failure( 'Deferred ticket save: a ticket was moved, then a listener failed.', $e, [ 'ticket_id' => $ticket_id ] );

			// The attendees are moved by a listener of that action, so they may still be here; the admin can move them from the attendee list.
			return $result->with_error(
				Parser::MOVE,
				$ticket_id,
				__( 'The ticket moved, but something that runs after a move failed, so its attendees may not have moved with it. Check the attendee list of this post.', 'event-tickets' ),
				true
			);
		}

		if ( ! $moved ) {
			return $result->with_error(
				Parser::MOVE,
				$ticket_id,
				sprintf(
					/* translators: %1$d: the ticket ID, %2$d: the destination post ID. */
					__( 'Ticket %1$d could not be moved to post %2$d.', 'event-tickets' ),
					$ticket_id,
					$destination_id
				)
			);
		}

		return $result;
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
	 *
	 * @throws \Throwable When the provider throws before the ticket is deleted; `guarded()` reports it.
	 */
	private function delete( Result $result, int $post_id, int $ticket_id ): Result {
		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		if ( ! $provider instanceof Tickets ) {
			return $result->with_error( Parser::DELETE, $ticket_id, $this->no_provider_message() );
		}

		$finished = true;

		try {
			$deleted = $provider->delete_ticket( $post_id, $ticket_id );
		} catch ( \Throwable $e ) {
			// A listener threw after the ticket was deleted: the delete happened, not all of it.
			if ( get_post( $ticket_id ) instanceof \WP_Post && 'trash' !== get_post_status( $ticket_id ) ) {
				throw $e;
			}

			$this->log_failure( 'Deferred ticket save: a ticket was deleted, then a listener failed.', $e, [ 'ticket_id' => $ticket_id ] );
			$deleted  = true;
			$finished = false;
		}

		if ( ! $deleted ) {
			return $result->with_error(
				Parser::DELETE,
				$ticket_id,
				sprintf(
					/* translators: %d: the ticket ID. */
					__( 'Ticket %d could not be deleted.', 'event-tickets' ),
					$ticket_id
				)
			);
		}

		try {
			/** This action is documented in src/Tribe/Metabox.php */
			do_action( 'tribe_tickets_ticket_deleted', $post_id );
		} catch ( \Throwable $e ) {
			// The ticket is deleted: reporting it as not deleted would be untrue.
			$this->log_failure( 'Deferred ticket save: a listener failed after a ticket was deleted.', $e, [ 'ticket_id' => $ticket_id ] );
			$finished = false;
		}

		return $finished
			? $result
			: $result->with_error( Parser::DELETE, $ticket_id, __( 'The ticket was deleted, but something that runs after a ticket is deleted failed.', 'event-tickets' ), true );
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
	 * @return bool Whether every listener finished.
	 */
	private function fire_added( int $post_id, int $ticket_id, array $data ): bool {
		try {
			/** This action is documented in src/Tribe/Metabox.php */
			do_action( 'tribe_tickets_ticket_added', $post_id, $ticket_id, $data );
		} catch ( \Throwable $e ) {
			// The ticket is saved: reporting it as not saved would make the editor create it again.
			$this->log_failure( 'Deferred ticket save: a listener failed after a ticket was saved.', $e, [ 'ticket_id' => $ticket_id ] );

			return false;
		}

		return true;
	}

	/**
	 * The IDs of a provider's tickets on a post, read from where the provider records the relation.
	 *
	 * @since TBD
	 *
	 * @param Tickets                         $provider   The provider.
	 * @param int                             $post_id    The post.
	 * @param array<int,array<string,string>> $meta_query More meta conditions the tickets must meet.
	 *
	 * @return int[] The ticket IDs.
	 */
	private function attached_ids( Tickets $provider, int $post_id, array $meta_query = [] ): array {
		$ids = get_posts(
			[
				'post_type'      => $provider->ticket_object,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'meta_query'     => array_merge( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- The relation is only in meta.
					[
						[
							'key'   => $provider->get_event_key(),
							'value' => (string) $post_id,
						],
					],
					$meta_query
				),
			]
		);

		return array_map( 'intval', $ids );
	}

	/**
	 * The ticket on a post that a `create` entry with this key created, if any.
	 *
	 * @since TBD
	 *
	 * @param Tickets $provider The provider the entry names.
	 * @param int     $post_id  The post being saved.
	 * @param string  $key      The entry's key.
	 *
	 * @return int The ticket ID, 0 when there is none.
	 */
	private function find_created( Tickets $provider, int $post_id, string $key ): int {
		$ids = $this->attached_ids(
			$provider,
			$post_id,
			[
				[
					'key'   => self::CREATE_KEY_META,
					'value' => $key,
				],
			]
		);

		return $ids[0] ?? 0;
	}

	/**
	 * Reads a `create` entry's key; anything that is not a short token of letters, digits and dashes is no key.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The entry's data.
	 *
	 * @return string The key, empty when there is none.
	 */
	private function create_key( array $data ): string {
		$key = $data[ self::CREATE_KEY ] ?? '';

		return is_string( $key ) && preg_match( '/^[A-Za-z0-9-]{8,64}$/', $key ) ? $key : '';
	}

	/**
	 * Keeps a `create` entry's key on the ticket it created.
	 *
	 * @since TBD
	 *
	 * @param int    $ticket_id The created ticket.
	 * @param string $key       The entry's key, empty when it carried none.
	 *
	 * @return void
	 */
	private function remember_key( int $ticket_id, string $key ): void {
		if ( '' !== $key ) {
			update_post_meta( $ticket_id, self::CREATE_KEY_META, $key );
		}
	}

	/**
	 * Whether a ticket is attached to a post.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket.
	 * @param int $post_id   The post.
	 *
	 * @return bool Whether the ticket's provider records it on the post.
	 */
	private function is_on_post( int $ticket_id, int $post_id ): bool {
		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		return $provider instanceof Tickets && (int) get_post_meta( $ticket_id, $provider->get_event_key(), true ) === $post_id;
	}

	/**
	 * Logs an exception caught while committing, for the developer; the editor is told in the result.
	 *
	 * @since TBD
	 *
	 * @param string              $what    What happened.
	 * @param \Throwable          $e       The exception.
	 * @param array<string,mixed> $context What it happened to.
	 *
	 * @return void
	 */
	private function log_failure( string $what, \Throwable $e, array $context ): void {
		do_action(
			'tribe_log',
			'error',
			$what,
			array_merge(
				[ 'source' => __CLASS__ ],
				$context,
				[
					'exception' => get_class( $e ),
					'message'   => $e->getMessage(),
				]
			)
		);
	}

	/**
	 * Counts the entries of the known parts of a raw payload, valid or not.
	 *
	 * @since TBD
	 *
	 * @param mixed $raw The raw `tec_tickets` value of the request.
	 *
	 * @return int The number of entries.
	 */
	private function count_raw_entries( $raw ): int {
		if ( ! is_array( $raw ) ) {
			return 0;
		}

		$entries = 0;

		foreach ( [ Parser::UPDATE, Parser::CREATE, Parser::DELETE, Parser::MOVE ] as $part ) {
			$entries += isset( $raw[ $part ] ) && is_array( $raw[ $part ] ) ? count( $raw[ $part ] ) : 0;
		}

		return $entries;
	}

	/**
	 * Sanitizes an entry's data the way the request reaches the AJAX save today.
	 *
	 * The AJAX handler reads `data` through `tribe_get_request_var()`, which runs `tribe_sanitize_deep()`
	 * over the whole array before `ticket_add()` sees it. The providers rely on that, so the deferred path
	 * runs the same sanitizer over the same array: what a ticket stores does not depend on the path it took.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The ticket data, as the editor sent it.
	 *
	 * @return array<string,mixed> The sanitized data.
	 */
	private function sanitize( array $data ): array {
		tribe_sanitize_deep( $data );

		return is_array( $data ) ? $data : [];
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
	 * Whether the entry carries a price the block editor's ticket endpoint would refuse.
	 *
	 * Tickets Commerce and PayPal refuse a negative or non-numeric price there; a blank price is a free ticket.
	 *
	 * @since TBD
	 *
	 * @param Tickets             $provider The provider the ticket is saved through.
	 * @param array<string,mixed> $data     The ticket data.
	 *
	 * @return bool Whether the price is invalid.
	 */
	private function has_invalid_price( Tickets $provider, array $data ): bool {
		if ( ! array_key_exists( 'ticket_price', $data ) ) {
			return false;
		}

		if ( ! $provider instanceof Module && ! $provider instanceof PayPal ) {
			return false;
		}

		if ( ! is_scalar( $data['ticket_price'] ) ) {
			return true;
		}

		$price = trim( (string) $data['ticket_price'] );

		return '' !== $price && ( ! is_numeric( $price ) || (float) $price < 0 );
	}

	/**
	 * The message for an entry with an invalid price.
	 *
	 * @since TBD
	 *
	 * @return string The message.
	 */
	private function invalid_price_message(): string {
		return __( 'Invalid price', 'event-tickets' );
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
	 * The message for a ticket saved before something that runs after the save failed.
	 *
	 * @since TBD
	 *
	 * @return string The message.
	 */
	private function saved_listener_failed_message(): string {
		return __( 'The ticket was saved, but something that runs after a ticket is saved failed.', 'event-tickets' );
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
