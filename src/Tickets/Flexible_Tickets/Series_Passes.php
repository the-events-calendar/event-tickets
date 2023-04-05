<?php
/**
 * Handles the Series Passes integration at different levels.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use tad_DI52_Container;
use TEC\Common\Provider\Controller;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Templates\Admin_Views;

/**
 * Class Series_Passes.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Series_Passes extends Controller {

	/**
	 * A reference to the templates handler.
	 *
	 * @since TBD
	 *
	 * @var Admin_Views
	 */
	private Admin_Views $admin_views;

	/**
	 * Series_Passes constructor.
	 *
	 * since TBD
	 *
	 * @param tad_DI52_Container $container The container instance.
	 * @param Admin_Views $admin_views The templates handler.
	 */
	public function __construct(
		tad_DI52_Container $container,
		Admin_Views $admin_views
	) {
		parent::__construct( $container );
		$this->admin_views = $admin_views;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'render_form_toggle' ] );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'render_form_toggle' ] );
	}

	/**
	 * Adds the toggle to the new ticket form.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void The toggle is added to the new ticket form.
	 */
	public function render_form_toggle( $post_id ): void {
		if ( ! ( is_numeric( $post_id ) && $post_id > 0 ) ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! ( $post instanceof \WP_Post && $post->post_type === Series_Post_Type::POSTTYPE ) ) {
			return;
		}

		$this->admin_views->template( 'form-toggle', [
			'disabled' => false,
		] );
	}
}