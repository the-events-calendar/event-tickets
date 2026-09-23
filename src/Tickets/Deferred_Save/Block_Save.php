<?php
/**
 * Commits the ticket changes sent with a block editor post save.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */

namespace TEC\Tickets\Deferred_Save;

use TEC\Common\Contracts\Container;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use Tribe__Tickets__Main as Tickets_Main;
use WP_Post;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class Block_Save.
 *
 * The block editor adds `tec_tickets` to the REST request that saves the post. This hooks the
 * `rest_after_insert_{type}` action of every ticketable post type, hands the payload to `Commit`
 * once the post and its meta are written, and adds the result to the response under `tec_tickets`
 * so the editor can match new tickets to their IDs and show errors on rejected ones.
 *
 * Priority 200 runs after ECP's Custom Tables v1 commits a recurring event's occurrences at 100, so
 * callbacks on the routing filter see the post after a split. REST autosaves never fire
 * `rest_after_insert_*`; the check on the request is there so that stays true whatever core does.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */
class Block_Save extends Controller_Contract {
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
	 * The results committed during this request, by post ID, waiting to be added to the response.
	 *
	 * @since TBD
	 *
	 * @var array<int,Result>
	 */
	private array $results = [];

	/**
	 * Block_Save constructor.
	 *
	 * @since TBD
	 *
	 * @param Container $container The container.
	 * @param Commit    $commit    The handler that saves a payload.
	 */
	public function __construct( Container $container, Commit $commit ) {
		parent::__construct( $container );
		$this->commit = $commit;
	}

	/**
	 * Hooks the REST save and response of every ticketable post type.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		foreach ( Tickets_Main::instance()->post_types() as $post_type ) {
			add_action( "rest_after_insert_{$post_type}", [ $this, 'on_rest_after_insert' ], self::PRIORITY, 3 );
			add_filter( "rest_prepare_{$post_type}", [ $this, 'add_result_to_response' ], 10, 3 );
		}
	}

	/**
	 * Unhooks the REST save and response.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		foreach ( Tickets_Main::instance()->post_types() as $post_type ) {
			remove_action( "rest_after_insert_{$post_type}", [ $this, 'on_rest_after_insert' ], self::PRIORITY );
			remove_filter( "rest_prepare_{$post_type}", [ $this, 'add_result_to_response' ], 10 );
		}
	}

	/**
	 * Commits the payload sent with the REST save, when there is one and the post uses deferred save.
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

		if ( ! $this->container->get( Controller::class )->uses_deferred_save( $post->ID ) ) {
			return null;
		}

		$result                     = $this->commit->run( $raw, $post->ID );
		$this->results[ $post->ID ] = $result;

		return $result;
	}

	/**
	 * Adds the commit result to the response of the save that produced it.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Response $response The response.
	 * @param WP_Post          $post     The post.
	 * @param WP_REST_Request  $request  The request.
	 *
	 * @return WP_REST_Response The response, with `tec_tickets` when this request committed a payload for the post.
	 */
	public function add_result_to_response( WP_REST_Response $response, WP_Post $post, WP_REST_Request $request ): WP_REST_Response {
		unset( $request );

		if ( ! isset( $this->results[ $post->ID ] ) ) {
			return $response;
		}

		$data                = $response->get_data();
		$data['tec_tickets'] = $this->results[ $post->ID ]->to_array();
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
