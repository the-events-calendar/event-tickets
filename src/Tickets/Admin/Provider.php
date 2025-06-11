<?php
/**
 * The main service provider for the Tickets Admin area.
 *
 * @since 5.3.4
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin;

use Tribe__Tickets__Admin__Views as Admin_Views;

/**
 * Service provider for the Tickets Admin area.
 *
 * @since 5.3.4
 * @package TEC\Tickets\Admin
 */
class Provider extends \TEC\Common\Contracts\Service_Provider {

	/**
	 * Register the provider singletons.
	 *
	 * @since 5.3.4
	 */
	public function register() {

		$this->register_hooks();

		// Register the SP on the container.
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'tickets.admin.provider', $this );

		// Register the Attendees provider.
		$this->container->register( Attendees\Provider::class );

		// Register the All Tickets provider.
		$this->container->register( Tickets\Provider::class );

		// Register the Onboarding wizard controller.
		$this->container->register( Onboarding\Controller::class );

		// Register singleton classes.
		$this->container->singleton( Plugin_Action_Links::class );
		$this->container->singleton( Glance_Items::class );

		add_action( 'tribe_template_before_include:tickets/admin-views/editor/panel/fields/dates', [ $this, 'render_default_ticket_type_header' ], 10, 3 );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for the Tickets Admin area.
	 *
	 * @since 5.3.4
	 */
	protected function register_hooks() {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having them registered to the container.
		$this->container->singleton( Hooks::class, $hooks );
		$this->container->singleton( 'tickets.admin.hooks', $hooks );
	}

	/**
	 * Render the default ticket type header.
	 *
	 * @since 5.8.0
	 *
	 * @param string        $file         The file being rendered.
	 * @param array<string> $name         The components of the name of the template being filtered..
	 * @param Admin_Views   $admin_views  The admin views instance.
	 */
	public function render_default_ticket_type_header( string $file, array $name, Admin_Views $admin_views ): void {
		$context = $admin_views->get_values();

		$post_id     = $context['post_id'] ?? '';
		$ticket_type = $context['ticket_type'] ?? '';

		if ( 'default' !== $ticket_type || empty( $post_id ) ) {
			return;
		}

		$post_type_object = get_post_type_object( get_post_type( $post_id ) );
		$post_type        = strtolower( get_post_type_labels( $post_type_object )->{'singular_name'} );

		$description = sprintf(
			// Translators: %1$s is the ticket type label_lowercase, %2$s is the post type label.
			_x(
				'A %1$s is specific to this %2$s.',
				'The help text for the default ticket type in the ticket form.',
				'event-tickets'
			),
			tec_tickets_get_default_ticket_type_label_lowercase( 'admin_ticket_type_help_text' ),
			$post_type
		);

		/**
		 * Allows for the modification of the default ticket type header description.
		 *
		 * Note the description will be passed through `wp_kses` with support for anchor tags.
		 *
		 * @since 5.8.0
		 *
		 * @param string $description The default description.
		 * @param int    $post_id     The ID of the post the ticket is being added to, or rendered for.
		 */
		$description = apply_filters( 'tec_tickets_ticket_type_default_header_description', $description, $post_id );

		$admin_views->template( 'editor/ticket-type-default-header', [
			'description' => $description,
		] );
	}
}
