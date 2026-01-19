<?php
/**
 * V2 RSVP Controller - TC-based implementation.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\RSVP\RSVP_Controller_Methods;

/**
 * Class Controller
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Controller extends Controller_Contract {
	use RSVP_Controller_Methods;

	/**
	 * The action that will be fired after the successful registration of this controller.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $registration_action = 'tec_tickets_rsvp_v2_registered';

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {

		$this->container->singleton( Metabox::class );
		$this->container->singleton( Classic_Editor::class );
		$this->container->singleton( Block_Editor::class );
		$this->container->singleton( Frontend::class );
		$this->container->singleton( Repository_Filters::class );
		$this->container->singleton( REST\Order_Endpoint::class );
		$this->container->singleton( REST\Ticket_Endpoint::class );
		$this->container->singleton( Cart\RSVP_Cart::class );

		$this->container->get( Assets::class )->register();

		$this->register_common_rsvp_implementations();

		// Bind the repositories as factories to make sure each instance is different.
		$this->container->bind(
			'tickets.ticket-repository.rsvp',
			Repositories\Ticket_Repository::class
		);
		$this->container->bind(
			'tickets.attendee-repository.rsvp',
			Repositories\Attendee_Repository::class
		);

		// Settings.
		add_filter(
			'tec_tickets_commerce_settings_top_level',
			$this->container->callback( Settings::class, 'change_tickets_commerce_settings' )
		);

		// Classic Editor.
		add_action( 'add_meta_boxes', $this->container->callback( Metabox::class, 'add' ) );
		add_filter(
			'tec_tickets_enabled_ticket_forms',
			$this->container->callback( Classic_Editor::class, 'do_not_render_rsvp_form_toggle' )
		);
		add_filter(
			'tec_tickets_editor_list_ticket_types',
			$this->container->callback( Classic_Editor::class, 'do_not_show_rsvp_in_tickets_metabox' )
		);

		// Block Editor.
		add_filter(
			'tribe_editor_config',
			$this->container->callback( Block_Editor::class, 'add_rsvp_v2_editor_config' )
		);
		add_filter(
			'pre_render_block',
			$this->container->callback( Block_Editor::class, 'enqueue_tickets_block_assets' ),
			10,
			2
		);

		// Frontend.
		add_action( 'wp_enqueue_scripts', $this->container->callback( Frontend::class, 'enqueue_rsvp_assets' ) );
		add_filter(
			'tec_tickets_front_end_rsvp_form_template_content',
			$this->container->callback( Frontend::class, 'render_rsvp_template' ),
			10,
			5
		);
		add_filter(
			'tribe_template_done',
			$this->container->callback( Frontend::class, 'prevent_template_render' ),
			10,
			2
		);

		// Repository.
		add_filter(
			'tec_tickets_commerce_repository_ticket_query_args',
			$this->container->callback( Repository_Filters::class, 'exclude_rsvp_tickets_from_repository_queries' ),
			10,
			2
		);
		add_filter(
			'tec_tickets_commerce_is_ticket',
			$this->container->callback( Repository_Filters::class, 'rsvp_are_tickets' ),
			10,
			2
		);
		add_filter(
			'tribe_repository_tc_tickets_query_args',
			$this->container->callback( Repository_Filters::class, 'maybe_include_rsvp_tickets' )
		);

		// REST.
		add_action( 'rest_api_init', [ $this, 'register_rest_endpoints' ] );
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter(
			'tec_tickets_commerce_settings_top_level',
			$this->container->callback( Settings::class, 'change_tickets_commerce_settings' )
		);
		remove_action( 'add_meta_boxes', $this->container->callback( Metabox::class, 'add' ) );
		remove_filter(
			'tec_tickets_enabled_ticket_forms',
			$this->container->callback( Classic_Editor::class, 'do_not_render_rsvp_form_toggle' )
		);
		remove_filter(
			'tec_tickets_editor_list_ticket_types',
			$this->container->callback( Classic_Editor::class, 'do_not_show_rsvp_in_tickets_metabox' )
		);
		remove_filter(
			'tribe_editor_config',
			$this->container->callback( Block_Editor::class, 'add_rsvp_v2_editor_config' )
		);
		remove_filter(
			'pre_render_block',
			$this->container->callback( Block_Editor::class, 'enqueue_tickets_block_assets' )
		);
		remove_action( 'wp_enqueue_scripts', $this->container->callback( Frontend::class, 'enqueue_rsvp_assets' ) );
		remove_filter(
			'tec_tickets_front_end_rsvp_form_template_content',
			$this->container->callback( Frontend::class, 'render_rsvp_template' )
		);
		remove_filter(
			'tribe_template_done',
			$this->container->callback( Frontend::class, 'prevent_template_render' )
		);
		remove_filter(
			'tec_tickets_commerce_repository_ticket_query_args',
			$this->container->callback( Repository_Filters::class, 'exclude_rsvp_tickets_from_repository_queries' )
		);
		remove_filter(
			'tec_tickets_commerce_is_ticket',
			$this->container->callback( Repository_Filters::class, 'rsvp_are_tickets' )
		);
		remove_filter(
			'tribe_repository_tc_tickets_query_args',
			$this->container->callback( Repository_Filters::class, 'maybe_include_rsvp_tickets' )
		);
		remove_action( 'rest_api_init', [ $this, 'register_rest_endpoints' ] );
	}

	/**
	 * Register REST API endpoints.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_rest_endpoints(): void {
		$this->container->make( REST\Order_Endpoint::class )->register();
		$this->container->make( REST\Ticket_Endpoint::class )->register();
	}
}
