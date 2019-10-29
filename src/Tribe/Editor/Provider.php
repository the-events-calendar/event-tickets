<?php
/**
 * Register Event Tickets provider
 *
 * @since 4.9
 */
class Tribe__Tickets__Editor__Provider extends tad_DI52_ServiceProvider {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since 4.9
	 *
	 */
	public function register() {
		// Register these all the time - as we now use them in most of the tempaltes, blocks or otherwise.
		$this->container->singleton( 'tickets.editor.template', 'Tribe__Tickets__Editor__Template' );
		$this->container->singleton( 'tickets.editor.blocks.tickets', 'Tribe__Tickets__Editor__Blocks__Tickets' );

		if (
			! tribe( 'editor' )->should_load_blocks()
			|| ! class_exists( 'Tribe__Tickets__Main' )
		) {
			return;
		}

		$this->container->singleton( 'tickets.editor.template.overwrite', 'Tribe__Tickets__Editor__Template__Overwrite', array( 'hook' ) );

		$this->container->singleton(
			'tickets.editor.compatibility.tickets',
			'Tribe__Tickets__Editor__Compatibility__Tickets',
			array( 'hook' )
		);

		$this->container->singleton( 'tickets.editor.assets', 'Tribe__Tickets__Editor__Assets', array( 'register' ) );

		$this->container->singleton( 'tickets.editor.blocks.tickets-item', 'Tribe__Tickets__Editor__Blocks__Tickets_Item' );
		$this->container->singleton( 'tickets.editor.blocks.rsvp', 'Tribe__Tickets__Editor__Blocks__Rsvp' );
		$this->container->singleton( 'tickets.editor.blocks.attendees', 'Tribe__Tickets__Editor__Blocks__Attendees' );

		$this->container->singleton( 'tickets.editor.meta', 'Tribe__Tickets__Editor__Meta' );
		$this->container->singleton( 'tickets.editor.rest.compatibility', 'Tribe__Tickets__Editor__REST__Compatibility', array( 'hook' ) );
		$this->container->singleton( 'tickets.editor.attendee_registration', 'Tribe__Tickets__Editor__Attendee_Registration' );
		$this->container->singleton( 'tickets.editor.attendees_table', 'Tribe__Tickets__Attendees_Table' );
		$this->container->singleton( 'tickets.editor.configuration', 'Tribe__Tickets__Editor__Configuration', array( 'hook' ) );

		$this->hook();
		/**
		 * Lets load all compatibility related methods
		 *
		 * @todo remove once RSVP and tickets blocks are completed
		 */
		$this->load_compatibility_tickets();

		// Initialize the correct Singleton
		tribe( 'tickets.editor.assets' );
		tribe( 'tickets.editor.configuration' );
		tribe( 'tickets.editor.template.overwrite' );
	}

	/**
	 * Any hooking any class needs happen here.
	 *
	 * In place of delegating the hooking responsibility to the single classes they are all hooked here.
	 *
	 * @since 4.9
	 *
	 */
	protected function hook() {
		// Setup the Meta registration
		add_action( 'init', tribe_callback( 'tickets.editor.meta', 'register' ), 15 );
		add_filter(
			'register_meta_args',
			tribe_callback( 'tickets.editor.meta', 'register_meta_args' ),
			10,
			4
		);

		add_filter(
			'get_post_metadata',
			tribe_callback( 'tickets.editor.meta', 'register_tickets_list_in_rest' ),
			15,
			4
		);

		// Setup the Rest compatibility layer for WP
		tribe( 'tickets.editor.rest.compatibility' );

		tribe( 'tickets.editor.attendee_registration' )->hook();

		// Register blocks
		add_action(
			'tribe_editor_register_blocks',
			tribe_callback( 'tickets.editor.blocks.rsvp', 'register' )
		);

		add_action(
			'tribe_editor_register_blocks',
			tribe_callback( 'tickets.editor.blocks.tickets', 'register' )
		);

		add_action(
			'tribe_editor_register_blocks',
			tribe_callback( 'tickets.editor.blocks.tickets-item', 'register' )
		);

		add_action(
			'tribe_editor_register_blocks',
			tribe_callback( 'tickets.editor.blocks.attendees', 'register' )
		);

		add_action(
			'block_categories',
			tribe_callback( 'tickets.editor', 'block_categories' )
		);
	}

	/**
	 * Initializes the correct classes for when Tickets is active.
	 *
	 * @since 4.9
	 *
	 * @return bool
	 */
	private function load_compatibility_tickets() {
		tribe( 'tickets.editor.compatibility.tickets' );
		return true;
	}

	/**
	 * Binds and sets up implementations at boot time.
	 *
	 * @since 4.9
	 */
	public function boot() {}
}
