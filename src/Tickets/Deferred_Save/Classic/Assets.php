<?php
/**
 * The classic editor's staging script and styles.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Classic
 */

namespace TEC\Tickets\Deferred_Save\Classic;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Deferred_Save\Controller;
use Tribe__Tickets__Main as Tickets_Main;

/**
 * Class Assets.
 *
 * Registers the staging module and its styles in the admin group the tickets metabox uses, and
 * enqueues them only on the edit screen of a post that uses deferred save.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Classic
 */
class Assets extends Controller_Contract {
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
	protected function do_register(): void {
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
					'data' => fn() => [
						'free'         => __( 'Free', 'event-tickets' ),
						'unlimited'    => __( 'Unlimited', 'event-tickets' ),
						'leaveMessage' => __( 'You have ticket changes that are not saved yet. Leave without saving them?', 'event-tickets' ),
					],
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
	 * Whether the current admin screen edits a post that uses deferred save.
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

		return $post_id > 0 && $this->container->get( Controller::class )->uses_deferred_save( $post_id );
	}
}
