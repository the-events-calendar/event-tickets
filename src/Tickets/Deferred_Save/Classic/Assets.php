<?php
/**
 * The classic editor's staging script and styles.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Classic
 */

namespace TEC\Tickets\Deferred_Save\Classic;

use Tribe__Tickets__Main as Tickets_Main;

/**
 * Class Assets.
 *
 * Registers the staging module and its styles in the admin group the tickets metabox uses, and
 * enqueues them only on the edit screen of a ticketable post. The feature Controller calls
 * `register()` and `unregister()`.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Classic
 */
final class Assets {
	/**
	 * The script handle.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const SCRIPT = 'tec-tickets-deferred-save';

	/**
	 * The style handle.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const STYLE = 'tec-tickets-deferred-save-style';

	/**
	 * Registers the assets.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register(): void {
		$main = Tickets_Main::instance();

		tec_asset(
			$main,
			self::SCRIPT,
			'deferred-save.js',
			[ 'jquery', 'wp-hooks', 'event-tickets-admin-js' ],
			'admin_enqueue_scripts',
			[
				'groups'       => 'event-tickets-admin',
				'conditionals' => [ $this, 'should_enqueue' ],
				'in_footer'    => true,
				'localize'     => [
					'name' => 'tecTicketsDeferredSave',
					'data' => [ $this, 'localized_data' ],
				],
			]
		);

		tec_asset(
			$main,
			self::STYLE,
			'deferred-save.css',
			[ 'event-tickets-admin-css' ],
			'admin_enqueue_scripts',
			[
				'groups'       => 'event-tickets-admin',
				'conditionals' => [ $this, 'should_enqueue' ],
			]
		);
	}

	/**
	 * The strings and limits the staging module reads.
	 *
	 * @since TBD
	 *
	 * @return array<string,mixed> The data, localized as `tecTicketsDeferredSave`.
	 */
	public function localized_data(): array {
		return [
			'free'            => __( 'Free', 'event-tickets' ),
			'unlimited'       => __( 'Unlimited', 'event-tickets' ),
			'leaveMessage'    => __( 'You have ticket changes that are not saved yet. Leave without saving them?', 'event-tickets' ),
			// PHP drops the fields past this count without a word; the module refuses a submit that would pass it.
			'maxInputVars'    => (int) ini_get( 'max_input_vars' ),
			'inputLimit'      => __( 'There are too many ticket changes to save with the post at once. Save the post with fewer of them staged, then stage the rest.', 'event-tickets' ),
			'moveBlocked'     => __( 'This ticket has changes waiting for the post save. Save the post before moving it.', 'event-tickets' ),
			'editBlocked'     => __( 'This ticket moves when the post is saved. Undo the move to edit it.', 'event-tickets' ),
			'deleteConfirm'   => __( 'Delete this ticket when the post is saved? You can undo this until then.', 'event-tickets' ),
			// The same string the provider's `duplicate_ticket()` adds to a copy's name.
			'copySuffix'      => __( '(copy)', 'event-tickets' ),
			'dateFormat'      => (int) \Tribe__Date_Utils::get_datepicker_format_index(),
			'duplicateFailed' => __( 'The ticket could not be copied. Reload the page and try again.', 'event-tickets' ),
			'invalidHeading'  => __( 'Some staged tickets are not valid. Fix them before saving the post.', 'event-tickets' ),
			'moveStaged'      => __( 'The move is staged and happens when you save the post. You may now close this window.', 'event-tickets' ),
			'rules'           => [
				'name'        => __( 'a name is required', 'event-tickets' ),
				'price'       => __( 'the price must be a non-negative number', 'event-tickets' ),
				'sale_price'  => __( 'the sale price must be a number below the price', 'event-tickets' ),
				'sale_window' => __( 'the sale window must start before it ends', 'event-tickets' ),
				'capacity'    => __( 'the capacity cannot be below the tickets already sold', 'event-tickets' ),
			],
		];
	}

	/**
	 * Unregisters the assets.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		wp_dequeue_script( self::SCRIPT );
		wp_deregister_script( self::SCRIPT );
		wp_dequeue_style( self::STYLE );
		wp_deregister_style( self::STYLE );
	}

	/**
	 * Whether the current admin screen edits a ticketable post.
	 *
	 * @since TBD
	 *
	 * @return bool Whether to enqueue.
	 */
	public function should_enqueue(): bool {
		$post_id = (int) get_the_ID();

		if ( ! $post_id && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();

			if ( ! $screen || 'post' !== $screen->base ) {
				return false;
			}
		}

		return $post_id > 0 && in_array( get_post_type( $post_id ), Tickets_Main::instance()->post_types(), true );
	}
}
