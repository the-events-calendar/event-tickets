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
use Tribe__Repository__Interface as Repository_Interface;
use WP_Query;

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
		$this->container->singleton( Constants::class );
		$this->container->register( Assets::class );
		$this->container->singleton( Metabox::class );

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

		$this->container->singleton( REST\Order_Endpoint::class );
		$this->container->singleton( REST\Ticket_Endpoint::class );
		$this->container->singleton( Cart\RSVP_Cart::class );

		add_action( 'add_meta_boxes', [ $this, 'configure' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_endpoints' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_rsvp_assets' ] );

		add_filter( 'tec_tickets_commerce_settings_top_level', [ $this, 'change_tickets_commerce_settings' ] );

		add_filter(
			'tec_tickets_commerce_repository_ticket_query_args',
			[ $this, 'exclude_rsvp_tickets_from_repository_queries' ],
			10,
			2
		);

		// Do not display the "Add RSVP" button in the Classic Editor metabox.
		add_filter( 'tec_tickets_enabled_ticket_forms', [ $this, 'do_not_render_rsvp_form_toggle' ] );
		// Do not show RSVP tickets in the Classic Editor metabox.
		add_filter( 'tec_tickets_editor_list_ticket_types', [ $this, 'do_not_show_rsvp_in_tickets_metabox' ] );

		add_filter( 'tec_tickets_front_end_rsvp_form_template_content', [ $this, 'render_rsvp_template' ], 10, 5 );
		// add_action( 'tribe_tickets_tickets_hook', [ $this, 'do_not_display_rsvp_v1_tickets_form' ], 10, 2 );
		add_filter( 'tribe_template_done', [ $this, 'prevent_template_render' ], 10, 2 );

		// Add V2 RSVP configuration to the block editor.
		add_filter( 'tribe_editor_config', [ $this, 'add_rsvp_v2_editor_config' ] );
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'add_meta_boxes', [ $this, 'configure' ] );
		remove_action( 'rest_api_init', [ $this, 'register_rest_endpoints' ] );
		remove_action( 'wp_enqueue_scripts', [ $this, 'enqueue_rsvp_assets' ] );

		remove_filter( 'tec_tickets_commerce_settings_top_level', [ $this, 'change_tickets_commerce_settings' ] );

		remove_filter(
			'tec_tickets_commerce_repository_ticket_query_args',
			[ $this, 'exclude_rsvp_tickets_from_repository_queries' ]
		);

		// Do not display the "Add RSVP" button in the Classic Editor metabox.
		remove_filter( 'tec_tickets_enabled_ticket_forms', [ $this, 'do_not_render_rsvp_form_toggle' ] );
		// Do not show RSVP tickets in the Classic Editor metabox.
		remove_filter( 'tec_tickets_editor_list_ticket_types', [ $this, 'do_not_show_rsvp_in_tickets_metabox' ] );

		remove_filter( 'tec_tickets_front_end_rsvp_form_template_content', [ $this, 'render_rsvp_template' ] );
		remove_action( 'tribe_tickets_tickets_hook', [ $this, 'do_not_display_rsvp_v1_tickets_form' ] );
		remove_filter( 'tribe_template_done', [ $this, 'prevent_template_render' ] );

		// Add V2 RSVP configuration to the block editor.
		remove_filter( 'tribe_editor_config', [ $this, 'add_rsvp_v2_editor_config' ] );
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

	/**
	 * Configures the RSVP metabox for the given post type.
	 *
	 * @since TBD
	 *
	 * @param string|null $post_type The post type to configure the metabox for.
	 *
	 * @return void
	 */
	public function configure( $post_type = null ): void {
		$this->container->make( Metabox::class )->configure( $post_type );
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
	public function do_not_show_rsvp_in_tickets_metabox( array $ticket_types ): array {
		$ticket_types[ 'rsvp' ] = [];

		return $ticket_types;
	}

	/**
	 * Render V2 RSVP template for TC-RSVP tickets on the frontend.
	 *
	 * Hooks into `tec_tickets_front_end_rsvp_form_template_content` to render
	 * the V2 commerce RSVP template instead of the generic RSVP block template.
	 *
	 * @since TBD
	 *
	 * @param string                  $content  The template content to be rendered.
	 * @param array<string,mixed>     $args     The RSVP block arguments.
	 * @param Tickets_Editor_Template $template The template object.
	 * @param WP_Post                 $post     The post object.
	 * @param bool                    $echo     Whether to echo the output.
	 *
	 * @return string The modified HTML or original if not TC-RSVP.
	 */
	public function render_rsvp_template(
		string $content,
		array $args,
		Tickets_Editor_Template $template,
		WP_Post $post,
		bool $echo
	): string {
		$active_rsvps = $args['active_rsvps'] ?? [];

		// Find the first TC-RSVP ticket in the active RSVPs.
		$rsvp = null;
		foreach ( $active_rsvps as $ticket ) {
			if ( $ticket->type() === Constants::TC_RSVP_TYPE ) {
				$rsvp = $ticket;
				break;
			}
		}

		// Only process if we have a TC-RSVP ticket.
		if ( $rsvp === null ) {
			return $content;
		}

		$rsvp_template_args = [
			'rsvp'          => $rsvp,
			'post_id'       => $post->ID,
			'block_html_id' => Constants::TC_RSVP_TYPE . uniqid( '', true ),
			'step'          => '',
			'active_rsvps'  => $rsvp->date_in_range() ? [ $rsvp ] : [],
			'must_login'    => ! is_user_logged_in() && $this->login_required(),
		];

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
	 * Filters the Tickets Commerce repository query args to exclude RSVP tickets from the list.
	 *
	 * @since TBD
	 *
	 * @param Repository_Interface $repository The repository instance, unused.
	 * @param array<string,mixed>  $query_args The query args to be used to fetch the tickets.
	 *
	 * @return array<string,mixed> The modified query args.
	 */
	public function exclude_rsvp_tickets_from_repository_queries( Repository_Interface $repository, array $query_args ): array {
		$query_args['meta_query'] = isset( $query_args['meta_query'] ) && is_array( $query_args['meta_query'] ) ?
			$query_args['meta_query']
			: [];
		$context = $repository->get_request_context();

		// Let's make sure the meta query is not being added twice.
		foreach ( $query_args['meta_query'] as $meta_query ) {
			if (
				isset( $meta_query['key'], $meta_query['value'] )
				&& $meta_query['key'] === '_type'
				&& $meta_query['value'] === Constants::TC_RSVP_TYPE
			) {
				// The meta query has already been filtered to either exclude or include RSVP tickets, bail.
				return $query_args;
			}
		}

		if ( $context === 'front_end_tickets_form' ) {
			// Include RSVP tickets from the list.
			return $query_args;
		}

		// Exclude RSVP tickets from the list.
		$query_args['meta_query'][ Constants::TYPE_META_QUERY_KEY ] = [
			'key'     => '_type',
			'compare' => '!=',
			'value'   => Constants::TC_RSVP_TYPE,
		];

		return $query_args;
	}
}
