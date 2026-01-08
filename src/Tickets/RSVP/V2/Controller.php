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
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\RSVP\RSVP_Controller_Methods;
use TEC\Tickets\Settings;
use Tribe__Tickets__Editor__Template as Tickets_Editor_Template;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Post;
use Tribe__Tickets__Tickets as Tickets_Handler;
use Tribe__Tickets__RSVP as RSVP_V1_Tickets_Handler;

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
		$block_editor = $this->container->get( Block_Editor::class );
		$block_editor->register();
		$this->container->singleton( Frontend::class );
		$this->container->singleton( Repository_Filters::class );
		$this->container->singleton( REST\Order_Endpoint::class );
		$this->container->singleton( Cart\RSVP_Cart::class );
		$this->container->singleton( Meta_Fields::class );
		$this->container->singleton( REST_Properties::class );

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
		add_action(
			'tec_event_tickets_rsvp_form__start',
			$this->container->callback( Metabox::class, 'display_responses_info' ),
			10,
			3
		);
		// Reposition the RSVP metabox to sit directly after the Tickets metabox.
		// Runs late (priority 100) so every metabox is registered before reordering.
		add_action( 'add_meta_boxes', $this->container->callback( Metabox::class, 'reorder_after_tickets_metabox' ), 100 );
		add_filter(
			'tec_tickets_enabled_ticket_forms',
			$this->container->callback( Classic_Editor::class, 'do_not_render_rsvp_form_toggle' )
		);
		add_filter(
			'tec_tickets_editor_list_ticket_types',
			$this->container->callback( Classic_Editor::class, 'do_not_show_rsvp_in_tickets_metabox' )
		);
		add_action(
			'save_post',
			$this->container->callback( Classic_Editor::class, 'save_rsvp_on_post_save' ),
			20
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
		// Load in editor chrome (WP < 6.3 non-iframe) AND canvas iframe (WP 6.3+).
		add_action(
			'enqueue_block_editor_assets',
			$this->container->callback( Block_Editor::class, 'enqueue_rsvp_block_editor_styles' ),
			20
		);
		add_action(
			'enqueue_block_assets',
			$this->container->callback( Block_Editor::class, 'enqueue_rsvp_block_editor_styles' ),
			20
		);

		// Frontend.
		add_action( 'wp_enqueue_scripts', $this->container->callback( Frontend::class, 'enqueue_rsvp_assets' ) );
		add_filter(
			'tec_tickets_front_end_rsvp_form_template_content',
			$this->container->callback( Frontend::class, 'render_rsvp_template' ),
			10,
			5
		);

		add_action(
			'event_tickets_attendee_update',
			$this->container->callback( Frontend::class, 'update_attendee_data' ),
			10,
			2
		);
		add_action(
			'tec_tickets_my_tickets_ticket_information_after_ticket_name',
			$this->container->callback( Frontend::class, 'render_my_tickets_ticket_status' )
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
		add_action( 'rest_api_init', $this->container->callback( REST\Order_Endpoint::class, 'register' ) );

		// RSVP-specific meta saving.
		add_action(
			'tec_tickets_commerce_after_save_ticket',
			$this->container->callback( Meta_Fields::class, 'save_show_not_going' ),
			10,
			3
		);

		// Add show_not_going property to REST responses for RSVP tickets.
		add_filter(
			'tec_tickets_build_ticket_properties',
			$this->container->callback( REST_Properties::class, 'add_show_not_going_to_properties' ),
			10,
			2
		);
		add_filter(
			'tec_rest_ticket_properties_to_add',
			$this->container->callback( REST_Properties::class, 'add_show_not_going_to_rest_properties' )
		);

		// Add show_not_going to REST API documentation.
		add_filter(
			'tec_rest_swagger_ticket_request_body_definition',
			$this->container->callback( REST_Properties::class, 'add_show_not_going_to_request_body_docs' )
		);
		add_filter(
			'tec_rest_swagger_ticket_definition',
			$this->container->callback( REST_Properties::class, 'add_show_not_going_to_response_docs' )
		);

		add_filter(
			'tec_tickets_rsvp_get_attendees_by_id_pre',
			$this->container->callback( Attendees::class, 'get_rsvp_attendees_by_id' ),
			10,
			2
		);

		// Attendees report: show Going/Not Going status and hide check-in for "not going" RSVPs.
		add_filter(
			'tribe_tickets_attendees_table_order_status',
			$this->container->callback( Attendees::class, 'modify_status_display' ),
			10,
			2
		);
		add_filter(
			'tec_tickets_attendees_table_column_check_in',
			$this->container->callback( Attendees::class, 'modify_checkin_display' ),
			10,
			2
		);
		add_filter(
			'event_tickets_attendees_table_row_actions',
			$this->container->callback( Attendees::class, 'modify_row_actions' ),
			10,
			2
		);

		add_action(
			'tec_tickets_commerce_single_order_details_metabox_after',
			$this->container->callback( Metabox::class, 'add_rsvp_status_to_single_order_details_metabox' )
		);

		// Void the hidden TC-RSVP Order once its last Attendee is deleted.
		add_action(
			'tec_tickets_commerce_attendee_before_delete',
			$this->container->callback( Attendees::class, 'void_order_after_last_attendee_deleted' )
		);

		add_action(
			'tec_tickets_commerce_single_order_details_metabox_after',
			$this->container->callback( Metabox::class, 'add_rsvp_status_to_single_order_details_metabox' )
		);

		add_filter( 'tribe_template_done', [ $this, 'prevent_template_render' ], 10, 2 );

		// Add V2 RSVP configuration to the block editor.
		add_filter( 'tribe_editor_config', [ $this, 'add_rsvp_v2_editor_config' ] );

		add_filter( 'tec_tickets_editor_list_tickets', [ $this, 'exclude_rsvp_from_tickets_list' ], 10, 2 );
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
		remove_action(
			'tec_event_tickets_rsvp_form__start',
			$this->container->callback( Metabox::class, 'display_responses_info' ),
			10
		);
		remove_action( 'add_meta_boxes', $this->container->callback( Metabox::class, 'reorder_after_tickets_metabox' ), 100 );
		remove_filter(
			'tec_tickets_enabled_ticket_forms',
			$this->container->callback( Classic_Editor::class, 'do_not_render_rsvp_form_toggle' )
		);
		remove_filter(
			'tec_tickets_editor_list_ticket_types',
			$this->container->callback( Classic_Editor::class, 'do_not_show_rsvp_in_tickets_metabox' )
		);
		remove_action(
			'save_post',
			$this->container->callback( Classic_Editor::class, 'save_rsvp_on_post_save' ),
			20
		);
		remove_filter(
			'tribe_editor_config',
			$this->container->callback( Block_Editor::class, 'add_rsvp_v2_editor_config' )
		);
		remove_filter(
			'pre_render_block',
			$this->container->callback( Block_Editor::class, 'enqueue_tickets_block_assets' )
		);
		remove_filter(
			'register_block_type_args',
			$this->container->callback( Block_Editor::class, 'add_rsvp_block_editor_style_args' ),
			10
		);
		remove_action(
			'enqueue_block_editor_assets',
			$this->container->callback( Block_Editor::class, 'enqueue_rsvp_block_editor_styles' ),
			20
		);
		remove_action(
			'enqueue_block_assets',
			$this->container->callback( Block_Editor::class, 'enqueue_rsvp_block_editor_styles' ),
			20
		);
		remove_action( 'wp_enqueue_scripts', $this->container->callback( Frontend::class, 'enqueue_rsvp_assets' ) );
		remove_filter(
			'tec_tickets_front_end_rsvp_form_template_content',
			$this->container->callback( Frontend::class, 'render_rsvp_template' )
		);
		remove_action(
			'event_tickets_attendee_update',
			$this->container->callback( Frontend::class, 'update_attendee_data' ),
		);
		remove_action(
			'tec_tickets_my_tickets_ticket_information_after_ticket_name',
			$this->container->callback( Frontend::class, 'render_my_tickets_ticket_status' ),
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
		remove_action( 'rest_api_init', $this->container->callback( REST\Order_Endpoint::class, 'register' ) );
		remove_action(
			'tec_tickets_commerce_after_save_ticket',
			$this->container->callback( Meta_Fields::class, 'save_show_not_going' )
		);

		remove_filter(
			'tec_tickets_build_ticket_properties',
			$this->container->callback( REST_Properties::class, 'add_show_not_going_to_properties' )
		);
		remove_filter(
			'tec_rest_ticket_properties_to_add',
			$this->container->callback( REST_Properties::class, 'add_show_not_going_to_rest_properties' )
		);
		remove_filter(
			'tec_rest_swagger_ticket_request_body_definition',
			$this->container->callback( REST_Properties::class, 'add_show_not_going_to_request_body_docs' )
		);
		remove_filter(
			'tec_rest_swagger_ticket_definition',
			$this->container->callback( REST_Properties::class, 'add_show_not_going_to_response_docs' )
		);
		remove_filter(
			'tec_tickets_rsvp_get_attendees_by_id_pre',
			$this->container->callback( Attendees::class, 'get_rsvp_attendees_by_id' )
		);
		remove_filter(
			'tribe_tickets_attendees_table_order_status',
			$this->container->callback( Attendees::class, 'modify_status_display' )
		);
		remove_filter(
			'tec_tickets_attendees_table_column_check_in',
			$this->container->callback( Attendees::class, 'modify_checkin_display' )
		);
		remove_filter(
			'event_tickets_attendees_table_row_actions',
			$this->container->callback( Attendees::class, 'modify_row_actions' )
		);
		remove_action(
			'tec_tickets_commerce_single_order_details_metabox_after',
			$this->container->callback( Metabox::class, 'add_rsvp_status_to_single_order_details_metabox' ),
		);
		remove_action(
			'tec_tickets_commerce_attendee_before_delete',
			$this->container->callback( Attendees::class, 'void_order_after_last_attendee_deleted' )
		);
		remove_action(
			'tec_tickets_commerce_single_order_details_metabox_after',
			$this->container->callback( Metabox::class, 'add_rsvp_status_to_single_order_details_metabox' ),
		);
	}

	/**
	 * Filters the fields rendered in the Payments tab to replace the toggle to deactivate Tickets Commerce
	 * with one that will not allow the user to do that.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $fields The fields to render in the tab.
	 *
	 * @return array<string,mixed> The filtered fields to render in the tab.
	 */
	public function change_tickets_commerce_settings( array $fields ): array {
		if ( ! isset( $fields['tec-settings-payment-enable'] ) ) {
			return $fields;
		}

		$is_tickets_commerce_enabled = tec_tickets_commerce_is_enabled();

		$fields['tec-settings-payment-enable'] = [
			'type' => 'html',
			'html' => '<div>
                  <input
                      type="hidden"
                      name="' . Settings::$tickets_commerce_enabled . '"
                      ' . checked( $is_tickets_commerce_enabled, true, false ) . '
                      id="tickets-commerce-enable-input"
                      class="tribe-dependency tribe-dependency-verified">
              </div>
              <h2 class="tec-tickets__admin-settings-tab-heading">' . esc_html__( 'Tickets Commerce',
					'event-tickets' ) . '</h2>',
		];

		return $fields;
	}

	/**
	 * Filters the enabled form toggles that would render in the default Tickets metabox to
	 * remove the RSVP one.
	 *
	 * @since TBD
	 *
	 * @param array<string,bool> $enabled A map from ticket types to their enabled status.
	 *
	 * @return array<string,bool> The filtered map of ticket types to their enabled status.
	 */
	public function do_not_render_rsvp_form_toggle( array $enabled ): array {
		$enabled['rsvp'] = false;

		return $enabled;
	}

	/**
	 * Filters the list table data to remove the RSVP tickets from the list.
	 *
	 * @since TBD
	 *
	 * @param array<string,array<Ticket_Object>> $ticket_types The ticket types and their tickets.
	 *
	 * @return array<string,array<Ticket_Object>> The filtered ticket types and their tickets.
	 */
	public function do_not_list_rsvp_tickets( array $ticket_types ): array {
		$ticket_types[ Constants::TC_RSVP_TYPE ] = [];

		return $ticket_types;
	}

	/**
	 * Render TC-RSVP template for TC-RSVP tickets on the frontend.
	 *
	 * Hooks into `tec_tickets_front_end_ticket_form_template_content` to render
	 * the V2 commerce RSVP template instead of the generic ticket form template.
	 *
	 * @since TBD
	 *
	 * @param string                  $content  The template content to be rendered.
	 * @param Ticket_Object|null      $rsvp     The RSVP ticket object or null.
	 * @param Tickets_Editor_Template $template The template object.
	 * @param WP_Post                 $post     The post object.
	 * @param bool                    $echo     Whether to echo the output.
	 *
	 * @return string The modified HTML or original if not TC-RSVP.
	 */
	public function render_rsvp_template(
		string $content,
		?Ticket_Object $rsvp,
		Tickets_Editor_Template $template,
		WP_Post $post,
		bool $echo
	): string {
		// Only process if we have an RSVP object.
		if ( $rsvp === null || $rsvp->type() !== Constants::TC_RSVP_TYPE ) {
			return $content;
		}

		// Create the RSVP template args.
		$rsvp_template_args = [
			'rsvp'          => $rsvp,
			'post_id'       => $post->ID,
			'block_html_id' => Constants::TC_RSVP_TYPE . uniqid( '', true ),
			'step'          => '',
			'active_rsvps'  => $rsvp && $rsvp->date_in_range() ? [ $rsvp ] : [],
			'must_login'    => ! is_user_logged_in() && $this->login_required(),
		];

		// Render the RSVP template and append to existing content.
		$content .= $template->template( 'v2/commerce/rsvp', $rsvp_template_args, $echo );

		return $content;
	}

	/**
	 * Enqueue RSVP assets on the frontend.
	 *
	 * Assets are only enqueued when viewing a single post/event that has TC-RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function enqueue_rsvp_assets(): void {
		// Only enqueue on singular posts
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_the_ID();

		// Only enqueue if the post has TC-RSVP tickets
		if ( ! $this->post_has_tc_rsvp_tickets( $post_id ) ) {
			return;
		}

		// Enqueue the asset group
		tribe_asset_enqueue_group( 'tec-tickets-commerce-rsvp' );
	}

	/**
	 * Check if a post has TC-RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID to check.
	 *
	 * @return bool True if the post has TC-RSVP tickets, false otherwise.
	 */
	protected function post_has_tc_rsvp_tickets( int $post_id ): bool {
		$module = $this->container->make( Module::class );
		$tickets = $module->get_tickets( $post_id );

		foreach ( $tickets as $ticket ) {
			$ticket_type = get_post_meta( $ticket->ID, '_type', true );

			if ( Constants::TC_RSVP_TYPE === $ticket_type ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns whether the RSVP form requires login.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the RSVP form requires login.
	 */
	private function login_required(): bool {
		$requirements = (array) tribe_get_option( 'ticket-authentication-requirements', [] );

		return in_array( 'event-tickets_rsvp', $requirements, true );
	}

	/**
	 * Removes the RSVP hooks that would render the RSVP v1 form on the frontend.
	 *
	 * The original code hooks as part of the construction, to avoid having to update all the existing code
	 * unhook the RSVP v1 hooks right after they are added.
	 *
	 * @since TBD
	 *
	 * @param Tickets_Handler $tickets_handler  The tickets handler instance.
	 * @param string          $ticket_form_hook The ticket form hook.
	 *
	 * @return void
	 */
	public function do_not_display_rsvp_v1_tickets_form( Tickets_Handler $tickets_handler, string $ticket_form_hook ): void {
		if ( ! $tickets_handler instanceof RSVP_V1_Tickets_Handler ) {
			return;
		}

		remove_action( $ticket_form_hook, [ $tickets_handler, 'maybe_add_front_end_tickets_form' ], 5 );
		remove_filter( $ticket_form_hook, [ $tickets_handler, 'show_tickets_unavailable_message' ], 6 );
		remove_filter( 'the_content', [ $tickets_handler, 'front_end_tickets_form_in_content' ], 11 );
		remove_filter( 'the_content', [ $tickets_handler, 'show_tickets_unavailable_message_in_content' ], 12 );
	}

	/**
	 * Filters the method checking whether some thing is a ticket or not.
	 *
	 * @since TBD
	 *
	 * @param bool                $is_ticket Whether the thing is a ticket or not.
	 * @param array<string,mixed> $thing     The thing to check.
	 *
	 * @return bool
	 */
	public function rsvp_ticket_is_ticket( bool $is_ticket, array $thing ): bool {
		if ( $is_ticket ) {
			// Already identified as a ticket, nothing to do here.
			return true;
		}

		return isset( $thing['type'] ) && $thing['type'] === Constants::TC_RSVP_TYPE;
	}

	/**
	 * Filters the attendee count to exclude the RSVP tickets depending on the context of the count.
	 *
	 * @since TBD
	 *
	 * @param array $args    {
	 *      List of arguments to filter attendees by.
	 *
	 *      @type array $by          List of ORM->by() filters to use. [what=>[args...]], [what=>arg], or
	 *                               [[what,args...]] format.
	 *      @type array $where_multi List of ORM->where_multi() filters to use. [[what,args...]] format.
	 * }
	 * @param int   $event_id   The Event ID we're checking.
	 * @param int   $user_id    An Optional User ID.
	 * @param string $context    The Context of the call, used to filter the attendees count.
	 *
	 * @return array $args    {
	 *      List of arguments to filter attendees by.
	 *
	 *      @type array $by          List of ORM->by() filters to use. [what=>[args...]], [what=>arg], or
	 *                               [[what,args...]] format.
	 *      @type array $where_multi List of ORM->where_multi() filters to use. [[what,args...]] format.
	 * }
	 */
	public function exclude_rsvp_tickets_from_attendee_count( array $args, int $event_id, int $user_id, string $context ): array {
		if ( ! in_array( $context, [
			'get_description_rsvp_ticket',
			'get_my_tickets_link_data',
		], true ) ) {
			return $args;
		}

		// Exclude Attendees that have the RSVP ticket type.
		$args['by']['_type'] = [ '!=', Constants::TC_RSVP_TYPE ];

		return $args;
	}

	/**
	 * Prevents the rendering of some RSVP templates in the context of the RSVP v2 implementation.
	 *
	 * @since TBD
	 *
	 * @param string|null     $done Whether the template has been rendered or not.
	 * @param string|string[] $name The template name in the form of a string or an array of strings.
	 *
	 * @return string|null An empty string to prevent template rendering if required, or the original value.
	 */
	public function prevent_template_render( $done, $name ) {
		if ( null !== $done ) {
			return $done;
		}


		$do_not_render = [
			'v2/commerce/rsvp/attendees',
			'v2/commerce/rsvp/attendees/attendee',
			'v2/commerce/rsvp/attendees/attendee/name',
			'v2/commerce/rsvp/attendees/attendee/rsvp',
			'v2/commerce/rsvp/attendees/title',
		];

		if ( in_array( $name, $do_not_render, true ) ) {
			// Return a non-null value to indicate the template was done.
			return '';
		}

		return $done;
	}

	/**
	 * Add V2 RSVP configuration to the block editor config.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $config The editor configuration.
	 *
	 * @return array<string,mixed> The modified editor configuration.
	 */
	public function add_rsvp_v2_editor_config( array $config ): array {
		$config['tickets']           = $config['tickets'] ?? [];
		$config['tickets']['rsvpV2'] = [
			'enabled'         => true,
			'ticketsEndpoint' => '/tec/v1/tickets',
			'ticketType'      => Constants::TC_RSVP_TYPE,
		];

		return $config;
	}

	/**
	 * Excludes RSVP v2 tickets from the tickets list meta used by the Tickets block.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object[] $tickets        The array of ticket objects.
	 * @param int                             $unused_post_id The post ID.
	 *
	 * @return Tribe__Tickets__Ticket_Object[] Filtered array with tc-rsvp tickets removed.
	 */
	public function exclude_rsvp_from_tickets_list( array $tickets, int $unused_post_id ): array {
		return array_filter(
			$tickets,
			static fn( $ticket ) => Constants::TC_RSVP_TYPE !== $ticket->type()
		);
	}
}
