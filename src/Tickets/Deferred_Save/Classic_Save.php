<?php
/**
 * Commits the ticket changes sent with a classic editor post save.
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

/**
 * Class Classic_Save.
 *
 * The classic editor sends the payload as form fields inside the post form. This hooks the save of
 * every ticketable post type and, for a post that uses deferred save, hands `tec_tickets` to
 * `Commit`. It runs after `Tribe__Tickets__Tickets_Handler::save_post()` (priority 10) so the ticket
 * order the form saved is what an update reads when it does not mention a menu order.
 *
 * It never runs on an autosave, a revision or during a REST request, never without its own nonce,
 * and only for the post the form's `post_ID` names, so a second ticketable post saved during the same
 * request never receives the payload. The post form's own nonce is bound to a post ID that ECP
 * rewrites on a split save, so it cannot be verified from here; `post_ID` is rewritten with it.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */
class Classic_Save extends Controller_Contract {
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
	 * Classic_Save constructor.
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
	 * Hooks the post save.
	 *
	 * The generic `save_post` is used, with the post type checked when it fires, so a post type made
	 * ticketable after this registered is still covered.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'save_post', [ $this, 'on_save_post' ], self::PRIORITY, 2 );
	}

	/**
	 * Unhooks the post save.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'save_post', [ $this, 'on_save_post' ], self::PRIORITY );
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
	 * Commits the payload sent with the post form, when there is one and the post uses deferred save.
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
			wp_is_post_autosave( $post )
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

		// The payload belongs to the post the form is for. Another ticketable post saved during this request (ECP saves a Series with its event) must not get it.
		if ( absint( $_POST['post_ID'] ?? 0 ) !== $post_id ) {
			return null;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return null;
		}

		if ( ! $this->container->get( Controller::class )->uses_deferred_save( $post_id ) ) {
			return null;
		}

		// The payload is parsed by `Payload` and its ticket data is sanitized by the providers when they save it, as on the AJAX path.
		$raw = wp_unslash( $_POST['tec_tickets'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$result = $this->commit->run( $raw, $post_id );

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
