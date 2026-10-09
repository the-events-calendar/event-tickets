<?php
/**
 * Commits the ticket changes sent with a block editor post save.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */

namespace TEC\Tickets\Deferred_Save;

use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class Block_Save.
 *
 * The block editor adds `tec_tickets` to the REST request that saves the post. The feature
 * Controller hooks this on `rest_after_insert_{type}` of every ticketable post type: it hands the
 * payload to `Commit` once the post and its meta are written, and adds the result to the response
 * under `tec_tickets` so the editor can match new tickets to their IDs and show errors on rejected
 * ones.
 *
 * Priority 200 runs after ECP's Custom Tables v1 commits a recurring event's occurrences at 100, so
 * callbacks on the routing filter see the post after a split. REST autosaves never fire
 * `rest_after_insert_*`; the check on the request is there so that stays true whatever core does.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */
final class Block_Save {
	/**
	 * The priority on `rest_after_insert_{type}`: after the Custom Tables v1 occurrence commit at 100.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const PRIORITY = 200;

	/**
	 * The handler that saves a payload.
	 *
	 * @since TBD
	 *
	 * @var Commit
	 */
	private Commit $commit;

	/**
	 * The results committed during this request, by post ID, each with the REST request that committed it,
	 * waiting to be added to that request's response. A batch saves several requests in one PHP request.
	 *
	 * @since TBD
	 *
	 * @var array<int,array{0:WP_REST_Request,1:Result}>
	 */
	private array $results = [];

	/**
	 * Block_Save constructor.
	 *
	 * @since TBD
	 *
	 * @param Commit $commit The handler that saves a payload.
	 */
	public function __construct( Commit $commit ) {
		$this->commit = $commit;
	}

	/**
	 * Commits the payload sent with the REST save, when there is one.
	 *
	 * @since TBD
	 *
	 * @param WP_Post         $post     The saved post.
	 * @param WP_REST_Request $request  The request that saved it.
	 * @param bool            $creating Whether the post was created by this request.
	 *
	 * @return Result|null The commit result, or `null` when nothing was committed.
	 */
	public function on_rest_after_insert( WP_Post $post, WP_REST_Request $request, bool $creating ): ?Result {
		unset( $creating );

		$raw = $request->get_param( 'tec_tickets' );

		if ( null === $raw || $this->is_autosave( $request ) ) {
			return null;
		}

		$result                     = $this->commit->run( $raw, $post->ID );
		$this->results[ $post->ID ] = [ $request, $result ];

		return $result;
	}

	/**
	 * Adds the commit result to the response of the save that produced it.
	 *
	 * @since TBD
	 *
	 * The filter runs on every read of every ticketable post type, so whatever an earlier callback hands on
	 * that is not a response is passed on untouched.
	 *
	 * @param WP_REST_Response|mixed $response The response.
	 * @param WP_Post|mixed          $post     The post.
	 * @param WP_REST_Request|mixed  $request  The request.
	 *
	 * @return WP_REST_Response|mixed The response, with `tec_tickets` when this request committed a payload for the post.
	 */
	public function add_result_to_response( $response, $post, $request ) {
		if ( ! $response instanceof WP_REST_Response || ! $post instanceof WP_Post || ! isset( $this->results[ $post->ID ] ) ) {
			return $response;
		}

		[ $committed_by, $result ] = $this->results[ $post->ID ];

		if ( $committed_by !== $request ) {
			return $response;
		}

		unset( $this->results[ $post->ID ] );

		$data = $response->get_data();
		// The id tells two equal answers apart: the editor's store keeps the old object when a new one is deep-equal.
		$data['tec_tickets'] = $result->to_array() + [ 'id' => wp_generate_uuid4() ];
		$response->set_data( $data );

		return $response;
	}

	/**
	 * Whether a request is a REST autosave.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return bool Whether the request targets an autosaves route.
	 */
	private function is_autosave( WP_REST_Request $request ): bool {
		return false !== strpos( (string) $request->get_route(), '/autosaves' );
	}
}
