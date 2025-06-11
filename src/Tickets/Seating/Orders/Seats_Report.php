<?php
/**
 * Seats Report class.
 */

namespace TEC\Tickets\Seating\Orders;

use TEC\Tickets\Commerce\Reports\Report_Abstract;
use TEC\Tickets\Commerce\Reports\Tabbed_View;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Service\Error_Content;
use TEC\Tickets\Seating\Service\Service;
use TEC\Tickets\Seating\Service\Service_Status;
use Tribe__Main;
use Tribe__Tickets__Tickets;
use WP_Error;
use WP_Post;
use Tribe__Tickets__Main as Tickets_Main;

/**
 * Class Seats_Tab.
 *
 * @since 5.16.0
 *
 * @package TEC/Tickets/Seating/Orders
 */
class Seats_Report extends Report_Abstract {
	/**
	 * Slug of the admin page for orders
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public static $page_slug = 'tec-tickets-seats';

	/**
	 * @var string
	 */
	public static $tab_slug = 'tec-tickets-seats-report';

	/**
	 * The action to register the assets for the report.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	public static $asset_action = 'tec-tickets-seats-report-assets';

	/**
	 * Order Pages ID on the menu.
	 *
	 * @since 5.16.0
	 *
	 * @var string The menu slug of the orders page
	 */
	public $seats_page;

	/**
	 * Hooks the actions and filter required by the class.
	 *
	 * @since 5.16.0
	 */
	public function register_tab() {
		// Register the tabbed view.
		$tc_tabbed_view = new Tabbed_View();
		$tc_tabbed_view->set_active( self::$tab_slug );
		$tc_tabbed_view->register();
	}

	/**
	 * Registers the Seats page among those the tabbed view should render.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function register_seats_page() {
		$page_title       = __( 'Seats', 'event-tickets' );
		$this->seats_page = add_submenu_page(
			'',
			$page_title,
			$page_title,
			'edit_posts',
			static::$page_slug,
			[ $this, 'render_page' ]
		);

		add_action( 'load-' . $this->seats_page, [ $this, 'screen_setup' ] );
	}

	/**
	 * Screen setup.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function screen_setup(): void {
		do_action( self::$asset_action );
	}

	/**
	 * Renders the order page
	 *
	 * @since 5.16.0
	 */
	public function render_page() {
		$tc_tabbed_view = new Tabbed_View();
		$tc_tabbed_view->set_active( self::$tab_slug );
		$tc_tabbed_view->render();

		$service        = tribe( Service::class );
		$service_status = $service->get_status();

		if ( $this->should_show_upsell() ) {
			$this->get_template()->template( 'seats-upsell' );

			return;
		}

		if ( ! $service_status->is_ok() ) {
			tribe( Error_Content::class )->render_tab( $service_status );

			return;
		}

		$this->get_template()->template( 'seats', $this->get_template_vars() );
	}

	/**
	 * Sets up the template variables used to render the Seats Report Page.
	 *
	 * @since 5.16.0
	 *
	 * @return array<string, mixed> The template variables.
	 */
	public function setup_template_vars(): array {
		$post_id = tribe_get_request_var( 'post_id' );
		$post_id = tribe_get_request_var( 'event_id', $post_id );
		$post    = get_post( $post_id );

		$ephemeral_token     = tribe( Service::class )->get_ephemeral_token( 6 * HOUR_IN_SECONDS, 'admin' );
		$token               = is_string( $ephemeral_token ) ? $ephemeral_token : '';
		$this->template_vars = [
			'post'       => $post,
			'post_id'    => $post_id,
			'iframe_url' => tribe( Service::class )->get_seat_report_url( $token, $post_id ),
			'token'      => $token,
			'error'      => $ephemeral_token instanceof WP_Error ? $ephemeral_token->get_error_message() : '',
		];

		return $this->template_vars;
	}

	/**
	 * Get the report link.
	 *
	 * @since 5.16.0
	 *
	 * @param WP_Post $post The Post object.
	 */
	public static function get_link( WP_Post $post ): string {
		return add_query_arg(
			[
				'post_type' => $post->post_type,
				'page'      => static::$page_slug,
				'post_id'   => $post->ID,
			],
			admin_url( 'edit.php' )
		);
	}

	/**
	 * Include seats action row.
	 *
	 * @since 5.16.0
	 *
	 * @param array<string,string> $actions The action items.
	 * @param WP_Post              $post The post object.
	 *
	 * @return array<string,string> The action items.
	 */
	public function add_seats_row_action( $actions, $post ): array {
		$post_id     = Tribe__Main::post_id_helper( $post );
		$slr_enabled = get_post_meta( $post_id, Meta::META_KEY_ENABLED, true );

		if ( ! $slr_enabled ) {
			return $actions;
		}

		$post = get_post( $post_id );

		if ( ! in_array( $post->post_type, Tickets_Main::instance()->post_types(), true ) ) {
			return $actions;
		}

		if ( ! $this->can_access_page( $post_id ) ) {
			return $actions;
		}
		
		$tickets = Tribe__Tickets__Tickets::get_ticket_counts( $post_id );

		if ( ! $tickets ) {
			return $actions;
		}
		
		$provider      = Tribe__Tickets__Tickets::get_event_ticket_provider_object( $post_id );
		$has_attendees = $provider->get_attendees_count( $post_id );

		if ( ! $has_attendees ) {
			return $actions;
		}

		$url         = self::get_link( $post );
		$post_labels = get_post_type_labels( get_post_type_object( $post->post_type ) );
		$post_type   = strtolower( $post_labels->singular_name );

		$actions['tickets_seats'] = sprintf(
			'<a title="%s" href="%s">%s</a>',
			sprintf(
				/* translators: %s: post type */
				esc_html__( 'See seats purchased for this %s', 'event-tickets' ),
				$post_type
			),
			esc_url( $url ),
			esc_html__( 'Seats', 'event-tickets' )
		);

		return $actions;
	}

	/**
	 * Returns whether the upsell should show or not.
	 *
	 * @since 5.16.0
	 *
	 * @return bool Whether the upsell should show or not.
	 */
	protected function should_show_upsell(): bool {
		$service_status = tribe( Service::class )->get_status();

		/**
		 * Filters whether the upsell should be shown in the Seats report tab.
		 *
		 * @since 5.16.0
		 *
		 * @param bool            $should_show_upsell Whether the upsell should be shown.
		 * @param Service_Status  $service_status     The seating service's status.
		 */
		return apply_filters(
			'tec_tickets_seating_should_show_upsell',
			$service_status->is_license_invalid() || $service_status->has_no_license(),
			$service_status
		);
	}
}
