<?php
/**
 * Handles the registration of all the Blocks managed by the plugin.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Blocks;
 */

namespace TEC\Tickets\Blocks;

use Tribe\Tickets\Editor\Warnings;
use Tribe__Tickets__Admin__Views as Admin_Views;
use Tribe__Tickets__Attendees_Table as Attendees_Table;
use Tribe__Tickets__Editor__Assets as Assets;
use Tribe__Tickets__Editor__Blocks__Attendees as Attendees_Block;
use Tribe__Tickets__Editor__Blocks__Rsvp as RSVP_Block;
use TEC\Tickets\Blocks\Tickets\Block as Tickets_Block;
use TEC\Tickets\Blocks\Ticket\Block as Ticket_Item_Block;
use Tribe__Tickets__Editor__Configuration as Configuration;
use Tribe__Tickets__Editor__Meta as Meta;
use Tribe__Tickets__Editor__REST__Compatibility as REST_Compatibility;
use Tribe__Tickets__Editor__Template as Template;
use Tribe__Tickets__Editor__Template__Overwrite as Template_Overwrite;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use TEC\Common\StellarWP\Assets\Config;
use Tribe__Tickets__Main as Tickets_Plugin;

/**
 * Class Controller.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Blocks;
 */
class Controller extends \TEC\Common\Contracts\Provider\Controller {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since 4.9
	 */
	public function do_register(): void {
		// Add group path for tickets blocks.
		Config::add_group_path( 'et-tickets-blocks', Tickets_Plugin::instance()->plugin_path . 'build/', 'Tickets/Blocks/' );

		// The general warnings class.
		$this->container->singleton( 'tickets.editor.warnings', Warnings::class, [ 'hook' ] );

		// Register these all the time - as we now use them in most of the templates, blocks or otherwise.
		$this->container->singleton( 'tickets.editor.template.overwrite', Template_Overwrite::class );
		$this->container->singleton( 'tickets.editor.template', Template::class );
		$this->container->singleton( 'tickets.editor.blocks.tickets', Tickets_Block::class, [ 'load' ] );
		$this->container->singleton( 'tickets.editor.blocks.rsvp', RSVP_Block::class, [ 'load' ] );
		$this->container->singleton( 'tickets.editor.blocks.tickets-item', Ticket_Item_Block::class, [ 'load' ] );
		$this->container->singleton( 'tickets.editor.blocks.attendees', Attendees_Block::class, [ 'load' ] );
		$this->container->singleton( 'tickets.editor.configuration', Configuration::class, [ 'hook' ] );

		$this->register_for_blocks();

		if ( wp_doing_ajax() ) {
			// The Tickets Block editor will handle AJAX requests, register now if we're in an AJAX context.
			tribe( 'tickets.editor.blocks.tickets' )->hook();
		}

		// Handle general non-block-specific instances.
		tribe( 'tickets.editor.warnings' );
	}

	/**
	 * Handle registration for blocks-functionality separately.
	 *
	 * @since 5.0.4
	 */
	public function register_for_blocks() {
		/** @var \Tribe__Editor $editor */
		$editor = tribe( 'editor' );

		$this->container->singleton(
			'tickets.editor.compatibility.tickets',
			'Tribe__Tickets__Editor__Compatibility__Tickets',
			[ 'hook' ]
		);

		$this->container->singleton( 'tickets.editor.assets', Assets::class, [ 'register' ] );
		$this->container->singleton( 'tickets.editor.meta', Meta::class );
		$this->container->singleton( 'tickets.editor.rest.compatibility', REST_Compatibility::class, [ 'hook' ] );
		$this->container->singleton( 'tickets.editor.attendees_table', Attendees_Table::class );

		$this->hook();

		/**
		 * Lets load all compatibility related methods
		 *
		 * @todo remove once RSVP and tickets blocks are completed
		 */
		$this->load_compatibility_tickets();

		// Only register for blocks if we are using them.
		if ( ! $editor->should_load_blocks() ) {
			return;
		}

		// Initialize the correct Singleton.
		tribe( 'tickets.editor.assets' );
		tribe( 'tickets.editor.configuration' );
		tribe( 'tickets.editor.template.overwrite' )->hook();
	}

	/**
	 * Register the blocks after plugins are fully loaded.
	 *
	 * @since 5.3.0
	 * @since 5.8.4 Correctly get post type when creating a new post or page.
	 */
	public function register_blocks() {
		if ( is_admin() ) {
			// In admin context, do not register the blocks if the post type is not ticketable.
			$post_id           = tribe_get_request_var( 'post' );
			$post_type_default = 'post';

			$post_type = $post_id ? get_post_type( $post_id ) : tribe_get_request_var( 'post_type', $post_type_default );

			if ( ! in_array( $post_type, (array) tribe_get_option( 'ticket-enabled-post-types', [] ), true ) ) {
				// Exit if the post type is not ticketable.
				return;
			}
		}

		// Register blocks.
		add_action( 'tribe_editor_register_blocks', [ tribe( 'tickets.editor.blocks.rsvp' ), 'register' ] );
		add_action( 'tribe_editor_register_blocks', [ tribe( 'tickets.editor.blocks.tickets' ), 'register' ] );
		add_action( 'tribe_editor_register_blocks', [ tribe( 'tickets.editor.blocks.tickets-item' ), 'register' ] );
		add_action( 'tribe_editor_register_blocks', [ tribe( 'tickets.editor.blocks.attendees' ), 'register' ] );
	}

	/**
	 * Any hooking any class needs happen here.
	 *
	 * In place of delegating the hooking responsibility to the single classes they are all hooked here.
	 *
	 * @since 4.9
	 */
	protected function hook() {
		// Setup the Meta registration.
		add_action( 'init', tribe_callback( 'tickets.editor.meta', 'register' ), 15 );
		add_filter( 'register_meta_args', tribe_callback( 'tickets.editor.meta', 'register_meta_args' ), 10, 4 );
		add_action( 'tribe_plugins_loaded', [ $this, 'register_blocks' ], 300 );

		// Handle REST specific meta filtering.
		add_filter( 'rest_dispatch_request', tribe_callback( 'tickets.editor.meta', 'filter_rest_dispatch_request' ), 10, 3 );

		// Setup the Rest compatibility layer for WP.
		tribe( 'tickets.editor.rest.compatibility' );

		global $wp_version;
		if ( version_compare( $wp_version, '5.8', '<' ) ) {
			// WP version is less then 5.8.
			add_action( 'block_categories', tribe_callback( 'tickets.editor', 'block_categories' ) );
		} else {
			// WP version is 5.8 or above.
			add_action( 'block_categories_all', tribe_callback( 'tickets.editor', 'block_categories' ) );
		}

		add_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'render_form_toggle_buttons' ] );
		add_action( 'tec_tickets_list_row_edit', [ $this, 'render_ticket_edit_controls' ], 10, 2 );
	}

	/**
	 * Render the New Ticket and New RSVP buttons in the metabox, as appropriate.
	 *
	 * @since 5.8.0
	 *
	 * @param int $post_id The post id.
	 */
	public function render_form_toggle_buttons( $post_id ): void {
		// By default, any ticket-able post type can have tickets and RSVPs.
		$enabled = [
			'default' => true,
			'rsvp'    => true,
		];

		$post_type = get_post_field( 'post_type', $post_id );

		/**
		 * Filters the default ticket forms enabled for a given post type.
		 *
		 * @since 5.8.0
		 *
		 * @param array<string,bool> $enabled The default enabled forms, a map from ticket types to their enabled status.
		 * @param int                $post_id The ID of the post being edited.
		 */
		$enabled = apply_filters( "tec_tickets_enabled_ticket_forms_{$post_type}", $enabled, $post_id );

		if ( ! empty( $enabled['default'] ) ) {
			tribe( Meta::class )->render_ticket_form_toggle( $post_id );
		}
		if ( ! empty( $enabled['rsvp'] ) ) {
			tribe( Meta::class )->render_rsvp_form_toggle( $post_id );
		}
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
	 * Render the ticket edit controls for the ticket list table.
	 *
	 * @since 5.8.0
	 *
	 * @param Ticket_Object $ticket  The ticket object.
	 * @param int|null      $post_id The ID of the post context of the print.
	 */
	public function render_ticket_edit_controls( Ticket_Object $ticket, int $post_id = null ): void {
		if ( $ticket->get_event_id() !== $post_id ) {
			// If the ticket is not associated with the current post, don't render the controls.
			return;
		}

		/** @var Admin_Views $admin_views */
		$admin_views           = tribe( 'tickets.admin.views' );
		$show_duplicate_button = ! function_exists( 'tribe_is_community_edit_event_page' )
								|| ! tribe_is_community_edit_event_page();

		$admin_views->template(
			'editor/list-row/edit',
			[
				'ticket'                => $ticket,
				'show_duplicate_button' => $show_duplicate_button,
			]
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'init', tribe_callback( 'tickets.editor.meta', 'register' ), 15 );
		remove_filter( 'register_meta_args', tribe_callback( 'tickets.editor.meta', 'register_meta_args' ) );
		remove_action( 'tribe_plugins_loaded', [ $this, 'register_blocks' ], 300 );
		remove_filter( 'rest_dispatch_request', tribe_callback( 'tickets.editor.meta', 'filter_rest_dispatch_request' ) );
		remove_action( 'block_categories', tribe_callback( 'tickets.editor', 'block_categories' ) );
		remove_action( 'block_categories_all', tribe_callback( 'tickets.editor', 'block_categories' ) );
		remove_action( 'tribe_events_tickets_new_ticket_buttons', [ $this, 'render_form_toggle_buttons' ] );
		remove_action( 'tec_tickets_list_row_edit', [ $this, 'render_ticket_edit_controls' ] );
	}
}
