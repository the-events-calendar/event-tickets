<?php
/**
 * Commits the ticket changes sent with a classic editor post save.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */

namespace TEC\Tickets\Deferred_Save;

use Tribe__Tickets__Main as Tickets_Main;
use WP_Post;

/**
 * Class Classic_Save.
 *
 * The classic editor sends the payload as form fields inside the post form. The `Controller` hooks
 * this on `save_post` for every ticketable post type, and it hands `tec_tickets` to `Commit`. It runs
 * after `Tribe__Tickets__Tickets_Handler::save_post()` (priority 10) so the ticket order the form saved
 * is what an update reads when it does not mention a menu order.
 *
 * It never runs on an autosave, a revision, a preview or during a REST request, never without its own
 * nonce, and only for the post the form's `post_ID` names, so a second ticketable post saved during the
 * same request never receives the payload. The post form's own nonce is bound to a post ID that ECP
 * rewrites on a split save, so it cannot be verified from here; `post_ID` is rewritten with it. It
 * commits a post at most once per request, however many times the post is saved during it.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */
final class Classic_Save {
	/**
	 * The nonce action.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const NONCE_ACTION = 'tec_tickets_deferred_save';

	/**
	 * The name of the form field carrying the nonce.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const NONCE_FIELD = 'tec_tickets_nonce';

	/**
	 * The name of the form field the classic editor writes after every staged field.
	 *
	 * PHP drops the request fields past `max_input_vars` in the order they arrive, so a payload without
	 * it lost some of its fields on the way and must not be applied.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const COMPLETE_FIELD = 'tec_tickets_complete';

	/**
	 * The priority on `save_post`: after the ticket order and settings save at 10.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const PRIORITY = 20;

	/**
	 * The handler that saves a payload.
	 *
	 * @since TBD
	 *
	 * @var Commit
	 */
	private Commit $commit;

	/**
	 * The posts committed during this request, by ID.
	 *
	 * @since TBD
	 *
	 * @var array<int,true>
	 */
	private array $committed = [];

	/**
	 * Classic_Save constructor.
	 *
	 * @since TBD
	 *
	 * @param Commit $commit The handler that saves a payload.
	 */
	public function __construct( Commit $commit ) {
		$this->commit = $commit;
	}

	/**
	 * Renders the hidden nonce field the classic editor prints inside the post form.
	 *
	 * @since TBD
	 *
	 * @return string The field's HTML.
	 */
	public static function nonce_field(): string {
		return wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD, false, false );
	}

	/**
	 * Refreshes the nonce field together with the post form's own nonces.
	 *
	 * Heartbeat refreshes the edit screen's nonces before they expire; the ones it refreshes are listed
	 * under `wp-refresh-post-nonces.replace`, by field ID, and core's script writes each into its field.
	 * Without this, a screen left open past the nonce lifetime would save the post and drop every staged
	 * ticket change. Core adds `replace` only for a user who may edit the post.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $response The heartbeat response.
	 *
	 * @return array<string,mixed> The response, with this nonce when core refreshed the post's.
	 */
	public function refresh_nonce( $response ): array {
		$response = (array) $response;

		if ( isset( $response['wp-refresh-post-nonces']['replace'] ) && is_array( $response['wp-refresh-post-nonces']['replace'] ) ) {
			$response['wp-refresh-post-nonces']['replace'][ self::NONCE_FIELD ] = wp_create_nonce( self::NONCE_ACTION );
		}

		return $response;
	}

	/**
	 * Commits the payload sent with the post form, when there is one.
	 *
	 * @since TBD
	 *
	 * @param int     $post_id The ID of the post being saved.
	 * @param WP_Post $post    The post being saved.
	 *
	 * @return Result|null The commit result, or `null` when nothing was committed.
	 */
	public function on_save_post( int $post_id, WP_Post $post ): ?Result {
		if (
			isset( $this->committed[ $post_id ] )
			|| wp_is_post_autosave( $post )
			|| wp_is_post_revision( $post )
			|| wp_is_rest_endpoint()
			|| ! in_array( $post->post_type, Tickets_Main::instance()->post_types(), true )
		) {
			return null;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- The nonce is verified right below.
		if ( empty( $_POST['tec_tickets'] ) || ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return null;
		}

		// Previewing a draft saves the draft itself, and the page keeps its staged changes for the real save.
		if ( isset( $_POST['wp-preview'] ) && 'dopreview' === $_POST['wp-preview'] ) {
			return null;
		}

		// The payload belongs to the post the form is for. Another ticketable post saved during this request (ECP saves a Series with its event) must not get it.
		if ( absint( $_POST['post_ID'] ?? 0 ) !== $post_id ) {
			return null;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return null;
		}

		// The payload is parsed by `Payload` and its ticket data is sanitized by the providers when they save it, as on the AJAX path.
		$raw      = wp_unslash( $_POST['tec_tickets'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$complete = isset( $_POST[ self::COMPLETE_FIELD ] );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// The post may be saved again during this save (TEC does it when "Sticky in Month View" changes): commit once.
		$this->committed[ $post_id ] = true;

		// A partial `update` would save a ticket with half its fields: apply nothing, and say so.
		$result = $complete
			? $this->commit->run( $raw, $post_id )
			: ( new Result() )->with_error( null, null, __( 'The ticket changes did not all reach the server, so none were saved. Stage fewer changes per save.', 'event-tickets' ) );

		/**
		 * Fires after the ticket changes sent with a classic editor post save were committed.
		 *
		 * @since TBD
		 *
		 * @param Result $result  The commit result: created ticket IDs by position and one error per failed entry.
		 * @param int    $post_id The ID of the post that was saved.
		 */
		do_action( 'tec_tickets_deferred_save_classic_committed', $result, $post_id );

		return $result;
	}
}
